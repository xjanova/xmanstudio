<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One VPS of the LocalVPN relay fleet (docs/RELAY_FLEET.md in the app repo).
 *
 * The node is identified on the wire by its numeric id (X-Node-Id, the
 * `--id` install flag and the `<node_id>` field of relay tickets); `code`
 * (th-bkk-1) is the human label shown to users and admins.
 *
 * @property array<int,string> $roles
 * @property string $secret
 */
class VpnNode extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_DRAINING = 'draining';

    public const STATUS_DISABLED = 'disabled';

    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_DRAINING, self::STATUS_DISABLED];

    public const ROLE_RELAY = 'relay';

    public const ROLE_GAME = 'game';

    public const ROLE_VPN = 'vpn';

    public const ROLES = [self::ROLE_RELAY, self::ROLE_GAME, self::ROLE_VPN];

    public const BILLING_MODES = ['out', 'in_out', 'max'];

    /** A node that has not synced for this long gets no new relay tickets. */
    public const FRESH_SECONDS = 60;

    protected $fillable = [
        'name',
        'code',
        'country',
        'city',
        'provider',
        'host',
        'relay_port',
        'wg_port',
        'wg_public_key',
        'roles',
        'status',
        'monthly_cost_thb',
        'included_gb',
        'overage_thb_per_gb',
        'billing_day',
        'billing_mode',
        'allow_overage',
        'max_clients',
        'weight',
    ];

    protected $hidden = [
        'secret',
    ];

    protected $casts = [
        'secret' => 'encrypted',
        'roles' => 'array',
        'last_sync' => 'array',
        'relay_port' => 'integer',
        'wg_port' => 'integer',
        'monthly_cost_thb' => 'decimal:2',
        'included_gb' => 'integer',
        'overage_thb_per_gb' => 'decimal:4',
        'billing_day' => 'integer',
        'allow_overage' => 'boolean',
        'max_clients' => 'integer',
        'weight' => 'integer',
        'current_clients' => 'integer',
        'last_seen_at' => 'datetime',
        'last_sync_time' => 'integer',
    ];

    // ─── Relationships ───

    /** The WireGuard exit this node runs (game / vpn roles), if any. */
    public function wireguardServer(): HasOne
    {
        return $this->hasOne(WireguardServer::class, 'vpn_node_id');
    }

    public function usageDaily(): HasMany
    {
        return $this->hasMany(VpnNodeUsageDaily::class, 'node_id');
    }

    // ─── Helpers ───

    /** 64 hex characters; the node uses the string itself as its HMAC key. */
    public static function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, (array) $this->roles, true);
    }

    /** Synced recently enough to be handed new users. */
    public function isFresh(?CarbonInterface $now = null): bool
    {
        $now ??= now();

        return $this->last_seen_at !== null
            && $this->last_seen_at->greaterThan($now->copy()->subSeconds(self::FRESH_SECONDS));
    }

    /**
     * What the node's WireGuard exit forwards: 'vpn' = everything (Pro only),
     * 'game' = only the game CIDRs, null = the node runs no exit.
     */
    public function exitMode(): ?string
    {
        if ($this->hasRole(self::ROLE_VPN)) {
            return self::ROLE_VPN;
        }

        return $this->hasRole(self::ROLE_GAME) ? self::ROLE_GAME : null;
    }

    /** host:port, with IPv6 literals bracketed. */
    public function endpoint(int $port): string
    {
        $host = filter_var($this->host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? "[{$this->host}]" : $this->host;

        return "{$host}:{$port}";
    }

    public function installCommand(string $secret): string
    {
        $roles = implode(',', (array) $this->roles);

        return sprintf('sudo bash install.sh --id %d --secret %s --roles %s', $this->id, $secret, $roles);
    }
}
