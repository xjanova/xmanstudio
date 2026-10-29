<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /chanthra-studio — the product page the app and its release notes open (/products/chanthra-studio redirects here).
 *
 *   — prices come from config/licenses.php only; nothing on the page is a typed-in number
 *   — while the shop says Coming Soon: no download link and no way to buy, the labels say so
 *   — once on sale: three download buttons (hero, the no-key card, the closing CTA) and one buy link per term,
 *     to the product's own pricing page (Chanthra is not sold through the site cart)
 *   — the version line is the newest release by date, never by string order ("0.9.0" sorts above "0.11.0")
 */
class ChanthraStudioPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_prices_come_from_the_license_table(): void
    {
        $html = $this->get('/chanthra-studio')->assertOk()->getContent();

        $this->assertStringContainsString('฿399', $html);
        $this->assertStringContainsString('฿2,500', $html);
        $this->assertStringContainsString('฿7,500', $html);
        $this->assertStringContainsString('ถูกกว่ารายเดือน 47%', $html);

        config()->set('licenses.plans.chanthra-studio', ['monthly' => 450, 'yearly' => 3000]);

        $html = $this->get('/chanthra-studio')->assertOk()->getContent();

        $this->assertStringContainsString('฿450', $html);
        $this->assertStringContainsString('฿3,000', $html);
        $this->assertStringNotContainsString('฿399', $html);
        $this->assertStringNotContainsString('฿7,500', $html);
        $this->assertStringNotContainsString('ตลอดชีพ ฿', $html); // a term the table dropped is quoted nowhere
    }

    public function test_while_coming_soon_nothing_downloads_and_nothing_sells(): void
    {
        $this->assertTrue(Product::where('slug', 'chanthra-studio')->sole()->isComingSoon());

        $html = $this->get('/chanthra-studio')->assertOk()->getContent();

        $this->assertStringNotContainsString(route('chanthra-studio.download'), $html);
        $this->assertStringNotContainsString('href="' . route('chanthra-studio.pricing') . '"', $html);
        $this->assertStringNotContainsString(route('cart.add', Product::where('slug', 'chanthra-studio')->sole()), $html);
        // three download labels + three plan labels
        $this->assertSame(6, substr_count($html, 'data-coming-soon'));
        $this->assertStringContainsString('ยังไม่เปิดขาย', $html);
        // the manual stays reachable
        $this->assertStringContainsString('href="' . route('chanthra-studio.manual') . '"', $html);
    }

    public function test_on_sale_it_links_the_download_and_each_plan_to_the_pricing_page(): void
    {
        $product = $this->onSale();

        $html = $this->get('/chanthra-studio')->assertOk()->getContent();

        $this->assertSame(3, substr_count($html, 'href="' . route('chanthra-studio.download') . '"'));
        $this->assertSame(3, substr_count($html, 'href="' . route('chanthra-studio.pricing') . '"'));
        $this->assertStringNotContainsString('data-coming-soon', $html);
        $this->assertStringNotContainsString(route('cart.add', $product), $html);
        $this->assertStringNotContainsStringIgnoringCase('github', $html);
    }

    public function test_the_version_line_is_the_newest_release_by_date(): void
    {
        $product = $this->onSale();

        $this->travel(-2)->days();
        $this->release($product, '0.9.0');
        $this->travelBack();
        $this->release($product, '0.11.0');

        $this->get('/chanthra-studio')->assertOk()
            ->assertSee('เวอร์ชันล่าสุด 0.11.0')
            ->assertSee('Chanthra Studio 0.11.0')
            ->assertDontSee('เวอร์ชันล่าสุด 0.9.0');
    }

    private function onSale(): Product
    {
        $product = Product::where('slug', 'chanthra-studio')->sole();
        $product->update(['is_coming_soon' => false, 'is_active' => true]);

        return $product;
    }

    private function release(Product $product, string $version): ProductVersion
    {
        return ProductVersion::create([
            'product_id' => $product->id,
            'version' => $version,
            'download_filename' => "ChanthraStudio-v{$version}-win-x64.zip",
            'file_size' => 94371840,
            'is_active' => true,
        ]);
    }
}
