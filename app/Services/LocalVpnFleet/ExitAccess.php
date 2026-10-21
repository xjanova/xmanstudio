<?php

namespace App\Services\LocalVpnFleet;

use App\Models\LicenseKey;
use App\Models\VpnNode;
use App\Models\WireguardClient;
use App\Models\WireguardServer;
use App\Support\LocalVpnFleet\FleetPolicy;
use App\Support\LocalVpnFleet\Tier;

/**
 * Who may use a WireGuard exit that runs on a fleet node.
 *
 *   mode vpn  (full tunnel) → Pro only
 *   mode game (game CIDRs)  → tiers whose policy says game: true
 *
 * plus the node's budget must still take the tier and the device must be
 * under its tier's exit_daily_mb today (0 = no cap). The same rules decide
 * both registration (WireguardController) and the peer list each node pulls
 * on sync, so a device that falls out of them is dropped from the node within
 * one sync. Servers without a node are not handled here: they keep the old
 * free/premium country rules.
 */
final class ExitAccess
{
    public function __construct(
        private readonly MachineLicenses $licenses,
        private readonly UsageLedger $ledger,
        private readonly FleetBudget $budget,
    ) {}

    /** The node behind a server can take users right now. */
    public function usable(WireguardServer $server): bool
    {
        $node = $server->node;

        return $node !== null
            && $server->is_active
            && $node->status === VpnNode::STATUS_ACTIVE
            && $node->exitMode() !== null
            && $node->isFresh()
            && (string) $server->public_key !== '';
    }

    /** Whether a tier may use an exit of this mode at all (ignores budget and caps). */
    public static function tierAllowed(?string $mode, string $tier, array $policy): bool
    {
        return match ($mode) {
            VpnNode::ROLE_VPN => $tier === Tier::PRO,
            VpnNode::ROLE_GAME => (bool) FleetPolicy::tier($policy, $tier)['game'],
            default => false,
        };
    }

    /**
     * Why this device may not register on a node-managed server, or null if
     * it may. The message is Thai: the app shows `error` as is.
     *
     * @return array{status: int, error: string}|null
     */
    public function refusal(WireguardServer $server, string $machineId, ?LicenseKey $license): ?array
    {
        if (! $server->isNodeManaged()) {
            return null;
        }

        $node = $server->node;
        if ($node === null) {
            return ['status' => 503, 'error' => 'เซิร์ฟเวอร์นี้เต็มชั่วคราว กรุณาลองใหม่ภายหลัง'];
        }
        $mode = $node?->exitMode();
        $policy = FleetPolicy::current();
        $tier = $this->licenses->tierFor($machineId, $license);

        if (! self::tierAllowed($mode, $tier, $policy)) {
            return [
                'status' => 403,
                'error' => $mode === VpnNode::ROLE_VPN
                    ? 'VPN เต็มรูปแบบสำหรับสมาชิก Pro'
                    : 'ฟีเจอร์ลดปิงสำหรับสมาชิก Pro',
            ];
        }

        if (! $this->usable($server) || ! ($this->budget->snapshot()->forNode($node->id)?->accepts($tier) ?? false)) {
            return ['status' => 503, 'error' => 'เซิร์ฟเวอร์นี้เต็มชั่วคราว กรุณาลองใหม่ภายหลัง'];
        }

        // A game exit with no game ranges would hand out a tunnel that routes nothing.
        if ($mode === VpnNode::ROLE_GAME && GameProfiles::allowCidrs() === []) {
            return ['status' => 503, 'error' => 'ยังไม่เปิดให้บริการลดปิง กรุณาลองใหม่ภายหลัง'];
        }

        $capMb = (int) FleetPolicy::tier($policy, $tier)['exit_daily_mb'];
        if ($capMb > 0 && $this->ledger->todayBytes($machineId, 'exit') >= $capMb * UsageLedger::BYTES_PER_MB) {
            return ['status' => 429, 'error' => 'ใช้โควตาลดปิงของวันนี้ครบแล้ว พรุ่งนี้ใช้ได้ใหม่'];
        }

        return null;
    }

    /**
     * The peers a node's wg0 should carry: connected clients of its server
     * that pass the rules above. A disabled node gets none.
     *
     * @return list<array{pub: string, ip: string, kbps: int}>
     */
    public function peersFor(VpnNode $node, ?WireguardServer $server, ?FleetBudgetSnapshot $snapshot = null): array
    {
        $mode = $node->exitMode();
        if ($server === null || $mode === null || $node->status === VpnNode::STATUS_DISABLED || ! $server->is_active) {
            return [];
        }

        $clients = WireguardClient::query()
            ->where('server_id', $server->id)
            ->where('is_connected', true)
            ->get(['id', 'machine_id', 'public_key', 'assigned_ip']);
        if ($clients->isEmpty()) {
            return [];
        }

        $policy = FleetPolicy::current();
        $nodeBudget = ($snapshot ?? $this->budget->snapshot())->forNode($node->id);
        $machineIds = $clients->pluck('machine_id')->all();
        $licenses = $this->licenses->resolve($machineIds);
        $usedToday = $this->ledger->todayBytesFor($machineIds, 'exit');

        $peers = [];
        foreach ($clients as $client) {
            $tier = Tier::forLicense($licenses[$client->machine_id] ?? null);
            if (! self::tierAllowed($mode, $tier, $policy)) {
                continue;
            }
            // Budget cut-offs drop a tier from the node, like an expiring relay ticket.
            // Draining keeps current users, so only the cut-off is checked, not `active`.
            if ($nodeBudget !== null && ! ($nodeBudget->accepts[$tier] ?? false)) {
                continue;
            }
            $capMb = (int) FleetPolicy::tier($policy, $tier)['exit_daily_mb'];
            if ($capMb > 0 && ($usedToday[$client->machine_id] ?? 0) >= $capMb * UsageLedger::BYTES_PER_MB) {
                continue;
            }

            $peers[] = ['pub' => $client->public_key, 'ip' => $client->assigned_ip, 'kbps' => 0];
        }

        return $peers;
    }
}
