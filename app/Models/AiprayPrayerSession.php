<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiprayPrayerSession extends Model
{
    protected $table = 'aipray_prayer_sessions';

    protected $fillable = [
        'session_uuid', 'device_id', 'chant_id', 'chant_title',
        'start_time', 'end_time', 'rounds_completed', 'total_lines',
        'lines_reached', 'used_voice_tracking', 'ip_address', 'user_agent', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'used_voice_tracking' => 'boolean',
    ];

    public function getDurationAttribute(): ?int
    {
        if (! $this->start_time || ! $this->end_time) {
            return null;
        }

        return $this->start_time->diffInSeconds($this->end_time);
    }

    public function getDurationFormattedAttribute(): string
    {
        $seconds = $this->duration;
        if (! $seconds) {
            return '0:00';
        }

        return sprintf('%d:%02d', floor($seconds / 60), $seconds % 60);
    }
}
