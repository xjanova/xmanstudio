<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetalXVideo extends Model
{
    protected $fillable = [
        'youtube_id',
        'title',
        'title_th',
        'description',
        'description_th',
        'thumbnail_url',
        'thumbnail_medium_url',
        'thumbnail_high_url',
        'channel_id',
        'channel_title',
        'view_count',
        'like_count',
        'comment_count',
        'duration',
        'duration_seconds',
        'video_type',
        'tags',
        'category',
        'privacy_status',
        'is_featured',
        'is_active',
        'order',
        'published_at',
        'synced_at',
        'ai_title_th',
        'ai_description_th',
        'ai_tags',
        'ai_category',
        'ai_confidence_score',
        'ai_generated',
        'ai_approved',
        'ai_generated_at',
        'ai_approved_at',
        'ai_approved_by',
        'metal_x_channel_id',
        'source',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'comment_count' => 'integer',
        'duration_seconds' => 'integer',
        'published_at' => 'datetime',
        'synced_at' => 'datetime',
        'ai_tags' => 'array',
        'ai_confidence_score' => 'decimal:2',
        'ai_generated' => 'boolean',
        'ai_approved' => 'boolean',
        'ai_generated_at' => 'datetime',
        'ai_approved_at' => 'datetime',
    ];

    /**
     * Get the channel this video belongs to.
     */
    public function metalXChannel(): BelongsTo
    {
        return $this->belongsTo(MetalXChannel::class, 'metal_x_channel_id');
    }

    /**
     * Get the playlists that contain this video.
     */
    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(MetalXPlaylist::class, 'metal_x_playlist_video', 'video_id', 'playlist_id')
            ->withPivot('position')
            ->withTimestamps();
    }

    /**
     * Get the user who approved the AI metadata.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ai_approved_by');
    }

    /**
     * Get the comments for the video.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(MetalXComment::class, 'video_id');
    }

    /**
     * Scope a query to only include active videos.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include featured videos.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to order by published date.
     */
    public function scopeLatest($query)
    {
        return $query->orderByDesc('published_at');
    }

    /**
     * Scope a query to order by custom order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Scope a query to filter by video type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('video_type', $type);
    }

    /**
     * Scope for shorts only.
     */
    public function scopeShorts($query)
    {
        return $query->where('video_type', 'short');
    }

    /**
     * Scope for live streams only.
     */
    public function scopeLive($query)
    {
        return $query->where('video_type', 'live');
    }

    /**
     * Scope a query to order by view count.
     */
    public function scopePopular($query)
    {
        return $query->orderByDesc('view_count');
    }

    /**
     * Classify video type based on duration and live broadcast content.
     */
    public static function classifyVideoType(int $durationSeconds, ?string $liveBroadcastContent = null): string
    {
        if ($liveBroadcastContent === 'live' || $liveBroadcastContent === 'upcoming') {
            return 'live';
        }

        if ($durationSeconds > 0 && $durationSeconds <= 60) {
            return 'short';
        }

        return 'standard';
    }

    /**
     * Get Thai label for video type.
     */
    public function getVideoTypeLabelAttribute(): string
    {
        return match ($this->video_type) {
            'short' => 'คลิปสั้น',
            'live' => 'ถ่ายทอดสด',
            default => 'วิดีโอ',
        };
    }

    /**
     * Get the YouTube watch URL.
     */
    public function getYoutubeUrlAttribute(): string
    {
        return "https://www.youtube.com/watch?v={$this->youtube_id}";
    }

    /**
     * Get the YouTube embed URL.
     */
    public function getEmbedUrlAttribute(): string
    {
        return "https://www.youtube.com/embed/{$this->youtube_id}";
    }

    /**
     * Get formatted view count.
     */
    public function getFormattedViewCountAttribute(): string
    {
        return $this->formatNumber($this->view_count);
    }

    /**
     * Get formatted like count.
     */
    public function getFormattedLikeCountAttribute(): string
    {
        return $this->formatNumber($this->like_count);
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDurationAttribute(): string
    {
        if (! $this->duration_seconds) {
            return $this->duration ?? '0:00';
        }

        $hours = floor($this->duration_seconds / 3600);
        $minutes = floor(($this->duration_seconds % 3600) / 60);
        $seconds = $this->duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Format large numbers to readable format.
     */
    protected function formatNumber(int $number): string
    {
        if ($number >= 1000000000) {
            return number_format($number / 1000000000, 1) . 'B';
        }
        if ($number >= 1000000) {
            return number_format($number / 1000000, 1) . 'M';
        }
        if ($number >= 1000) {
            return number_format($number / 1000, 1) . 'K';
        }

        return number_format($number);
    }

    /**
     * Get the best available thumbnail.
     */
    public function getBestThumbnailAttribute(): ?string
    {
        return $this->thumbnail_high_url ?? $this->thumbnail_medium_url ?? $this->thumbnail_url;
    }
}
