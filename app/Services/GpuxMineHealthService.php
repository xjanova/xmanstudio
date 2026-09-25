<?php

namespace App\Services;

use App\Models\GpuJobEarning;
use App\Models\GpuNode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * GPUxMINE ยังเดินอยู่ไหม — ตัวจับเวลา เงินที่ค้าง และงานที่รอคน
 *
 * ทุกอย่างในระบบนี้ล้มแบบเงียบ: cron ที่ตั้งผิดทำให้เครื่องไม่ไปถึง aixman และเงิน
 * ไม่เข้ากระเป๋า โดยไม่มีหน้าไหนขึ้นแดง ที่นี่ตอบจากหลักฐาน: คำสั่งประทับเวลาตัวเอง
 * ทุกครั้งที่เริ่มรัน (beat) และเงินที่ควรเดินไปแล้วแต่ยังค้างอยู่
 *
 * ใช้ทั้ง gpuxmine:doctor และหน้าแอดมิน — ทุกเมธอดที่นี่อ่านฐานข้อมูลกับแคชเท่านั้น
 * ไม่ยิงออกไปข้างนอก (การถาม relay/aixman อยู่ใน doctor)
 */
class GpuxMineHealthService
{
    public const TASK_SYNC = 'sync-nodes';

    public const TASK_SETTLE = 'settle-earnings';

    /**
     * เงินที่พ้นระยะพักมาเกินเท่านี้แล้วยังไม่ขยับ = ตัวปล่อยเงิน (รันทุกชั่วโมง) ไม่ได้ทำงาน
     */
    public const STUCK_GRACE_HOURS = 2;

    /** การถอนที่ค้างนานกว่านี้ = relay หรือ aixman ปฏิเสธซ้ำ ๆ ไม่ใช่แค่รอรอบ */
    public const RETIRE_STALE_MINUTES = 30;

    private const HEARTBEAT_KEY = 'gpuxmine:heartbeat:';

    public function __construct(
        private readonly GpuxMineEarningSettlementService $settlement,
    ) {}

    /** คำสั่งเริ่มรันแล้ว — ประทับตั้งแต่ต้น เพราะสิ่งที่ถามคือ "cron เรียกไหม" ไม่ใช่ "รอบนี้สำเร็จไหม" */
    public function beat(string $task): void
    {
        try {
            Cache::forever(self::HEARTBEAT_KEY . $task, now()->getTimestamp());
        } catch (\Throwable $e) {
            // แคชล่มต้องไม่ทำให้การส่งงานหรือการจ่ายเงินล่มตาม
            Log::warning('[GPUxMINE] could not record the scheduler heartbeat', ['task' => $task, 'error' => $e->getMessage()]);
        }
    }

    public function lastBeat(string $task): ?Carbon
    {
        try {
            $timestamp = Cache::get(self::HEARTBEAT_KEY . $task);
        } catch (\Throwable) {
            return null;
        }

        return is_numeric($timestamp) ? Carbon::createFromTimestamp((int) $timestamp) : null;
    }

    /**
     * pending ที่พ้นระยะพักมาเกิน STUCK_GRACE_HOURS แล้ว — ไม่นับเครื่องที่ถูกระงับ (ค้างโดยตั้งใจ)
     *
     * @return array{count:int, satang:int, oldest:?Carbon}
     */
    public function stuckPending(): array
    {
        $cutoff = now()->subHours($this->settlement->holdHours() + self::STUCK_GRACE_HOURS);

        $query = $this->settlement->notFrozen(
            GpuJobEarning::query()
                ->where('status', GpuJobEarning::STATUS_PENDING)
                ->whereNotNull('user_id')
                ->where(function (Builder $q) use ($cutoff) {
                    $q->where('completed_at', '<=', $cutoff)
                        ->orWhere(fn (Builder $q) => $q->whereNull('completed_at')->where('created_at', '<=', $cutoff));
                })
        );

        return $this->summarise($query, 'COALESCE(completed_at, created_at)');
    }

    /**
     * cleared ที่รอโอนมาเกิน STUCK_GRACE_HOURS — ปกติคือบัญชีหรือกระเป๋าที่ถูกปิด (ตั้งใจข้าม)
     * ถ้าเยอะผิดปกติ แปลว่าการโอนล้มซ้ำ ๆ
     *
     * @return array{count:int, satang:int, oldest:?Carbon}
     */
    public function stuckCleared(): array
    {
        $query = $this->settlement->notFrozen(
            GpuJobEarning::query()
                ->where('status', GpuJobEarning::STATUS_CLEARED)
                ->whereNotNull('user_id')
                ->where('amount_satang', '>=', 0)
                ->where(fn (Builder $q) => $q->whereNull('cleared_at')
                    ->orWhere('cleared_at', '<=', now()->subHours(self::STUCK_GRACE_HOURS)))
        );

        return $this->summarise($query, 'COALESCE(cleared_at, completed_at, created_at)');
    }

    /** @return array{count:int, satang:int} งานที่รอแอดมินตัดสิน */
    public function awaitingReview(): array
    {
        $row = GpuJobEarning::where('status', GpuJobEarning::STATUS_REVIEW)
            ->selectRaw('COUNT(*) as jobs, COALESCE(SUM(amount_satang), 0) as satang')
            ->first();

        return ['count' => (int) ($row->jobs ?? 0), 'satang' => (int) ($row->satang ?? 0)];
    }

    /** เครื่องที่ถอนแล้วแต่ aixman หรือ relay ยังไม่ยืนยันมานานเกิน RETIRE_STALE_MINUTES */
    public function staleRetirements(): int
    {
        return GpuNode::onlyTrashed()
            ->where('retire_status', GpuNode::RETIRE_PENDING)
            ->where('updated_at', '<=', now()->subMinutes(self::RETIRE_STALE_MINUTES))
            ->count();
    }

    /** เครื่องที่ยังใช้งานอยู่แต่ส่งให้ aixman ครั้งล่าสุดไม่สำเร็จ */
    public function dispatchErrors(): int
    {
        return GpuNode::paired()->where('dispatch_status', 'error')->count();
    }

    public function pairedNodes(): int
    {
        return GpuNode::paired()->count();
    }

    /** เวลาที่ส่งให้ aixman ครั้งล่าสุด (ไม่ว่าผลจะเป็นอย่างไร) */
    public function lastPushAt(): ?Carbon
    {
        $at = GpuNode::withTrashed()->max('dispatch_synced_at');

        return $at ? Carbon::parse($at) : null;
    }

    /** @return array{count:int, satang:int, oldest:?Carbon} */
    private function summarise(Builder $query, string $timeExpression): array
    {
        $row = $query->toBase()
            ->selectRaw("COUNT(*) as jobs, COALESCE(SUM(amount_satang), 0) as satang, MIN({$timeExpression}) as oldest")
            ->first();

        return [
            'count' => (int) ($row->jobs ?? 0),
            'satang' => (int) ($row->satang ?? 0),
            'oldest' => ! empty($row->oldest) ? Carbon::parse($row->oldest) : null,
        ];
    }
}
