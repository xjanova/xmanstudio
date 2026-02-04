<?php

namespace App\Models;

use App\Events\PaymentMatched;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function device()
    {
        return $this->belongsTo(SmsCheckerDevice::class, 'device_id', 'device_id');
    }

    /**
     * Try to match this notification with a pending payment transaction.
     * Uses unique decimal amount matching.
     */
    public function attemptMatch(): bool
    {
        if ($this->type !== 'credit') {
            return false;
        }

        // Find matching unique amount
        $uniqueAmount = UniquePaymentAmount::where('unique_amount', $this->amount)
            ->where('status', 'reserved')
            ->where('expires_at', '>', now())
            ->first();

        if ($uniqueAmount && $uniqueAmount->transaction_type === 'order') {
            // Match found
            $this->status = 'matched';
            $this->matched_transaction_id = $uniqueAmount->transaction_id;
            $this->save();

            $uniqueAmount->status = 'used';
            $uniqueAmount->matched_at = now();
            $uniqueAmount->save();

            // Auto-confirm the Order
            $order = Order::find($uniqueAmount->transaction_id);
            if ($order && $order->payment_status === 'pending') {
                // Check device approval mode
                $device = $this->device;
                $approvalMode = $device ? $device->getApprovalMode() : 'auto';

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
            }

            return true;
        }

        // Fallback: try to match by exact amount (for rentals, etc.)
        if ($this->reference_number) {
            $order = Order::where('reference_number', $this->reference_number)
                ->where('payment_status', 'pending')
                ->first();

            if ($order && abs((float) $order->total - (float) $this->amount) < 0.01) {
                $this->status = 'matched';
                $this->matched_transaction_id = $order->id;
                $this->save();

                $order->update([
                    'sms_notification_id' => $this->id,
                    'sms_verification_status' => 'matched',
                    'sms_verified_at' => now(),
                    'payment_status' => 'processing',
                ]);

                event(new PaymentMatched($order, $this));

                return true;
            }
        }

        return false;
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
