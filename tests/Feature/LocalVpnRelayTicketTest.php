<?php

namespace Tests\Feature;

use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\Setting;
use App\Models\VpnNetwork;
use App\Models\VpnNetworkMember;
use App\Models\VpnNode;
use App\Services\LocalVpnFleet\FleetBudget;
use App\Support\LocalVpnFleet\FleetClock;
use App\Support\LocalVpnFleet\FleetPolicy;
use App\Support\LocalVpnFleet\RelayTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The LVR1 relay ticket in POST /api/v1/localvpn/heartbeat
 * (docs/RELAY_FLEET.md, "Ticket" and "Relay ticket choice").
 */
class LocalVpnRelayTicketTest extends TestCase
{
    use RefreshDatabase;

    private const GB = 1_000_000_000;

    private VpnNetwork $network;

    protected function setUp(): void
    {
        parent::setUp();

        $this->network = VpnNetwork::create([
            'name' => 'LAN party',
            'slug' => 'lan-party',
            'owner_device_id' => 'owner-pc',
            'max_members' => 10,
            'virtual_subnet' => '10.10.0.0/24',
        ]);
    }

    private function node(string $code, array $attributes = [], string $secret = 'secret-of-'): VpnNode
    {
        $node = new VpnNode(array_merge([
            'name' => $code,
            'code' => $code,
            'country' => 'TH',
            'host' => '203.0.113.10',
            'relay_port' => 51821,
            'wg_port' => 51820,
            'roles' => ['relay'],
            'status' => VpnNode::STATUS_ACTIVE,
            'included_gb' => 100,
            'billing_day' => 1,
            'billing_mode' => 'out',
            'max_clients' => 2000,
            'weight' => 100,
        ], $attributes));
        $node->secret = $secret . $code;
        $node->save();
        $node->forceFill(['last_seen_at' => $attributes['last_seen_at'] ?? now()->subSeconds(5), 'current_clients' => $attributes['current_clients'] ?? 0])->save();

        return $node;
    }

    /** Add outbound GB to today's counters (as a sync would). */
    private function usage(VpnNode $node, float $txGb): void
    {
        $row = DB::table('vpn_node_usage_daily')->where('node_id', $node->id)->where('date', FleetClock::today());
        if ($row->exists()) {
            $row->increment('nic_tx', (int) ($txGb * self::GB));
        } else {
            DB::table('vpn_node_usage_daily')->insert(['node_id' => $node->id, 'date' => FleetClock::today(), 'nic_tx' => (int) ($txGb * self::GB)]);
        }
        FleetBudget::forget();
    }

    private function device(string $machineId, ?string $licenseType = null, string $vip = '10.10.0.2'): VpnNetworkMember
    {
        if ($licenseType !== null) {
            LicenseKey::create([
                'product_id' => Product::where('slug', 'localvpn')->value('id'),
                'license_key' => strtoupper($licenseType) . '-' . $machineId,
                'license_type' => $licenseType,
                'status' => 'active',
                'machine_id' => $machineId,
                'expires_at' => $licenseType === 'lifetime' ? null : now()->addDays(10),
            ]);
        }

        return VpnNetworkMember::create([
            'network_id' => $this->network->id,
            'machine_id' => $machineId,
            'display_name' => $machineId,
            'virtual_ip' => $vip,
            'is_online' => true,
            'last_heartbeat_at' => now(),
            'joined_at' => now(),
        ]);
    }

    private function heartbeat(VpnNetworkMember $member): TestResponse
    {
        return $this->postJson('/api/v1/localvpn/heartbeat', [
            'slug' => $this->network->slug,
            'machine_id' => $member->machine_id,
        ])->assertOk();
    }

    public function test_the_heartbeat_carries_a_ticket_the_node_can_verify(): void
    {
        $node = $this->node('th-bkk-1', ['host' => '203.0.113.10', 'relay_port' => 51821]);
        $member = $this->device('alice-pc', 'monthly', '10.10.0.7');

        $relay = $this->heartbeat($member)->json('relay');

        $this->assertSame(['host', 'port', 'node', 'token', 'expires_at'], array_keys($relay));
        $this->assertSame('203.0.113.10', $relay['host']);
        $this->assertSame(51821, $relay['port']);
        $this->assertSame('th-bkk-1', $relay['node']);

        // v1.<node_id>.<network_id>.<vip>.<exp>.<kbps>.<daily_mb>.<sig>, Pro limits from the policy.
        $parts = explode('.', $relay['token']);
        $this->assertCount(11, $parts);
        [$version, $nodeId, $networkId, $a, $b, $c, $d, $exp, $kbps, $dailyMb, $sig] = $parts;
        $this->assertSame(['v1', (string) $node->id, (string) $this->network->id, '10.10.0.7'], [$version, $nodeId, $networkId, "{$a}.{$b}.{$c}.{$d}"]);
        $this->assertSame(['20000', '10000'], [$kbps, $dailyMb]);
        $this->assertSame(hash_hmac('sha256', implode('.', array_slice($parts, 0, 10)), 'secret-of-th-bkk-1'), $sig);
        $this->assertTrue(RelayTicket::verify('secret-of-th-bkk-1', $relay['token']));

        // exp: on a 10-minute boundary, 10–20 minutes ahead; expires_at is the same instant in UTC.
        $this->assertSame(0, (int) $exp % 600);
        $this->assertGreaterThanOrEqual(time() + 600, (int) $exp);
        $this->assertLessThanOrEqual(time() + 1200, (int) $exp);
        $this->assertSame(gmdate('Y-m-d\TH:i:s\Z', (int) $exp), $relay['expires_at']);

        // The next heartbeat a few seconds later gets the very same token.
        $this->assertSame($relay['token'], $this->heartbeat($member)->json('relay.token'));
    }

    public function test_no_qualifying_node_means_relay_null(): void
    {
        $member = $this->device('alice-pc');

        // No nodes at all — the key is still there, null.
        $this->heartbeat($member)->assertJsonPath('relay', null)->assertJsonPath('success', true);

        // Present but stale, draining, disabled, full, or without the relay role.
        $this->node('stale', ['last_seen_at' => now()->subSeconds(61)]);
        $this->node('draining', ['status' => VpnNode::STATUS_DRAINING]);
        $this->node('disabled', ['status' => VpnNode::STATUS_DISABLED]);
        $this->node('full', ['max_clients' => 10, 'current_clients' => 10]);
        $this->node('game-only', ['roles' => ['game']]);
        FleetBudget::forget();

        $this->heartbeat($member)->assertJsonPath('relay', null);
    }

    public function test_budget_cut_offs_drop_free_then_trial_then_everyone(): void
    {
        $node = $this->node('th-bkk-1');
        $free = $this->device('free-pc', null, '10.10.0.2');
        $trial = $this->device('trial-pc', 'demo', '10.10.0.3');
        $pro = $this->device('pro-pc', 'yearly', '10.10.0.4');
        $tickets = fn () => array_map(fn ($m) => $this->heartbeat($m)->json('relay') !== null, [$free, $trial, $pro]);

        $this->usage($node, 79);
        $this->assertSame([true, true, true], $tickets());

        $this->usage($node, 6);   // 85 %
        $this->assertSame([false, true, true], $tickets());

        $this->usage($node, 7);   // 92 %
        $this->assertSame([false, false, true], $tickets());

        $this->usage($node, 7);   // 99 %
        $this->assertSame([false, false, false], $tickets());

        // allow_overage keeps Pro going past the last cut — and only Pro …
        $node->update(['allow_overage' => true]);
        FleetBudget::forget();
        $this->assertSame([false, false, true], $tickets());

        // … until the fleet's projected month cost passes the budget.
        Setting::setValue(FleetPolicy::SETTING_KEY, ['budget' => ['monthly_thb' => 100]], 'json');
        $node->update(['monthly_cost_thb' => 500]);
        FleetBudget::forget();
        $this->assertSame([false, false, false], $tickets());
    }

    public function test_free_and_trial_tickets_carry_their_own_limits(): void
    {
        $this->node('th-bkk-1');
        $parts = fn ($member) => array_slice(explode('.', $this->heartbeat($member)->json('relay.token')), 8, 2);

        $this->assertSame(['1500', '300'], $parts($this->device('free-pc', null, '10.10.0.2')));
        $this->assertSame(['8000', '3000'], $parts($this->device('trial-pc', 'demo', '10.10.0.3')));
    }

    public function test_a_device_over_its_daily_relay_cap_gets_no_ticket(): void
    {
        $this->node('th-bkk-1');
        $member = $this->device('free-pc');
        $row = ['date' => FleetClock::today(), 'kind' => 'relay', 'machine_id' => 'free-pc', 'bytes' => 300 * 1048576 - 1];
        DB::table('vpn_user_usage_daily')->insert($row);

        $this->assertNotNull($this->heartbeat($member)->json('relay'));

        DB::table('vpn_user_usage_daily')->where('machine_id', 'free-pc')->increment('bytes');
        $this->heartbeat($member)->assertJsonPath('relay', null);

        // Yesterday's usage doesn't count today.
        Carbon::setTestNow(now()->addDay());
        try {
            DB::table('vpn_nodes')->update(['last_seen_at' => now()]);
            $this->assertNotNull($this->heartbeat($member)->json('relay'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_thai_nodes_come_first_then_the_lightest_load_per_weight(): void
    {
        $this->node('sg-1', ['country' => 'SG', 'current_clients' => 0]);
        $this->node('th-busy', ['current_clients' => 900, 'weight' => 100]);
        $this->node('th-big', ['current_clients' => 900, 'weight' => 300]);
        $member = $this->device('alice-pc');

        $this->heartbeat($member)->assertJsonPath('relay.node', 'th-big');

        VpnNode::whereIn('code', ['th-busy', 'th-big'])->update(['status' => VpnNode::STATUS_DRAINING]);
        $this->heartbeat($member)->assertJsonPath('relay.node', 'sg-1');
    }

    public function test_the_old_http_relay_answers_410_without_touching_the_database(): void
    {
        DB::enableQueryLog();

        $this->postJson('/api/v1/localvpn/relay', [
            'slug' => 'lan-party',
            'source_machine_id' => 'alice-pc',
            'target_virtual_ip' => '10.10.0.3',
            'data' => base64_encode('hello'),
        ])->assertStatus(410)->assertExactJson(['success' => false, 'error' => 'relay moved to UDP']);

        // The global IP block-list check may read; the endpoint itself reads and writes nothing.
        $touched = array_filter(array_column(DB::getQueryLog(), 'query'), fn (string $sql) => preg_match('/vpn_|license_keys|products|insert|update/i', $sql));
        $this->assertSame([], array_values($touched));
    }
}
