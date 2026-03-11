<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Affiliate extends Model
{
    protected $fillable = [
        'user_id',
        'parent_id',
        'depth',
        'path',
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
        'depth' => 'integer',
    ];

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Recursive children (for eager loading entire subtree).
     */
    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren.user');
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

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    // ── Tree Methods ──

    /**
     * Get all ancestor affiliates (using materialized path).
     */
    public function getAncestors(): Collection
    {
        if (empty($this->path)) {
            return collect();
        }

        $ids = array_map('intval', explode('/', $this->path));

        return static::whereIn('id', $ids)->with('user')->get()
            ->sortBy(fn ($a) => array_search($a->id, $ids));
    }

    /**
     * Get all descendants (using materialized path).
     */
    public function getDescendants(): Collection
    {
        $id = $this->id;

        return static::where(function ($q) use ($id) {
            $q->where('path', 'LIKE', "{$id}/%")
                ->orWhere('path', 'LIKE', "%/{$id}/%")
                ->orWhere('path', 'LIKE', "%/{$id}");
        })->with('user')->get();
    }

    /**
     * Check if this affiliate is a descendant of another.
     */
    public function isDescendantOf(int $affiliateId): bool
    {
        if (empty($this->path)) {
            return false;
        }

        return in_array($affiliateId, array_map('intval', explode('/', $this->path)));
    }

    /**
     * Update materialized path based on parent.
     */
    public function updatePath(): void
    {
        if ($this->parent_id) {
            $parent = static::find($this->parent_id);
            if ($parent) {
                $this->path = $parent->path ? $parent->path . '/' . $parent->id : (string) $parent->id;
                $this->depth = $parent->depth + 1;
            } else {
                $this->path = null;
                $this->depth = 0;
            }
        } else {
            $this->path = null;
            $this->depth = 0;
        }
        $this->saveQuietly();
    }

    /**
     * Recursively update paths for all descendants.
     */
    public function updateDescendantPaths(): void
    {
        foreach ($this->children()->get() as $child) {
            $child->updatePath();
            $child->updateDescendantPaths();
        }
    }

    /**
     * Build tree data for Alpine.js org chart.
     */
    public function toTreeArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user->name ?? 'Unknown',
            'email' => $this->user->email ?? '',
            'referral_code' => $this->referral_code,
            'commission_rate' => (float) $this->commission_rate,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'total_earned' => (float) $this->total_earned,
            'total_referrals' => $this->total_referrals,
            'total_conversions' => $this->total_conversions,
            'children_count' => $this->children->count(),
            'children' => $this->children->map(fn ($child) => $child->toTreeArray())->toArray(),
        ];
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
     * Get or create affiliate for a user, optionally setting a parent.
     * If no parent specified, defaults to admin's affiliate (first admin user).
     */
    public static function getOrCreateForUser(int $userId, ?int $parentId = null): self
    {
        $existing = self::where('user_id', $userId)->first();
        if ($existing) {
            return $existing;
        }

        // If no referrer, default parent to admin's affiliate
        if ($parentId === null) {
            $adminAffiliate = self::getAdminAffiliate($userId);
            if ($adminAffiliate) {
                $parentId = $adminAffiliate->id;
            }
        }

        $affiliate = self::create([
            'user_id' => $userId,
            'parent_id' => $parentId,
            'referral_code' => self::generateReferralCode(),
            'commission_rate' => 10.00,
            'status' => 'active',
        ]);

        $affiliate->updatePath();

        return $affiliate->fresh();
    }

    /**
     * Get admin's affiliate record (auto-create if needed).
     * Returns null only if the user being created IS the admin.
     */
    private static function getAdminAffiliate(int $excludeUserId): ?self
    {
        // Find first admin user (not the user being registered)
        $admin = \App\Models\User::whereIn('role', ['admin', 'super_admin'])
            ->where('id', '!=', $excludeUserId)
            ->orderBy('id')
            ->first();

        if (! $admin) {
            return null;
        }

        // Get or create admin's own affiliate (without parent — root node)
        $adminAffiliate = self::where('user_id', $admin->id)->first();
        if (! $adminAffiliate) {
            $adminAffiliate = self::create([
                'user_id' => $admin->id,
                'parent_id' => null,
                'referral_code' => self::generateReferralCode(),
                'commission_rate' => 10.00,
                'status' => 'active',
            ]);
            $adminAffiliate->updatePath();
            $adminAffiliate = $adminAffiliate->fresh();
        }

        return $adminAffiliate;
    }

    /**
     * Record a click.
     */
    public function recordClick(): void
    {
        $this->increment('total_clicks');
    }

    /**
     * Get the full referral URL (site-wide, not tied to specific product).
     */
    public function getReferralUrlAttribute(): string
    {
        return url('/?ref=' . $this->referral_code);
    }

    /**
     * Calculate commission for a given payment amount.
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
     * Get direct children count.
     */
    public function getDirectChildrenCountAttribute(): int
    {
        return $this->children()->count();
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
