<?php

namespace App\Console\Commands;

use App\Models\GpuNode;
use App\Services\GpuxMineDispatchService;
use App\Services\GpuxMineRelayService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * ดึงสถานะเครื่องจาก relay แล้วส่งต่อให้ aixman
 *
 * หน้าเว็บ "เครื่องของฉัน" รีเฟรชให้เองตอนเจ้าของเปิดดู แต่การส่งงานต้องไม่
 * รอให้ใครเปิดหน้าไหน: เครื่องที่เพิ่งประเมินตัวเองเสร็จตอนตีสามต้องได้งาน
 * ตอนตีสาม ไม่ใช่ตอนเจ้าของตื่นมาเปิดเว็บ
 *
 * ส่งต่อให้ aixman เฉพาะเมื่อมีอะไรเปลี่ยนจริง — ออนไลน์/ออฟไลน์ ผลประเมิน
 * หรืองานที่รับได้ ไม่งั้นทุกนาทีคือการยิง webhook หนึ่งครั้งต่อเครื่องหนึ่งตัว
 * ตลอดไป
 */
class GpuxMineSyncNodesCommand extends Command
{
    protected $signature = 'gpuxmine:sync-nodes {--force : ส่งให้ aixman ทุกเครื่องแม้ไม่มีอะไรเปลี่ยน}';

    protected $description = 'ดึงสถานะเครื่อง GPUxMINE จาก relay แล้วขึ้นทะเบียนรับงานที่ aixman';

    public function handle(GpuxMineRelayService $relay, GpuxMineDispatchService $dispatch): int
    {
        if (! $relay->isConfigured()) {
            $this->warn('ยังไม่ได้ตั้งค่า GPUXMINE_RELAY_URL / GPUXMINE_RELAY_ADMIN_KEY');

            return self::SUCCESS;   // ยังไม่ตั้งค่า ไม่ใช่ความล้มเหลว
        }

        $live = $relay->workers();
        $nodes = GpuNode::paired()->get();

        $changed = 0;
        $pushed = 0;

        foreach ($nodes as $node) {
            $row = $live[$node->worker_id] ?? null;

            $telemetry = is_array($row['telemetry'] ?? null) ? $row['telemetry'] : [];
            $online = (bool) ($row['online'] ?? false);

            // relay ไม่รู้จัก worker นี้แล้ว (store ถูกล้าง / ย้าย relay)
            // ไม่ลบแถวทิ้ง เพราะยอดค้างจ่ายและประวัติยังต้องตามได้
            $before = [
                $node->online, $node->assessed, $node->score,
                $node->tier, json_encode($node->can_run), json_encode($node->lanes),
            ];

            $node->forceFill([
                'online' => $online,
                'agent_version' => $row['agentVersion'] ?? $node->agent_version,
                'last_seen_at' => ! empty($row['lastSeenAt']) ? Carbon::parse($row['lastSeenAt']) : $node->last_seen_at,
                'assessed' => (bool) ($telemetry['assessed'] ?? false),
                'score' => (int) ($telemetry['score'] ?? 0),
                'tier' => (string) ($telemetry['tier'] ?? 'unrated'),
                'gpu_name' => $telemetry['gpuName'] ?? $node->gpu_name,
                'vram_total_mb' => (int) ($telemetry['vramTotalMb'] ?? $node->vram_total_mb),
                'can_run' => $telemetry['canRun'] ?? $node->can_run,
                'lanes' => $telemetry['lanes'] ?? $node->lanes,
                'provisional' => $telemetry['provisional'] ?? $node->provisional,
                'free_share_pct' => (int) ($telemetry['freeSharePct'] ?? $node->free_share_pct),
            ]);

            $after = [
                $node->online, $node->assessed, $node->score,
                $node->tier, json_encode($node->can_run), json_encode($node->lanes),
            ];

            if ($node->isDirty()) {
                $node->save();
                $changed++;
            }

            // ความสามารถหรือการเชื่อมต่อเปลี่ยน = aixman ต้องรู้
            // คะแนนขยับนิดหน่อยระหว่างการวัดสองครั้งไม่ใช่เหตุให้ยิง
            $worthPushing = $this->option('force')
                || $before[0] !== $after[0]
                || $before[1] !== $after[1]
                || $before[4] !== $after[4]
                // เลนเปลี่ยนคือเหตุผลที่ต้องยิงที่สุด: เครื่องเพิ่งพิสูจน์ว่าทำงาน
                // ด่วนไม่ทัน แล้วยังถูกส่งงานด่วนต่อจนกว่าจะมีอย่างอื่นเปลี่ยน
                || $before[5] !== $after[5]
                || $node->dispatch_synced_at === null;

            if ($worthPushing && $dispatch->sync($node)) {
                $pushed++;
            } elseif ($worthPushing) {
                $pushed++;   // ส่งแล้ว แม้ผลจะเป็น "ยังรับงานไม่ได้"
            }
        }

        $this->info("เครื่องทั้งหมด {$nodes->count()} · อัปเดต {$changed} · ส่งให้ aixman {$pushed}");

        return self::SUCCESS;
    }
}
