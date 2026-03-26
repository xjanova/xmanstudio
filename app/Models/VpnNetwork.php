<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VpnNetwork extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'password_hash',
        'owner_user_id',
        'owner_device_id',
        'max_members',
        'is_public',
        'virtual_subnet',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'max_members' => 'integer',
    ];

    // ==================== Relationships ====================

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(VpnNetworkMember::class, 'network_id');
    }

    public function relaySessions(): HasMany
    {
        return $this->hasMany(VpnRelaySession::class, 'network_id');
    }

    public function trafficLogs(): HasMany
    {
        return $this->hasMany(VpnTrafficLog::class, 'network_id');
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    // ==================== Methods ====================

    /**
     * Assign the next available virtual IP in the subnet.
     */
    public function assignNextVirtualIp(): string
    {
        // Parse subnet: e.g. "10.10.0.0/24"
        $parts = explode('/', $this->virtual_subnet);
        $baseIp = $parts[0];
        $prefix = (int) ($parts[1] ?? 24);

        $baseOctets = explode('.', $baseIp);
        $baseInt = ip2long($baseIp);

        // Number of usable hosts: 2^(32 - prefix) - 2 (exclude network & broadcast)
        $hostBits = 32 - $prefix;
        $maxHosts = pow(2, $hostBits) - 2;

        // Get all used IPs in this network
        $usedIps = $this->members()->pluck('virtual_ip')->toArray();

        // Start from .1 (gateway is typically .1, so start from .2)
        for ($i = 2; $i <= $maxHosts + 1; $i++) {
            $candidateIp = long2ip($baseInt + $i);
            if (! in_array($candidateIp, $usedIps)) {
                return $candidateIp;
            }
        }

        // Fallback: return .1 if everything is taken (shouldn't happen with max_members)
        return long2ip($baseInt + 1);
    }

    /**
     * Check if the network is full.
     */
    public function isFull(): bool
    {
        return $this->getMemberCount() >= $this->max_members;
    }

    /**
     * Get current member count.
     */
    public function getMemberCount(): int
    {
        return $this->members()->count();
    }

    /**
     * Get online member count.
     */
    public function getOnlineCount(): int
    {
        return $this->members()->where('is_online', true)->count();
    }
}
