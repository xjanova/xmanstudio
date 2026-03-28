<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LicenseKey extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'order_id',
        'user_id',
        'license_key',
        'status',
        'license_type',
        'activated_at',
        'expires_at',
        'last_validated_at',
        'device_id',
        'machine_id',
        'machine_fingerprint',
        'drm_id',
        'android_id',
        'max_activations',
        'activations',
        'metadata',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_validated_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'machine_fingerprint',
    ];

    const TYPE_DEMO = 'demo';

    const TYPE_DAILY = 'daily';

    const TYPE_WEEKLY = 'weekly';

    const TYPE_MONTHLY = 'monthly';

    const TYPE_YEARLY = 'yearly';

    const TYPE_LIFETIME = 'lifetime';

    const TYPE_FREE = 'free';

    const TYPE_PRODUCT = 'product';

    const STATUS_ACTIVE = 'active';

    const STATUS_EXPIRED = 'expired';

    const STATUS_REVOKED = 'revoked';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activations(): HasMany
    {
        return $this->hasMany(LicenseActivity::class, 'license_id')
            ->whereIn('action', [
                LicenseActivity::ACTION_ACTIVATED,
                LicenseActivity::ACTION_DEACTIVATED,
                LicenseActivity::ACTION_REACTIVATED,
                LicenseActivity::ACTION_MACHINE_RESET,
            ])
            ->latest();
    }

    public function getActivationCountAttribute(): int
    {
        return (int) $this->attributes['activations'];
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now())
                    ->orWhere('license_type', self::TYPE_LIFETIME);
            });
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('license_key', strtoupper(trim($key)));
    }

    public function scopeByMachine($query, string $machineId)
    {
        return $query->where('machine_id', $machineId);
    }

    public function isExpired(): bool
    {
        // Lifetime and free licenses never expire
        if (in_array($this->license_type, [self::TYPE_LIFETIME, self::TYPE_FREE])) {
            return false;
        }
        if ($this->expires_at === null) {
            return true;
        }

        return $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->status === self::STATUS_ACTIVE && ! $this->isExpired();
    }

    public function daysRemaining(): int
    {
        if (in_array($this->license_type, [self::TYPE_LIFETIME, self::TYPE_FREE])) {
            return 999999;
        }
        if ($this->expires_at === null) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->expires_at, false));
    }

    public static function generateKey(): string
    {
        $segments = [];
        for ($i = 0; $i < 4; $i++) {
            $segments[] = strtoupper(Str::random(4));
        }

        return implode('-', $segments);
    }

    public static function generateDemoKey(): string
    {
        return 'DEMO-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
    }

    public function activateOnMachine(string $machineId, string $fingerprint): bool
    {
        if ($this->machine_id && $this->machine_id !== $machineId) {
            if ($this->activations >= $this->max_activations) {
                return false;
            }
        }

        // Only calculate expires_at if not already set (preserve purchase-time expiry)
        $expiresAt = $this->expires_at ?? match ($this->license_type) {
            self::TYPE_DAILY => now()->addDay(),
            self::TYPE_WEEKLY => now()->addDays(7),
            self::TYPE_MONTHLY => now()->addDays(30),
            self::TYPE_YEARLY => now()->addYear(),
            self::TYPE_LIFETIME, self::TYPE_FREE => null,
            self::TYPE_DEMO => now()->addDays(3),
            default => null,
        };

        $this->update([
            'machine_id' => $machineId,
            'machine_fingerprint' => bcrypt($fingerprint),
            'activated_at' => now(),
            'expires_at' => $expiresAt,
            'last_validated_at' => now(),
            'activations' => $this->activations + 1,
        ]);

        return true;
    }
}
