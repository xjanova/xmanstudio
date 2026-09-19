<?php

namespace Tests\Feature;

use App\Mail\DomainRenewalNoticeMail;
use App\Models\DomainContact;
use App\Models\DomainRegistration;
use App\Models\DomainTld;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\DomainPurchaseException;
use App\Services\DomainRegistrarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Renewals — the half of the domain shop that was promised and never built.
 *
 * The customer page says, in Thai and English, that we charge the wallet thirty
 * days before expiry and always warn first. Until now `auto_renew` was a flag
 * nothing read: a customer could switch it on, keep money in their wallet, and
 * still lose the domain, having been told twice that they would not.
 *
 * The rules these tests hold to:
 *   — no charge without a warning that went out days earlier
 *   — never charge twice for the same period
 *   — a renewal that fails upstream gives the money back
 *   — an empty wallet is not a failure, it is a reason to try again tomorrow
 */
class DomainRenewalTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected DomainRegistration $domain;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setValue('hostinger_api_token', 'test-token');
        Setting::setValue('domain_margin_percent', 45);
        Setting::setValue('domain_price_rounding', 10);
        Cache::flush();
        RateLimiter::clear('hostinger-api');

        DomainTld::updateOrCreate(['tld' => 'com'], [
            'item_id_register' => 'test-domain-com-thb-1y',
            'item_id_renew' => 'test-domain-com-thb-1y',
            'cost_usd_cents' => 31900,
            'renew_cost_usd_cents' => 51900,
            'cost_currency' => 'THB',
            'margin_percent' => null,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create();

        $contact = DomainContact::create([
            'user_id' => $this->user->id,
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'email' => 'somchai@example.com',
            'phone_country_code' => '+66',
            'phone' => '812345678',
            'address1' => '1 Sukhumvit',
            'city' => 'Bangkok',
            'zip' => '10110',
            'country' => 'TH',
        ]);

        $this->domain = DomainRegistration::create([
            'user_id' => $this->user->id,
            'domain' => 'example.com',
            'tld' => 'com',
            'status' => DomainRegistration::STATUS_ACTIVE,
            'kind' => DomainRegistration::KIND_REGISTER,
            'domain_contact_id' => $contact->id,
            'remote_subscription_id' => 'sub-123',
            'price_thb' => 470,
            'cost_usd_cents' => 31900,
            'cost_currency' => 'THB',
            'fx_rate' => 36.5,
            'idempotency_key' => 'seed-' . uniqid(),
            'auto_renew' => true,
            'registered_at' => now()->subYear(),
            'expires_at' => now()->addDays(25),
        ]);
    }

    /** ฿519 renewal cost at 45% margin, rounded up to 10 → 760. */
    protected function renewalPrice(): float
    {
        return 760.0;
    }

    protected function fund(float $amount): Wallet
    {
        $wallet = Wallet::getOrCreateForUser($this->user->id);
        $wallet->deposit($amount, 'test funding');

        return $wallet->fresh();
    }

    protected function service(): DomainRegistrarService
    {
        return app(DomainRegistrarService::class);
    }

    // ───────────────────────────────────────── the money path

    public function test_a_renewal_charges_the_wallet_and_moves_the_expiry(): void
    {
        Http::fake(['*' => Http::response(['id' => 'order-9'], 200)]);
        $this->fund(2000);
        $before = $this->domain->expires_at->copy();

        $renewal = $this->service()->renew($this->domain);

        $this->assertSame(DomainRegistration::STATUS_ACTIVE, $renewal->status);
        $this->assertSame(DomainRegistration::KIND_RENEW, $renewal->kind);
        $this->assertSame($this->domain->id, $renewal->renewal_of);
        $this->assertEqualsWithDelta($this->renewalPrice(), (float) $renewal->price_thb, 0.01);

        // The customer paid, so the year has to actually be added.
        $this->assertTrue($this->domain->fresh()->expires_at->greaterThan($before));
        $this->assertEqualsWithDelta(
            2000 - $this->renewalPrice(),
            (float) Wallet::getOrCreateForUser($this->user->id)->fresh()->balance,
            0.01,
        );
    }

    public function test_renewing_uses_the_renewal_price_not_the_first_year_price(): void
    {
        Http::fake(['*' => Http::response([], 200)]);
        $this->fund(2000);

        $renewal = $this->service()->renew($this->domain);

        // First year is 319 → 470. Renewal is 519 → 760. Quoting the cheaper
        // one every year is how a reseller loses money quietly.
        $this->assertEqualsWithDelta(760.0, (float) $renewal->price_thb, 0.01);
        $this->assertNotEqualsWithDelta(470.0, (float) $renewal->price_thb, 0.01);
    }

    public function test_an_empty_wallet_is_refused_without_charging(): void
    {
        Http::fake(['*' => Http::response([], 200)]);
        $this->fund(100);

        $this->expectException(DomainPurchaseException::class);

        try {
            $this->service()->renew($this->domain);
        } finally {
            $this->assertSame(0, DomainRegistration::where('kind', DomainRegistration::KIND_RENEW)->count());
            $this->assertEqualsWithDelta(100.0, (float) Wallet::getOrCreateForUser($this->user->id)->fresh()->balance, 0.01);
        }
    }

    public function test_a_double_tap_does_not_buy_two_years(): void
    {
        Http::fake(['*' => Http::response([], 200)]);
        $this->fund(5000);

        $first = $this->service()->renew($this->domain);
        $second = $this->service()->renew($this->domain->fresh());

        $this->assertSame($first->id, $second->id, 'the second press must return the first renewal');
        $this->assertSame(1, DomainRegistration::where('kind', DomainRegistration::KIND_RENEW)->count());
        $this->assertEqualsWithDelta(
            5000 - $this->renewalPrice(),
            (float) Wallet::getOrCreateForUser($this->user->id)->fresh()->balance,
            0.01,
            'charged once, not twice',
        );
    }

    public function test_an_upstream_failure_refunds_in_full(): void
    {
        Http::fake(['*' => Http::response(['message' => 'nope'], 500)]);
        $this->fund(2000);
        $before = $this->domain->expires_at->copy();

        $renewal = $this->service()->renew($this->domain);

        $this->assertSame(DomainRegistration::STATUS_REFUNDED, $renewal->status);
        $this->assertNotNull($renewal->refund_transaction_id);
        $this->assertEqualsWithDelta(2000.0, (float) Wallet::getOrCreateForUser($this->user->id)->fresh()->balance, 0.01);
        $this->assertEquals($before, $this->domain->fresh()->expires_at, 'a refunded renewal must not extend anything');
    }

    public function test_a_domain_with_no_upstream_handle_is_not_charged(): void
    {
        $this->fund(2000);
        $this->domain->update(['remote_subscription_id' => null]);

        $this->expectException(DomainPurchaseException::class);

        try {
            $this->service()->renew($this->domain->fresh());
        } finally {
            $this->assertEqualsWithDelta(2000.0, (float) Wallet::getOrCreateForUser($this->user->id)->fresh()->balance, 0.01);
        }
    }

    // ───────────────────────────────────────── warn before charging

    public function test_nothing_is_charged_before_a_warning_has_been_sent(): void
    {
        Mail::fake();
        Http::fake(['*' => Http::response([], 200)]);
        $this->fund(2000);
        $this->domain->update(['expires_at' => now()->addDays(20)]);

        // First run: inside the charge window, but no notice has gone out yet.
        $this->artisan('domains:renew')->assertSuccessful();

        Mail::assertSent(DomainRenewalNoticeMail::class, 1);
        $this->assertSame(0, DomainRegistration::where('kind', DomainRegistration::KIND_RENEW)->count(),
            'the warning and the charge must not land in the same minute');
        $this->assertEqualsWithDelta(2000.0, (float) Wallet::getOrCreateForUser($this->user->id)->fresh()->balance, 0.01);
    }

    public function test_the_charge_follows_once_the_warning_has_had_time_to_be_read(): void
    {
        Mail::fake();
        Http::fake(['*' => Http::response([], 200)]);
        $this->fund(2000);
        $this->domain->update([
            'expires_at' => now()->addDays(20),
            'renewal_notice_sent_at' => now()->subDays(4),
        ]);

        $this->artisan('domains:renew')->assertSuccessful();

        $this->assertSame(1, DomainRegistration::where('kind', DomainRegistration::KIND_RENEW)->count());
        $this->assertEqualsWithDelta(
            2000 - $this->renewalPrice(),
            (float) Wallet::getOrCreateForUser($this->user->id)->fresh()->balance,
            0.01,
        );
    }

    public function test_only_one_warning_goes_out_per_period(): void
    {
        Mail::fake();
        Http::fake(['*' => Http::response([], 200)]);
        $this->domain->update(['expires_at' => now()->addDays(35)]);

        $this->artisan('domains:renew')->assertSuccessful();
        $this->artisan('domains:renew')->assertSuccessful();

        Mail::assertSent(DomainRenewalNoticeMail::class, 1);
    }

    public function test_a_successful_renewal_arms_next_years_warning(): void
    {
        Http::fake(['*' => Http::response([], 200)]);
        $this->fund(2000);
        $this->domain->update(['renewal_notice_sent_at' => now()->subDays(5)]);

        $this->service()->renew($this->domain);

        $this->assertNull($this->domain->fresh()->renewal_notice_sent_at,
            'the lock has to clear, or the customer is never warned again');
    }

    public function test_a_domain_without_auto_renew_is_left_alone(): void
    {
        Mail::fake();
        Http::fake(['*' => Http::response([], 200)]);
        $this->fund(2000);
        $this->domain->update([
            'auto_renew' => false,
            'expires_at' => now()->addDays(20),
            'renewal_notice_sent_at' => now()->subDays(5),
        ]);

        $this->artisan('domains:renew')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame(0, DomainRegistration::where('kind', DomainRegistration::KIND_RENEW)->count());
        $this->assertEqualsWithDelta(2000.0, (float) Wallet::getOrCreateForUser($this->user->id)->fresh()->balance, 0.01);
    }

    public function test_an_empty_wallet_is_skipped_and_kept_for_tomorrow(): void
    {
        Mail::fake();
        Http::fake(['*' => Http::response([], 200)]);
        $this->fund(100);
        $this->domain->update([
            'expires_at' => now()->addDays(20),
            'renewal_notice_sent_at' => now()->subDays(5),
        ]);

        // Not an error: there are twenty more days to top up.
        $this->artisan('domains:renew')->assertSuccessful();

        $this->assertSame(0, DomainRegistration::where('kind', DomainRegistration::KIND_RENEW)->count());
        $this->assertTrue($this->domain->fresh()->auto_renew, 'the switch must stay on');
    }

    // ───────────────────────────────────────── the customer's own button

    public function test_the_owner_can_renew_from_their_domain_page(): void
    {
        Http::fake(['*' => Http::response([], 200)]);
        $this->fund(2000);

        $this->actingAs($this->user)
            ->post(route('customer.domains.renew', $this->domain->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(1, DomainRegistration::where('kind', DomainRegistration::KIND_RENEW)->count());
    }

    public function test_renewing_with_too_little_money_says_so_instead_of_throwing(): void
    {
        Http::fake(['*' => Http::response([], 200)]);
        $this->fund(50);

        $this->actingAs($this->user)
            ->post(route('customer.domains.renew', $this->domain->id))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_nobody_else_can_renew_your_domain(): void
    {
        $stranger = User::factory()->create();
        Wallet::getOrCreateForUser($stranger->id)->deposit(5000, 'test');

        $this->actingAs($stranger)
            ->post(route('customer.domains.renew', $this->domain->id))
            ->assertNotFound();

        $this->assertSame(0, DomainRegistration::where('kind', DomainRegistration::KIND_RENEW)->count());
    }

    // ───────────────────────────────────────── the list stays one row per name

    public function test_a_renewal_does_not_appear_as_a_second_domain(): void
    {
        Http::fake(['*' => Http::response([], 200)]);
        $this->fund(2000);
        $this->service()->renew($this->domain);

        $response = $this->actingAs($this->user)->get(route('customer.domains.index'));

        $response->assertOk();
        // Two rows in the table, one domain in the list. Counting the name in
        // the HTML would be counting the layout instead: it appears in the
        // link, the heading and the title, and how many times depends on which
        // theme is active.
        $this->assertSame(2, DomainRegistration::where('user_id', $this->user->id)->count());
        $response->assertViewHas('domains', fn ($domains) => $domains->count() === 1
            && $domains->first()->kind === DomainRegistration::KIND_REGISTER);
    }
}
