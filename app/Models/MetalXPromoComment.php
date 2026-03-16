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
        'scheduled_at',
        'posted_at',
        'error_message',
    ];

    protected $casts = [
        'generated_by_ai' => 'boolean',
        'scheduled_at' => 'datetime',
        'posted_at' => 'datetime',
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
