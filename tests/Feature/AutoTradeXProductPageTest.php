<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * /products/autotradex — prices come from config/licenses.php only and each term links to the
 * app's own checkout (/autotradex/checkout/{plan}); the free download is /autotradex/download;
 * a Coming Soon AutoTradeX sells nothing.
 */
class AutoTradeXProductPageTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        // the page reads the version from the database only — it never asks GitHub
        Http::preventStrayRequests();
        Http::fake();

        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $this->product = Product::updateOrCreate(['slug' => 'autotradex'], [
            'category_id' => $category->id, 'name' => 'AutoTradeX', 'description' => 'x', 'price' => 0,
            'requires_license' => true, 'is_active' => true, 'is_coming_soon' => false, 'coming_soon_until' => null,
        ]);
    }

    public function test_every_term_the_config_sells_links_to_its_checkout_at_the_config_price(): void
    {
        config(['licenses.plans.autotradex' => ['monthly' => 345, 'lifetime' => 6789]]);

        $html = $this->get('/products/autotradex')->assertOk()->assertViewIs('products.autotradex')->getContent();

        $this->assertStringContainsString('href="' . route('autotradex.checkout', 'monthly') . '"', $html);
        $this->assertStringContainsString('href="' . route('autotradex.checkout', 'lifetime') . '"', $html);
        $this->assertStringNotContainsString(route('autotradex.checkout', 'yearly'), $html);
        $this->assertStringContainsString('฿345', $html);
        $this->assertStringContainsString('฿6,789', $html);
        // no number left over from an old price table
        $this->assertStringNotContainsString('฿299', $html);
        $this->assertStringNotContainsString('฿1,990', $html);
        $this->assertStringNotContainsString('฿19,900', $html);
    }

    public function test_the_page_offers_the_free_download_and_carries_seo(): void
    {
        $html = $this->get('/products/autotradex')->assertOk()->getContent();

        $this->assertStringContainsString('href="' . route('autotradex.download') . '"', $html);
        $this->assertStringContainsString('images/products/autotradex/v2/og.jpg', $html);
        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('id="pricing"', $html);
        $this->assertStringNotContainsString('github.com', $html);
    }

    public function test_a_coming_soon_autotradex_sells_nothing_and_shows_the_label(): void
    {
        $this->product->update(['is_coming_soon' => true]);

        $html = $this->get('/products/autotradex')->assertOk()->getContent();

        $this->assertStringContainsString('data-coming-soon', $html);
        $this->assertStringNotContainsString('href="' . route('autotradex.download') . '"', $html);
        foreach (['monthly', 'yearly', 'lifetime'] as $term) {
            $this->assertStringNotContainsString(route('autotradex.checkout', $term), $html);
        }
    }
}
