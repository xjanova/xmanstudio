<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetalXContentPlan extends Model
{
    protected $fillable = [
        'channel_id',
        'name',
        'topic_prompt',
        'music_prompt',
        'music_style',
        'music_duration',
        'template',
        'template_settings',
        'eq_settings',
        'media_mode',
        'privacy_status',
        'media_pool_tags',
        'media_count',
        'schedule_frequency_hours',
        'preferred_publish_hour',
        'preferred_publish_days',
        'is_enabled',
        'max_queue_size',
        'last_generated_at',
        'next_generation_at',
        'total_generated',
    ];

    protected $casts = [
        'template_settings' => 'array',
        'eq_settings' => 'array',
        'media_pool_tags' => 'array',
        'preferred_publish_days' => 'array',
        'is_enabled' => 'boolean',
        'schedule_frequency_hours' => 'integer',
        'preferred_publish_hour' => 'integer',
        'max_queue_size' => 'integer',
        'media_count' => 'integer',
        'music_duration' => 'integer',
        'total_generated' => 'integer',
        'last_generated_at' => 'datetime',
        'next_generation_at' => 'datetime',
    ];

    public const FREQUENCY_PRESETS = [
        6 => 'ทุก 6 ชั่วโมง',
        12 => 'ทุก 12 ชั่วโมง',
        24 => 'วันละครั้ง',
        48 => 'ทุก 2 วัน',
        72 => 'ทุก 3 วัน',
        168 => 'สัปดาห์ละครั้ง',
    ];

    public const DAYS_OF_WEEK = [
        1 => 'จันทร์',
        2 => 'อังคาร',
        3 => 'พุธ',
        4 => 'พฤหัสบดี',
        5 => 'ศุกร์',
        6 => 'เสาร์',
        0 => 'อาทิตย์',
    ];

    // Relationships
    public function channel(): BelongsTo
    {
        return $this->belongsTo(MetalXChannel::class, 'channel_id');
    }

    public function videoProjects(): HasMany
    {
        return $this->hasMany(MetalXVideoProject::class, 'content_plan_id');
    }

    // Scopes
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeDue($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('next_generation_at')
                ->orWhere('next_generation_at', '<=', now());
        });
    }

    // Helpers
    public function isDue(): bool
    {
        return $this->next_generation_at === null || $this->next_generation_at->lte(now());
    }

    public function getActiveProjectCount(): int
    {
        return $this->videoProjects()
            ->whereNotIn('status', ['published', 'failed'])
            ->count();
    }

    public function canGenerate(): bool
    {
        return $this->is_enabled
            && $this->isDue()
            && $this->getActiveProjectCount() < $this->max_queue_size;
    }

    public function calculateNextGeneration(): void
    {
        $next = now()->addHours($this->schedule_frequency_hours);

        // If preferred days are set, find the next matching day
        $preferredDays = $this->preferred_publish_days;
        if (! empty($preferredDays)) {
            $maxAttempts = 14;
            while (! in_array($next->dayOfWeek, $preferredDays) && $maxAttempts > 0) {
                $next->addDay();
                $maxAttempts--;
            }
        }

        // Set preferred hour
        if ($this->preferred_publish_hour !== null) {
            $next->setTime($this->preferred_publish_hour, 0, 0);
            if ($next->lte(now())) {
                $next->addDay();
            }
        }

        $this->update([
            'next_generation_at' => $next,
            'last_generated_at' => now(),
        ]);
        $this->increment('total_generated');
    }

    public function calculatePublishTime(): \Carbon\Carbon
    {
        $publishTime = now()->addHours(2); // Give time for rendering

        // Adjust to preferred hour
        if ($this->preferred_publish_hour !== null) {
            $publishTime->setTime($this->preferred_publish_hour, 0, 0);
            if ($publishTime->lte(now()->addHour())) {
                $publishTime->addDay();
            }
        }

        // Adjust to preferred day
        $preferredDays = $this->preferred_publish_days;
        if (! empty($preferredDays)) {
            $maxAttempts = 14;
            while (! in_array($publishTime->dayOfWeek, $preferredDays) && $maxAttempts > 0) {
                $publishTime->addDay();
                $maxAttempts--;
            }
        }

        return $publishTime;
    }

    public function getFrequencyLabelAttribute(): string
    {
        return self::FREQUENCY_PRESETS[$this->schedule_frequency_hours]
            ?? "ทุก {$this->schedule_frequency_hours} ชั่วโมง";
    }

    public function getPreferredDaysLabelAttribute(): string
    {
        $days = $this->preferred_publish_days;
        if (empty($days)) {
            return 'ทุกวัน';
        }

        return collect($days)->map(fn ($d) => self::DAYS_OF_WEEK[$d] ?? $d)->implode(', ');
    }
}
