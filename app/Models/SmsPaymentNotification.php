<?php

namespace App\Models;

use App\Events\PaymentMatched;
use App\Events\WalletTopupMatched;
use App\Services\LicenseService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                $graceMinutes = (int) config('smschecker.orphan.match_window_minutes', 60);
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
                $graceMinutes = (int) config('smschecker.orphan.match_window_minutes', 60);
                $uniqueAmount = UniquePaymentAmount::where('unique_amount', $this->amount)
                    ->where('status', 'expired')
                    ->where('expires_at', '>', now()->subMinutes($graceMinutes))
                    ->lockForUpdate()
                    ->first();

                if ($uniqueAmount) {
                    // ตรวจว่า order/topup ยังเป็น pending อยู่ไหม ถ้า cancel ไปแล้วไม่ match
                    $stillPending = false;
                    if ($uniqueAmount->transaction_type === 'order') {
                        $order = Order::find($uniqueAmount->transaction_id);
                        $stillPending = $order && in_array($order->payment_status, ['pending', 'expired']);
                    } elseif ($uniqueAmount->transaction_type === 'wallet_topup') {
                        $topup = WalletTopup::find($uniqueAmount->transaction_id);
                        $stillPending = $topup && in_array($topup->status, [WalletTopup::STATUS_PENDING, WalletTopup::STATUS_EXPIRED, WalletTopup::STATUS_REJECTED]);
                    } elseif ($uniqueAmount->transaction_type === 'project_order') {
                        $project = ProjectOrder::find($uniqueAmount->transaction_id);
                        $stillPending = $project && in_array($project->payment_status, ['unpaid', 'partial', null]);
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

            if ($uniqueAmount->transaction_type === 'project_order') {
                return $this->matchProjectOrder($uniqueAmount, $approvalMode);
            }

            return false;
        });
    }

    /**
     * Match with a ProjectOrder (add to paid_amount)
     */
    protected function matchProjectOrder(UniquePaymentAmount $uniqueAmount, string $approvalMode): bool
    {
        $project = ProjectOrder::lockForUpdate()->find($uniqueAmount->transaction_id);
        if (! $project) {
            $this->update(['status' => 'matched', 'matched_transaction_id' => $uniqueAmount->transaction_id]);

            return true;
        }

        if (! in_array($project->payment_status, ['unpaid', 'partial', null])) {
            $this->update(['status' => 'matched', 'matched_transaction_id' => $uniqueAmount->transaction_id]);

            return true;
        }

        $newPaid = round((float) $project->paid_amount + (float) $uniqueAmount->base_amount, 2);
        // Tolerance 1 baht for decimal suffix (unique amount adds 0.01-0.99)
        $remaining = (float) $project->total_price - $newPaid;
        $paymentStatus = $remaining <= 1.00 ? 'paid' : 'partial';

        // Cap paid_amount to total_price when fully paid
        if ($paymentStatus === 'paid') {
            $newPaid = (float) $project->total_price;
        }

        if ($approvalMode === 'auto') {
            $project->update([
                'sms_notification_id' => $this->id,
                'sms_verification_status' => 'confirmed',
                'sms_verified_at' => now(),
                'paid_amount' => $newPaid,
                'payment_status' => $paymentStatus,
            ]);
            $this->update(['status' => 'confirmed', 'matched_transaction_id' => $uniqueAmount->transaction_id]);
        } else {
            $project->update([
                'sms_notification_id' => $this->id,
                'sms_verification_status' => 'matched',
                'sms_verified_at' => now(),
            ]);
            $this->update(['status' => 'matched', 'matched_transaction_id' => $uniqueAmount->transaction_id]);
        }

        // Note: PaymentMatched event expects Order type, not ProjectOrder
        // Log instead of firing event to avoid TypeError
        Log::info('ProjectOrder payment matched', [
            'project_id' => $project->id,
            'project_number' => $project->project_number,
            'notification_id' => $this->id,
            'paid_amount' => $newPaid,
            'payment_status' => $paymentStatus,
            'approval_mode' => $approvalMode,
        ]);

        return true;
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
        app(LicenseService::class)->generateLicensesForOrder($order);
    }

    /**
     * Match with a Wallet Topup
     */
    protected function matchWalletTopup(UniquePaymentAmount $uniqueAmount, string $approvalMode): bool
    {
        $this->status = 'matched';
        $this->matched_transaction_id = $uniqueAmount->transaction_id;
        $this->save();

        // ⚠️ CRITICAL: ต้อง load wallet relationship ด้วย ไม่งั้น approve() จะ fail
        $topup = WalletTopup::with('wallet')->find($uniqueAmount->transaction_id);
        if (! $topup) {
            \Log::warning('matchWalletTopup: Topup not found', [
                'transaction_id' => $uniqueAmount->transaction_id,
            ]);

            return true; // Still matched, but topup not found
        }

        // Allow recovery of expired/auto-rejected topups (SMS came after cleanup)
        if ($topup->status === WalletTopup::STATUS_EXPIRED ||
            ($topup->status === WalletTopup::STATUS_REJECTED && str_contains($topup->reject_reason ?? '', 'หมดเวลาโอนเงิน'))) {
            $topup->update(['status' => WalletTopup::STATUS_PENDING, 'reject_reason' => null]);
            $topup->refresh();
            \Log::info('matchWalletTopup: Recovered expired/auto-rejected topup to pending', [
                'topup_id' => $topup->id,
            ]);
        }

        if (! in_array($topup->status, [WalletTopup::STATUS_PENDING])) {
            \Log::info('matchWalletTopup: Topup not in pending state, skipping', [
                'topup_id' => $topup->id,
                'status' => $topup->status,
            ]);

            return true; // Still matched, but topup in final state (approved/rejected)
        }

        if ($approvalMode === 'auto') {
            // Auto-approve: directly approve the topup
            $topup->update([
                'sms_notification_id' => $this->id,
                'sms_verification_status' => 'confirmed',
                'sms_verified_at' => now(),
            ]);
            // ⚠️ refresh model to ensure DB state is in sync
            $topup->refresh();
            $topup->load('wallet');

            // ⚠️ ตรวจสอบว่า wallet มีอยู่จริง ถ้าไม่มีให้สร้างให้
            if (! $topup->wallet) {
                \Log::warning('matchWalletTopup: Wallet is NULL! Creating wallet for user', [
                    'topup_id' => $topup->id,
                    'wallet_id' => $topup->wallet_id,
                    'user_id' => $topup->user_id,
                ]);
                $wallet = Wallet::getOrCreateForUser($topup->user_id);
                $topup->update(['wallet_id' => $wallet->id]);
                $topup->refresh();
                $topup->load('wallet');
            }

            // Approve the topup (adds money to wallet)
            try {
                \Log::info('matchWalletTopup: Calling approve()', [
                    'topup_id' => $topup->id,
                    'topup_status' => $topup->status,
                    'wallet_id' => $topup->wallet_id,
                    'wallet_exists' => $topup->wallet !== null,
                    'wallet_balance' => $topup->wallet?->balance,
                    'total_amount' => $topup->total_amount,
                ]);

                $approved = $topup->approve(0); // 0 = system approved

                if (! $approved) {
                    \Log::error('matchWalletTopup: approve() returned false!', [
                        'topup_id' => $topup->id,
                        'topup_status' => $topup->status,
                        'topup_status_raw' => $topup->getRawOriginal('status'),
                        'status_equals_pending' => $topup->status === WalletTopup::STATUS_PENDING,
                        'wallet_exists' => $topup->wallet !== null,
                    ]);
                } else {
                    \Log::info('matchWalletTopup: approve() SUCCESS', [
                        'topup_id' => $topup->id,
                        'new_status' => $topup->fresh()->status,
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('matchWalletTopup: approve() threw exception!', [
                    'topup_id' => $topup->id,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile() . ':' . $e->getLine(),
                    'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 5),
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
