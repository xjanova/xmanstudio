<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * คุยกับ relay ของ GPUxMINE
 *
 * relay เป็นตัวรับสายจากเครื่องที่บ้านคน (เครื่องเป็นฝ่ายโทรออก จึงไม่ต้อง
 * เปิดพอร์ตหรือตั้ง firewall) และเป็นคนออก credential ให้ worker
 * XMAN Studio เป็นฝ่ายเดียวที่ถือ admin key ของ relay — เจ้าของเครื่องไม่เคย
 * เห็นมัน และ aixman ก็ไม่ต้องรู้จัก
 */
class GpuxMineRelayService
{
    private const TIMEOUT = 10;

    public function isConfigured(): bool
    {
        return (bool) config('services.gpuxmine.relay_url')
            && (bool) config('services.gpuxmine.admin_key');
    }

    /**
     * ขอ worker ใหม่จาก relay
     *
     * token ที่ได้กลับมาเป็น plaintext ครั้งเดียวและครั้งเดียวเท่านั้น —
     * relay เก็บแค่ hash ถ้าเราทำหาย ต้องออก worker ใหม่ทั้งตัว ไม่มีทาง
     * ขอ token เดิมคืน
     *
     * @return array{workerId:string, token:string, agentRelayUrl:string, aixmanEndpoint:string}|null
     */
    public function enroll(string $label): ?array
    {
        if (! $this->isConfigured()) {
            Log::warning('GPUxMINE relay not configured — cannot enrol node', ['label' => $label]);

            return null;
        }

        try {
            $response = Http::withHeaders(['X-Admin-Key' => (string) config('services.gpuxmine.admin_key')])
                ->timeout(self::TIMEOUT)
                ->post($this->url('/enroll') . '?label=' . urlencode($label));

            if (! $response->successful()) {
                Log::error('GPUxMINE relay refused enrolment', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();
            if (! is_array($data) || empty($data['workerId']) || empty($data['token'])) {
                Log::error('GPUxMINE relay returned an enrolment without credentials', ['body' => $response->body()]);

                return null;
            }

            return [
                'workerId' => (string) $data['workerId'],
                'token' => (string) $data['token'],
                'agentRelayUrl' => (string) ($data['agentRelayUrl'] ?? ''),
                'aixmanEndpoint' => (string) ($data['aixmanEndpoint'] ?? ''),
            ];
        } catch (\Throwable $e) {
            Log::error('GPUxMINE relay enrolment threw', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * เครื่องทั้งหมดที่ relay รู้จัก พร้อม telemetry ล่าสุดของตัวที่ออนไลน์
     *
     * คืน array ที่ key ด้วย workerId เพื่อให้ผู้เรียกจับคู่กับแถวในฐานข้อมูล
     * ได้ตรง ๆ โดยไม่ต้องวนซ้อน
     *
     * @return array<string, array<string, mixed>>
     */
    public function workers(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $response = Http::withHeaders(['X-Admin-Key' => (string) config('services.gpuxmine.admin_key')])
                ->timeout(self::TIMEOUT)
                ->get($this->url('/admin/workers'));

            if (! $response->successful()) {
                Log::warning('GPUxMINE relay worker listing failed', ['status' => $response->status()]);

                return [];
            }

            $rows = $response->json();
            if (! is_array($rows)) {
                return [];
            }

            $byId = [];
            foreach ($rows as $row) {
                if (is_array($row) && ! empty($row['workerId'])) {
                    $byId[(string) $row['workerId']] = $row;
                }
            }

            return $byId;
        } catch (\Throwable $e) {
            // relay ล่มไม่ใช่เหตุให้หน้าเว็บล่ม — เครื่องที่ยังเปิดอยู่ก็ยัง
            // ทำงานต่อได้ตามปกติ เราแค่ไม่รู้สถานะล่าสุดชั่วคราว
            Log::warning('GPUxMINE relay unreachable', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * relay ตัวปัจจุบันยังรู้จัก worker นี้อยู่ไหม
     *
     * ไม่ใช่คำถามเชิงทฤษฎี: ย้าย relay ไปอีกเครื่องเมื่อไร `workers.json`
     * ก็เริ่มนับหนึ่งใหม่ และ token ของทุกเครื่องที่ลงทะเบียนไว้ก่อนหน้าก็ใช้ไม่ได้
     * ทันที ถ้าไม่ถามก่อน เราจะคืน credential ที่ relay ปฏิเสธแน่ ๆ ให้เจ้าของ
     * เครื่องไปนั่งงงว่าทำไมต่อไม่ติด
     */
    public function knows(string $workerId): bool
    {
        return array_key_exists($workerId, $this->workers());
    }

    /** ที่อยู่ที่เครื่องลูกต้องต่อเข้ามา แปลงจาก URL ของ relay ที่ตั้งไว้ตอนนี้ */
    public function agentUrl(): string
    {
        $base = rtrim((string) config('services.gpuxmine.relay_url'), '/');

        // https://host:8443 → wss://host:8443/agent — พอร์ตต้องติดไปด้วย
        // ไม่งั้นเครื่องจะไปเคาะ 443 ซึ่งบนเซิร์ฟเวอร์ที่ relay อยู่ตอนนี้
        // เปิดให้เฉพาะ Cloudflare
        return preg_replace('#^http#', 'ws', $base) . '/agent';
    }

    /** ปลายทางที่ aixman ยิงงานเข้ามา */
    public function tunnelEndpoint(string $workerId): string
    {
        return rtrim((string) config('services.gpuxmine.relay_url'), '/') . '/w/' . $workerId;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.gpuxmine.relay_url'), '/') . $path;
    }
}
