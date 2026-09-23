<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Models\VpsInstance;
use App\Models\VpsPlan;
use App\Support\VpsFirewall;
use App\Support\VpsLabels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\FakesSupplierApi;
use Tests\TestCase;

/**
 * The control panel's tabs and the tools behind them: firewall, SSH keys,
 * reverse DNS, resolvers, malware scanner, recovery mode, panel password.
 *
 * Every tool acts on the customer's own machine only — and the firewall on
 * the one firewall recorded for that rental, whatever a form might carry.
 */
class VpsPanelToolsTest extends TestCase
{
    use FakesSupplierApi;
    use RefreshDatabase;

    protected User $user;

    protected VpsInstance $server;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setValue('hostinger_api_token', 'test-token');
        Cache::flush();
        RateLimiter::clear('hostinger-api');

        User::factory()->create(['role' => 'super_admin']);
        $this->user = User::factory()->create();

        $plan = VpsPlan::create([
            'slug' => 'kvm2', 'remote_item_id' => 'hostingerinth-vps-kvm2', 'remote_name' => 'KVM 2',
            'name' => 'VPS Business', 'category' => 'vps', 'cpus' => 2, 'memory_mb' => 8192,
            'disk_mb' => 102400, 'bandwidth_mb' => 8192000, 'network_mbps' => 300, 'is_active' => true,
            'prices' => ['1m' => ['item_id' => 'hostingerinth-vps-kvm2-thb-1m', 'currency' => 'THB', 'first' => 45900, 'renew' => 72900, 'months' => 1]],
        ]);

        $this->server = VpsInstance::create([
            'user_id' => $this->user->id, 'vps_plan_id' => $plan->id, 'plan_name' => 'VPS Business',
            'specs' => $plan->specSnapshot(), 'period' => '1m', 'months' => 1, 'status' => VpsInstance::STATUS_ACTIVE,
            'hostname' => 'app.example.com', 'template_id' => 1077, 'template_name' => 'Ubuntu 24.04 LTS',
            'data_center_id' => 21, 'remote_vm_id' => 555001, 'remote_subscription_id' => 'sub_1', 'state' => 'running',
            'ipv4' => '203.0.113.10', 'auto_renew' => true, 'activated_at' => now()->subDays(3), 'expires_at' => now()->addDays(27),
        ]);

        $this->upstream($this->machine());
    }

    /** @return array<string,mixed> */
    protected function machine(array $vm = [], array $routes = []): array
    {
        return $routes + [
            'GET /api/vps/v1/virtual-machines/555001' => $vm + [
                'id' => 555001, 'subscription_id' => 'sub_1', 'hostname' => 'app.example.com', 'state' => 'running',
                'actions_lock' => 'unlocked', 'ns1' => '1.1.1.1', 'ns2' => '8.8.8.8', 'firewall_group_id' => null,
                'ipv4' => [['id' => 9001, 'address' => '203.0.113.10', 'ptr' => 'app.example.com']],
                'ipv6' => [['id' => 9002, 'address' => '2001:db8::10', 'ptr' => null]],
                'template' => ['id' => 1077, 'name' => 'Ubuntu 24.04 LTS'],
            ],
        ];
    }

    protected function show(string $tab): string
    {
        return $this->actingAs($this->user)
            ->get(route('customer.vps.show', ['id' => $this->server->id, 'tab' => $tab]))
            ->assertOk()
            ->getContent();
    }

    // =============================================================== tabs

    public function test_every_tab_renders_and_only_asks_upstream_for_what_it_shows(): void
    {
        $this->upstream($this->machine([], [
            'GET /api/vps/v1/virtual-machines/555001/actions' => ['data' => [['id' => 1, 'name' => 'ct_restart', 'state' => 'success', 'created_at' => now()->subHour()->toIso8601String()]]],
            'GET /api/vps/v1/virtual-machines/555001/public-keys' => ['data' => [['id' => 5, 'name' => 'vps' . $this->server->id . '-laptop', 'key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIOMqqnkVzrm0SdG6UOoqKLsabgH5C9okWi0dh2l9GKJl me@laptop']]],
            'GET /api/vps/v1/virtual-machines/555001/monarx' => ['scanned_files' => 1200, 'malicious' => 0, 'compromised' => 0, 'scan_started_at' => now()->subHour()->toIso8601String()],
        ]));

        $overview = $this->show('overview');
        $this->assertStringContainsString('ssh root@203.0.113.10', $overview);
        $this->assertStringContainsString('รีสตาร์ท', $overview);
        $this->assertCount(0, $this->sentTo('GET', '*/public-keys'));
        $this->assertCount(0, $this->sentTo('GET', '*/snapshot'));

        $network = $this->show('network');
        $this->assertStringContainsString(route('customer.vps.firewall', $this->server->id), $network);
        $this->assertStringContainsString('laptop', $network);
        $this->assertStringContainsString('SHA256:', $network);
        $this->assertStringContainsString('value="app.example.com"', $network);   // PTR of the IPv4
        $this->assertStringContainsString('1,200', $network);                    // files scanned

        $this->assertStringContainsString('name="confirm_hostname"', $this->show('system'));
        $this->assertStringContainsString('name="recovery_password"', $this->show('system'));
        $this->assertStringContainsString('รีสตาร์ท', $this->show('activity'));
        $this->assertStringContainsString(route('customer.vps.auto-renew', $this->server->id), $this->show('billing'));

        foreach (['overview', 'network', 'backups', 'system', 'activity', 'billing'] as $tab) {
            $this->assertStringNotContainsStringIgnoringCase('hostinger', strip_tags($this->show($tab)));
        }
    }

    public function test_an_unknown_tab_falls_back_to_the_overview(): void
    {
        $this->assertStringContainsString('ssh root@203.0.113.10', $this->show('../../etc'));
    }

    public function test_a_panel_template_offers_its_login_and_a_panel_password(): void
    {
        $this->upstream($this->machine(['template' => ['id' => 1130, 'name' => 'Ubuntu 24.04 with CloudPanel']]));

        $this->assertStringContainsString('https://203.0.113.10:8443', $this->show('overview'));
        $this->assertStringContainsString('name="panel_password"', $this->show('system'));
    }

    // ============================================================ ownership

    public function test_another_customers_server_is_a_404_for_every_new_tool(): void
    {
        $stranger = User::factory()->create();

        foreach (['firewall', 'ssh-keys', 'reverse-dns', 'resolvers', 'malware', 'recovery', 'panel-password'] as $name) {
            $this->actingAs($stranger)
                ->post(route('customer.vps.' . $name, $this->server->id), ['action' => 'enable'])
                ->assertNotFound();
        }

        $this->assertCount(0, array_filter($this->sent, fn ($r) => $r['method'] !== 'GET'));
    }

    // ============================================================= firewall

    public function test_turning_the_firewall_on_creates_one_firewall_seeds_ssh_and_activates_it(): void
    {
        $this->upstream($this->machine([], [
            'POST /api/vps/v1/firewall' => ['id' => 700, 'name' => 'xman-vps-1', 'rules' => []],
            'GET /api/vps/v1/firewall/700' => ['id' => 700, 'rules' => [], 'is_synced' => true],
            'PUT /api/vps/v1/firewall/700/rules' => ['id' => 700, 'rules' => []],
            'POST /api/vps/v1/firewall/700/activate/555001' => ['id' => 1, 'name' => 'firewall_activate', 'state' => 'sent'],
        ]));

        $this->actingAs($this->user)
            ->post(route('customer.vps.firewall', $this->server->id), ['action' => 'enable'])
            ->assertRedirect(route('customer.vps.show', ['id' => $this->server->id, 'tab' => 'network']))
            ->assertSessionHas('success');

        $this->assertSame(700, $this->server->fresh()->remote_firewall_id);
        $this->assertCount(1, $this->sentTo('POST', '/api/vps/v1/firewall'));

        // An empty firewall would drop SSH too: it is seeded before it is switched on.
        $seed = $this->sentTo('PUT', '/api/vps/v1/firewall/700/rules')[0]['body'];
        $this->assertTrue(VpsFirewall::allowsSsh($seed['rules']));
        $this->assertCount(1, $this->sentTo('POST', '/api/vps/v1/firewall/700/activate/555001'));

        // Again: the same firewall, not a second one.
        Cache::flush();
        $this->actingAs($this->user)->post(route('customer.vps.firewall', $this->server->id), ['action' => 'enable']);
        $this->assertCount(1, $this->sentTo('POST', '/api/vps/v1/firewall'));
        $this->assertCount(2, $this->sentTo('POST', '/api/vps/v1/firewall/700/activate/555001'));
    }

    public function test_rules_that_lock_ssh_out_need_an_explicit_yes(): void
    {
        $this->server->update(['remote_firewall_id' => 700]);
        $this->upstream($this->machine([], ['PUT /api/vps/v1/firewall/700/rules' => ['id' => 700, 'rules' => []]]));

        $webOnly = ['action' => 'save', 'rules' => [['protocol' => 'HTTPS', 'port' => '', 'source_detail' => '']]];

        $this->actingAs($this->user)
            ->post(route('customer.vps.firewall', $this->server->id), $webOnly)
            ->assertSessionHas('error');
        $this->assertCount(0, $this->sentTo('PUT', '/api/vps/v1/firewall/700/rules'));

        $this->actingAs($this->user)
            ->post(route('customer.vps.firewall', $this->server->id), $webOnly + ['confirm_no_ssh' => 1])
            ->assertSessionHas('success');

        $sent = $this->sentTo('PUT', '/api/vps/v1/firewall/700/rules')[0]['body'];
        $this->assertSame([['protocol' => 'HTTPS', 'port' => '443', 'source' => 'any', 'source_detail' => 'any']], $sent['rules']);
        $this->assertTrue($sent['sync']);
    }

    public function test_a_saved_rule_set_is_validated_and_goes_only_to_this_servers_firewall(): void
    {
        $this->server->update(['remote_firewall_id' => 700]);
        $this->upstream($this->machine([], ['PUT /api/vps/v1/firewall/*/rules' => ['id' => 700, 'rules' => []]]));

        $this->actingAs($this->user)->post(route('customer.vps.firewall', $this->server->id), [
            'action' => 'save',
            'rules' => [['protocol' => 'TCP', 'port' => '70000', 'source_detail' => '']],
        ])->assertSessionHas('error');

        $this->actingAs($this->user)->post(route('customer.vps.firewall', $this->server->id), [
            'action' => 'save',
            'firewall_id' => 1,  // never read
            'rules' => [
                ['protocol' => 'SSH', 'port' => '', 'source_detail' => '198.51.100.0/24'],
                ['protocol' => 'TCP', 'port' => '8000:8100', 'source_detail' => 'any'],
                ['protocol' => '', 'port' => '', 'source_detail' => ''],   // blank row dropped
            ],
        ])->assertSessionHas('success');

        $puts = $this->sentTo('PUT', '/api/vps/v1/firewall/*/rules');
        $this->assertCount(1, $puts);
        $this->assertSame('/api/vps/v1/firewall/700/rules', $puts[0]['path']);
        $this->assertSame([
            ['protocol' => 'SSH', 'port' => '22', 'source' => 'custom', 'source_detail' => '198.51.100.0/24'],
            ['protocol' => 'TCP', 'port' => '8000:8100', 'source' => 'any', 'source_detail' => 'any'],
        ], $puts[0]['body']['rules']);
    }

    /** "Turn on" acts on the SAVED rules: if those block SSH it needs its own yes, whatever the editor shows. */
    public function test_switching_on_saved_rules_without_ssh_needs_its_own_confirmation(): void
    {
        $this->server->update(['remote_firewall_id' => 700]);
        $this->upstream($this->machine([], [
            'GET /api/vps/v1/firewall/700' => ['id' => 700, 'is_synced' => true, 'rules' => [
                ['id' => 1, 'protocol' => 'HTTPS', 'port' => '443', 'source' => 'any', 'source_detail' => 'any'],
            ]],
            'POST /api/vps/v1/firewall/700/activate/555001' => ['id' => 1, 'state' => 'sent'],
        ]));

        $this->actingAs($this->user)
            ->post(route('customer.vps.firewall', $this->server->id), ['action' => 'enable'])
            ->assertSessionHas('error');
        $this->assertCount(0, $this->sentTo('POST', '/api/vps/v1/firewall/700/activate/555001'));

        // The page offers that confirmation on the button itself.
        $this->assertStringContainsString('Saved rules block SSH', $this->show('network'));

        $this->actingAs($this->user)
            ->post(route('customer.vps.firewall', $this->server->id), ['action' => 'enable', 'confirm_no_ssh' => 1])
            ->assertSessionHas('success');
        $this->assertCount(1, $this->sentTo('POST', '/api/vps/v1/firewall/700/activate/555001'));
    }

    /** A new firewall on a CloudPanel server keeps the panel reachable. */
    public function test_a_new_firewall_is_seeded_with_the_templates_own_ports(): void
    {
        $this->server->update(['template_name' => 'Ubuntu 24.04 with CloudPanel']);
        $this->upstream($this->machine(['template' => ['id' => 1130, 'name' => 'Ubuntu 24.04 with CloudPanel']], [
            'POST /api/vps/v1/firewall' => ['id' => 701, 'rules' => []],
            'GET /api/vps/v1/firewall/701' => ['id' => 701, 'rules' => []],
            'PUT /api/vps/v1/firewall/701/rules' => ['id' => 701, 'rules' => []],
            'POST /api/vps/v1/firewall/701/activate/555001' => ['id' => 1, 'state' => 'sent'],
        ]));

        // The confirmation names every door that will stay open.
        $this->assertStringContainsString('TCP 8443', $this->show('network'));

        $this->actingAs($this->user)->post(route('customer.vps.firewall', $this->server->id), ['action' => 'enable']);

        $seeded = collect($this->sentTo('PUT', '/api/vps/v1/firewall/701/rules')[0]['body']['rules']);
        $this->assertTrue($seeded->contains(fn ($r) => $r['protocol'] === 'TCP' && $r['port'] === '8443'));
        $this->assertTrue(VpsFirewall::allowsSsh($seeded->all()));
    }

    /** A create that timed out may have gone through: the next click adopts that firewall. */
    public function test_an_existing_firewall_for_this_server_is_adopted_not_duplicated(): void
    {
        $this->upstream($this->machine([], [
            'GET /api/vps/v1/firewall' => ['data' => [
                ['id' => 650, 'name' => 'owner-own-firewall', 'rules' => []],
                ['id' => 702, 'name' => 'xman-vps-' . $this->server->id, 'rules' => []],
            ], 'meta' => ['current_page' => 1]],
            'GET /api/vps/v1/firewall/702' => ['id' => 702, 'rules' => [['id' => 1, 'protocol' => 'SSH', 'port' => '22', 'source' => 'any', 'source_detail' => 'any']]],
            'POST /api/vps/v1/firewall/702/activate/555001' => ['id' => 1, 'state' => 'sent'],
        ]));

        $this->actingAs($this->user)
            ->post(route('customer.vps.firewall', $this->server->id), ['action' => 'enable'])
            ->assertSessionHas('success');

        $this->assertSame(702, $this->server->fresh()->remote_firewall_id);
        $this->assertCount(0, $this->sentTo('POST', '/api/vps/v1/firewall'));
    }

    /** Deleted upstream: the page offers a fresh start instead of being stuck on "couldn't read". */
    public function test_a_firewall_deleted_upstream_is_forgotten(): void
    {
        $this->server->update(['remote_firewall_id' => 703]);
        $this->upstream($this->machine([], [
            'GET /api/vps/v1/firewall/703' => Http::response(['message' => 'Not found'], 404),
        ]));

        $html = $this->show('network');

        $this->assertNull($this->server->fresh()->remote_firewall_id);
        $this->assertStringContainsString('name="action" value="enable"', $html);
    }

    public function test_malformed_rule_input_is_refused_not_a_crash(): void
    {
        $this->server->update(['remote_firewall_id' => 700]);

        $this->actingAs($this->user)
            ->post(route('customer.vps.firewall', $this->server->id), ['action' => 'save', 'rules' => [['protocol' => ['x'], 'port' => ['1']]]])
            ->assertRedirect();

        $this->actingAs($this->user)
            ->post(route('customer.vps.firewall', $this->server->id), ['action' => ['enable']])
            ->assertSessionHas('error');

        $this->assertCount(0, $this->sentTo('PUT', '*'));
    }

    public function test_turning_the_firewall_off(): void
    {
        $this->server->update(['remote_firewall_id' => 700]);
        $this->upstream($this->machine(['firewall_group_id' => 700], [
            'POST /api/vps/v1/firewall/700/deactivate/555001' => ['id' => 1, 'state' => 'sent'],
        ]));

        $this->actingAs($this->user)
            ->post(route('customer.vps.firewall', $this->server->id), ['action' => 'disable'])
            ->assertSessionHas('success');

        $this->assertCount(1, $this->sentTo('POST', '/api/vps/v1/firewall/700/deactivate/555001'));
    }

    // ============================================================ SSH keys

    public function test_an_ssh_key_is_stored_under_a_recognisable_name_and_attached_to_this_server_only(): void
    {
        $key = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIOMqqnkVzrm0SdG6UOoqKLsabgH5C9okWi0dh2l9GKJl me@laptop';

        $this->upstream($this->machine([], [
            'GET /api/vps/v1/virtual-machines/555001/public-keys' => ['data' => []],
            'POST /api/vps/v1/public-keys' => ['id' => 77, 'name' => 'x', 'key' => $key],
            'POST /api/vps/v1/public-keys/attach/555001' => ['id' => 1, 'state' => 'sent'],
        ]));

        $this->actingAs($this->user)
            ->post(route('customer.vps.ssh-keys', $this->server->id), ['key_name' => 'laptop', 'public_key' => $key])
            ->assertSessionHas('success');

        $created = $this->sentTo('POST', '/api/vps/v1/public-keys')[0]['body'];
        $this->assertSame('vps' . $this->server->id . '-laptop', $created['name']);
        $this->assertSame([77], $this->sentTo('POST', '/api/vps/v1/public-keys/attach/555001')[0]['body']['ids']);
    }

    public function test_a_private_key_or_garbage_is_refused_before_anything_is_sent(): void
    {
        $this->actingAs($this->user)
            ->post(route('customer.vps.ssh-keys', $this->server->id), ['public_key' => "-----BEGIN OPENSSH PRIVATE KEY-----\nabc"])
            ->assertSessionHasErrors('public_key');

        // A private key pasted by mistake must not be parked in the session.
        $this->assertNull(session()->getOldInput('public_key'));
        $this->assertCount(0, $this->sentTo('POST', '/api/vps/v1/public-keys'));
    }

    /** A machine that will not answer is remembered for a moment, not asked again on every poll. */
    public function test_a_failing_machine_lookup_is_not_repeated_on_every_status_poll(): void
    {
        $this->upstream(['GET /api/vps/v1/virtual-machines/555001' => Http::response(['message' => 'Not found'], 404)]);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->user)->getJson(route('customer.vps.status', $this->server->id))->assertOk();
        }

        $this->assertCount(1, $this->sentTo('GET', '/api/vps/v1/virtual-machines/555001'));
    }

    // ========================================================= network misc

    public function test_reverse_dns_uses_this_servers_own_address_and_blank_removes_it(): void
    {
        $this->upstream($this->machine([], [
            'POST /api/vps/v1/virtual-machines/555001/ptr/9001' => ['id' => 1, 'state' => 'sent'],
            'DELETE /api/vps/v1/virtual-machines/555001/ptr/9002' => ['id' => 2, 'state' => 'sent'],
        ]));

        $this->actingAs($this->user)
            ->post(route('customer.vps.reverse-dns', $this->server->id), ['ip' => 'v4', 'ptr' => 'Mail.Example.com'])
            ->assertSessionHas('success');
        $this->assertSame('mail.example.com', $this->sentTo('POST', '/api/vps/v1/virtual-machines/555001/ptr/9001')[0]['body']['domain']);

        $this->actingAs($this->user)
            ->post(route('customer.vps.reverse-dns', $this->server->id), ['ip' => 'v6', 'ptr' => ''])
            ->assertSessionHas('success');
        $this->assertCount(1, $this->sentTo('DELETE', '/api/vps/v1/virtual-machines/555001/ptr/9002'));

        $this->actingAs($this->user)
            ->post(route('customer.vps.reverse-dns', $this->server->id), ['ip' => 'v4', 'ptr' => 'not a hostname'])
            ->assertSessionHasErrors('ptr');
    }

    public function test_resolvers_must_be_ip_addresses(): void
    {
        $this->upstream($this->machine([], ['PUT /api/vps/v1/virtual-machines/555001/nameservers' => ['id' => 1, 'state' => 'sent']]));

        $this->actingAs($this->user)
            ->post(route('customer.vps.resolvers', $this->server->id), ['ns1' => 'dns.google', 'ns2' => ''])
            ->assertSessionHasErrors('ns1');

        $this->actingAs($this->user)
            ->post(route('customer.vps.resolvers', $this->server->id), ['ns1' => '9.9.9.9', 'ns2' => '149.112.112.112'])
            ->assertSessionHas('success');

        $this->assertSame(['ns1' => '9.9.9.9', 'ns2' => '149.112.112.112'], $this->sentTo('PUT', '/api/vps/v1/virtual-machines/555001/nameservers')[0]['body']);
    }

    public function test_the_malware_scanner_installs_and_uninstalls(): void
    {
        $this->upstream($this->machine([], [
            'POST /api/vps/v1/virtual-machines/555001/monarx' => ['id' => 1, 'state' => 'sent'],
            'DELETE /api/vps/v1/virtual-machines/555001/monarx' => ['id' => 2, 'state' => 'sent'],
        ]));

        $this->actingAs($this->user)->post(route('customer.vps.malware', $this->server->id), ['action' => 'install'])->assertSessionHas('success');
        $this->actingAs($this->user)->post(route('customer.vps.malware', $this->server->id), ['action' => 'uninstall'])->assertSessionHas('success');
        $this->actingAs($this->user)->post(route('customer.vps.malware', $this->server->id), ['action' => 'nuke'])->assertSessionHas('error');

        $this->assertCount(1, $this->sentTo('POST', '/api/vps/v1/virtual-machines/555001/monarx'));
        $this->assertCount(1, $this->sentTo('DELETE', '/api/vps/v1/virtual-machines/555001/monarx'));
    }

    // ================================================================ system

    public function test_recovery_mode_starts_with_a_strong_temporary_password_and_stops(): void
    {
        $this->upstream($this->machine([], [
            'POST /api/vps/v1/virtual-machines/555001/recovery' => ['id' => 1, 'state' => 'sent'],
            'DELETE /api/vps/v1/virtual-machines/555001/recovery' => ['id' => 2, 'state' => 'sent'],
        ]));

        $this->actingAs($this->user)->post(route('customer.vps.recovery', $this->server->id), [
            'action' => 'start', 'recovery_password' => 'short', 'recovery_password_confirmation' => 'short',
        ])->assertSessionHasErrors('recovery_password');

        // A failed form must not park the password in the session.
        $this->assertNull(session()->getOldInput('recovery_password'));
        $this->assertCount(0, $this->sentTo('POST', '/api/vps/v1/virtual-machines/555001/recovery'));

        $this->actingAs($this->user)->post(route('customer.vps.recovery', $this->server->id), [
            'action' => 'start', 'recovery_password' => 'Rescue2026Strong', 'recovery_password_confirmation' => 'Rescue2026Strong',
        ])->assertSessionHas('success');

        $this->assertSame('Rescue2026Strong', $this->sentTo('POST', '/api/vps/v1/virtual-machines/555001/recovery')[0]['body']['root_password']);

        $this->actingAs($this->user)->post(route('customer.vps.recovery', $this->server->id), ['action' => 'stop'])->assertSessionHas('success');
        $this->assertCount(1, $this->sentTo('DELETE', '/api/vps/v1/virtual-machines/555001/recovery'));
    }

    public function test_the_panel_password_is_set_on_this_server(): void
    {
        $this->upstream($this->machine([], ['PUT /api/vps/v1/virtual-machines/555001/panel-password' => ['id' => 1, 'state' => 'sent']]));

        $this->actingAs($this->user)->post(route('customer.vps.panel-password', $this->server->id), [
            'panel_password' => 'PanelPass2026x', 'panel_password_confirmation' => 'PanelPass2026x',
        ])->assertSessionHas('success');

        $this->assertSame('PanelPass2026x', $this->sentTo('PUT', '/api/vps/v1/virtual-machines/555001/panel-password')[0]['body']['password']);
    }

    // ============================================================ the helpers

    public function test_firewall_rule_parsing(): void
    {
        [$rules, $errors] = VpsFirewall::normalise([
            ['protocol' => 'TCP', 'port' => '0', 'source_detail' => ''],
            ['protocol' => 'UDP', 'port' => '53', 'source_detail' => '10.0.0.1-10.0.0.9'],
            ['protocol' => 'TCP', 'port' => '22', 'source_detail' => '2001:db8::/32'],
            ['protocol' => 'TCP', 'port' => '22', 'source_detail' => '2001:db8::/32'],   // duplicate
            ['protocol' => 'EVIL', 'port' => '1', 'source_detail' => ''],
            ['protocol' => 'ICMP', 'port' => '', 'source_detail' => '300.1.1.1'],
        ]);

        $this->assertCount(3, $errors);
        $this->assertCount(2, $rules);
        $this->assertTrue(VpsFirewall::allowsSsh($rules));
        $this->assertFalse(VpsFirewall::allowsSsh([['protocol' => 'HTTPS', 'port' => '443']]));
        $this->assertTrue(VpsFirewall::allowsSsh(VpsFirewall::preset('web')));
        $this->assertTrue(VpsFirewall::allowsSsh([['protocol' => 'TCP', 'port' => '20:30']]));
        $this->assertFalse(VpsFirewall::validPort('9000:8000'));
        $this->assertTrue(VpsFirewall::validSource('203.0.113.0/24'));
        $this->assertFalse(VpsFirewall::validSource('203.0.113.0/33'));
    }

    public function test_action_names_read_as_words_and_never_name_the_supplier(): void
    {
        $this->assertSame('รีสตาร์ท', VpsLabels::action('ct_restart')['th']);
        $this->assertSame('กู้คืนจากสแนปช็อต', VpsLabels::action('ct_restore_snapshot')['th']);
        $this->assertSame('โหมดกู้ระบบ', VpsLabels::action('vps_stop_recovery')['th']);
        $this->assertSame('เปิดใช้งานอีกครั้ง', VpsLabels::action('ct_unsuspend')['th']);
        // Taking a backup is not restoring one.
        $this->assertSame('สำรองข้อมูล', VpsLabels::action('ct_create_backup')['th']);
        $this->assertSame('กู้คืนจากแบ็กอัป', VpsLabels::action('ct_restore_backup')['th']);
        $this->assertStringNotContainsStringIgnoringCase('hostinger', VpsLabels::action('hostinger_sync_thing')['en']);
    }
}
