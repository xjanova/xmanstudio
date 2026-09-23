<?php

namespace Tests\Feature;

use App\Models\DomainContact;
use App\Models\DomainRegistration;
use App\Models\DomainTld;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\DomainPurchaseException;
use App\Services\DomainRegistrarService;
use App\Services\HostingerApiService;
use App\Support\UpstreamBilling;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\FakesSupplierApi;
use Tests\TestCase;

/**
 * What the audit of 2026-09-23 found in the domain shop, pinned down.
 *
 * Each one lost money or a domain without anybody noticing: an upstream
 * auto-renewal left on for customers who did not ask for one (paid with our
 * card), a 202 purchase that was never registered and then refunded while
 * the domain sat paid-for in our account, a timeout refunded although the
 * domain went through, a double submit able to refund a live domain.
 */
class DomainAuditFixesTest extends TestCase
{
    use FakesSupplierApi;
    use RefreshDatabase;

    protected User $user;

    protected DomainContact $contact;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setValue('hostinger_api_token', 'test-token');
        Setting::setValue('domain_margin_percent', 45);
        Setting::setValue('domain_price_rounding', 10);
        Cache::flush();
        RateLimiter::clear('hostinger-api');
        Mail::fake();

        DomainTld::updateOrCreate(['tld' => 'com'], [
            'item_id_register' => 'hostingerinth-domain-com-thb-1y',
            'item_id_renew' => 'hostingerinth-domain-com-thb-1y',
            'cost_usd_cents' => 31900,
            'renew_cost_usd_cents' => 51900,
            'cost_currency' => 'THB',
            'margin_percent' => null,
            'is_active' => true,
            'search_by_default' => true,
            'is_featured' => true,
        ]);

        $this->user = User::factory()->create();
        $this->contact = DomainContact::create([
            'user_id' => $this->user->id, 'first_name' => 'Somchai', 'last_name' => 'Jaidee',
            'email' => 'somchai@example.com', 'phone_country_code' => '+66', 'phone' => '812345678',
            'address1' => '1 Sukhumvit', 'city' => 'Bangkok', 'zip' => '10110', 'country' => 'TH',
        ]);

        Wallet::getOrCreateForUser($this->user->id)->deposit(5000, 'test funding');
    }

    protected function service(): DomainRegistrarService
    {
        return app(DomainRegistrarService::class);
    }

    protected function balance(): string
    {
        return (string) Wallet::getOrCreateForUser($this->user->id)->fresh()->balance;
    }

    protected function available(string $domain): array
    {
        return ['POST /api/domains/v1/availability' => [['domain' => $domain, 'is_available' => true]]];
    }

    // ============================================================ auto-renewal

    /** Left on, upstream renews with OUR card a domain the customer chose to let go. */
    public function test_upstream_auto_renewal_goes_off_even_when_the_customer_did_not_ask_for_auto_renew(): void
    {
        $this->upstream($this->available('letgo.com') + [
            'POST /api/domains/v1/whois' => ['id' => 741288],
            'POST /api/domains/v1/portfolio' => ['id' => 1, 'subscription_id' => 'sub_letgo', 'status' => 'completed'],
            'GET /api/domains/v1/portfolio/letgo.com' => ['domain' => 'letgo.com', 'status' => 'active', 'expires_at' => now()->addYear()->toIso8601String()],
        ]);

        $registration = $this->service()->register($this->user->id, 'letgo.com', $this->contact, ['auto_renew' => false]);

        $this->assertSame(DomainRegistration::STATUS_ACTIVE, $registration->status);
        $this->assertFalse($registration->auto_renew);
        $this->assertCount(1, $this->sentTo('DELETE', '/api/billing/v1/subscriptions/sub_letgo/auto-renewal/disable'));
    }

    // ============================================================= the 202 path

    /** Paid but never registered: pending_setup must be FINISHED, not refunded at the timeout. */
    public function test_a_pending_setup_domain_is_registered_with_the_customers_own_contact(): void
    {
        $this->upstream($this->available('slow.com') + [
            'POST /api/domains/v1/whois' => ['id' => 741288],
            'POST /api/domains/v1/portfolio' => Http::response(['id' => 2, 'subscription_id' => 'sub_slow', 'status' => 'payment_initiated'], 202),
        ]);

        $registration = $this->service()->register($this->user->id, 'slow.com', $this->contact);
        $this->assertSame(DomainRegistration::STATUS_REGISTERING, $registration->status);

        // Payment cleared upstream: the domain sits in our account, unregistered.
        // (Past the per-row poll back-off, so the job actually looks.)
        $this->travel(4)->minutes();
        $this->upstream([
            'GET /api/domains/v1/portfolio/slow.com' => ['domain' => 'slow.com', 'status' => 'pending_setup', 'expires_at' => null],
            'POST /api/domains/v1/portfolio/slow.com/setup' => ['message' => 'Request accepted'],
        ]);

        $this->artisan('domains:reconcile')->assertSuccessful();

        $setup = $this->sentTo('POST', '/api/domains/v1/portfolio/slow.com/setup');
        $this->assertCount(1, $setup);
        $this->assertSame(741288, $setup[0]['body']['domain_contacts']['owner_id']);
        $this->assertSame(DomainRegistration::STATUS_REGISTERING, $registration->fresh()->status);

        // Registered: it becomes active — and was never refunded.
        $this->travel(4)->minutes();
        $this->upstream([
            'GET /api/domains/v1/portfolio/slow.com' => ['domain' => 'slow.com', 'status' => 'active', 'expires_at' => now()->addYear()->toIso8601String()],
        ]);

        $this->artisan('domains:reconcile')->assertSuccessful();

        $this->assertSame(DomainRegistration::STATUS_ACTIVE, $registration->fresh()->status);
        $this->assertNull($registration->fresh()->refund_transaction_id);
    }

    /** A domain that is in our portfolio was paid for — the timer must never refund it. */
    public function test_a_domain_found_upstream_is_never_refunded_on_the_timer(): void
    {
        $this->upstream($this->available('stuck.com') + [
            'POST /api/domains/v1/whois' => ['id' => 5],
            'POST /api/domains/v1/portfolio' => Http::response(['id' => 3, 'subscription_id' => 'sub_stuck', 'status' => 'payment_initiated'], 202),
        ]);

        $registration = $this->service()->register($this->user->id, 'stuck.com', $this->contact);

        $this->travel(DomainRegistrarService::SETTLE_TIMEOUT_MINUTES + 5)->minutes();
        $this->upstream([
            'GET /api/domains/v1/portfolio/stuck.com' => ['domain' => 'stuck.com', 'status' => 'requested'],
        ]);

        $this->artisan('domains:reconcile')->assertSuccessful();

        $this->assertSame(DomainRegistration::STATUS_REGISTERING, $registration->fresh()->status);
        $this->assertNull($registration->fresh()->refund_transaction_id);
    }

    /**
     * A 202 is an order upstream took, paid with a charge still clearing: the
     * domain only appears once it clears. Refunded when it never does — after
     * a day, not after the ninety minutes a lost answer gets.
     */
    public function test_an_accepted_order_that_never_appears_is_refunded_after_a_day(): void
    {
        $before = $this->balance();

        $this->upstream($this->available('ghost.com') + [
            'POST /api/domains/v1/whois' => ['id' => 5],
            'POST /api/domains/v1/portfolio' => Http::response(['id' => 4, 'subscription_id' => null, 'status' => 'payment_initiated'], 202),
        ]);

        $registration = $this->service()->register($this->user->id, 'ghost.com', $this->contact);

        $this->upstream([
            'GET /api/domains/v1/portfolio/ghost.com' => Http::response(['message' => 'Not found'], 404),
            'GET /api/domains/v1/portfolio' => [['id' => 1, 'domain' => 'xman4289.com', 'status' => 'active']],
        ]);

        $this->travel(DomainRegistrarService::SETTLE_TIMEOUT_MINUTES + 5)->minutes();
        $this->artisan('domains:reconcile')->assertSuccessful();
        $this->assertSame(DomainRegistration::STATUS_REGISTERING, $registration->fresh()->status);

        $this->travel(DomainRegistrarService::ACCEPTED_TIMEOUT_MINUTES)->minutes();
        $this->artisan('domains:reconcile')->assertSuccessful();

        $this->assertSame(DomainRegistration::STATUS_REFUNDED, $registration->fresh()->status);
        $this->assertSame($before, $this->balance());
    }

    /** "Could not look" is not "not there": a lookup that fails refunds nothing, however long it lasts. */
    public function test_a_lost_answer_is_refunded_only_when_the_portfolio_list_says_it_is_not_there(): void
    {
        $before = $this->balance();

        $this->upstream($this->available('lost.com') + [
            'POST /api/domains/v1/whois' => ['id' => 5],
            'POST /api/domains/v1/portfolio' => fn () => throw new ConnectionException('cURL error 28: Operation timed out'),
        ]);

        $registration = $this->service()->register($this->user->id, 'lost.com', $this->contact);
        $this->assertSame(DomainRegistration::STATUS_REGISTERING, $registration->status);

        // The registrar will not answer at all.
        $this->upstream([
            'GET /api/domains/v1/portfolio/lost.com' => Http::response(['message' => 'Server error'], 500),
            'GET /api/domains/v1/portfolio' => Http::response(['message' => 'Server error'], 500),
        ]);

        $this->travel(DomainRegistrarService::SETTLE_TIMEOUT_MINUTES + 5)->minutes();
        $this->artisan('domains:reconcile')->assertSuccessful();

        $this->assertSame(DomainRegistration::STATUS_REGISTERING, $registration->fresh()->status);
        $this->assertNull($registration->fresh()->refund_transaction_id);

        // It answers, and the name is not ours: now it is known.
        $this->upstream([
            'GET /api/domains/v1/portfolio/lost.com' => Http::response(['message' => 'Server error'], 500),
            'GET /api/domains/v1/portfolio' => [['id' => 1, 'domain' => 'xman4289.com', 'status' => 'active']],
        ]);

        $this->travel(4)->minutes();
        $this->artisan('domains:reconcile')->assertSuccessful();

        $this->assertSame(DomainRegistration::STATUS_REFUNDED, $registration->fresh()->status);
        $this->assertSame($before, $this->balance());
    }

    // ================================================================ timeouts

    public function test_a_timed_out_purchase_is_looked_up_instead_of_refunded(): void
    {
        $this->upstream($this->available('maybe.com') + [
            'POST /api/domains/v1/whois' => ['id' => 5],
            'POST /api/domains/v1/portfolio' => fn () => throw new ConnectionException('cURL error 28: timed out'),
        ]);

        $registration = $this->service()->register($this->user->id, 'maybe.com', $this->contact);

        $this->assertSame(DomainRegistration::STATUS_REGISTERING, $registration->status);
        $this->assertNull($registration->refund_transaction_id);

        // It went through after all.
        $this->travel(4)->minutes();
        $this->upstream([
            'GET /api/domains/v1/portfolio/maybe.com' => ['domain' => 'maybe.com', 'status' => 'active', 'expires_at' => now()->addYear()->toIso8601String()],
        ]);

        $this->artisan('domains:reconcile')->assertSuccessful();
        $this->assertSame(DomainRegistration::STATUS_ACTIVE, $registration->fresh()->status);
    }

    /** Upstream falling over mid-order can be after it registered the name: as unknown as a timeout. */
    public function test_a_server_error_on_purchase_is_looked_up_not_refunded(): void
    {
        $this->upstream($this->available('crash.com') + [
            'POST /api/domains/v1/whois' => ['id' => 5],
            'POST /api/domains/v1/portfolio' => Http::response(['message' => 'Internal server error'], 500),
        ]);

        $registration = $this->service()->register($this->user->id, 'crash.com', $this->contact);

        $this->assertSame(DomainRegistration::STATUS_REGISTERING, $registration->status);
        $this->assertNull($registration->refund_transaction_id);
        // Nothing in it about payment: sales stay open.
        $this->assertFalse(UpstreamBilling::isPaused());
    }

    /** A 5xx whose words are about our card: nothing refunded yet, but no next customer charged into it. */
    public function test_a_server_error_about_payment_pauses_sales_without_refunding(): void
    {
        $this->upstream($this->available('card5.com') + [
            'POST /api/domains/v1/whois' => ['id' => 5],
            'POST /api/domains/v1/portfolio' => Http::response(['message' => 'Payment processing failed'], 500),
        ]);

        $registration = $this->service()->register($this->user->id, 'card5.com', $this->contact);

        $this->assertSame(DomainRegistration::STATUS_REGISTERING, $registration->status);
        $this->assertNull($registration->refund_transaction_id);
        $this->assertTrue(UpstreamBilling::isPaused());
    }

    /** Refused before a byte went out: nothing can have been bought, so the money goes back at once. */
    public function test_a_refused_connection_is_refunded_on_the_spot(): void
    {
        $before = $this->balance();

        $this->upstream($this->available('down.com') + [
            'POST /api/domains/v1/whois' => ['id' => 5],
            'POST /api/domains/v1/portfolio' => fn () => throw new ConnectionException('cURL error 7: Failed to connect to developers.hostinger.com port 443'),
        ]);

        $registration = $this->service()->register($this->user->id, 'down.com', $this->contact);

        $this->assertSame(DomainRegistration::STATUS_REFUNDED, $registration->status);
        $this->assertSame($before, $this->balance());
    }

    /** The HTTP client must never replay an order on its own: a retried POST is a second domain on our card. */
    public function test_a_purchase_is_sent_once_even_when_the_connection_drops(): void
    {
        $this->upstream($this->available('once.com') + [
            'POST /api/domains/v1/whois' => ['id' => 5],
            'POST /api/domains/v1/portfolio' => fn () => throw new ConnectionException('cURL error 52: Empty reply from server'),
        ]);

        $this->service()->register($this->user->id, 'once.com', $this->contact);

        $this->assertCount(1, $this->sentTo('POST', '/api/domains/v1/portfolio'));
    }

    /** Reads are safe to repeat, and are. */
    public function test_a_read_is_retried_after_a_dropped_connection(): void
    {
        $calls = 0;

        $this->upstream([
            'GET /api/domains/v1/portfolio/flaky.com' => function () use (&$calls) {
                if (++$calls === 1) {
                    throw new ConnectionException('cURL error 56: Connection reset by peer');
                }

                return Http::response(['domain' => 'flaky.com', 'status' => 'active']);
            },
        ]);

        $details = app(HostingerApiService::class)->getDomain('flaky.com');

        $this->assertSame('active', $details['status'] ?? null);
        $this->assertSame(2, $calls);
    }

    /**
     * Two orders for one name, one of them with an answer we never heard:
     * "the name is in our portfolio" cannot say whose it is. Neither is
     * activated nor refunded by the machine.
     */
    public function test_two_orders_for_one_name_are_left_to_a_person(): void
    {
        $other = User::factory()->create();

        foreach ([$this->user, $other] as $i => $owner) {
            DomainRegistration::create([
                'user_id' => $owner->id, 'domain' => 'twice.com', 'tld' => 'com',
                'status' => DomainRegistration::STATUS_REGISTERING, 'kind' => 'register',
                'domain_contact_id' => $this->contact->id, 'price_thb' => 470,
                'idempotency_key' => 'k-twice-' . $i,
                'created_at' => now()->subMinutes(30),
            ]);
        }

        $this->upstream([
            'GET /api/domains/v1/portfolio/twice.com' => ['domain' => 'twice.com', 'status' => 'active', 'expires_at' => now()->addYear()->toIso8601String()],
        ]);

        $this->artisan('domains:reconcile')->assertSuccessful();

        $this->assertSame(0, DomainRegistration::where('domain', 'twice.com')->where('status', DomainRegistration::STATUS_ACTIVE)->count());
        $this->assertSame(2, DomainRegistration::where('domain', 'twice.com')->where('status', DomainRegistration::STATUS_REGISTERING)->count());
    }

    // ================================================================ renewals

    protected function pendingRenewal(DomainRegistration $domain, array $overrides = []): DomainRegistration
    {
        return DomainRegistration::create($overrides + [
            'user_id' => $domain->user_id, 'domain' => $domain->domain, 'tld' => 'com',
            'status' => DomainRegistration::STATUS_PENDING, 'kind' => DomainRegistration::KIND_RENEW,
            'renewal_of' => $domain->id, 'remote_subscription_id' => $domain->remote_subscription_id,
            'price_thb' => 760, 'idempotency_key' => 'k-renew-' . $domain->id,
            'previous_expires_at' => $domain->expires_at,
            'created_at' => now()->subMinutes(20),
        ]);
    }

    /**
     * The daily sync copies the registrar's new date onto the domain as soon
     * as a renewal lands. Measured against THAT, a renewal that went through
     * looked like one that never did — and was refunded.
     */
    public function test_a_renewal_that_went_through_is_confirmed_after_the_sync_moved_the_date(): void
    {
        $domain = $this->liveDomain('kept.com');
        $old = now()->addDays(10)->startOfDay();
        $new = $old->copy()->addYear();
        $domain->update(['expires_at' => $old]);

        $renewal = $this->pendingRenewal($domain->fresh());

        // The sync got there first.
        $domain->update(['expires_at' => $new]);

        $this->upstream([
            'GET /api/domains/v1/portfolio/kept.com' => ['domain' => 'kept.com', 'status' => 'active', 'expires_at' => $new->toIso8601String()],
        ]);

        $this->travel(DomainRegistrarService::SETTLE_TIMEOUT_MINUTES)->minutes();

        $this->assertSame('confirmed', $this->service()->settleRenewal($renewal->fresh()));
        $this->assertSame(DomainRegistration::STATUS_ACTIVE, $renewal->fresh()->status);
        $this->assertNull($renewal->fresh()->refund_transaction_id);
    }

    /** A 202 renewal is a charge still clearing: the date moves when the registrar's does, not before. */
    public function test_a_renewal_still_clearing_does_not_move_the_date_yet(): void
    {
        $domain = $this->liveDomain('clearing.com');
        $before = $domain->expires_at->copy();

        $this->upstream([
            'POST /api/billing/v1/subscriptions/sub_clearing.com/renew' => Http::response(['id' => 77, 'subscription_id' => 'sub_clearing.com', 'status' => 'payment_initiated'], 202),
        ]);

        $renewal = $this->service()->renew($domain);

        $this->assertSame(DomainRegistration::STATUS_PENDING, $renewal->status);
        $this->assertSame('77', $renewal->remote_order_id);
        $this->assertTrue($domain->fresh()->expires_at->equalTo($before));
        // And no second charge while it clears.
        $this->assertFalse($domain->fresh()->canRenew());

        $this->upstream([
            'GET /api/domains/v1/portfolio/clearing.com' => ['domain' => 'clearing.com', 'status' => 'active', 'expires_at' => $before->copy()->addYear()->toIso8601String()],
        ]);

        $this->travel(10)->minutes();

        $this->assertSame('confirmed', $this->service()->settleRenewal($renewal->fresh()));
        $this->assertTrue($domain->fresh()->expires_at->gt($before->copy()->addMonths(11)));
    }

    /** Could not read the registrar's date: nothing is decided, however long it has been. */
    public function test_a_renewal_is_never_refunded_on_a_failed_lookup(): void
    {
        $domain = $this->liveDomain('quiet.com');
        $renewal = $this->pendingRenewal($domain);

        $this->upstream([
            'GET /api/domains/v1/portfolio/quiet.com' => Http::response(['message' => 'Server error'], 500),
        ]);

        $this->travel(DomainRegistrarService::SETTLE_TIMEOUT_MINUTES + 30)->minutes();

        $this->assertSame('unknown', $this->service()->settleRenewal($renewal->fresh()));
        $this->assertSame(DomainRegistration::STATUS_PENDING, $renewal->fresh()->status);
        $this->assertNull($renewal->fresh()->refund_transaction_id);
    }

    /** Past the grace period a restore fee lands on our card: not a self-service button. */
    public function test_a_domain_lapsed_past_the_grace_period_cannot_be_renewed_from_the_wallet(): void
    {
        $domain = $this->liveDomain('late.com');
        $domain->update([
            'status' => DomainRegistration::STATUS_EXPIRED,
            'expires_at' => now()->subDays(DomainRegistration::RENEW_GRACE_DAYS + 3),
        ]);
        $this->upstream();

        $this->assertFalse($domain->fresh()->canRenew());

        try {
            $this->service()->renew($domain->fresh());
            $this->fail('Expected a renewal past the grace period to be refused');
        } catch (DomainPurchaseException $e) {
            $this->assertStringContainsString('ติดต่อทีมงาน', $e->getMessage());
        }

        $this->assertCount(0, $this->sentTo('POST', '*/renew'));
    }

    // ============================================================ double submit

    /** The first request is still talking to the registrar; the second must not buy or refund. */
    public function test_a_duplicate_submit_in_flight_neither_buys_nor_refunds(): void
    {
        $row = DomainRegistration::create([
            'user_id' => $this->user->id, 'domain' => 'race.com', 'tld' => 'com',
            'status' => DomainRegistration::STATUS_PENDING, 'kind' => 'register',
            'domain_contact_id' => $this->contact->id, 'price_thb' => 470,
            'idempotency_key' => DomainRegistration::makeIdempotencyKey($this->user->id, 'race.com', 'register'),
        ]);

        $this->upstream($this->available('race.com') + [
            'POST /api/domains/v1/portfolio' => Http::response(['message' => 'Domain is not available'], 422),
        ]);

        $again = $this->service()->register($this->user->id, 'race.com', $this->contact);

        $this->assertSame($row->id, $again->id);
        $this->assertCount(0, $this->sentTo('POST', '/api/domains/v1/portfolio'));
        $this->assertNull($row->fresh()->refund_transaction_id);
    }

    /** A refunded attempt must not block buying the same name again the same day. */
    public function test_a_refunded_attempt_does_not_block_a_retry_today(): void
    {
        $this->upstream($this->available('retry.com') + [
            'POST /api/domains/v1/whois' => ['id' => 5],
            'POST /api/domains/v1/portfolio' => Http::response(['message' => 'The registry rejected the request'], 422),
        ]);

        $first = $this->service()->register($this->user->id, 'retry.com', $this->contact);
        $this->assertSame(DomainRegistration::STATUS_REFUNDED, $first->status);

        $this->upstream($this->available('retry.com') + [
            'POST /api/domains/v1/whois' => ['id' => 5],
            'POST /api/domains/v1/portfolio' => ['id' => 9, 'subscription_id' => 'sub_retry', 'status' => 'completed'],
            'GET /api/domains/v1/portfolio/retry.com' => ['domain' => 'retry.com', 'status' => 'active'],
        ]);

        $second = $this->service()->register($this->user->id, 'retry.com', $this->contact);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(DomainRegistration::STATUS_ACTIVE, $second->status);
    }

    // ======================================================== our card refused

    public function test_a_payment_refusal_pauses_sales(): void
    {
        $this->upstream($this->available('card.com') + [
            'POST /api/domains/v1/whois' => ['id' => 5],
            'POST /api/domains/v1/portfolio' => Http::response(['message' => 'Your card was declined'], 402),
        ]);

        $registration = $this->service()->register($this->user->id, 'card.com', $this->contact);

        $this->assertSame(DomainRegistration::STATUS_REFUNDED, $registration->status);
        $this->assertTrue(UpstreamBilling::isPaused());
    }

    // ================================================== transfer-out, forwarding

    protected function liveDomain(string $name = 'mine.com', ?int $userId = null): DomainRegistration
    {
        return DomainRegistration::create([
            'user_id' => $userId ?? $this->user->id, 'domain' => $name, 'tld' => 'com',
            'status' => DomainRegistration::STATUS_ACTIVE, 'kind' => 'register', 'price_thb' => 470,
            'idempotency_key' => 'k-' . $name, 'remote_subscription_id' => 'sub_' . $name,
            'expires_at' => now()->addMonths(6), 'domain_contact_id' => $this->contact->id,
        ]);
    }

    /** The transfer code alone does nothing while the domain is locked. */
    public function test_the_customer_can_unlock_their_own_domain_for_transfer(): void
    {
        $domain = $this->liveDomain();
        $someoneElses = $this->liveDomain('theirs.com', User::factory()->create()->id);
        $this->upstream();

        $this->actingAs($this->user)
            ->post(route('customer.domains.lock', $someoneElses->id), ['lock' => 0])
            ->assertNotFound();

        $this->actingAs($this->user)
            ->post(route('customer.domains.lock', $domain->id), ['lock' => 0])
            ->assertSessionHas('success');

        $this->assertCount(1, $this->sentTo('DELETE', '/api/domains/v1/portfolio/mine.com/domain-lock'));
    }

    public function test_forwarding_rejects_a_loop_and_saves_a_real_target(): void
    {
        $domain = $this->liveDomain();
        $this->upstream([
            'PUT /api/domains/v1/forwarding/mine.com' => Http::response(['message' => 'Not found'], 404),
            'POST /api/domains/v1/forwarding' => ['domain' => 'mine.com', 'redirect_type' => '301', 'redirect_url' => 'https://facebook.com/mypage'],
        ]);

        $this->actingAs($this->user)
            ->post(route('customer.domains.forwarding', $domain->id), ['redirect_url' => 'https://www.mine.com/x', 'redirect_type' => '301'])
            ->assertSessionHas('error');

        $this->assertCount(0, $this->sentTo('POST', '/api/domains/v1/forwarding'));

        $this->actingAs($this->user)
            ->post(route('customer.domains.forwarding', $domain->id), ['redirect_url' => 'https://facebook.com/mypage', 'redirect_type' => '301'])
            ->assertSessionHas('success');

        // No forwarding existed, so the update fell through to a create.
        $created = $this->sentTo('POST', '/api/domains/v1/forwarding');
        $this->assertCount(1, $created);
        $this->assertSame('mine.com', $created[0]['body']['domain']);
    }

    public function test_only_this_domains_own_snapshots_can_be_restored(): void
    {
        $domain = $this->liveDomain();
        $this->upstream([
            'GET /api/dns/v1/snapshots/mine.com' => [['id' => 11, 'reason' => 'update', 'created_at' => now()->subDay()->toIso8601String()]],
        ]);

        $this->actingAs($this->user)
            ->post(route('customer.domains.dns-restore', [$domain->id, 999]))
            ->assertSessionHas('error');

        $this->assertCount(0, $this->sentTo('POST', '*/restore'));

        $this->actingAs($this->user)
            ->post(route('customer.domains.dns-restore', [$domain->id, 11]))
            ->assertSessionHas('success');

        $this->assertCount(1, $this->sentTo('POST', '/api/dns/v1/snapshots/mine.com/11/restore'));
    }

    // ================================================================== status

    public function test_a_lapsed_domain_is_marked_expired_and_stays_renewable(): void
    {
        $domain = $this->liveDomain('old.com');
        $domain->update(['expires_at' => now()->subDays(2)]);

        $this->upstream([
            'GET /api/domains/v1/portfolio/old.com' => ['domain' => 'old.com', 'status' => 'expired', 'expires_at' => now()->subDays(2)->toIso8601String()],
        ]);

        $this->artisan('domains:sync-status')->assertSuccessful();

        $domain->refresh();
        $this->assertSame(DomainRegistration::STATUS_EXPIRED, $domain->status);
        // The grace period is exactly when the customer comes looking.
        $this->assertTrue($domain->canRenew());
    }

    public function test_a_domain_without_a_subscription_is_linked_by_a_single_name_match(): void
    {
        $domain = $this->liveDomain('link.com');
        $domain->update(['remote_subscription_id' => null]);

        $this->upstream([
            'GET /api/billing/v1/subscriptions' => [
                ['id' => 'sub_other', 'name' => 'other.com', 'is_auto_renewed' => false],
                ['id' => 'sub_link', 'name' => 'Domain link.com', 'is_auto_renewed' => true],
            ],
        ]);

        $this->artisan('domains:sync-status')->assertSuccessful();

        $this->assertSame('sub_link', $domain->fresh()->remote_subscription_id);
        $this->assertCount(1, $this->sentTo('DELETE', '/api/billing/v1/subscriptions/sub_link/auto-renewal/disable'));
    }

    /** "ample.com" is inside "example.com": a substring match would renew somebody else's domain. */
    public function test_a_subscription_is_linked_only_on_the_whole_name(): void
    {
        $domain = $this->liveDomain('ample.com');
        $domain->update(['remote_subscription_id' => null]);

        $this->upstream([
            'GET /api/billing/v1/subscriptions' => [
                ['id' => 'sub_example', 'name' => 'Domain example.com', 'is_auto_renewed' => true],
                ['id' => 'sub_au', 'name' => 'ample.com.au', 'is_auto_renewed' => true],
            ],
        ]);

        $this->assertNull($this->service()->linkSubscription($domain));
        $this->assertNull($domain->fresh()->remote_subscription_id);
    }

    // ============================================================ price labels

    /** The owner's rule: every price says it is the first year, and what the years after cost. */
    public function test_the_shop_labels_first_year_and_following_year_prices(): void
    {
        User::factory()->create(['role' => 'super_admin']);
        $this->upstream();

        // .com: first year 319 × 1.45 = 462.55 → 470 ; renewal 519 × 1.45 = 752.55 → 760
        $this->get(route('domains.index'))
            ->assertOk()
            ->assertSee('ปีแรก')
            ->assertSee('470 ฿')
            ->assertSee('→ 760 ฿', false);

        $this->get(route('domains.pricing'))
            ->assertOk()
            ->assertSee('ปีแรก')
            ->assertSee('ปีต่อไป (ต่อปี)')
            ->assertSee('760 ฿');
    }
}
