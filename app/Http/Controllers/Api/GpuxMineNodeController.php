<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GpuNode;
use App\Models\Product;
use App\Models\ProductDevice;
use App\Services\GpuxMineDispatchService;
use App\Services\GpuxMineRelayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    ) {}

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
            // ข้อความเดียวกันทั้งกรณีรหัสผิดและรหัสหมดอายุ เพื่อไม่ให้คนที่
            // สุ่มรหัสรู้ว่าเดาถูกบางส่วน — แต่บอกทางออกให้คนที่พิมพ์ถูกแล้ว
            // รหัสหมดอายุพอดี
            return response()->json([
                'success' => false,
                'error_code' => 'PAIRING_INVALID',
                'message' => 'รหัสจับคู่ไม่ถูกต้องหรือหมดอายุแล้ว — กดขอรหัสใหม่ที่หน้าเครื่องของฉัน',
            ], 422);
        }

        // เครื่องเดิมที่เคยจับคู่แล้วมาขอใหม่ (ลงโปรแกรมใหม่ / ล้างเครื่อง):
        // คืน worker เดิม ไม่สร้างใหม่ ไม่งั้นประวัติงานและคะแนนสะสมขาดตอน
        $existing = GpuNode::where('user_id', $node->user_id)
            ->where('machine_id', $validated['machine_id'])
            ->whereNotNull('worker_id')
            ->whereNull('deleted_at')
            ->first();

        if ($existing !== null) {
            $node->delete();   // รหัสที่เพิ่งออกไม่ได้ใช้ ทิ้งไป

            return $this->credentials($existing, $validated, reused: true);
        }

        $label = $validated['machine_name'] ?: ('เครื่องของ ' . ($node->user?->name ?? 'สมาชิก'));
        $enrolment = $this->relay->enroll($label);

        if ($enrolment === null) {
            return response()->json([
                'success' => false,
                'error_code' => 'RELAY_UNAVAILABLE',
                'message' => 'ระบบรับเครื่องยังไม่พร้อม กรุณาลองใหม่อีกครั้งในอีกสักครู่',
            ], 503);
        }

        $device = $this->rememberDevice($validated);

        $node->forceFill([
            'product_device_id' => $device?->id,
            'machine_id' => $validated['machine_id'],
            'label' => $label,
            'worker_id' => $enrolment['workerId'],
            'relay_token' => $enrolment['token'],
            'relay_url' => $enrolment['agentRelayUrl'],
            'tunnel_endpoint' => $enrolment['aixmanEndpoint'],
            'agent_version' => $validated['app_version'] ?? null,
            'paired_at' => now(),
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

    private function credentials(GpuNode $node, array $validated, bool $reused): JsonResponse
    {
        // เครื่องเดิมกลับมา: อัปเดตสิ่งที่อาจเปลี่ยน แล้วคืนของเดิม
        if ($reused) {
            $node->forceFill([
                'agent_version' => $validated['app_version'] ?? $node->agent_version,
                'paired_at' => $node->paired_at ?? now(),
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
