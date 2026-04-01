<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WireguardServer extends Model
{
    protected $fillable = [
        'name',
        'country_code',
        'country_name',
        'endpoint',
        'public_key',
        'private_key',
        'address',
        'dns',
        'listen_port',
        'max_clients',
        'is_active',
        'is_healthy',
        'last_health_check_at',
    ];

    protected $casts = [
        'private_key' => 'encrypted',
        'listen_port' => 'integer',
        'max_clients' => 'integer',
        'is_active' => 'boolean',
        'is_healthy' => 'boolean',
        'last_health_check_at' => 'datetime',
    ];

    /**
     * Hide private_key from serialization by default.
     */
    protected $hidden = [
        'private_key',
    ];

    // ─── Relationships ───

    public function clients(): HasMany
    {
        return $this->hasMany(WireguardClient::class, 'server_id');
    }

    // ─── Scopes ───

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHealthy($query)
    {
        return $query->where('is_healthy', true);
    }

    // ─── Methods ───

    /**
     * Get the next available IP address in the server's subnet.
     * Server address is e.g. "10.20.0.1/24" — clients get 10.20.0.2, 10.20.0.3, etc.
     */
    public function getNextAvailableIp(): ?string
    {
        $parts = explode('/', $this->address);
        $serverIp = $parts[0]; // e.g. 10.20.0.1
        $prefix = (int) ($parts[1] ?? 24);

        // Calculate network range
        $ipLong = ip2long($serverIp);
        $mask = ~((1 << (32 - $prefix)) - 1) & 0xFFFFFFFF;
        $networkLong = $ipLong & $mask;
        $broadcastLong = $networkLong | (~$mask & 0xFFFFFFFF);

        // Get all assigned IPs for this server
        $assignedIps = $this->clients()->pluck('assigned_ip')->map(fn ($ip) => ip2long($ip))->toArray();

        // Start from network + 2 (skip network address and server address)
        $serverIpLong = ip2long($serverIp);
        for ($candidate = $networkLong + 2; $candidate < $broadcastLong; $candidate++) {
            if ($candidate === $serverIpLong) {
                continue;
            }
            if (! in_array($candidate, $assignedIps)) {
                return long2ip($candidate);
            }
        }

        return null; // No available IPs
    }

    /**
     * Get current load as a percentage (0-100).
     */
    public function getCurrentLoad(): float
    {
        if ($this->max_clients <= 0) {
            return 100.0;
        }

        $clientCount = $this->clients()->count();

        return round(($clientCount / $this->max_clients) * 100, 1);
    }

    /**
     * Check if the server has reached its client capacity.
     */
    public function isAtCapacity(): bool
    {
        return $this->clients()->count() >= $this->max_clients;
    }

    /**
     * Get the WireGuard interface name (derived from address or default).
     */
    public function getInterfaceName(): string
    {
        return 'wg0';
    }
}
