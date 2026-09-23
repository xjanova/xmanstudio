<?php

namespace Tests\Feature;

use App\Mail\AdminAlertMail;
use App\Models\DomainContact;
use App\Models\DomainRegistration;
use App\Models\DomainTld;
use App\Models\Setting;
use App\Models\User;
use App\Models\VpsInstance;
use App\Models\VpsPlan;
use App\Models\Wallet;
use App\Services\DomainPurchaseException;
use App\Services\DomainRegistrarService;
use App\Services\HostingerApiService;
use App\Support\Alerts\BusinessAlerts;
use App\Support\UpstreamBilling;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\FakesSupplierApi;
use Tests\TestCase;

/**
 * "Can we still pay the supplier?" — checked ahead of time, acted on when the
 * answer is no, and heard about by a person even before Telegram is set up.
 */
class UpstreamBillingTest extends TestCase
{
    use FakesSupplierApi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setValue('hostinger_api_token', 'test-token');
        Cache::flush();
        RateLimiter::clear('hostinger-api');
        Mail::fake();
    }

    protected function method(array $overrides = []): array
    {
        return $overrides + [
            'id' => 1, 'name' => 'Card', 'identifier' => '****1111', 'payment_method' => 'googlepay',
            'is_default' => true, 'is_expired' => false, 'is_suspended' => false,
            'created_at' => '2024-03-27T01:52:23Z', 'expires_at' => now()->addYears(3)->toIso8601String(),
        ];
    }

    public function test_a_healthy_default_card_is_ok(): void
    {
        $this->upstream(['GET /api/billing/v1/payment-methods' => [$this->method()]]);

        $health = UpstreamBilling::check(app(HostingerApiService::class));

        $this->assertSame('ok', $health['level']);
        $this->assertTrue($health['ok']);
        // Only what identifies the method, never its digits.
        $this->assertArrayNotHasKey('identifier', $health['methods'][0]);
    }

    public function test_a_card_expiring_this_month_is_a_warning(): void
    {
        $this->upstream(['GET /api/billing/v1/payment-methods' => [$this->method(['expires_at' => now()->addDays(10)->toIso8601String()])]]);

        $this->assertSame('warning', UpstreamBilling::check(app(HostingerApiService::class))['level']);
    }

    public function test_an_expired_default_or_no_default_is_critical(): void
    {
        $this->upstream(['GET /api/billing/v1/payment-methods' => [$this->method(['is_expired' => true])]]);
        $this->assertSame('critical', UpstreamBilling::check(app(HostingerApiService::class))['level']);

        $this->upstream(['GET /api/billing/v1/payment-methods' => [$this->method(['is_default' => false])]]);
        $this->assertSame('critical', UpstreamBilling::check(app(HostingerApiService::class))['level']);

        $this->upstream(['GET /api/billing/v1/payment-methods' => []]);
        $this->assertSame('critical', UpstreamBilling::check(app(HostingerApiService::class))['level']);
    }

    public function test_the_check_pauses_on_critical_and_lifts_only_its_own_pause(): void
    {
        $this->upstream(['GET /api/billing/v1/payment-methods' => [$this->method(['is_suspended' => true])]]);
        $this->artisan('hostinger:billing-check')->assertSuccessful();

        $this->assertTrue(UpstreamBilling::isPaused());
        $this->assertSame('health', UpstreamBilling::paused()['source']);

        $this->upstream(['GET /api/billing/v1/payment-methods' => [$this->method()]]);
        $this->artisan('hostinger:billing-check')->assertSuccessful();
        $this->assertFalse(UpstreamBilling::isPaused());

        // A refused charge is invisible from the method list — a healthy
        // check must not reopen a shop that a declined card just closed.
        UpstreamBilling::pause('card declined', 'purchase');
        $this->artisan('hostinger:billing-check')->assertSuccessful();
        $this->assertTrue(UpstreamBilling::isPaused());
    }

    /** Critical, and nobody set up Telegram: the owner hears by e-mail instead of not at all. */
    public function test_an_unheard_critical_alert_goes_to_the_admin_by_email_once(): void
    {
        Setting::setValue('contact_email', 'owner@example.com');
        $this->upstream(['GET /api/billing/v1/payment-methods' => []]);

        $this->artisan('hostinger:billing-check')->assertSuccessful();
        $this->artisan('hostinger:billing-check')->assertSuccessful();

        Mail::assertSent(AdminAlertMail::class, 1);
        Mail::assertSent(AdminAlertMail::class, fn (AdminAlertMail $m) => $m->hasTo('owner@example.com'));
    }

    /** Customers whose wallet cannot cover a due renewal: one Telegram card a day, not one per run. */
    public function test_short_customer_wallets_reach_the_admin_telegram_once_a_day(): void
    {
        Setting::setValue('telegram_bot_token', '123456:TEST-TOKEN', 'string', 'telegram');
        Setting::setValue('telegram_chat_id', '-1001234567890', 'string', 'telegram');
        Setting::setValue('telegram_alerts_enabled', '1', 'boolean', 'telegram');

        $this->upstream(['POST /bot*' => ['ok' => true, 'result' => ['message_id' => 77]]]);

        $items = [[
            'what' => 'vps', 'name' => 'app.example.com', 'owner' => 'Somchai',
            'need' => 950.0, 'have' => 100.0, 'expires' => now()->addDays(3)->format('d/m/Y'),
        ]];

        BusinessAlerts::renewalsShortOfFunds('vps', $items);
        $afterFirst = count($this->sentTo('POST', '/bot*'));

        BusinessAlerts::renewalsShortOfFunds('vps', $items);

        $this->assertGreaterThan(0, $afterFirst);
        $this->assertCount($afterFirst, $this->sentTo('POST', '/bot*'));
        // Telegram took it, so nobody is e-mailed as well.
        Mail::assertNothingSent();
    }

    public function test_upstream_auto_renewal_is_switched_off_only_on_what_we_resell(): void
    {
        DomainRegistration::create([
            'user_id' => User::factory()->create()->id, 'domain' => 'customer.com', 'tld' => 'com', 'status' => 'active',
            'kind' => 'register', 'price_thb' => 470, 'idempotency_key' => 'k1', 'remote_subscription_id' => 'sub_customer',
        ]);

        $this->upstream([
            'GET /api/billing/v1/payment-methods' => [$this->method()],
            'GET /api/billing/v1/subscriptions' => [
                ['id' => 'sub_customer', 'name' => 'customer.com', 'is_auto_renewed' => true],
                // The owner's own domain lives in the same account and must keep renewing.
                ['id' => 'sub_owner', 'name' => 'xman4289.com', 'is_auto_renewed' => true],
            ],
        ]);

        $this->artisan('hostinger:billing-check')->assertSuccessful();

        $this->assertCount(1, $this->sentTo('DELETE', '/api/billing/v1/subscriptions/sub_customer/auto-renewal/disable'));
        $this->assertCount(0, $this->sentTo('DELETE', '/api/billing/v1/subscriptions/sub_owner/*'));
    }

    /** Refunded after upstream took it: nobody pays us for it, so it must not renew on our card either. */
    public function test_auto_renewal_is_switched_off_on_refunded_orders_too(): void
    {
        $plan = VpsPlan::create([
            'slug' => 'kvm2', 'remote_item_id' => 'hostingerinth-vps-kvm2', 'remote_name' => 'KVM 2', 'name' => 'VPS Business',
            'category' => 'vps', 'cpus' => 2, 'memory_mb' => 8192, 'disk_mb' => 102400, 'bandwidth_mb' => 8192000,
            'network_mbps' => 300, 'prices' => [], 'is_active' => true,
        ]);

        VpsInstance::create([
            'user_id' => User::factory()->create()->id, 'vps_plan_id' => $plan->id, 'plan_name' => 'VPS Business',
            'period' => '1m', 'months' => 1, 'status' => VpsInstance::STATUS_REFUNDED, 'hostname' => 'gone.example.com',
            'template_id' => 1077, 'data_center_id' => 21, 'remote_subscription_id' => 'sub_refunded_vps',
        ]);

        DomainRegistration::create([
            'user_id' => User::factory()->create()->id, 'domain' => 'refunded.com', 'tld' => 'com', 'status' => 'refunded',
            'kind' => 'register', 'price_thb' => 470, 'idempotency_key' => 'k-refunded', 'remote_subscription_id' => 'sub_refunded_domain',
        ]);

        $this->upstream([
            'GET /api/billing/v1/payment-methods' => [$this->method()],
            'GET /api/billing/v1/subscriptions' => [
                ['id' => 'sub_refunded_vps', 'name' => 'KVM 2', 'is_auto_renewed' => true],
                ['id' => 'sub_refunded_domain', 'name' => 'refunded.com', 'is_auto_renewed' => true],
            ],
            'GET /api/vps/v1/virtual-machines' => [],
        ]);

        $this->artisan('hostinger:billing-check')->assertSuccessful();

        $this->assertCount(1, $this->sentTo('DELETE', '/api/billing/v1/subscriptions/sub_refunded_vps/auto-renewal/disable'));
        $this->assertCount(1, $this->sentTo('DELETE', '/api/billing/v1/subscriptions/sub_refunded_domain/auto-renewal/disable'));
    }

    /** Bought, never installed, claimed by no order: reported — never touched, it may be the owner's. */
    public function test_machines_bought_for_nobody_are_reported_not_touched(): void
    {
        $plan = VpsPlan::create([
            'slug' => 'kvm2', 'remote_item_id' => 'hostingerinth-vps-kvm2', 'remote_name' => 'KVM 2', 'name' => 'VPS Business',
            'category' => 'vps', 'cpus' => 2, 'memory_mb' => 8192, 'disk_mb' => 102400, 'bandwidth_mb' => 8192000,
            'network_mbps' => 300, 'prices' => [], 'is_active' => true,
        ]);

        $this->travel(-10)->days();
        VpsInstance::create([
            'user_id' => User::factory()->create()->id, 'vps_plan_id' => $plan->id, 'plan_name' => 'VPS Business',
            'period' => '1m', 'months' => 1, 'status' => VpsInstance::STATUS_ACTIVE, 'hostname' => 'sold.example.com',
            'template_id' => 1077, 'data_center_id' => 21, 'remote_vm_id' => 555100, 'remote_subscription_id' => 'sub_sold',
        ]);
        $this->travelBack();

        $this->upstream([
            'GET /api/billing/v1/payment-methods' => [$this->method()],
            'GET /api/billing/v1/subscriptions' => [],
            'GET /api/vps/v1/virtual-machines' => [
                // Ours, claimed.
                ['id' => 555100, 'subscription_id' => 'sub_sold', 'state' => 'running', 'created_at' => now()->subDays(9)->toIso8601String()],
                // The owner's, from before the shop opened.
                ['id' => 400, 'subscription_id' => 'sub_owner', 'state' => 'initial', 'created_at' => now()->subYear()->toIso8601String()],
                // Bought after a refund, nobody's.
                ['id' => 555200, 'subscription_id' => 'sub_orphan', 'state' => 'initial', 'plan' => 'KVM 2', 'created_at' => now()->subDays(2)->toIso8601String()],
                // Minutes old: an order may still claim it.
                ['id' => 555300, 'subscription_id' => 'sub_new', 'state' => 'initial', 'created_at' => now()->subMinutes(20)->toIso8601String()],
            ],
        ]);

        $this->artisan('hostinger:billing-check')
            ->expectsOutputToContain('#555200 (KVM 2)')
            ->doesntExpectOutputToContain('#400')
            ->doesntExpectOutputToContain('#555300')
            ->assertSuccessful();

        // Reported only: no auto-renewal switched, nothing cancelled.
        $this->assertCount(0, $this->sentTo('DELETE', '*'));
    }

    /** While paused, a domain buyer is turned away BEFORE their wallet is touched. */
    public function test_a_paused_shop_refuses_domain_orders_before_the_debit(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::getOrCreateForUser($user->id);
        $wallet->deposit(1000, 'test');

        DomainTld::updateOrCreate(['tld' => 'com'], [
            'item_id_register' => 'test-domain-com-thb-1y', 'cost_usd_cents' => 31900, 'renew_cost_usd_cents' => 51900,
            'cost_currency' => 'THB', 'is_active' => true,
        ]);

        $contact = DomainContact::create([
            'user_id' => $user->id, 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.com',
            'phone_country_code' => '+66', 'phone' => '812345678', 'address1' => '1', 'city' => 'Bangkok',
            'zip' => '10110', 'country' => 'TH',
        ]);

        UpstreamBilling::pause('card declined');
        $this->upstream();

        try {
            app(DomainRegistrarService::class)->register($user->id, 'paused.com', $contact);
            $this->fail('Expected a paused shop to refuse');
        } catch (DomainPurchaseException $e) {
            $this->assertSame(UpstreamBilling::customerMessage(), $e->getMessage());
        }

        $this->assertSame('1000.00', $wallet->fresh()->balance);
        $this->assertSame([], $this->sent);
    }

    public function test_the_admin_can_resume_sales(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        UpstreamBilling::pause('card declined');

        // Not something a customer can press.
        $this->actingAs($customer)->post(route('admin.upstream-billing.resume'));
        $this->assertTrue(UpstreamBilling::isPaused());

        $this->actingAs($admin)
            ->post(route('admin.upstream-billing.resume'))
            ->assertSessionHas('success');

        $this->assertFalse(UpstreamBilling::isPaused());
    }
}
