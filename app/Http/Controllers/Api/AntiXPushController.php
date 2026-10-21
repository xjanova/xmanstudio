<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\ProductDevice;
use App\Services\AntiXPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

/**
 * POST /api/v1/product/anti-x/push — an Anti X PC asks to wake its paired phones.
 *
 * Only a PC registered for Anti X (register-device) can ask, at most 30 times an hour; with a key, the key must be an
 * Anti X key. The text is chosen here, not by the PC (see AntiXPushService), so a stolen machine id can at worst send
 * the same harmless "Anti X พบเหตุการณ์" to phone tokens it would also have to know.
 */
class AntiXPushController extends Controller
{
    public const PER_HOUR = 30;

    public function send(Request $request, AntiXPushService $push): JsonResponse
    {
        $data = $request->validate([
            'machine_id' => 'required|string|min:32|max:64',
            'license_key' => 'nullable|string|max:64',
            'tokens' => 'required|array|min:1|max:20',
            'tokens.*' => ['required', 'string', 'max:4096', 'regex:/^[\x21-\x7E]+$/'],
            'level' => 'required|in:critical,warning,test',
            'count' => 'nullable|integer|min:0|max:1000000',
        ]);

        $product = Product::where('slug', 'anti-x')->first();
        $device = $product ? ProductDevice::where('product_id', $product->id)->where('machine_id', $data['machine_id'])->first() : null;
        if (! $device) {
            return $this->fail(403, 'UNKNOWN_MACHINE', 'เครื่องนี้ยังไม่ได้ลงทะเบียนกับ Anti X');
        }
        if (! empty($data['license_key'])
            && ! LicenseKey::where('license_key', strtoupper(trim($data['license_key'])))->where('product_id', $product->id)->exists()) {
            return $this->fail(403, 'INVALID_LICENSE', 'คีย์นี้ไม่ใช่ของ Anti X');
        }

        $bucket = 'anti-x-push:' . $data['machine_id'];
        if (RateLimiter::tooManyAttempts($bucket, self::PER_HOUR)) {
            return $this->fail(429, 'RATE_LIMITED', 'ส่งแจ้งเตือนถี่เกินไป — ลองใหม่ใน ' . RateLimiter::availableIn($bucket) . ' วินาที');
        }
        if (! $push->isConfigured()) {
            return $this->fail(503, 'PUSH_NOT_CONFIGURED', 'xman4289.com ยังไม่ได้ตั้งค่าการแจ้งเตือนมือถือ');
        }
        RateLimiter::hit($bucket, 3600);

        try {
            $result = $push->send(array_values(array_unique($data['tokens'])), $data['level'], (int) ($data['count'] ?? 0));
        } catch (RuntimeException $e) {
            report($e);

            return $this->fail(502, 'PUSH_FAILED', 'ส่งแจ้งเตือนไม่สำเร็จ — ลองใหม่ภายหลัง');
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    private function fail(int $status, string $code, string $message): JsonResponse
    {
        return response()->json(['success' => false, 'error_code' => $code, 'message' => $message], $status);
    }
}
