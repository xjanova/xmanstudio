<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An add-on a product's rental can carry (XMAN MLM: more members, a custom domain, …).
 *
 * Prices are satang per unit for one full cycle of the main package; which of the two applies
 * follows the main package's cycle. A purchase made mid-cycle is prorated by
 * App\Services\Rentals\RentalAddonPricing, then renews with the main package at full price.
 */
class RentalAddon extends Model
{
    protected $fillable = [
        'product_key',
        'code',
        'name',
        'name_th',
        'description_th',
        'price_monthly_satang',
        'price_yearly_satang',
        'unit_label_th',
        'max_quantity',
        'exclusive_group',
        'is_active',
        'price_is_proposal',
        'sort_order',
    ];

    protected $casts = [
        'price_monthly_satang' => 'integer',
        'price_yearly_satang' => 'integer',
        'max_quantity' => 'integer',
        'is_active' => 'boolean',
        'price_is_proposal' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserRentalAddon::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForProduct(Builder $query, ?string $productKey): Builder
    {
        return $query->where('product_key', $productKey)->orderBy('sort_order')->orderBy('id');
    }

    public function getDisplayNameAttribute(): string
    {
        return app()->getLocale() === 'th' && $this->name_th ? $this->name_th : ($this->name_th ?: $this->name);
    }

    /** Price of one unit for one full cycle ("monthly" | "yearly"), in satang. */
    public function cyclePriceSatang(string $cycle): int
    {
        return $cycle === 'yearly' ? (int) $this->price_yearly_satang : (int) $this->price_monthly_satang;
    }

    public function allowsQuantity(): bool
    {
        return (int) $this->max_quantity > 1;
    }
}
