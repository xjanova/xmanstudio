<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\User;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * LocalVPN on the site: /products/localvpn, the three Pro plans through the cart, and the data
 * migration of 2026-10-11 (monthly row price, two devices per paid key).
 *
 * Prices come from config/licenses.php only — the page, the cart and the pricing API all read it.
 */
class LocalVpnProductPageTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->withoutVite();

        // the page reads the version from the database only — it never asks GitHub
        Http::preventStrayRequests();

        $this->product = Product::where('slug', 'localvpn')->firstOrFail();
    }

    // ── the product row (migration) ─────────────────────────────────────

    public function test_the_migration_makes_the_row_price_the_monthly_plan(): void
    {
        $this->assertEquals(199, (float) $this->product->price);
        $this->assertSame('monthly', $this->product->defaultLicenseType());
        $this->assertStringContainsString('Windows และ Android', $this->product->short_description);
        $this->assertContains('Windows 10/11 และ Android 7.0+', $this->product->features);
        $this->assertStringNotContainsString('iOS', json_encode($this->product->features, JSON_UNESCAPED_UNICODE));
    }

    public function test_the_migration_gives_active_paid_keys_two_devices_and_touches_nothing_else(): void
    {
        $keys = [
            'yearly' => $this->key('yearly', 1),
            'monthly' => $this->key('monthly', 1),
            'lifetime_three' => $this->key('lifetime', 3),
            'free' => $this->key('free', 1),
            'demo' => $this->key('demo', 1),
            'revoked' => $this->key('yearly', 1, ['status' => 'revoked']),
        ];
        $antiX = $this->key('yearly', 1, ['product_id' => Product::where('slug', 'anti-x')->value('id')]);

        // an admin's own wording stays
        $this->product->update(['short_description' => 'แก้ในหน้า admin', 'features' => ['ข้อความของแอดมิน']]);

        $this->migration()->up();

        $this->assertSame(['ข้อความของแอดมิน'], $this->product->fresh()->features);

        $this->assertSame(2, $keys['yearly']->fresh()->max_activations);
        $this->assertSame(2, $keys['monthly']->fresh()->max_activations);
        $this->assertSame(3, $keys['lifetime_three']->fresh()->max_activations);
        $this->assertSame(1, $keys['free']->fresh()->max_activations);
        $this->assertSame(1, $keys['demo']->fresh()->max_activations);
        $this->assertSame(1, $keys['revoked']->fresh()->max_activations);
        $this->assertSame(1, $antiX->fresh()->max_activations);

        $this->assertSame('แก้ในหน้า admin', $this->product->fresh()->short_description);
        $this->assertEquals(199, (float) $this->product->fresh()->price);

        // and rolling back puts nothing back
        $this->migration()->down();
        $this->assertSame(2, $keys['yearly']->fresh()->max_activations);
    }

    // ── plans ───────────────────────────────────────────────────────────

    public function test_the_pricing_api_sells_the_three_plans_and_points_at_the_product_page(): void
    {
        $this->getJson('/api/v1/product/localvpn/pricing')->assertOk()
            ->assertJsonPath('data.plans.monthly.price', 199)
            ->assertJsonPath('data.plans.yearly.price', 1990)
            ->assertJsonPath('data.plans.lifetime.price', 4990)
            ->assertJsonPath('data.purchase_url', url('/products/localvpn'));
    }

    public function test_each_term_goes_into_the_cart_at_its_table_price(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['monthly' => 199, 'yearly' => 1990, 'lifetime' => 4990] as $term => $price) {
            CartItem::query()->delete();

            // the form's own price is ignored — the cart charges config/licenses.php
            $this->post(route('cart.add', $this->product), ['quantity' => 1, 'license_type' => $term, 'price' => 1])
                ->assertSessionHas('success');

            $item = CartItem::sole();
            $this->assertEquals($price, (float) $item->price, $term);
            $this->assertSame($term, json_decode($item->custom_requirements, true)['license_type']);
        }
    }

    public function test_buy_now_goes_straight_to_the_cart(): void
    {
        $this->post(route('cart.add', $this->product), ['license_type' => 'yearly', 'buy_now' => 1])
            ->assertRedirect(route('cart.index'));

        $this->assertEquals(1990, (float) CartItem::sole()->price);
    }

    public function test_a_purchase_that_names_no_term_is_a_monthly_two_device_key(): void
    {
        $this->freezeSecond();
        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(),
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '0800000000',
            'subtotal' => 199,
            'total' => 199,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $this->product->id, 'product_name' => 'LocalVPN', 'price' => 199, 'quantity' => 1, 'subtotal' => 199]);

        app(LicenseService::class)->generateLicensesForOrder($order);

        $key = LicenseKey::where('product_id', $this->product->id)->sole();
        $this->assertSame('monthly', $key->license_type);
        $this->assertTrue($key->expires_at->equalTo(now()->addDays(30)));
        $this->assertSame(2, $key->max_activations);
    }

    // ── the page ────────────────────────────────────────────────────────

    public function test_the_page_sells_three_plans_and_downloads_from_this_site(): void
    {
        $html = $this->get('/products/localvpn')->assertOk()->getContent();

        $this->assertSame(3, substr_count($html, 'action="' . route('cart.add', $this->product) . '"'));
        foreach (['monthly', 'yearly', 'lifetime'] as $term) {
            $this->assertSame(1, substr_count($html, 'name="license_type" value="' . $term . '"'), $term);
        }
        $this->assertSame(3, substr_count($html, 'name="buy_now" value="1"'));

        foreach (['฿199', '฿1,990', '฿4,990', '30 วัน', '1 คีย์ใช้ได้ 2 เครื่อง', 'Windows 10/11', 'Android 7.0'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertMatchesRegularExpression('/<title>[^<]*฿199[^<]*<\/title>/u', $html);

        // the old prices are gone from every corner of the page
        $page = $this->pageBody($html);
        foreach (['399', '2,500', '2500', '5,000', '5000'] as $old) {
            $this->assertStringNotContainsString($old, $page, $old);
        }

        // Windows installer (its own route once this site has it) and Android APK — hero, Free card,
        // download section, closing call to action
        $windows = Route::has('localvpn.download.windows') ? route('localvpn.download.windows') : route('localvpn.download');
        $this->assertSame(4, substr_count($html, 'href="' . $windows . '"'));
        $this->assertSame(4, substr_count($html, 'href="' . route('localvpn.download.apk') . '"'));
        $this->assertStringContainsString('href="' . route('localvpn.install-guide') . '"', $html);
        $this->assertStringNotContainsStringIgnoringCase('github', $html);

        // a card per plan carries the id the app's old /localvpn#plan links jump to
        foreach (['monthly', 'yearly', 'lifetime'] as $term) {
            $this->assertStringContainsString('id="' . $term . '"', $html);
        }

        // meta and social preview
        $this->assertStringContainsString('<link rel="canonical" href="' . url('/products/localvpn') . '"', $html);
        $this->assertStringContainsString(asset('images/products/localvpn/og.jpg'), $html);
        $this->assertStringContainsString('"@type":"SoftwareApplication"', $html);

        // a guest sees no "my keys" link
        $this->assertStringNotContainsString(route('customer.licenses'), $html);
    }

    public function test_the_page_follows_the_price_table(): void
    {
        config()->set('licenses.plans.localvpn', ['monthly' => 249, 'yearly' => 2490, 'lifetime' => 5990]);

        $html = $this->get('/products/localvpn')->assertOk()->getContent();

        $this->assertStringContainsString('฿249', $html);
        $this->assertStringContainsString('฿2,490', $html);
        $this->assertStringContainsString('฿5,990', $html);
        $this->assertStringNotContainsString('฿199', $html);
        $this->assertStringNotContainsString('฿1,990', $html);
    }

    public function test_every_picture_on_the_page_ships_with_the_site(): void
    {
        $html = $this->get('/products/localvpn')->assertOk()->getContent();

        preg_match_all('#' . preg_quote(asset('images/products/localvpn/'), '#') . '([a-z0-9/\-.]+)#', $html, $matches);
        $this->assertNotEmpty($matches[1]);

        foreach (array_unique($matches[1]) as $file) {
            $this->assertFileExists(public_path('images/products/localvpn/' . $file), $file);
        }
        $this->assertFileExists(public_path('artwork/product/localvpn.webp'));
    }

    public function test_a_buyer_can_buy_another_key_and_find_their_keys(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(), 'user_id' => $user->id, 'customer_name' => $user->name,
            'customer_email' => $user->email, 'customer_phone' => '0800000000', 'subtotal' => 199, 'total' => 199,
            'status' => 'completed', 'payment_status' => 'paid',
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $this->product->id, 'product_name' => 'LocalVPN', 'price' => 199, 'quantity' => 1, 'subtotal' => 199]);

        $html = $this->actingAs($user)->get('/products/localvpn')->assertOk()->getContent();

        $this->assertSame(3, substr_count($html, 'name="buy_now" value="1"'));
        $this->assertStringContainsString('ซื้อ License เพิ่ม', $html);
        $this->assertStringContainsString('href="' . route('customer.licenses') . '"', $html);
    }

    public function test_the_page_shows_the_version_this_site_serves(): void
    {
        ProductVersion::create([
            'product_id' => $this->product->id,
            'version' => '1.1.0',
            'is_active' => true,
        ]);

        $this->get('/products/localvpn')->assertOk()->assertSee('LocalVPN 1.1.0');
    }

    public function test_old_localvpn_links_land_on_the_product_page(): void
    {
        $this->get('/localvpn')->assertRedirect(route('products.show', 'localvpn'));
        $this->get(route('localvpn.pricing'))->assertOk()->assertSee(route('products.show', 'localvpn'), false);
    }

    public function test_the_listing_card_shows_the_monthly_price_and_links_to_the_page(): void
    {
        $html = $this->get('/products')->assertOk()->getContent();

        $this->assertStringContainsString('href="' . route('products.show', 'localvpn') . '"', $html);
        // Its picture, the one every page shows (Product::artwork_url).
        $this->assertStringContainsString($this->product->fresh()->artwork_url, $html);

        $start = strpos($html, 'href="' . route('products.show', 'localvpn') . '"');
        $card = substr($html, $start, 6000);
        $this->assertStringContainsString('฿199', $card);
    }

    // ── helpers ─────────────────────────────────────────────────────────

    /** the page's own markup, without the per-request CSRF tokens (random characters) */
    private function pageBody(string $html): string
    {
        $start = strpos($html, '<div class="lvp ');
        $end = strpos($html, '</script>', strpos($html, 'application/ld+json'));
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        return preg_replace('/name="_token" value="[^"]*"/', '', substr($html, $start, $end - $start));
    }

    private function key(string $type, int $devices, array $attributes = []): LicenseKey
    {
        return LicenseKey::create($attributes + [
            'product_id' => $this->product->id,
            'license_key' => LicenseKey::generateKey(),
            'license_type' => $type,
            'status' => 'active',
            'expires_at' => in_array($type, ['lifetime', 'free'], true) ? null : now()->addMonth(),
            'max_activations' => $devices,
            'activations' => 0,
        ]);
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_10_11_120000_localvpn_pro_prices_and_two_devices.php');
    }
}
