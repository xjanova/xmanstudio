<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiprayAudioSample extends Model
{
    protected $table = 'aipray_audio_samples';

    protected $fillable = [
        'filename', 'original_name', 'file_path', 'chant_id', 'line_index',
        'category', 'label', 'transcript', 'duration', 'sample_rate',
        'format', 'file_size', 'status', 'device_info', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'duration' => 'float',
    ];

    public function scopeLabeled($query)
    {
        return $query->where('status', 'labeled');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function getDurationFormattedAttribute(): string
    {
        if (! $this->duration) {
            return '0:00';
        }
        $minutes = floor($this->duration / 60);
        $seconds = floor($this->duration % 60);

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
