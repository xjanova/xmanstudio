<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromoCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount',
        'min_purchase',
        'applicable_packages',
        'first_purchase_only',
        'max_uses',
        'max_uses_per_user',
        'times_used',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'applicable_packages' => 'array',
        'first_purchase_only' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get usages for this promo code
     */
    public function usages(): HasMany
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    /**
     * Scope for active promo codes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')->orWhereRaw('times_used < max_uses');
            });
    }

    /**
     * Scope by code
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', strtoupper(trim($code)));
    }

    /**
     * Check if code can be used by user
     */
    public function canBeUsedBy(User $user): array
    {
        // Check first purchase only
        if ($this->first_purchase_only) {
            $hasPreviousRentals = $user->rentals()
                ->where('status', '!=', UserRental::STATUS_PENDING)
                ->exists();

            if ($hasPreviousRentals) {
                return [
                    'valid' => false,
                    'message' => 'โค้ดนี้ใช้ได้เฉพาะการซื้อครั้งแรกเท่านั้น',
                ];
            }
        }

        // Check per user usage limit
        $userUsages = $this->usages()->where('user_id', $user->id)->count();
        if ($userUsages >= $this->max_uses_per_user) {
            return [
                'valid' => false,
                'message' => 'คุณใช้โค้ดนี้ครบจำนวนแล้ว',
            ];
        }

        return ['valid' => true, 'message' => 'โค้ดใช้งานได้'];
    }

    /**
     * Calculate discount for amount
     */
    public function calculateDiscount(float $amount, ?int $packageId = null): float
    {
        // Check package applicability
        if ($this->applicable_packages && $packageId) {
            if (!in_array($packageId, $this->applicable_packages)) {
                return 0;
            }
        }

        // Check minimum purchase
        if ($amount < $this->min_purchase) {
            return 0;
        }

        if ($this->discount_type === 'percentage') {
            $discount = $amount * ($this->discount_value / 100);
        } else {
            $discount = $this->discount_value;
        }

        // Apply max discount cap
        if ($this->max_discount && $discount > $this->max_discount) {
            $discount = $this->max_discount;
        }

        return min($discount, $amount);
    }

    /**
     * Record usage of promo code
     */
    public function recordUsage(User $user, RentalPayment $payment, float $discountAmount): PromoCodeUsage
    {
        $this->increment('times_used');

        return $this->usages()->create([
            'user_id' => $user->id,
            'rental_payment_id' => $payment->id,
            'discount_amount' => $discountAmount,
        ]);
    }

    /**
     * Get discount text for display
     */
    public function getDiscountText(): string
    {
        if ($this->discount_type === 'percentage') {
            return "ลด {$this->discount_value}%";
        }
        return "ลด " . number_format($this->discount_value, 0) . " บาท";
    }
}
