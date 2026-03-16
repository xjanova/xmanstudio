<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetalXAutomationLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'action_type',
        'video_id',
        'comment_id',
        'status',
        'details',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    public function video(): BelongsTo
    {
        return $this->belongsTo(MetalXVideo::class, 'video_id');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(MetalXComment::class, 'comment_id');
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForAction($query, string $type)
    {
        return $query->where('action_type', $type);
    }

    public static function log(string $action, string $status, array $options = []): self
    {
        return self::create([
            'action_type' => $action,
            'video_id' => $options['video_id'] ?? null,
            'comment_id' => $options['comment_id'] ?? null,
            'status' => $status,
            'details' => $options['details'] ?? null,
            'error_message' => $options['error_message'] ?? null,
            'created_at' => now(),
        ]);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'success' => 'สำเร็จ',
            'failed' => 'ล้มเหลว',
            'skipped' => 'ข้าม',
            default => $this->status,
        };
    }

    public function getActionLabelAttribute(): string
    {
        return MetalXAutomationSchedule::ACTION_TYPES[$this->action_type] ?? $this->action_type;
    }
}
