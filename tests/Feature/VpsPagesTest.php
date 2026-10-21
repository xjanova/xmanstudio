<?php

namespace Tests\Feature;

use App\Models\DomainRegistration;
use App\Models\Setting;
use App\Models\User;
use App\Models\VpsInstance;
use App\Models\VpsPayment;
use App\Models\VpsPlan;
use App\Services\HostingerApiService;
use App\Support\UpstreamBilling;
use App\Support\VpsCatalog;
use App\Support\VpsPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use Tests\Concerns\FakesSupplierApi;
use Tests\TestCase;

/**
 * Every new page renders — with real-looking data, as the right person — and
 * none of them lets the supplier's name reach a customer.
 */
class VpsPagesTest extends TestCase
{
    use FakesSupplierApi;
    use RefreshDatabase;

    protected User $admin;

    protected User $user;

    protected VpsPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setValue('hostinger_api_token', 'test-token');
        Cache::flush();
        RateLimiter::clear('hostinger-api');

        // Without an admin the setup wizard takes over every page.
        $this->admin = User::factory()->create(['role' => 'super_admin']);
        $this->user = User::factory()->create();

        $this->plan = VpsPlan::create([
            'slug' => 'kvm2', 'remote_item_id' => 'hostingerinth-vps-kvm2', 'remote_name' => 'KVM 2',
            'name' => 'VPS Business', 'category' => 'vps', 'cpus' => 2, 'memory_mb' => 8192,
            'disk_mb' => 102400, 'bandwidth_mb' => 8192000, 'network_mbps' => 300, 'is_active' => true, 'is_featured' => true,
            'prices' => [
                '1m' => ['item_id' => 'hostingerinth-vps-kvm2-thb-1m', 'currency' => 'THB', 'first' => 45900, 'renew' => 72900, 'months' => 1],
                '1y' => ['item_id' => 'hostingerinth-vps-kvm2-thb-1y', 'currency' => 'THB', 'first' => 394800, 'renew' => 586800, 'months' => 12],
            ],
        ]);

        $this->upstream([
            'GET /api/vps/v1/virtual-machines/555001' => [
                'id' => 555001, 'subscription_id' => 'sub_1', 'hostname' => 'app.example.com', 'state' => 'running',
                'actions_lock' => 'unlocked', 'ipv4' => [['id' => 1, 'address' => '203.0.113.10']],
                'template' => ['id' => 1077, 'name' => 'Ubuntu 24.04 LTS'],
            ],
            'GET /api/vps/v1/virtual-machines/555001/snapshot' => ['id' => 7, 'restore_time' => 600, 'created_at' => now()->subDay()->toIso8601String(), 'expires_at' => now()->addDays(19)->toIso8601String()],
            'GET /api/vps/v1/virtual-machines/555001/backups' => ['data' => [['id' => 31, 'size' => 1048576, 'created_at' => now()->subDays(2)->toIso8601String()]], 'meta' => []],
            'GET /api/vps/v1/virtual-machines/555001/metrics' => [
                'cpu_usage' => ['unit' => '%', 'usage' => [now()->subHour()->timestamp => 12.5, now()->timestamp => 20.0]],
                'ram_usage' => ['unit' => 'bytes', 'usage' => [now()->subHour()->timestamp => 2147483648, now()->timestamp => 3221225472]],
                'disk_space' => ['unit' => 'bytes', 'usage' => [now()->timestamp => 10737418240]],
                'outgoing_traffic' => ['unit' => 'bytes', 'usage' => [now()->timestamp => 1048576]],
                'incoming_traffic' => ['unit' => 'bytes', 'usage' => [now()->timestamp => 2097152]],
                'uptime' => ['unit' => 'milliseconds', 'usage' => [now()->timestamp => 7200000]],
            ],
            'GET /api/billing/v1/payment-methods' => [['id' => 1, 'payment_method' => 'googlepay', 'is_default' => true, 'is_expired' => false, 'is_suspended' => false, 'expires_at' => now()->addYears(3)->toIso8601String()]],
        ]);
    }

    protected function server(array $overrides = []): VpsInstance
    {
        $server = VpsInstance::create($overrides + [
            'user_id' => $this->user->id, 'vps_plan_id' => $this->plan->id, 'plan_name' => 'VPS Business',
            'specs' => $this->plan->specSnapshot(), 'period' => '1m', 'months' => 1,
            'status' => VpsInstance::STATUS_ACTIVE, 'hostname' => 'app.example.com',
            'template_id' => 1077, 'template_name' => 'Ubuntu 24.04 LTS', 'data_center_id' => 21,
            'data_center_name' => 'Kuala Lumpur, มาเลเซีย', 'remote_vm_id' => 555001,
            'remote_subscription_id' => 'sub_1', 'state' => 'running', 'ipv4' => '203.0.113.10',
            'auto_renew' => true, 'activated_at' => now()->subDays(3), 'expires_at' => now()->addDays(27),
        ]);

        VpsPayment::create([
            'vps_instance_id' => $server->id, 'user_id' => $server->user_id, 'kind' => 'order',
            'status' => 'paid', 'amount_thb' => 600, 'months' => 1, 'idempotency_key' => 'pay-' . $server->id,
        ]);

        return $server;
    }

    public function test_the_public_vps_page_shows_both_prices_and_never_the_supplier(): void
    {
        $html = $this->get(route('vps.index'))->assertOk()->getContent();

        $this->assertStringContainsString('VPS Business', $html);
        $this->assertStringContainsString('600', $html);   // first month
        $this->assertStringContainsString('950', $html);   // following months
        $this->assertStringNotContainsStringIgnoringCase('hostinger', strip_tags($html));
        $this->assertStringNotContainsString('KVM 2', strip_tags($html));
    }

    public function test_the_order_form_renders_for_a_signed_in_customer(): void
    {
        $html = $this->actingAs($this->user)->get(route('vps.order', 'kvm2'))->assertOk()->getContent();

        $this->assertStringContainsString('name="root_password"', $html);
        $this->assertStringContainsString('name="order_token"', $html);
        $this->assertStringContainsString('name="expected_amount"', $html);
        $this->assertStringContainsString('Ubuntu 24.04 LTS', $html);
        $this->assertStringContainsString('Kuala Lumpur', $html);
        $this->assertStringNotContainsString('Ubuntu 24.04 with Hostinger Tools', $html);
    }

    public function test_the_order_form_picks_the_os_from_cards_and_draws_flags_not_emoji(): void
    {
        $html = $this->actingAs($this->user)->get(route('vps.order', 'kvm2'))->assertOk()->getContent();

        // The OS is picked from radio cards that still post template_id, with
        // the default (Ubuntu 24.04 LTS) already chosen — not a <select>.
        $this->assertMatchesRegularExpression('/<input type="radio" name="template_id" value="1077"[^>]*\schecked/', $html);
        $this->assertMatchesRegularExpression('/<input type="radio" name="template_id" value="1121"[^>]*>/', $html);
        $this->assertDoesNotMatchRegularExpression('/<select[^>]*name="template_id"/', $html);

        // Windows draws no flag emoji ("MY" in a box): Kuala Lumpur gets an SVG flag.
        $this->assertMatchesRegularExpression('/id="vps-dc-flag-21">\s*<svg[^>]*viewBox="0 0 30 20"/', $html);
        $this->assertStringContainsString('fill="#010066"', $html);   // Malaysia's blue canton
        $this->assertStringNotContainsString(VpsCatalog::flag('my'), $html);
        $this->assertStringNotContainsString(VpsCatalog::flag('de'), $html);

        // The order summary shows today's price AND the renewal price, together.
        $summary = Str::between($html, 'aria-labelledby="vps-summary-title"', '</aside>');
        $this->assertStringContainsString('ยอดชำระวันนี้', $summary);
        $this->assertStringContainsString(VpsPricing::format($this->plan->firstPriceThb('1m')), $summary);
        $this->assertStringContainsString('เดือนถัดไป (ต่ออายุ)', $summary);
        $this->assertStringContainsString(VpsPricing::format($this->plan->renewPriceThb('1m')), $summary);
        $this->assertStringContainsString('Kuala Lumpur', $summary);
        $this->assertStringContainsString('name="accept_terms"', $summary);

        $this->assertStringNotContainsStringIgnoringCase('hostinger', $html);
    }

    public function test_a_rejected_order_comes_back_with_the_same_choices(): void
    {
        $html = $this->actingAs($this->user)
            ->from(route('vps.order', 'kvm2'))
            ->followingRedirects()
            ->post(route('vps.order.store', 'kvm2'), [
                'order_token' => (string) Str::uuid(),
                'period' => '1m',
                'template_id' => 1121,
                'data_center_id' => 19,
                'hostname' => 'app.example.com',
                'root_password' => 'too-short',
                'expected_amount' => 600,
                'auto_renew' => '0',
                'accept_terms' => '1',
            ])
            ->assertOk()
            ->getContent();

        // Docker is still the chosen card, and its tab is the one open.
        $this->assertMatchesRegularExpression('/name="template_id" value="1121"[^>]*\schecked/', $html);
        $this->assertDoesNotMatchRegularExpression('/name="template_id" value="1077"[^>]*\schecked/', $html);
        $this->assertMatchesRegularExpression('/id="vps-os-tab-app"[^>]*aria-selected="true"/', $html);
        $this->assertMatchesRegularExpression('/name="data_center_id" value="19"[^>]*\schecked/', $html);
        $this->assertStringContainsString('Ubuntu 24.04 with Docker', Str::between($html, 'aria-labelledby="vps-summary-title"', '</aside>'));

        // The root password is never written back into the page.
        $this->assertStringNotContainsString('too-short', $html);
    }

    public function test_the_order_form_still_renders_when_the_catalogue_is_down(): void
    {
        $this->upstream([
            'GET /api/vps/v1/templates' => Http::response(['message' => 'down'], 500),
            'GET /api/vps/v1/data-centers' => Http::response(['message' => 'down'], 500),
        ]);

        $html = $this->actingAs($this->user)->get(route('vps.order', 'kvm2'))
            ->assertOk()
            ->assertSee('โหลดตัวเลือกเซิร์ฟเวอร์ไม่สำเร็จ')
            ->getContent();

        // Nothing to choose, so nothing can be ordered — but both prices still show.
        $this->assertDoesNotMatchRegularExpression('/name="template_id"/', $html);
        $this->assertMatchesRegularExpression('/<button type="submit"\s+disabled/', $html);
        $summary = Str::between($html, 'aria-labelledby="vps-summary-title"', '</aside>');
        $this->assertStringContainsString(VpsPricing::format($this->plan->firstPriceThb('1m')), $summary);
        $this->assertStringContainsString(VpsPricing::format($this->plan->renewPriceThb('1m')), $summary);
    }

    public function test_panels_that_need_a_licence_say_so_on_their_card(): void
    {
        $this->upstream([
            'GET /api/vps/v1/templates' => [
                ['id' => 1077, 'name' => 'Ubuntu 24.04 LTS', 'description' => 'Ubuntu'],
                ['id' => 1126, 'name' => 'AlmaLinux 9 with cPanel', 'description' => 'cPanel &amp; WHM'],
            ],
        ]);

        $this->actingAs($this->user)->get(route('vps.order', 'kvm2'))
            ->assertOk()
            ->assertSee('id="vps-os-tab-panel"', false)
            ->assertSee('ต้องซื้อไลเซนส์แยก')
            ->assertSee('licence sold separately');
    }

    public function test_the_flag_and_icon_partials_never_print_what_they_were_given(): void
    {
        $flag = view('vps.partials.flag', ['country' => '"><script>alert(1)</script>', 'class' => 'w-6 h-4'])->render();
        $this->assertStringNotContainsString('<script', $flag);
        $this->assertStringContainsString('<svg', $flag);

        $this->assertStringContainsString('>ZA</text>', view('vps.partials.flag', ['country' => 'za'])->render());
        $this->assertStringContainsString('#C8102E', view('vps.partials.flag', ['country' => 'uk'])->render());

        $icon = view('vps.partials.os-icon', ['name' => 'Ubuntu 24.04 with <img src=x onerror=alert(1)>', 'class' => 'w-8 h-8'])->render();
        $this->assertStringNotContainsString('<img', $icon);
        $this->assertStringContainsString('<svg', $icon);
    }

    public function test_the_order_form_needs_a_login(): void
    {
        $this->get(route('vps.order', 'kvm2'))->assertRedirect();
    }

    public function test_the_order_form_says_so_while_sales_are_paused(): void
    {
        UpstreamBilling::pause('card declined');

        $this->actingAs($this->user)->get(route('vps.order', 'kvm2'))
            ->assertOk()
            ->assertSee(UpstreamBilling::customerMessage());
    }

    public function test_the_customer_list_and_control_panel_render(): void
    {
        $server = $this->server();

        $this->actingAs($this->user)->get(route('customer.vps.index'))
            ->assertOk()
            ->assertSee('app.example.com');

        $html = $this->actingAs($this->user)->get(route('customer.vps.show', $server->id))->assertOk()->getContent();

        $this->assertStringContainsString('203.0.113.10', $html);
        $this->assertStringContainsString('ssh root@203.0.113.10', $html);
        $this->assertStringNotContainsStringIgnoringCase('hostinger', strip_tags($html));

        // The reinstall form lives on the System tab, behind its own link.
        $this->assertStringContainsString(route('customer.vps.show', ['id' => $server->id, 'tab' => 'system']), $html);

        $system = $this->actingAs($this->user)->get(route('customer.vps.show', ['id' => $server->id, 'tab' => 'system']))->assertOk()->getContent();
        $this->assertStringContainsString('name="confirm_hostname"', $system);
    }

    public function test_a_server_being_built_renders_its_waiting_state(): void
    {
        $server = $this->server([
            'status' => VpsInstance::STATUS_PROVISIONING, 'remote_vm_id' => null, 'state' => null,
            'ipv4' => null, 'activated_at' => null, 'expires_at' => null,
        ]);

        // The poller gets its URL through @js, which escapes the slashes.
        $this->actingAs($this->user)->get(route('customer.vps.show', $server->id))
            ->assertOk()
            ->assertSee(Js::from(route('customer.vps.status', $server->id))->toHtml(), false);

        $this->actingAs($this->user)->getJson(route('customer.vps.status', $server->id))
            ->assertOk()
            ->assertJsonPath('settled', false);
    }

    public function test_the_admin_pages_render_with_the_billing_panel(): void
    {
        $this->server();
        $this->server([
            'hostname' => 'stuck.example.com', 'status' => VpsInstance::STATUS_FAILED, 'remote_vm_id' => 555002,
            'last_error' => 'setup failed 3 times',
        ]);
        UpstreamBilling::check(app(HostingerApiService::class));

        $this->actingAs($this->admin)->get(route('admin.vps.index'))
            ->assertOk()
            ->assertSee('KVM 2')
            ->assertSee('stuck.example.com')
            ->assertSee(route('admin.upstream-billing.check'), false);

        $this->actingAs($this->admin)->get(route('admin.domains.index'))
            ->assertOk()
            ->assertSee(route('admin.upstream-billing.check'), false);
    }

    public function test_customers_cannot_open_the_admin_pages(): void
    {
        $this->actingAs($this->user)->get(route('admin.vps.index'))->assertStatus(403);
    }

    public function test_the_customer_domain_page_renders_the_new_controls(): void
    {
        $domain = DomainRegistration::create([
            'user_id' => $this->user->id, 'domain' => 'mine.com', 'tld' => 'com', 'status' => 'active',
            'kind' => 'register', 'price_thb' => 470, 'idempotency_key' => 'k-mine',
            'remote_subscription_id' => 'sub_mine', 'expires_at' => now()->addMonths(6),
        ]);

        $this->upstream([
            'GET /api/domains/v1/portfolio/mine.com' => ['domain' => 'mine.com', 'status' => 'active', 'is_locked' => true, 'is_lockable' => true],
            'GET /api/dns/v1/zones/mine.com' => [['name' => '@', 'type' => 'A', 'ttl' => 3600, 'records' => [['content' => '203.0.113.10']]]],
            'GET /api/dns/v1/snapshots/mine.com' => [['id' => 11, 'reason' => 'update', 'created_at' => now()->subDay()->toIso8601String()]],
            'GET /api/domains/v1/forwarding/mine.com' => ['domain' => 'mine.com', 'redirect_type' => '301', 'redirect_url' => 'https://facebook.com/mypage'],
        ]);

        $this->actingAs($this->user)->get(route('customer.domains.show', $domain->id))
            ->assertOk()
            ->assertSee(route('customer.domains.lock', $domain->id), false)
            ->assertSee(route('customer.domains.forwarding', $domain->id), false)
            ->assertSee(route('customer.domains.dns-restore', [$domain->id, 11]), false)
            ->assertSee('https://facebook.com/mypage');
    }
}
