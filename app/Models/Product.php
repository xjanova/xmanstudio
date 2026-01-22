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
    ];

    protected $casts = [
        'features' => 'array',
        'images' => 'array',
        'is_custom' => 'boolean',
        'requires_license' => 'boolean',
        'is_active' => 'boolean',
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
}
