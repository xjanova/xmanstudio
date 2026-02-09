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
        $deviceId = $request->header('X-Device-Id');
        if ($deviceId && $device->device_id !== $deviceId) {
            Log::warning('SMS Checker: Device ID mismatch', [
                'expected' => $device->device_id,
                'received' => $deviceId,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Device ID mismatch',
            ], 403);
        }

        // Rate limiting: 120 req/min per device
        // Note: ใช้ค่าตรงเพราะ config cache อาจค้างค่าเก่า (30)
        // ถ้าต้องการเปลี่ยน ให้แก้ที่นี่ + config/smschecker.php + รัน config:cache
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

        // Attach device to request (use attributes for object, not merge)
        $request->attributes->set('sms_checker_device', $device);

        return $next($request);
    }
}
