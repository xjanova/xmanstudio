<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * "ซอฟต์แวร์ที่พร้อมใช้งานจริง" on the home page lists only what a visitor can buy or
 * download today. It used to list the newest six active rows, so two Coming Soon products
 * sat under that heading while three on sale were left off (owner, 2026-10-12).
 */
class HomeProductsOnSaleTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        ThemeService::setTheme('nova');
        Cache::forget('site_theme');

        // Without an admin the home page redirects to the setup wizard.
        User::factory()->create(['role' => 'admin']);

        $this->category = Category::create([
            'name' => 'Software',
            'slug' => 'software',
            'description' => 'Software',
            'is_active' => true,
        ]);
    }

    private function product(string $name, array $attributes = []): Product
    {
        return Product::create([
            'category_id' => $this->category->id,
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $name,
            'price' => 199,
            'stock' => 0,
            'is_active' => true,
            ...$attributes,
        ]);
    }

    /** The products section of the classic (Nova) home page. */
    private function productsSection(): string
    {
        $html = $this->get('/?view=classic')->assertOk()->getContent();

        $start = strpos($html, 'id="nova-products"');
        $this->assertNotFalse($start, 'the products section is on the page');

        return substr($html, $start, strpos($html, '</section>', $start) - $start);
    }

    public function test_only_products_on_sale_are_listed(): void
    {
        $this->product('Zz On Sale App');
        $this->product('Zz Launched App', ['is_coming_soon' => true, 'coming_soon_until' => now()->subDay()]);
        // Created last, so "newest first" would have put them at the top.
        $this->product('Zz Coming Soon App', ['is_coming_soon' => true]);
        $this->product('Zz Switched Off App', ['is_active' => false]);

        $section = $this->productsSection();

        $this->assertStringContainsString('Zz On Sale App', $section);
        $this->assertStringContainsString('Zz Launched App', $section);
        $this->assertStringNotContainsString('Zz Coming Soon App', $section);
        $this->assertStringNotContainsString('Zz Switched Off App', $section);
        $this->assertStringNotContainsString('Coming soon', $section);
    }

    public function test_up_to_nine_products_are_listed(): void
    {
        foreach (range(1, 10) as $i) {
            // Ahead of any product the migrations seed, so these ten are the newest.
            $this->product("Zz App {$i}")->forceFill(['created_at' => now()->addMinutes($i)])->save();
        }

        $section = $this->productsSection();

        $this->assertSame(9, substr_count($section, 'class="nova-card '));
        // The oldest one is the one left out.
        $this->assertStringNotContainsString('Zz App 1<', $section);
        $this->assertStringContainsString('Zz App 10<', $section);
    }

    public function test_a_card_shows_the_real_picture_and_not_the_drawn_key_art(): void
    {
        // Both pictures exist in the repository for WinXTools: the real dashboard cut and the
        // drawn key art that artwork_url falls back to.
        $this->assertFileExists(public_path('artwork/product/winx-tools.webp'));
        Product::create([
            'category_id' => $this->category->id,
            'name' => 'WinXTools',
            'slug' => 'winx-tools',
            'description' => 'WinXTools',
            'price' => 199,
            'stock' => 0,
            'is_active' => true,
        ]);

        $section = $this->productsSection();

        $this->assertStringContainsString('images/products/cards/winx-tools.webp', $section);
        $this->assertStringNotContainsString('artwork/product/winx-tools.webp', $section);
    }

    public function test_the_universe_card_shows_the_same_real_picture(): void
    {
        // Not saved: the migrations already seed a localvpn row.
        $product = new Product(['name' => 'LocalVPN', 'slug' => 'localvpn', 'price' => 199, 'is_active' => true]);

        $html = view('partials.universe.st-products', ['featuredProducts' => collect([$product])])->render();

        $this->assertStringContainsString('images/products/cards/localvpn.webp', $html);
        $this->assertStringNotContainsString('artwork/universe/products/localvpn.webp', $html);
    }
}
