<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_order_id',
        'user_id',
        'name',
        'role',
        'email',
        'phone',
        'is_lead',
    ];

    protected $casts = [
        'is_lead' => 'boolean',
    ];

    /**
     * Role labels in Thai
     */
    public const ROLE_LABELS = [
        'project_manager' => 'ผู้จัดการโครงการ',
        'developer' => 'นักพัฒนา',
        'designer' => 'นักออกแบบ',
        'tester' => 'ผู้ทดสอบ',
        'analyst' => 'นักวิเคราะห์',
        'support' => 'ฝ่ายสนับสนุน',
    ];

    /**
     * Role icons
     */
    public const ROLE_ICONS = [
        'project_manager' => '👔',
        'developer' => '💻',
        'designer' => '🎨',
        'tester' => '🔍',
        'analyst' => '📊',
        'support' => '🛠️',
    ];

    /**
     * Relationships
     */
    public function projectOrder(): BelongsTo
    {
        return $this->belongsTo(ProjectOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessors
     */
    public function getRoleLabelAttribute(): string
    {
        return self::ROLE_LABELS[$this->role] ?? $this->role;
    }

    public function getRoleIconAttribute(): string
    {
        return self::ROLE_ICONS[$this->role] ?? '👤';
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->user?->name ?? $this->name;
    }
}
