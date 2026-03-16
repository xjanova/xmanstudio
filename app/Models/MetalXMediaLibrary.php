<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetalXMediaLibrary extends Model
{
    protected $table = 'metal_x_media_library';

    protected $fillable = [
        'type',
        'file_path',
        'filename',
        'tags',
        'source',
        'source_id',
        'file_size',
        'duration_seconds',
        'resolution',
        'usage_count',
        'is_active',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
    ];

    public const TYPES = [
        'image' => 'ภาพนิ่ง',
        'video_clip' => 'คลิปวิดีโอ',
    ];

    public const SOURCES = [
        'freepik' => 'Freepik',
        'custom' => 'อัปโหลดเอง',
        'ai_generated' => 'AI สร้าง',
    ];

    // Scopes
    public function scopeImages($query)
    {
        return $query->where('type', 'image');
    }

    public function scopeVideoClips($query)
    {
        return $query->where('type', 'video_clip');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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

    public function scopeForMediaMode($query, string $mode)
    {
        return match ($mode) {
            'images' => $query->images(),
            'video_clips' => $query->videoClips(),
            'mixed' => $query, // all types
            default => $query->images(),
        };
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
