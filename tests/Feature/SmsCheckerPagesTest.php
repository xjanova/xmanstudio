<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The two SMS Checker pages are two products:
 *   /smschecker                      — the Android app (slug smschecker): prices from config 'plans.smschecker',
 *                                      every term goes through its own /smschecker/pricing checkout
 *   /products/sms-payment-checker    — the WordPress/WooCommerce plugin: prices from 'plans.sms-payment-checker',
 *                                      sold through the cart with a license_type on every form
 * Neither page carries a price of its own; a Coming Soon product sells nothing.
 */
class SmsCheckerPagesTest extends TestCase
{
    use RefreshDatabase;

    private Product $smsApp;

    private Product $plugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        // neither page may ask GitHub or anything else outside
        Http::preventStrayRequests();
        Http::fake();

        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $this->smsApp = Product::updateOrCreate(['slug' => 'smschecker'], [
            'category_id' => $category->id, 'name' => 'SmsChecker', 'description' => 'x', 'price' => 499,
            'requires_license' => true, 'is_active' => true, 'is_coming_soon' => false, 'coming_soon_until' => null,
        ]);
        $this->plugin = Product::updateOrCreate(['slug' => 'sms-payment-checker'], [
            'category_id' => $category->id, 'name' => 'SMS Payment Checker', 'description' => 'x', 'price' => 990,
            'requires_license' => true, 'is_active' => true, 'is_coming_soon' => false, 'coming_soon_until' => null,
        ]);
    }

    public function test_the_app_page_shows_the_config_prices_and_sends_every_term_to_its_own_checkout(): void
    {
        config(['licenses.plans.smschecker' => ['monthly' => 345, 'yearly' => 3456, 'lifetime' => 12345]]);

        $html = $this->get('/smschecker')->assertOk()->assertViewIs('smschecker.detail')->getContent();

        $this->assertStringContainsString('฿345', $html);
        $this->assertStringContainsString('฿3,456', $html);
        $this->assertStringContainsString('฿12,345', $html);
        // nothing left over from the price table the code no longer charges
        $this->assertStringNotContainsString('฿4,990', $html);
        $this->assertStringNotContainsString('฿29,000', $html);

        $this->assertStringContainsString('href="' . route('smschecker.pricing') . '"', $html);
        $this->assertStringContainsString('href="' . route('smschecker.download') . '"', $html);
        // the app is sold through its own checkout, never through the shop cart
        $this->assertStringNotContainsString(route('cart.add', $this->smsApp), $html);
    }

    public function test_the_app_page_carries_seo_and_no_github_link(): void
    {
        $html = $this->get('/smschecker')->assertOk()->getContent();

        $this->assertStringContainsString('images/products/smschecker/v2/og.jpg', $html);
        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('id="pricing"', $html);
        $this->assertStringNotContainsString('github.com', $html);
    }

    public function test_the_app_page_names_the_latest_release(): void
    {
        ProductVersion::create([
            'product_id' => $this->smsApp->id,
            'version' => '2.0.188',
            'github_release_url' => 'https://api.github.com/repos/example/example/releases/assets/1',
            'is_active' => true,
        ]);

        $html = $this->get('/smschecker')->assertOk()->getContent();

        $this->assertStringContainsString('v2.0.188', $html);
        $this->assertStringContainsString('"softwareVersion":"2.0.188"', $html);
    }

    public function test_a_coming_soon_app_shows_the_label_and_sells_nothing(): void
    {
        $this->smsApp->update(['is_coming_soon' => true]);

        $html = $this->get('/smschecker')->assertOk()->getContent();

        $this->assertStringContainsString('data-coming-soon', $html);
        $this->assertStringNotContainsString('href="' . route('smschecker.download') . '"', $html);
        $this->assertStringNotContainsString('href="' . route('smschecker.pricing') . '"', $html);
    }

    public function test_the_plugin_page_sells_every_term_through_the_cart_at_the_config_price(): void
    {
        config(['licenses.plans.sms-payment-checker' => ['monthly' => 456, 'yearly' => 4567, 'lifetime' => 23456]]);

        $html = $this->get('/products/sms-payment-checker')->assertOk()->assertViewIs('products.smspaymentchecker')->getContent();

        $this->assertStringContainsString('฿456', $html);
        $this->assertStringContainsString('฿4,567', $html);
        $this->assertStringContainsString('฿23,456', $html);
        foreach (['monthly', 'yearly', 'lifetime'] as $term) {
            $this->assertStringContainsString('name="license_type" value="' . $term . '"', $html);
        }
        // every cart form names its term: a form without one would charge products.price with no expiry
        $this->assertSame(3, substr_count($html, 'action="' . route('cart.add', $this->plugin) . '"'));
        $this->assertSame(3, substr_count($html, 'name="license_type"'));
        $this->assertStringNotContainsString('฿990', $html);
        $this->assertStringNotContainsString('17%', $html);
    }

    public function test_the_plugin_page_carries_seo(): void
    {
        $html = $this->get('/products/sms-payment-checker')->assertOk()->getContent();

        $this->assertStringContainsString('images/products/smschecker/v2/og.jpg', $html);
        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('id="pricing"', $html);
        $this->assertStringContainsString('id="features"', $html);
    }

    public function test_a_coming_soon_plugin_sells_nothing(): void
    {
        $this->plugin->update(['is_coming_soon' => true]);

        $html = $this->get('/products/sms-payment-checker')->assertOk()->getContent();

        $this->assertStringContainsString('data-coming-soon', $html);
        $this->assertStringNotContainsString(route('cart.add', $this->plugin), $html);
    }

    public function test_the_plugin_page_links_the_app_only_while_the_app_is_on_sale(): void
    {
        $this->assertStringContainsString('href="' . route('smschecker.detail') . '"', $this->get('/products/sms-payment-checker')->getContent());

        // sales closed: /smschecker answers 404, so the plugin page must not send anyone there
        $this->smsApp->forceFill(['is_active' => false])->save();
        $this->assertStringNotContainsString('href="' . route('smschecker.detail') . '"', $this->get('/products/sms-payment-checker')->getContent());
    }

    public function test_a_revoked_lifetime_key_without_an_expiry_does_not_break_the_plugin_page(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(), 'user_id' => $user->id, 'customer_name' => $user->name,
            'customer_email' => $user->email, 'customer_phone' => '0800000000', 'subtotal' => 1, 'total' => 1,
            'status' => 'completed', 'payment_status' => 'paid',
        ]);
        LicenseKey::create([
            'product_id' => $this->plugin->id, 'order_id' => $order->id, 'user_id' => $user->id,
            'license_key' => 'TEST-' . uniqid(), 'status' => 'revoked', 'license_type' => 'lifetime', 'expires_at' => null,
        ]);

        $html = $this->actingAs($user)->get('/products/sms-payment-checker')->assertOk()->getContent();

        $this->assertStringContainsString('ต่ออายุตอนนี้', $html);
    }
}
