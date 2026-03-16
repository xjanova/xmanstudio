<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MetalXMusicGeneration extends Model
{
    protected $fillable = [
        'prompt',
        'style',
        'duration_seconds',
        'suno_task_id',
        'status',
        'audio_url',
        'audio_path',
        'title',
        'metadata',
        'error_message',
    ];

    protected $casts = [
        'metadata' => 'array',
        'duration_seconds' => 'integer',
    ];

    public function videoProject(): HasOne
    {
        return $this->hasOne(MetalXVideoProject::class, 'music_generation_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'รอสร้าง',
            'processing' => 'กำลังสร้าง',
            'completed' => 'สร้างเสร็จ',
            'failed' => 'ล้มเหลว',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'gray',
            'processing' => 'yellow',
            'completed' => 'green',
            'failed' => 'red',
            default => 'gray',
        };
    }
}
