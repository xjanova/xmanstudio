<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GPUxMINE's product page (/products/gpuxmine)
 *
 *   — its own view, not the generic page with a ฿0 cart and made-up Windows requirements
 *   — the client is free and nothing is for sale (no licence plans, Pro Miner not sold): no cart form, no price
 *     and no income figure anywhere — what a job pays is the pool's to say
 *   — every download button is /gpuxmine/download, the installer streamed from this site
 *   — the hold before money reaches the wallet is read from the same config the payout run uses
 *   — Coming Soon turns every download into the label
 */
class GpuxMineProductPageTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        // created by 2026_09_18_000002_register_gpuxmine_product when the database has a category
        $this->product = Product::firstOrCreate(['slug' => 'gpuxmine'], [
            'category_id' => $category->id,
            'name' => 'GPUxMINE Worker',
            'description' => 'x',
            'price' => 0,
            'requires_license' => true,
            'is_active' => true,
        ]);
        $this->product->update(['is_active' => true, 'is_coming_soon' => false, 'coming_soon_until' => null]);
    }

    public function test_the_page_is_its_own_view(): void
    {
        $this->get('/products/gpuxmine')
            ->assertOk()
            ->assertViewIs('products.gpuxmine')
            ->assertSee(asset('images/products/gpuxmine/v2/og.jpg'), false)
            ->assertSee('application/ld+json', false);
    }

    public function test_every_download_button_is_the_installer_from_this_site(): void
    {
        $html = $this->get('/products/gpuxmine')->assertOk()->getContent();

        // hero, the free card, the closing call to action
        $this->assertSame(3, substr_count($html, 'href="' . route('gpuxmine.download') . '"'));
        $this->assertStringNotContainsString('github.com', $html);
        // the next step after installing: the pairing code on My GPUs
        $this->assertStringContainsString('href="' . route('gpuxmine.index') . '"', $html);
    }

    public function test_nothing_is_for_sale_and_no_money_figure_is_promised(): void
    {
        foreach ([null, User::factory()->create()] as $viewer) {
            $html = ($viewer ? $this->actingAs($viewer) : $this)->get('/products/gpuxmine')->assertOk()->getContent();
            $main = $this->content($html);

            $this->assertStringNotContainsString(route('cart.add', $this->product), $main);
            $this->assertStringNotContainsString('license_type', $main);
            $this->assertStringNotContainsString('data-tpix-price', $main);
            $this->assertDoesNotMatchRegularExpression('/฿\s?\d/u', $main, 'a baht amount on the page');
        }
    }

    public function test_the_version_and_the_hold_come_from_the_site_not_the_page(): void
    {
        ProductVersion::create([
            'product_id' => $this->product->id,
            'version' => '0.1.18',
            'download_filename' => 'GpuxMine-win-Setup.exe',
            'file_size' => 90177536,
            'is_active' => true,
            'synced_at' => now(),
        ]);
        config(['services.gpuxmine.earning_hold_hours' => 36, 'services.gpuxmine.max_nodes_per_user' => 4]);

        $this->get('/products/gpuxmine')
            ->assertOk()
            ->assertSee('GPUxMINE 0.1.18')
            ->assertSee('พัก 36 ชม.')
            ->assertSee('สูงสุด 4 เครื่อง')
            ->assertDontSee('พัก 24 ชม.');
    }

    public function test_coming_soon_shows_the_label_instead_of_any_download(): void
    {
        $this->product->update(['is_coming_soon' => true]);

        $html = $this->get('/products/gpuxmine')->assertOk()->getContent();

        $this->assertStringNotContainsString('href="' . route('gpuxmine.download') . '"', $html);
        $this->assertSame(3, substr_count($html, 'data-coming-soon'));
        $this->assertStringContainsString('Coming Soon', $html);
    }

    /** The page's own part, without the site's header and footer (a signed-in header may show a wallet balance). */
    private function content(string $html): string
    {
        $start = strpos($html, 'class="xp relative overflow-hidden"');
        $this->assertNotFalse($start, 'the product page frame');

        return substr($html, $start);
    }
}
