<?php

namespace App\Console\Commands;

use App\Models\GpuNode;
use App\Services\GpuxMineDispatchService;
use App\Services\GpuxMineNodeStateService;
use App\Services\GpuxMineRelayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ให้ worker ที่ลงทะเบียนก่อน relay แยกกุญแจ มีกุญแจอุโมงค์ของ aixman เป็นของตัวเอง (C4)
 *
 * worker รุ่นนั้นมีกุญแจใบเดียว ใช้ทั้งเครื่องต่อ /agent และ aixman เปิด /w/ — ใครได้
 * agent.json ของเครื่องไปก็เป็น aixman ได้ relay ออกกุญแจอุโมงค์ใบใหม่ให้ worker เดิมได้
 * (`rotate?only=tunnel`) โดยเครื่องไม่หลุด แต่ตั้งแต่วินาทีที่ relay ตอบ ใบเดิมเปิด /w/
 * ไม่ได้อีก ใบใหม่ต้องถูกเก็บและถึง aixman ทันที — เคยไม่มีทางทำขั้นนี้จากฝั่งเราเลย
 * คู่มือ relay สั่งให้ยิงคำสั่งเองด้วย curl ซึ่งทำให้ทุกเครื่องที่ถูกย้ายได้ 401 จาก aixman
 * ไปตลอด เพราะไม่มีใครเก็บใบใหม่ไว้
 *
 * เลือกแถวที่ยังไม่มีกุญแจอุโมงค์ของตัวเอง (tunnel_token ว่าง หรือเท่ากับกุญแจของเครื่อง
 * ซึ่งคือสิ่งที่ relay ตอบตอน Relay__IssueTunnelTokens ยังปิด) ทีละเครื่อง: ขอใบใหม่ →
 * เก็บลงแถว → ส่งให้ aixman ทันที ส่งไม่ถึงตอนนี้ แถวค้างเป็น "ต้องส่ง" (ลายนิ้วมือ
 * เปลี่ยนเพราะกุญแจเปลี่ยน) gpuxmine:sync-nodes ส่งซ้ำให้ทุกนาที
 *
 * ข้ามเครื่องที่กำลังเรนเดอร์งานลูกค้าอยู่ (เว้นแต่ --include-busy) — ช่วงระหว่าง relay ตอบ
 * กับ aixman ได้ใบใหม่ คำขอของงานนั้นจะได้ 401 รันซ้ำได้เรื่อย ๆ จนไม่เหลือแถวให้ทำ
 *
 * ลำดับ: relay รุ่นที่มีคำสั่งนี้ → xmanstudio รุ่นนี้ → ตั้ง Relay__IssueTunnelTokens=true
 * ที่ relay → รันคำสั่งนี้ (ลอง --dry-run ก่อน)
 */
class GpuxMineRotateTunnelTokensCommand extends Command
{
    protected $signature = 'gpuxmine:rotate-tunnel-tokens
        {--worker=* : ทำเฉพาะ worker id เหล่านี้}
        {--limit=100 : ทำไม่เกินกี่เครื่องต่อการรันหนึ่งครั้ง}
        {--include-busy : ทำเครื่องที่กำลังทำงานให้ลูกค้าด้วย (งานนั้นอาจล้มแล้วถูกส่งไปลองที่อื่น)}
        {--dry-run : แสดงรายการที่จะทำ โดยไม่แตะ relay หรือ aixman}';

    protected $description = 'ออกกุญแจอุโมงค์แยกให้ worker GPUxMINE รุ่นกุญแจใบเดียว แล้วส่งให้ aixman ทันที';

    public function handle(
        GpuxMineRelayService $relay,
        GpuxMineDispatchService $dispatch,
    ): int {
        if (! $relay->isConfigured()) {
            $this->error('ยังไม่ได้ตั้งค่า GPUXMINE_RELAY_URL / GPUXMINE_RELAY_ADMIN_KEY');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        // สองคนรันพร้อมกัน = worker เดียวกันถูกหมุนสองครั้ง แล้วใบที่เก็บลงแถวอาจเป็นใบที่
        // relay ยกเลิกไปแล้ว — ให้รันได้ทีละคนเท่านั้น
        $lock = Cache::lock('gpuxmine:rotate-tunnel-tokens', 900);
        if (! $dryRun && ! $lock->get()) {
            $this->error('มีคนรันคำสั่งนี้อยู่แล้ว — รอให้เสร็จก่อน');

            return self::FAILURE;
        }

        try {
            return $this->rotateAll($relay, $dispatch, $dryRun);
        } finally {
            if (! $dryRun) {
                $lock->release();
            }
        }
    }

    private function rotateAll(GpuxMineRelayService $relay, GpuxMineDispatchService $dispatch, bool $dryRun): int
    {
        $only = array_values(array_filter((array) $this->option('worker'), fn ($id) => is_string($id) && $id !== ''));
        $limit = max(1, (int) $this->option('limit'));

        $candidates = GpuNode::paired()
            ->whereNotNull('worker_id')
            ->when($only !== [], fn ($q) => $q->whereIn('worker_id', $only))
            ->orderBy('id')
            ->get()
            ->filter(fn (GpuNode $node) => $this->needsOwnTunnelToken($node))
            ->values();

        if ($candidates->isEmpty()) {
            $this->info('ทุกเครื่องมีกุญแจอุโมงค์ของตัวเองแล้ว — ไม่มีอะไรต้องทำ');

            return self::SUCCESS;
        }

        $counts = ['rotated' => 0, 'pushed' => 0, 'busy' => 0, 'unknown' => 0, 'failed' => 0];
        $failures = 0;

        foreach ($candidates as $node) {
            if ($counts['rotated'] >= $limit) {
                break;
            }

            if ($this->isBusy($node) && ! $this->option('include-busy')) {
                $counts['busy']++;
                $this->line("  ข้าม {$node->worker_id} — กำลังทำงานให้ลูกค้า");

                continue;
            }

            if ($dryRun) {
                $counts['rotated']++;
                $this->line("  จะออกกุญแจใหม่ให้ {$node->worker_id} ({$node->displayName()})");

                continue;
            }

            $issued = $relay->rotateWorker($node->worker_id, tunnelOnly: true);

            if ($issued['outcome'] === GpuxMineRelayService::ROTATE_UNSUPPORTED) {
                $this->error('relay ตัวนี้ยังไม่มีคำสั่ง rotate — อัปเกรด relay ก่อน แล้วรันใหม่');

                return self::FAILURE;
            }

            if ($issued['outcome'] === GpuxMineRelayService::ROTATE_UNKNOWN) {
                $counts['unknown']++;
                $this->warn("  {$node->worker_id}: relay ไม่รู้จัก worker นี้ — เจ้าของต้องจับคู่ใหม่");

                continue;
            }

            if ($issued['outcome'] !== GpuxMineRelayService::ROTATE_OK) {
                $counts['failed']++;
                $this->warn("  {$node->worker_id}: relay ไม่ออกกุญแจให้");

                if (++$failures >= GpuxMineNodeStateService::GIVE_UP_AFTER_FAILURES) {
                    $this->error('relay ปฏิเสธติดกันหลายเครื่อง — หยุดรอบนี้');

                    break;
                }

                continue;
            }

            $failures = 0;

            // relay เปลี่ยนแล้ว ใบเดิมเปิด /w/ ไม่ได้อีก — เก็บก่อนทำอย่างอื่น
            $node->forceFill(['tunnel_token' => $issued['tunnelToken']])->save();
            $counts['rotated']++;

            Log::info('[GPUxMINE] worker given its own tunnel token', ['node_id' => $node->id, 'worker_id' => $node->worker_id]);

            $outcome = $dispatch->push($node);
            if ($outcome === GpuxMineDispatchService::OUTCOME_OK) {
                $counts['pushed']++;
                $this->line("  {$node->worker_id}: ได้กุญแจใหม่ และ aixman รับแล้ว");
            } else {
                $this->warn("  {$node->worker_id}: ได้กุญแจใหม่ แต่ส่งให้ aixman ไม่สำเร็จ ({$outcome}) — gpuxmine:sync-nodes ส่งซ้ำทุกนาที ระหว่างนี้ aixman เรียกเครื่องนี้ไม่ได้");
            }
        }

        $this->info(sprintf(
            '%s %d เครื่อง · aixman รับแล้ว %d · ข้ามเพราะกำลังทำงาน %d · relay ไม่รู้จัก %d · ล้ม %d · รอทำทั้งหมด %d',
            $dryRun ? 'จะออกกุญแจใหม่' : 'ออกกุญแจใหม่',
            $counts['rotated'],
            $counts['pushed'],
            $counts['busy'],
            $counts['unknown'],
            $counts['failed'],
            $candidates->count(),
        ));

        return $counts['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * ยังใช้กุญแจใบเดียวกับเครื่องอยู่ไหม — tunnel_token ว่าง (relay รุ่นเก่า) หรือเป็นค่าเดียว
     * กับ relay_token (relay รุ่นใหม่ที่ยังไม่เปิดการแยก ตอบ tunnelToken = token)
     */
    private function needsOwnTunnelToken(GpuNode $node): bool
    {
        return $node->tunnel_token === null
            || $node->tunnel_token === ''
            || hash_equals((string) $node->relay_token, (string) $node->tunnel_token);
    }

    private function isBusy(GpuNode $node): bool
    {
        return $node->busy === true || $node->dispatch_worker_status === 'busy';
    }
}
