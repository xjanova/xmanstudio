<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommission extends Model
{
    protected $fillable = [
        'affiliate_id',
        'order_id',
        'referred_user_id',
        'order_amount',
        'commission_rate',
        'commission_amount',
        'status',
        'wallet_transaction_id',
        'paid_at',
        'admin_note',
    ];

    protected $casts = [
        'order_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    // ── Relationships ──

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    // ── Scopes ──

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    // ── Helpers ──

    /**
     * Approve this commission (auto-pay to wallet).
     */
    public function approveAndPay(?int $adminId = null): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $affiliate = $this->affiliate;
        $wallet = Wallet::getOrCreateForUser($affiliate->user_id);

        // Add commission to wallet as bonus
        $transaction = $wallet->addBonus(
            $this->commission_amount,
            "ค่าแนะนำ Affiliate จาก Order #{$this->order->order_number}",
            $adminId,
            [
                'affiliate_commission_id' => $this->id,
                'affiliate_id' => $affiliate->id,
                'order_id' => $this->order_id,
            ]
        );

        // Update commission record
        $this->update([
            'status' => 'paid',
            'wallet_transaction_id' => $transaction->id,
            'paid_at' => now(),
        ]);

        // Update affiliate totals
        $affiliate->increment('total_paid', $this->commission_amount);
        $affiliate->decrement('total_pending', $this->commission_amount);

        return true;
    }

    /**
     * Reject this commission.
     */
    public function reject(?string $reason = null): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'admin_note' => $reason,
        ]);

        $this->affiliate->decrement('total_pending', $this->commission_amount);

        return true;
    }

    /**
     * Status label in Thai.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'รอตรวจสอบ',
            'approved' => 'อนุมัติแล้ว',
            'paid' => 'จ่ายแล้ว',
            'rejected' => 'ปฏิเสธ',
            default => $this->status,
        };
    }

    /**
     * Status color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'approved' => 'blue',
            'paid' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }
}
