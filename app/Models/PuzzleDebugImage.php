<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PuzzleDebugImage extends Model
{
    protected $fillable = [
        'machine_id',
        'app_version',
        'detection_method',
        'gap_x',
        'slider_x',
        'drag_dist',
        'track_width',
        'success',
        'actual_gap_x',
        'image_paths',
        'metadata',
        'captured_at',
    ];

    protected $casts = [
        'image_paths' => 'array',
        'metadata' => 'array',
        'success' => 'boolean',
        'captured_at' => 'datetime',
    ];

    /**
     * Get full URLs for all stored images
     */
    public function getImageUrlsAttribute(): array
    {
        if (! $this->image_paths) {
            return [];
        }

        return array_map(function ($path) {
            return Storage::disk('public')->url($path);
        }, $this->image_paths);
    }

    /**
     * Scope: filter by machine
     */
    public function scopeForMachine($query, string $machineId)
    {
        return $query->where('machine_id', $machineId);
    }

    /**
     * Scope: unlabeled (no actual_gap_x set yet)
     */
    public function scopeUnlabeled($query)
    {
        return $query->whereNull('actual_gap_x');
    }

    /**
     * Scope: labeled (has actual_gap_x)
     */
    public function scopeLabeled($query)
    {
        return $query->whereNotNull('actual_gap_x');
    }
}
