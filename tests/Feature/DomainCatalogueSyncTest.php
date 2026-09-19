<?php

namespace Tests\Feature;

use App\Console\Commands\SyncDomainCatalogue;
use App\Models\DomainTld;
use App\Models\Setting;
use App\Models\User;
use App\Support\DomainPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Reading the registrar's catalogue, whatever money it is priced in.
 *
 * Our reseller account is a Thai one: all 1,276 price rows come back in THB and
 * the item ids read `hostingerinth-domain-com-thb-1y`. The parser wanted USD and
 * a currency from a hardcoded list of three, so it matched nothing — every TLD
 * kept a null item id, and a null item id means the shop refuses to sell. The
 * catalogue looked populated on the admin page while nothing on it could be
 * bought.
 *
 * The other half of the bug would have been worse: accept the THB figure and
 * let DomainPricing multiply it by the USD→THB rate, and a 319 baht domain is
 * offered at about 16,900.
 */
class DomainCatalogueSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setValue('hostinger_api_token', 'test-token');
        Setting::setValue('domain_usd_thb_rate', 36.5);
        Setting::setValue('domain_margin_percent', 45);
        Setting::setValue('domain_price_rounding', 10);
        Cache::flush();
    }

    /**
     * The exact shape production returns, trimmed to two TLDs.
     *
     * @return array<int,array<string,mixed>>
     */
    protected function thaiCatalogue(): array
    {
        return [
            [
                'id' => 'hostingerinth-vps-kvm1',
                'name' => 'KVM 1',
                'category' => 'VPS',
                'prices' => [
                    ['id' => 'hostingerinth-vps-kvm1-thb-1y', 'currency' => 'THB', 'price' => 478800, 'first_period_price' => 298800, 'period' => 1, 'period_unit' => 'year'],
                ],
            ],
            [
                'id' => 'hostingerinth-domain-com',
                'name' => '.COM Domain',
                'category' => 'DOMAIN',
                'prices' => [
                    ['id' => 'hostingerinth-domain-com-thb-1y', 'currency' => 'THB', 'price' => 51900, 'first_period_price' => 31900, 'period' => 1, 'period_unit' => 'year'],
                    ['id' => 'hostingerinth-domain-com-thb-3y', 'currency' => 'THB', 'price' => 155700, 'first_period_price' => 123700, 'period' => 3, 'period_unit' => 'year'],
                    ['id' => 'hostingerinth-domain-com-thb-2y', 'currency' => 'THB', 'price' => 103800, 'first_period_price' => 76800, 'period' => 2, 'period_unit' => 'year'],
                ],
            ],
            [
                // The id glues the labels — `couk`, not `co-uk`. The NAME is
                // where the real suffix is, and a catalogue row called ".couk"
                // is a row no customer can ever buy.
                'id' => 'hostingerinth-domain-couk',
                'name' => '.CO.UK Domain',
                'category' => 'DOMAIN',
                'prices' => [
                    ['id' => 'hostingerinth-domain-couk-thb-1y', 'currency' => 'THB', 'price' => 39900, 'first_period_price' => 25900, 'period' => 1, 'period_unit' => 'year'],
                    // A period-0 row: the catalogue carries these for one-off
                    // items and they must not be read as a yearly price.
                    ['id' => 'hostingerinth-domain-couk-thb-restore', 'currency' => 'THB', 'price' => 350000, 'first_period_price' => 350000, 'period' => 0, 'period_unit' => ''],
                ],
            ],
        ];
    }

    /**
     * @param  array<int,array<string,mixed>>  $items
     * @return array<string,array<string,mixed>>
     */
    protected function parse(array $items): array
    {
        $method = new ReflectionMethod(SyncDomainCatalogue::class, 'parseDomainItems');

        return $method->invoke(app(SyncDomainCatalogue::class), $items);
    }

    public function test_a_thai_priced_catalogue_is_read_rather_than_discarded(): void
    {
        $parsed = $this->parse($this->thaiCatalogue());

        $this->assertArrayHasKey('com', $parsed, 'a THB catalogue used to parse to nothing at all');
        $this->assertArrayHasKey('co.uk', $parsed);
        $this->assertArrayNotHasKey('kvm1', $parsed, 'VPS is not a domain');

        $this->assertSame('hostingerinth-domain-com-thb-1y', $parsed['com']['item_id_register']);
        $this->assertSame('THB', $parsed['com']['currency']);
        // first_period_price is the first year; price is what renewal costs.
        $this->assertSame(31900, $parsed['com']['cost']);
        $this->assertSame(51900, $parsed['com']['renew']);
    }

    public function test_a_multi_label_suffix_keeps_its_dots(): void
    {
        $parsed = $this->parse($this->thaiCatalogue());

        $this->assertArrayHasKey('co.uk', $parsed, 'the id reads couk — the name is what says .CO.UK');
        $this->assertArrayNotHasKey('couk', $parsed);
        $this->assertSame('hostingerinth-domain-couk-thb-1y', $parsed['co.uk']['item_id_register'],
            'the item id is still what we order with, however it is spelled');
    }

    public function test_an_item_with_no_usable_name_falls_back_to_its_id(): void
    {
        $parsed = $this->parse([[
            'id' => 'x-domain-com',
            'name' => 'Domain Registration',
            'category' => 'DOMAIN',
            'prices' => [
                ['id' => 'x-domain-com-thb-1y', 'currency' => 'THB', 'price' => 51900, 'first_period_price' => 31900, 'period' => 1, 'period_unit' => 'year'],
            ],
        ]]);

        $this->assertArrayHasKey('com', $parsed);
    }

    public function test_only_the_one_year_row_is_priced(): void
    {
        $parsed = $this->parse($this->thaiCatalogue());

        // The 2y and 3y rows are cheaper per year and would undercut the real
        // price; the restore row is not a yearly price at all.
        $this->assertSame(31900, $parsed['com']['cost']);
        $this->assertSame(25900, $parsed['co.uk']['cost']);
    }

    public function test_a_dollar_catalogue_still_works(): void
    {
        $parsed = $this->parse([[
            'id' => 'hostingercom-domain-com',
            'category' => 'DOMAIN',
            'prices' => [
                ['id' => 'hostingercom-domain-com-usd-1y', 'currency' => 'USD', 'price' => 1599, 'first_period_price' => 1099, 'period' => 1, 'period_unit' => 'year'],
            ],
        ]]);

        $this->assertSame('USD', $parsed['com']['currency']);
        $this->assertSame(1099, $parsed['com']['cost']);
    }

    public function test_dollars_win_when_the_catalogue_offers_both(): void
    {
        // Converting from the currency we already hold a rate for beats
        // guessing at a second one.
        $parsed = $this->parse([[
            'id' => 'x-domain-com',
            'category' => 'DOMAIN',
            'prices' => [
                ['id' => 'x-domain-com-thb-1y', 'currency' => 'THB', 'price' => 51900, 'first_period_price' => 31900, 'period' => 1, 'period_unit' => 'year'],
                ['id' => 'x-domain-com-usd-1y', 'currency' => 'USD', 'price' => 1599, 'first_period_price' => 1099, 'period' => 1, 'period_unit' => 'year'],
            ],
        ]]);

        $this->assertSame('USD', $parsed['com']['currency']);
        $this->assertSame(1099, $parsed['com']['cost']);
    }

    public function test_a_baht_cost_is_not_run_through_the_exchange_rate(): void
    {
        // 319 baht at 45% margin, rounded up to 10 → 470. Multiplied by 36.5
        // first it would be 16,900, which is the shape of the bug this guards.
        $this->assertSame(470.0, DomainPricing::sell(31900, 45, 'THB'));
        $this->assertSame(319.0, DomainPricing::costThb(31900, 36.5, 'THB'));

        // A dollar cost still converts.
        $this->assertSame(590.0, DomainPricing::sell(1099, 45, 'USD'));
        $this->assertSame(401.14, DomainPricing::costThb(1099, 36.5, 'USD'));
    }

    public function test_the_tld_price_follows_the_currency_on_its_row(): void
    {
        $tld = DomainTld::updateOrCreate(['tld' => 'com'], [
            'item_id_register' => 'hostingerinth-domain-com-thb-1y',
            'cost_usd_cents' => 31900,
            'renew_cost_usd_cents' => 51900,
            'cost_currency' => 'THB',
            'margin_percent' => null,
            'is_active' => true,
        ]);

        $this->assertSame('THB', $tld->costCurrency());
        $this->assertSame(470.0, $tld->registerPriceThb());
        $this->assertSame(760.0, $tld->renewPriceThb());
        $this->assertStringContainsString('฿', $tld->costLabel());
    }

    public function test_rows_from_before_currencies_were_tracked_are_dollars(): void
    {
        // The seeded fallback catalogue was quoted in USD, and those rows have
        // no currency of their own. They must keep converting.
        $tld = DomainTld::updateOrCreate(['tld' => 'net'], [
            'cost_usd_cents' => 1099,
            'renew_cost_usd_cents' => 1599,
            'margin_percent' => null,
            'is_active' => true,
        ]);

        $this->assertSame('USD', $tld->costCurrency());
        $this->assertSame(590.0, $tld->registerPriceThb());
    }

    public function test_the_sync_writes_the_currency_it_read(): void
    {
        Http::fake(['*' => Http::response(['data' => $this->thaiCatalogue()], 200)]);

        $this->artisan('domains:sync-catalogue')->assertSuccessful();

        $com = DomainTld::where('tld', 'com')->first();
        $this->assertSame('THB', $com->cost_currency);
        $this->assertSame(31900, $com->cost_usd_cents);
        $this->assertSame('hostingerinth-domain-com-thb-1y', $com->item_id_register);
        $this->assertNotNull($com->synced_at, 'a synced row must say when');
    }

    public function test_an_unconfigured_shop_is_quiet_rather_than_failing(): void
    {
        // Both commands are scheduled. Reporting FAILURE for "no token yet"
        // wrote an ERROR line to the production log every five minutes.
        Setting::setValue('hostinger_api_token', '');
        Cache::flush();

        $this->artisan('domains:sync-catalogue')->assertSuccessful();
        $this->artisan('domains:reconcile')->assertSuccessful();
    }

    public function test_the_admin_can_pull_prices_from_the_page(): void
    {
        Http::fake(['*' => Http::response(['data' => $this->thaiCatalogue()], 200)]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.domains.sync'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('THB', DomainTld::where('tld', 'com')->first()->cost_currency);
    }

    public function test_a_dry_run_from_the_page_changes_nothing(): void
    {
        Http::fake(['*' => Http::response(['data' => $this->thaiCatalogue()], 200)]);
        $admin = User::factory()->create(['role' => 'admin']);
        $before = DomainTld::where('tld', 'com')->first()?->item_id_register;

        $this->actingAs($admin)
            ->post(route('admin.domains.sync'), ['dry' => 1])
            ->assertSessionHas('success');

        $this->assertSame($before, DomainTld::where('tld', 'com')->first()?->item_id_register);
    }

    public function test_only_an_admin_can_pull_prices(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.domains.sync'))
            ->assertForbidden();
    }
}
