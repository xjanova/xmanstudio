<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AutoTradeX Device Model
 *
 * เก็บข้อมูล device ที่ลงทะเบียนจาก AutoTradeX app
 * ใช้สำหรับ:
 * - ติดตาม device ที่รอ activate
 * - ป้องกัน trial abuse
 * - Lock license กับ device
 */
class AutoTradeXDevice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'autotradex_devices';

    // Status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_TRIAL = 'trial';

    public const STATUS_LICENSED = 'licensed';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_DEMO = 'demo'; // Trial expired - limited functionality

    protected $fillable = [
        'machine_id',
        'machine_name',
        'os_version',
        'app_version',
        'hardware_hash',
        'first_ip',
        'last_ip',
        'status',
        'license_id',
        'trial_attempts',
        'first_trial_at',
        'trial_expires_at',
        'is_suspicious',
        'abuse_reason',
        'related_devices',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'first_trial_at' => 'datetime',
        'trial_expires_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'is_suspicious' => 'boolean',
        'related_devices' => 'array',
    ];

    /**
     * Relationship: License
     */
    public function license(): BelongsTo
    {
        return $this->belongsTo(LicenseKey::class, 'license_id');
    }

    /**
     * Check if device can start trial
     */
    public function canStartTrial(): bool
    {
        // Blocked devices cannot start trial
        if ($this->status === self::STATUS_BLOCKED) {
            return false;
        }

        // Already has active trial
        if ($this->status === self::STATUS_TRIAL && ! $this->isTrialExpired()) {
            return false;
        }

        // Already licensed
        if ($this->status === self::STATUS_LICENSED) {
            return false;
        }

        // Suspicious devices cannot start trial
        if ($this->is_suspicious) {
            return false;
        }

        // Too many trial attempts
        if ($this->trial_attempts >= 3) {
            return false;
        }

        return true;
    }

    /**
     * Check if trial is expired
     */
    public function isTrialExpired(): bool
    {
        if (! $this->trial_expires_at) {
            return true;
        }

        return $this->trial_expires_at->isPast();
    }

    /**
     * Get trial days remaining
     */
    public function trialDaysRemaining(): int
    {
        if (! $this->trial_expires_at || $this->isTrialExpired()) {
            return 0;
        }

        return (int) now()->diffInDays($this->trial_expires_at, false);
    }

    /**
     * Find related devices by IP
     */
    public function findRelatedByIp(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('id', '!=', $this->id)
            ->where(function ($query) {
                $query->where('first_ip', $this->first_ip)
                    ->orWhere('last_ip', $this->last_ip)
                    ->orWhere('first_ip', $this->last_ip)
                    ->orWhere('last_ip', $this->first_ip);
            })
            ->get();
    }

    /**
     * Find related devices by hardware hash
     */
    public function findRelatedByHardware(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->hardware_hash) {
            return collect();
        }

        return self::where('id', '!=', $this->id)
            ->where('hardware_hash', $this->hardware_hash)
            ->get();
    }

    /**
     * Check for trial abuse patterns
     *
     * @return array ['is_abuse' => bool, 'reasons' => array]
     */
    public function checkTrialAbuse(): array
    {
        $reasons = [];

        // Check 1: Same hardware hash with expired trial
        $hardwareRelated = $this->findRelatedByHardware();
        $expiredTrials = $hardwareRelated->where('status', self::STATUS_EXPIRED)->count();
        if ($expiredTrials > 0) {
            $reasons[] = "Same hardware found with {$expiredTrials} expired trial(s)";
        }

        // Check 2: Same IP with multiple trials
        $ipRelated = $this->findRelatedByIp();
        $ipTrials = $ipRelated->whereIn('status', [self::STATUS_TRIAL, self::STATUS_EXPIRED])->count();
        if ($ipTrials >= 2) {
            $reasons[] = "Same IP found with {$ipTrials} trial device(s)";
        }

        // Check 3: Too many trial attempts
        if ($this->trial_attempts >= 2) {
            $reasons[] = "Device has {$this->trial_attempts} trial attempts";
        }

        // Check 4: Trial started and expired recently (within 14 days), trying again
        if ($this->first_trial_at && $this->trial_expires_at) {
            $daysSinceExpiry = $this->trial_expires_at->diffInDays(now());
            if ($this->isTrialExpired() && $daysSinceExpiry < 14) {
                $reasons[] = 'Trial expired recently, possible reset attempt';
            }
        }

        return [
            'is_abuse' => count($reasons) > 0,
            'reasons' => $reasons,
        ];
    }

    /**
     * Mark device as suspicious
     */
    public function markSuspicious(string $reason): void
    {
        $this->update([
            'is_suspicious' => true,
            'abuse_reason' => $reason,
        ]);
    }

    /**
     * Block device
     */
    public function block(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_BLOCKED,
            'is_suspicious' => true,
            'abuse_reason' => $reason,
        ]);
    }

    /**
     * Start trial for device
     */
    public function startTrial(int $days = 7): bool
    {
        if (! $this->canStartTrial()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_TRIAL,
            'trial_attempts' => $this->trial_attempts + 1,
            'first_trial_at' => $this->first_trial_at ?? now(),
            'trial_expires_at' => now()->addDays($days),
        ]);

        // Update related devices list
        $this->updateRelatedDevices();

        return true;
    }

    /**
     * Update related devices JSON
     */
    public function updateRelatedDevices(): void
    {
        $related = [];

        // By IP
        $ipRelated = $this->findRelatedByIp();
        foreach ($ipRelated as $device) {
            $related[$device->id] = [
                'type' => 'ip',
                'status' => $device->status,
            ];
        }

        // By hardware
        $hwRelated = $this->findRelatedByHardware();
        foreach ($hwRelated as $device) {
            if (isset($related[$device->id])) {
                $related[$device->id]['type'] = 'both';
            } else {
                $related[$device->id] = [
                    'type' => 'hardware',
                    'status' => $device->status,
                ];
            }
        }

        if (count($related) > 0) {
            $this->update(['related_devices' => $related]);
        }
    }

    /**
     * Scope: Pending devices
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Active trials
     */
    public function scopeActiveTrial($query)
    {
        return $query->where('status', self::STATUS_TRIAL)
            ->where('trial_expires_at', '>', now());
    }

    /**
     * Scope: Suspicious devices
     */
    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious', true);
    }

    /**
     * Scope: Demo mode devices
     */
    public function scopeDemoMode($query)
    {
        return $query->where('status', self::STATUS_DEMO);
    }

    /**
     * Switch device to demo mode (trial expired)
     */
    public function switchToDemoMode(): void
    {
        $this->update([
            'status' => self::STATUS_DEMO,
        ]);
    }

    /**
     * Check if device is in demo mode
     */
    public function isDemoMode(): bool
    {
        return $this->status === self::STATUS_DEMO ||
               ($this->status === self::STATUS_EXPIRED) ||
               ($this->status === self::STATUS_TRIAL && $this->isTrialExpired());
    }

    /**
     * Get demo mode config for this device
     */
    public function getDemoModeConfig(): array
    {
        return [
            'can_view_opportunities' => true,
            'can_execute_trades' => false,
            'can_use_auto_trading' => false,
            'max_exchanges' => 2,
            'reminder_interval_minutes' => 15,
            'demo_message' => 'คุณกำลังใช้งาน Demo Mode - ไม่สามารถเทรดจริงได้ กรุณา Activate License เพื่อใช้งานเต็มรูปแบบ',
        ];
    }
}
