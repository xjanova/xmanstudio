<?php

namespace App\Http\Middleware;

use App\Models\SmsCheckerDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifySmsCheckerDevice
{
    /**
     * Verify the SMS Checker device API key and device status.
     *
     * Required Headers:
     * - X-Api-Key: Device API key (required)
     * - X-Device-Id: Device ID (optional, for additional verification)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Api-Key');

        if (! $apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key is required',
            ], 401);
        }

        $device = SmsCheckerDevice::findByApiKey($apiKey);

        if (! $device) {
            Log::warning('SMS Checker: Invalid API key attempt', [
                'ip' => $request->ip(),
                'api_key_prefix' => substr($apiKey, 0, 8) . '...',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid API key',
            ], 401);
        }

        if (! $device->isActive()) {
            Log::warning('SMS Checker: Inactive device attempt', [
                'device_id' => $device->device_id,
                'status' => $device->status,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Device is ' . $device->status,
            ], 403);
        }

        // Verify device ID if provided
        // ✅ Auto-sync: ถ้า API key ถูกต้อง + device active → อัพเดท device_id อัตโนมัติ
        // เพราะ device_id เป็น global ค่าเดียวในแอพ แต่ server หลายตัวเก็บแยก
        // เมื่อ user เพิ่ม server ใหม่ device_id อาจเปลี่ยน → server เก่าไม่ตรง
        $deviceId = $request->header('X-Device-Id');
        if ($deviceId && $device->device_id !== $deviceId) {
            Log::info('SmsChecker: Auto-sync device_id', [
                'old_device_id' => $device->device_id,
                'new_device_id' => $deviceId,
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
            $device->update(['device_id' => $deviceId]);
        }

        // Critical endpoints: ไม่ต้อง rate limit — ต้องผ่านเสมอ
        // - register-device/fcm-token: FCM token registration
        // - debug-report: diagnostic
        // - notify: SMS notification
        // - approve/reject: order approval ต้องไม่ถูก block
        // - orders/match: SMS matching ต้องทำงานได้ทันทีเมื่อรับ SMS
        $criticalPaths = ['register-device', 'debug-report', 'debug-topup', 'register-fcm-token', 'notify', 'notify-action', 'approve', 'reject', 'orders/match'];
        $isCritical = false;
        foreach ($criticalPaths as $path) {
            if (str_contains($request->path(), $path)) {
                $isCritical = true;
                break;
            }
        }

        if (! $isCritical) {
            // Rate limiting สำหรับ data sync endpoints เท่านั้น (orders, sync, status)
            $rateLimit = 120;
            $cacheKey = 'smschecker_rate:' . $device->device_id;
            $requestCount = cache()->get($cacheKey, 0);

            if ($requestCount >= $rateLimit) {
                Log::warning('SMS Checker: Rate limit exceeded', [
                    'device_id' => $device->device_id,
                    'count' => $requestCount,
                    'limit' => $rateLimit,
                    'path' => $request->path(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Rate limit exceeded',
                ], 429);
            }

            cache()->put($cacheKey, $requestCount + 1, now()->addMinute());
        }

        // Attach device to request (use attributes for object, not merge)
        $request->attributes->set('sms_checker_device', $device);

        return $next($request);
    }
}
