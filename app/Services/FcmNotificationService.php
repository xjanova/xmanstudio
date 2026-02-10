<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging (FCM) Notification Service
 *
 * Sends push notifications to Android SmsChecker app via FCM HTTP v1 API.
 *
 * Setup Requirements:
 * 1. Create Firebase project at https://console.firebase.google.com
 * 2. Download service account JSON key
 * 3. Set FIREBASE_CREDENTIALS_PATH in .env
 * 4. Set FIREBASE_PROJECT_ID in .env
 */
class FcmNotificationService
{
    private ?string $accessToken = null;

    private ?int $tokenExpiry = null;

    /**
     * Send push notification for new order
     */
    public function notifyNewOrder(Order $order, ?SmsCheckerDevice $device = null): bool
    {
        $tokens = $this->getTargetTokens($device);
        if (empty($tokens)) {
            Log::debug('FCM: No tokens available for new order notification');

            return false;
        }

        $data = [
            'type' => 'new_order',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'amount' => number_format((float) ($order->uniquePaymentAmount?->unique_amount ?? $order->total), 2, '.', ''),
            'customer_name' => $order->customer_name ?? 'N/A',
        ];

        $notification = [
            'title' => 'คำสั่งซื้อใหม่ รอชำระเงิน',
            'body' => sprintf(
                'คำสั่งซื้อ #%s ยอด ฿%s',
                $order->order_number,
                number_format((float) ($order->uniquePaymentAmount?->unique_amount ?? $order->total), 2)
            ),
        ];

        return $this->sendToMultipleTokens($tokens, $data, $notification);
    }

    /**
     * Send push notification when payment is matched
     */
    public function notifyPaymentMatched(Order $order, SmsPaymentNotification $smsNotification, ?SmsCheckerDevice $device = null): bool
    {
        $tokens = $this->getTargetTokens($device);
        if (empty($tokens)) {
            Log::debug('FCM: No tokens available for payment matched notification');

            return false;
        }

        $banks = config('smschecker.banks', []);
        $bankName = $banks[$smsNotification->bank] ?? $smsNotification->bank;

        $data = [
            'type' => 'payment_matched',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'amount' => number_format((float) $smsNotification->amount, 2, '.', ''),
            'bank' => $smsNotification->bank,
            'status' => $smsNotification->status,
        ];

        $notification = [
            'title' => 'ยืนยันการชำระเงินแล้ว!',
            'body' => sprintf(
                'คำสั่งซื้อ #%s ยอด ฿%s (%s)',
                $order->order_number,
                number_format((float) $smsNotification->amount, 2),
                $bankName
            ),
        ];

        return $this->sendToMultipleTokens($tokens, $data, $notification);
    }

    /**
     * Send push notification for order status update
     */
    public function notifyOrderUpdate(Order $order, string $status, ?SmsCheckerDevice $device = null): bool
    {
        $tokens = $this->getTargetTokens($device);
        if (empty($tokens)) {
            return false;
        }

        $statusLabels = [
            'pending' => 'รอชำระเงิน',
            'matched' => 'พบการโอนเงิน',
            'confirmed' => 'ยืนยันแล้ว',
            'rejected' => 'ถูกปฏิเสธ',
            'expired' => 'หมดอายุ',
        ];

        $data = [
            'type' => 'order_update',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'status' => $status,
        ];

        $notification = [
            'title' => 'อัพเดทคำสั่งซื้อ',
            'body' => sprintf(
                'คำสั่งซื้อ #%s สถานะ: %s',
                $order->order_number,
                $statusLabels[$status] ?? $status
            ),
        ];

        return $this->sendToMultipleTokens($tokens, $data, $notification);
    }

    /**
     * Send push notification when order is approved (by admin or system).
     * Android app receives this and updates local DB immediately.
     */
    public function notifyOrderApproved(Order $order, ?SmsCheckerDevice $device = null): bool
    {
        $tokens = $this->getTargetTokens($device);
        if (empty($tokens)) {
            return false;
        }

        $amount = $order->uniquePaymentAmount
            ? number_format((float) $order->uniquePaymentAmount->unique_amount, 2, '.', '')
            : number_format((float) $order->total, 2, '.', '');

        $data = [
            'type' => 'order_approved',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'amount' => $amount,
            'payment_status' => $order->payment_status ?? 'paid',
            'sms_verification_status' => $order->sms_verification_status ?? 'confirmed',
        ];

        $notification = [
            'title' => '✅ อนุมัติคำสั่งซื้อแล้ว',
            'body' => sprintf(
                'คำสั่งซื้อ #%s ยอด ฿%s อนุมัติแล้ว',
                $order->order_number,
                $amount
            ),
        ];

        Log::info('FCM: Sending order_approved push', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);

        return $this->sendToMultipleTokens($tokens, $data, $notification);
    }

    /**
     * Send push notification when order is rejected (by admin or system).
     */
    public function notifyOrderRejected(Order $order, ?SmsCheckerDevice $device = null): bool
    {
        $tokens = $this->getTargetTokens($device);
        if (empty($tokens)) {
            return false;
        }

        $data = [
            'type' => 'order_rejected',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status ?? 'failed',
            'sms_verification_status' => $order->sms_verification_status ?? 'rejected',
        ];

        $notification = [
            'title' => '❌ ปฏิเสธคำสั่งซื้อ',
            'body' => sprintf(
                'คำสั่งซื้อ #%s ถูกปฏิเสธ',
                $order->order_number
            ),
        ];

        Log::info('FCM: Sending order_rejected push', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);

        return $this->sendToMultipleTokens($tokens, $data, $notification);
    }

    /**
     * Send push notification when order is cancelled (by admin).
     */
    public function notifyOrderCancelled(Order $order, ?SmsCheckerDevice $device = null): bool
    {
        $tokens = $this->getTargetTokens($device);
        if (empty($tokens)) {
            return false;
        }

        $data = [
            'type' => 'order_cancelled',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status ?? 'cancelled',
            'sms_verification_status' => $order->sms_verification_status ?? 'cancelled',
        ];

        $notification = [
            'title' => '🚫 ยกเลิกคำสั่งซื้อ',
            'body' => sprintf(
                'คำสั่งซื้อ #%s ถูกยกเลิก',
                $order->order_number
            ),
        ];

        Log::info('FCM: Sending order_cancelled push', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);

        return $this->sendToMultipleTokens($tokens, $data, $notification);
    }

    /**
     * Send push notification when order is deleted (by admin).
     * Sends order_id so Android app can remove it from local DB.
     */
    public function notifyOrderDeleted(int $orderId, string $orderNumber, ?SmsCheckerDevice $device = null): bool
    {
        $tokens = $this->getTargetTokens($device);
        if (empty($tokens)) {
            return false;
        }

        $data = [
            'type' => 'order_deleted',
            'order_id' => (string) $orderId,
            'order_number' => $orderNumber,
        ];

        // Silent push - no notification shown for deletion
        Log::info('FCM: Sending order_deleted push', [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
        ]);

        return $this->sendToMultipleTokens($tokens, $data, null);
    }

    /**
     * Send silent push to trigger sync
     */
    public function triggerSync(?SmsCheckerDevice $device = null): bool
    {
        $tokens = $this->getTargetTokens($device);
        if (empty($tokens)) {
            Log::warning('FCM triggerSync: No tokens found - cannot send push notification');

            return false;
        }

        Log::info('FCM triggerSync: Sending to ' . count($tokens) . ' token(s)');

        $data = [
            'type' => 'sync',
            'timestamp' => (string) (time() * 1000),
        ];

        // Silent push - no notification shown
        return $this->sendToMultipleTokens($tokens, $data, null);
    }

    /**
     * Check if FCM is enabled via admin settings
     */
    public function isEnabled(): bool
    {
        return (bool) Setting::getValue('fcm_enabled', true);
    }

    /**
     * Send FCM message to multiple tokens
     */
    private function sendToMultipleTokens(array $tokens, array $data, ?array $notification): bool
    {
        // Check if FCM is disabled via admin settings
        if (! $this->isEnabled()) {
            Log::debug('FCM: Disabled via admin settings, skipping send');

            return false;
        }

        if (empty($tokens)) {
            return false;
        }

        $successCount = 0;
        $failedTokens = [];

        foreach ($tokens as $token) {
            if ($this->sendToToken($token, $data, $notification)) {
                $successCount++;
            } else {
                $failedTokens[] = $token;
            }
        }

        // Mark failed tokens as invalid
        if (! empty($failedTokens)) {
            $this->markTokensInvalid($failedTokens);
        }

        Log::debug('FCM: Sent to ' . $successCount . '/' . count($tokens) . ' tokens');

        return $successCount > 0;
    }

    /**
     * Send FCM message to a single token
     */
    private function sendToToken(string $token, array $data, ?array $notification): bool
    {
        $accessToken = $this->getAccessToken();
        if (! $accessToken) {
            Log::error('FCM: Failed to get access token');

            return false;
        }

        // Read project ID from database first, fallback to config
        $projectId = Setting::getValue('fcm_project_id') ?: config('services.firebase.project_id');
        if (! $projectId) {
            Log::error('FCM: Firebase project ID not configured');

            return false;
        }

        $message = [
            'token' => $token,
            'data' => array_map('strval', $data), // FCM data must be strings
            'android' => [
                'priority' => 'high',
                'ttl' => '86400s', // 24 hours
            ],
        ];

        // Add notification if provided (visible push)
        if ($notification) {
            $message['notification'] = $notification;
            $message['android']['notification'] = [
                'channel_id' => 'sms_payment_channel',
                'click_action' => 'OPEN_ORDERS',
            ];
        }

        try {
            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post(
                    "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                    ['message' => $message]
                );

            if ($response->successful()) {
                return true;
            }

            $error = $response->json('error.details.0.errorCode') ?? $response->json('error.message');
            Log::warning('FCM: Send failed', [
                'error' => $error,
                'status' => $response->status(),
            ]);

            // Token-specific errors that indicate token is invalid
            if (in_array($error, ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
                return false;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('FCM: Exception during send', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Get OAuth2 access token for FCM API
     */
    private function getAccessToken(): ?string
    {
        // Return cached token if still valid
        if ($this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry - 60) {
            return $this->accessToken;
        }

        // Read credentials path from database first, fallback to config
        $credentialsPath = Setting::getValue('fcm_credentials_path') ?: config('services.firebase.credentials');
        if (! $credentialsPath || ! file_exists($credentialsPath)) {
            Log::error('FCM: Firebase credentials file not found', ['path' => $credentialsPath]);

            return null;
        }

        try {
            $credentials = json_decode(file_get_contents($credentialsPath), true);

            // Create JWT
            $now = time();
            $jwt = $this->createJwt([
                'iss' => $credentials['client_email'],
                'sub' => $credentials['client_email'],
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            ], $credentials['private_key']);

            // Exchange JWT for access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->accessToken = $data['access_token'];
                $this->tokenExpiry = time() + ($data['expires_in'] ?? 3600);

                return $this->accessToken;
            }

            Log::error('FCM: Failed to get access token', ['response' => $response->json()]);

            return null;
        } catch (\Exception $e) {
            Log::error('FCM: Exception getting access token', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Create JWT for Google OAuth2
     */
    private function createJwt(array $payload, string $privateKey): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => 'RS256',
        ];

        $segments = [
            $this->base64UrlEncode(json_encode($header)),
            $this->base64UrlEncode(json_encode($payload)),
        ];

        $signingInput = implode('.', $segments);

        openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Get FCM tokens to send to
     */
    private function getTargetTokens(?SmsCheckerDevice $device): array
    {
        if ($device && $device->fcm_token) {
            Log::debug('FCM getTargetTokens: Using specific device token', [
                'device_id' => $device->device_id,
                'token_prefix' => substr($device->fcm_token, 0, 20) . '...',
            ]);

            return [$device->fcm_token];
        }

        // Get all active devices with FCM tokens
        $allDevices = SmsCheckerDevice::where('status', 'active')->get();
        $tokensResult = $allDevices->filter(fn ($d) => ! empty($d->fcm_token))->pluck('fcm_token')->toArray();

        Log::debug('FCM getTargetTokens: Query result', [
            'active_devices_total' => $allDevices->count(),
            'devices_with_token' => count($tokensResult),
            'devices_detail' => $allDevices->map(fn ($d) => [
                'device_id' => $d->device_id,
                'device_name' => $d->device_name ?? 'N/A',
                'has_fcm_token' => ! empty($d->fcm_token),
                'fcm_token_prefix' => $d->fcm_token ? substr($d->fcm_token, 0, 20) . '...' : null,
            ])->toArray(),
        ]);

        return $tokensResult;
    }

    /**
     * Mark tokens as invalid (remove from devices)
     */
    private function markTokensInvalid(array $tokens): void
    {
        if (empty($tokens)) {
            return;
        }

        SmsCheckerDevice::whereIn('fcm_token', $tokens)
            ->update(['fcm_token' => null]);

        Log::info('FCM: Marked invalid tokens', ['count' => count($tokens)]);
    }

    /**
     * Register/update FCM token for a device
     */
    public function registerToken(SmsCheckerDevice $device, string $fcmToken): bool
    {
        // Remove token from other devices (one token = one device)
        SmsCheckerDevice::where('fcm_token', $fcmToken)
            ->where('id', '!=', $device->id)
            ->update(['fcm_token' => null]);

        $device->update(['fcm_token' => $fcmToken]);

        Log::debug('FCM: Token registered', [
            'device_id' => $device->device_id,
        ]);

        return true;
    }
}
