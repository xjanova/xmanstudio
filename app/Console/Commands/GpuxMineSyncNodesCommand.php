<?php

namespace App\Console\Commands;

use App\Models\GpuNode;
use App\Services\GpuxMineDispatchService;
use App\Services\GpuxMineHealthService;
use App\Services\GpuxMineNodeStateService;
use App\Services\GpuxMineRelayService;
use Illuminate\Console\Command;

/**
 * ดึงสถานะเครื่องจาก relay แล้วส่งต่อให้ aixman
 *
 * หน้าเว็บ "เครื่องของฉัน" รีเฟรชให้เองตอนเจ้าของเปิดดู แต่การส่งงานต้องไม่
 * รอให้ใครเปิดหน้าไหน: เครื่องที่เพิ่งประเมินตัวเองเสร็จตอนตีสามต้องได้งาน
 * ตอนตีสาม ไม่ใช่ตอนเจ้าของตื่นมาเปิดเว็บ
 *
 * การส่งต่อตัดสินจากสภาพปัจจุบัน ไม่ใช่จาก "รอบนี้เห็นอะไรเปลี่ยน" — ดู
 * GpuxMineNodeStateService::needsPush() เครื่องที่ไม่มีอะไรใหม่และ aixman
 * ตอบรับไปแล้วไม่ถูกยิงซ้ำ นอกจากรอบทวนทุกสิบนาที
 *
 * relay ตอบไม่ได้ = ไม่แตะแถวไหนเลยและจบด้วย FAILURE เคยถือว่า "ไม่มีเครื่อง"
 * แล้วเขียนทุกเครื่องเป็นออฟไลน์ ล้างคะแนน และบอก aixman ให้ถอดทั้งกอง
 *
 * ทุกรอบยังทำให้ relay ตรงกับฐานข้อมูลด้วย: worker ของเครื่องที่ถูกระงับต้องถูกปิดที่
 * relay และเครื่องที่ยกเลิกระงับแล้วต้องเปิด (ปุ่มแอดมินสั่งแค่ครั้งเดียว) และ worker ที่
 * ถอนแล้วแต่ค้างที่ relay รุ่นเก่าถูกลบเมื่อ relay อัปเกรด
 */
class GpuxMineSyncNodesCommand extends Command
{
    protected $signature = 'gpuxmine:sync-nodes {--force : ส่งให้ aixman ทุกเครื่องแม้ไม่มีอะไรเปลี่ยน}';

    protected $description = 'ดึงสถานะเครื่อง GPUxMINE จาก relay แล้วขึ้นทะเบียนรับงานที่ aixman';

    public function handle(
        GpuxMineRelayService $relay,
        GpuxMineDispatchService $dispatch,
        GpuxMineNodeStateService $state,
        GpuxMineHealthService $health,
    ): int {
        // หลักฐานว่า cron เรียกเราจริง — gpuxmine:doctor ดูตรงนี้
        $health->beat(GpuxMineHealthService::TASK_SYNC);

        if (! $relay->isConfigured()) {
            $this->warn('ยังไม่ได้ตั้งค่า GPUXMINE_RELAY_URL / GPUXMINE_RELAY_ADMIN_KEY');

            return self::SUCCESS;   // ยังไม่ตั้งค่า ไม่ใช่ความล้มเหลว
        }

        // เครื่องที่เจ้าของถอนไปแล้วแต่ยังถอนที่ aixman/relay ไม่สำเร็จ
        // ไม่ขึ้นกับรายชื่อจาก relay จึงทำก่อน
        $retired = $state->retryPendingRetirements();

        $live = $relay->workers();
        if ($live === null) {
            $this->error('อ่านรายชื่อเครื่องจาก relay ไม่ได้ — รอบนี้ไม่แตะสถานะเครื่องใด ๆ');

            return self::FAILURE;
        }

        // worker ที่ถอนแล้วแต่ค้างที่ relay รุ่นเก่า — ต้องใช้รายชื่อ จึงทำหลังอ่านรายชื่อได้
        $retired += $state->retryAwaitingRelay($live);

        $total = 0;
        $changed = 0;
        $pushed = 0;
        $gates = 0;
        $failures = 0;
        $gateFailures = 0;
        $aixmanDown = false;

        foreach (GpuNode::paired()->lazyById(100) as $node) {
            $total++;
            $row = $live[$node->worker_id] ?? null;

            if ($state->apply($node, $row)) {
                $changed++;
            }

            // ประตูที่ relay ต้องตรงกับการระงับ — ปุ่มของแอดมินสั่งครั้งเดียว ตรงนี้ตามให้
            // จนตรง relay ปฏิเสธติดกันหลายครั้ง = relay มีปัญหา หยุดสั่งรอบนี้
            if ($gateFailures < GpuxMineNodeStateService::GIVE_UP_AFTER_FAILURES) {
                $gate = $state->reconcileRelayGate($node, $row);
                if ($gate === true) {
                    $gates++;
                    $gateFailures = 0;
                } elseif ($gate === false) {
                    $gateFailures++;
                }
            }

            if ($aixmanDown || ! ($this->option('force') || $state->needsPush($node))) {
                continue;
            }

            $outcome = $dispatch->push($node);
            if ($outcome === GpuxMineDispatchService::OUTCOME_SKIPPED) {
                continue;
            }

            $pushed++;

            if ($outcome === GpuxMineDispatchService::OUTCOME_UNREACHABLE) {
                // aixman ล่ม: ยิงเครื่องที่เหลือทีละ 10 วินาทีไม่ช่วยอะไร และจะลาก
                // รอบนี้ยาวจนชนรอบถัดไป แถวที่ยังไม่ได้ส่งยังค้างเป็น "ต้องส่ง"
                // อยู่แล้ว รอบหน้าหยิบต่อเอง
                if (++$failures >= GpuxMineNodeStateService::GIVE_UP_AFTER_FAILURES) {
                    $aixmanDown = true;
                }
            } else {
                $failures = 0;
            }
        }

        $this->info("เครื่องทั้งหมด {$total} · อัปเดต {$changed} · ส่งให้ aixman {$pushed} · แก้ประตูที่ relay {$gates} · ถอนค้างสำเร็จ {$retired}");

        if ($gateFailures > 0) {
            $this->warn('relay ไม่รับคำสั่งเปิด/ปิด worker ' . $gateFailures . ' ครั้ง — รอบหน้าลองใหม่');
        }

        if ($aixmanDown) {
            $this->error('ติดต่อ aixman ไม่ได้ติดกันหลายเครื่อง — หยุดส่งรอบนี้ รอบหน้าลองใหม่');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
