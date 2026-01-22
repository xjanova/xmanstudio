<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectProgress extends Model
{
    use HasFactory;

    protected $table = 'project_progress';

    protected $fillable = [
        'project_order_id',
        'project_feature_id',
        'created_by',
        'title',
        'description',
        'type',
        'attachments',
        'is_public',
        'notify_customer',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_public' => 'boolean',
        'notify_customer' => 'boolean',
    ];

    /**
     * Type labels in Thai
     */
    public const TYPE_LABELS = [
        'update' => 'อัพเดททั่วไป',
        'milestone' => 'ครบไมล์สโตน',
        'issue' => 'พบปัญหา',
        'delivery' => 'ส่งมอบงาน',
        'meeting' => 'นัดประชุม',
        'change_request' => 'ขอเปลี่ยนแปลง',
    ];

    /**
     * Type colors for UI
     */
    public const TYPE_COLORS = [
        'update' => 'blue',
        'milestone' => 'green',
        'issue' => 'red',
        'delivery' => 'purple',
        'meeting' => 'yellow',
        'change_request' => 'orange',
    ];

    /**
     * Type icons
     */
    public const TYPE_ICONS = [
        'update' => '📝',
        'milestone' => '🎯',
        'issue' => '⚠️',
        'delivery' => '📦',
        'meeting' => '📅',
        'change_request' => '🔄',
    ];

    /**
     * Relationships
     */
    public function projectOrder(): BelongsTo
    {
        return $this->belongsTo(ProjectOrder::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(ProjectFeature::class, 'project_feature_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
        return self::TYPE_ICONS[$this->type] ?? '📋';
    }

    /**
     * Scopes
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeForCustomer($query)
    {
        return $query->where('is_public', true)->orderByDesc('created_at');
    }
}
