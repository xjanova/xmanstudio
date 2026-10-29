<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * /products/phonex-manager — the price is the shop row's own ($product->price), the version comes
 * from the database only, a Coming Soon PhoneX offers no download and no way to buy, and the page
 * keeps its old purchase path (no cart form of its own).
 */
class PhoneXManagerProductPageTest extends TestCase
{
    use RefreshDatabase;

    private const SLUG = 'phonex-manager';

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Http::preventStrayRequests();
        Http::fake();

        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $this->product = Product::firstOrCreate(['slug' => self::SLUG], [
            'category_id' => $category->id, 'name' => 'PhoneX Manager', 'description' => 'x', 'price' => 1990,
            'requires_license' => true, 'is_active' => true,
        ]);
        $this->product->update(['is_active' => true, 'is_coming_soon' => false, 'coming_soon_until' => null, 'price' => 1990]);
    }

    public function test_the_price_is_the_shop_rows_price_not_a_number_in_the_page(): void
    {
        $this->product->update(['price' => 2345]);

        $html = $this->get('/products/' . self::SLUG)->assertOk()->assertViewIs('products.phonexmanager')->getContent();

        $this->assertStringContainsString('฿2,345', $html);
        $this->assertStringNotContainsString('฿1,990', $html);
        $this->assertStringContainsString('images/products/phonex-manager/v2/og.jpg', $html);
    }

    public function test_the_page_has_no_cart_form_of_its_own(): void
    {
        $html = $this->get('/products/' . self::SLUG)->assertOk()->getContent();

        $this->assertStringNotContainsString('action="' . route('cart.add', $this->product) . '"', $html);
    }

    public function test_the_version_comes_from_the_database(): void
    {
        ProductVersion::create(['product_id' => $this->product->id, 'version' => '1.4.2', 'is_active' => true, 'download_filename' => 'PhoneXManager.zip']);

        $this->get('/products/' . self::SLUG)->assertOk()->assertSee('PhoneX Manager 1.4.2');
    }

    public function test_a_coming_soon_phonex_offers_no_download_and_no_purchase(): void
    {
        $this->product->update(['is_coming_soon' => true]);

        $html = $this->get('/products/' . self::SLUG)->assertOk()->getContent();

        $this->assertStringContainsString('data-coming-soon', $html);
        $this->assertStringContainsString('Coming Soon — ยังไม่เปิดขาย', $html);
        $this->assertStringNotContainsString('href="' . route('customer.downloads') . '"', $html);
        $this->assertStringNotContainsString('action="' . route('cart.add', $this->product) . '"', $html);
    }

    public function test_the_page_never_advertises_lock_or_identity_bypass_tools(): void
    {
        $html = mb_strtolower($this->get('/products/' . self::SLUG)->assertOk()->getContent());

        foreach (['frp', 'bypass', 'engineer tools', 'mi account', 'find device', 'network unlock', 'screen lock', 'imei'] as $word) {
            $this->assertStringNotContainsString($word, $html, "the sales page mentions \"{$word}\"");
        }
    }
}
