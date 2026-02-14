<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BugReportAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'bug_report_id',
        'filename',
        'file_path',
        'file_type',
        'file_size',
        'description',
    ];

    /**
     * Get the bug report this attachment belongs to
     */
    public function bugReport(): BelongsTo
    {
        return $this->belongsTo(BugReport::class);
    }

    /**
     * Get the full URL to the file
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get human-readable file size
     */
    public function getFormattedSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Check if file is an image
     */
    public function isImage(): bool
    {
        return in_array($this->file_type, ['image', 'jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    /**
     * Delete the physical file
     */
    public function deleteFile(): bool
    {
        return Storage::delete($this->file_path);
    }
}
