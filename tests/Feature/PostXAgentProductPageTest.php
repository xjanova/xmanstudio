<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PostXAgent's product page (/products/postx-agent)
 *
 *   — its own reviewed view (products.postxagent), not the generic page
 *   — while the shop says Coming Soon: the label where every download and buy button would be, no cart form,
 *     no link to the Download Center (owner's rule 2026-10-12)
 *   — on sale: one cart form at the row's own price, sold as a single licence (no license_type — the product has
 *     no term plans in config/licenses.php)
 *   — the price is never written into the page: it follows products.price
 */
class PostXAgentProductPageTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'PostXAgent',
            'slug' => 'postx-agent',
            'description' => 'x',
            'price' => 7990,
            'stock' => 999,
            'requires_license' => true,
            'is_active' => true,
            'is_coming_soon' => true,
        ]);
    }

    public function test_the_page_is_its_own_view(): void
    {
        $this->get('/products/postx-agent')
            ->assertOk()
            ->assertViewIs('products.postxagent')
            ->assertSee(asset('images/products/postx-agent/v2/og.jpg'), false)
            ->assertSee('application/ld+json', false)
            ->assertSee('id="pricing"', false)
            ->assertSee('id="features"', false);
    }

    public function test_coming_soon_offers_nothing_to_buy_or_download(): void
    {
        $this->version();

        foreach ([null, User::factory()->create()] as $viewer) {
            $html = ($viewer ? $this->actingAs($viewer) : $this)->get('/products/postx-agent')->assertOk()->getContent();
            $main = $this->content($html);

            $this->assertStringContainsString('data-coming-soon', $main);
            $this->assertStringContainsString('Coming Soon — ยังไม่เปิดให้ดาวน์โหลด', $main);
            $this->assertStringContainsString('Coming Soon — ยังไม่เปิดขาย', $main);
            $this->assertStringNotContainsString('action="' . route('cart.add', $this->product) . '"', $main);
            $this->assertStringNotContainsString('href="' . route('customer.downloads') . '"', $main);
            $this->assertStringNotContainsString('/download/postx-agent', $main);
            $this->assertStringContainsString('id="download"', $main);
        }
    }

    public function test_on_sale_it_is_one_cart_form_at_the_rows_own_price(): void
    {
        // a price no page could have hard-coded
        $this->product->update(['is_coming_soon' => false, 'price' => 1234]);

        $main = $this->content($this->get('/products/postx-agent')->assertOk()->getContent());

        $this->assertSame(1, substr_count($main, 'action="' . route('cart.add', $this->product) . '"'));
        $this->assertStringNotContainsString('name="license_type"', $main);
        $this->assertStringContainsString('฿1,234', $main);
        $this->assertStringNotContainsString('7,990', $main);
        $this->assertStringNotContainsString('data-coming-soon', $main);
    }

    public function test_a_buyer_downloads_from_the_download_center_once_it_is_on_sale(): void
    {
        $this->product->update(['is_coming_soon' => false]);
        $this->version();
        $buyer = User::factory()->create();
        $order = Order::create([
            'user_id' => $buyer->id,
            'order_number' => 'ORD-PXA-1',
            'customer_name' => $buyer->name,
            'customer_email' => $buyer->email,
            'subtotal' => 100,
            'tax' => 0,
            'total' => 100,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $this->product->id, 'product_name' => 'PostXAgent', 'price' => 100, 'quantity' => 1, 'subtotal' => 100]);

        $main = $this->content($this->actingAs($buyer)->get('/products/postx-agent')->assertOk()->getContent());

        $this->assertStringContainsString('href="' . route('customer.downloads') . '"', $main);
    }

    public function test_the_view_holds_no_price_of_its_own(): void
    {
        $source = file_get_contents(resource_path('views/products/postxagent.blade.php'));

        $this->assertDoesNotMatchRegularExpression('/฿\s?\d/u', $source, 'a baht amount written into the page');
        $this->assertStringNotContainsString('7990', $source);
        $this->assertStringNotContainsString('7,990', $source);
    }

    private function version(): void
    {
        ProductVersion::create([
            'product_id' => $this->product->id,
            'version' => '1.0.0',
            'download_filename' => 'PostXAgent-win.zip',
            'file_size' => 52428800,
            'is_active' => true,
            'synced_at' => now(),
        ]);
    }

    private function content(string $html): string
    {
        $start = strpos($html, 'class="xp relative overflow-hidden"');
        $this->assertNotFalse($start, 'the product page frame');

        return substr($html, $start);
    }
}
