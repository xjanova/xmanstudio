<?php

namespace Tests\Feature;

use App\Models\DomainTld;
use App\Models\Setting;
use App\Models\User;
use App\Support\DomainPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The currency argument that is not optional.
 *
 * costThb() defaults to USD because the catalogue used to be USD-only. Our
 * reseller account bills in THB, so every caller that forgets the argument
 * multiplies a baht figure by the exchange rate: a 319 baht .com is reported
 * as costing 11,644 baht, and the admin table shows every single TLD losing
 * money on every sale.
 *
 * The selling price never had the bug — registerPriceThb() passes the
 * currency through — which is why the shop quoted correctly while the admin
 * table was nonsense, and why nobody caught it from the customer side.
 */
class DomainPricingCurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setValue('domain_margin_percent', 45);
        Setting::setValue('domain_usd_thb_rate', 36.5);
        Setting::setValue('domain_price_rounding', 10);
        Cache::flush();
    }

    public function test_a_thb_cost_is_not_multiplied_by_the_exchange_rate(): void
    {
        $this->assertSame(319.0, DomainPricing::costThb(31900, null, 'THB'));
    }

    public function test_a_usd_cost_still_is(): void
    {
        $this->assertSame(365.0, DomainPricing::costThb(1000, 36.5, 'USD'));
    }

    public function test_the_selling_price_respects_the_billing_currency(): void
    {
        // 319 THB + 45% = 462.55, rounded up to the nearest 10.
        $this->assertSame(470.0, DomainPricing::sell(31900, 45, 'THB'));

        // $10 at 36.5 = 365 THB + 45% = 529.25, rounded up.
        $this->assertSame(530.0, DomainPricing::sell(1000, 45, 'USD'));
    }

    public function test_a_thb_priced_tld_shows_a_profit_not_a_loss(): void
    {
        $tld = $this->tld();

        $sell = $tld->registerPriceThb();
        $cost = DomainPricing::costThb($tld->cost_usd_cents, null, $tld->costCurrency());

        $this->assertGreaterThan(0, $sell - $cost,
            'a catalogue priced in our own currency cannot be sold at a loss by default');
    }

    /**
     * The regression itself: the admin table read the cost without the
     * currency and printed a five-figure loss on every row.
     */
    public function test_the_admin_table_reports_the_real_cost_and_profit(): void
    {
        $this->tld();

        $response = $this->actingAs($this->admin())->get('/admin/domains');

        $response->assertSuccessful();

        // The true cost, with the baht symbol — not "$319.00", and not the
        // 11,644 that the exchange rate produced.
        $response->assertSee('319.00 ฿', escape: false);
        $response->assertDontSee('$319.00', escape: false);
        $response->assertDontSee('11,644', escape: false);
    }

    public function test_a_usd_priced_tld_still_shows_its_converted_cost(): void
    {
        $this->tld(['tld' => 'io', 'cost_usd_cents' => 1000, 'cost_currency' => 'USD']);

        $response = $this->actingAs($this->admin())->get('/admin/domains');

        $response->assertSuccessful();
        // Dollars as the billed amount, baht underneath as the converted one.
        $response->assertSee('$10.00', escape: false);
        $response->assertSee('365 ฿', escape: false);
    }

    private function tld(array $overrides = []): DomainTld
    {
        return DomainTld::updateOrCreate(
            ['tld' => $overrides['tld'] ?? 'com'],
            array_merge([
                'item_id_register' => 'test-domain-com-thb-1y',
                'item_id_renew' => 'test-domain-com-thb-1y',
                'cost_usd_cents' => 31900,
                'renew_cost_usd_cents' => 51900,
                'cost_currency' => 'THB',
                'margin_percent' => null,
                'is_active' => true,
            ], $overrides)
        );
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret-password'),
            'password_set_at' => now(),
            'is_active' => true,
            'role' => 'admin',
        ]);
    }
}
