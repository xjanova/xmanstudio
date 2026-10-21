<?php

namespace Tests\Feature;

use App\Mail\VpsExpiryMail;
use App\Mail\VpsRenewalNoticeMail;
use App\Models\DomainRegistration;
use App\Models\Setting;
use App\Models\User;
use App\Models\VpsInstance;
use App\Models\VpsPayment;
use App\Models\VpsPlan;
use App\Models\Wallet;
use App\Services\VpsOrderException;
use App\Services\VpsProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\Concerns\FakesSupplierApi;
use Tests\TestCase;

/**
 * Everything after the order: renewals from the wallet, the daily renewal
 * run, the customer's control panel, and the catalogue that prices it all.
 */
class VpsLifecycleTest extends TestCase
{
    use FakesSupplierApi;
    use RefreshDatabase;

    protected User $user;

    protected VpsPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setValue('hostinger_api_token', 'test-token');
        Setting::setValue('vps_margin_percent', 30);
        Setting::setValue('vps_price_rounding', 10);
        Cache::flush();
        RateLimiter::clear('hostinger-api');
        Mail::fake();

        $this->user = User::factory()->create();
        $this->plan = VpsPlan::create([
            'slug' => 'kvm2', 'remote_item_id' => 'hostingerinth-vps-kvm2', 'remote_name' => 'KVM 2',
            'name' => 'VPS Business', 'category' => 'vps', 'cpus' => 2, 'memory_mb' => 8192,
            'disk_mb' => 102400, 'bandwidth_mb' => 8192000, 'network_mbps' => 300,
            'prices' => [
                '1m' => ['item_id' => 'hostingerinth-vps-kvm2-thb-1m', 'currency' => 'THB', 'first' => 45900, 'renew' => 72900, 'months' => 1],
            ],
            'is_active' => true,
        ]);
    }

    protected function fund(float $amount, ?User $user = null): Wallet
    {
        $wallet = Wallet::getOrCreateForUser(($user ?? $this->user)->id);
        $wallet->deposit($amount, 'test funding');

        return $wallet->fresh();
    }

    protected function server(array $overrides = []): VpsInstance
    {
        return VpsInstance::create($overrides + [
            'user_id' => $this->user->id,
            'vps_plan_id' => $this->plan->id,
            'plan_name' => 'VPS Business',
            'specs' => $this->plan->specSnapshot(),
            'period' => '1m',
            'months' => 1,
            'status' => VpsInstance::STATUS_ACTIVE,
            'hostname' => 'app.example.com',
            'template_id' => 1077,
            'template_name' => 'Ubuntu 24.04 LTS',
            'data_center_id' => 21,
            'remote_vm_id' => 555001,
            'remote_subscription_id' => 'sub_vps_1',
            'state' => 'running',
            'ipv4' => '203.0.113.10',
            'auto_renew' => true,
            'activated_at' => now()->subMonth(),
            'expires_at' => now()->addDays(2),
        ]);
    }

    // ================================================================ renewals

    public function test_renewal_charges_the_renewal_price_and_extends_the_rental(): void
    {
        $wallet = $this->fund(2000);
        $server = $this->server();
        $before = $server->expires_at->copy();

        $this->upstream([
            'POST /api/billing/v1/subscriptions/sub_vps_1/renew' => ['id' => 991, 'subscription_id' => 'sub_vps_1', 'status' => 'completed'],
            'GET /api/billing/v1/subscriptions' => [],
            'GET /api/vps/v1/virtual-machines/555001' => ['id' => 555001, 'state' => 'running'],
        ]);

        $payment = app(VpsProvisioningService::class)->renew($server);

        $this->assertSame(VpsPayment::STATUS_PAID, $payment->status);
        // The RENEWAL price (729 × 1.3 → 950), not the first month's 600.
        $this->assertSame('950.00', $payment->amount_thb);
        $this->assertSame('1050.00', $wallet->fresh()->balance);
        $this->assertTrue($server->fresh()->expires_at->gt($before->copy()->addDays(27)));
    }

    public function test_a_refused_renewal_refunds_and_can_be_tried_again(): void
    {
        $wallet = $this->fund(2000);
        $server = $this->server();

        $this->upstream([
            'POST /api/billing/v1/subscriptions/sub_vps_1/renew' => Http::response(['message' => 'subscription cannot be renewed'], 422),
        ]);

        $first = app(VpsProvisioningService::class)->renew($server);
        $this->assertSame(VpsPayment::STATUS_REFUNDED, $first->status);
        $this->assertSame('2000.00', $wallet->fresh()->balance);

        // Same day, same paid-until date: a retry must still be possible —
        // a refunded attempt may not hold the idempotency key hostage.
        $this->upstream([
            'POST /api/billing/v1/subscriptions/sub_vps_1/renew' => ['id' => 992, 'status' => 'completed'],
            'GET /api/billing/v1/subscriptions' => [],
        ]);

        $second = app(VpsProvisioningService::class)->renew($server->fresh());

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(VpsPayment::STATUS_PAID, $second->status);
        $this->assertSame('1050.00', $wallet->fresh()->balance);
    }

    /** A 202 renewal is a charge still clearing: the paid-until date waits for upstream's to move. */
    public function test_a_renewal_still_clearing_does_not_extend_the_rental_yet(): void
    {
        $this->fund(2000);
        $server = $this->server();
        $before = $server->expires_at->copy();

        $this->upstream([
            'POST /api/billing/v1/subscriptions/sub_vps_1/renew' => Http::response(['id' => 994, 'subscription_id' => 'sub_vps_1', 'status' => 'payment_initiated'], 202),
        ]);

        $payment = app(VpsProvisioningService::class)->renew($server);

        $this->assertSame(VpsPayment::STATUS_PENDING, $payment->status);
        $this->assertSame('994', $payment->remote_order_id);
        $this->assertTrue($server->fresh()->expires_at->equalTo($before));
        $this->assertFalse($server->fresh()->canRenew());

        // Cleared upstream.
        $this->upstream([
            'GET /api/billing/v1/subscriptions' => [
                ['id' => 'sub_vps_1', 'status' => 'active', 'expires_at' => $before->copy()->addMonth()->toIso8601String()],
            ],
        ]);

        $this->assertSame('confirmed', app(VpsProvisioningService::class)->settleRenewal($payment->fresh()));
        $this->assertSame(VpsPayment::STATUS_PAID, $payment->fresh()->status);
        $this->assertTrue($server->fresh()->expires_at->gt($before->copy()->addDays(27)));
    }

    /**
     * The machine's own date may already be the new one by the time a lost
     * renewal is settled. Compared with THAT, the renewal looked like it never
     * happened — and was refunded although upstream had renewed.
     */
    public function test_a_lost_renewal_is_confirmed_against_the_date_it_was_paid_for(): void
    {
        $server = $this->server();
        $old = $server->expires_at->copy();
        $new = $old->copy()->addMonth();

        $payment = VpsPayment::create([
            'vps_instance_id' => $server->id, 'user_id' => $server->user_id, 'kind' => VpsPayment::KIND_RENEW,
            'status' => VpsPayment::STATUS_PENDING, 'amount_thb' => 950, 'months' => 1,
            'idempotency_key' => 'renew-lost', 'previous_expires_at' => $old,
        ]);

        // A refresh already moved ours.
        $server->update(['expires_at' => $new]);

        $this->upstream([
            'GET /api/billing/v1/subscriptions' => [['id' => 'sub_vps_1', 'status' => 'active', 'expires_at' => $new->toIso8601String()]],
        ]);

        $this->travel(VpsProvisioningService::UNKNOWN_OUTCOME_MINUTES + 5)->minutes();

        $this->assertSame('confirmed', app(VpsProvisioningService::class)->settleRenewal($payment->fresh()));
        $this->assertNull($payment->fresh()->refund_transaction_id);
    }

    public function test_a_renewal_is_never_refunded_on_a_failed_lookup(): void
    {
        $server = $this->server();
        $wallet = $this->fund(100);

        $payment = VpsPayment::create([
            'vps_instance_id' => $server->id, 'user_id' => $server->user_id, 'kind' => VpsPayment::KIND_RENEW,
            'status' => VpsPayment::STATUS_PENDING, 'amount_thb' => 950, 'months' => 1,
            'idempotency_key' => 'renew-quiet', 'previous_expires_at' => $server->expires_at,
        ]);

        $this->upstream(['GET /api/billing/v1/subscriptions' => Http::response(['message' => 'Server error'], 500)]);
        $this->travel(VpsProvisioningService::UNKNOWN_OUTCOME_MINUTES + 30)->minutes();

        $this->assertSame('unknown', app(VpsProvisioningService::class)->settleRenewal($payment->fresh()));
        $this->assertSame(VpsPayment::STATUS_PENDING, $payment->fresh()->status);
        $this->assertSame('100.00', $wallet->fresh()->balance);
    }

    public function test_renewal_is_refused_without_a_subscription_link(): void
    {
        $this->fund(2000);
        $server = $this->server(['remote_subscription_id' => null]);
        $this->upstream();

        $this->expectException(VpsOrderException::class);
        app(VpsProvisioningService::class)->renew($server);
    }

    // ========================================================= the daily run

    public function test_the_daily_run_warns_first_then_charges_after_the_lead_time(): void
    {
        $this->fund(2000);
        $server = $this->server(['expires_at' => now()->addDays(6)]);

        $this->upstream([
            'POST /api/billing/v1/subscriptions/sub_vps_1/renew' => ['id' => 993, 'status' => 'completed'],
            'GET /api/billing/v1/subscriptions' => [],
        ]);

        $this->artisan('vps:renew')->assertSuccessful();

        Mail::assertSent(VpsRenewalNoticeMail::class);
        $this->assertNotNull($server->fresh()->renewal_notice_sent_at);
        // Warned, not yet charged: 6 days out is beyond the 3-day charge point.
        $this->assertCount(0, $this->sentTo('POST', '*/renew'));

        $this->travel(4)->days();
        $this->artisan('vps:renew')->assertSuccessful();

        $this->assertCount(1, $this->sentTo('POST', '*/renew'));
        $this->assertSame(1, VpsPayment::where('kind', 'renew')->where('status', 'paid')->count());
    }

    public function test_a_short_wallet_is_told_once_and_not_charged(): void
    {
        $this->fund(100);
        $server = $this->server([
            'expires_at' => now()->addDays(2),
            'renewal_notice_sent_at' => now()->subDays(3),
        ]);

        $this->upstream();

        $this->artisan('vps:renew')->assertSuccessful();
        $this->artisan('vps:renew')->assertSuccessful();

        Mail::assertSent(VpsExpiryMail::class, 1);
        $this->assertContains('short', $server->fresh()->reminders_sent);
        $this->assertCount(0, $this->sentTo('POST', '*/renew'));
    }

    public function test_a_server_left_to_lapse_is_reminded_once_per_milestone(): void
    {
        $server = $this->server(['auto_renew' => false, 'expires_at' => now()->addDays(5)]);
        $this->upstream();

        $this->artisan('vps:renew')->assertSuccessful();
        $this->artisan('vps:renew')->assertSuccessful();

        Mail::assertSent(VpsExpiryMail::class, 1);
        $this->assertContains('lapse-7', $server->fresh()->reminders_sent);
    }

    public function test_a_server_past_its_date_is_marked_expired_unless_upstream_moved_it(): void
    {
        $lapsed = $this->server(['expires_at' => now()->subDay(), 'auto_renew' => false]);
        $renewedElsewhere = $this->server([
            'expires_at' => now()->subDay(),
            'auto_renew' => false,
            'remote_subscription_id' => 'sub_hpanel',
            'remote_vm_id' => 555002,
        ]);

        $this->upstream([
            'GET /api/billing/v1/subscriptions' => [
                ['id' => 'sub_vps_1', 'status' => 'not_renewing', 'expires_at' => now()->subDay()->toIso8601String()],
                ['id' => 'sub_hpanel', 'status' => 'active', 'expires_at' => now()->addMonth()->toIso8601String()],
            ],
        ]);

        $this->artisan('vps:renew')->assertSuccessful();

        $this->assertSame(VpsInstance::STATUS_EXPIRED, $lapsed->fresh()->status);
        $this->assertSame(VpsInstance::STATUS_ACTIVE, $renewedElsewhere->fresh()->status);
    }

    // ================================================================= the panel

    public function test_someone_elses_server_is_a_404_everywhere(): void
    {
        $server = $this->server();
        $stranger = User::factory()->create();
        $this->upstream();

        $this->actingAs($stranger)->get(route('customer.vps.show', $server->id))->assertNotFound();
        $this->actingAs($stranger)->post(route('customer.vps.power', $server->id), ['action' => 'stop'])->assertNotFound();
        $this->actingAs($stranger)->post(route('customer.vps.renew', $server->id))->assertNotFound();

        $this->assertCount(0, $this->sentTo('POST', '*/stop'));
    }

    public function test_power_buttons_reach_the_machine(): void
    {
        $server = $this->server();
        $this->upstream([
            'POST /api/vps/v1/virtual-machines/555001/restart' => ['id' => 1, 'name' => 'restart', 'state' => 'sent'],
        ]);

        $this->actingAs($this->user)
            ->post(route('customer.vps.power', $server->id), ['action' => 'restart'])
            ->assertSessionHas('success');

        $this->assertCount(1, $this->sentTo('POST', '/api/vps/v1/virtual-machines/555001/restart'));

        $this->actingAs($this->user)
            ->post(route('customer.vps.power', $server->id), ['action' => 'destroy'])
            ->assertSessionHas('error');
    }

    public function test_reinstall_needs_the_hostname_typed_back(): void
    {
        $server = $this->server();
        $this->upstream();

        $this->actingAs($this->user)->post(route('customer.vps.reinstall', $server->id), [
            'template_id' => 1121,
            'root_password' => 'An0therStrongPwd',
            'confirm_hostname' => 'wrong.example.com',
        ])->assertSessionHas('error');

        $this->assertCount(0, $this->sentTo('POST', '*/recreate'));

        $this->actingAs($this->user)->post(route('customer.vps.reinstall', $server->id), [
            'template_id' => 1121,
            'root_password' => 'An0therStrongPwd',
            'confirm_hostname' => 'APP.example.com',
        ])->assertSessionHas('success');

        $recreate = $this->sentTo('POST', '/api/vps/v1/virtual-machines/555001/recreate');
        $this->assertCount(1, $recreate);
        $this->assertSame(1121, $recreate[0]['body']['template_id']);
        $this->assertSame('Ubuntu 24.04 with Docker', $server->fresh()->template_name);
    }

    public function test_pointing_a_domain_writes_only_the_web_records_of_the_customers_own_domain(): void
    {
        $server = $this->server();
        $mine = DomainRegistration::create([
            'user_id' => $this->user->id, 'domain' => 'myshop.com', 'tld' => 'com', 'status' => 'active',
            'kind' => 'register', 'price_thb' => 470, 'idempotency_key' => 'k-mine',
        ]);
        $theirs = DomainRegistration::create([
            'user_id' => User::factory()->create()->id, 'domain' => 'notmine.com', 'tld' => 'com', 'status' => 'active',
            'kind' => 'register', 'price_thb' => 470, 'idempotency_key' => 'k-theirs',
        ]);

        $this->upstream([
            'POST /api/dns/v1/zones/myshop.com/validate' => ['ok' => true],
            'PUT /api/dns/v1/zones/myshop.com' => ['message' => 'ok'],
        ]);

        $this->actingAs($this->user)
            ->post(route('customer.vps.point-domain', $server->id), ['domain_id' => $theirs->id])
            ->assertSessionHas('error');

        $this->assertCount(0, $this->sentTo('PUT', '/api/dns/v1/zones/*'));

        $this->actingAs($this->user)
            ->post(route('customer.vps.point-domain', $server->id), ['domain_id' => $mine->id])
            ->assertSessionHas('success');

        $put = $this->sentTo('PUT', '/api/dns/v1/zones/myshop.com')[0];
        $this->assertTrue($put['body']['overwrite']);
        $this->assertSame(['@|A', 'www|CNAME'], collect($put['body']['zone'])->map(fn ($r) => $r['name'] . '|' . $r['type'])->all());
        $this->assertSame('203.0.113.10', $put['body']['zone'][0]['records'][0]['content']);
    }

    public function test_a_failed_order_form_never_sends_the_password_back_to_the_page(): void
    {
        $this->fund(10);
        $this->upstream();

        $response = $this->actingAs($this->user)->post(route('vps.order.store', $this->plan->slug), [
            'order_token' => (string) Str::uuid(),
            'period' => '1m',
            'template_id' => 1077,
            'data_center_id' => 21,
            'hostname' => 'app.example.com',
            'root_password' => 'Str0ngPassw0rdX',
            'auto_renew' => 1,
            'expected_amount' => 600,
            'accept_terms' => 1,
        ]);

        // Wallet too short: refused with a message, and the form refilled
        // WITHOUT the root password.
        $response->assertSessionHas('error');
        $this->assertNull(session()->getOldInput('root_password'));
        $this->assertSame('app.example.com', session()->getOldInput('hostname'));
    }

    /** A validation failure refills the form from the session — never with the root password. */
    public function test_a_form_that_fails_validation_does_not_park_the_root_password_in_the_session(): void
    {
        $this->upstream();

        $this->actingAs($this->user)->post(route('vps.order.store', $this->plan->slug), [
            'order_token' => (string) Str::uuid(),
            'period' => '1m',
            'template_id' => 1077,
            'data_center_id' => 21,
            'hostname' => 'app.example.com',
            'root_password' => 'Str0ngPassw0rdX',
            'expected_amount' => 600,
            // accept_terms missing → ValidationException, which flashes input
        ])->assertSessionHasErrors('accept_terms');

        $this->assertSame('app.example.com', session()->getOldInput('hostname'));
        $this->assertNull(session()->getOldInput('root_password'));
    }

    /** An unticked checkbox sends nothing — that must mean "off", not the default "on". */
    public function test_ordering_with_auto_renew_unticked_leaves_it_off(): void
    {
        $this->fund(1000);
        $this->upstream([
            'POST /api/vps/v1/virtual-machines' => [
                'order' => ['id' => 1, 'subscription_id' => 'sub_off'],
                'virtual_machine' => ['id' => 555009, 'subscription_id' => 'sub_off', 'hostname' => 'off.example.com', 'state' => 'running', 'actions_lock' => 'unlocked', 'ipv4' => [['id' => 1, 'address' => '203.0.113.20']]],
            ],
        ]);

        $this->actingAs($this->user)->post(route('vps.order.store', $this->plan->slug), [
            'order_token' => (string) Str::uuid(),
            'period' => '1m',
            'template_id' => 1077,
            'data_center_id' => 21,
            'hostname' => 'off.example.com',
            'root_password' => 'Str0ngPassw0rdX',
            'expected_amount' => 600,
            'accept_terms' => 1,
        ])->assertRedirect();

        $server = VpsInstance::where('hostname', 'off.example.com')->firstOrFail();
        $this->assertFalse($server->auto_renew);
        $this->assertSame(VpsInstance::STATUS_ACTIVE, $server->status);
    }

    // =============================================================== catalogue

    public function test_the_catalogue_sync_reads_the_real_account_shape(): void
    {
        VpsPlan::query()->delete();

        $item = fn (string $slug, string $name, int $cpus, int $mem, array $prices) => [
            'id' => 'hostingerinth-vps-' . $slug, 'name' => $name, 'category' => 'VPS',
            'metadata' => ['cpus' => (string) $cpus, 'memory' => (string) $mem, 'bandwidth' => (string) ($mem * 1000), 'disk_space' => (string) ($mem * 12.5), 'network' => '300'],
            'prices' => $prices,
        ];
        $p = fn (string $id, int $first, int $renew, int $period, string $unit) => [
            'id' => $id, 'name' => $id, 'currency' => 'THB', 'price' => $renew, 'first_period_price' => $first, 'period' => $period, 'period_unit' => $unit,
        ];

        $this->upstream([
            'GET /api/billing/v1/catalog' => [
                $item('kvm1', 'KVM 1', 1, 4096, [
                    $p('hostingerinth-vps-kvm1-thb-1y', 298800, 478800, 1, 'year'),
                    $p('hostingerinth-vps-kvm1-thb-1m', 34900, 57900, 1, 'month'),
                    $p('hostingerinth-vps-kvm1-thb-2y', 501600, 837600, 2, 'year'),
                ]),
                $item('kvmminecraftalex', 'Game Panel 1', 1, 4096, [
                    $p('hostingerinth-vps-kvmminecraftalex-thb-1m', 36900, 57900, 1, 'month'),
                ]),
            ],
        ]);

        $this->artisan('vps:sync-catalogue')->assertSuccessful();

        $kvm1 = VpsPlan::where('slug', 'kvm1')->first();
        $game = VpsPlan::where('slug', 'kvmminecraftalex')->first();

        $this->assertSame('VPS Starter', $kvm1->name);
        $this->assertSame('KVM 1', $kvm1->remote_name);
        $this->assertTrue($kvm1->is_active);
        $this->assertSame(['1m', '1y', '2y'], $kvm1->periods());
        $this->assertSame('hostingerinth-vps-kvm1-thb-1m', $kvm1->price('1m')['item_id']);
        $this->assertSame(12, $kvm1->price('1y')['months']);

        // Unfamiliar plans arrive switched off and without the supplier's name.
        $this->assertSame('game', $game->category);
        $this->assertFalse($game->is_active);
        $this->assertStringNotContainsString('Game Panel', $game->name);
    }
}
