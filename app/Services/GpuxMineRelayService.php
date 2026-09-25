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

    /** ผลของ deleteWorker() */
    public const DELETE_DONE = 'deleted';

    public const DELETE_UNSUPPORTED = 'unsupported';

    public const DELETE_FAILED = 'failed';

    /** ผลของ rotateWorker() */
    public const ROTATE_OK = 'ok';

    public const ROTATE_UNKNOWN = 'unknown';

    public const ROTATE_UNSUPPORTED = 'unsupported';

    public const ROTATE_FAILED = 'failed';

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
     * คืนหนึ่งใน DELETE_*:
     *   - DELETE_DONE: relay ตอบ 2xx — relay รุ่นที่มีคำสั่งนี้ตอบ 200 เสมอ แม้ไม่รู้จัก
     *     worker นั้นแล้ว (`existed:false`) เพราะ "ไม่อยู่แล้ว" คือผลที่ต้องการ
     *   - DELETE_UNSUPPORTED: 404/405 — relay รุ่นที่ยังไม่มีเส้นทางนี้ worker ยังอยู่
     *     และกุญแจยังต่อ /agent ได้ เคยนับเป็นลบสำเร็จ แถวจึงถูกปิดว่าเสร็จ และไม่มีใคร
     *     กลับมาลบให้หลังอัปเกรด relay ตอนนี้ผู้เรียกเก็บไว้รอ relay รุ่นใหม่
     *   - DELETE_FAILED: relay ปฏิเสธด้วยเหตุอื่น หรือติดต่อไม่ได้ — ลองใหม่ได้
     */
    public function deleteWorker(string $workerId): string
    {
        if (! $this->isConfigured()) {
            return self::DELETE_FAILED;
        }

        try {
            $response = $this->admin()->delete($this->url('/admin/workers/' . rawurlencode($workerId)));

            if ($response->successful()) {
                return self::DELETE_DONE;
            }

            if (in_array($response->status(), [404, 405], true)) {
                Log::notice('GPUxMINE relay has no worker delete yet — the worker stays until the relay is upgraded', [
                    'worker_id' => $workerId,
                    'status' => $response->status(),
                ]);

                return self::DELETE_UNSUPPORTED;
            }

            Log::warning('GPUxMINE relay refused to delete a worker', [
                'worker_id' => $workerId,
                'status' => $response->status(),
            ]);

            return self::DELETE_FAILED;
        } catch (\Throwable $e) {
            Log::warning('GPUxMINE relay delete threw', ['worker_id' => $workerId, 'error' => $e->getMessage()]);

            return self::DELETE_FAILED;
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
     * ออกกุญแจใหม่ให้ worker เดิม ใบเก่าใช้ไม่ได้ทันที
     *
     * $tunnelOnly = true (`?only=tunnel`): ออกเฉพาะกุญแจอุโมงค์ของ aixman เครื่องยังต่ออยู่
     * ด้วยกุญแจเดิม — ทางย้าย worker ที่ลงทะเบียนก่อนแยกกุญแจให้มีใบของ aixman เอง
     * (gpuxmine:rotate-tunnel-tokens) ตั้งแต่วินาทีที่ relay ตอบ ใบเดิมเปิด /w/ ไม่ได้แล้ว
     * ผู้เรียกต้องเก็บใบใหม่และส่งให้ aixman ทันที
     *
     * false: ออกใหม่ทั้งสองใบ สายของเครื่องถูกตัด และเครื่องต้องจับคู่ใหม่
     *
     * outcome เป็นหนึ่งใน ROTATE_*: ROTATE_UNKNOWN คือ relay ตอบ `unknown-worker`,
     * ROTATE_UNSUPPORTED คือ relay รุ่นที่ยังไม่มีเส้นทางนี้ (404/405 แบบอื่น)
     *
     * @return array{outcome:string, workerId:string, token:?string, tunnelToken:?string}
     */
    public function rotateWorker(string $workerId, bool $tunnelOnly = false): array
    {
        $result = ['outcome' => self::ROTATE_FAILED, 'workerId' => $workerId, 'token' => null, 'tunnelToken' => null];

        if (! $this->isConfigured()) {
            return $result;
        }

        try {
            $path = '/admin/workers/' . rawurlencode($workerId) . '/rotate' . ($tunnelOnly ? '?only=tunnel' : '');
            $response = $this->admin()->post($this->url($path));
            $data = $response->json();

            if (in_array($response->status(), [404, 405], true)) {
                $unknown = is_array($data) && ($data['error'] ?? null) === 'unknown-worker';

                return ['outcome' => $unknown ? self::ROTATE_UNKNOWN : self::ROTATE_UNSUPPORTED] + $result;
            }

            // ใบที่ต้องได้กลับมา: ของ aixman เสมอ และของเครื่องเมื่อออกใหม่ทั้งสองใบ
            $issued = is_array($data)
                && ! empty($data['tunnelToken'])
                && ($tunnelOnly || ! empty($data['token']));

            if (! $response->successful() || ! $issued) {
                Log::warning('GPUxMINE relay refused to rotate a worker', [
                    'worker_id' => $workerId,
                    'tunnel_only' => $tunnelOnly,
                    'status' => $response->status(),
                ]);

                return $result;
            }

            return [
                'outcome' => self::ROTATE_OK,
                'workerId' => (string) ($data['workerId'] ?? $workerId),
                'token' => ! empty($data['token']) ? (string) $data['token'] : null,
                'tunnelToken' => (string) $data['tunnelToken'],
            ];
        } catch (\Throwable $e) {
            Log::warning('GPUxMINE relay rotate threw', ['worker_id' => $workerId, 'error' => $e->getMessage()]);

            return $result;
        }
    }

    /**
     * ถาม relay ด้วย admin key หนึ่งครั้ง แล้วบอกผลแบบที่คนอ่านแก้ต่อได้ (gpuxmine:doctor)
     *
     * ต่างจาก workers() ตรงที่ไม่กลืนสาเหตุ: 401/403 คือ admin key ไม่ตรง, ต่อไม่ติด
     * คือ URL หรือเครือข่าย — สองอย่างนี้แก้คนละที่ ไม่มีการ log หรือคืนตัว key
     *
     * @return array{ok:bool, status:?int, workers:?int, online:?int, error:?string}
     */
    public function probe(): array
    {
        $result = ['ok' => false, 'status' => null, 'workers' => null, 'online' => null, 'error' => null];

        if (! $this->isConfigured()) {
            return ['error' => 'ยังไม่ได้ตั้งค่า'] + $result;
        }

        try {
            $response = $this->admin()->get($this->url('/admin/workers'));
        } catch (\Throwable $e) {
            return ['error' => 'ติดต่อไม่ได้ (' . class_basename($e) . ')'] + $result;
        }

        $result['status'] = $response->status();
        $rows = $response->json();

        if (in_array($response->status(), [401, 403], true)) {
            return ['error' => 'relay ไม่รับ admin key (HTTP ' . $response->status() . ')'] + $result;
        }

        if (! $response->successful() || ! is_array($rows) || ! array_is_list($rows)) {
            return ['error' => 'relay ตอบผิดรูปแบบ (HTTP ' . $response->status() . ')'] + $result;
        }

        return [
            'ok' => true,
            'workers' => count($rows),
            'online' => count(array_filter($rows, fn ($row) => is_array($row) && ($row['online'] ?? false) === true)),
        ] + $result;
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

    /**
     * ปลายทางที่จะเก็บให้ worker ที่ relay เพิ่งออก — คิดจาก GPUXMINE_RELAY_URL ไม่ใช่จาก
     * aixmanEndpoint ที่ relay ตอบมา
     *
     * relay สร้าง aixmanEndpoint จาก scheme และ host ของคำขอที่มันเห็น ซึ่งอยู่หลัง nginx:
     * ตอนติดตั้งครั้งแรก proxy ตั้งผิด แถวที่จับคู่ช่วงนั้นได้ http:// หรือ URL ที่หลุด :8443
     * aixman รุ่นใหม่ตอบ 400 กับปลายทางที่ไม่ใช่ https ทุกครั้ง เครื่องนั้นไม่ได้งานเลย
     * ค่าที่ตั้งไว้เป็นของจริงของการติดตั้ง — ใช้ค่าที่ relay ตอบเฉพาะเมื่อค่าที่ตั้งไว้เองใช้ไม่ได้
     */
    public function endpointFor(string $workerId, ?string $reported = null): string
    {
        $configured = $this->tunnelEndpoint($workerId);

        return ! self::acceptableEndpoint($configured) && self::acceptableEndpoint($reported)
            ? (string) $reported
            : $configured;
    }

    /**
     * aixman รับปลายทางนี้ไหม — กติกาเดียวกับ endpointProblem() ของ aixman
     * (src/lib/gpu/community-push.ts): https เท่านั้น ยกเว้น http ไปเครื่องตัวเองตอนพัฒนา
     * กุญแจของ aixman วิ่งไปกับทุกคำขอ ผ่าน http คือทุกคนระหว่างทางอ่านได้
     */
    public static function acceptableEndpoint(?string $url): bool
    {
        if ($url === null || $url === '') {
            return false;
        }

        $parts = parse_url($url);
        if (! is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '') {
            return false;
        }

        return $scheme === 'https'
            || ($scheme === 'http' && in_array($host, ['localhost', '127.0.0.1'], true));
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
