<?php

namespace App\Services\LocalVpnFleet;

use App\Models\LicenseKey;
use App\Models\VpnNetworkMember;
use App\Models\VpnNode;
use App\Support\LocalVpnFleet\FleetPolicy;
use App\Support\LocalVpnFleet\RelayTicket;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;

/**
 * Picks the LVR1 relay for a heartbeat (docs/RELAY_FLEET.md, "Relay ticket
 * choice"): nodes with the relay role, active, synced in the last 60 s, not
 * full, whose budget still takes the user's tier — and only while the user is
 * under their tier's relay_daily_mb today (summed across nodes). Thai nodes
 * first (the users are in Thailand), then the lightest load per unit of
 * weight. Nothing qualifies → null, and the app stays on direct P2P.
 */
final class RelayTicketIssuer
{
    public const PREFERRED_COUNTRY = 'TH';

    public function __construct(
        private readonly MachineLicenses $licenses,
        private readonly UsageLedger $ledger,
        private readonly FleetBudget $budget,
    ) {}

    /** @return array{host: string, port: int, node: string, token: string, expires_at: string}|null */
    public function forMember(VpnNetworkMember $member, ?LicenseKey $license): ?array
    {
        $vip = (string) $member->virtual_ip;
        if (! filter_var($vip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return null;
        }

        $tier = $this->licenses->tierFor((string) $member->machine_id, $license);
        $limits = FleetPolicy::tier(FleetPolicy::current(), $tier);

        $capMb = (int) $limits['relay_daily_mb'];
        if ($capMb > 0 && $this->ledger->todayBytes((string) $member->machine_id, 'relay') >= $capMb * UsageLedger::BYTES_PER_MB) {
            return null;
        }

        $node = $this->pickNode($tier);
        if ($node === null) {
            return null;
        }

        try {
            $secret = (string) $node->secret;
        } catch (DecryptException) {
            // APP_KEY changed under the stored secret: no ticket rather than a failed heartbeat.
            Log::warning('[LocalVPN fleet] node secret cannot be decrypted; rotate it in admin', ['node' => $node->id]);

            return null;
        }
        $exp = RelayTicket::expiry(time());
        $kbps = (int) $limits['relay_kbps'];

        return [
            'host' => $node->host,
            'port' => (int) $node->relay_port,
            'node' => $node->code,
            'token' => RelayTicket::token($secret, $node->id, (int) $member->network_id, $vip, $exp, $kbps, $capMb),
            'expires_at' => CarbonImmutable::createFromTimestampUTC($exp)->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    private function pickNode(string $tier): ?VpnNode
    {
        $snapshot = $this->budget->snapshot();

        return VpnNode::query()
            ->where('status', VpnNode::STATUS_ACTIVE)
            ->where('last_seen_at', '>=', now()->subSeconds(VpnNode::FRESH_SECONDS))
            ->get()
            ->filter(fn (VpnNode $node) => $node->hasRole(VpnNode::ROLE_RELAY)
                && $node->current_clients < $node->max_clients
                && ($snapshot->forNode($node->id)?->accepts($tier) ?? false))
            ->sortBy([
                fn (VpnNode $a, VpnNode $b) => ($b->country === self::PREFERRED_COUNTRY) <=> ($a->country === self::PREFERRED_COUNTRY),
                fn (VpnNode $a, VpnNode $b) => ($a->current_clients / max(1, $a->weight)) <=> ($b->current_clients / max(1, $b->weight)),
                fn (VpnNode $a, VpnNode $b) => $a->id <=> $b->id,
            ])
            ->first();
    }
}
