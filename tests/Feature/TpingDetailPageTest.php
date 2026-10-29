<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * /tping — the Tping product page (owner, 2026-10-28: "ข้อมูล ฟีเจอร์ถูกต้องตามปัจจุบัน ไลเซ่น ดาวโหลด ราคา ต้องไม่ผิดพลาด").
 *
 *   — every price comes from config/licenses.php: change the table and the page follows, no number written in the view
 *   — each term is bought through Tping's own checkout (/tping/checkout/{plan}), never the site cart
 *   — the download buttons go to /tping/download (the page that hands out the APK), three of them
 *   — the version shown is the active one /tping/download/apk serves
 *   — Coming Soon: no download link and no checkout link anywhere on the page
 */
class TpingDetailPageTest extends TestCase
{
    use RefreshDatabase;

    private Product $tping;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->withoutVite();
        User::factory()->create(['role' => 'admin']); // without an admin every page goes to the setup wizard

        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $this->tping = Product::firstOrCreate(['slug' => 'tping'], [
            'category_id' => $category->id, 'name' => 'Tping', 'description' => 'x', 'price' => 399,
            'requires_license' => true, 'is_active' => true,
        ]);
        $this->tping->update(['is_active' => true, 'is_coming_soon' => false, 'coming_soon_until' => null]);
    }

    public function test_prices_come_from_the_license_table(): void
    {
        config(['licenses.plans.tping' => ['monthly' => 1234, 'yearly' => 9876, 'lifetime' => 45678]]);

        $html = $this->get('/tping')->assertOk()->getContent();

        foreach (['1,234', '9,876', '45,678'] as $price) {
            $this->assertStringContainsString('฿' . $price, $html);
        }
        // the old numbers are nowhere on the page
        foreach (['฿399', '฿2,500', '฿5,000'] as $old) {
            $this->assertStringNotContainsString($old, $html);
        }
        // the saving badge is worked out from the same table
        $this->assertStringContainsString('ถูกกว่ารายเดือน ' . (int) floor((1 - 9876 / (1234 * 12)) * 100) . '%', $html);
    }

    public function test_a_term_the_store_does_not_sell_is_not_offered(): void
    {
        config(['licenses.plans.tping' => ['monthly' => 399, 'yearly' => 2500]]);

        $html = $this->get('/tping')->assertOk()->getContent();

        $this->assertStringContainsString('href="' . route('tping.checkout', 'yearly') . '"', $html);
        $this->assertStringNotContainsString(route('tping.checkout', 'lifetime'), $html);
    }

    public function test_each_term_is_bought_through_the_tping_checkout_not_the_cart(): void
    {
        $html = $this->get('/tping')->assertOk()->getContent();

        foreach (['monthly', 'yearly', 'lifetime'] as $term) {
            $this->assertSame(1, substr_count($html, 'href="' . route('tping.checkout', $term) . '"'), $term);
        }
        $this->assertStringNotContainsString(route('cart.add', $this->tping), $html);
        $this->assertStringContainsString('href="' . route('tping.pricing') . '"', $html);
        $this->assertStringContainsString('href="' . route('tping.install-guide') . '"', $html);
    }

    public function test_the_download_buttons_go_to_the_download_page_and_show_the_served_version(): void
    {
        ProductVersion::create(['product_id' => $this->tping->id, 'version' => '1.2.102', 'is_active' => true]);
        ProductVersion::create(['product_id' => $this->tping->id, 'version' => '1.2.103', 'is_active' => false]);

        $html = $this->get('/tping')->assertOk()->getContent();

        // hero, trial card, closing call to action
        $this->assertSame(3, substr_count($html, 'href="' . route('tping.download') . '"'));
        $this->assertStringContainsString('Tping 1.2.102', $html);
        $this->assertStringNotContainsString('1.2.103', $html);
    }

    public function test_coming_soon_leaves_nothing_to_download_or_buy(): void
    {
        $this->tping->update(['is_coming_soon' => true]);

        $html = $this->get('/tping')->assertOk()->getContent();

        $this->assertStringNotContainsString('href="' . route('tping.download') . '"', $html);
        $this->assertStringNotContainsString('/tping/checkout/', $html);
        $this->assertSame(6, substr_count($html, 'data-coming-soon')); // 3 download labels + 3 plan labels
    }
}
