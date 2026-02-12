<?php

namespace App\Models;

use App\Events\PaymentMatched;
use App\Events\WalletTopupMatched;
use App\Mail\PaymentConfirmedMail;
use App\Services\LicenseService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SmsPaymentNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank',
        'type',
        'amount',
        'account_number',
        'sender_or_receiver',
        'reference_number',
        'sms_timestamp',
        'device_id',
        'nonce',
        'status',
        'matched_transaction_id',
        'raw_payload',
        'ip_address',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'sms_timestamp' => 'datetime',
    ];

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCredit($query)
    {
        return $query->where('type', 'credit');
    }

    public function scopeDebit($query)
    {
        return $query->where('type', 'debit');
    }

    public function scopeMatched($query)
    {
        return $query->where('status', 'matched');
    }

    // Relationships
    public function matchedOrder()
    {
        return $this->belongsTo(Order::class, 'matched_transaction_id');
    }

    public function matchedWalletTopup()
    {
        return $this->hasOne(WalletTopup::class, 'sms_notification_id');
    }

    public function device()
    {
        return $this->belongsTo(SmsCheckerDevice::class, 'device_id', 'device_id');
    }

    /**
     * ดึงบัญชีธนาคารที่ตรงกับ bank code ของ notification นี้
     * ใช้ตรวจสอบว่า SMS มาจากบัญชีที่ลงทะเบียนไว้
     */
    public function getMatchingBankAccount(): ?BankAccount
    {
        return BankAccount::active()
            ->smsCheckerEnabled()
            ->byBank($this->bank)
            ->first();
    }

    /**
     * ตรวจสอบว่า notification มาจากบัญชีที่ลงทะเบียนไว้หรือไม่
     */
    public function isFromRegisteredAccount(): bool
    {
        return $this->getMatchingBankAccount() !== null;
    }

    /**
     * Try to match this notification with a pending payment transaction.
     * Uses unique decimal amount matching.
     * Supports both Orders and Wallet Topups.
     *
     * ใช้ pessimistic locking (lockForUpdate) ป้องกัน double-match
     * เมื่อ SMS 2 ตัวที่มียอดเดียวกันมาพร้อมกัน
     */
    public function attemptMatch(): bool
    {
        if ($this->type !== 'credit') {
            return false;
        }

        return DB::transaction(function () {
            // Step 1: หา active unique amount (ยังไม่หมดอายุ)
            $uniqueAmount = UniquePaymentAmount::where('unique_amount', $this->amount)
                ->where('status', 'reserved')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            // Step 2: ถ้าไม่เจอ active → ลอง match กับที่หมดอายุไม่นาน (grace period)
            // กรณี SMS มาช้า / rate limit block / network delay
            if (! $uniqueAmount) {
                $graceMinutes = (int) config('smschecker.orphan_match_window', 60);
                $uniqueAmount = UniquePaymentAmount::where('unique_amount', $this->amount)
                    ->where('status', 'reserved')
                    ->where('expires_at', '<=', now())
                    ->where('expires_at', '>', now()->subMinutes($graceMinutes))
                    ->lockForUpdate()
                    ->first();

                if ($uniqueAmount) {
                    Log::info('SMS Payment: Grace period match for expired amount', [
                        'notification_id' => $this->id,
                        'unique_amount_id' => $uniqueAmount->id,
                        'amount' => $this->amount,
                        'expired_at' => $uniqueAmount->expires_at,
                        'grace_minutes' => $graceMinutes,
                    ]);
                }
            }

            // Step 3: ยังไม่เจอ → ลอง match กับ expired status ที่ order ยังเป็น pending
            // (cleanup อาจเปลี่ยน status เป็น 'expired' แล้ว แต่ order อาจยังไม่ถูก cancel)
            if (! $uniqueAmount) {
                $graceMinutes = (int) config('smschecker.orphan_match_window', 60);
                $uniqueAmount = UniquePaymentAmount::where('unique_amount', $this->amount)
                    ->where('status', 'expired')
                    ->where('expires_at', '>', now()->subMinutes($graceMinutes))
                    ->lockForUpdate()
                    ->first();

                if ($uniqueAmount) {
                    // ตรวจว่า order/topup ยังเป็น pending อยู่ไหม ถ้า cancel ไปแล้วไม่ match
                    $stillPending = false;
                    if ($uniqueAmount->transaction_type === 'order') {
                        $order = \App\Models\Order::find($uniqueAmount->transaction_id);
                        $stillPending = $order && in_array($order->payment_status, ['pending', 'expired']);
                    } elseif ($uniqueAmount->transaction_type === 'wallet_topup') {
                        $topup = WalletTopup::find($uniqueAmount->transaction_id);
                        $stillPending = $topup && in_array($topup->status, [WalletTopup::STATUS_PENDING, WalletTopup::STATUS_EXPIRED]);
                    }

                    if (! $stillPending) {
                        $uniqueAmount = null; // Order ถูก cancel จริงแล้ว ไม่ match
                    } else {
                        Log::info('SMS Payment: Recovered expired amount match', [
                            'notification_id' => $this->id,
                            'unique_amount_id' => $uniqueAmount->id,
                            'amount' => $this->amount,
                        ]);
                    }
                }
            }

            if (! $uniqueAmount) {
                return false;
            }

            // Mark unique amount as used
            $uniqueAmount->status = 'used';
            $uniqueAmount->matched_at = now();
            $uniqueAmount->save();

            // Get device approval mode
            $device = $this->device;
            $approvalMode = $device ? $device->getApprovalMode() : 'auto';

            // Handle based on transaction type
            if ($uniqueAmount->transaction_type === 'order') {
                return $this->matchOrder($uniqueAmount, $approvalMode);
            }

            if ($uniqueAmount->transaction_type === 'wallet_topup') {
                return $this->matchWalletTopup($uniqueAmount, $approvalMode);
            }

            return false;
        });
    }

    /**
     * Match with an Order
     */
    protected function matchOrder(UniquePaymentAmount $uniqueAmount, string $approvalMode): bool
    {
        $this->status = 'matched';
        $this->matched_transaction_id = $uniqueAmount->transaction_id;
        $this->save();

        $order = Order::find($uniqueAmount->transaction_id);
        if (! $order) {
            return true; // Still matched, but order not found
        }

        // รองรับทั้ง pending (ปกติ) และ expired (grace period match หลังหมดเวลา)
        if (! in_array($order->payment_status, ['pending', 'expired'])) {
            return true; // Already paid/confirmed/cancelled — skip
        }

        if ($approvalMode === 'auto') {
            // Auto-approve: อนุมัติทันที → payment_status = 'paid'
            $order->update([
                'sms_notification_id' => $this->id,
                'sms_verification_status' => 'confirmed',
                'sms_verified_at' => now(),
                'payment_status' => 'paid',
                'paid_at' => now(),
                'status' => 'completed',
            ]);
            $this->update(['status' => 'confirmed']);

            // Generate license keys + send email (same as admin confirmPayment)
            try {
                $this->generateLicensesForOrder($order);
            } catch (\Exception $e) {
                Log::error('matchOrder: License generation failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            // Manual/Smart: set to processing for admin review
            $order->update([
                'sms_notification_id' => $this->id,
                'sms_verification_status' => 'matched',
                'sms_verified_at' => now(),
                'payment_status' => 'processing',
            ]);
        }

        // Fire event for notifications
        event(new PaymentMatched($order, $this));

        return true;
    }

    /**
     * Auto-generate license keys for order items + send email.
     */
    private function generateLicensesForOrder(Order $order): void
    {
        $order->load('items.product');
        $licenseService = app(LicenseService::class);
        $generated = false;

        foreach ($order->items as $item) {
            if (! $item->product || ! $item->product->requires_license) {
                continue;
            }

            $existingCount = LicenseKey::where('order_id', $order->id)
                ->where('product_id', $item->product_id)
                ->count();

            if ($existingCount >= $item->quantity) {
                continue;
            }

            $licenseType = 'yearly';
            if ($item->custom_requirements) {
                $requirements = json_decode($item->custom_requirements, true);
                if (! empty($requirements['license_type'])) {
                    $licenseType = $requirements['license_type'];
                }
            }

            $expiresAt = match ($licenseType) {
                'monthly' => now()->addDays(30),
                'yearly' => now()->addYear(),
                'lifetime' => null,
                default => now()->addYear(),
            };

            $toGenerate = $item->quantity - $existingCount;
            $licenses = $licenseService->generateLicenses($licenseType, $toGenerate, 1, $item->product_id);

            foreach ($licenses as $license) {
                LicenseKey::where('id', $license['id'])->update([
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'expires_at' => $expiresAt,
                ]);
            }

            $generated = true;
        }

        if ($generated && $order->customer_email) {
            try {
                Mail::to($order->customer_email)
                    ->send(new PaymentConfirmedMail($order->fresh(['items.product', 'user'])));
            } catch (\Exception $e) {
                Log::error('matchOrder: Failed to send payment confirmed email', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Match with a Wallet Topup
     */
    protected function matchWalletTopup(UniquePaymentAmount $uniqueAmount, string $approvalMode): bool
    {
        $this->status = 'matched';
        $this->matched_transaction_id = $uniqueAmount->transaction_id;
        $this->save();

        $topup = WalletTopup::find($uniqueAmount->transaction_id);
        if (! $topup || $topup->status !== WalletTopup::STATUS_PENDING) {
            return true; // Still matched, but topup state changed
        }

        if ($approvalMode === 'auto') {
            // Auto-approve: directly approve the topup
            $topup->update([
                'sms_notification_id' => $this->id,
                'sms_verification_status' => 'confirmed',
                'sms_verified_at' => now(),
            ]);

            // Approve the topup (adds money to wallet)
            $approved = $topup->approve(0); // 0 = system approved
            if (! $approved) {
                \Log::warning('matchWalletTopup: approve() returned false', [
                    'topup_id' => $topup->id,
                    'topup_status' => $topup->status,
                ]);
            }

            $this->update(['status' => 'confirmed']);
        } else {
            // Manual/Smart: set to matched for admin review
            $topup->update([
                'sms_notification_id' => $this->id,
                'sms_verification_status' => 'matched',
                'sms_verified_at' => now(),
            ]);
        }

        // Fire event for notifications
        if (class_exists(WalletTopupMatched::class)) {
            event(new WalletTopupMatched($topup, $this));
        }

        return true;
    }

    /**
     * Get bank display name in Thai
     */
    public function getBankDisplayNameAttribute(): string
    {
        $banks = config('smschecker.banks', []);

        return $banks[$this->bank] ?? $this->bank;
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format((float) $this->amount, 2);
    }
}
