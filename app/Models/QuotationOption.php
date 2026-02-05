<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationOption extends Model
{
    protected $fillable = [
        'quotation_category_id',
        'key',
        'name',
        'name_th',
        'description',
        'description_th',
        'features',
        'features_th',
        'steps',
        'steps_th',
        'long_description',
        'long_description_th',
        'price',
        'image',
        'order',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'order' => 'integer',
        'features' => 'array',
        'features_th' => 'array',
        'steps' => 'array',
        'steps_th' => 'array',
    ];

    /**
     * Get the category that owns this option
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(QuotationCategory::class, 'quotation_category_id');
    }

    /**
     * Scope to get only active options
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get options ordered by order field
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

    /**
     * Get formatted price with currency
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 0) . ' ฿';
    }
}
