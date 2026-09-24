<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
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

    /** หน้า "เครื่องของฉัน" อ่านรายชื่อจากแคชนี้ — ไม่ยิง relay ทุกครั้งที่มีคนเปิดหน้า */
    private const PAGE_CACHE_KEY = 'gpuxmine:relay:workers';

    private const PAGE_CACHE_SECONDS = 15;

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
     * relay รุ่นใหม่ออกกุญแจสองใบ: `token` ให้เครื่องใช้ต่อ /agent และ
     * `tunnelToken` ให้ aixman ใช้เปิด /w/ รุ่นเก่าออกใบเดียว ซึ่งกรณีนั้น
     * tunnelToken เป็น null และทุกฝ่ายใช้ token ใบเดิม
     *
     * @return array{workerId:string, token:string, tunnelToken:?string, agentRelayUrl:string, aixmanEndpoint:string}|null
     */
    public function enroll(string $label): ?array
    {
        if (! $this->isConfigured()) {
            Log::warning('GPUxMINE relay not configured — cannot enrol node', ['label' => $label]);

            return null;
        }

        try {
            $response = $this->admin()->post($this->url('/enroll') . '?label=' . urlencode($label));

            if (! $response->successful()) {
                Log::error('GPUxMINE relay refused enrolment', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();
            if (! is_array($data) || empty($data['workerId']) || empty($data['token'])) {
                // ไม่ log body — ถ้ามี token มาครึ่งเดียว มันก็ยังเป็นความลับ
                Log::error('GPUxMINE relay returned an enrolment without credentials');

                return null;
            }

            return [
                'workerId' => (string) $data['workerId'],
                'token' => (string) $data['token'],
                'tunnelToken' => ! empty($data['tunnelToken']) ? (string) $data['tunnelToken'] : null,
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
     * **null แปลว่าอ่านไม่ได้ ไม่ใช่ "ไม่มีเครื่อง"** — เคยคืน [] ทั้งสองกรณี
     * ซึ่งแปลว่า relay กระตุกครั้งเดียวก็พอให้ทุกเครื่องถูกเขียนเป็นออฟไลน์
     * คะแนนหาย และถูกส่งไปบอก aixman ให้ถอดออกจากคิวทั้งกองพร้อมกัน ผู้เรียก
     * ต้องแยกสองกรณีนี้ให้ออก: [] คือ relay ตอบแล้วว่าไม่รู้จักใครเลย ส่วน
     * null คือเราไม่รู้
     *
     * @return array<string, array<string, mixed>>|null
     */
    public function workers(): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->admin()->get($this->url('/admin/workers'));

            if (! $response->successful()) {
                Log::warning('GPUxMINE relay worker listing failed', ['status' => $response->status()]);

                return null;
            }

            $rows = $response->json();
            if (! is_array($rows) || ! array_is_list($rows)) {
                Log::warning('GPUxMINE relay worker listing was not a list');

                return null;
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

            return null;
        }
    }

    /**
     * รายชื่อเดียวกับ workers() แต่แคชไว้สั้น ๆ สำหรับหน้าเว็บ
     *
     * หน้า "เครื่องของฉัน" เคยเรียก relay แบบรอคำตอบทุกครั้งที่มีคนเปิด ถ้า
     * relay ช้า หน้าเว็บก็ค้างตาม และเจ้าของที่กดรีเฟรชรัว ๆ คือการยิง relay
     * รัว ๆ สิบห้าวินาทียังสดพอให้คนที่เพิ่งกด START เห็นผลเกือบทันที
     *
     * คืน fetchedAt มาด้วย เพื่อให้ผู้เรียกไม่เอาภาพเก่าไปทับแถวที่ตัวจับเวลา
     * เพิ่งเขียนด้วยข้อมูลที่ใหม่กว่า ความล้มเหลวไม่ถูกแคช
     *
     * @return array{fetchedAt:int, workers:array<string, array<string, mixed>>}|null
     */
    public function workersSnapshot(): ?array
    {
        $cached = Cache::get(self::PAGE_CACHE_KEY);
        if (is_array($cached) && isset($cached['fetchedAt'], $cached['workers'])) {
            return $cached;
        }

        $fetchedAt = now()->getTimestamp();
        $workers = $this->workers();
        if ($workers === null) {
            return null;
        }

        $snapshot = ['fetchedAt' => $fetchedAt, 'workers' => $workers];
        Cache::put(self::PAGE_CACHE_KEY, $snapshot, self::PAGE_CACHE_SECONDS);

        return $snapshot;
    }

    /**
     * relay ตัวปัจจุบันยังรู้จัก worker นี้อยู่ไหม
     *
     * ไม่ใช่คำถามเชิงทฤษฎี: ย้าย relay ไปอีกเครื่องเมื่อไร `workers.json`
     * ก็เริ่มนับหนึ่งใหม่ และ token ของทุกเครื่องที่ลงทะเบียนไว้ก่อนหน้าก็ใช้ไม่ได้
     * ทันที ถ้าไม่ถามก่อน เราจะคืน credential ที่ relay ปฏิเสธแน่ ๆ ให้เจ้าของ
     * เครื่องไปนั่งงงว่าทำไมต่อไม่ติด
     *
     * ตอบได้สามแบบ: true รู้จัก, false ไม่รู้จัก, null ถาม relay ไม่ได้ —
     * กรณีสุดท้ายห้ามถือเป็น "ไม่รู้จัก" ไม่งั้น relay กระตุกครั้งเดียวทำให้
     * เครื่องที่มาจับคู่ใหม่ถูกออก worker ใหม่ทิ้ง worker เดิมไว้เป็นกำพร้า
     */
    public function knows(string $workerId): ?bool
    {
        $workers = $this->workers();

        return $workers === null ? null : array_key_exists($workerId, $workers);
    }

    /**
     * ลบ worker ออกจาก relay — กุญแจทั้งสองใบใช้ไม่ได้อีก และสายที่ต่ออยู่ถูกตัด
     *
     * คืน true เมื่อ relay ยืนยันว่า worker ไม่อยู่แล้ว ซึ่งรวม 404 (relay
     * ไม่รู้จักตั้งแต่แรก หรือ relay รุ่นเก่าที่ยังไม่มีเส้นทางนี้ — กรณีหลัง
     * ลองซ้ำก็ไม่ได้อะไร)
     */
    public function deleteWorker(string $workerId): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $response = $this->admin()->delete($this->url('/admin/workers/' . rawurlencode($workerId)));

            if ($response->successful() || in_array($response->status(), [404, 405], true)) {
                return true;
            }

            Log::warning('GPUxMINE relay refused to delete a worker', [
                'worker_id' => $workerId,
                'status' => $response->status(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::warning('GPUxMINE relay delete threw', ['worker_id' => $workerId, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * ระงับ worker ที่ relay — เครื่องต่อ /agent ไม่ได้ และ aixman เปิด /w/ ไม่ได้
     * จนกว่าจะ enableWorker() ต่างจาก deleteWorker ตรงที่ย้อนกลับได้
     */
    public function disableWorker(string $workerId): bool
    {
        return $this->adminAction($workerId, 'disable');
    }

    public function enableWorker(string $workerId): bool
    {
        return $this->adminAction($workerId, 'enable');
    }

    /**
     * ออกกุญแจใหม่ทั้งสองใบให้ worker เดิม ใบเก่าใช้ไม่ได้ทันที
     *
     * @return array{workerId:string, token:string, tunnelToken:?string}|null
     */
    public function rotateWorker(string $workerId): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->admin()->post($this->url('/admin/workers/' . rawurlencode($workerId) . '/rotate'));
            $data = $response->json();

            if (! $response->successful() || ! is_array($data) || empty($data['token'])) {
                Log::warning('GPUxMINE relay refused to rotate a worker', [
                    'worker_id' => $workerId,
                    'status' => $response->status(),
                ]);

                return null;
            }

            return [
                'workerId' => (string) ($data['workerId'] ?? $workerId),
                'token' => (string) $data['token'],
                'tunnelToken' => ! empty($data['tunnelToken']) ? (string) $data['tunnelToken'] : null,
            ];
        } catch (\Throwable $e) {
            Log::warning('GPUxMINE relay rotate threw', ['worker_id' => $workerId, 'error' => $e->getMessage()]);

            return null;
        }
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

    private function adminAction(string $workerId, string $action): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $response = $this->admin()->post($this->url('/admin/workers/' . rawurlencode($workerId) . '/' . $action));

            if ($response->successful()) {
                return true;
            }

            Log::warning("GPUxMINE relay refused to {$action} a worker", [
                'worker_id' => $workerId,
                'status' => $response->status(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::warning("GPUxMINE relay {$action} threw", ['worker_id' => $workerId, 'error' => $e->getMessage()]);

            return false;
        }
    }

    private function admin(): PendingRequest
    {
        return Http::withHeaders(['X-Admin-Key' => (string) config('services.gpuxmine.admin_key')])
            ->timeout(self::TIMEOUT);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.gpuxmine.relay_url'), '/') . $path;
    }
}
