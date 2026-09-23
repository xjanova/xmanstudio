<?php

namespace Tests\Feature;

use App\Mail\VpsReadyMail;
use App\Models\Setting;
use App\Models\User;
use App\Models\VpsInstance;
use App\Models\VpsPayment;
use App\Models\VpsPlan;
use App\Models\Wallet;
use App\Services\VpsOrderException;
use App\Services\VpsProvisioningService;
use App\Support\UpstreamBilling;
use App\Support\VpsCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\Concerns\FakesSupplierApi;
use Tests\TestCase;

/**
 * The VPS money paths.
 *
 * Each test is a way a customer pays for nothing, or gets a server for free:
 * a double submit, a refused order that keeps the money, a timeout refunded
 * while the machine was being built with our card, a card we cannot pay with
 * that keeps taking customers' money only to hand it back.
 */
class VpsOrderTest extends TestCase
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
            'slug' => 'kvm2',
            'remote_item_id' => 'hostingerinth-vps-kvm2',
            'remote_name' => 'KVM 2',
            'name' => 'VPS Business',
            'category' => 'vps',
            'cpus' => 2,
            'memory_mb' => 8192,
            'disk_mb' => 102400,
            'bandwidth_mb' => 8192000,
            'network_mbps' => 300,
            'prices' => [
                '1m' => ['item_id' => 'hostingerinth-vps-kvm2-thb-1m', 'currency' => 'THB', 'first' => 45900, 'renew' => 72900, 'months' => 1],
                '1y' => ['item_id' => 'hostingerinth-vps-kvm2-thb-1y', 'currency' => 'THB', 'first' => 394800, 'renew' => 586800, 'months' => 12],
            ],
            'is_active' => true,
        ]);
    }

    protected function fund(float $amount): Wallet
    {
        $wallet = Wallet::getOrCreateForUser($this->user->id);
        $wallet->deposit($amount, 'test funding');

        return $wallet->fresh();
    }

    protected function service(): VpsProvisioningService
    {
        return app(VpsProvisioningService::class);
    }

    /** @return array<string,mixed> */
    protected function orderOptions(array $overrides = []): array
    {
        return $overrides + [
            'template_id' => 1077,
            'data_center_id' => 21,
            'hostname' => 'app.example.com',
            'password' => 'Str0ngPassw0rdX',
            'auto_renew' => true,
        ];
    }

    protected function vm(array $overrides = []): array
    {
        return $overrides + [
            'id' => 555001,
            'subscription_id' => 'sub_vps_1',
            'hostname' => 'app.example.com',
            'state' => 'running',
            'actions_lock' => 'unlocked',
            'ipv4' => [['id' => 9001, 'address' => '203.0.113.10', 'ptr' => null]],
            'ipv6' => null,
            'template' => ['id' => 1077, 'name' => 'Ubuntu 24.04 LTS', 'description' => 'Ubuntu'],
            'created_at' => now()->toIso8601String(),
        ];
    }

    // ================================================================ pricing

    public function test_first_period_and_renewal_are_priced_separately_and_rounded_up(): void
    {
        // 459 × 1.30 = 596.70 → 600 ; 729 × 1.30 = 947.70 → 950
        $this->assertSame(600.0, $this->plan->firstPriceThb('1m'));
        $this->assertSame(950.0, $this->plan->renewPriceThb('1m'));
        $this->assertTrue($this->plan->renewalIsDearer('1m'));
        // A period the catalogue does not offer cannot be bought.
        $this->assertNull($this->plan->price('2y'));
        $this->assertSame(['1m', '1y'], $this->plan->periods());
    }

    public function test_supplier_named_templates_are_never_offered(): void
    {
        $this->upstream();

        $names = collect(app(VpsCatalog::class)->templates())->pluck('name')->all();

        $this->assertContains('Ubuntu 24.04 LTS', $names);
        $this->assertNotContains('Ubuntu 24.04 with Hostinger Tools', $names);
    }

    // ================================================================ ordering

    public function test_successful_order_debits_once_activates_and_switches_off_upstream_renewal(): void
    {
        $wallet = $this->fund(1000);

        $this->upstream([
            'POST /api/vps/v1/virtual-machines' => Http::response([
                'order' => ['id' => 777, 'subscription_id' => 'sub_vps_1', 'status' => 'completed'],
                'virtual_machine' => $this->vm(),
            ]),
            'GET /api/billing/v1/subscriptions' => Http::response([
                ['id' => 'sub_vps_1', 'name' => 'KVM 2', 'status' => 'active', 'is_auto_renewed' => true, 'expires_at' => now()->addMonth()->toIso8601String()],
            ]),
        ]);

        $server = $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(), (string) Str::uuid());

        $this->assertSame(VpsInstance::STATUS_ACTIVE, $server->status);
        $this->assertSame('203.0.113.10', $server->ipv4);
        $this->assertSame('400.00', $wallet->fresh()->balance);
        $this->assertSame(VpsPayment::STATUS_PAID, $server->orderPayment->status);

        // Ours renews from the wallet: upstream's own renewal must be off, or
        // our card pays for the same month twice.
        $this->assertCount(1, $this->sentTo('DELETE', '*/subscriptions/sub_vps_1/auto-renewal/disable'));

        // The password went to the supplier once, and nowhere else.
        $purchase = $this->sentTo('POST', '/api/vps/v1/virtual-machines')[0];
        $this->assertSame('Str0ngPassw0rdX', $purchase['body']['setup']['password']);
        $this->assertSame('hostingerinth-vps-kvm2-thb-1m', $purchase['body']['item_id']);
        $this->assertFalse(Cache::has('vps:secret:' . $server->id));

        Mail::assertSent(VpsReadyMail::class);
    }

    public function test_double_submit_of_one_form_buys_one_server(): void
    {
        $wallet = $this->fund(2000);

        $this->upstream([
            'POST /api/vps/v1/virtual-machines' => Http::response([
                'order' => ['id' => 777, 'subscription_id' => 'sub_vps_1'],
                'virtual_machine' => $this->vm(),
            ]),
        ]);

        $token = (string) Str::uuid();
        $first = $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(), $token);
        $second = $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(), $token);

        $this->assertSame($first->id, $second->id);
        $this->assertCount(1, $this->sentTo('POST', '/api/vps/v1/virtual-machines'));
        $this->assertSame(1, VpsInstance::count());
        $this->assertSame('1400.00', $wallet->fresh()->balance);
    }

    public function test_a_duplicate_submit_while_the_first_is_in_flight_does_not_call_upstream(): void
    {
        $this->fund(2000);
        $this->upstream();

        // The first request has taken the money and is still talking to the
        // supplier: its row is pending. The duplicate must not buy again.
        $token = (string) Str::uuid();
        $instance = VpsInstance::create([
            'user_id' => $this->user->id, 'vps_plan_id' => $this->plan->id, 'plan_name' => 'VPS Business',
            'period' => '1m', 'months' => 1, 'status' => VpsInstance::STATUS_PENDING, 'hostname' => 'app.example.com',
            'template_id' => 1077, 'data_center_id' => 21,
        ]);
        VpsPayment::create([
            'vps_instance_id' => $instance->id, 'user_id' => $this->user->id, 'kind' => 'order', 'status' => 'pending',
            'amount_thb' => 600, 'idempotency_key' => substr(hash('sha256', 'vps-order|' . $this->user->id . '|' . $token), 0, 64),
        ]);

        $again = $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(), $token);

        $this->assertSame($instance->id, $again->id);
        $this->assertCount(0, $this->sentTo('POST', '/api/vps/v1/virtual-machines'));
    }

    public function test_short_wallet_is_refused_before_anything_is_bought(): void
    {
        $wallet = $this->fund(100);
        $this->upstream();

        try {
            $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(), (string) Str::uuid());
            $this->fail('Expected the order to be refused');
        } catch (VpsOrderException $e) {
            $this->assertStringContainsString('ยอดเงินไม่พอ', $e->getMessage());
        }

        $this->assertSame('100.00', $wallet->fresh()->balance);
        $this->assertSame(0, VpsInstance::count());
        $this->assertCount(0, $this->sentTo('POST', '/api/vps/v1/virtual-machines'));
    }

    public function test_a_price_that_moved_since_the_form_was_shown_is_not_charged(): void
    {
        $wallet = $this->fund(1000);
        $this->upstream();

        $this->expectException(VpsOrderException::class);

        try {
            $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(['expected_amount' => 460]), (string) Str::uuid());
        } finally {
            $this->assertSame('1000.00', $wallet->fresh()->balance);
        }
    }

    public function test_upstream_refusal_refunds_in_full_without_pausing_sales(): void
    {
        $wallet = $this->fund(1000);

        $this->upstream([
            'POST /api/vps/v1/virtual-machines' => Http::response(['message' => 'The hostname is invalid.', 'errors' => ['setup.hostname' => ['invalid']]], 422),
        ]);

        $server = $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(), (string) Str::uuid());

        $this->assertSame(VpsInstance::STATUS_REFUNDED, $server->status);
        $this->assertSame('1000.00', $wallet->fresh()->balance);
        $this->assertSame(VpsPayment::STATUS_REFUNDED, $server->orderPayment->status);
        $this->assertFalse(UpstreamBilling::isPaused());
    }

    /** Our card declined: refund, stop selling, and do not take the next customer's money. */
    public function test_a_payment_refusal_pauses_all_sales_before_the_next_debit(): void
    {
        $wallet = $this->fund(2000);

        $this->upstream([
            'POST /api/vps/v1/virtual-machines' => Http::response(['message' => 'Payment failed: insufficient funds'], 422),
        ]);

        $server = $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(), (string) Str::uuid());

        $this->assertSame(VpsInstance::STATUS_REFUNDED, $server->status);
        $this->assertTrue(UpstreamBilling::isPaused());

        try {
            $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(), (string) Str::uuid());
            $this->fail('Expected sales to be paused');
        } catch (VpsOrderException $e) {
            $this->assertSame(UpstreamBilling::customerMessage(), $e->getMessage());
        }

        // Nothing debited the second time, and only one purchase ever left.
        $this->assertSame('2000.00', $wallet->fresh()->balance);
        $this->assertCount(1, $this->sentTo('POST', '/api/vps/v1/virtual-machines'));
    }

    // ============================================================ 202 + reconcile

    public function test_a_202_is_installed_later_with_the_password_the_customer_chose(): void
    {
        $this->fund(1000);

        $this->upstream([
            'POST /api/vps/v1/virtual-machines' => Http::response(['id' => 888, 'subscription_id' => 'sub_late', 'status' => 'payment_initiated', 'message' => 'processing'], 202),
        ]);

        $server = $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(), (string) Str::uuid());

        $this->assertSame(VpsInstance::STATUS_PROVISIONING, $server->status);
        $this->assertSame('sub_late', $server->remote_subscription_id);
        // Taken upstream, charge still clearing: not spent until the machine shows.
        $this->assertSame(VpsPayment::STATUS_PENDING, $server->orderPayment->status);
        // The password waits encrypted in the cache — never in the database.
        $this->assertTrue(Cache::has('vps:secret:' . $server->id));
        $this->assertStringNotContainsString('Str0ngPassw0rdX', (string) json_encode($server->fresh()->getAttributes()));

        // Upstream's payment clears; the machine appears in `initial`.
        $this->upstream([
            'GET /api/vps/v1/virtual-machines' => Http::response([$this->vm(['id' => 555002, 'subscription_id' => 'sub_late', 'state' => 'initial', 'hostname' => 'srv555002.hstgr.cloud'])]),
            'POST /api/vps/v1/virtual-machines/555002/setup' => Http::response($this->vm(['id' => 555002, 'subscription_id' => 'sub_late', 'state' => 'running'])),
        ]);

        $outcome = $this->service()->reconcile($server->fresh());

        $this->assertSame('linked + setup', $outcome);
        $setup = $this->sentTo('POST', '/api/vps/v1/virtual-machines/555002/setup')[0];
        $this->assertSame('Str0ngPassw0rdX', $setup['body']['password']);
        $this->assertSame(1077, $setup['body']['template_id']);

        $server->refresh();
        $this->assertSame(VpsInstance::STATUS_ACTIVE, $server->status);
        $this->assertSame(VpsPayment::STATUS_PAID, $server->orderPayment->status);
        $this->assertFalse($server->needs_password_reset);
        // The supplier's default hostname never replaces the customer's.
        $this->assertSame('app.example.com', $server->hostname);
        $this->assertFalse(Cache::has('vps:secret:' . $server->id));
    }

    /** A 202 whose charge never clears leaves no machine — refunded, but only after a day of looking. */
    public function test_an_accepted_order_whose_machine_never_comes_is_refunded_after_a_day(): void
    {
        $wallet = $this->fund(1000);

        $this->upstream([
            'POST /api/vps/v1/virtual-machines' => Http::response(['id' => 889, 'subscription_id' => null, 'status' => 'payment_initiated'], 202),
        ]);

        $server = $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(), (string) Str::uuid());

        $this->upstream(['GET /api/vps/v1/virtual-machines' => Http::response([])]);

        $this->travel(VpsProvisioningService::UNKNOWN_OUTCOME_MINUTES + 5)->minutes();
        $this->assertSame('waiting', $this->service()->reconcile($server->fresh()));
        $this->assertSame('400.00', $wallet->fresh()->balance);

        $this->travel(VpsProvisioningService::ACCEPTED_ORDER_MINUTES)->minutes();
        $this->assertSame('refunded', $this->service()->reconcile($server->fresh()));
        $this->assertSame('1000.00', $wallet->fresh()->balance);
    }

    /** Upstream falling over mid-order can be after it built the machine: wait and look, never refund on the spot. */
    public function test_a_server_error_on_purchase_is_looked_up_not_refunded(): void
    {
        $wallet = $this->fund(1000);

        $this->upstream([
            'POST /api/vps/v1/virtual-machines' => Http::response(['message' => 'Internal server error'], 500),
        ]);

        $server = $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(), (string) Str::uuid());

        $this->assertSame(VpsInstance::STATUS_PROVISIONING, $server->status);
        $this->assertSame(VpsPayment::STATUS_PENDING, $server->orderPayment->status);
        $this->assertSame('400.00', $wallet->fresh()->balance);
        // The password is kept for the install that may yet be needed.
        $this->assertTrue(Cache::has('vps:secret:' . $server->id));
        $this->assertCount(1, $this->sentTo('POST', '/api/vps/v1/virtual-machines'));
    }

    /** "Could not read the machine list" is not "there is no machine". */
    public function test_a_machine_list_that_will_not_load_never_refunds(): void
    {
        $wallet = $this->fund(1000);
        $this->upstream(['POST /api/vps/v1/virtual-machines' => fn () => throw new ConnectionException('cURL error 28: timed out')]);

        $server = $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(), (string) Str::uuid());

        $this->upstream(['GET /api/vps/v1/virtual-machines' => Http::response(['message' => 'Server error'], 500)]);
        $this->travel(VpsProvisioningService::UNKNOWN_OUTCOME_MINUTES + 30)->minutes();

        $this->assertSame('unknown', $this->service()->reconcile($server->fresh()));
        $this->assertSame(VpsInstance::STATUS_PROVISIONING, $server->fresh()->status);
        $this->assertSame('400.00', $wallet->fresh()->balance);
    }

    /**
     * Two customers who chose the same hostname, both waiting on a lost
     * answer: adopting by name could hand one of them the other's server —
     * with the other's root password. A person decides.
     */
    public function test_two_orders_with_the_same_hostname_are_left_to_a_person(): void
    {
        $this->fund(1000);
        $this->upstream(['POST /api/vps/v1/virtual-machines' => fn () => throw new ConnectionException('cURL error 28: timed out')]);

        $mine = $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(), (string) Str::uuid());

        $other = User::factory()->create();
        Wallet::getOrCreateForUser($other->id)->deposit(1000, 'test funding');
        $theirs = $this->service()->order($other, $this->plan, '1m', $this->orderOptions(), (string) Str::uuid());

        $this->upstream([
            'GET /api/vps/v1/virtual-machines' => Http::response([$this->vm(['id' => 555010, 'subscription_id' => null])]),
        ]);

        $this->assertSame('ambiguous', $this->service()->reconcile($mine->fresh()));
        $this->assertSame('ambiguous', $this->service()->reconcile($theirs->fresh()));
        $this->assertNull($mine->fresh()->remote_vm_id);
        $this->assertNull($theirs->fresh()->remote_vm_id);
    }

    /** A 202 machine still wears the supplier's hostname: it is found as the one uninstalled machine of this plan. */
    public function test_an_uninstalled_machine_of_the_plan_is_found_when_the_hostname_cannot_match(): void
    {
        $this->fund(1000);
        $this->upstream(['POST /api/vps/v1/virtual-machines' => fn () => throw new ConnectionException('cURL error 28: timed out')]);

        $server = $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(), (string) Str::uuid());

        $blank = $this->vm(['id' => 555011, 'subscription_id' => 'sub_blank', 'state' => 'initial', 'hostname' => 'srv555011.hstgr.cloud', 'plan' => 'KVM 2']);

        $this->upstream([
            'GET /api/vps/v1/virtual-machines' => Http::response([
                $blank,
                // Another plan's machine is not this order's.
                $this->vm(['id' => 555012, 'subscription_id' => 'sub_big', 'state' => 'initial', 'hostname' => 'srv555012.hstgr.cloud', 'plan' => 'KVM 8']),
            ]),
            'POST /api/vps/v1/virtual-machines/555011/setup' => Http::response(['id' => 555011, 'state' => 'creating'] + $blank),
        ]);

        $this->assertSame('linked + setup', $this->service()->reconcile($server->fresh()));
        $this->assertSame(555011, (int) $server->fresh()->remote_vm_id);
        $this->assertSame('Str0ngPassw0rdX', $this->sentTo('POST', '/api/vps/v1/virtual-machines/555011/setup')[0]['body']['password']);
    }

    /** Upstream still says `initial` for a while after taking a setup. Sending it again failed healthy builds. */
    public function test_an_accepted_setup_is_not_sent_again_while_upstream_catches_up(): void
    {
        $this->fund(1000);

        $this->upstream([
            'POST /api/vps/v1/virtual-machines' => Http::response([
                'order' => ['id' => 1, 'subscription_id' => 'sub_init'],
                'virtual_machine' => $this->vm(['id' => 555013, 'subscription_id' => 'sub_init', 'state' => 'initial']),
            ]),
        ]);

        $server = $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(), (string) Str::uuid());

        $initial = $this->vm(['id' => 555013, 'subscription_id' => 'sub_init', 'state' => 'initial']);

        $this->upstream([
            'GET /api/vps/v1/virtual-machines/555013' => Http::response($initial),
            'POST /api/vps/v1/virtual-machines/555013/setup' => Http::response($initial),
        ]);

        $this->assertSame('setup-sent', $this->service()->reconcile($server->fresh()));

        $this->travel(3)->minutes();
        $this->assertSame('waiting', $this->service()->reconcile($server->fresh()));

        $this->assertCount(1, $this->sentTo('POST', '/api/vps/v1/virtual-machines/555013/setup'));
        $this->assertSame(0, (int) $server->fresh()->setup_attempts);
        $this->assertSame(VpsInstance::STATUS_PROVISIONING, $server->fresh()->status);
    }

    /** A timeout may have gone through with our card — it must not be refunded on a guess. */
    public function test_a_timed_out_purchase_is_found_upstream_instead_of_refunded(): void
    {
        $wallet = $this->fund(1000);

        $this->upstream([
            'POST /api/vps/v1/virtual-machines' => fn () => throw new ConnectionException('cURL error 28: timed out'),
        ]);

        $server = $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(), (string) Str::uuid());

        $this->assertSame(VpsInstance::STATUS_PROVISIONING, $server->status);
        $this->assertSame(VpsPayment::STATUS_PENDING, $server->orderPayment->status);
        $this->assertSame('400.00', $wallet->fresh()->balance);

        // It did go through: the machine shows up under the customer's hostname.
        $this->upstream([
            'GET /api/vps/v1/virtual-machines' => Http::response([$this->vm(['id' => 555003])]),
        ]);

        $this->assertSame('linked + active', $this->service()->reconcile($server->fresh()));
        $this->assertSame(VpsInstance::STATUS_ACTIVE, $server->fresh()->status);
        $this->assertSame(VpsPayment::STATUS_PAID, $server->fresh()->orderPayment->status);
        $this->assertSame('400.00', $wallet->fresh()->balance);
    }

    public function test_a_timed_out_purchase_that_never_appears_is_refunded_after_the_window(): void
    {
        $wallet = $this->fund(1000);

        $this->upstream([
            'POST /api/vps/v1/virtual-machines' => fn () => throw new ConnectionException('cURL error 28: timed out'),
        ]);

        $server = $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(), (string) Str::uuid());

        $this->upstream(['GET /api/vps/v1/virtual-machines' => Http::response([])]);

        // Too early: keep looking.
        $this->assertSame('waiting', $this->service()->reconcile($server->fresh()));

        $this->travel(VpsProvisioningService::UNKNOWN_OUTCOME_MINUTES + 1)->minutes();

        $this->assertSame('refunded', $this->service()->reconcile($server->fresh()));
        $this->assertSame('1000.00', $wallet->fresh()->balance);
    }

    /** The owner's own servers live in the same account — never claimed for an order. */
    public function test_an_older_machine_with_the_same_hostname_is_not_claimed(): void
    {
        $this->fund(1000);
        $this->upstream(['POST /api/vps/v1/virtual-machines' => fn () => throw new ConnectionException('timed out')]);

        $server = $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(), (string) Str::uuid());

        $this->upstream([
            'GET /api/vps/v1/virtual-machines' => Http::response([$this->vm(['id' => 100, 'created_at' => now()->subYear()->toIso8601String()])]),
        ]);

        $this->assertSame('waiting', $this->service()->reconcile($server->fresh()));
        $this->assertNull($server->fresh()->remote_vm_id);
    }

    public function test_a_machine_that_errors_while_building_is_flagged_not_refunded(): void
    {
        $wallet = $this->fund(1000);

        $this->upstream([
            'POST /api/vps/v1/virtual-machines' => Http::response([
                'order' => ['id' => 1, 'subscription_id' => 'sub_e'],
                'virtual_machine' => $this->vm(['id' => 555004, 'state' => 'creating']),
            ]),
        ]);

        $server = $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(), (string) Str::uuid());

        $this->upstream([
            'GET /api/vps/v1/virtual-machines/555004' => Http::response($this->vm(['id' => 555004, 'state' => 'error'])),
        ]);

        $this->assertSame('failed', $this->service()->reconcile($server->fresh()));
        $this->assertSame(VpsInstance::STATUS_FAILED, $server->fresh()->status);
        // Our money is spent upstream; a person decides about the customer's.
        $this->assertSame('400.00', $wallet->fresh()->balance);
    }

    public function test_admin_refund_is_only_for_servers_that_never_worked(): void
    {
        $wallet = $this->fund(1000);

        $this->upstream([
            'POST /api/vps/v1/virtual-machines' => Http::response([
                'order' => ['id' => 1, 'subscription_id' => 'sub_r'],
                'virtual_machine' => $this->vm(['id' => 555005]),
            ]),
        ]);

        $server = $this->service()->order($this->user, $this->plan, '1m', $this->orderOptions(), (string) Str::uuid());
        $this->assertSame(VpsInstance::STATUS_ACTIVE, $server->status);

        $this->expectException(VpsOrderException::class);
        $this->service()->adminRefund($server, 'test');
    }
}
