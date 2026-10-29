<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * /products/skidrow-killer — while Coming Soon the page offers no file and no way to buy. On sale, the one
 * price is the shop row's (products.price, never a number in the view), the cart form posts to cart.add and
 * the download goes through the licence-checked download page. A buyer sees the state of their key.
 */
class SkidrowKillerProductPageTest extends TestCase
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
        $this->product = Product::firstOrCreate(['slug' => 'skidrow-killer'], [
            'category_id' => $category->id, 'name' => 'Skidrow Killer', 'description' => 'x', 'price' => 299,
            'requires_license' => true, 'is_active' => true,
        ]);
        $this->product->update(['is_active' => true, 'is_coming_soon' => true, 'coming_soon_until' => null, 'is_custom' => false]);
    }

    public function test_a_coming_soon_skidrow_killer_offers_no_download_and_no_purchase(): void
    {
        $version = $this->release();

        $html = $this->get('/products/skidrow-killer')->assertOk()->assertViewIs('products.skidrowkiller')->getContent();

        // hero, price card, download section and the closing call to action
        $this->assertSame(4, substr_count($html, 'data-coming-soon'));
        $this->assertStringContainsString('Coming Soon — ยังไม่เปิดให้ดาวน์โหลด', $html);
        $this->assertStringNotContainsString('action="' . route('cart.add', $this->product) . '"', $html);
        $this->assertStringNotContainsString(route('download.page', ['slug' => 'skidrow-killer', 'version' => $version->version]), $html);
        $this->assertStringNotContainsString('"@type":"Offer"', $html);
    }

    public function test_the_price_is_the_shop_rows_not_a_number_written_into_the_page(): void
    {
        $this->product->update(['price' => 1234, 'is_coming_soon' => false]);

        $html = $this->get('/products/skidrow-killer')->assertOk()->getContent();

        $this->assertStringContainsString('฿1,234', $html);
        $this->assertStringNotContainsString('฿299', $html);
        $this->assertStringContainsString('"price":"1234"', $html);
    }

    public function test_on_sale_the_page_sells_through_the_cart_and_downloads_through_the_licence_check(): void
    {
        $this->product->update(['is_coming_soon' => false]);
        $version = $this->release();

        $html = $this->get('/products/skidrow-killer')->assertOk()->getContent();

        $this->assertStringNotContainsString('data-coming-soon', $html);
        $this->assertStringContainsString('action="' . route('cart.add', $this->product) . '"', $html);
        $this->assertStringContainsString('name="quantity"', $html);
        $this->assertStringContainsString(route('download.page', ['slug' => 'skidrow-killer', 'version' => $version->version]), $html);
        $this->assertStringContainsString('"@type":"Offer"', $html);
        // every screen is a mock-up until a real capture exists, and the page says so
        $this->assertStringContainsString('images/products/skidrow-killer/v2/og.jpg', $html);
        $this->assertStringContainsString('images/products/skidrow-killer/v2/home.webp', $html);
        $this->assertStringContainsString('ภาพตัวอย่าง · MOCKUP', $html);
    }

    public function test_a_custom_priced_product_asks_for_a_quote_instead_of_a_cart(): void
    {
        $this->product->update(['is_coming_soon' => false, 'is_custom' => true]);

        $html = $this->get('/products/skidrow-killer')->assertOk()->getContent();

        $this->assertStringNotContainsString('action="' . route('cart.add', $this->product) . '"', $html);
        $this->assertStringContainsString(route('quote.index'), $html);
        $this->assertStringNotContainsString('"@type":"Offer"', $html);
    }

    public function test_a_buyer_sees_the_state_of_their_key(): void
    {
        $buyer = User::factory()->create();
        $order = $this->order($buyer);

        $key = $this->key($order, ['expires_at' => now()->addMonths(6)]);
        $this->actingAs($buyer)->get('/products/skidrow-killer')->assertOk()
            ->assertSee('data-license-state="active"', false)
            ->assertSee(route('customer.licenses.show', $key), false);

        $key->update(['expires_at' => now()->addDays(3)]);
        $this->actingAs($buyer)->get('/products/skidrow-killer')->assertOk()->assertSee('data-license-state="expiring"', false);

        $key->update(['expires_at' => now()->subDay()]);
        $this->actingAs($buyer)->get('/products/skidrow-killer')->assertOk()->assertSee('data-license-state="expired"', false);

        // a key without an end date must not crash the page or read as "0 days left"
        $key->update(['expires_at' => null, 'license_type' => 'lifetime']);
        $this->actingAs($buyer)->get('/products/skidrow-killer')->assertOk()->assertSee('data-license-state="active"', false);
    }

    private function release(): ProductVersion
    {
        return ProductVersion::create([
            'product_id' => $this->product->id,
            'version' => '3.6.0',
            'download_url' => 'https://downloads.example.test/skidrow-killer-3.6.0.zip',
            'download_filename' => 'skidrow-killer-3.6.0.zip',
            'file_size' => 1024,
            'is_active' => true,
            'synced_at' => now(),
        ]);
    }

    private function order(User $buyer): Order
    {
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(), 'user_id' => $buyer->id, 'customer_name' => $buyer->name,
            'customer_email' => $buyer->email, 'customer_phone' => '0800000000', 'subtotal' => 299, 'total' => 299,
            'status' => 'completed', 'payment_status' => 'paid',
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $this->product->id, 'product_name' => 'Skidrow Killer',
            'price' => 299, 'quantity' => 1, 'subtotal' => 299,
        ]);

        return $order;
    }

    private function key(Order $order, array $attributes): LicenseKey
    {
        return LicenseKey::create($attributes + [
            'product_id' => $this->product->id,
            'order_id' => $order->id,
            'license_key' => LicenseKey::generateKey(),
            'license_type' => 'yearly',
            'status' => 'active',
            'max_activations' => 1,
            'activations' => 0,
        ]);
    }
}
