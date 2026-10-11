<?php

namespace Tests\Unit\LocalVpnFleet;

use App\Models\LicenseKey;
use App\Support\LocalVpnFleet\FleetPolicy;
use App\Support\LocalVpnFleet\Ipv4Cidr;
use App\Support\LocalVpnFleet\RelayTicket;
use App\Support\LocalVpnFleet\Tier;
use Tests\TestCase;

/** Relay tickets, tiers, CIDR checks and policy defaults — the fleet's small parts. */
class FleetPrimitivesTest extends TestCase
{
    public function test_ticket_expiry_is_the_next_ten_minute_boundary_plus_ten_minutes(): void
    {
        $boundary = 1_760_000_400; // divisible by 600
        $this->assertSame(0, $boundary % 600);

        $this->assertSame($boundary + 600, RelayTicket::expiry($boundary));
        $this->assertSame($boundary + 1200, RelayTicket::expiry($boundary + 1));
        $this->assertSame($boundary + 1200, RelayTicket::expiry($boundary + 599));

        // Stable across a run of 15-second heartbeats inside one step.
        $tokens = array_unique(array_map(
            fn (int $t) => RelayTicket::token('s', 3, 12, '10.10.0.2', RelayTicket::expiry($t), 1500, 300),
            range($boundary + 1, $boundary + 599, 15),
        ));
        $this->assertCount(1, $tokens);
    }

    public function test_ticket_wire_format_and_signature(): void
    {
        $token = RelayTicket::token('node-secret', 7, 12, '10.10.0.2', 1760001000, 1500, 300);
        $payload = 'v1.7.12.10.10.0.2.1760001000.1500.300';

        $this->assertSame($payload . '.' . hash_hmac('sha256', $payload, 'node-secret'), $token);
        $this->assertMatchesRegularExpression('/\.[0-9a-f]{64}$/', $token);
        $this->assertTrue(RelayTicket::verify('node-secret', $token));
        $this->assertFalse(RelayTicket::verify('other-secret', $token));
        $this->assertFalse(RelayTicket::verify('node-secret', str_replace('.1500.', '.9999.', $token)));
    }

    public function test_tiers_from_license_types(): void
    {
        $license = fn (string $type, ?string $expires = '+10 days', string $status = 'active') => new LicenseKey([
            'license_type' => $type,
            'status' => $status,
            'expires_at' => $expires ? now()->modify($expires) : null,
        ]);

        $this->assertSame(Tier::FREE, Tier::forLicense(null));
        $this->assertSame(Tier::FREE, Tier::forLicense($license('free', null)));
        foreach (['lifetime', 'yearly', 'monthly', 'weekly', 'daily'] as $paid) {
            $this->assertSame(Tier::PRO, Tier::forLicense($license($paid)), $paid);
        }
        $this->assertSame(Tier::PRO, Tier::forLicense($license('lifetime', null)));
        $this->assertSame(Tier::TRIAL, Tier::forLicense($license('demo')));
        $this->assertSame(Tier::TRIAL, Tier::forLicense($license('trial')));
        $this->assertSame(Tier::FREE, Tier::forLicense($license('monthly', '-1 day')), 'expired');
        $this->assertSame(Tier::FREE, Tier::forLicense($license('demo', '-1 day')), 'expired trial');
        $this->assertSame(Tier::FREE, Tier::forLicense($license('monthly', '+10 days', 'revoked')));
    }

    public function test_game_cidrs_are_ipv4_aligned_narrow_and_public(): void
    {
        $this->assertSame(['43.229.64.0/18', null], Ipv4Cidr::check('43.229.64.0/18'));
        $this->assertSame(['8.8.8.8/32', null], Ipv4Cidr::check('8.8.8.8'));

        foreach ([
            '0.0.0.0/0', '0.0.0.0/1', '128.0.0.0/7', // a free full VPN
            '10.0.0.0/8', '169.254.169.254/32', '172.16.5.0/24', '192.168.1.0/24', '100.64.0.0/16', '127.0.0.1',
            '224.0.0.0/8', '255.255.255.255',
            '43.229.64.5/18', // not aligned
            '2001:db8::/32', 'game.example.com', '1.2.3.4/33', '1.2.3/24', '1.2.3.4/24/1', '',
        ] as $bad) {
            [$cidr, $error] = Ipv4Cidr::check($bad);
            $this->assertNull($cidr, "{$bad} must be refused");
            $this->assertNotNull($error);
        }

        [$cidrs, $errors] = Ipv4Cidr::parseList("# Garena SEA\n43.229.64.0/18\n\n  103.6.0.0/16  # Singapore\n43.229.64.0/18\n10.0.0.0/8\n");
        $this->assertSame(['43.229.64.0/18', '103.6.0.0/16'], $cidrs);
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('10.0.0.0/8', $errors[0]);
    }

    public function test_policy_falls_back_to_the_defaults_key_by_key(): void
    {
        $this->assertSame(FleetPolicy::DEFAULTS, FleetPolicy::normalize(null));
        $this->assertSame(FleetPolicy::DEFAULTS, FleetPolicy::normalize('not json'));

        $policy = FleetPolicy::normalize([
            'tiers' => ['free' => ['relay_kbps' => '2000', 'game' => '1', 'relay_daily_mb' => -5]],
            'budget' => ['monthly_thb' => '4500.5', 'pro_cut_pct' => 250],
        ]);

        $this->assertSame(2000, $policy['tiers']['free']['relay_kbps']);
        $this->assertTrue($policy['tiers']['free']['game']);
        $this->assertSame(300, $policy['tiers']['free']['relay_daily_mb'], 'negative ignored');
        $this->assertSame(4500.5, $policy['budget']['monthly_thb']);
        $this->assertSame(98, $policy['budget']['pro_cut_pct'], 'out of range ignored');
        $this->assertSame(FleetPolicy::DEFAULTS['tiers']['pro'], $policy['tiers']['pro']);
    }
}
