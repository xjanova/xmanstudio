<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class UserRental extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'rental_package_id',
        'starts_at',
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
        if (!$this->expires_at) {
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
        if (!$this->expires_at) {
            return false;
        }
        return $this->expires_at->isPast();
    }

    /**
     * Get remaining days
     */
    public function getDaysRemainingAttribute(): int
    {
        if (!$this->expires_at || $this->is_expired) {
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
            'expires_at' => $this->rentalPackage->calculateExpiryDate(now()),
        ]);

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
    public function getStatusLabel(): string
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
