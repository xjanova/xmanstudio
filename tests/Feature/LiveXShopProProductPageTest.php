<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * /products/livexshop-pro — its own page since 2026-10 (it used to fall through to products/show).
 * Live x Shop Pro is Coming Soon and still being built (D:\Code\LiveShop pro: roadmap phase 1), so
 * while Coming Soon the page offers no file and no way to buy. Its one price comes from the shop row
 * (products.price), never from the view, and nothing on it is sold as working until the app does it.
 */
class LiveXShopProProductPageTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        // the page reads everything from the database
        Http::preventStrayRequests();
        Http::fake();

        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $this->product = Product::firstOrCreate(['slug' => 'livexshop-pro'], [
            'category_id' => $category->id, 'name' => 'Live x Shop Pro', 'description' => 'x', 'price' => 5990,
            'requires_license' => true, 'is_active' => true,
        ]);
        $this->product->update(['is_active' => true, 'is_coming_soon' => true, 'coming_soon_until' => null, 'is_custom' => false]);
    }

    public function test_the_shop_slug_gets_its_own_page(): void
    {
        $this->get('/products/livexshop-pro')->assertOk()->assertViewIs('products.livexshoppro');
    }

    public function test_a_coming_soon_live_x_shop_pro_offers_no_download_and_no_purchase(): void
    {
        $html = $this->get('/products/livexshop-pro')->assertOk()->getContent();

        // hero, price card and the closing call to action
        $this->assertSame(3, substr_count($html, 'data-coming-soon'));
        $this->assertStringNotContainsString(route('cart.add', $this->product), $html);
        $this->assertStringNotContainsString('href="' . route('customer.downloads') . '"', $html);
        $this->assertStringNotContainsString('/download/livexshop-pro', $html);
        // the generic page's "notify me" link is kept
        $this->assertStringContainsString('href="' . route('quote.index') . '"', $html);
        // nothing to buy, so no Offer for search engines either
        $this->assertStringNotContainsString('"@type":"Offer"', $html);
    }

    public function test_the_price_is_the_shop_rows_not_a_number_written_into_the_page(): void
    {
        $this->product->update(['price' => 1234]);

        $html = $this->get('/products/livexshop-pro')->assertOk()->getContent();

        $this->assertStringContainsString('฿1,234', $html);
        $this->assertStringNotContainsString('5,990', $html);
    }

    public function test_it_claims_nothing_the_app_does_not_do(): void
    {
        $html = $this->get('/products/livexshop-pro')->assertOk()->getContent();

        // the old never-wired page sold multi-platform streaming, Twitch/YouTube/Instagram and a free download
        $this->assertStringNotContainsString('Twitch', $html);
        $this->assertStringNotContainsString('Multi-Platform Streaming', $html);
        $this->assertStringNotContainsString('ดาวน์โหลดฟรี', $html);
        // the shop issues a yearly key by default and the app has no licence code yet: no "buy once" promise
        $this->assertStringNotContainsString('ซื้อขาด', $html);
        $this->assertStringNotContainsString('ตลอดชีพ', $html);
        // every screen is a mock-up drawn from the app's XAML, and the page says so
        $this->assertStringContainsString('ภาพตัวอย่าง · MOCKUP', $html);
        $this->assertStringContainsString('images/products/livexshop-pro/v2/og.jpg', $html);
        $this->assertStringContainsString('images/products/livexshop-pro/v2/dashboard.webp', $html);
    }

    public function test_on_sale_a_guest_buys_through_the_cart_and_a_buyer_is_sent_to_their_downloads(): void
    {
        $this->product->update(['is_coming_soon' => false]);

        $guest = $this->get('/products/livexshop-pro')->assertOk()->getContent();
        $this->assertStringNotContainsString('data-coming-soon', $guest);
        $this->assertSame(1, substr_count($guest, 'action="' . route('cart.add', $this->product) . '"'));
        $this->assertStringNotContainsString('href="' . route('customer.downloads') . '"', $guest);
        $this->assertStringContainsString('"@type":"Offer"', $guest);

        $buyer = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(), 'user_id' => $buyer->id, 'customer_name' => $buyer->name,
            'customer_email' => $buyer->email, 'customer_phone' => '0800000000', 'subtotal' => 5990, 'total' => 5990,
            'status' => 'completed', 'payment_status' => 'paid',
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $this->product->id, 'product_name' => 'Live x Shop Pro',
            'price' => 5990, 'quantity' => 1, 'subtotal' => 5990,
        ]);

        $html = $this->actingAs($buyer)->get('/products/livexshop-pro')->assertOk()->getContent();
        // hero, price card and the closing call to action
        $this->assertSame(3, substr_count($html, 'href="' . route('customer.downloads') . '"'));
        // one key per purchase: a buyer can still buy another
        $this->assertSame(1, substr_count($html, 'action="' . route('cart.add', $this->product) . '"'));
    }

    public function test_a_custom_quote_product_is_sent_to_the_quote_page_not_the_cart(): void
    {
        $this->product->update(['is_coming_soon' => false, 'is_custom' => true]);

        $html = $this->get('/products/livexshop-pro')->assertOk()->getContent();

        $this->assertStringNotContainsString(route('cart.add', $this->product), $html);
        $this->assertStringContainsString('สอบถามราคา', $html);
    }
}
