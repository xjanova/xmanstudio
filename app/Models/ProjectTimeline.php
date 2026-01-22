<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTimeline extends Model
{
    use HasFactory;

    protected $table = 'project_timeline';

    protected $fillable = [
        'project_order_id',
        'title',
        'description',
        'event_date',
        'type',
        'is_completed',
    ];

    protected $casts = [
        'event_date' => 'date',
        'is_completed' => 'boolean',
    ];

    /**
     * Type labels in Thai
     */
    public const TYPE_LABELS = [
        'start' => 'เริ่มโครงการ',
        'milestone' => 'ไมล์สโตน',
        'deadline' => 'กำหนดส่ง',
        'delivery' => 'ส่งมอบ',
        'meeting' => 'ประชุม',
        'payment' => 'ชำระเงิน',
        'end' => 'จบโครงการ',
    ];

    /**
     * Type colors
     */
    public const TYPE_COLORS = [
        'start' => 'green',
        'milestone' => 'blue',
        'deadline' => 'red',
        'delivery' => 'purple',
        'meeting' => 'yellow',
        'payment' => 'emerald',
        'end' => 'gray',
    ];

    /**
     * Type icons
     */
    public const TYPE_ICONS = [
        'start' => '🚀',
        'milestone' => '🎯',
        'deadline' => '⏰',
        'delivery' => '📦',
        'meeting' => '📅',
        'payment' => '💰',
        'end' => '🏁',
    ];

    /**
     * Relationships
     */
    public function projectOrder(): BelongsTo
    {
        return $this->belongsTo(ProjectOrder::class);
    }

    /**
     * Accessors
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function getTypeColorAttribute(): string
    {
        return self::TYPE_COLORS[$this->type] ?? 'gray';
    }

    public function getTypeIconAttribute(): string
    {
        return self::TYPE_ICONS[$this->type] ?? '📌';
    }

    /**
     * Scopes
     */
    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now())
            ->where('is_completed', false)
            ->orderBy('event_date');
    }

    public function scopePast($query)
    {
        return $query->where('event_date', '<', now())
            ->orWhere('is_completed', true)
            ->orderByDesc('event_date');
    }

    /**
     * Methods
     */
    public function markAsCompleted(): void
    {
        $this->update(['is_completed' => true]);
    }

    public function isOverdue(): bool
    {
        return $this->event_date->isPast() && ! $this->is_completed;
    }
}
