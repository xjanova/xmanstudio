<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WireguardClient extends Model
{
    protected $fillable = [
        'server_id',
        'machine_id',
        'public_key',
        'assigned_ip',
        'is_connected',
        'bytes_rx',
        'bytes_tx',
        'last_handshake_at',
        'connected_at',
    ];

    protected $casts = [
        'is_connected' => 'boolean',
        'bytes_rx' => 'integer',
        'bytes_tx' => 'integer',
        'last_handshake_at' => 'datetime',
        'connected_at' => 'datetime',
    ];

    // ─── Relationships ───

    public function server(): BelongsTo
    {
        return $this->belongsTo(WireguardServer::class, 'server_id');
    }

    // ─── Methods ───

    /**
     * Generate a WireGuard .conf file content for this client.
     * Note: The client's private key is NOT stored on the server — the client must
     * insert their own PrivateKey into the [Interface] section.
     */
    public function generateConfig(): string
    {
        $server = $this->server;

        $config = "[Interface]\n";
        $config .= "PrivateKey = <INSERT_YOUR_PRIVATE_KEY>\n";
        $config .= "Address = {$this->assigned_ip}/32\n";
        $config .= "DNS = {$server->dns}\n";
        $config .= "\n";
        $config .= "[Peer]\n";
        $config .= "PublicKey = {$server->public_key}\n";
        $config .= "Endpoint = {$server->endpoint}\n";
        $config .= "AllowedIPs = 0.0.0.0/0, ::/0\n";
        $config .= "PersistentKeepalive = 25\n";

        return $config;
    }
}
