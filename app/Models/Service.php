<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'name_th',
        'description',
        'description_th',
        'icon',
        'image',
        'features',
        'features_th',
        'starting_price',
        'price_unit',
        'order',
        'is_active',
        'is_featured',
        'is_coming_soon',
        'coming_soon_until',
    ];

    protected $casts = [
        'features' => 'array',
        'features_th' => 'array',
        'starting_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_coming_soon' => 'boolean',
        'coming_soon_until' => 'datetime',
    ];

    /**
     * Scope for active services
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured services
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for ordering
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /**
     * Get localized name
     */
    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'th' && $this->name_th) {
            return $this->name_th;
        }

        return $this->name;
    }

    /**
     * Get localized description
     */
    public function getLocalizedDescriptionAttribute(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'th' && $this->description_th) {
            return $this->description_th;
        }

        return $this->description;
    }

    /**
     * Get localized features
     */
    public function getLocalizedFeaturesAttribute(): array
    {
        $locale = app()->getLocale();

        if ($locale === 'th' && $this->features_th) {
            return $this->features_th;
        }

        return $this->features ?? [];
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        if (! $this->starting_price) {
            return 'ติดต่อสอบถาม';
        }

        return number_format($this->starting_price) . ' บาท/' . $this->price_unit;
    }

    /**
     * Check if service is currently coming soon
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
     * Check if service is available
     */
    public function isAvailable(): bool
    {
        return $this->is_active && ! $this->isComingSoon();
    }

    /**
     * Scope for coming soon services
     */
    public function scopeComingSoon($query)
    {
        return $query->where('is_coming_soon', true);
    }

    /**
     * Scope for available services (active and not coming soon)
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
