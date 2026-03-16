<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetalXAutomationSchedule extends Model
{
    protected $fillable = [
        'video_id',
        'action_type',
        'is_enabled',
        'frequency_minutes',
        'max_actions_per_run',
        'last_run_at',
        'next_run_at',
        'run_count',
        'settings',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'frequency_minutes' => 'integer',
        'max_actions_per_run' => 'integer',
        'run_count' => 'integer',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'settings' => 'array',
    ];

    public const ACTION_TYPES = [
        'auto_reply' => 'ตอบคอมเม้นต์อัตโนมัติ',
        'auto_like' => 'กดไลค์อัตโนมัติ',
        'auto_moderate' => 'ตรวจสอบคอมเม้นต์',
        'promo_comment' => 'โพสเรียกยอด',
        'sync_comments' => 'ซิงค์คอมเม้นต์',
        'auto_generate' => 'สร้างวิดีโออัตโนมัติ',
    ];

    public const FREQUENCY_PRESETS = [
        15 => 'ทุก 15 นาที',
        30 => 'ทุก 30 นาที',
        60 => 'ทุกชั่วโมง',
        120 => 'ทุก 2 ชั่วโมง',
        360 => 'ทุก 6 ชั่วโมง',
        720 => 'ทุก 12 ชั่วโมง',
        1440 => 'วันละครั้ง',
    ];

    public function video(): BelongsTo
    {
        return $this->belongsTo(MetalXVideo::class, 'video_id');
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeDue($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('next_run_at')
                ->orWhere('next_run_at', '<=', now());
        });
    }

    public function scopeForAction($query, string $type)
    {
        return $query->where('action_type', $type);
    }

    public function isDue(): bool
    {
        return $this->next_run_at === null || $this->next_run_at->lte(now());
    }

    public function markRun(): void
    {
        $this->update([
            'last_run_at' => now(),
            'next_run_at' => now()->addMinutes($this->frequency_minutes),
        ]);
        $this->increment('run_count');
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTION_TYPES[$this->action_type] ?? $this->action_type;
    }

    public function getFrequencyLabelAttribute(): string
    {
        return self::FREQUENCY_PRESETS[$this->frequency_minutes]
            ?? "ทุก {$this->frequency_minutes} นาที";
    }

    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }
}
