<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount',
        'min_order_amount',
        'min_items',
        'usage_limit',
        'usage_limit_per_user',
        'used_count',
        'starts_at',
        'expires_at',
        'applicable_products',
        'applicable_categories',
        'excluded_products',
        'applicable_license_types',
        'first_order_only',
        'allowed_user_ids',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'applicable_products' => 'array',
        'applicable_categories' => 'array',
        'excluded_products' => 'array',
        'applicable_license_types' => 'array',
        'allowed_user_ids' => 'array',
        'first_order_only' => 'boolean',
        'is_active' => 'boolean',
    ];

    const TYPE_PERCENTAGE = 'percentage';

    const TYPE_FIXED = 'fixed';

    /**
     * Usages relationship
     */
    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Check if coupon is valid
     */
    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        // Check date range
        if ($this->starts_at && now()->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && now()->gt($this->expires_at)) {
            return false;
        }

        // Check usage limit
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can use this coupon
     */
    public function canBeUsedBy(?User $user, float $orderAmount = 0, array $productIds = []): array
    {
        if (! $user) {
            return ['valid' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อนใช้คูปอง'];
        }

        if (! $this->isValid()) {
            return ['valid' => false, 'message' => 'คูปองไม่สามารถใช้งานได้'];
        }

        // Check minimum order amount
        if ($this->min_order_amount && $orderAmount < $this->min_order_amount) {
            return [
                'valid' => false,
                'message' => 'ยอดสั่งซื้อขั้นต่ำ ฿' . number_format($this->min_order_amount, 2),
            ];
        }

        // Check user usage limit
        $userUsageCount = $this->usages()->where('user_id', $user->id)->count();
        if ($userUsageCount >= $this->usage_limit_per_user) {
            return ['valid' => false, 'message' => 'คุณใช้คูปองนี้ครบจำนวนแล้ว'];
        }

        // Check first order only
        if ($this->first_order_only) {
            $hasOrder = Order::where('user_id', $user->id)
                ->where('status', '!=', 'cancelled')
                ->exists();
            if ($hasOrder) {
                return ['valid' => false, 'message' => 'คูปองนี้สำหรับการสั่งซื้อครั้งแรกเท่านั้น'];
            }
        }

        // Check allowed users
        if (! empty($this->allowed_user_ids) && ! in_array($user->id, $this->allowed_user_ids)) {
            return ['valid' => false, 'message' => 'คูปองนี้ไม่สามารถใช้กับบัญชีของคุณได้'];
        }

        // Check applicable products
        if (! empty($this->applicable_products) && ! empty($productIds)) {
            $applicableCount = count(array_intersect($productIds, $this->applicable_products));
            if ($applicableCount === 0) {
                return ['valid' => false, 'message' => 'คูปองนี้ไม่สามารถใช้กับสินค้าที่เลือกได้'];
            }
        }

        // Check excluded products
        if (! empty($this->excluded_products) && ! empty($productIds)) {
            $excludedCount = count(array_intersect($productIds, $this->excluded_products));
            if ($excludedCount === count($productIds)) {
                return ['valid' => false, 'message' => 'สินค้าทั้งหมดถูกยกเว้นจากคูปองนี้'];
            }
        }

        return ['valid' => true, 'message' => 'ใช้คูปองได้'];
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount(float $amount): float
    {
        if ($this->discount_type === self::TYPE_PERCENTAGE) {
            $discount = $amount * ($this->discount_value / 100);

            // Apply max discount cap
            if ($this->max_discount && $discount > $this->max_discount) {
                $discount = $this->max_discount;
            }
        } else {
            $discount = $this->discount_value;
        }

        // Discount cannot exceed order amount
        return min($discount, $amount);
    }

    /**
     * Record usage
     */
    public function recordUsage(User $user, ?Order $order, float $discountAmount, float $orderAmount): CouponUsage
    {
        $usage = $this->usages()->create([
            'user_id' => $user->id,
            'order_id' => $order?->id,
            'discount_amount' => $discountAmount,
            'order_amount' => $orderAmount,
        ]);

        $this->increment('used_count');

        return $usage;
    }

    /**
     * Generate unique code
     */
    public static function generateCode(int $length = 8): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhereColumn('used_count', '<', 'usage_limit');
            });
    }

    /**
     * Accessors
     */
    public function getDiscountLabelAttribute(): string
    {
        if ($this->discount_type === self::TYPE_PERCENTAGE) {
            $label = "{$this->discount_value}%";
            if ($this->max_discount) {
                $label .= ' (สูงสุด ฿' . number_format($this->max_discount, 0) . ')';
            }

            return $label;
        }

        return '฿' . number_format($this->discount_value, 0);
    }

    public function getStatusLabelAttribute(): string
    {
        if (! $this->is_active) {
            return 'ปิดใช้งาน';
        }

        if ($this->expires_at && now()->gt($this->expires_at)) {
            return 'หมดอายุ';
        }

        if ($this->starts_at && now()->lt($this->starts_at)) {
            return 'ยังไม่เริ่ม';
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return 'ใช้ครบแล้ว';
        }

        return 'ใช้งานได้';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status_label) {
            'ใช้งานได้' => 'success',
            'ยังไม่เริ่ม' => 'warning',
            'หมดอายุ', 'ใช้ครบแล้ว' => 'danger',
            default => 'secondary',
        };
    }
}
