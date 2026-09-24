<?php

namespace App\Services;

use App\Models\GpuNode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * สถานะของเครื่องหนึ่งเครื่อง: รับจาก relay, ตัดสินว่า aixman ต้องรู้ไหม, และถอนออก
 *
 * หน้า "เครื่องของฉัน" กับ gpuxmine:sync-nodes เคยเขียนสถานะแยกกันคนละแบบ
 * หน้าเว็บเขียนแถวแต่ไม่เคยส่งต่อ ส่วนตัวจับเวลาส่งต่อเฉพาะเมื่อเห็นแถว
 * "เปลี่ยน" — เจ้าของที่กด START แล้วเปิดหน้าเว็บดู จึงทำให้ตัวจับเวลาเห็นแถว
 * ที่อัปเดตไปแล้ว และไม่ส่งอะไรให้ aixman อีกเลย ตอนนี้ทั้งสองทางใช้ที่นี่
 * ที่เดียว และการตัดสินว่าต้องส่งไหมดูจาก "aixman รับอะไรไปล่าสุด"
 * (dispatch_fingerprint) ไม่ใช่ "เมื่อกี้เราเห็นอะไร"
 */
class GpuxMineNodeStateService
{
    /**
     * aixman บอกว่า worker ถูกปิดไปแล้ว ทั้งที่เครื่องออนไลน์และมีสิทธิ์รับงาน
     * — ส่งซ้ำให้ฟื้นก่อนถึงรอบสิบนาที แต่ไม่ถี่กว่านี้ เผื่อแอดมินฝั่ง aixman
     * ปิดไว้เองและจะไม่ยอมฟื้นให้
     */
    private const TERMINATED_RETRY_MINUTES = 2;

    /** ติดต่อ aixman หรือ relay ไม่ได้ติดกันเท่านี้ครั้ง ถือว่าล่มทั้งรอบ */
    public const GIVE_UP_AFTER_FAILURES = 3;

    public function __construct(
        private readonly GpuxMineRelayService $relay,
        private readonly GpuxMineDispatchService $dispatch,
    ) {}

    /**
     * รวมแถวหนึ่งจากรายชื่อของ relay ลงในแถวของเครื่อง แล้วบันทึกเฉพาะที่เปลี่ยน
     *
     * $row เป็น null = relay ไม่รู้จัก worker นี้ (store ถูกล้าง / ย้าย relay)
     * ถือเป็นออฟไลน์ แต่ไม่ลบแถวทิ้ง เพราะยอดค้างจ่ายและประวัติยังต้องตามได้
     *
     * ผลประเมินเครื่อง (คะแนน ระดับ งานที่รับได้) คงค่าล่าสุดไว้ตลอดช่วงที่
     * ไม่มี telemetry — relay ส่ง telemetry มาเฉพาะเครื่องที่ต่ออยู่ ถ้าเขียน
     * ศูนย์ทับทุกครั้งที่เครื่องหลุด เจ้าของจะเห็นคะแนนหายทุกครั้งที่ปิดคอม
     * และ aixman จะได้ยินว่าเครื่องยังไม่ประเมินทั้งที่ประเมินไปแล้ว
     *
     * @param  array<string, mixed>|null  $row
     * @return bool บันทึกอะไรลงไปไหม
     */
    public function apply(GpuNode $node, ?array $row): bool
    {
        $telemetry = is_array($row['telemetry'] ?? null) ? $row['telemetry'] : [];
        $online = (bool) ($row['online'] ?? false);

        $fresh = [
            'online' => $online,
            'agent_version' => ! empty($row['agentVersion'])
                ? mb_substr((string) $row['agentVersion'], 0, 32)
                : $node->agent_version,
            'last_seen_at' => $this->time($row['lastSeenAt'] ?? null) ?? $node->last_seen_at,
            // ออฟไลน์ = ไม่รู้ ไม่ใช่ "ไม่รับ" — aixman ดู online อยู่แล้ว
            'accepting' => $online ? $this->flag($row, $telemetry, 'accepting') : null,
            'busy' => $online ? $this->flag($row, $telemetry, 'busy') : null,
        ];

        if ($telemetry !== []) {
            $fresh += [
                'assessed' => (bool) ($telemetry['assessed'] ?? false),
                'score' => max(0, (int) ($telemetry['score'] ?? 0)),
                'tier' => mb_substr((string) ($telemetry['tier'] ?? 'unrated'), 0, 16),
                'gpu_name' => isset($telemetry['gpuName']) && $telemetry['gpuName'] !== ''
                    ? mb_substr((string) $telemetry['gpuName'], 0, 255)
                    : $node->gpu_name,
                'vram_total_mb' => max(0, (int) ($telemetry['vramTotalMb'] ?? $node->vram_total_mb)),
                'can_run' => is_array($telemetry['canRun'] ?? null) ? $telemetry['canRun'] : $node->can_run,
                'lanes' => is_array($telemetry['lanes'] ?? null) ? $telemetry['lanes'] : $node->lanes,
                'provisional' => is_array($telemetry['provisional'] ?? null) ? $telemetry['provisional'] : $node->provisional,
                // ไคลเอนต์รุ่นที่ยังไม่บอกค่านี้ = คงค่าเดิม ไม่ใช่รีเซ็ตเป็นศูนย์
                'free_share_pct' => isset($telemetry['freeSharePct'])
                    ? max(0, min(100, (int) $telemetry['freeSharePct']))
                    : (int) $node->free_share_pct,
            ];
        }

        $node->forceFill($fresh);
        if (! $node->isDirty()) {
            return false;
        }

        $node->save();

        return true;
    }

    /**
     * aixman ต้องได้ยินเรื่องเครื่องนี้ตอนนี้ไหม
     *
     * ตัดสินจากสภาพปัจจุบันทุกครั้ง (level-triggered) ไม่ใช่จากการเห็นอะไร
     * เปลี่ยน: ส่งไม่ถึงรอบก่อน = ส่งใหม่, aixman ไม่เคยตอบรับ = ส่งใหม่,
     * ข้อมูลตอนนี้ต่างจากชุดที่ aixman ตอบรับล่าสุด = ส่งใหม่, และส่งซ้ำ
     * ทุกสิบนาทีแม้ไม่มีอะไรเปลี่ยน เผื่อ aixman ปิด worker ไปเองระหว่างนั้น
     */
    public function needsPush(GpuNode $node): bool
    {
        if ($node->paired_at === null || $node->worker_id === null || $node->dispatchToken() === null) {
            return false;
        }

        // ยังไม่ได้ตั้งค่า aixman: รู้ผลอยู่แล้ว ไม่ต้องเขียนแถวซ้ำทุกนาที —
        // ตั้งค่าเมื่อไรก็ส่งรอบถัดไป
        if ($node->dispatch_status === 'unconfigured') {
            return $this->dispatch->isConfigured();
        }

        if ($node->dispatch_fingerprint === null
            || in_array($node->dispatch_status, [null, 'error'], true)
            || $node->dispatch_synced_at === null) {
            return true;
        }

        // ส่งซ้ำตามรอบเวลาเฉพาะกับ aixman ที่บอกสถานะ worker กลับมาแล้ว —
        // aixman รุ่นก่อนหน้าเขียนแถวที่กำลังเรนเดอร์เป็น warming ทุกครั้งที่ได้
        // ข้อมูลเครื่อง ส่งซ้ำทุกสิบนาทีกับรุ่นนั้นคือการฆ่างานกลางทางเป็นระยะ
        // (สำหรับรุ่นนั้น ยังส่งเมื่อข้อมูลเปลี่ยนหรือส่งไม่ถึง เหมือนที่เคยทำ)
        if ($node->dispatch_worker_status !== null
            && $node->dispatch_synced_at->lte(now()->subMinutes($this->resyncMinutes()))) {
            return true;
        }

        if ($node->dispatch_worker_status === 'terminated'
            && $node->online
            && $node->dispatch_status === 'eligible'
            && ! $node->isSuspended()
            && $node->dispatch_synced_at->lte(now()->subMinutes(self::TERMINATED_RETRY_MINUTES))) {
            return true;
        }

        return $this->dispatch->fingerprint($node) !== $node->dispatch_fingerprint;
    }

    /**
     * ให้หน้าเว็บส่งต่อให้ aixman หลังตอบเจ้าของไปแล้ว
     *
     * เจ้าของที่เพิ่งกด START แล้วสลับมาดูหน้านี้ ไม่ควรต้องรอตัวจับเวลา
     * (ซึ่งบนเซิร์ฟเวอร์อาจวิ่งแค่ทุกห้านาที) แต่ก็ไม่ควรรอ aixman ตอบก่อน
     * หน้าเว็บจะขึ้น — ยิงหลังส่งหน้าให้เบราว์เซอร์แล้ว และไม่เกินนาทีละครั้ง
     * ต่อเครื่อง ไม่ว่าจะกดรีเฟรชรัวแค่ไหน
     */
    public function pushAfterResponse(GpuNode $node): void
    {
        if (! Cache::add('gpuxmine:page-push:' . $node->id, true, 60)) {
            return;
        }

        $id = $node->id;
        app()->terminating(function () use ($id) {
            $fresh = GpuNode::find($id);
            if ($fresh !== null && $this->needsPush($fresh)) {
                $this->dispatch->push($fresh);
            }
        });
    }

    /**
     * ถอน worker ของเครื่องนี้ออกทั้งที่ aixman และที่ relay แล้วจดผลไว้
     *
     * สำเร็จทั้งสองฝั่งเท่านั้นถึงนับว่าเสร็จ ไม่งั้นแถวค้าง pending ไว้ให้
     * ตัวจับเวลาลองใหม่ — แม้เจ้าของจะกดถอนและแถวถูก soft delete ไปแล้วก็ตาม
     */
    public function retire(GpuNode $node): bool
    {
        if ($node->worker_id === null) {
            $node->forceFill(['retire_status' => GpuNode::RETIRE_DONE])->save();

            return true;
        }

        $aixman = $this->dispatch->retire($node);
        $relay = $this->relay->deleteWorker($node->worker_id);
        $done = $aixman && $relay;

        $node->forceFill(['retire_status' => $done ? GpuNode::RETIRE_DONE : GpuNode::RETIRE_PENDING])->save();

        if (! $done) {
            Log::warning('GPUxMINE worker retirement incomplete — will retry', [
                'worker_id' => $node->worker_id,
                'aixman' => $aixman,
                'relay' => $relay,
            ]);
        }

        return $done;
    }

    /** เจ้าของถอนเครื่องออก: หยุดส่งงาน เพิกถอนกุญแจ แล้วเก็บประวัติไว้ (soft delete) */
    public function forget(GpuNode $node): bool
    {
        $done = $this->retire($node);
        $node->delete();

        return $done;
    }

    /**
     * เครื่องเดิมได้ worker ใหม่จาก relay — ถอน worker เก่าทิ้งให้หมด
     *
     * ต้องเรียกหลังแถวจริงเปลี่ยนไปใช้ worker ใหม่แล้ว เก็บ worker เก่าไว้ใน
     * แถวสำรองที่ soft delete ไว้ตั้งแต่เกิด ด้วยเหตุผลสองข้อ:
     *   - ถอนไม่สำเร็จรอบนี้ ตัวจับเวลายังรู้ว่าต้องถอน worker ไหน
     *   - งานที่ worker เก่าทำเสร็จหลังจากนี้ aixman ยังหาเจ้าของเจอจาก
     *     worker_id (aixman อ่าน gpu_nodes รวมแถวที่ soft delete)
     */
    public function retireReplacedWorker(GpuNode $live, string $oldWorkerId): bool
    {
        if ($oldWorkerId === $live->worker_id) {
            return true;
        }

        $tomb = new GpuNode;
        $tomb->forceFill([
            'user_id' => $live->user_id,
            'referrer_user_id' => $live->referrer_user_id,
            'product_device_id' => $live->product_device_id,
            'machine_id' => $live->machine_id,
            'label' => $live->label,
            'gpu_name' => $live->gpu_name,
            'vram_total_mb' => (int) $live->vram_total_mb,
            'worker_id' => $oldWorkerId,
            'paired_at' => $live->paired_at,
            'dispatch_note' => 'worker เดิมของเครื่องนี้ ก่อนลงทะเบียนใหม่กับ relay',
            'retire_status' => GpuNode::RETIRE_PENDING,
            'deleted_at' => now(),
        ])->save();

        return $this->retire($tomb);
    }

    /**
     * ลองถอน worker ที่ค้างอยู่อีกครั้ง
     *
     * @return int จำนวนที่ถอนสำเร็จรอบนี้
     */
    public function retryPendingRetirements(int $limit = 50): int
    {
        $done = 0;
        $failures = 0;

        $rows = GpuNode::onlyTrashed()
            ->where('retire_status', GpuNode::RETIRE_PENDING)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($rows as $row) {
            if ($this->retire($row)) {
                $done++;
                $failures = 0;
            } elseif (++$failures >= self::GIVE_UP_AFTER_FAILURES) {
                break;   // อีกฝั่งล่มอยู่ ไล่ทีละแถวก็เสียเวลาเปล่า รอบหน้าค่อยว่ากัน
            }
        }

        return $done;
    }

    public function resyncMinutes(): int
    {
        return max(1, (int) config('services.gpuxmine.resync_minutes', 10));
    }

    /**
     * @param  array<string, mixed>|null  $row
     * @param  array<string, mixed>  $telemetry
     */
    private function flag(?array $row, array $telemetry, string $key): ?bool
    {
        $value = $row[$key] ?? $telemetry[$key] ?? null;

        return is_bool($value) ? $value : null;
    }

    private function time(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->setTimezone((string) config('app.timezone', 'UTC'));
        } catch (\Throwable) {
            return null;
        }
    }
}
