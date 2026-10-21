<?php

namespace App\Services;

use App\Models\GpuNode;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
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
    /** aixman ตอบรับด้วย 2xx — ลายนิ้วมือของข้อมูลชุดนี้ถูกบันทึกแล้ว */
    public const OUTCOME_OK = 'ok';

    /** aixman ตอบแล้วแต่ไม่รับ (4xx หรือคำตอบอ่านไม่ออก) */
    public const OUTCOME_ERROR = 'error';

    /** ติดต่อ aixman ไม่ได้ หรือ aixman ล่ม (5xx) — ยิงเครื่องถัดไปก็คงไม่ต่างกัน */
    public const OUTCOME_UNREACHABLE = 'unreachable';

    public const OUTCOME_UNCONFIGURED = 'unconfigured';

    /** เครื่องยังไม่มีตัวตนบน relay ให้ส่ง */
    public const OUTCOME_SKIPPED = 'skipped';

    /**
     * ค่าที่ส่งไปทุกครั้งแต่ไม่นับเป็น "ข้อมูลเปลี่ยน"
     *
     * busy สลับทุกครั้งที่เครื่องเริ่มหรือจบงาน และ aixman รู้เองอยู่แล้วว่า
     * ส่งงานไปให้ใคร ส่วน score ขยับนิดหน่อยระหว่างการวัดสองครั้งซึ่งไม่ใช่
     * เหตุให้ยิง — ทั้งคู่ไปถึง aixman อยู่ดีในรอบส่งซ้ำตามรอบเวลา
     */
    private const VOLATILE_FIELDS = ['busy', 'score'];

    public function __construct(
        private readonly GpuxMineRelayService $relay,
    ) {}

    public function isConfigured(): bool
    {
        return (bool) config('services.aixman.api_base')
            && (bool) config('services.aixman.webhook_secret');
    }

    /**
     * สิ่งที่ aixman ได้รู้เกี่ยวกับเครื่องนี้ (สัญญา C1)
     *
     * ฟิลด์ตั้งแต่ freeSharePct ลงไปเป็นของใหม่ aixman รุ่นเก่ามองข้ามได้
     * โดยไม่เสียอะไร
     *
     * @return array<string, mixed>
     */
    public function payload(GpuNode $node): array
    {
        $suspended = $node->isSuspended();

        return [
            'workerId' => $node->worker_id,
            'endpoint' => $this->endpoint($node),
            // กุญแจที่ aixman ต้องใช้เปิด /w/ — ใบแยกของ aixman ถ้า relay ออกให้
            'token' => $node->dispatchToken(),
            'label' => $node->displayName(),
            // เครื่องที่ถูกระงับถูกส่งเป็นออฟไลน์ด้วย: aixman รุ่นที่ยังไม่รู้จัก `suspended`
            // (และ relay รุ่นที่ยังตัดสายไม่ได้) จะยังส่งงานให้เครื่องที่ถูกระงับต่อ ขณะที่
            // รายได้ของมันถูกพักไว้ — เจ้าของทำงานฟรีโดยไม่รู้ตัว ออฟไลน์คือสิ่งที่ทุกรุ่น
            // เข้าใจและถอดออกจากคิว รุ่นใหม่ดู `suspended` อยู่แล้ว
            'online' => (bool) $node->online && ! $suspended,
            'assessed' => (bool) $node->assessed,
            'gpuName' => $node->gpu_name,
            'vramTotalMb' => (int) $node->vram_total_mb,
            'score' => (int) $node->score,
            'tier' => $node->tier,
            'canRun' => $node->can_run ?? [],
            // ส่งไปด้วยเสมอ แม้ว่าง: aixman อ่าน "ไม่มีข้อมูล" เป็นเร็วเต็มที่
            // ซึ่งถูกสำหรับโหนดที่ยังไม่ได้อัปเดตไคลเอนต์
            'lanes' => $node->lanes ?? [],
            'provisional' => $node->provisional ?? [],
            'ownerUserId' => $node->user_id,
            'freeSharePct' => max(0, min(100, (int) $node->free_share_pct)),
            'pro' => $this->isProMiner($node),
            // null = ไม่รู้ (ไคลเอนต์รุ่นเก่าไม่ได้บอก หรือเครื่องออฟไลน์อยู่)
            'accepting' => $node->accepting,
            'busy' => $node->busy,
            'referrerUserId' => $node->referrer_user_id,
            'firstPairedAt' => $this->firstPairedAt((int) $node->user_id),
            // แอดมินระงับ — aixman ต้องหยุดส่งงาน แต่ถอยกลับได้ ต่างจากการถอน
            'suspended' => $suspended,
        ];
    }

    /**
     * ปลายทางที่ส่งให้ aixman — ค่าที่เก็บไว้ ยกเว้นเมื่อ aixman ไม่รับมัน (http ไปเครื่องอื่น
     * หรือว่าง) และคิดจาก GPUXMINE_RELAY_URL ได้ค่าที่รับ
     *
     * aixman รุ่นใหม่ตอบ 400 กับปลายทางที่ไม่ใช่ https ทุกครั้ง แถวที่จับคู่ตอน proxy หน้า relay
     * ตั้งผิดเก็บ http ไว้ ถ้าส่งตามที่เก็บ ทุกการส่ง (รวมการระงับที่แอดมินเพิ่งกด) ตกหมดจนกว่า
     * gpuxmine:sync-nodes จะเขียนแถวให้ตรง (GpuxMineNodeStateService::apply) ค่าที่ส่งตรงนี้
     * เท่ากับที่ apply() จะเขียน ลายนิ้วมือจึงไม่ขยับซ้ำเมื่อแถวถูกแก้ตามมา
     */
    private function endpoint(GpuNode $node): ?string
    {
        $stored = $node->tunnel_endpoint;

        if ($node->worker_id === null
            || GpuxMineRelayService::acceptableEndpoint($stored)
            || ! $this->relay->isConfigured()) {
            return $stored;
        }

        $configured = $this->relay->tunnelEndpoint($node->worker_id);

        return GpuxMineRelayService::acceptableEndpoint($configured) ? $configured : $stored;
    }

    /**
     * aixman ที่ตอบเครื่องนี้ครั้งล่าสุดเป็นรุ่นที่รับสัญญา C1 แล้วหรือยัง
     *
     * รุ่นก่อนสัญญานี้ (main) ก็คืน worker.status อยู่แล้ว ({id, externalId, status,
     * modelKey}) จึงใช้ "มี status" แยกรุ่นไม่ได้ — ตัวที่มีเฉพาะรุ่นใหม่คือ lastError
     * (มี key เสมอ แม้ค่าเป็น null) push() เก็บ dispatch_worker_status เฉพาะเมื่อเห็น key นี้
     * ค่านี้ไม่เป็น null จึงแปลว่ารุ่นใหม่
     *
     * สำคัญเพราะรุ่นเก่าเขียน status = warming ทับแถวทุกครั้งที่ได้ข้อมูลเครื่อง รวมแถว
     * ที่กำลังเรนเดอร์ แล้วตัวเก็บกวาดของมันก็ฆ่า worker ที่ warming นานเกินชั่วโมง
     * การส่งซ้ำตามรอบเวลา และการส่งทุกครั้งที่ accepting พลิก จึงทำได้กับรุ่นใหม่เท่านั้น
     */
    public function knowsCurrentContract(GpuNode $node): bool
    {
        return $node->dispatch_worker_status !== null;
    }

    /**
     * ลายนิ้วมือของข้อมูลชุดที่จะส่ง
     *
     * ตัวจับเวลาเทียบค่านี้กับ dispatch_fingerprint (ชุดล่าสุดที่ aixman
     * ตอบรับ) แทนการจำว่า "เมื่อกี้เห็นอะไร" — หน้าเว็บจะเขียนแถวไปก่อนกี่รอบ
     * ก็ไม่ทำให้การส่งหายอีก เพราะสิ่งที่ aixman ยังไม่ได้รับยังต่างจากที่
     * aixman รับไปแล้วอยู่ดี
     *
     * token ถูกย่อเป็น sha1 ก่อนเข้าลายนิ้วมือ — ค่าที่เก็บลงฐานข้อมูลไม่ควร
     * มาจากความลับตรง ๆ แม้จะผ่าน hash อีกชั้นแล้วก็ตาม
     *
     * accepting นับเป็นข้อมูลเปลี่ยนเฉพาะกับ aixman รุ่นที่รับสัญญานี้ ($current)
     * มันพลิกทุกครั้งที่เจ้าของขยับเมาส์ (YieldWhenActive) เปิดเกมเต็มจอ หรือการ์ดร้อน
     * ถึงเพดาน และ aixman รุ่นเก่าไม่ได้ใช้ค่านี้เลย แต่เขียนแถวเป็น warming ทุกครั้งที่
     * ได้ข้อมูล — ส่งทุกครั้งที่พลิกคือฆ่างานที่กำลังเรนเดอร์ ค่ายังไปถึงทุกครั้งที่ส่งด้วย
     * เหตุอื่น $current = null คือให้ตัดสินจากแถว (knowsCurrentContract)
     *
     * @param  array<string, mixed>|null  $payload
     */
    public function fingerprint(GpuNode $node, ?array $payload = null, ?bool $current = null): string
    {
        $basis = $payload ?? $this->payload($node);
        $current ??= $this->knowsCurrentContract($node);

        foreach (self::VOLATILE_FIELDS as $field) {
            unset($basis[$field]);
        }
        if (! $current) {
            unset($basis['accepting']);
        }
        $basis['token'] = sha1((string) ($basis['token'] ?? ''));

        return sha1((string) json_encode($basis, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * แจ้ง aixman ว่ามีเครื่องนี้ และมันทำอะไรได้บ้าง
     *
     * เขียนผลกลับลงแถวเสมอ ทั้งสำเร็จและไม่สำเร็จ เพราะหน้า "เครื่องของฉัน"
     * ต้องตอบเจ้าของให้ได้ว่าทำไมเครื่องที่เปิดค้างไว้ทั้งคืนถึงยังไม่ได้งาน
     *
     * dispatch_fingerprint ขยับเฉพาะเมื่อ aixman ตอบ 2xx — ส่งไม่ถึงหรือ aixman
     * ไม่รับ แถวจะอยู่ในสถานะ error ซึ่งตัวจับเวลาถือเป็นเหตุให้ส่งซ้ำรอบหน้า
     *
     * @return string หนึ่งใน OUTCOME_*
     */
    public function push(GpuNode $node): string
    {
        if ($node->worker_id === null || $node->dispatchToken() === null) {
            return self::OUTCOME_SKIPPED;
        }

        // อ่านค่าเหล่านี้ใหม่จากฐานข้อมูลก่อนส่งทุกครั้ง — ตัวจับเวลาโหลดเครื่องทีละร้อย แถวในมือ
        // อาจเก่ากว่าที่แอดมินเพิ่งระงับ/แบน เจ้าของเพิ่งถอน หรือ gpuxmine:rotate-tunnel-tokens
        // เพิ่งเก็บกุญแจใหม่ ส่งของเก่าไปคือ aixman ปลดการระงับเอง ฟื้น worker ที่เพิ่งถอน
        // กลับมา หรือได้กุญแจที่ relay ไม่รับแล้ว
        if ($node->exists) {
            $fresh = ['suspended_at', 'relay_token', 'tunnel_token'];
            $current = GpuNode::withTrashed()->whereKey($node->getKey())->first(['id', 'deleted_at', ...$fresh]);
            if ($current === null || $current->trashed()) {
                return self::OUTCOME_SKIPPED;
            }
            $raw = [];
            foreach ($fresh as $column) {
                $raw[$column] = $current->getRawOriginal($column);
            }
            $node->setRawAttributes($raw + $node->getAttributes());
            foreach ($fresh as $column) {
                $node->syncOriginalAttribute($column);
            }
        }

        if (! $this->isConfigured()) {
            $node->forceFill([
                'dispatch_status' => 'unconfigured',
                'dispatch_note' => 'ยังไม่ได้ตั้งค่าเชื่อมต่อ aixman',
                'dispatch_synced_at' => now(),
            ])->save();

            return self::OUTCOME_UNCONFIGURED;
        }

        $payload = $this->payload($node);

        try {
            $response = $this->aixman()
                ->retry(2, 500, throw: false)
                ->post($this->nodesUrl(), $payload);

            $body = $response->json();

            if ($response->successful() && is_array($body)) {
                $worker = is_array($body['worker'] ?? null) ? $body['worker'] : [];
                // รุ่นที่รับสัญญานี้ใส่ lastError มาเสมอ (null ได้) — รุ่นก่อนหน้าก็คืน status
                // แต่ไม่มี lastError ดู knowsCurrentContract()
                $current = array_key_exists('lastError', $worker) && isset($worker['status']);

                $node->forceFill([
                    'dispatch_status' => mb_substr((string) ($body['status'] ?? 'unknown'), 0, 32),
                    'dispatch_note' => isset($body['note']) ? mb_substr((string) $body['note'], 0, 255) : null,
                    'dispatch_synced_at' => now(),
                    // ลายนิ้วมือคิดตามรุ่นของ aixman ที่เพิ่งตอบ ให้ตรงกับที่ needsPush() จะเทียบ
                    'dispatch_fingerprint' => $this->fingerprint($node, $payload, $current),
                    // aixman รุ่นเก่า: ไม่เก็บ — null คือ "ยังไม่รู้" และคือสัญญาณว่าห้ามส่งซ้ำตามรอบ
                    'dispatch_worker_status' => $current ? mb_substr((string) $worker['status'], 0, 32) : null,
                    'dispatch_last_error' => $current && ! empty($worker['lastError']) ? mb_substr((string) $worker['lastError'], 0, 255) : null,
                ])->save();

                return self::OUTCOME_OK;
            }

            Log::error('GPUxMINE node sync to aixman failed', [
                'worker_id' => $node->worker_id,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            $node->forceFill([
                'dispatch_status' => 'error',
                'dispatch_note' => 'aixman ตอบ HTTP ' . $response->status(),
                'dispatch_synced_at' => now(),
            ])->save();

            return $response->serverError() ? self::OUTCOME_UNREACHABLE : self::OUTCOME_ERROR;
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

            return self::OUTCOME_UNREACHABLE;
        }
    }

    /**
     * ส่งแล้วบอกว่าได้สิทธิ์รับงานไหม — สำหรับผู้เรียกที่สนใจแค่นั้น
     */
    public function sync(GpuNode $node): bool
    {
        return $this->push($node) === self::OUTCOME_OK
            && $node->dispatch_status === 'eligible';
    }

    /**
     * บอก aixman ให้หยุดส่งงานไปที่ worker นี้
     *
     * คืน true เมื่อ aixman ยืนยัน (2xx หรือ 404 = ไม่มีแถวนี้แล้ว) เท่านั้น
     * เคยยิงแล้วไม่ดูคำตอบเลย ถอนไม่สำเร็จ aixman ก็ส่งงานไปเครื่องที่เจ้าของ
     * ถอนออกไปแล้วต่อไปเรื่อย ๆ
     */
    public function retireWorker(string $workerId): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $response = $this->aixman()->delete($this->nodesUrl(), ['workerId' => $workerId]);

            if ($response->successful() || $response->status() === 404) {
                return true;
            }

            Log::warning('GPUxMINE node retire refused by aixman', [
                'worker_id' => $workerId,
                'status' => $response->status(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::warning('GPUxMINE node retire failed', [
                'worker_id' => $workerId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** เครื่องถูกถอนออกจากระบบ — หยุดส่งงานให้มัน แต่ไม่ยุ่งกับเครื่องเจ้าของ */
    public function retire(GpuNode $node): bool
    {
        return $node->worker_id === null || $this->retireWorker($node->worker_id);
    }

    /**
     * aixman รับเราไหม — โดยไม่เขียนอะไรที่นั่น (gpuxmine:doctor)
     *
     * ยิง POST /api/gpux/nodes ด้วย body ที่ไม่มี workerId: aixman ตรวจความลับ
     * ก่อนตรวจ body เสมอ ดังนั้น 400 = ถึงแล้วและความลับตรง (และไม่มีแถวไหนถูก
     * เขียน เพราะ body ไม่ผ่านการตรวจ), 401 = ความลับสองฝั่งไม่ตรงกัน,
     * 404/405 = aixman ตัวนั้นยังไม่มีเส้นทางนี้
     *
     * @return array{ok:bool, status:?int, error:?string}
     */
    public function probe(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'status' => null, 'error' => 'ยังไม่ได้ตั้งค่า'];
        }

        try {
            $response = $this->aixman()->post($this->nodesUrl(), ['probe' => 'gpuxmine:doctor']);
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => null, 'error' => 'ติดต่อไม่ได้ (' . class_basename($e) . ')'];
        }

        $status = $response->status();

        return match (true) {
            $status === 400 => ['ok' => true, 'status' => $status, 'error' => null],
            $status === 401, $status === 403 => ['ok' => false, 'status' => $status, 'error' => 'aixman ไม่รับ AIXMAN_WEBHOOK_SECRET (ต้องตรงกับ XMAN_WEBHOOK_SECRET ของ aixman)'],
            $status === 404, $status === 405 => ['ok' => false, 'status' => $status, 'error' => 'aixman ตัวนี้ยังไม่มี /api/gpux/nodes'],
            default => ['ok' => false, 'status' => $status, 'error' => 'aixman ตอบ HTTP ' . $status . ' (คาดว่าจะได้ 400)'],
        };
    }

    /**
     * จุดต่อของ Pro Miner (D10) — ยังคืน false เสมอ
     *
     * Pro Miner ยังไม่มีราคาและยังไม่มีไลเซนส์ไหนถูกตีธงว่าเป็น pro
     * (ผลิตภัณฑ์ gpuxmine ราคา 0 ไม่มีแพ็กเกจ) วันที่ขายจริง ให้ตอบตรงนี้ว่า
     * เจ้าของเครื่องถือไลเซนส์ gpuxmine ที่ active และเป็น pro อยู่หรือไม่
     * แล้วทุกอย่างปลายทางจะได้ค่านี้ไปเอง
     */
    public function isProMiner(GpuNode $node): bool
    {
        return false;
    }

    /**
     * วันที่เจ้าของคนนี้จับคู่เครื่องแรก รวมเครื่องที่ถอนไปแล้ว
     *
     * aixman ใช้นับช่วงเวลาที่ผู้แนะนำยังได้ส่วนแบ่ง — ต้องเป็นวันแรกจริง
     * ไม่ใช่วันของเครื่องล่าสุด ไม่งั้นถอนแล้วจับคู่ใหม่ก็ต่ออายุได้เรื่อย ๆ
     */
    public function firstPairedAt(int $userId): ?string
    {
        $first = GpuNode::withTrashed()
            ->where('user_id', $userId)
            ->whereNotNull('paired_at')
            ->min('paired_at');

        return $first ? Carbon::parse($first)->toIso8601String() : null;
    }

    private function aixman(): PendingRequest
    {
        return Http::withHeaders(['x-webhook-secret' => (string) config('services.aixman.webhook_secret')])
            ->timeout((int) config('services.aixman.timeout', 10));
    }

    private function nodesUrl(): string
    {
        return rtrim((string) config('services.aixman.api_base'), '/') . '/api/gpux/nodes';
    }
}
