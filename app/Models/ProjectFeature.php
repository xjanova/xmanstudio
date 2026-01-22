<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_order_id',
        'name',
        'description',
        'order',
        'status',
        'progress_percent',
        'due_date',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'date',
    ];

    /**
     * Status labels in Thai
     */
    public const STATUS_LABELS = [
        'pending' => 'รอดำเนินการ',
        'in_progress' => 'กำลังทำ',
        'completed' => 'เสร็จแล้ว',
        'cancelled' => 'ยกเลิก',
    ];

    /**
     * Status colors for UI
     */
    public const STATUS_COLORS = [
        'pending' => 'gray',
        'in_progress' => 'blue',
        'completed' => 'green',
        'cancelled' => 'red',
    ];

    /**
     * Status icons
     */
    public const STATUS_ICONS = [
        'pending' => '⏳',
        'in_progress' => '🔄',
        'completed' => '✅',
        'cancelled' => '❌',
    ];

    /**
     * Relationships
     */
    public function projectOrder(): BelongsTo
    {
        return $this->belongsTo(ProjectOrder::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(ProjectProgress::class);
    }

    /**
     * Accessors
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    public function getStatusIconAttribute(): string
    {
        return self::STATUS_ICONS[$this->status] ?? '❓';
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Methods
     */
    public function markAsInProgress(): void
    {
        $this->update([
            'status' => 'in_progress',
        ]);

        $this->projectOrder->updateProgress();
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'progress_percent' => 100,
            'completed_at' => now(),
        ]);

        $this->projectOrder->updateProgress();
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && $this->status !== 'completed';
    }
}
