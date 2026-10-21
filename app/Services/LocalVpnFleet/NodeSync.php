<?php

namespace App\Services\LocalVpnFleet;

use App\Models\VpnNetworkMember;
use App\Models\VpnNode;
use App\Models\WireguardClient;
use App\Support\LocalVpnFleet\FleetClock;
use Illuminate\Support\Facades\DB;

/**
 * Applies one authenticated node sync (POST /api/v1/localvpn/node/sync) and
 * builds the answer the node acts on. The caller has already checked the
 * signature and the time window; the replay check that matters happens here,
 * atomically, inside the same transaction as the writes.
 *
 * Usage counters in the body are deltas since the node's previous accepted
 * sync. Relay usage is keyed by network id + virtual IP (→ member → device),
 * exit usage by WireGuard public key (→ client of this node's server →
 * device). Rows that resolve to nobody still count toward the node's totals.
 */
final class NodeSync
{
    public const SYNC_INTERVAL_SECONDS = 10;

    /** More rows than any node could have clients is a broken agent, not usage. */
    public const MAX_USAGE_ROWS = 20_000;

    /** 1 PB: anything larger in one delta is garbage. */
    private const MAX_DELTA = 1_000_000_000_000_000;

    private const WG_KEY = '/^[A-Za-z0-9+\/]{43}=$/';

    public function __construct(
        private readonly UsageLedger $ledger,
        private readonly MachineLicenses $licenses,
        private readonly ExitAccess $exits,
        private readonly FleetBudget $budget,
    ) {}

    /**
     * @throws SyncReplayed when $time is not later than the last accepted sync
     */
    public function apply(VpnNode $node, int $time, array $body): array
    {
        DB::transaction(function () use ($node, $time, $body) {
            $claimed = VpnNode::query()
                ->whereKey($node->id)
                ->where('last_sync_time', '<', $time)
                ->update(['last_sync_time' => $time]);

            if ($claimed === 0) {
                throw new SyncReplayed;
            }

            $this->recordUsage($node, $body);
            $this->updateNode($node, $body);
            $this->updateExitServer($node);
        });

        return $this->answer($node->refresh());
    }

    /** The response body, exactly as docs/RELAY_FLEET.md describes it. */
    public function answer(VpnNode $node): array
    {
        $snapshot = $this->budget->snapshot();
        $server = $node->wireguardServer()->first();
        $mode = $node->exitMode();
        $disabled = $node->status === VpnNode::STATUS_DISABLED;
        $exitEnabled = ! $disabled && $mode !== null && $server !== null && $server->is_active;

        $budget = $snapshot->forNode($node->id)?->toSyncArray() ?? [
            'accept_new' => false,
            'min_tier' => null,
            'used_gb' => 0.0,
            'quota_gb' => (int) $node->included_gb,
        ];
        // The snapshot is cached for a few seconds; the status on the row is current.
        if ($node->status !== VpnNode::STATUS_ACTIVE) {
            $budget['accept_new'] = false;
            $budget['min_tier'] = null;
        }

        return [
            'ok' => true,
            'status' => $node->status,
            'sync_interval_s' => self::SYNC_INTERVAL_SECONDS,
            'budget' => $budget,
            'relay' => [
                'enabled' => ! $disabled && $node->hasRole(VpnNode::ROLE_RELAY),
                'max_clients' => (int) $node->max_clients,
            ],
            'exit' => [
                'enabled' => $exitEnabled,
                'mode' => $mode ?? VpnNode::ROLE_GAME,
                'address' => $server?->address,
                'listen_port' => $server?->listen_port,
                'peers' => $exitEnabled ? $this->exits->peersFor($node, $server, $snapshot) : [],
                'allow_cidrs' => $exitEnabled ? GameProfiles::allowCidrs() : [],
            ],
        ];
    }

    private function recordUsage(VpnNode $node, array $body): void
    {
        $relay = is_array($body['relay'] ?? null) ? $body['relay'] : [];
        $exit = is_array($body['exit'] ?? null) ? $body['exit'] : [];
        $nic = is_array($body['nic'] ?? null) ? $body['nic'] : [];

        $relayByPair = $this->relayUsage($relay['usage'] ?? []);
        $exitByKey = $this->exitUsage($exit['usage'] ?? []);

        // Relay: (network, virtual IP) → member → device.
        $relayByMachine = [];
        if ($relayByPair !== []) {
            $nets = array_unique(array_map(fn ($pair) => (int) explode('|', $pair)[0], array_keys($relayByPair)));
            $vips = array_unique(array_map(fn ($pair) => explode('|', $pair)[1], array_keys($relayByPair)));
            $members = VpnNetworkMember::query()
                ->whereIn('network_id', $nets)
                ->whereIn('virtual_ip', $vips)
                ->toBase()
                ->get(['network_id', 'virtual_ip', 'machine_id']);
            foreach ($members as $member) {
                $bytes = $relayByPair[$member->network_id . '|' . $member->virtual_ip] ?? 0;
                if ($bytes > 0) {
                    $relayByMachine[$member->machine_id] = ($relayByMachine[$member->machine_id] ?? 0) + $bytes;
                }
            }
        }

        // Exit: public key → client of this node's own server → device.
        $exitByMachine = [];
        $server = $node->wireguardServer()->first();
        if ($server !== null && $exitByKey !== []) {
            $clients = WireguardClient::query()
                ->where('server_id', $server->id)
                ->whereIn('public_key', array_keys($exitByKey))
                ->toBase()
                ->get(['id', 'machine_id', 'public_key']);
            $this->bumpClientCounters($clients, $exitByKey);
            foreach ($clients as $client) {
                [$rx, $tx] = $exitByKey[$client->public_key];
                $exitByMachine[$client->machine_id] = ($exitByMachine[$client->machine_id] ?? 0) + $rx + $tx;
            }
        }

        $licenses = $this->licenses->resolve(array_merge(array_keys($relayByMachine), array_keys($exitByMachine)));
        $withLicense = fn (array $byMachine) => collect($byMachine)
            ->map(fn (int $bytes, $machineId) => ['bytes' => $bytes, 'license_key_id' => $licenses[$machineId]->id ?? null])
            ->all();

        $today = FleetClock::today();
        $this->ledger->addUserUsage($today, 'relay', $withLicense($relayByMachine));
        $this->ledger->addUserUsage($today, 'exit', $withLicense($exitByMachine));

        $exitTotal = array_sum(array_map(fn (array $rxTx) => $rxTx[0] + $rxTx[1], $exitByKey));
        $this->ledger->addNodeUsage(
            $node->id,
            $today,
            self::count($nic['rx'] ?? 0),
            self::count($nic['tx'] ?? 0),
            self::count($relay['rx'] ?? 0) + self::count($relay['tx'] ?? 0),
            $exitTotal,
            self::count($relay['clients'] ?? 0) + self::count($exit['peers'] ?? 0),
        );
    }

    private function updateNode(VpnNode $node, array $body): void
    {
        $relay = is_array($body['relay'] ?? null) ? $body['relay'] : [];
        $exit = is_array($body['exit'] ?? null) ? $body['exit'] : [];
        $nic = is_array($body['nic'] ?? null) ? $body['nic'] : [];

        $updates = [
            'last_seen_at' => now(),
            'current_clients' => min(self::count($relay['clients'] ?? 0), 4_000_000_000),
            'last_sync' => [
                'at' => now()->toIso8601String(),
                'agent' => self::label($body['agent'] ?? null),
                'roles' => array_values(array_intersect(VpnNode::ROLES, (array) ($body['roles'] ?? []))),
                'nic' => ['rx' => self::count($nic['rx'] ?? 0), 'tx' => self::count($nic['tx'] ?? 0)],
                'relay' => [
                    'clients' => self::count($relay['clients'] ?? 0),
                    'rx' => self::count($relay['rx'] ?? 0),
                    'tx' => self::count($relay['tx'] ?? 0),
                ],
                'exit' => ['peers' => self::count($exit['peers'] ?? 0)],
                'load1' => is_numeric($body['load1'] ?? null) ? round((float) $body['load1'], 2) : null,
            ],
        ];

        $key = $body['wg_public_key'] ?? null;
        if (is_string($key) && preg_match(self::WG_KEY, $key)) {
            $updates['wg_public_key'] = $key;
        }
        foreach (['wg_port', 'relay_port'] as $port) {
            $value = filter_var($body[$port] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
            if ($value !== false) {
                $updates[$port] = $value;
            }
        }

        $node->forceFill($updates)->save();
    }

    /** Copy what the node reports into its WireGuard server row. */
    private function updateExitServer(VpnNode $node): void
    {
        $server = $node->wireguardServer()->first();
        if ($server === null) {
            return;
        }

        $server->forceFill([
            'endpoint' => $node->endpoint((int) $node->wg_port),
            'last_health_check_at' => now(),
        ]);
        if ($node->wg_public_key) {
            $server->public_key = $node->wg_public_key;
        }
        // Healthy once the node has told us the key clients must talk to.
        $server->is_healthy = (string) $server->public_key !== '';
        $server->save();
    }

    /** @return array<string, int> "net|vip" => bytes */
    private function relayUsage(mixed $rows): array
    {
        $usage = [];
        foreach (array_slice(is_array($rows) ? $rows : [], 0, self::MAX_USAGE_ROWS) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $net = filter_var($row['net'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $vip = $row['vip'] ?? null;
            $bytes = self::count($row['bytes'] ?? 0);
            if ($net === false || ! is_string($vip) || ! filter_var($vip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || $bytes === 0) {
                continue;
            }
            $usage["{$net}|{$vip}"] = ($usage["{$net}|{$vip}"] ?? 0) + $bytes;
        }

        return $usage;
    }

    /** @return array<string, array{0: int, 1: int}> public key => [rx, tx] */
    private function exitUsage(mixed $rows): array
    {
        $usage = [];
        foreach (array_slice(is_array($rows) ? $rows : [], 0, self::MAX_USAGE_ROWS) as $row) {
            if (! is_array($row) || ! is_string($row['pub'] ?? null) || ! preg_match(self::WG_KEY, $row['pub'])) {
                continue;
            }
            $rx = self::count($row['rx'] ?? 0);
            $tx = self::count($row['tx'] ?? 0);
            if ($rx + $tx === 0) {
                continue;
            }
            [$oldRx, $oldTx] = $usage[$row['pub']] ?? [0, 0];
            $usage[$row['pub']] = [$oldRx + $rx, $oldTx + $tx];
        }

        return $usage;
    }

    /**
     * Server-side transfer counters and a "last seen" for each client that
     * moved traffic, so the health check can tell live peers from stale ones.
     */
    private function bumpClientCounters($clients, array $exitByKey): void
    {
        if ($clients->isEmpty()) {
            return;
        }

        $rxCase = '';
        $txCase = '';
        foreach ($clients as $client) {
            [$rx, $tx] = $exitByKey[$client->public_key];
            $rxCase .= ' WHEN ' . (int) $client->id . ' THEN ' . (int) $rx;
            $txCase .= ' WHEN ' . (int) $client->id . ' THEN ' . (int) $tx;
        }

        DB::table('wireguard_clients')
            ->whereIn('id', $clients->pluck('id')->all())
            ->update([
                'bytes_rx' => DB::raw("bytes_rx + CASE id{$rxCase} ELSE 0 END"),
                'bytes_tx' => DB::raw("bytes_tx + CASE id{$txCase} ELSE 0 END"),
                'last_handshake_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /** A non-negative counter from untrusted JSON (ints, floats or digit strings). */
    private static function count(mixed $value): int
    {
        if (is_int($value)) {
            return max(0, min($value, self::MAX_DELTA));
        }
        if (is_float($value) || (is_string($value) && ctype_digit($value))) {
            return (int) max(0, min((float) $value, self::MAX_DELTA));
        }

        return 0;
    }

    private static function label(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $clean = preg_replace('/[^\x20-\x7E]/', '', $value);

        return $clean === '' ? null : mb_substr($clean, 0, 64);
    }
}
