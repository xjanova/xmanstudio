<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'user_id',
        'transaction_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'reference_type',
        'reference_id',
        'payment_method',
        'payment_reference',
        'description',
        'admin_note',
        'created_by',
        'status',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    const TYPE_DEPOSIT = 'deposit';
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_PAYMENT = 'payment';
    const TYPE_REFUND = 'refund';
    const TYPE_BONUS = 'bonus';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_CASHBACK = 'cashback';

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_DEPOSIT => 'เติมเงิน',
            self::TYPE_WITHDRAWAL => 'ถอนเงิน',
            self::TYPE_PAYMENT => 'ชำระเงิน',
            self::TYPE_REFUND => 'คืนเงิน',
            self::TYPE_BONUS => 'โบนัส',
            self::TYPE_ADJUSTMENT => 'ปรับยอด',
            self::TYPE_CASHBACK => 'เงินคืน',
            default => $this->type,
        };
    }

    /**
     * Get type icon
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_DEPOSIT => 'bi-plus-circle-fill',
            self::TYPE_WITHDRAWAL => 'bi-dash-circle-fill',
            self::TYPE_PAYMENT => 'bi-cart-fill',
            self::TYPE_REFUND => 'bi-arrow-counterclockwise',
            self::TYPE_BONUS => 'bi-gift-fill',
            self::TYPE_ADJUSTMENT => 'bi-sliders',
            self::TYPE_CASHBACK => 'bi-cash-coin',
            default => 'bi-circle-fill',
        };
    }

    /**
     * Get type color
     */
    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_DEPOSIT, self::TYPE_REFUND, self::TYPE_BONUS, self::TYPE_CASHBACK => 'success',
            self::TYPE_WITHDRAWAL, self::TYPE_PAYMENT => 'danger',
            self::TYPE_ADJUSTMENT => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'รอดำเนินการ',
            self::STATUS_COMPLETED => 'สำเร็จ',
            self::STATUS_FAILED => 'ล้มเหลว',
            self::STATUS_CANCELLED => 'ยกเลิก',
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
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Check if amount is positive (credit)
     */
    public function isCredit(): bool
    {
        return $this->amount > 0;
    }

    /**
     * Check if amount is negative (debit)
     */
    public function isDebit(): bool
    {
        return $this->amount < 0;
    }

    /**
     * Scopes
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCredits($query)
    {
        return $query->where('amount', '>', 0);
    }

    public function scopeDebits($query)
    {
        return $query->where('amount', '<', 0);
    }
}
