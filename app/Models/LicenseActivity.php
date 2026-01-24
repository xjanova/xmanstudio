<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseActivity extends Model
{
    // Action constants
    public const ACTION_CREATED = 'created';

    public const ACTION_ACTIVATED = 'activated';

    public const ACTION_DEACTIVATED = 'deactivated';

    public const ACTION_VALIDATED = 'validated';

    public const ACTION_EXPIRED = 'expired';

    public const ACTION_REVOKED = 'revoked';

    public const ACTION_REACTIVATED = 'reactivated';

    public const ACTION_EXTENDED = 'extended';

    public const ACTION_MACHINE_RESET = 'machine_reset';

    public const ACTION_FAILED_ACTIVATION = 'failed_activation';

    public const ACTION_SUSPICIOUS = 'suspicious_activity';

    // Actor types
    public const ACTOR_SYSTEM = 'system';

    public const ACTOR_ADMIN = 'admin';

    public const ACTOR_API = 'api';

    public const ACTOR_USER = 'user';

    protected $fillable = [
        'license_id',
        'action',
        'user_id',
        'actor_type',
        'machine_id',
        'ip_address',
        'user_agent',
        'country',
        'city',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the license that owns the activity.
     */
    public function license(): BelongsTo
    {
        return $this->belongsTo(LicenseKey::class, 'license_id');
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a license activity.
     */
    public static function log(
        int $licenseId,
        string $action,
        ?int $userId = null,
        string $actorType = self::ACTOR_SYSTEM,
        ?string $machineId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $metadata = null,
        ?string $notes = null
    ): self {
        return self::create([
            'license_id' => $licenseId,
            'action' => $action,
            'user_id' => $userId,
            'actor_type' => $actorType,
            'machine_id' => $machineId,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'metadata' => $metadata,
            'notes' => $notes,
        ]);
    }

    /**
     * Get action label in Thai.
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'สร้าง License',
            self::ACTION_ACTIVATED => 'Activate',
            self::ACTION_DEACTIVATED => 'Deactivate',
            self::ACTION_VALIDATED => 'Validate',
            self::ACTION_EXPIRED => 'หมดอายุ',
            self::ACTION_REVOKED => 'ยกเลิก',
            self::ACTION_REACTIVATED => 'เปิดใช้งานใหม่',
            self::ACTION_EXTENDED => 'ขยายเวลา',
            self::ACTION_MACHINE_RESET => 'รีเซ็ตเครื่อง',
            self::ACTION_FAILED_ACTIVATION => 'Activate ไม่สำเร็จ',
            self::ACTION_SUSPICIOUS => 'พบกิจกรรมน่าสงสัย',
            default => $this->action,
        };
    }

    /**
     * Get action icon.
     */
    public function getActionIconAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'M12 6v6m0 0v6m0-6h6m-6 0H6',
            self::ACTION_ACTIVATED => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            self::ACTION_DEACTIVATED => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
            self::ACTION_VALIDATED => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
            self::ACTION_EXPIRED => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            self::ACTION_REVOKED => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636',
            self::ACTION_REACTIVATED => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
            self::ACTION_EXTENDED => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            self::ACTION_MACHINE_RESET => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
            self::ACTION_FAILED_ACTIVATION => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            self::ACTION_SUSPICIOUS => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            default => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        };
    }

    /**
     * Get action color classes.
     */
    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'from-blue-400 to-indigo-600',
            self::ACTION_ACTIVATED => 'from-green-400 to-emerald-600',
            self::ACTION_DEACTIVATED => 'from-gray-400 to-gray-600',
            self::ACTION_VALIDATED => 'from-cyan-400 to-teal-600',
            self::ACTION_EXPIRED => 'from-amber-400 to-orange-600',
            self::ACTION_REVOKED => 'from-red-400 to-rose-600',
            self::ACTION_REACTIVATED => 'from-green-400 to-emerald-600',
            self::ACTION_EXTENDED => 'from-purple-400 to-violet-600',
            self::ACTION_MACHINE_RESET => 'from-orange-400 to-amber-600',
            self::ACTION_FAILED_ACTIVATION => 'from-red-400 to-rose-600',
            self::ACTION_SUSPICIOUS => 'from-red-500 to-pink-600',
            default => 'from-gray-400 to-gray-600',
        };
    }
}
