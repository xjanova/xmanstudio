<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletBonusTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'min_amount',
        'max_amount',
        'bonus_type',
        'bonus_value',
        'is_active',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'bonus_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    const TYPE_PERCENTAGE = 'percentage';

    const TYPE_FIXED = 'fixed';

    /**
     * Get bonus label
     */
    public function getBonusLabelAttribute(): string
    {
        if ($this->bonus_type === self::TYPE_PERCENTAGE) {
            return "{$this->bonus_value}%";
        }

        return '฿' . number_format($this->bonus_value, 0);
    }

    /**
     * Get range label
     */
    public function getRangeLabelAttribute(): string
    {
        $min = '฿' . number_format($this->min_amount, 0);

        if ($this->max_amount) {
            $max = '฿' . number_format($this->max_amount, 0);

            return "{$min} - {$max}";
        }

        return "{$min} ขึ้นไป";
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
