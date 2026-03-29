<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VpnNetworkMember extends Model
{
    protected $fillable = [
        'network_id',
        'device_id',
        'machine_id',
        'display_name',
        'virtual_ip',
        'public_ip',
        'public_port',
        'vpn_gateway_country',
        'vpn_gateway_hostname',
        'is_online',
        'last_heartbeat_at',
        'joined_at',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'last_heartbeat_at' => 'datetime',
        'joined_at' => 'datetime',
        'public_port' => 'integer',
    ];

    // ==================== Relationships ====================

    public function network(): BelongsTo
    {
        return $this->belongsTo(VpnNetwork::class, 'network_id');
    }

    public function relaySessions(): HasMany
    {
        return $this->hasMany(VpnRelaySession::class, 'source_member_id');
    }

    // ==================== Methods ====================

    /**
     * Mark the member as online.
     */
    public function goOnline(): void
    {
        $this->update([
            'is_online' => true,
            'last_heartbeat_at' => now(),
        ]);
    }

    /**
     * Mark the member as offline.
     */
    public function goOffline(): void
    {
        $this->update([
            'is_online' => false,
        ]);
    }

    /**
     * Update heartbeat timestamp and optional connection info.
     */
    public function updateHeartbeat(?string $publicIp = null, ?int $publicPort = null, ?string $vpnGatewayCountry = null, ?string $vpnGatewayHostname = null): void
    {
        $data = [
            'is_online' => true,
            'last_heartbeat_at' => now(),
            'vpn_gateway_country' => $vpnGatewayCountry, // null clears VPN gateway status
            'vpn_gateway_hostname' => $vpnGatewayCountry ? $vpnGatewayHostname : null,
        ];

        if ($publicIp !== null) {
            $data['public_ip'] = $publicIp;
        }
        if ($publicPort !== null) {
            $data['public_port'] = $publicPort;
        }

        $this->update($data);
    }
}
