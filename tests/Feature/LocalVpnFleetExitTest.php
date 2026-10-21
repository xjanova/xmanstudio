<?php

namespace Tests\Feature;

use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\VpnGameProfile;
use App\Models\VpnNode;
use App\Models\WireguardClient;
use App\Models\WireguardServer;
use App\Support\LocalVpnFleet\FleetClock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * POST /api/v1/localvpn/wireguard/register for exits that run on fleet nodes:
 * the fleet policy decides (vpn = Pro only, game = tiers with game: true),
 * refusals carry a Thai `error` the app shows, nothing runs `sudo wg` here,
 * and the web server's own exit keeps working as before.
 */
class LocalVpnFleetExitTest extends TestCase
{
    use RefreshDatabase;

    private const CLIENT_KEY = 'QUJDREVGR0hJSktMTU5PUFFSU1RVVldYWVowMTIzNDU=';

    private const NODE_KEY = 'NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNA=';

    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();
    }

    private function exitNode(string $role, string $country = 'SG'): WireguardServer
    {
        $node = new VpnNode([
            'name' => "{$role} node",
            'code' => "{$role}-" . strtolower($country),
            'country' => $country,
            'host' => '198.51.100.7',
            'relay_port' => 51821,
            'wg_port' => 51820,
            'wg_public_key' => self::NODE_KEY,
            'roles' => [$role],
            'status' => VpnNode::STATUS_ACTIVE,
            'included_gb' => 1000,
            'billing_day' => 1,
            'billing_mode' => 'out',
            'max_clients' => 2000,
            'weight' => 100,
        ]);
        $node->secret = 'node-secret';
        $node->save();
        $node->forceFill(['last_seen_at' => now()])->save();

        return WireguardServer::create([
            'vpn_node_id' => $node->id,
            'name' => "{$role} {$country}",
            'country_code' => $country,
            'country_name' => $country,
            'endpoint' => '198.51.100.7:51820',
            'public_key' => self::NODE_KEY,
            'private_key' => null,
            'address' => '10.66.0.1/24',
            'dns' => '1.1.1.1, 8.8.8.8',
            'listen_port' => 51820,
            'is_active' => true,
            'is_healthy' => true,
        ]);
    }

    private function license(string $machineId, string $type): void
    {
        LicenseKey::create([
            'product_id' => Product::where('slug', 'localvpn')->value('id'),
            'license_key' => strtoupper($type) . '-' . $machineId,
            'license_type' => $type,
            'status' => 'active',
            'machine_id' => $machineId,
            'expires_at' => now()->addDays(10),
        ]);
    }

    private function register(string $machineId, array $extra = [])
    {
        return $this->postJson('/api/v1/localvpn/wireguard/register', array_merge([
            'machine_id' => $machineId,
            'public_key' => self::CLIENT_KEY,
        ], $extra));
    }

    private function gameProfile(): void
    {
        VpnGameProfile::create(['name' => 'RoV', 'slug' => 'rov', 'region' => 'SEA', 'cidrs' => "43.229.64.0/18\n103.6.0.0/16"]);
    }

    public function test_free_users_are_refused_a_game_exit_in_thai(): void
    {
        $server = $this->exitNode('game');
        $this->gameProfile();

        $this->register('free-pc', ['server_id' => $server->id])
            ->assertStatus(403)
            ->assertExactJson(['success' => false, 'error' => 'ฟีเจอร์ลดปิงสำหรับสมาชิก Pro']);

        // Asking by country gets the same answer instead of a silent fallback elsewhere.
        $this->register('free-pc', ['country_code' => 'SG'])
            ->assertStatus(403)
            ->assertJsonPath('error', 'ฟีเจอร์ลดปิงสำหรับสมาชิก Pro');

        $this->assertSame(0, WireguardClient::count());
    }

    public function test_a_trial_user_gets_a_game_exit_that_routes_only_game_ranges(): void
    {
        $server = $this->exitNode('game');
        $this->gameProfile();
        $this->license('trial-pc', 'demo');

        $response = $this->register('trial-pc', ['country_code' => 'SG'])->assertOk();

        $response->assertJsonPath('config.peer.public_key', self::NODE_KEY)
            ->assertJsonPath('config.peer.endpoint', '198.51.100.7:51820')
            ->assertJsonPath('config.peer.allowed_ips', '103.6.0.0/16, 43.229.64.0/18')
            ->assertJsonPath('config.interface.address', '10.66.0.2/32')
            ->assertJsonPath('config.interface.dns', '1.1.1.1, 8.8.8.8')
            ->assertJsonPath('server.id', $server->id)
            ->assertJsonPath('server.mode', 'game');

        $this->assertDatabaseHas('wireguard_clients', ['server_id' => $server->id, 'machine_id' => 'trial-pc', 'is_connected' => true]);
        // The node pulls its peers from the database; no sudo wg on the web server.
        Process::assertNothingRan();

        // Disconnecting is a database change too.
        $this->postJson('/api/v1/localvpn/wireguard/disconnect', ['machine_id' => 'trial-pc'])->assertOk();
        $this->assertDatabaseHas('wireguard_clients', ['machine_id' => 'trial-pc', 'is_connected' => false]);
        Process::assertNothingRan();
    }

    public function test_a_full_vpn_node_is_for_pro_only(): void
    {
        $server = $this->exitNode('vpn', 'JP');
        $this->license('trial-pc', 'demo');
        $this->license('pro-pc', 'monthly');

        $this->register('trial-pc', ['server_id' => $server->id])
            ->assertStatus(403)
            ->assertJsonPath('error', 'VPN เต็มรูปแบบสำหรับสมาชิก Pro');

        $this->register('pro-pc', ['server_id' => $server->id])
            ->assertOk()
            ->assertJsonPath('config.peer.allowed_ips', '0.0.0.0/0, ::/0')
            ->assertJsonPath('server.mode', 'vpn');
    }

    public function test_daily_exit_cap_quiet_node_and_missing_game_list_are_refused(): void
    {
        $server = $this->exitNode('game');
        $this->license('trial-pc', 'demo');

        // No game profiles yet: a game exit would route nothing.
        $this->register('trial-pc', ['server_id' => $server->id])->assertStatus(503)->assertJsonPath('success', false);

        $this->gameProfile();
        DB::table('vpn_user_usage_daily')->insert(['date' => FleetClock::today(), 'kind' => 'exit', 'machine_id' => 'trial-pc', 'bytes' => 1500 * 1048576]);
        $this->register('trial-pc', ['server_id' => $server->id])
            ->assertStatus(429)
            ->assertJsonPath('error', 'ใช้โควตาลดปิงของวันนี้ครบแล้ว พรุ่งนี้ใช้ได้ใหม่');

        DB::table('vpn_user_usage_daily')->delete();
        $server->node->forceFill(['last_seen_at' => now()->subMinutes(5)])->save();
        $this->register('trial-pc', ['server_id' => $server->id])->assertStatus(503);
    }

    public function test_the_server_list_marks_fleet_exits_by_tier(): void
    {
        $game = $this->exitNode('game');
        $vpn = $this->exitNode('vpn', 'JP');
        $this->license('trial-pc', 'demo');

        $response = $this->getJson('/api/v1/localvpn/wireguard/servers?machine_id=trial-pc')->assertOk();

        $this->assertSame([$game->id], array_column($response->json('servers'), 'id'));
        $this->assertSame([$vpn->id], array_column($response->json('locked_servers'), 'id'));
        $this->assertSame('game', $response->json('servers.0.mode'));
    }

    public function test_the_health_check_leaves_node_exits_to_their_nodes(): void
    {
        $server = $this->exitNode('game');
        $client = fn (string $machine, string $ip, array $times) => WireguardClient::create(array_merge([
            'server_id' => $server->id, 'machine_id' => $machine, 'public_key' => self::CLIENT_KEY, 'assigned_ip' => $ip, 'is_connected' => true,
        ], $times));
        $live = $client('live-pc', '10.66.0.2', ['connected_at' => now()->subHour(), 'last_handshake_at' => now()->subMinute()]);
        $idle = $client('idle-pc', '10.66.0.3', ['connected_at' => now()->subHour(), 'last_handshake_at' => now()->subMinutes(20)]);
        $fresh = $client('fresh-pc', '10.66.0.4', ['connected_at' => now()->subMinutes(2)]);

        $this->artisan('wireguard:health-check')->assertSuccessful();

        // No `wg show` here: it would list the web server's own wg0 and drop everyone on the node.
        Process::assertNothingRan();
        $this->assertSame([true, false, true], [$live->refresh()->is_connected, $idle->refresh()->is_connected, $fresh->refresh()->is_connected]);
        $this->assertTrue($server->refresh()->is_healthy);

        $server->node->forceFill(['last_seen_at' => now()->subMinutes(3)])->save();
        $this->artisan('wireguard:health-check')->assertSuccessful();
        $this->assertFalse($server->refresh()->is_healthy);
    }

    public function test_the_local_exit_still_uses_sudo_and_the_free_country_rule(): void
    {
        $local = WireguardServer::create([
            'name' => 'Thailand',
            'country_code' => 'TH',
            'country_name' => 'Thailand',
            'endpoint' => 'vpn.example.com:51820',
            'public_key' => self::NODE_KEY,
            'private_key' => 'server-private-key',
            'address' => '10.20.0.1/24',
            'is_active' => true,
            'is_healthy' => true,
        ]);

        $this->register('free-pc', ['country_code' => 'TH'])
            ->assertOk()
            ->assertJsonPath('server.id', $local->id)
            ->assertJsonPath('server.mode', null)
            ->assertJsonPath('config.peer.allowed_ips', '0.0.0.0/0, ::/0');

        Process::assertRan(fn ($process) => str_contains($process->command, 'wg set') && str_contains($process->command, 'allowed-ips'));

        $this->register('free-pc', ['country_code' => 'FR'])->assertStatus(403);
    }
}
