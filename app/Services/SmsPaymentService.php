<?php

namespace App\Services;

use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use App\Models\WalletTopup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SmsPaymentService
{
    protected LineNotifyService $lineNotifyService;

    public function __construct(LineNotifyService $lineNotifyService)
    {
        $this->lineNotifyService = $lineNotifyService;
    }

    /**
     * Process an incoming SMS payment notification from the Android app.
     */
    public function processNotification(array $payload, SmsCheckerDevice $device, string $ipAddress): array
    {
        return DB::transaction(function () use ($payload, $device, $ipAddress) {
            // Check for duplicate nonce (replay attack prevention)
            $existingNonce = DB::table('sms_payment_nonces')
                ->where('nonce', $payload['nonce'])
                ->exists();

            if ($existingNonce) {
                $this->log('warning', 'SMS Payment: Duplicate nonce detected', [
                    'nonce' => $payload['nonce'],
                    'device_id' => $device->device_id,
                ]);

                return [
                    'success' => false,
                    'message' => 'Duplicate request (nonce already used)',
                ];
            }

            // Record nonce
            DB::table('sms_payment_nonces')->insert([
                'nonce' => $payload['nonce'],
                'device_id' => $device->device_id,
                'used_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create notification record
            $notification = SmsPaymentNotification::create([
                'bank' => $payload['bank'],
                'type' => $payload['type'],
                'amount' => $payload['amount'],
                'account_number' => $payload['account_number'] ?? '',
                'sender_or_receiver' => $payload['sender_or_receiver'] ?? '',
                'reference_number' => $payload['reference_number'] ?? '',
                'sms_timestamp' => date('Y-m-d H:i:s', $payload['sms_timestamp'] / 1000),
                'device_id' => $device->device_id,
                'nonce' => $payload['nonce'],
                'status' => 'pending',
                'raw_payload' => json_encode($payload),
                'ip_address' => $ipAddress,
            ]);

            // Update device activity
            $device->update([
                'last_active_at' => now(),
                'ip_address' => $ipAddress,
            ]);

            // Attempt auto-match for credit transactions
            $matched = false;
            if ($notification->type === 'credit') {
                $matched = $notification->attemptMatch();
            }

            $this->log('info', 'SMS Payment notification processed', [
                'notification_id' => $notification->id,
                'bank' => $notification->bank,
                'type' => $notification->type,
                'amount' => $notification->amount,
                'matched' => $matched,
            ]);

            return [
                'success' => true,
                'message' => $matched ? 'Payment matched and confirmed' : 'Notification recorded',
                'data' => [
                    'notification_id' => $notification->id,
                    'status' => $notification->status,
                    'matched' => $matched,
                    'matched_transaction_id' => $notification->matched_transaction_id,
                ],
            ];
        });
    }

    /**
     * Decrypt the encrypted payload from the app.
     *
     * รูปแบบข้อมูล: Base64(IV[12 bytes] + Ciphertext + AuthTag[16 bytes])
     *
     * @param  string  $encryptedData  Base64 encoded AES-256-GCM encrypted data
     * @param  string  $secretKey  The device's secret key
     * @return array|null Decrypted payload or null on failure
     */
    public function decryptPayload(string $encryptedData, string $secretKey): ?array
    {
        try {
            $combined = base64_decode($encryptedData);
            if ($combined === false || strlen($combined) < 12) {
                return null;
            }

            $ivLength = 12; // GCM IV is 12 bytes
            $tagLength = 16; // GCM tag is 16 bytes

            $iv = substr($combined, 0, $ivLength);
            $cipherTextWithTag = substr($combined, $ivLength);

            // Separate ciphertext and tag
            $tag = substr($cipherTextWithTag, -$tagLength);
            $cipherText = substr($cipherTextWithTag, 0, -$tagLength);

            // SECURITY: ใช้ PBKDF2 สร้าง encryption key (ตรงกับ Android CryptoManager)
            $key = $this->deriveKey($secretKey, 'encryption');

            $decrypted = openssl_decrypt(
                $cipherText,
                'aes-256-gcm',
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($decrypted === false) {
                $this->log('warning', 'SMS Payment: Decryption failed (auth tag mismatch or wrong key)');

                return null;
            }

            $payload = json_decode($decrypted, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->log('warning', 'SMS Payment: Invalid JSON in payload');

                return null;
            }

            return $payload;
        } catch (\Exception $e) {
            $this->log('error', 'SMS Payment: Decryption error', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Verify HMAC signature.
     *
     * ลายเซ็น = HMAC-SHA256(encrypted_data + nonce + timestamp, hmacKey)
     * hmacKey ถูก derive แยกจาก encryption key ผ่าน PBKDF2
     */
    public function verifySignature(string $data, string $signature, string $secretKey): bool
    {
        // SECURITY: ใช้ dedicated HMAC key (แยกจาก encryption key)
        $hmacKey = $this->deriveKey($secretKey, 'hmac-signing');
        $expected = base64_encode(hash_hmac('sha256', $data, $hmacKey, true));

        return hash_equals($expected, $signature);
    }

    /**
     * Derive a strong key from secret using PBKDF2-SHA256
     *
     * ต้องตรงกับ Android CryptoManager.deriveKey() ทุกประการ:
     * - Algorithm: PBKDF2WithHmacSHA256
     * - Iterations: 100,000
     * - Key length: 256 bits (32 bytes)
     * - Salt: "thaiprompt-smschecker-v1:{context}"
     *
     * หมายเหตุ: ใช้ salt เดียวกันกับ Android app เพื่อความเข้ากันได้
     *
     * @param  string  $secret  Secret key string
     * @param  string  $context  Purpose context ('encryption' or 'hmac-signing')
     * @return string 32-byte derived key (raw binary)
     */
    private function deriveKey(string $secret, string $context = 'encryption'): string
    {
        $salt = "thaiprompt-smschecker-v1:{$context}";

        return hash_pbkdf2('sha256', $secret, $salt, 100000, 32, true);
    }

    /**
     * Generate a unique payment amount for a transaction.
     */
    public function generateUniqueAmount(
        float $baseAmount,
        ?int $transactionId = null,
        string $transactionType = 'order',
        int $expiryMinutes = 30
    ): ?UniquePaymentAmount {
        $expiry = $expiryMinutes ?: (int) config('smschecker.unique_amount_expiry', 30);

        return UniquePaymentAmount::generate(
            $baseAmount,
            $transactionId,
            $transactionType,
            $expiry
        );
    }

    /**
     * Get pending (unmatched) notifications.
     */
    public function getPendingNotifications(int $limit = 50)
    {
        return SmsPaymentNotification::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Cleanup expired data and auto-cancel orders.
     *
     * - ยกเลิก Orders ที่ unique amount หมดอายุ (30 นาที)
     * - ล้าง unique amounts ที่หมดอายุ → สถานะ 'expired'
     * - ลบ nonces เก่า
     * - ล้าง pending notifications เก่า
     */
    public function cleanup(): array
    {
        $stats = [
            'cancelled_orders' => 0,
            'cancelled_topups' => 0,
            'expired_amounts' => 0,
            'deleted_nonces' => 0,
            'expired_notifications' => 0,
        ];

        // ========================================
        // Step 1: ยกเลิก Orders และ WalletTopups ที่หมดเวลาชำระ
        // ========================================

        // ดึง unique amounts ที่หมดอายุและยังเป็น 'reserved'
        $expiredUniqueAmounts = UniquePaymentAmount::where('status', 'reserved')
            ->where('expires_at', '<=', now())
            ->with(['order', 'walletTopup'])
            ->get();

        foreach ($expiredUniqueAmounts as $uniqueAmount) {
            // ยกเลิก Order ถ้ายังเป็น pending และยังไม่ได้ชำระ
            if ($uniqueAmount->transaction_type === 'order' &&
                $uniqueAmount->order &&
                $uniqueAmount->order->status === 'pending' &&
                $uniqueAmount->order->payment_status !== 'paid') {

                $uniqueAmount->order->update([
                    'status' => 'cancelled',
                    'payment_status' => 'expired',
                    'notes' => ($uniqueAmount->order->notes ? $uniqueAmount->order->notes . "\n" : '') .
                        'หมดเวลาชำระเงิน - ระบบยกเลิกอัตโนมัติ ' . now()->format('d/m/Y H:i'),
                ]);
                $stats['cancelled_orders']++;

                $this->log('info', 'SMS Payment: Auto-cancelled expired order', [
                    'order_id' => $uniqueAmount->order->id,
                    'order_number' => $uniqueAmount->order->order_number,
                    'amount' => $uniqueAmount->unique_amount,
                ]);
            }

            // ยกเลิก WalletTopup ถ้ายังเป็น pending
            if ($uniqueAmount->transaction_type === 'wallet_topup' &&
                $uniqueAmount->walletTopup &&
                $uniqueAmount->walletTopup->status === WalletTopup::STATUS_PENDING) {

                $uniqueAmount->walletTopup->update([
                    'status' => WalletTopup::STATUS_EXPIRED,
                    'notes' => 'หมดเวลาชำระเงิน - ระบบยกเลิกอัตโนมัติ ' . now()->format('d/m/Y H:i'),
                ]);
                $stats['cancelled_topups']++;

                $this->log('info', 'SMS Payment: Auto-cancelled expired wallet topup', [
                    'topup_id' => $uniqueAmount->walletTopup->id,
                    'amount' => $uniqueAmount->unique_amount,
                ]);
            }

            // อัปเดต unique amount เป็น expired
            $uniqueAmount->update(['status' => 'expired']);
            $stats['expired_amounts']++;
        }

        // ========================================
        // Step 2: ลบ nonces เก่า
        // ========================================

        $nonceExpiry = config('smschecker.nonce_expiry_hours', 24);
        $stats['deleted_nonces'] = DB::table('sms_payment_nonces')
            ->where('used_at', '<', now()->subHours($nonceExpiry))
            ->delete();

        // ========================================
        // Step 3: ล้าง pending notifications เก่า (> 7 วัน)
        // ========================================

        $stats['expired_notifications'] = SmsPaymentNotification::where('status', 'pending')
            ->where('created_at', '<', now()->subDays(7))
            ->update(['status' => 'expired']);

        $this->log('info', 'SMS Payment cleanup completed', $stats);

        return $stats;
    }

    /**
     * Send LINE notification for matched payment.
     */
    public function notifyPaymentMatched(\App\Models\Order $order, SmsPaymentNotification $notification): bool
    {
        $banks = config('smschecker.banks', []);
        $bankName = $banks[$notification->bank] ?? $notification->bank;

        $message = "💰 ยืนยันการชำระเงิน!\n"
            . "━━━━━━━━━━━━━━━\n"
            . "🔢 Order: {$order->order_number}\n"
            . "👤 ลูกค้า: {$order->customer_name}\n"
            . "📧 อีเมล: {$order->customer_email}\n"
            . "📱 โทร: {$order->customer_phone}\n"
            . "━━━━━━━━━━━━━━━\n"
            . "🏦 ธนาคาร: {$bankName}\n"
            . '💵 ยอด: ฿' . number_format((float) $notification->amount, 2) . "\n"
            . '📋 สถานะ: ' . ($notification->status === 'confirmed' ? '✅ ยืนยันแล้ว' : '⏳ รอตรวจสอบ') . "\n"
            . "━━━━━━━━━━━━━━━\n"
            . '⏰ ' . now()->format('d/m/Y H:i');

        return $this->lineNotifyService->send($message);
    }

    /**
     * Safe logging helper.
     */
    private function log(string $level, string $message, array $context = []): void
    {
        $configLevel = config('smschecker.log_level', 'info');
        $levels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];

        if (($levels[$level] ?? 0) >= ($levels[$configLevel] ?? 0)) {
            Log::$level($message, $context);
        }
    }
}
