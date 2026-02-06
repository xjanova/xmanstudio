<?php

namespace App\Models;

use App\Events\PaymentMatched;
use App\Events\WalletTopupMatched;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
            // Find matching unique amount with pessimistic lock
            $uniqueAmount = UniquePaymentAmount::where('unique_amount', $this->amount)
                ->where('status', 'reserved')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

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
        if (! $order || $order->payment_status !== 'pending') {
            return true; // Still matched, but order state changed
        }

        if ($approvalMode === 'auto') {
            // Auto-approve: set to confirmed
            $order->update([
                'sms_notification_id' => $this->id,
                'sms_verification_status' => 'confirmed',
                'sms_verified_at' => now(),
                'payment_status' => 'confirmed',
                'paid_at' => now(),
            ]);
            $this->update(['status' => 'confirmed']);
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
     * Match with a Wallet Topup
     */
    protected function matchWalletTopup(UniquePaymentAmount $uniqueAmount, string $approvalMode): bool
    {
        $this->status = 'matched';
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
            $topup->approve(0); // 0 = system approved

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
