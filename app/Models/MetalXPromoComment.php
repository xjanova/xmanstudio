<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetalXPromoComment extends Model
{
    protected $fillable = [
        'video_id',
        'comment_text',
        'youtube_comment_id',
        'generated_by_ai',
        'status',
        'is_pinned',
        'should_pin',
        'scheduled_at',
        'posted_at',
        'pinned_at',
        'error_message',
    ];

    protected $casts = [
        'generated_by_ai' => 'boolean',
        'is_pinned' => 'boolean',
        'should_pin' => 'boolean',
        'scheduled_at' => 'datetime',
        'posted_at' => 'datetime',
        'pinned_at' => 'datetime',
    ];

    public function video(): BelongsTo
    {
        return $this->belongsTo(MetalXVideo::class, 'video_id');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeReadyToPost($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeNeedsPinning($query)
    {
        return $query->where('status', 'posted')
            ->where('should_pin', true)
            ->where('is_pinned', false)
            ->whereNotNull('youtube_comment_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'แบบร่าง',
            'scheduled' => 'รอโพส',
            'posted' => 'โพสแล้ว',
            'failed' => 'ล้มเหลว',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'scheduled' => 'yellow',
            'posted' => 'green',
            'failed' => 'red',
            default => 'gray',
        };
    }
}
