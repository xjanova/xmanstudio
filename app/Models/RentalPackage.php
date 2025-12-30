<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class RentalPackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'name_th',
        'description',
        'description_th',
        'duration_type',
        'duration_value',
        'price',
        'original_price',
        'currency',
        'features',
        'limits',
        'is_active',
        'is_featured',
        'is_popular',
        'sort_order',
        'has_trial',
        'trial_days',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'features' => 'array',
        'limits' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_popular' => 'boolean',
        'has_trial' => 'boolean',
    ];

    protected $appends = ['display_name', 'duration_text', 'discount_percentage'];

    /**
     * Get user rentals for this package
     */
    public function userRentals(): HasMany
    {
        return $this->hasMany(UserRental::class);
    }

    /**
     * Scope for active packages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured packages
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Get display name based on locale
     */
    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'th' && $this->name_th ? $this->name_th : $this->name;
    }

    /**
     * Get duration text for display
     */
    public function getDurationTextAttribute(): string
    {
        $value = $this->duration_value;
        $texts = [
            'hourly' => $value === 1 ? '1 ชั่วโมง' : "{$value} ชั่วโมง",
            'daily' => $value === 1 ? '1 วัน' : "{$value} วัน",
            'weekly' => $value === 1 ? '1 สัปดาห์' : "{$value} สัปดาห์",
            'monthly' => $value === 1 ? '1 เดือน' : "{$value} เดือน",
            'yearly' => $value === 1 ? '1 ปี' : "{$value} ปี",
        ];

        return $texts[$this->duration_type] ?? "{$value} {$this->duration_type}";
    }

    /**
     * Get discount percentage if original price exists
     */
    public function getDiscountPercentageAttribute(): ?int
    {
        if (!$this->original_price || $this->original_price <= $this->price) {
            return null;
        }

        return (int) round((($this->original_price - $this->price) / $this->original_price) * 100);
    }

    /**
     * Calculate expiry date from start date
     */
    public function calculateExpiryDate(Carbon $startDate): Carbon
    {
        return match ($this->duration_type) {
            'hourly' => $startDate->copy()->addHours($this->duration_value),
            'daily' => $startDate->copy()->addDays($this->duration_value),
            'weekly' => $startDate->copy()->addWeeks($this->duration_value),
            'monthly' => $startDate->copy()->addMonths($this->duration_value),
            'yearly' => $startDate->copy()->addYears($this->duration_value),
            default => $startDate->copy()->addDays($this->duration_value),
        };
    }

    /**
     * Get formatted price
     */
    public function getFormattedPrice(): string
    {
        return number_format($this->price, 0) . ' ' . $this->currency;
    }

    /**
     * Get duration in days
     */
    public function getDurationInDays(): int
    {
        return match ($this->duration_type) {
            'hourly' => max(1, (int) ceil($this->duration_value / 24)),
            'daily' => $this->duration_value,
            'weekly' => $this->duration_value * 7,
            'monthly' => $this->duration_value * 30,
            'yearly' => $this->duration_value * 365,
            default => $this->duration_value,
        };
    }
}
