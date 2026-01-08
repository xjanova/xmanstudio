<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuotationCategory extends Model
{
    protected $fillable = [
        'key',
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
