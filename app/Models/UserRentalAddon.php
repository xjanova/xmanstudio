<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An add-on bought for one rental. It lives and renews with that rental: its expires_at follows
 * the rental's on activation, renewal and admin extension (App\Services\Rentals\RentalLifecycle).
 *
 * pending   — waiting for the payment that buys it (rental_payment_id)
 * active    — in force for as long as the rental is
 * cancelled — the rental was cancelled, its payment refused, or it was dropped at renewal
 */
class UserRentalAddon extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_rental_id',
        'rental_addon_id',
        'rental_payment_id',
        'quantity',
        'status',
        'price_satang',
        'days_charged',
        'cycle_days',
        'starts_at',
        'expires_at',
        'cancelled_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_satang' => 'integer',
        'days_charged' => 'integer',
        'cycle_days' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(UserRental::class, 'user_rental_id');
    }

    public function addon(): BelongsTo
    {
        return $this->belongsTo(RentalAddon::class, 'rental_addon_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(RentalPayment::class, 'rental_payment_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'รอชำระเงิน',
            self::STATUS_ACTIVE => 'ใช้งานอยู่',
            self::STATUS_CANCELLED => 'ยกเลิกแล้ว',
            default => (string) $this->status,
        };
    }
}
