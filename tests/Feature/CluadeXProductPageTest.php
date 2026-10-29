<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * /products/cluadex-ai-coding-assistant — the page the CluadeX app opens to buy a key
 * (XmanLicenseService.DefaultPurchaseUrl). Prices come from config/licenses.php only, the free
 * download is the app's own route (/cluadex/download), and a Coming Soon CluadeX sells nothing.
 */
class CluadeXProductPageTest extends TestCase
{
    use RefreshDatabase;

    private const SLUG = 'cluadex-ai-coding-assistant';

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        // the page reads the version from the database only — it never asks GitHub
        Http::preventStrayRequests();
        Http::fake();

        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $this->product = Product::firstOrCreate(['slug' => self::SLUG], [
            'category_id' => $category->id, 'name' => 'CluadeX', 'description' => 'x', 'price' => 0,
            'requires_license' => true, 'is_active' => true,
        ]);
        $this->product->update(['is_active' => true, 'is_coming_soon' => false, 'coming_soon_until' => null]);
    }

    public function test_every_term_the_config_sells_gets_one_cart_form_at_the_config_price(): void
    {
        config(['licenses.plans.' . self::SLUG => ['yearly' => 345, 'lifetime' => 6789]]);

        $html = $this->get('/products/' . self::SLUG)->assertOk()->assertViewIs('products.xcluadeagent')->getContent();

        $this->assertSame(2, substr_count($html, 'action="' . route('cart.add', $this->product) . '"'));
        $this->assertStringContainsString('name="license_type" value="yearly"', $html);
        $this->assertStringContainsString('name="license_type" value="lifetime"', $html);
        $this->assertStringNotContainsString('name="license_type" value="monthly"', $html);
        $this->assertStringContainsString('฿345', $html);
        $this->assertStringContainsString('฿6,789', $html);
        // no number left over from an old price table
        $this->assertStringNotContainsString('฿199', $html);
        $this->assertStringNotContainsString('฿1,999', $html);
    }

    public function test_the_free_download_is_the_apps_own_route_not_the_buyers_only_page(): void
    {
        ProductVersion::create(['product_id' => $this->product->id, 'version' => '3.0.190', 'is_active' => true, 'download_filename' => 'CluadeX-win-Setup.exe', 'file_size' => 480 * 1024 * 1024]);

        $html = $this->get('/products/' . self::SLUG)->assertOk()->getContent();

        // hero, free card and the closing call to action
        $this->assertSame(3, substr_count($html, 'href="' . route('cluadex.download') . '"'));
        // /download/{slug} sends a guest to the login page and a non-buyer away — never the free button
        $this->assertStringNotContainsString('href="' . route('download.page', self::SLUG) . '"', $html);
        $this->assertStringContainsString('CluadeX 3.0.190', $html);
        $this->assertStringContainsString('images/products/cluadex/v2/og.jpg', $html);
    }

    public function test_a_coming_soon_cluadex_offers_no_download_and_no_purchase(): void
    {
        $this->product->update(['is_coming_soon' => true]);

        $html = $this->get('/products/' . self::SLUG)->assertOk()->getContent();

        $this->assertStringContainsString('data-coming-soon', $html);
        $this->assertStringNotContainsString('href="' . route('cluadex.download') . '"', $html);
        $this->assertStringNotContainsString('action="' . route('cart.add', $this->product) . '"', $html);
    }
}
