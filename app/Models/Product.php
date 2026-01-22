<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'short_description',
        'features',
        'price',
        'image',
        'images',
        'sku',
        'is_custom',
        'requires_license',
        'stock',
        'low_stock_threshold',
        'is_active',
        'is_coming_soon',
        'coming_soon_until',
    ];

    protected $casts = [
        'features' => 'array',
        'images' => 'array',
        'is_custom' => 'boolean',
        'requires_license' => 'boolean',
        'is_active' => 'boolean',
        'is_coming_soon' => 'boolean',
        'coming_soon_until' => 'datetime',
        'price' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function licenseKeys(): HasMany
    {
        return $this->hasMany(LicenseKey::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ProductVersion::class);
    }

    public function githubSetting(): HasOne
    {
        return $this->hasOne(GithubSetting::class);
    }

    /**
     * Get the latest active version
     */
    public function latestVersion()
    {
        return $this->versions()->active()->latest()->first();
    }

    /**
     * Check if product has GitHub settings configured
     */
    public function hasGithubSettings(): bool
    {
        return $this->githubSetting()->exists();
    }

    /**
     * Check if product is currently coming soon
     */
    public function isComingSoon(): bool
    {
        if (! $this->is_coming_soon) {
            return false;
        }

        // If coming_soon_until is set, check if it's still in the future
        if ($this->coming_soon_until) {
            return $this->coming_soon_until->isFuture();
        }

        return true;
    }

    /**
     * Check if product is available for purchase
     */
    public function isAvailable(): bool
    {
        return $this->is_active && ! $this->isComingSoon();
    }

    /**
     * Scope for coming soon products
     */
    public function scopeComingSoon($query)
    {
        return $query->where('is_coming_soon', true);
    }

    /**
     * Scope for available products (active and not coming soon)
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->where('is_coming_soon', false)
                    ->orWhere(function ($q2) {
                        $q2->where('is_coming_soon', true)
                            ->whereNotNull('coming_soon_until')
                            ->where('coming_soon_until', '<=', now());
                    });
            });
    }
}
