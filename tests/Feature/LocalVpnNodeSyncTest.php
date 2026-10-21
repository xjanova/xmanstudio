<?php

namespace Tests\Feature;

use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\VpnGameProfile;
use App\Models\VpnNetwork;
use App\Models\VpnNetworkMember;
use App\Models\VpnNode;
use App\Models\WireguardClient;
use App\Models\WireguardServer;
use App\Support\LocalVpnFleet\FleetClock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * POST /api/v1/localvpn/node/sync — what every relay-fleet node sends every
 * 10 s (docs/RELAY_FLEET.md, "Node sync"): HMAC auth with replay protection,
 * usage deltas added to the daily counters, the node's WireGuard key copied
 * into its exit row, and the answer it builds its relay and wg0 from.
 */
class LocalVpnNodeSyncTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-node-secret-0123456789abcdef';

    private const KEY_A = 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=';

    private const KEY_B = 'BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBA=';

    private const KEY_C = 'CCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCA=';

    private const NODE_KEY = 'NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNA=';

    private int $clock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock = time();
        Process::fake();
    }

    private function node(array $attributes = []): VpnNode
    {
        $node = new VpnNode(array_merge([
            'name' => 'Bangkok 1',
            'code' => 'th-bkk-1',
            'country' => 'TH',
            'host' => '203.0.113.10',
            'relay_port' => 51821,
            'wg_port' => 51820,
            'roles' => ['relay'],
            'status' => VpnNode::STATUS_ACTIVE,
            'included_gb' => 1000,
            'billing_day' => 1,
            'billing_mode' => 'out',
            'max_clients' => 2000,
            'weight' => 100,
        ], $attributes));
        $node->secret = self::SECRET;
        $node->save();

        return $node;
    }

    private function sync(VpnNode $node, array $body, ?int $time = null, string $secret = self::SECRET, ?string $nodeId = null): TestResponse
    {
        $raw = json_encode($body);
        $time ??= ++$this->clock;

        return $this->call('POST', '/api/v1/localvpn/node/sync', [], [], [], [
            'HTTP_X_NODE_ID' => $nodeId ?? (string) $node->id,
            'HTTP_X_NODE_TIME' => (string) $time,
            'HTTP_X_NODE_SIGNATURE' => hash_hmac('sha256', $time . '.' . $raw, $secret),
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $raw);
    }

    private function license(string $machineId, string $type, ?string $expires = '+30 days'): LicenseKey
    {
        return LicenseKey::create([
            'product_id' => Product::where('slug', 'localvpn')->value('id'),
            'license_key' => strtoupper($type) . '-' . $machineId,
            'license_type' => $type,
            'status' => 'active',
            'machine_id' => $machineId,
            'expires_at' => $expires ? now()->modify($expires) : null,
        ]);
    }

    private function member(string $machineId, string $vip): VpnNetworkMember
    {
        $network = VpnNetwork::firstOrCreate(['slug' => 'lan-party'], [
            'name' => 'LAN party',
            'owner_device_id' => $machineId,
            'max_members' => 10,
            'virtual_subnet' => '10.10.0.0/24',
        ]);

        return VpnNetworkMember::create([
            'network_id' => $network->id,
            'machine_id' => $machineId,
            'display_name' => $machineId,
            'virtual_ip' => $vip,
            'is_online' => true,
            'last_heartbeat_at' => now(),
            'joined_at' => now(),
        ]);
    }

    private function exitServer(VpnNode $node, array $attributes = []): WireguardServer
    {
        return WireguardServer::create(array_merge([
            'vpn_node_id' => $node->id,
            'name' => 'Singapore game',
            'country_code' => 'SG',
            'country_name' => 'Singapore',
            'endpoint' => 'old:1',
            'public_key' => '',
            'private_key' => null,
            'address' => '10.66.0.1/24',
            'listen_port' => 51820,
            'is_active' => true,
            'is_healthy' => false,
        ], $attributes));
    }

    // ── Authentication ───────────────────────────────────────────────

    public function test_a_correctly_signed_sync_is_accepted_and_answered_in_the_spec_shape(): void
    {
        $node = $this->node();

        $this->sync($node, ['agent' => 'lvnode/1.0.0', 'roles' => ['relay'], 'relay' => ['clients' => 3]])
            ->assertOk()
            ->assertExactJson([
                'ok' => true,
                'status' => 'active',
                'sync_interval_s' => 10,
                'budget' => ['accept_new' => true, 'min_tier' => 'free', 'used_gb' => 0.0, 'quota_gb' => 1000],
                'relay' => ['enabled' => true, 'max_clients' => 2000],
                'exit' => [
                    'enabled' => false, 'mode' => 'game', 'address' => null, 'listen_port' => null,
                    'peers' => [], 'allow_cidrs' => [],
                ],
            ]);

        $node->refresh();
        $this->assertNotNull($node->last_seen_at);
        $this->assertSame(3, $node->current_clients);
        $this->assertSame('lvnode/1.0.0', $node->last_sync['agent']);
    }

    public function test_a_bad_signature_is_refused_without_touching_anything(): void
    {
        $node = $this->node();

        $this->sync($node, ['relay' => ['clients' => 9]], secret: 'wrong-secret')
            ->assertStatus(401)
            ->assertExactJson(['ok' => false, 'error' => 'unauthorized']);

        // Unknown node, malformed headers: the same answer.
        $this->sync($node, [], nodeId: '999')->assertStatus(401)->assertJsonPath('error', 'unauthorized');
        $this->sync($node, [], nodeId: 'th-bkk-1')->assertStatus(401);
        $this->postJson('/api/v1/localvpn/node/sync', [])->assertStatus(401);

        $this->assertNull($node->refresh()->last_seen_at);
        $this->assertSame(0, (int) $node->last_sync_time);
    }

    public function test_a_time_more_than_five_minutes_off_is_refused(): void
    {
        $node = $this->node();

        $this->sync($node, [], time() - 301)->assertStatus(401)->assertJsonPath('error', 'stale-time')->assertJsonStructure(['server_time']);
        $this->sync($node, [], time() + 400)->assertStatus(401)->assertJsonPath('error', 'stale-time');
        $this->sync($node, [], time() - 250)->assertOk();
    }

    public function test_a_replayed_or_older_time_is_refused(): void
    {
        $node = $this->node();
        $time = time();
        $body = ['nic' => ['rx' => 10, 'tx' => 20]];

        $this->sync($node, $body, $time)->assertOk();
        // The exact same request captured off the wire, and an older one.
        $this->sync($node, $body, $time)->assertStatus(409)->assertJsonPath('error', 'replayed');
        $this->sync($node, $body, $time - 1)->assertStatus(409);

        $this->assertSame($time, (int) $node->refresh()->last_sync_time);
        $this->assertSame(20, (int) DB::table('vpn_node_usage_daily')->where('node_id', $node->id)->value('nic_tx'));

        $this->sync($node, $body, $time + 1)->assertOk();
        $this->assertSame(40, (int) DB::table('vpn_node_usage_daily')->where('node_id', $node->id)->value('nic_tx'));
    }

    // ── Usage ────────────────────────────────────────────────────────

    public function test_usage_deltas_are_added_up_per_node_and_per_device(): void
    {
        $node = $this->node(['roles' => ['relay', 'game']]);
        $server = $this->exitServer($node);
        $alice = $this->member('alice-pc', '10.10.0.2');
        $this->member('bob-pc', '10.10.0.3');
        $aliceLicense = $this->license('alice-pc', 'free', null);
        $carolLicense = $this->license('carol-phone', 'monthly');
        $carol = WireguardClient::create(['server_id' => $server->id, 'machine_id' => 'carol-phone', 'public_key' => self::KEY_A, 'assigned_ip' => '10.66.0.2', 'is_connected' => true]);

        $net = $alice->network_id;
        $first = [
            'nic' => ['rx' => 1000, 'tx' => 3000],
            'relay' => ['clients' => 4, 'rx' => 500, 'tx' => 700, 'usage' => [
                ['net' => $net, 'vip' => '10.10.0.2', 'bytes' => 4096],
                ['net' => $net, 'vip' => '10.10.0.3', 'bytes' => 100],
                ['net' => $net, 'vip' => '10.10.0.2', 'bytes' => 4], // same device twice in one sync
                ['net' => $net, 'vip' => '10.10.0.99', 'bytes' => 50], // nobody
                ['net' => 'x', 'vip' => 'nope', 'bytes' => 1], // garbage row: skipped, not fatal
            ]],
            'exit' => ['peers' => 1, 'usage' => [
                ['pub' => self::KEY_A, 'rx' => 10, 'tx' => 20],
                ['pub' => self::KEY_C, 'rx' => 5, 'tx' => 5], // not a client of this node
            ]],
        ];
        $this->sync($node, $first)->assertOk();
        $this->sync($node, ['nic' => ['rx' => 1, 'tx' => 2], 'relay' => ['clients' => 2, 'usage' => [
            ['net' => $net, 'vip' => '10.10.0.2', 'bytes' => 1000],
        ]]])->assertOk();

        $today = FleetClock::today();
        $day = DB::table('vpn_node_usage_daily')->where('node_id', $node->id)->first();
        $this->assertSame($today, substr($day->date, 0, 10));
        $this->assertSame([1001, 3002, 1200, 40, 5], [(int) $day->nic_rx, (int) $day->nic_tx, (int) $day->relay_bytes, (int) $day->exit_bytes, (int) $day->peak_clients]);

        $usage = DB::table('vpn_user_usage_daily')->where('date', $today)->orderBy('kind')->orderBy('machine_id')->get()
            ->map(fn ($r) => [$r->kind, $r->machine_id, (int) $r->bytes, $r->license_key_id === null ? null : (int) $r->license_key_id])
            ->all();
        $this->assertSame([
            ['exit', 'carol-phone', 30, $carolLicense->id],
            ['relay', 'alice-pc', 5100, $aliceLicense->id],
            ['relay', 'bob-pc', 100, null],
        ], $usage);

        $carol->refresh();
        $this->assertSame([10, 20], [$carol->bytes_rx, $carol->bytes_tx]);
        $this->assertNotNull($carol->last_handshake_at);
    }

    // ── WireGuard exit ───────────────────────────────────────────────

    public function test_the_node_key_and_endpoint_go_into_its_exit_row_and_the_answer_lists_allowed_peers(): void
    {
        $node = $this->node(['roles' => ['game'], 'country' => 'SG', 'host' => '198.51.100.7']);
        $server = $this->exitServer($node);
        VpnGameProfile::create(['name' => 'RoV', 'slug' => 'rov', 'region' => 'SEA', 'cidrs' => "43.229.64.0/18\n# note\n103.6.0.0/16"]);
        VpnGameProfile::create(['name' => 'Off', 'slug' => 'off', 'cidrs' => '8.8.8.0/24', 'enabled' => false]);

        $this->license('pro-pc', 'yearly');
        $this->license('trial-pc', 'demo', '+3 days');
        $this->license('free-pc', 'free', null);
        $this->license('capped-pc', 'demo', '+3 days');
        foreach ([['pro-pc', self::KEY_A, '10.66.0.2'], ['trial-pc', self::KEY_B, '10.66.0.3'], ['free-pc', self::KEY_C, '10.66.0.4'], ['capped-pc', self::NODE_KEY, '10.66.0.5']] as [$machine, $key, $ip]) {
            WireguardClient::create(['server_id' => $server->id, 'machine_id' => $machine, 'public_key' => $key, 'assigned_ip' => $ip, 'is_connected' => true]);
        }
        WireguardClient::create(['server_id' => $server->id, 'machine_id' => 'gone-pc', 'public_key' => 'DDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDA=', 'assigned_ip' => '10.66.0.6', 'is_connected' => false]);
        // Trial exit cap is 1500 MB a day; this device used it up.
        DB::table('vpn_user_usage_daily')->insert(['date' => FleetClock::today(), 'kind' => 'exit', 'machine_id' => 'capped-pc', 'bytes' => 1500 * 1048576]);

        $response = $this->sync($node, ['wg_public_key' => self::NODE_KEY, 'wg_port' => 51999])->assertOk();

        $response->assertJsonPath('exit.enabled', true)
            ->assertJsonPath('exit.mode', 'game')
            ->assertJsonPath('exit.address', '10.66.0.1/24')
            ->assertJsonPath('exit.listen_port', 51820)
            ->assertJsonPath('exit.allow_cidrs', ['103.6.0.0/16', '43.229.64.0/18'])
            ->assertJsonPath('exit.peers', [
                ['pub' => self::KEY_A, 'ip' => '10.66.0.2', 'kbps' => 0],
                ['pub' => self::KEY_B, 'ip' => '10.66.0.3', 'kbps' => 0],
            ])
            ->assertJsonPath('relay.enabled', false);

        $server->refresh();
        $this->assertSame(self::NODE_KEY, $server->public_key);
        $this->assertSame('198.51.100.7:51999', $server->endpoint);
        $this->assertTrue($server->is_healthy);
        $this->assertNull($server->private_key);
        $this->assertSame(51999, $node->refresh()->wg_port);
        Process::assertNothingRan();
    }

    public function test_a_vpn_node_carries_only_pro_devices_and_a_disabled_node_carries_nobody(): void
    {
        $node = $this->node(['roles' => ['vpn']]);
        $server = $this->exitServer($node);
        $this->license('pro-pc', 'lifetime', null);
        $this->license('trial-pc', 'demo', '+3 days');
        WireguardClient::create(['server_id' => $server->id, 'machine_id' => 'pro-pc', 'public_key' => self::KEY_A, 'assigned_ip' => '10.66.0.2', 'is_connected' => true]);
        WireguardClient::create(['server_id' => $server->id, 'machine_id' => 'trial-pc', 'public_key' => self::KEY_B, 'assigned_ip' => '10.66.0.3', 'is_connected' => true]);

        $this->sync($node, [])->assertOk()
            ->assertJsonPath('exit.mode', 'vpn')
            ->assertJsonPath('exit.peers', [['pub' => self::KEY_A, 'ip' => '10.66.0.2', 'kbps' => 0]]);

        $node->update(['status' => VpnNode::STATUS_DISABLED]);
        $this->sync($node, [])->assertOk()
            ->assertJsonPath('status', 'disabled')
            ->assertJsonPath('relay.enabled', false)
            ->assertJsonPath('exit.enabled', false)
            ->assertJsonPath('exit.peers', [])
            ->assertJsonPath('budget.accept_new', false);
    }
}
