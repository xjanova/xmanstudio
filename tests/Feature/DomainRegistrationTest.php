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
use App\Support\DomainPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * The money paths.
 *
 * Every test here is a way a customer loses money or gets something for
 * free, and each one has been a real bug in some shop somewhere: the double
 * tap that charges twice, the failed API call that keeps the payment, the
 * domain that someone else registered while the form was open.
 */
class DomainRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected DomainContact $contact;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setValue('hostinger_api_token', 'test-token');
        Setting::setValue('domain_usd_thb_rate', 36.5);
        Setting::setValue('domain_margin_percent', 45);
        Setting::setValue('domain_price_rounding', 10);
        Cache::flush();
        RateLimiter::clear('hostinger-api');

        // The catalogue migration already seeds .com, so pin it to known
        // numbers rather than inserting a duplicate.
        DomainTld::updateOrCreate(['tld' => 'com'], [
            'item_id_register' => 'test-domain-com-usd-1y',
            'item_id_renew' => 'test-domain-com-usd-1y',
            'cost_usd_cents' => 1099,
            'renew_cost_usd_cents' => 1599,
            'margin_percent' => null,
            'is_active' => true,
            'search_by_default' => true,
        ]);

        $this->user = User::factory()->create();

        $this->contact = DomainContact::create([
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

    /** The catalogue price is cost × rate × (1 + margin), rounded up. */
    public function test_price_is_derived_from_cost_and_margin(): void
    {
        $tld = DomainTld::where('tld', 'com')->first();

        // 10.99 × 36.5 = 401.13 → ×1.45 = 581.64 → round up to 10 = 590
        $this->assertSame(590.0, $tld->registerPriceThb());
        // Renewal uses the renewal cost, not the register cost.
        $this->assertSame(850.0, $tld->renewPriceThb());
        $this->assertTrue($tld->renewalIsDearer());
    }

    /** A negative margin in settings must not sell below cost. */
    public function test_negative_margin_falls_back_to_the_default(): void
    {
        Setting::setValue('domain_margin_percent', -50);
        Cache::flush();

        $this->assertSame(DomainPricing::DEFAULT_MARGIN, DomainPricing::defaultMargin());
    }

    /** A zero exchange rate would price the whole catalogue at nothing. */
    public function test_zero_fx_rate_falls_back_rather_than_selling_free(): void
    {
        Setting::setValue('domain_usd_thb_rate', 0);
        Cache::flush();

        $this->assertSame(DomainPricing::DEFAULT_FX_RATE, DomainPricing::fxRate());
        $this->assertGreaterThan(0, DomainTld::where('tld', 'com')->first()->registerPriceThb());
    }

    public function test_successful_registration_debits_once_and_activates(): void
    {
        $wallet = $this->fund(1000);

        Http::fake([
            '*availability*' => Http::response(['data' => [['domain' => 'myshop.com', 'is_available' => true]]]),
            '*whois*' => Http::response(['id' => 741288]),
            '*portfolio/myshop.com*' => Http::response(['status' => 'active', 'expires_at' => now()->addYear()->toIso8601String()]),
            '*portfolio*' => Http::response(['id' => 2957086, 'subscription_id' => 'sub_x', 'status' => 'completed']),
            '*' => Http::response([], 200),
        ]);

        $registration = $this->service()->register($this->user->id, 'myshop.com', $this->contact);

        $this->assertSame(DomainRegistration::STATUS_ACTIVE, $registration->status);
        $this->assertSame('590.00', $registration->price_thb);
        $this->assertNotNull($registration->wallet_transaction_id);
        $this->assertSame('410.00', $wallet->fresh()->balance);
    }

    /** 202 means paid but NOT registered — it must not show as active. */
    public function test_accepted_but_unfinished_order_stays_registering(): void
    {
        $this->fund(1000);

        Http::fake([
            '*availability*' => Http::response(['data' => [['domain' => 'slow.com', 'is_available' => true]]]),
            '*whois*' => Http::response(['id' => 1]),
            '*portfolio*' => Http::response(['id' => 1, 'subscription_id' => 'sub_y', 'status' => 'payment_initiated'], 202),
            '*' => Http::response([], 200),
        ]);

        $registration = $this->service()->register($this->user->id, 'slow.com', $this->contact);

        $this->assertSame(DomainRegistration::STATUS_REGISTERING, $registration->status);
        $this->assertNull($registration->registered_at);
    }

    /** Tapping "buy" twice must charge once. */
    public function test_double_tap_does_not_charge_twice(): void
    {
        $wallet = $this->fund(2000);

        Http::fake([
            '*availability*' => Http::response(['data' => [['domain' => 'once.com', 'is_available' => true]]]),
            '*whois*' => Http::response(['id' => 1]),
            '*portfolio/once.com*' => Http::response(['status' => 'active']),
            '*portfolio*' => Http::response(['id' => 1, 'subscription_id' => 'sub_z', 'status' => 'completed']),
            '*' => Http::response([], 200),
        ]);

        $first = $this->service()->register($this->user->id, 'once.com', $this->contact);
        $second = $this->service()->register($this->user->id, 'once.com', $this->contact);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, DomainRegistration::where('domain', 'once.com')->count());
        $this->assertSame('1410.00', $wallet->fresh()->balance);
    }

    /** No money moves when the balance is short. */
    public function test_insufficient_balance_refuses_without_charging(): void
    {
        $wallet = $this->fund(100);

        Http::fake([
            '*availability*' => Http::response(['data' => [['domain' => 'broke.com', 'is_available' => true]]]),
            '*' => Http::response([], 200),
        ]);

        $this->expectException(DomainPurchaseException::class);

        try {
            $this->service()->register($this->user->id, 'broke.com', $this->contact);
        } finally {
            $this->assertSame('100.00', $wallet->fresh()->balance);
            $this->assertSame(0, DomainRegistration::count());
        }
    }

    /** Someone else registered it while the form was open. */
    public function test_name_taken_during_checkout_refuses_without_charging(): void
    {
        $wallet = $this->fund(1000);

        Http::fake([
            '*availability*' => Http::response(['data' => [['domain' => 'gone.com', 'is_available' => false]]]),
            '*' => Http::response([], 200),
        ]);

        $this->expectException(DomainPurchaseException::class);

        try {
            $this->service()->register($this->user->id, 'gone.com', $this->contact);
        } finally {
            $this->assertSame('1000.00', $wallet->fresh()->balance);
            $this->assertSame(0, DomainRegistration::count());
        }
    }

    /** The registrar refuses after we already took the money. */
    public function test_upstream_failure_refunds_in_full(): void
    {
        $wallet = $this->fund(1000);

        Http::fake([
            '*availability*' => Http::response(['data' => [['domain' => 'fail.com', 'is_available' => true]]]),
            '*whois*' => Http::response(['id' => 1]),
            '*portfolio*' => Http::response(['message' => 'registry rejected'], 422),
            '*' => Http::response([], 200),
        ]);

        $registration = $this->service()->register($this->user->id, 'fail.com', $this->contact);

        $this->assertSame(DomainRegistration::STATUS_REFUNDED, $registration->status);
        $this->assertNotNull($registration->refund_transaction_id);
        $this->assertSame('1000.00', $wallet->fresh()->balance);
    }

    /** A refund that is retried must not credit a second time. */
    public function test_refund_is_not_applied_twice(): void
    {
        $wallet = $this->fund(1000);

        Http::fake([
            '*availability*' => Http::response(['data' => [['domain' => 'twice.com', 'is_available' => true]]]),
            '*whois*' => Http::response(['id' => 1]),
            '*portfolio*' => Http::response([], 500),
            '*' => Http::response([], 200),
        ]);

        $registration = $this->service()->register($this->user->id, 'twice.com', $this->contact);
        $this->service()->failAndRefund($registration->fresh(), 'retry of the same failure');

        $this->assertSame('1000.00', $wallet->fresh()->balance);
        $this->assertSame(1, $wallet->transactions()->where('type', 'refund')->count());
    }

    /** Registering in someone else's name must be impossible. */
    public function test_cannot_register_using_another_users_contact(): void
    {
        $this->fund(1000);
        $other = User::factory()->create();
        $theirContact = DomainContact::create([
            'user_id' => $other->id,
            'first_name' => 'Other', 'last_name' => 'Person',
            'email' => 'other@example.com',
            'phone_country_code' => '+66', 'phone' => '899999999',
            'address1' => '2 Silom', 'city' => 'Bangkok', 'zip' => '10500', 'country' => 'TH',
        ]);

        Http::fake(['*' => Http::response([], 200)]);

        $this->expectException(DomainPurchaseException::class);
        $this->service()->register($this->user->id, 'steal.com', $theirContact);
    }

    /** A TLD with no item id cannot be ordered at a made-up price. */
    public function test_tld_without_item_id_is_not_sellable(): void
    {
        DomainTld::updateOrCreate(['tld' => 'zzz'], ['cost_usd_cents' => 500, 'is_active' => true]);
        $this->fund(1000);

        Http::fake(['*' => Http::response([], 200)]);

        $this->expectException(DomainPurchaseException::class);
        $this->service()->register($this->user->id, 'anything.zzz', $this->contact);
    }

    /** Premium names are priced per-name upstream; our per-TLD price is wrong. */
    public function test_premium_name_is_refused_rather_than_sold_at_a_loss(): void
    {
        $wallet = $this->fund(1000);

        Http::fake([
            '*availability*' => Http::response(['data' => [
                ['domain' => 'gold.com', 'is_available' => true, 'is_premium' => true],
            ]]),
            '*' => Http::response([], 200),
        ]);

        $this->expectException(DomainPurchaseException::class);

        try {
            $this->service()->register($this->user->id, 'gold.com', $this->contact);
        } finally {
            $this->assertSame('1000.00', $wallet->fresh()->balance);
        }
    }

    /** last_error can quote the registrar; it must not serialise out. */
    public function test_internal_error_text_is_hidden_from_serialisation(): void
    {
        $registration = DomainRegistration::create([
            'user_id' => $this->user->id,
            'domain' => 'x.com', 'tld' => 'com',
            'price_thb' => 590, 'cost_usd_cents' => 1099, 'fx_rate' => 36.5,
            'idempotency_key' => 'k1',
            'last_error' => 'Hostinger said no',
        ]);

        $this->assertArrayNotHasKey('last_error', $registration->toArray());
    }

    /** Registrant personal data must not leak through model serialisation. */
    public function test_contact_personal_data_is_hidden_from_serialisation(): void
    {
        $array = $this->contact->toArray();

        foreach (['phone', 'address1', 'zip'] as $field) {
            $this->assertArrayNotHasKey($field, $array, "$field should be hidden");
        }
    }

    /**
     * Removing a record in the form must actually delete it upstream.
     *
     * The update call only touches the name/type pairs it is handed, so a
     * removed row that is merely absent stays live — the table says it is
     * gone and the internet disagrees.
     */
    public function test_removing_a_record_sends_a_delete(): void
    {
        $domain = DomainRegistration::create([
            'user_id' => $this->user->id,
            'domain' => 'dns.com', 'tld' => 'com', 'status' => DomainRegistration::STATUS_ACTIVE,
            'price_thb' => 590, 'cost_usd_cents' => 1099, 'fx_rate' => 36.5,
            'idempotency_key' => 'dns-key',
        ]);

        Http::fake([
            '*dns/v1/zones/dns.com/validate*' => Http::response([], 200),
            '*dns/v1/zones/dns.com' => Http::response([
                'data' => [
                    ['name' => '@', 'type' => 'A', 'ttl' => 14400, 'records' => [['content' => '203.0.113.1']]],
                    ['name' => 'old', 'type' => 'CNAME', 'ttl' => 14400, 'records' => [['content' => 'legacy.example.com']]],
                    ['name' => '@', 'type' => 'SOA', 'ttl' => 86400, 'records' => [['content' => 'ns1. hostmaster. 1']]],
                ],
            ]),
            '*' => Http::response([], 200),
        ]);

        // Submit only the A record — "old" CNAME was removed by the customer.
        $this->actingAs($this->user)
            ->post(route('customer.domains.dns', $domain->id), [
                'records' => [
                    ['name' => '@', 'type' => 'A', 'content' => '203.0.113.9', 'ttl' => 14400],
                ],
            ])
            ->assertSessionHas('success');

        $deleteSent = false;
        $soaTouched = false;

        Http::recorded(function ($request) use (&$deleteSent, &$soaTouched) {
            if ($request->method() !== 'DELETE') {
                return;
            }

            $deleteSent = true;

            foreach ($request->data()['filters'] ?? [] as $filter) {
                if (($filter['type'] ?? '') === 'SOA') {
                    $soaTouched = true;
                }
            }
        });

        $this->assertTrue($deleteSent, 'the removed CNAME should have been deleted upstream');
        $this->assertFalse($soaTouched, 'system records must never be deleted');
    }

    /** Unticking "offer next time" still keeps the registrant on file. */
    public function test_registrant_is_always_kept_but_can_be_hidden_from_the_picker(): void
    {
        $this->fund(1000);

        Http::fake([
            '*availability*' => Http::response(['data' => [['domain' => 'keep.com', 'is_available' => true]]]),
            '*whois*' => Http::response(['id' => 1]),
            '*portfolio/keep.com*' => Http::response(['status' => 'active']),
            '*portfolio*' => Http::response(['id' => 1, 'subscription_id' => 's', 'status' => 'completed']),
            '*' => Http::response([], 200),
        ]);

        DomainTld::where('tld', 'com')->update(['item_id_register' => 'test-domain-com-usd-1y']);

        $this->actingAs($this->user)->post(route('domains.register.store', 'keep.com'), [
            'first_name' => 'Nid', 'last_name' => 'Noi',
            'email' => 'nid@example.com',
            'phone_country_code' => '+66', 'phone' => '811111111',
            'address1' => '9 Rama IV', 'city' => 'Bangkok', 'zip' => '10500', 'country' => 'TH',
            'accept_terms' => '1',
            // save_contact deliberately absent — the box was unticked.
        ]);

        $created = DomainContact::where('email', 'nid@example.com')->first();

        $this->assertNotNull($created, 'the registrant must be kept on file regardless');
        $this->assertTrue($created->hidden_from_picker);
    }
}
