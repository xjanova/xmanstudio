<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetalXVideoProject extends Model
{
    protected $fillable = [
        'channel_id',
        'content_plan_id',
        'music_generation_id',
        'video_id',
        'title',
        'description',
        'tags',
        'privacy_status',
        'template',
        'template_settings',
        'eq_settings',
        'images',
        'video_clips',
        'media_mode',
        'status',
        'video_file_path',
        'video_duration_seconds',
        'scheduled_at',
        'published_at',
        'ai_metadata_generated',
        'error_message',
        'created_by',
        'auto_generated',
    ];

    protected $casts = [
        'tags' => 'array',
        'template_settings' => 'array',
        'images' => 'array',
        'video_clips' => 'array',
        'eq_settings' => 'array',
        'ai_metadata_generated' => 'boolean',
        'auto_generated' => 'boolean',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'video_duration_seconds' => 'integer',
    ];

    public const STATUSES = [
        'draft' => 'แบบร่าง',
        'generating_music' => 'กำลังสร้างเพลง',
        'music_ready' => 'เพลงพร้อม',
        'generating_videos' => 'กำลังสร้างคลิปวิดีโอ',
        'rendering' => 'กำลังเรนเดอร์',
        'rendered' => 'เรนเดอร์เสร็จ',
        'uploading' => 'กำลังอัปโหลด',
        'uploaded' => 'อัปโหลดแล้ว',
        'published' => 'เผยแพร่แล้ว',
        'failed' => 'ล้มเหลว',
    ];

    public const TEMPLATES = [
        'visualizer' => 'Visualizer + ภาพสไลด์',
        'slideshow' => 'Slideshow',
        'ken_burns' => 'Ken Burns Effect',
        'zoom_pan' => 'Zoom & Pan',
    ];

    public const MEDIA_MODES = [
        'images' => 'ภาพนิ่ง',
        'video_clips' => 'คลิปวิดีโอ AI (Freepik)',
        'mixed' => 'ผสม (ภาพ + คลิป)',
    ];

    public const EQ_STYLES = [
        'showcqt' => 'Frequency Bars (CQT)',
        'showwaves' => 'Waveform',
        'showfreqs' => 'Spectrum Bars',
        'bars' => 'Custom Bars',
    ];

    // Relationships
    public function channel(): BelongsTo
    {
        return $this->belongsTo(MetalXChannel::class, 'channel_id');
    }

    public function contentPlan(): BelongsTo
    {
        return $this->belongsTo(MetalXContentPlan::class, 'content_plan_id');
    }

    public function musicGeneration(): BelongsTo
    {
        return $this->belongsTo(MetalXMusicGeneration::class, 'music_generation_id');
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(MetalXVideo::class, 'video_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeReady($query)
    {
        return $query->whereIn('status', ['music_ready', 'rendered']);
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at')->where('scheduled_at', '>', now());
    }

    public function scopeReadyToPublish($query)
    {
        return $query->where('status', 'rendered')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now());
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'generating_music' => 'yellow',
            'music_ready' => 'blue',
            'generating_videos' => 'orange',
            'rendering' => 'yellow',
            'rendered' => 'indigo',
            'uploading' => 'yellow',
            'uploaded' => 'green',
            'published' => 'green',
            'failed' => 'red',
            default => 'gray',
        };
    }

    public function getTemplateLabelAttribute(): string
    {
        return self::TEMPLATES[$this->template] ?? $this->template;
    }

    public function getTemplateSetting(string $key, $default = null)
    {
        return data_get($this->template_settings, $key, $default);
    }

    public function hasVideoClips(): bool
    {
        return ! empty($this->video_clips);
    }

    public function isEqEnabled(): bool
    {
        return (bool) data_get($this->eq_settings, 'enabled', false);
    }

    /**
     * Get combined list of images and video clips with type info.
     */
    public function getMediaItems(): array
    {
        $items = [];

        foreach ($this->images ?? [] as $image) {
            $items[] = [
                'type' => 'image',
                'path' => $image,
            ];
        }

        foreach ($this->video_clips ?? [] as $clip) {
            $items[] = [
                'type' => 'video_clip',
                'path' => $clip,
            ];
        }

        return $items;
    }
}
