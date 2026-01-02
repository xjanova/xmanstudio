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
    ];

    protected $casts = [
        'features' => 'array',
        'features_th' => 'array',
        'starting_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
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
        if (!$this->starting_price) {
            return 'ติดต่อสอบถาม';
        }

        return number_format($this->starting_price) . ' บาท/' . $this->price_unit;
    }
}
