<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\GpuNode;
use App\Models\Product;
use App\Models\ProductDevice;
use App\Services\GpuxMineDispatchService;
use App\Services\GpuxMineNodeStateService;
use App\Services\GpuxMineReferrerResolver;
use App\Services\GpuxMineRelayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ทางเดียวที่โปรแกรมไคลเอนต์ได้ตัวตนของตัวเอง
 *
 * ก่อนหน้านี้ไม่มีทางนี้เลย — คนติดตั้งโปรแกรมแล้วเปิดมา เห็นคำว่า
 * "ยังไม่ได้ลงทะเบียน" โดยไม่มีปุ่มอะไรให้กด เพราะ WorkerId กับ Token
 * มีแต่คนที่รันคำสั่งบน relay เองเท่านั้นที่ออกได้
 *
 * ขั้นตอน: เจ้าของล็อกอินเว็บ กด "เพิ่มเครื่อง" ได้รหัสจับคู่ 8 ตัว
 * พิมพ์ลงในโปรแกรม โปรแกรมยิงมาที่นี่พร้อม machine id ของเครื่อง
 * เราขอ worker จาก relay ผูกกับบัญชีเจ้าของ แล้วคืน credential ให้ครั้งเดียว
 *
 * เว็บเป็นฝ่ายออกรหัส ไม่ใช่โปรแกรม เพราะคนที่ล็อกอินอยู่แล้วคือคนที่เรารู้
 * ว่าเป็นใคร — โปรแกรมที่เพิ่งติดตั้งยังไม่ได้พิสูจน์อะไรเลย
 */
class GpuxMineNodeController extends Controller
{
    private const PRODUCT_SLUG = 'gpuxmine';

    public function __construct(
        private readonly GpuxMineRelayService $relay,
        private readonly GpuxMineDispatchService $dispatch,
        private readonly GpuxMineNodeStateService $state,
        private readonly GpuxMineReferrerResolver $referrers,
    ) {}

    /**
     * ยอดแนะนำเพื่อนของเจ้าของเครื่อง สำหรับแสดงในโปรแกรม
     *
     * หน้า Referrals ในโปรแกรมเคยเป็นข้อความอย่างเดียว ขึ้นว่า "จะแสดงเมื่อ
     * เชื่อมบัญชี" กับขีดกลางแทนตัวเลขทุกช่อง ทั้งที่ระบบ affiliate บนเว็บ
     * มีข้อมูลครบอยู่แล้ว เจ้าของเครื่องจึงไม่เคยเห็นว่าตัวเองชวนใครได้บ้าง
     * นอกจากจะเปิดเว็บไปดูเอง
     *
     * ยืนยันตัวด้วย worker id กับ token ของ relay ไม่ใช่ machine id เปล่า ๆ
     * machine id เดาได้จากเครื่องเดียวกัน แต่ token ออกให้ครั้งเดียวตอนจับคู่
     * และนี่คือข้อมูลรายได้ ไม่ใช่ข้อมูลสาธารณะ
     */
    public function referral(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'worker_id' => ['required', 'string', 'max:64'],
            'token' => ['required', 'string', 'max:256'],
        ]);

        $node = GpuNode::where('worker_id', $validated['worker_id'])->whereNotNull('paired_at')->first();

        // เทียบแบบเวลาคงที่ เพราะการเทียบสตริงธรรมดาบอกความยาวของ token
        // ที่ถูกต้องผ่านเวลาที่ใช้ตอบ
        if (! $node || ! hash_equals((string) $node->relay_token, $validated['token'])) {
            return response()->json(['success' => false, 'message' => 'ตัวตนเครื่องไม่ถูกต้อง'], 401);
        }

        $affiliate = Affiliate::where('user_id', $node->user_id)->first();

        if (! $affiliate) {
            // ยังไม่ได้สมัครเป็นผู้แนะนำ ไม่ใช่ข้อผิดพลาด — บอกไปตรง ๆ
            // พร้อมที่ที่ไปสมัคร ดีกว่าคืนศูนย์ให้เข้าใจผิดว่าชวนแล้วไม่ได้อะไร
            return response()->json([
                'success' => true,
                'data' => [
                    'enrolled' => false,
                    'join_url' => url('/affiliate'),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'enrolled' => true,
                'referral_code' => $affiliate->referral_code,
                'referral_url' => url('/?ref=' . $affiliate->referral_code),
                'commission_rate' => (float) $affiliate->commission_rate,
                'total_referrals' => (int) $affiliate->total_referrals,
                'total_conversions' => (int) $affiliate->total_conversions,
                // บาททศนิยมสองตำแหน่งตามที่เก็บไว้ ไม่แปลงหน่วยระหว่างทาง
                'total_earned' => (float) $affiliate->total_earned,
                'total_paid' => (float) $affiliate->total_paid,
                'total_pending' => (float) $affiliate->total_pending,
                'status' => $affiliate->status,
                'dashboard_url' => url('/affiliate'),
            ],
        ]);
    }

    public function claim(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // รหัสจับคู่จากหน้าเว็บ รับทั้งมีขีดและไม่มี เพราะคนพิมพ์ตามที่เห็น
            'pairing_code' => 'required|string|max:16',
            'machine_id' => 'required|string|min:32|max:64',
            'machine_name' => 'nullable|string|max:255',
            'os_version' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:50',
            'hardware_hash' => 'nullable|string|max:64',
        ]);

        $code = $this->normaliseCode($validated['pairing_code']);

        /** @var GpuNode|null $node */
        $node = GpuNode::where('pairing_code', $code)->first();

        if ($node === null || ! $node->pairingIsUsable()) {
            return $this->pairingInvalid();
        }

        // จองรหัสก่อนทำอะไรต่อ ด้วย UPDATE แบบมีเงื่อนไขที่ชนะได้คนเดียว —
        // ไม่งั้นโปรแกรมที่กดยืนยันซ้ำ หรือสองเครื่องที่พิมพ์รหัสเดียวกันพร้อมกัน
        // จะได้ worker คนละตัวจาก relay ทั้งคู่ แล้วตัวหนึ่งกลายเป็นกำพร้า
        // ระหว่างที่จองอยู่ รหัสนี้ใช้ไม่ได้ (pairingIsUsable = false) ถ้าจบ
        // แบบไม่สำเร็จ คืนรหัสให้ใช้ต่อได้จนหมดอายุเดิม
        $expiresAt = $node->pairing_expires_at;
        $taken = GpuNode::whereKey($node->id)
            ->whereNull('paired_at')
            ->where('pairing_code', $code)
            ->where('pairing_expires_at', '>', now())
            ->update(['pairing_expires_at' => null]);

        if ($taken !== 1) {
            return $this->pairingInvalid();
        }

        $response = null;

        try {
            $response = $this->claimTaken($node, $validated);
        } finally {
            if ($response === null || $response->getStatusCode() >= 300) {
                GpuNode::whereKey($node->id)
                    ->whereNull('paired_at')
                    ->whereNull('pairing_expires_at')
                    ->update(['pairing_expires_at' => $expiresAt]);
            }
        }

        return $response;
    }

    /** รหัสที่จองไว้แล้ว — จากตรงนี้ไม่มีใครแย่งรหัสนี้ได้อีก */
    private function claimTaken(GpuNode $node, array $validated): JsonResponse
    {
        // เครื่องเดิมที่เคยจับคู่แล้วมาขอใหม่ (ลงโปรแกรมใหม่ / ล้างเครื่อง):
        // คืน worker เดิม ไม่สร้างใหม่ ไม่งั้นประวัติงานและคะแนนสะสมขาดตอน
        $existing = GpuNode::where('user_id', $node->user_id)
            ->where('machine_id', $validated['machine_id'])
            ->whereNotNull('worker_id')
            ->whereNull('deleted_at')
            ->first();

        // ...แต่คืนของเดิมได้ก็ต่อเมื่อ relay ตัวปัจจุบันยังรู้จัก worker นั้นจริง
        //
        // ย้าย relay ไปอีกเครื่องเมื่อไร `workers.json` เริ่มนับหนึ่งใหม่ และ
        // token เดิมใช้ไม่ได้ทันที ถ้าไม่เช็กตรงนี้ เจ้าของเครื่องจะได้ credential
        // ที่ relay ปฏิเสธ แล้วนั่งงงว่าลงทะเบียนสำเร็จแต่ทำไมไม่เคยได้งาน
        // (เจอตอนย้าย relay จากเครื่อง dev ขึ้นเซิร์ฟเวอร์จริง)
        if ($existing !== null) {
            $known = $this->relay->knows($existing->worker_id);

            // ถาม relay ไม่ได้ ≠ relay ไม่รู้จัก — ถ้าเดาว่าไม่รู้จักแล้วออก
            // worker ใหม่ ตัวเดิมที่ยังใช้ได้ดีจะถูกทิ้ง ให้ลองใหม่ดีกว่า
            if ($known === null) {
                return $this->relayUnavailable();
            }

            if ($known) {
                if ($existing->referrer_user_id === null) {
                    $existing->referrer_user_id = $node->referrer_user_id
                        ?? $this->referrers->resolve((int) $existing->user_id, fromRequest: false);
                }

                $node->delete();   // รหัสที่เพิ่งออกไม่ได้ใช้ ทิ้งไป

                $response = $this->credentials($existing, $validated, reused: true);

                // ที่อยู่ relay อาจเพิ่งเปลี่ยนใน credentials() — aixman ต้องรู้
                // ด้วย ไม่งั้นมันยิงงานไปที่อยู่เดิมต่อ
                $this->dispatch->sync($existing);

                return $response;
            }
        }

        // เพดานเครื่องต่อบัญชี (D9) — นับเฉพาะเครื่องใหม่ เครื่องเดิมที่กลับมา
        // จับคู่ซ้ำไม่ได้เพิ่มจำนวน
        if ($existing === null && $this->atNodeCap((int) $node->user_id)) {
            $cap = (int) config('services.gpuxmine.max_nodes_per_user', 10);

            return response()->json([
                'success' => false,
                'error_code' => 'NODE_LIMIT',
                'message' => "บัญชีนี้ลงทะเบียนเครื่องครบ {$cap} เครื่องแล้ว — ถอนเครื่องที่ไม่ได้ใช้ออกที่หน้าเครื่องของฉันก่อน แล้วขอรหัสใหม่",
            ], 409);
        }

        $label = $validated['machine_name'] ?: ('เครื่องของ ' . ($node->user?->name ?? 'สมาชิก'));
        $enrolment = $this->relay->enroll($label);

        if ($enrolment === null) {
            return $this->relayUnavailable();
        }

        $device = $this->rememberDevice($validated);

        // เครื่องเดิมที่ worker หายไปจาก relay: ออก worker ใหม่ให้ แต่เขียนทับ
        // แถวเดิม ไม่สร้างแถวใหม่ — เจ้าของ ชื่อเครื่อง และประวัติยังอยู่ที่เดิม
        if ($existing !== null) {
            $oldWorkerId = $existing->worker_id;

            DB::transaction(function () use ($existing, $node, $enrolment, $validated) {
                $existing->forceFill([
                    'worker_id' => $enrolment['workerId'],
                    'relay_token' => $enrolment['token'],
                    // relay รุ่นเก่าไม่ออกใบนี้ — ต้องล้างใบของ worker เก่าทิ้ง
                    // ไม่งั้น aixman ได้กุญแจที่เปิดอุโมงค์ของ worker ใหม่ไม่ได้
                    'tunnel_token' => $enrolment['tunnelToken'],
                    'relay_url' => $enrolment['agentRelayUrl'],
                    'tunnel_endpoint' => $enrolment['aixmanEndpoint'],
                    'agent_version' => $validated['app_version'] ?? $existing->agent_version,
                    'online' => false,
                    'assessed' => false,
                    'accepting' => null,
                    'busy' => null,
                    'referrer_user_id' => $existing->referrer_user_id
                        ?? $node->referrer_user_id
                        ?? $this->referrers->resolve((int) $existing->user_id, fromRequest: false),
                    'dispatch_status' => null,
                    'dispatch_note' => null,
                    'dispatch_fingerprint' => null,
                    'dispatch_worker_status' => null,
                    'dispatch_last_error' => null,
                ])->save();

                $node->delete();
            });

            // worker เก่าต้องออกจากทั้ง aixman และ relay — เคยปล่อยค้างไว้
            // ให้ aixman ส่งงานไปหาต่อ ถ้าถอนไม่สำเร็จตอนนี้ ตัวจับเวลาลองต่อ
            $this->state->retireReplacedWorker($existing, $oldWorkerId);
            $this->dispatch->sync($existing);

            Log::info('GPUxMINE node re-enrolled on a new relay', [
                'user_id' => $existing->user_id,
                'worker_id' => $existing->worker_id,
                'replaced_worker_id' => $oldWorkerId,
            ]);

            return $this->credentials($existing, $validated, reused: false);
        }

        $node->forceFill([
            'product_device_id' => $device?->id,
            'machine_id' => $validated['machine_id'],
            'label' => $label,
            'worker_id' => $enrolment['workerId'],
            'relay_token' => $enrolment['token'],
            'tunnel_token' => $enrolment['tunnelToken'],
            'relay_url' => $enrolment['agentRelayUrl'],
            'tunnel_endpoint' => $enrolment['aixmanEndpoint'],
            'agent_version' => $validated['app_version'] ?? null,
            'paired_at' => now(),
            // ผู้แนะนำ: ถ้าหน้าเว็บจับไว้ตอนออกรหัสแล้วใช้ตัวนั้น (D8)
            'referrer_user_id' => $node->referrer_user_id
                ?? $this->referrers->resolve((int) $node->user_id, fromRequest: false),
            // ใช้แล้วใช้อีกไม่ได้ ล้างทิ้งทันทีที่แลกสำเร็จ
            'pairing_code' => null,
            'pairing_expires_at' => null,
        ])->save();

        // ขึ้นทะเบียนที่ aixman ทันที ตอนนี้เครื่องยังไม่ได้ประเมินตัวเอง
        // จึงยังไม่มีสิทธิ์รับงาน — แต่แถวต้องมีอยู่ก่อน ไม่งั้นพอประเมินเสร็จ
        // จะไม่มีอะไรให้ปรับสถานะ
        $this->dispatch->sync($node);

        Log::info('GPUxMINE node paired', [
            'user_id' => $node->user_id,
            'worker_id' => $node->worker_id,
        ]);

        return $this->credentials($node, $validated, reused: false);
    }

    private function atNodeCap(int $userId): bool
    {
        $cap = (int) config('services.gpuxmine.max_nodes_per_user', 10);

        return $cap > 0
            && GpuNode::where('user_id', $userId)->whereNotNull('paired_at')->count() >= $cap;
    }

    private function pairingInvalid(): JsonResponse
    {
        // ข้อความเดียวกันทั้งกรณีรหัสผิดและรหัสหมดอายุ เพื่อไม่ให้คนที่
        // สุ่มรหัสรู้ว่าเดาถูกบางส่วน — แต่บอกทางออกให้คนที่พิมพ์ถูกแล้ว
        // รหัสหมดอายุพอดี
        return response()->json([
            'success' => false,
            'error_code' => 'PAIRING_INVALID',
            'message' => 'รหัสจับคู่ไม่ถูกต้องหรือหมดอายุแล้ว — กดขอรหัสใหม่ที่หน้าเครื่องของฉัน',
        ], 422);
    }

    private function relayUnavailable(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error_code' => 'RELAY_UNAVAILABLE',
            'message' => 'ระบบรับเครื่องยังไม่พร้อม กรุณาลองใหม่อีกครั้งในอีกสักครู่',
        ], 503);
    }

    private function credentials(GpuNode $node, array $validated, bool $reused): JsonResponse
    {
        // เครื่องเดิมกลับมา: อัปเดตสิ่งที่อาจเปลี่ยน แล้วคืนของเดิม
        if ($reused) {
            $node->forceFill([
                'agent_version' => $validated['app_version'] ?? $node->agent_version,
                'paired_at' => $node->paired_at ?? now(),
                // ที่อยู่ของ relay เป็นเรื่องของการติดตั้ง ไม่ใช่คุณสมบัติของเครื่อง
                // ถ้าย้าย relay ไปพอร์ตหรือโฮสต์ใหม่ เครื่องที่กลับมาจับคู่ต้อง
                // ได้ที่อยู่ปัจจุบัน ไม่ใช่ที่อยู่ที่เขียนไว้ตั้งแต่วันแรก
                'relay_url' => $this->relay->agentUrl(),
                'tunnel_endpoint' => $this->relay->tunnelEndpoint($node->worker_id),
            ])->save();
        }

        return response()->json([
            'success' => true,
            'message' => $reused
                ? 'เครื่องนี้เคยลงทะเบียนไว้แล้ว — เชื่อมต่อด้วยข้อมูลเดิม'
                : 'ลงทะเบียนเครื่องเรียบร้อย',
            'data' => [
                'worker_id' => $node->worker_id,
                'token' => $node->relay_token,
                'relay_url' => $node->relay_url,
                'label' => $node->displayName(),
                'owner' => $node->user?->name,
            ],
        ]);
    }

    /**
     * บันทึกเครื่องลงทะเบียนอุปกรณ์กลางที่ทุกผลิตภัณฑ์ใช้ร่วมกัน
     *
     * ไม่ใช่ของประดับ: ไลเซนส์ Pro Miner ผูกกับ machine_id ผ่านตารางนี้
     * และการตรวจการใช้ทรัยอัลซ้ำก็อ่านจากที่นี่ที่เดียว
     */
    private function rememberDevice(array $validated): ?ProductDevice
    {
        $product = Product::where('slug', self::PRODUCT_SLUG)->first();
        if ($product === null) {
            // ยังไม่ได้รัน migration ที่ลงทะเบียนผลิตภัณฑ์ — จับคู่ต่อได้
            // เพราะการรับงานไม่ได้ต้องใช้ไลเซนส์ แค่บันทึกไว้ไม่ได้เท่านั้น
            Log::warning('GPUxMINE product row missing — node paired without a device record');

            return null;
        }

        return ProductDevice::updateOrCreate(
            ['product_id' => $product->id, 'machine_id' => $validated['machine_id']],
            [
                'machine_name' => $validated['machine_name'] ?? null,
                'os_version' => $validated['os_version'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
                'hardware_hash' => $validated['hardware_hash'] ?? null,
                'last_ip' => request()->ip(),
                'first_ip' => request()->ip(),
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]
        );
    }

    /** "abcd efgh" · "abcd-efgh" · "ABCDEFGH" ล้วนเป็นรหัสเดียวกัน */
    private function normaliseCode(string $raw): string
    {
        $clean = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $raw) ?? '');

        return strlen($clean) === 8
            ? substr($clean, 0, 4) . '-' . substr($clean, 4)
            : $clean;
    }
}
