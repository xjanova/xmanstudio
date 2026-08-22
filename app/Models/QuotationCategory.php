<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuotationCategory extends Model
{
    /** Main service category shown in the quotation builder's service grid. */
    public const TYPE_SERVICE = 'service';

    /** Optional extras group (support, delivery, hosting, design, SEO). */
    public const TYPE_ADDON = 'addon';

    protected $fillable = [
        'key',
        'type',
        'name',
        'name_th',
        'icon',
        'description',
        'description_th',
        'image',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get all options for this category
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuotationOption::class);
    }

    /**
     * Get active options ordered by order field
     */
    public function activeOptions(): HasMany
    {
        return $this->hasMany(QuotationOption::class)
            ->where('is_active', true)
            ->orderBy('order');
    }

    /**
     * Scope to get only active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get categories ordered by order field
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Scope to main service categories only (excludes add-on groups)
     */
    public function scopeServices($query)
    {
        return $query->where('type', self::TYPE_SERVICE);
    }

    /**
     * Scope to add-on groups only (support, delivery, hosting, design, SEO)
     */
    public function scopeAddons($query)
    {
        return $query->where('type', self::TYPE_ADDON);
    }

    /**
     * Get the display name based on current locale
     */
    public function getDisplayNameAttribute(): string
    {
        return app()->getLocale() === 'th' && $this->name_th
            ? $this->name_th
            : $this->name;
    }

    /**
     * Get the display description based on current locale
     */
    public function getDisplayDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'th' && $this->description_th
            ? $this->description_th
            : $this->description;
    }
}
