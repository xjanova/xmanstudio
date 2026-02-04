<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WalletTopup extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'user_id',
        'topup_id',
        'amount',
        'bonus_amount',
        'total_amount',
        'payment_method',
        'status',
        'reject_reason',
        'approved_by',
        'approved_at',
        'expires_at',
        'metadata',
        // SMS Payment fields
        'unique_payment_amount_id',
        'payment_display_amount',
        'sms_notification_id',
        'sms_verification_status',
        'sms_verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'payment_display_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
        'sms_verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_EXPIRED = 'expired';

    const METHOD_BANK_TRANSFER = 'bank_transfer';

    const METHOD_PROMPTPAY = 'promptpay';

    const METHOD_TRUEMONEY = 'truemoney';

    const METHOD_CREDIT_CARD = 'credit_card';

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function uniquePaymentAmount()
    {
        return $this->belongsTo(UniquePaymentAmount::class);
    }

    public function smsNotification()
    {
        return $this->belongsTo(SmsPaymentNotification::class, 'sms_notification_id');
    }

    /**
     * Generate unique topup ID
     */
    public static function generateTopupId(): string
    {
        return 'TOP'.now()->format('ymd').strtoupper(Str::random(6));
    }

    /**
     * Calculate bonus based on amount
     */
    public static function calculateBonus(float $amount): float
    {
        $tier = WalletBonusTier::where('is_active', true)
            ->where('min_amount', '<=', $amount)
            ->where(function ($q) use ($amount) {
                $q->whereNull('max_amount')
                    ->orWhere('max_amount', '>=', $amount);
            })
            ->orderBy('min_amount', 'desc')
            ->first();

        if (! $tier) {
            return 0;
        }

        if ($tier->bonus_type === 'percentage') {
            return $amount * ($tier->bonus_value / 100);
        }

        return $tier->bonus_value;
    }

    /**
     * Approve topup
     */
    public function approve(int $approvedBy): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        // Add money to wallet
        $this->wallet->deposit(
            $this->total_amount,
            "เติมเงิน #{$this->topup_id}",
            $this->payment_method,
            $this->payment_reference,
            $approvedBy,
            [
                'topup_id' => $this->id,
                'amount' => $this->amount,
                'bonus' => $this->bonus_amount,
            ]
        );

        return true;
    }

    /**
     * Reject topup
     */
    public function reject(int $approvedBy, string $reason): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'reject_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'รอตรวจสอบ',
            self::STATUS_APPROVED => 'อนุมัติแล้ว',
            self::STATUS_REJECTED => 'ปฏิเสธ',
            self::STATUS_EXPIRED => 'หมดอายุ',
            default => $this->status,
        };
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_EXPIRED => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get payment method label
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            self::METHOD_BANK_TRANSFER => 'โอนเงิน',
            self::METHOD_PROMPTPAY => 'PromptPay',
            self::METHOD_TRUEMONEY => 'TrueMoney',
            self::METHOD_CREDIT_CARD => 'บัตรเครดิต',
            default => $this->payment_method,
        };
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
}
