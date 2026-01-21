<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdPlacement extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'ad_placements';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'code',
        'enabled',
        'position',
        'pages',
        'priority',
        'description',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'enabled' => 'boolean',
        'pages' => 'array',
    ];

    /**
     * Get active ads for a specific position and page.
     */
    public static function getForPosition(string $position, string $page = 'all'): ?self
    {
        return self::where('enabled', true)
            ->where('position', $position)
            ->where(function ($query) use ($page) {
                $query->whereJsonContains('pages', 'all')
                    ->orWhereJsonContains('pages', $page);
            })
            ->orderBy('priority', 'desc')
            ->first();
    }

    /**
     * Get all active ads for a specific page.
     */
    public static function getForPage(string $page = 'all'): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('enabled', true)
            ->where(function ($query) use ($page) {
                $query->whereJsonContains('pages', 'all')
                    ->orWhereJsonContains('pages', $page);
            })
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Check if this ad should be displayed on a specific page.
     */
    public function shouldDisplayOn(string $page): bool
    {
        if (! $this->enabled) {
            return false;
        }

        $pages = $this->pages ?? [];

        return in_array('all', $pages) || in_array($page, $pages);
    }
}
