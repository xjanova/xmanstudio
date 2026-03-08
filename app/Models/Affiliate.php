<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Affiliate extends Model
{
    protected $fillable = [
        'user_id',
        'referral_code',
        'commission_rate',
        'status',
        'total_earned',
        'total_paid',
        'total_pending',
        'total_clicks',
        'total_referrals',
        'total_conversions',
        'notes',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'total_pending' => 'decimal:2',
    ];

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ── Helpers ──

    /**
     * Generate a unique referral code.
     */
    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Get or create affiliate for a user.
     */
    public static function getOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'referral_code' => self::generateReferralCode(),
                'commission_rate' => 10.00,
                'status' => 'active',
            ]
        );
    }

    /**
     * Record a click.
     */
    public function recordClick(): void
    {
        $this->increment('total_clicks');
    }

    /**
     * Get the full referral URL for Tping pricing.
     */
    public function getReferralUrlAttribute(): string
    {
        return url('/tping/pricing?ref=' . $this->referral_code);
    }

    /**
     * Calculate commission for a given order amount.
     */
    public function calculateCommission(float $orderAmount): float
    {
        return floor($orderAmount * $this->commission_rate / 100);
    }

    /**
     * Check if this affiliate is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get unpaid balance.
     */
    public function getUnpaidBalanceAttribute(): float
    {
        return $this->total_earned - $this->total_paid;
    }

    /**
     * Get conversion rate (%).
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->total_clicks === 0) {
            return 0;
        }

        return round(($this->total_conversions / $this->total_clicks) * 100, 1);
    }

    /**
     * Status label in Thai.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'ใช้งาน',
            'pending' => 'รอตรวจสอบ',
            'suspended' => 'ระงับ',
            default => $this->status,
        };
    }

    /**
     * Status color class.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'green',
            'pending' => 'yellow',
            'suspended' => 'red',
            default => 'gray',
        };
    }
}
