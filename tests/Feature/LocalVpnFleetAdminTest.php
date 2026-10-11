<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Models\VpnGameProfile;
use App\Models\VpnNode;
use App\Models\WireguardServer;
use App\Support\LocalVpnFleet\FleetPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * LocalVPN → Fleet in the admin panel: nodes (secret shown once, stored
 * encrypted, never listed), policy validation, game profile CIDR validation,
 * the cost page, and admin-only access.
 */
class LocalVpnFleetAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function nodeForm(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Bangkok 1',
            'code' => 'th-bkk-1',
            'country' => 'th',
            'city' => 'Bangkok',
            'provider' => 'Vultr',
            'host' => '203.0.113.10',
            'relay_port' => 51821,
            'wg_port' => 51820,
            'roles' => ['relay', 'game'],
            'status' => 'active',
            'monthly_cost_thb' => 350,
            'included_gb' => 2000,
            'overage_thb_per_gb' => 0.35,
            'billing_day' => 5,
            'billing_mode' => 'out',
            'allow_overage' => '0',
            'max_clients' => 1500,
            'weight' => 100,
            'wg_address' => '10.66.0.1/24',
            'wg_dns' => '1.1.1.1, 8.8.8.8',
            'wg_max_clients' => 250,
        ], $overrides);
    }

    public function test_creating_a_node_shows_its_secret_once_and_stores_it_encrypted(): void
    {
        $this->actingAs($this->admin)->get(route('admin.localvpn.fleet.nodes.create'))
            ->assertOk()->assertSee('เพิ่มโหนดและสร้างรหัสลับ');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.localvpn.fleet.nodes.store'), $this->nodeForm())
            ->assertOk()
            ->assertHeader('Cache-Control');

        $node = VpnNode::firstOrFail();
        $secret = $node->secret;

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $secret);
        $this->assertSame('TH', $node->country);
        $this->assertSame(['relay', 'game'], $node->roles);
        $response->assertSee($secret)
            ->assertSee("sudo bash install.sh --id {$node->id} --secret {$secret} --roles relay,game", false);

        // Encrypted at rest.
        $raw = DB::table('vpn_nodes')->where('id', $node->id)->value('secret');
        $this->assertNotSame($secret, $raw);
        $this->assertStringNotContainsString($secret, $raw);

        // The node's WireGuard exit row exists, holds no private key, waits for the node's key.
        $server = WireguardServer::where('vpn_node_id', $node->id)->firstOrFail();
        $this->assertSame('10.66.0.1/24', $server->address);
        $this->assertSame('203.0.113.10:51820', $server->endpoint);
        $this->assertSame('TH', $server->country_code);
        $this->assertNull($server->private_key);
        $this->assertFalse($server->is_healthy);

        // Never again: not in the list, not on the edit page.
        $this->actingAs($this->admin)->get(route('admin.localvpn.fleet.nodes'))
            ->assertOk()->assertSee('th-bkk-1')->assertDontSee($secret);
        $this->actingAs($this->admin)->get(route('admin.localvpn.fleet.nodes.edit', $node))
            ->assertOk()->assertDontSee($secret);
    }

    public function test_rotating_the_secret_replaces_it_and_shows_the_new_one_once(): void
    {
        $this->actingAs($this->admin)->post(route('admin.localvpn.fleet.nodes.store'), $this->nodeForm(['roles' => ['relay']]));
        $node = VpnNode::firstOrFail();
        $old = $node->secret;

        $response = $this->actingAs($this->admin)->post(route('admin.localvpn.fleet.nodes.rotate', $node))->assertOk();

        $new = $node->refresh()->secret;
        $this->assertNotSame($old, $new);
        $response->assertSee($new)->assertDontSee($old);
    }

    public function test_node_input_is_validated(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.localvpn.fleet.nodes.store'), $this->nodeForm([
                'code' => 'TH BKK!',
                'host' => 'not a host',
                'relay_port' => 70000,
                'roles' => ['relay', 'game', 'socks'],
                'billing_day' => 31,
                'billing_mode' => 'weekly',
                'included_gb' => 0,
                'wg_address' => '8.8.8.1/24',
                'wg_dns' => '1.1.1.1, nope',
            ]))
            ->assertSessionHasErrors(['code', 'host', 'relay_port', 'roles.2', 'billing_day', 'billing_mode', 'included_gb', 'wg_address', 'wg_dns']);

        foreach (['10.66.0.0/24' => 'เครือข่าย', '10.66.0.255/24' => 'broadcast', '10.66.0.1/30' => '/16', '10.66.0.1' => 'IP/prefix'] as $address => $hint) {
            $this->actingAs($this->admin)
                ->post(route('admin.localvpn.fleet.nodes.store'), $this->nodeForm(['wg_address' => $address]))
                ->assertSessionHasErrors(['wg_address']);
            $this->assertStringContainsString($hint, session('errors')->first('wg_address'), $address);
        }

        // A game/vpn node needs its exit subnet; a relay-only node doesn't.
        $this->actingAs($this->admin)
            ->post(route('admin.localvpn.fleet.nodes.store'), $this->nodeForm(['wg_address' => '']))
            ->assertSessionHasErrors(['wg_address']);
        $this->actingAs($this->admin)
            ->post(route('admin.localvpn.fleet.nodes.store'), $this->nodeForm(['roles' => ['relay'], 'wg_address' => '']))
            ->assertOk();

        $this->assertSame(1, VpnNode::count());
        $this->assertSame(0, WireguardServer::count());
    }

    public function test_editing_toggling_and_deleting_a_node(): void
    {
        $this->actingAs($this->admin)->post(route('admin.localvpn.fleet.nodes.store'), $this->nodeForm());
        $node = VpnNode::firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.localvpn.fleet.nodes.update', $node), $this->nodeForm(['name' => 'Bangkok one', 'roles' => ['relay'], 'status' => 'draining']))
            ->assertRedirect(route('admin.localvpn.fleet.nodes'));
        $node->refresh();
        $this->assertSame(['Bangkok one', ['relay'], 'draining'], [$node->name, $node->roles, $node->status]);
        // Dropping the exit roles switches its WireGuard row off.
        $this->assertFalse(WireguardServer::where('vpn_node_id', $node->id)->firstOrFail()->is_active);

        $this->actingAs($this->admin)->post(route('admin.localvpn.fleet.nodes.toggle', $node))->assertRedirect();
        $this->assertSame('disabled', $node->refresh()->status);
        $this->actingAs($this->admin)->post(route('admin.localvpn.fleet.nodes.toggle', $node))->assertRedirect();
        $this->assertSame('active', $node->refresh()->status);

        $this->actingAs($this->admin)->delete(route('admin.localvpn.fleet.nodes.delete', $node))->assertRedirect(route('admin.localvpn.fleet.nodes'));
        $this->assertSame(0, VpnNode::count());
        $this->assertSame(0, WireguardServer::count());
    }

    public function test_policy_is_validated_and_saved(): void
    {
        $tiers = [
            'free' => ['relay_kbps' => 1000, 'relay_daily_mb' => 200, 'game' => '0', 'exit_daily_mb' => 0],
            'trial' => ['relay_kbps' => 8000, 'relay_daily_mb' => 3000, 'game' => '1', 'exit_daily_mb' => 1000],
            'pro' => ['relay_kbps' => 0, 'relay_daily_mb' => 0, 'game' => '1', 'exit_daily_mb' => 5000],
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.localvpn.fleet.policy.update'), [
                'tiers' => $tiers,
                'budget' => ['monthly_thb' => 2500, 'free_cut_pct' => 90, 'trial_cut_pct' => 85, 'pro_cut_pct' => 101],
            ])
            ->assertSessionHasErrors(['budget.trial_cut_pct', 'budget.pro_cut_pct']);
        $this->assertNull(Setting::getValue(FleetPolicy::SETTING_KEY));

        $this->actingAs($this->admin)
            ->post(route('admin.localvpn.fleet.policy.update'), [
                'tiers' => array_replace_recursive($tiers, ['free' => ['relay_kbps' => -1]]),
                'budget' => ['monthly_thb' => 2500, 'free_cut_pct' => 70, 'trial_cut_pct' => 85, 'pro_cut_pct' => 95],
            ])
            ->assertSessionHasErrors(['tiers.free.relay_kbps']);

        $this->actingAs($this->admin)
            ->post(route('admin.localvpn.fleet.policy.update'), [
                'tiers' => $tiers,
                'budget' => ['monthly_thb' => 2500, 'free_cut_pct' => 70, 'trial_cut_pct' => 85, 'pro_cut_pct' => 95],
            ])
            ->assertSessionHasNoErrors();

        $policy = FleetPolicy::current();
        $this->assertSame(['relay_kbps' => 1000, 'relay_daily_mb' => 200, 'game' => false, 'exit_daily_mb' => 0], $policy['tiers']['free']);
        $this->assertSame(0, $policy['tiers']['pro']['relay_kbps']);
        $this->assertSame(['monthly_thb' => 2500.0, 'free_cut_pct' => 70, 'trial_cut_pct' => 85, 'pro_cut_pct' => 95], $policy['budget']);

        $this->actingAs($this->admin)->get(route('admin.localvpn.fleet.policy'))->assertOk()->assertSee('ขีดจำกัดตามระดับสมาชิก');
    }

    public function test_game_profile_cidrs_are_validated_on_save(): void
    {
        $profile = ['name' => 'RoV', 'slug' => 'rov-sea', 'region' => 'SEA', 'enabled' => '1', 'sort' => 1];

        $this->actingAs($this->admin)
            ->post(route('admin.localvpn.fleet.game-profiles.store'), $profile + ['cidrs' => "43.229.64.0/18\n10.0.0.0/8\n0.0.0.0/0\n2001:db8::/32\n43.229.64.9/18"])
            ->assertSessionHasErrorsIn('profileCreate', ['cidrs']);
        $errors = session('errors')->getBag('profileCreate')->get('cidrs');
        $this->assertCount(4, $errors);
        $this->assertSame(0, VpnGameProfile::count());

        $this->actingAs($this->admin)
            ->post(route('admin.localvpn.fleet.game-profiles.store'), $profile + ['cidrs' => "# only comments\n"])
            ->assertSessionHasErrorsIn('profileCreate', ['cidrs']);

        $this->actingAs($this->admin)
            ->post(route('admin.localvpn.fleet.game-profiles.store'), $profile + ['cidrs' => "# Garena SEA\r\n43.229.64.0/18\r\n103.6.0.0/16"])
            ->assertSessionHasNoErrors();
        $saved = VpnGameProfile::firstOrFail();
        $this->assertSame(['43.229.64.0/18', '103.6.0.0/16'], $saved->cidrList());

        // The probe must sit inside the ranges the exit forwards.
        $this->actingAs($this->admin)
            ->put(route('admin.localvpn.fleet.game-profiles.update', $saved), $profile + ['cidrs' => '43.229.64.0/18', 'probe_host' => '8.8.8.8:443'])
            ->assertSessionHasErrorsIn('profileUpdate', ['probe_host']);
        $this->actingAs($this->admin)
            ->put(route('admin.localvpn.fleet.game-profiles.update', $saved), $profile + ['cidrs' => '43.229.64.0/18', 'probe_host' => 'login.example.com'])
            ->assertSessionHasErrorsIn('profileUpdate', ['probe_host']);
        $this->actingAs($this->admin)
            ->put(route('admin.localvpn.fleet.game-profiles.update', $saved), $profile + ['cidrs' => "43.229.64.0/18\n103.6.0.0/16", 'probe_host' => '43.229.65.10:7000'])
            ->assertSessionHasNoErrors();
        $this->assertSame('43.229.65.10:7000', $saved->refresh()->probe_host);

        $this->actingAs($this->admin)
            ->put(route('admin.localvpn.fleet.game-profiles.update', $saved), $profile + ['cidrs' => '172.16.0.0/12'])
            ->assertSessionHasErrorsIn('profileUpdate', ['cidrs']);

        $this->actingAs($this->admin)
            ->put(route('admin.localvpn.fleet.game-profiles.update', $saved), array_merge($profile, ['enabled' => '0', 'cidrs' => '43.229.65.0/24']))
            ->assertSessionHasNoErrors();
        $this->assertFalse($saved->refresh()->enabled);
        $this->getJson('/api/v1/localvpn/game-profiles')->assertExactJson(['profiles' => []]);

        $this->actingAs($this->admin)->delete(route('admin.localvpn.fleet.game-profiles.delete', $saved))->assertRedirect();
        $this->assertSame(0, VpnGameProfile::count());
    }

    public function test_the_cost_page_renders_totals_days_and_top_users(): void
    {
        $this->actingAs($this->admin)->post(route('admin.localvpn.fleet.nodes.store'), $this->nodeForm(['roles' => ['relay']]));
        $node = VpnNode::firstOrFail();
        $today = \App\Support\LocalVpnFleet\FleetClock::today();
        DB::table('vpn_node_usage_daily')->insert(['node_id' => $node->id, 'date' => $today, 'nic_tx' => 5_000_000_000, 'nic_rx' => 1_000_000_000]);
        DB::table('vpn_user_usage_daily')->insert(['date' => $today, 'kind' => 'relay', 'machine_id' => 'heavy-user-pc', 'bytes' => 3_000_000_000]);

        $this->actingAs($this->admin)->get(route('admin.localvpn.fleet.cost'))
            ->assertOk()
            ->assertSee('th-bkk-1')
            ->assertSee('heavy-user-pc')
            ->assertSee('3.00 GB')
            ->assertSee('fleetDailyChart');

        $this->actingAs($this->admin)->get(route('admin.localvpn.fleet.nodes'))->assertOk()->assertSee('5.0 / 2,000', false);
    }

    public function test_only_admins_reach_the_fleet_pages(): void
    {
        $customer = User::factory()->create(['role' => 'user']);
        $urls = [route('admin.localvpn.fleet.nodes'), route('admin.localvpn.fleet.policy'), route('admin.localvpn.fleet.cost')];

        foreach ($urls as $url) {
            $this->get($url)->assertRedirect();
        }
        foreach ($urls as $url) {
            $this->assertNotSame(200, $this->actingAs($customer)->get($url)->getStatusCode(), $url);
        }

        $this->actingAs($customer)->post(route('admin.localvpn.fleet.nodes.store'), $this->nodeForm());
        $this->assertSame(0, VpnNode::count());
    }
}
