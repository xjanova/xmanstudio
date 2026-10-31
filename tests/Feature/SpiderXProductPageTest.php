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
 * /products/spiderx — SpiderX is Coming Soon (owner, 2026-10-12): no file and no way to buy anywhere
 * on the page. Its one price comes from the shop row (products.price), never from the view, and the
 * page links nothing on GitHub (the repo link was dead and cleared on 2026-10-12).
 */
class SpiderXProductPageTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        // the page reads everything from the database — it never asks GitHub
        Http::preventStrayRequests();
        Http::fake();

        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $this->product = Product::firstOrCreate(['slug' => 'spiderx'], [
            'category_id' => $category->id, 'name' => 'SpiderX', 'description' => 'x', 'price' => 2990,
            'requires_license' => true, 'is_active' => true,
        ]);
        $this->product->update(['is_active' => true, 'is_coming_soon' => true, 'coming_soon_until' => null]);
    }

    public function test_a_coming_soon_spiderx_offers_no_download_and_no_purchase(): void
    {
        $html = $this->get('/products/spiderx')->assertOk()->assertViewIs('products.spiderx')->getContent();

        // hero, price card and the closing call to action
        $this->assertSame(3, substr_count($html, 'data-coming-soon'));
        $this->assertStringNotContainsString(route('cart.add', $this->product), $html);
        $this->assertStringNotContainsString('name="license_type"', $html);
        $this->assertStringNotContainsString('href="' . route('customer.downloads') . '"', $html);
        $this->assertStringNotContainsString('/download/spiderx', $html);
        // nothing to buy, so no Offer for search engines either
        $this->assertStringNotContainsString('"@type":"Offer"', $html);
    }

    public function test_the_price_is_the_shop_rows_not_a_number_written_into_the_page(): void
    {
        $this->product->update(['price' => 1234]);

        $html = $this->get('/products/spiderx')->assertOk()->getContent();

        $this->assertStringContainsString('฿1,234', $html);
        $this->assertStringNotContainsString('฿2,990', $html);
    }

    public function test_no_github_link_and_the_new_pictures(): void
    {
        $html = $this->get('/products/spiderx')->assertOk()->getContent();

        $this->assertStringNotContainsStringIgnoringCase('github.com', $html);
        $this->assertStringContainsString('images/products/spiderx/v2/og.jpg', $html);
        $this->assertStringContainsString('images/products/spiderx/v2/dashboard.webp', $html);
        // every screen is a mock-up until a real capture exists, and the page says so
        $this->assertStringContainsString('ภาพตัวอย่าง · MOCKUP', $html);
    }

    public function test_on_sale_a_buyer_is_sent_to_their_downloads_and_nobody_else_is(): void
    {
        $this->product->update(['is_coming_soon' => false]);

        $guest = $this->get('/products/spiderx')->assertOk()->getContent();
        $this->assertStringNotContainsString('data-coming-soon', $guest);
        $this->assertStringNotContainsString('href="' . route('customer.downloads') . '"', $guest);
        $this->assertStringContainsString('"@type":"Offer"', $guest);

        $buyer = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(), 'user_id' => $buyer->id, 'customer_name' => $buyer->name,
            'customer_email' => $buyer->email, 'customer_phone' => '0800000000', 'subtotal' => 2990, 'total' => 2990,
            'status' => 'completed', 'payment_status' => 'paid',
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $this->product->id, 'product_name' => 'SpiderX',
            'price' => 2990, 'quantity' => 1, 'subtotal' => 2990,
        ]);

        $html = $this->actingAs($buyer)->get('/products/spiderx')->assertOk()->getContent();
        // hero, price card and the closing call to action
        $this->assertSame(3, substr_count($html, 'href="' . route('customer.downloads') . '"'));
    }
}
