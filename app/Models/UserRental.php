<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class UserRental extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'rental_package_id',
        'starts_at',
        // When the cycle now being paid for began: first activation, then each renewal.
        'period_starts_at',
        'expires_at',
        'cancelled_at',
        'status',
        'amount_paid',
        'currency',
        'payment_method',
        'payment_reference',
        'usage_stats',
        'auto_renew',
        'next_renewal_at',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'period_starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'next_renewal_at' => 'datetime',
        'amount_paid' => 'decimal:2',
        'usage_stats' => 'array',
        'metadata' => 'array',
        'auto_renew' => 'boolean',
    ];

    protected $appends = ['is_active', 'is_expired', 'days_remaining'];

    const STATUS_PENDING = 'pending';

    const STATUS_ACTIVE = 'active';

    const STATUS_EXPIRED = 'expired';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_SUSPENDED = 'suspended';

    protected static function booted(): void
    {
        static::creating(function (UserRental $rental) {
            if (! $rental->uuid) {
                $rental->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Add-ons bought for this rental (any status).
     */
    public function addons(): HasMany
    {
        return $this->hasMany(UserRentalAddon::class);
    }

    /** The product this rental is for ("mlm"), null for a generic rental. */
    public function productKey(): ?string
    {
        return $this->rentalPackage()->withTrashed()->value('product_key');
    }

    /** "monthly" | "yearly", from the package (see RentalPackage::billingCycle). */
    public function billingCycle(): ?string
    {
        $package = $this->rentalPackage ?? $this->rentalPackage()->withTrashed()->first();

        return $package?->billingCycle();
    }

    /**
     * Get the user that owns the rental
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the rental package
     */
    public function rentalPackage(): BelongsTo
    {
        return $this->belongsTo(RentalPackage::class);
    }

    /**
     * Get payments for this rental
     */
    public function payments(): HasMany
    {
        return $this->hasMany(RentalPayment::class);
    }

    /**
     * Get invoices for this rental
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(RentalInvoice::class);
    }

    /**
     * Scope for active rentals
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('expires_at', '>', now());
    }

    /**
     * Scope for expired rentals
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope for pending rentals
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Check if rental is currently active
     */
    public function getIsActiveAttribute(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return $this->status === self::STATUS_ACTIVE
            && $this->expires_at->isFuture();
    }

    /**
     * Check if rental is expired
     */
    public function getIsExpiredAttribute(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Get remaining days
     */
    public function getDaysRemainingAttribute(): int
    {
        if (! $this->expires_at || $this->is_expired) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->expires_at, false));
    }

    /**
     * Activate the rental
     */
    public function activate(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_ACTIVE,
            'starts_at' => now(),
            'period_starts_at' => now(),
            'expires_at' => $this->rentalPackage->calculateExpiryDate(now()),
        ]);

        return true;
    }

    /**
     * Pay for one more cycle: from the current expiry if it is still ahead (nothing lost by
     * renewing early), from now if it has passed. A different package of the same product (monthly
     * → yearly) can be given; the rental moves onto it.
     *
     * Only an active or expired rental renews — a cancelled or suspended one is left as it is.
     */
    public function renew(?RentalPackage $package = null): bool
    {
        if (! in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_EXPIRED], true)) {
            return false;
        }

        $package ??= $this->rentalPackage;
        $base = $this->expires_at && $this->expires_at->isFuture() ? $this->expires_at->copy() : now();

        $this->update([
            'rental_package_id' => $package->id,
            'status' => self::STATUS_ACTIVE,
            'starts_at' => $this->starts_at ?? $base,
            'period_starts_at' => $base,
            'expires_at' => $package->calculateExpiryDate($base),
        ]);
        $this->setRelation('rentalPackage', $package);

        return true;
    }

    /**
     * Expire the rental
     */
    public function expire(): bool
    {
        $this->update(['status' => self::STATUS_EXPIRED]);

        return true;
    }

    /**
     * Cancel the rental
     */
    public function cancel(?string $reason = null): bool
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'auto_renew' => false,
            'notes' => $reason ? ($this->notes . "\nยกเลิก: " . $reason) : $this->notes,
        ]);

        return true;
    }

    /**
     * Get formatted status
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'รอชำระเงิน',
            self::STATUS_ACTIVE => 'ใช้งานอยู่',
            self::STATUS_EXPIRED => 'หมดอายุ',
            self::STATUS_CANCELLED => 'ยกเลิกแล้ว',
            self::STATUS_SUSPENDED => 'ระงับชั่วคราว',
            default => $this->status,
        };
    }
}
