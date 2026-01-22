<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MetalXPlaylist extends Model
{
    protected $fillable = [
        'youtube_id',
        'title',
        'title_th',
        'description',
        'description_th',
        'thumbnail_url',
        'video_count',
        'is_featured',
        'is_active',
        'is_synced',
        'order',
        'synced_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'is_synced' => 'boolean',
        'video_count' => 'integer',
        'synced_at' => 'datetime',
    ];

    /**
     * Get the videos in this playlist.
     */
    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(MetalXVideo::class, 'metal_x_playlist_video', 'playlist_id', 'video_id')
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    /**
     * Scope a query to only include active playlists.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include featured playlists.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to order by custom order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Get the YouTube playlist URL.
     */
    public function getYoutubeUrlAttribute(): ?string
    {
        if (! $this->youtube_id) {
            return null;
        }

        return "https://www.youtube.com/playlist?list={$this->youtube_id}";
    }

    /**
     * Update video count from relationship.
     */
    public function updateVideoCount(): void
    {
        $this->update(['video_count' => $this->videos()->count()]);
    }
}
