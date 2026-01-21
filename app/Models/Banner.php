<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'banners';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'image',
        'crop_data',
        'display_width',
        'display_height',
        'link_url',
        'target_blank',
        'enabled',
        'position',
        'pages',
        'priority',
        'start_date',
        'end_date',
        'views',
        'clicks',
        'description',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'enabled' => 'boolean',
        'target_blank' => 'boolean',
        'pages' => 'array',
        'crop_data' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'views' => 'integer',
        'clicks' => 'integer',
    ];

    /**
     * Check if banner is currently active based on date/time.
     */
    public function isActive(): bool
    {
        if (! $this->enabled) {
            return false;
        }

        $now = Carbon::now();

        // Check start date
        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        // Check end date
        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Check if this banner should be displayed on a specific page.
     */
    public function shouldDisplayOn(string $page): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        $pages = $this->pages ?? [];

        return in_array('all', $pages) || in_array($page, $pages);
    }

    /**
     * Get active banner for a specific position and page.
     */
    public static function getForPosition(string $position, string $page = 'all'): ?self
    {
        $now = Carbon::now();

        return self::where('enabled', true)
            ->where('position', $position)
            ->where(function ($query) use ($page) {
                $query->whereJsonContains('pages', 'all')
                    ->orWhereJsonContains('pages', $page);
            })
            ->where(function ($query) use ($now) {
                $query->where(function ($q) use ($now) {
                    // No start date or start date has passed
                    $q->whereNull('start_date')
                        ->orWhere('start_date', '<=', $now);
                })
                    ->where(function ($q) use ($now) {
                        // No end date or end date hasn't passed
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', $now);
                    });
            })
            ->orderBy('priority', 'desc')
            ->first();
    }

    /**
     * Get all active banners for a specific page.
     */
    public static function getForPage(string $page = 'all'): Collection
    {
        $now = Carbon::now();

        return self::where('enabled', true)
            ->where(function ($query) use ($page) {
                $query->whereJsonContains('pages', 'all')
                    ->orWhereJsonContains('pages', $page);
            })
            ->where(function ($query) use ($now) {
                $query->where(function ($q) use ($now) {
                    $q->whereNull('start_date')
                        ->orWhere('start_date', '<=', $now);
                })
                    ->where(function ($q) use ($now) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', $now);
                    });
            })
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Increment view count.
     */
    public function incrementViews(): void
    {
        $this->increment('views');
    }

    /**
     * Increment click count.
     */
    public function incrementClicks(): void
    {
        $this->increment('clicks');
    }

    /**
     * Get the full URL to the banner image.
     */
    public function getImageUrlAttribute(): string
    {
        return asset('storage/'.$this->image);
    }

    /**
     * Get click-through rate (CTR) as percentage.
     */
    public function getCtrAttribute(): float
    {
        if ($this->views === 0) {
            return 0;
        }

        return round(($this->clicks / $this->views) * 100, 2);
    }

    /**
     * Get status text.
     */
    public function getStatusTextAttribute(): string
    {
        if (! $this->enabled) {
            return 'ปิดใช้งาน';
        }

        $now = Carbon::now();

        if ($this->start_date && $now->lt($this->start_date)) {
            return 'รอเริ่ม';
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return 'หมดอายุ';
        }

        return 'กำลังแสดง';
    }
}
