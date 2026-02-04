<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'subtotal',
        'tax',
        'discount',
        'coupon_id',
        'coupon_code',
        'total',
        'payment_method',
        'payment_status',
        'paid_at',
        'wallet_transaction_id',
        'payment_slip',
        'status',
        'notes',
        'sent_to_line',
        // SMS Payment fields
        'unique_payment_amount_id',
        'sms_notification_id',
        'sms_verification_status',
        'sms_verified_at',
        'payment_display_amount',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'sms_verified_at' => 'datetime',
        'payment_display_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get the unique payment amount for this order.
     */
    public function uniquePaymentAmount(): BelongsTo
    {
        return $this->belongsTo(UniquePaymentAmount::class);
    }

    /**
     * Get the SMS notification that matched this order.
     */
    public function smsNotification(): BelongsTo
    {
        return $this->belongsTo(SmsPaymentNotification::class, 'sms_notification_id');
    }

    /**
     * Check if this order is using SMS payment verification.
     */
    public function usesSmsPayment(): bool
    {
        return $this->unique_payment_amount_id !== null;
    }

    /**
     * Check if SMS payment has been verified.
     */
    public function isSmsVerified(): bool
    {
        return in_array($this->sms_verification_status, ['confirmed', 'matched']);
    }

    /**
     * Get the amount to display to customer (unique amount if using SMS payment).
     */
    public function getDisplayAmountAttribute(): float
    {
        return $this->payment_display_amount ?? $this->total;
    }

    /**
     * Check if this order is waiting for SMS verification.
     */
    public function isWaitingForSmsVerification(): bool
    {
        return $this->usesSmsPayment()
            && $this->sms_verification_status === 'pending'
            && $this->payment_status === 'pending';
    }

    /**
     * Scope: Orders waiting for SMS verification
     */
    public function scopeWaitingForSmsVerification($query)
    {
        return $query->whereNotNull('unique_payment_amount_id')
            ->where('sms_verification_status', 'pending')
            ->where('payment_status', 'pending');
    }

    /**
     * Scope: Orders with matched SMS but not yet confirmed
     */
    public function scopeSmsMatched($query)
    {
        return $query->whereNotNull('unique_payment_amount_id')
            ->where('sms_verification_status', 'matched');
    }
}
