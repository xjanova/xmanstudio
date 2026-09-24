<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\LicensePlans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One price table — config/licenses.php 'plans' — behind every page, checkout
 * and API that quotes a licence term.
 *
 * There used to be nine and they disagreed: the pricing API made up
 * 399/2,500/5,000 for any product it had no entry for, AutoTradeX's app and web
 * checkout quoted different monthly prices, and CluadeX's landing page offered
 * plans its product page did not sell.
 */
class LicensePlansTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    // ── the table ───────────────────────────────────────────────────────

    public function test_every_plan_is_a_term_the_store_knows_at_a_real_price(): void
    {
        foreach (config('licenses.plans') as $slug => $plans) {
            $this->assertNotEmpty($plans, $slug);

            foreach ($plans as $term => $price) {
                // the pricing API and the licence generator know these three
                $this->assertContains($term, ['monthly', 'yearly', 'lifetime'], "{$slug}.{$term}");
                $this->assertIsInt($price, "{$slug}.{$term}");
                $this->assertGreaterThan(0, $price, "{$slug}.{$term}");
            }
        }

        foreach (config('licenses.cart_products') as $slug) {
            $this->assertNotSame([], LicensePlans::for($slug), "{$slug} is sold in the cart but has no price");
        }
    }

    public function test_the_prices_the_owner_settled_on(): void
    {
        $this->assertSame(['monthly' => 299, 'yearly' => 1990, 'lifetime' => 19900], LicensePlans::for('autotradex'));
        $this->assertSame(['yearly' => 199, 'lifetime' => 1999], LicensePlans::for('cluadex-ai-coding-assistant'));
        $this->assertSame(['yearly' => 199], LicensePlans::for('winx-tools'));
        $this->assertSame(['monthly' => 399], LicensePlans::for('brainx'));
        $this->assertSame([], LicensePlans::for('aipray'));
    }

    public function test_the_save_badge_never_claims_more_than_the_customer_saves(): void
    {
        $this->assertSame(47, LicensePlans::yearlySaving('tping'));      // 2,500 against 12 × 399 = 47.8 %
        $this->assertSame(44, LicensePlans::yearlySaving('autotradex')); // 1,990 against 12 × 299 = 44.5 %
        $this->assertSame(16, LicensePlans::yearlySaving('smschecker')); // 4,990 against 12 × 499 = 16.7 % — the page said 48
        $this->assertNull(LicensePlans::yearlySaving('cluadex-ai-coding-assistant')); // no monthly plan to compare with
    }

    // ── the pricing API ───────────────────────────────────────────────────

    public function test_the_pricing_api_answers_exactly_the_table_for_every_product_in_it(): void
    {
        $days = ['monthly' => 30, 'yearly' => 365, 'lifetime' => null];

        foreach (config('licenses.plans') as $slug => $prices) {
            $this->licensed($slug);

            $plans = $this->getJson("/api/v1/product/{$slug}/pricing")->assertOk()->json('data.plans');

            $this->assertSame(array_keys($prices), array_keys($plans), $slug);

            foreach ($prices as $term => $price) {
                $this->assertSame($price, $plans[$term]['price'], "{$slug}.{$term}");
                $this->assertSame('THB', $plans[$term]['currency'], "{$slug}.{$term}");
                $this->assertSame($days[$term], $plans[$term]['duration_days'], "{$slug}.{$term}");
            }
        }
    }

    public function test_a_product_with_no_price_gets_no_plans_instead_of_invented_ones(): void
    {
        foreach (['aipray', 'postx-agent', 'gpuxmine'] as $slug) {
            $this->licensed($slug);

            $response = $this->getJson("/api/v1/product/{$slug}/pricing")
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.product.slug', $slug)
                ->assertJsonPath('data.purchase_url', route('products.show', $slug));

            // {} and not [] — plans is a map in every client
            $this->assertStringContainsString('"plans":{}', $response->getContent(), $slug);
        }
    }

    public function test_an_unknown_product_is_still_not_found(): void
    {
        $this->getJson('/api/v1/product/no-such-product/pricing')
            ->assertNotFound()
            ->assertJsonPath('error_code', 'PRODUCT_NOT_FOUND');
    }

    // ── the cart ──────────────────────────────────────────────────────────

    public function test_the_cart_charges_the_table_price_whatever_the_form_says(): void
    {
        foreach (['winx-tools' => 'yearly', 'cluadex-ai-coding-assistant' => 'lifetime', 'brainx' => 'monthly'] as $slug => $term) {
            $product = $this->licensed($slug);

            $this->post(route('cart.add', $product), ['license_type' => $term, 'price' => 1])->assertRedirect();

            $this->assertEquals(
                LicensePlans::price($slug, $term),
                (float) CartItem::where('product_id', $product->id)->sole()->price,
                "{$slug} {$term}"
            );
        }
    }

    public function test_the_cart_refuses_a_term_the_table_does_not_sell(): void
    {
        $cluadex = $this->licensed('cluadex-ai-coding-assistant');

        $this->postJson(route('cart.add', $cluadex), ['license_type' => 'monthly'])->assertStatus(422);

        $this->assertSame(0, CartItem::count());
    }

    public function test_a_product_with_a_checkout_of_its_own_is_not_sold_through_the_cart(): void
    {
        // priced in the table, but bought at /tping/checkout, which ties the key to the phone
        $tping = $this->licensed('tping');

        $this->postJson(route('cart.add', $tping), ['license_type' => 'monthly'])->assertStatus(422);

        $this->assertSame(0, CartItem::count());
    }

    public function test_a_price_changed_in_the_table_reaches_the_api_the_page_and_the_cart(): void
    {
        config()->set('licenses.plans.winx-tools', ['yearly' => 249]);
        $winx = $this->licensed('winx-tools');

        $this->getJson('/api/v1/product/winx-tools/pricing')->assertJsonPath('data.plans.yearly.price', 249);
        $this->get('/products/winx-tools')->assertOk()->assertSee('฿249')->assertDontSee('฿199');

        $this->post(route('cart.add', $winx), ['license_type' => 'yearly']);
        $this->assertEquals(249, (float) CartItem::sole()->price);
    }

    // ── each product's own pages ─────────────────────────────────────────────

    public function test_tping_pages_and_checkout_price_from_the_table(): void
    {
        $this->licensed('tping');

        $this->get('/tping/pricing')->assertOk()
            ->assertSee('฿399')->assertSee('฿2,500')->assertSee('฿5,000')
            ->assertSee('ประหยัด 47%');

        config()->set('licenses.plans.tping', ['monthly' => 450]);
        $this->actingAs(User::factory()->create());

        $this->get('/tping/checkout/monthly')->assertOk()->assertSee('฿450');
        // a term the table stopped selling is gone from checkout too
        $this->get('/tping/checkout/lifetime')->assertNotFound();
    }

    public function test_localvpn_premium_prices_from_the_table(): void
    {
        $this->licensed('localvpn');

        $this->get('/localvpn/pricing')->assertOk()
            ->assertSee('฿399')->assertSee('฿2,500')->assertSee('฿5,000')
            ->assertSee('ประหยัด 47%');
    }

    public function test_autotradex_sells_at_the_same_price_on_the_web_and_in_the_app(): void
    {
        $this->licensed('autotradex');

        $this->get('/autotradex/pricing')->assertOk()
            ->assertSee('฿299')->assertSee('฿1,990')->assertSee('฿19,900')
            ->assertSee('ประหยัด 44%')
            ->assertDontSee('฿990')->assertDontSee('฿7,900');

        $this->actingAs(User::factory()->create())
            ->get('/autotradex/checkout/monthly')->assertOk()->assertSee('฿299');

        $response = $this->getJson('/api/v1/autotradex/pricing')->assertOk()
            ->assertJsonPath('data.plans.monthly.price', 299)
            ->assertJsonPath('data.plans.yearly.price', 1990)
            ->assertJsonPath('data.plans.lifetime.price', 19900)
            ->assertJsonPath('data.plans.yearly.save_percent', 44);

        // the app treats a pricing answer missing any of these as a fake server
        // (AutoTrade-X LicenseService.cs, the verify-server fallback)
        foreach (['"success"', '"plans"', 'autotradex'] as $needle) {
            $this->assertStringContainsString($needle, $response->getContent());
        }

        // register-device hands the app the same plans, as a map it reads into a dictionary
        $this->postJson('/api/v1/autotradex/register-device', ['machine_id' => str_repeat('a1', 16)])
            ->assertOk()
            ->assertJsonPath('data.pricing.plans.monthly.original_price', 299)
            ->assertJsonPath('data.pricing.plans.yearly.original_price', 1990)
            ->assertJsonPath('data.pricing.plans.lifetime.original_price', 19900);
    }

    public function test_the_cluadex_pages_sell_the_two_plans_the_product_page_sells(): void
    {
        $cluadex = $this->licensed('cluadex-ai-coding-assistant');

        $html = $this->get('/cluadex/pricing')->assertOk()->getContent();

        $this->assertStringContainsString('฿199', $html);
        $this->assertStringContainsString('฿1,999', $html);
        foreach (['฿899', '฿4,999', 'ประหยัด 63%', 'value="monthly"'] as $gone) {
            $this->assertStringNotContainsString($gone, $html);
        }

        // each plan posts its term to the cart, which charges the table's price
        $this->assertSame(2, substr_count($html, 'action="' . route('cart.add', $cluadex) . '"'));
        $this->assertStringContainsString('name="license_type" value="yearly"', $html);
        $this->assertStringContainsString('name="license_type" value="lifetime"', $html);

        $this->post(route('cart.add', $cluadex), ['license_type' => 'yearly'])->assertRedirect();
        $this->assertEquals(199, (float) CartItem::sole()->price);

        // the landing page's Pro card quotes the entry plan, per year
        $this->get('/cluadex')->assertOk()
            ->assertSee('฿199<span class="text-base text-gray-400">/ปี</span>', false);
    }

    public function test_the_product_pages_quote_the_table_and_post_no_price(): void
    {
        $this->licensed('cluadex-ai-coding-assistant');

        $html = $this->get('/products/cluadex-ai-coding-assistant')->assertOk()->getContent();
        $this->assertStringContainsString('ซื้อ License รายปี - ฿199', $html);
        $this->assertStringContainsString('ซื้อ License ตลอดชีพ - ฿1,999', $html);
        // the cart never read a posted price; the forms stop sending one
        $this->assertStringNotContainsString('name="price"', $html);

        $this->licensed('brainx');
        $this->get('/products/brainx')->assertOk()->assertSee('฿399/เดือน', false);
    }

    // ── helpers ──────────────────────────────────────────────────────────

    /** A licensed, active product — the one a migration made if there is one, else a new one. */
    private function licensed(string $slug, array $attributes = []): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'software'],
            ['name' => 'Software', 'description' => 'x']
        );

        $product = Product::firstOrCreate(['slug' => $slug], [
            'category_id' => $category->id,
            'name' => ucfirst($slug),
            'description' => 'x',
            'price' => 0,
            'stock' => 999,
        ]);

        $product->update($attributes + ['requires_license' => true, 'is_active' => true]);

        return $product->fresh();
    }
}
