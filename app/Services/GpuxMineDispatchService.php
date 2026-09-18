<?php

namespace App\Services;

use App\Models\GpuNode;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ส่งเครื่องที่จับคู่แล้วไปขึ้นทะเบียนรับงานที่ aixman
 *
 * ทิศทางเดียว: XMAN Studio → aixman เสมอ เพราะ XMAN Studio เป็นที่เดียวที่ถือ
 * token ของ worker (relay เก็บแค่ hash) และเป็นที่เดียวที่รู้ว่าเครื่องนี้
 * เป็นของใคร aixman ไม่ต้องรู้จัก relay และไม่ต้องรู้จักเจ้าของ — มันรับมา
 * แค่ "ปลายทางนี้ ส่งงานแบบนี้ได้"
 *
 * แยกจาก AixmanService เพราะคนละสัญญา: ตัวนั้นแจ้งเครดิตที่ลูกค้าซื้อ ตัวนี้
 * ขึ้นทะเบียนกำลังประมวลผล ใช้ความลับเดียวกันเพราะเป็นคู่สายเดียวกัน
 */
class GpuxMineDispatchService
{
    public function isConfigured(): bool
    {
        return (bool) config('services.aixman.api_base')
            && (bool) config('services.aixman.webhook_secret');
    }

    /**
     * แจ้ง aixman ว่ามีเครื่องนี้ และมันทำอะไรได้บ้าง
     *
     * เขียนผลกลับลงแถวเสมอ ทั้งสำเร็จและไม่สำเร็จ เพราะหน้า "เครื่องของฉัน"
     * ต้องตอบเจ้าของให้ได้ว่าทำไมเครื่องที่เปิดค้างไว้ทั้งคืนถึงยังไม่ได้งาน
     */
    public function sync(GpuNode $node): bool
    {
        if ($node->worker_id === null || $node->relay_token === null) {
            return false;
        }

        if (! $this->isConfigured()) {
            $node->forceFill([
                'dispatch_status' => 'unconfigured',
                'dispatch_note' => 'ยังไม่ได้ตั้งค่าเชื่อมต่อ aixman',
                'dispatch_synced_at' => now(),
            ])->save();

            return false;
        }

        try {
            $response = Http::withHeaders(['x-webhook-secret' => (string) config('services.aixman.webhook_secret')])
                ->timeout((int) config('services.aixman.timeout', 10))
                ->retry(2, 500, throw: false)
                ->post(rtrim((string) config('services.aixman.api_base'), '/') . '/api/gpux/nodes', [
                    'workerId' => $node->worker_id,
                    'endpoint' => $node->tunnel_endpoint,
                    'token' => $node->relay_token,
                    'label' => $node->displayName(),
                    'online' => $node->online,
                    'assessed' => $node->assessed,
                    'gpuName' => $node->gpu_name,
                    'vramTotalMb' => $node->vram_total_mb,
                    'score' => $node->score,
                    'tier' => $node->tier,
                    'canRun' => $node->can_run ?? [],
                    // ส่งไปด้วยเสมอ แม้ว่าง: aixman อ่าน "ไม่มีข้อมูล" เป็นเร็วเต็มที่
                    // ซึ่งถูกสำหรับโหนดที่ยังไม่ได้อัปเดตไคลเอนต์
                    'lanes' => $node->lanes ?? [],
                    'provisional' => $node->provisional ?? [],
                    'ownerUserId' => $node->user_id,
                ]);

            $body = $response->json();

            if ($response->successful() && is_array($body)) {
                $node->forceFill([
                    'dispatch_status' => (string) ($body['status'] ?? 'unknown'),
                    'dispatch_note' => isset($body['note']) ? mb_substr((string) $body['note'], 0, 255) : null,
                    'dispatch_synced_at' => now(),
                ])->save();

                return ($body['status'] ?? null) === 'eligible';
            }

            Log::error('GPUxMINE node sync to aixman failed', [
                'worker_id' => $node->worker_id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $node->forceFill([
                'dispatch_status' => 'error',
                'dispatch_note' => 'aixman ตอบ HTTP ' . $response->status(),
                'dispatch_synced_at' => now(),
            ])->save();

            return false;
        } catch (\Throwable $e) {
            Log::error('GPUxMINE node sync to aixman threw', [
                'worker_id' => $node->worker_id,
                'error' => $e->getMessage(),
            ]);

            $node->forceFill([
                'dispatch_status' => 'error',
                'dispatch_note' => 'ติดต่อ aixman ไม่ได้',
                'dispatch_synced_at' => now(),
            ])->save();

            return false;
        }
    }

    /** เครื่องถูกถอนออกจากระบบ — หยุดส่งงานให้มัน แต่ไม่ยุ่งกับเครื่องเจ้าของ */
    public function retire(GpuNode $node): void
    {
        if ($node->worker_id === null || ! $this->isConfigured()) {
            return;
        }

        try {
            Http::withHeaders(['x-webhook-secret' => (string) config('services.aixman.webhook_secret')])
                ->timeout((int) config('services.aixman.timeout', 10))
                ->delete(rtrim((string) config('services.aixman.api_base'), '/') . '/api/gpux/nodes', [
                    'workerId' => $node->worker_id,
                ]);
        } catch (\Throwable $e) {
            // ถอนไม่สำเร็จแปลว่า aixman อาจยังส่งงานมาอีกพักหนึ่ง ซึ่ง relay
            // จะตอบ 503 ให้เองเมื่อเครื่องไม่ได้ต่ออยู่ — ไม่ใช่เหตุให้ค้าง
            Log::warning('GPUxMINE node retire failed', [
                'worker_id' => $node->worker_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
