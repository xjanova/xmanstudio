<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetalXMusicLibrary extends Model
{
    protected $table = 'metal_x_music_library';

    protected $fillable = [
        'title',
        'file_path',
        'style',
        'tags',
        'duration_seconds',
        'source',
        'usage_count',
        'is_active',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
    ];

    public const STYLES = [
        'metal' => 'Metal',
        'rock' => 'Rock',
        'electronic' => 'Electronic',
        'ambient' => 'Ambient',
        'synthwave' => 'Synthwave',
        'cinematic' => 'Cinematic',
        'lofi' => 'Lo-Fi',
        'other' => 'อื่นๆ',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByStyle($query, string $style)
    {
        return $query->where('style', $style);
    }

    public function scopeByTags($query, array $tags)
    {
        return $query->where(function ($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('tags', $tag);
            }
        });
    }

    public function scopeLeastUsed($query)
    {
        return $query->orderBy('usage_count', 'asc');
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function getDurationHumanAttribute(): string
    {
        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
