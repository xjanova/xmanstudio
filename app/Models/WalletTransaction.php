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

    /**
     * รายได้จากการแชร์การ์ดจอ GPUxMINE (gpuxmine:settle-earnings)
     *
     * แยกจาก deposit/bonus เพราะไม่ใช่เงินที่เจ้าของเติมเข้ามา — ไม่บวก total_deposited
     */
    const TYPE_EARNING = 'earning';

    /**
     * metadata.source ของรายการที่ประเภทเดียวแยกความหมายไม่ได้ — ป้ายภาษาไทยอ่านจากตรงนี้
     * ไม่ต้องเพิ่มค่าใน enum ของคอลัมน์ type (aixman และรายงานเดิมไม่ต้องรู้จักอะไรใหม่)
     */
    const SOURCE_GPUXMINE_REFERRAL = 'gpuxmine_referral';

    const SOURCE_WITHDRAWAL_RETURN = 'withdrawal_return';

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
            self::TYPE_BONUS => 'โบนัส',
            self::TYPE_CASHBACK => 'เงินคืน',
            self::TYPE_EARNING => $this->source() === self::SOURCE_GPUXMINE_REFERRAL ? 'ค่าแนะนำ GPUxMINE' : 'รายได้แชร์การ์ดจอ',
            self::TYPE_ADJUSTMENT => 'ปรับยอด',
            self::TYPE_REFUND => $this->source() === self::SOURCE_WITHDRAWAL_RETURN ? 'คืนยอดที่ขอถอน' : 'คืนเงิน',
            default => $this->type,
        };
    }

    /** metadata.source (ถ้ามี) — บอกว่ารายการนี้มาจากระบบไหน */
    public function source(): ?string
    {
        $source = is_array($this->metadata) ? ($this->metadata['source'] ?? null) : null;

        return is_string($source) ? $source : null;
    }

    /**
     * สถานะเป็นคำไทยสำหรับประวัติในกระเป๋า — null เมื่อไม่ต้องบอกอะไรเพิ่ม (รายการสำเร็จทั่วไป)
     *
     * รายการถอนเงินมีสี่สถานะที่ผู้ใช้ต้องแยกออก: ยอดยังถูกพักอยู่ โอนออกแล้ว หรือถูกคืนเข้ากระเป๋า
     * (แอดมินไม่อนุมัติ = failed, ผู้ใช้ยกเลิกเอง = cancelled — ค่าที่คอลัมน์ status มีอยู่แล้ว)
     */
    public function stateLabel(): ?string
    {
        if ($this->type === self::TYPE_WITHDRAWAL) {
            return match ($this->status) {
                self::STATUS_PENDING => 'พักยอดไว้ · รอแอดมินอนุมัติ',
                self::STATUS_COMPLETED => 'โอนเข้าบัญชีแล้ว',
                self::STATUS_FAILED => 'ไม่อนุมัติ · คืนเข้ากระเป๋าแล้ว',
                self::STATUS_CANCELLED => 'ยกเลิกแล้ว · คืนเข้ากระเป๋าแล้ว',
                default => $this->status_label,
            };
        }

        return $this->status === self::STATUS_COMPLETED ? null : $this->status_label;
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
            self::TYPE_EARNING => 'bi-gpu-card',
            default => 'bi-circle-fill',
        };
    }

    /**
     * Get type color
     */
    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_DEPOSIT, self::TYPE_REFUND, self::TYPE_BONUS, self::TYPE_CASHBACK, self::TYPE_EARNING => 'success',
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
