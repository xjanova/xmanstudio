<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BtUserStats extends Model
{
    protected $fillable = [
        'machine_id',
        'display_name',
        'total_uploaded_bytes',
        'total_downloaded_bytes',
        'total_files_shared',
        'total_files_downloaded',
        'seed_time_seconds',
        'score',
        'rank_position',
    ];

    protected $casts = [
        'total_uploaded_bytes' => 'integer',
        'total_downloaded_bytes' => 'integer',
        'total_files_shared' => 'integer',
        'total_files_downloaded' => 'integer',
        'seed_time_seconds' => 'integer',
        'score' => 'integer',
        'rank_position' => 'integer',
    ];

    /**
     * Get upload/download ratio (seed ratio).
     */
    public function seedRatio(): float
    {
        if ($this->total_downloaded_bytes == 0) {
            return $this->total_uploaded_bytes > 0 ? PHP_FLOAT_MAX : 0.0;
        }

        return round($this->total_uploaded_bytes / $this->total_downloaded_bytes, 2);
    }

    /**
     * Calculate score based on activity.
     * Formula: files_shared*100 + seed_hours*10 + uploaded_gb*50 + files_downloaded*5
     */
    public function calculateScore(): int
    {
        $seedHours = $this->seed_time_seconds / 3600;
        $uploadedGb = $this->total_uploaded_bytes / (1024 * 1024 * 1024);

        return (int) (
            $this->total_files_shared * 100
            + $seedHours * 10
            + $uploadedGb * 50
            + $this->total_files_downloaded * 5
        );
    }

    /**
     * Get human-readable uploaded size.
     */
    public function getFormattedUpload(): string
    {
        return $this->formatBytes($this->total_uploaded_bytes);
    }

    /**
     * Get human-readable downloaded size.
     */
    public function getFormattedDownload(): string
    {
        return $this->formatBytes($this->total_downloaded_bytes);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes == 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);

        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
