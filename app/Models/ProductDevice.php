<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product Device Model
 *
 * Generic model สำหรับเก็บข้อมูล device ที่ลงทะเบียนจากทุกผลิตภัณฑ์
 * ใช้สำหรับ:
 * - ติดตาม device ที่รอ activate
 * - ป้องกัน trial abuse
 * - Lock license กับ device
 */
class ProductDevice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_devices';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_TRIAL = 'trial';
    public const STATUS_LICENSED = 'licensed';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_DEMO = 'demo';

    protected $fillable = [
        'product_id',
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
        // Early bird discount tracking
        'early_bird_used',
        'early_bird_used_at',
        'early_bird_order_id',
    ];

    protected $casts = [
        'first_trial_at' => 'datetime',
        'trial_expires_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'is_suspicious' => 'boolean',
        'related_devices' => 'array',
        'early_bird_used' => 'boolean',
        'early_bird_used_at' => 'datetime',
    ];

    /**
     * Relationship: Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

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
        if ($this->status === self::STATUS_BLOCKED) {
            return false;
        }

        if ($this->status === self::STATUS_TRIAL && !$this->isTrialExpired()) {
            return false;
        }

        if ($this->status === self::STATUS_LICENSED) {
            return false;
        }

        if ($this->is_suspicious) {
            return false;
        }

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
        if (!$this->trial_expires_at) {
            return true;
        }

        return $this->trial_expires_at->isPast();
    }

    /**
     * Get trial days remaining
     */
    public function trialDaysRemaining(): int
    {
        if (!$this->trial_expires_at || $this->isTrialExpired()) {
            return 0;
        }

        return (int) now()->diffInDays($this->trial_expires_at, false);
    }

    /**
     * Find related devices by IP (within same product)
     */
    public function findRelatedByIp(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('id', '!=', $this->id)
            ->where('product_id', $this->product_id)
            ->where(function ($query) {
                $query->where('first_ip', $this->first_ip)
                    ->orWhere('last_ip', $this->last_ip)
                    ->orWhere('first_ip', $this->last_ip)
                    ->orWhere('last_ip', $this->first_ip);
            })
            ->get();
    }

    /**
     * Find related devices by hardware hash (within same product)
     */
    public function findRelatedByHardware(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->hardware_hash) {
            return collect();
        }

        return self::where('id', '!=', $this->id)
            ->where('product_id', $this->product_id)
            ->where('hardware_hash', $this->hardware_hash)
            ->get();
    }

    /**
     * Check for trial abuse patterns
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

        // Check 4: Trial expired recently
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
     * Unblock device
     */
    public function unblock(): void
    {
        $this->update([
            'status' => self::STATUS_PENDING,
            'is_suspicious' => false,
            'abuse_reason' => null,
        ]);
    }

    /**
     * Start trial for device
     */
    public function startTrial(int $days = 7): bool
    {
        if (!$this->canStartTrial()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_TRIAL,
            'trial_attempts' => $this->trial_attempts + 1,
            'first_trial_at' => $this->first_trial_at ?? now(),
            'trial_expires_at' => now()->addDays($days),
        ]);

        $this->updateRelatedDevices();

        return true;
    }

    /**
     * Update related devices JSON
     */
    public function updateRelatedDevices(): void
    {
        $related = [];

        $ipRelated = $this->findRelatedByIp();
        foreach ($ipRelated as $device) {
            $related[$device->id] = [
                'type' => 'ip',
                'status' => $device->status,
            ];
        }

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
     * Switch device to demo mode
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
     * Check if device is eligible for early bird discount
     */
    public function isEligibleForEarlyBird(): bool
    {
        if ($this->early_bird_used) {
            return false;
        }

        if ($this->status === self::STATUS_LICENSED) {
            return false;
        }

        return $this->status === self::STATUS_TRIAL && !$this->isTrialExpired();
    }

    /**
     * Mark early bird discount as used
     */
    public function useEarlyBirdDiscount(string $orderId): void
    {
        $this->update([
            'early_bird_used' => true,
            'early_bird_used_at' => now(),
            'early_bird_order_id' => $orderId,
        ]);
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_LICENSED => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            self::STATUS_TRIAL => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            self::STATUS_BLOCKED => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            self::STATUS_EXPIRED => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
            self::STATUS_DEMO => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_LICENSED => 'Licensed',
            self::STATUS_TRIAL => 'Trial',
            self::STATUS_BLOCKED => 'Blocked',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_DEMO => 'Demo',
            self::STATUS_PENDING => 'Pending',
            default => ucfirst($this->status),
        };
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeActiveTrial($query)
    {
        return $query->where('status', self::STATUS_TRIAL)
            ->where('trial_expires_at', '>', now());
    }

    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious', true);
    }

    public function scopeBlocked($query)
    {
        return $query->where('status', self::STATUS_BLOCKED);
    }

    public function scopeDemoMode($query)
    {
        return $query->where('status', self::STATUS_DEMO);
    }

    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByMachineId($query, string $machineId)
    {
        return $query->where('machine_id', $machineId);
    }
}
