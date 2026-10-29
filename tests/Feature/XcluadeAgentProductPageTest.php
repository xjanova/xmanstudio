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
use Tests\TestCase;

/**
 * XcluadeAgent's product page (/products/xcluade-agent) — its own view, not the generic products.show
 * and never the CluadeX page (products.xcluadeagent).
 *
 *   — the price is the product row's, never a figure written into the page; the cart sends no
 *     license_type, so the key is the shop's default term (Product::defaultLicenseType(): a year)
 *   — Coming Soon: every download is the label, nothing can be put in the cart
 *   — on sale: two cart forms (the pricing card with a quantity, the closing call to action);
 *     a buyer reaches the Download Center from #download
 *   — no claim the app's code does not back (SSL monitoring, Windows Server, unlimited projects,
 *     the AI fixing production by itself)
 */
class XcluadeAgentProductPageTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Without an admin every page redirects to the setup wizard.
        User::factory()->create(['role' => 'admin']);

        $category = Category::firstOrCreate(['slug' => 'ai'], ['name' => 'AI', 'description' => 'x']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'XcluadeAgent',
            'slug' => 'xcluade-agent',
            'sku' => 'XCA-001',
            'description' => 'x',
            'price' => 4321,
            'requires_license' => true,
            'stock' => 999,
            'is_active' => true,
            'is_coming_soon' => true,
        ]);
    }

    public function test_the_page_is_its_own_view(): void
    {
        $this->get('/products/xcluade-agent')
            ->assertOk()
            ->assertViewIs('products.xcluade-agent')
            ->assertSee('XcluadeAgent')
            ->assertSee('application/ld+json', false)
            ->assertSee('id="features"', false)
            ->assertSee('id="pricing"', false);
    }

    public function test_coming_soon_shows_the_label_and_sells_nothing(): void
    {
        $this->release();

        foreach ([null, $this->buyer()] as $viewer) {
            $html = ($viewer ? $this->actingAs($viewer) : $this)->get('/products/xcluade-agent')->assertOk()->getContent();
            $main = $this->content($html);

            // hero, pricing card, #download, closing call to action
            $this->assertSame(4, substr_count($main, 'data-coming-soon'));
            $this->assertStringContainsString('Coming Soon — ยังไม่เปิดให้ดาวน์โหลด', $main);
            $this->assertStringNotContainsString(route('cart.add', $this->product), $main);
            $this->assertStringNotContainsString('href="' . route('customer.downloads') . '"', $main);
            // the listed price still shows, from the product row
            $this->assertStringContainsString('฿4,321', $main);
        }
    }

    public function test_on_sale_it_sells_through_the_cart_at_the_rows_price(): void
    {
        $this->product->update(['is_coming_soon' => false]);

        $main = $this->content($this->get('/products/xcluade-agent')->assertOk()->getContent());

        $this->assertStringNotContainsString('data-coming-soon', $main);
        $this->assertSame(2, substr_count($main, 'action="' . route('cart.add', $this->product) . '"'));
        $this->assertStringNotContainsString('license_type', $main);
        $this->assertStringContainsString('฿4,321', $main);
        $this->assertStringNotContainsString('3,490', $main);
        // no license_type → the shop issues its default term for this slug
        $this->assertSame(LicenseKey::TYPE_YEARLY, $this->product->defaultLicenseType());
        $this->assertStringContainsString('อายุ License: 1 ปี', $main);
    }

    public function test_a_buyer_reaches_the_download_center_once_it_is_on_sale(): void
    {
        $this->product->update(['is_coming_soon' => false]);
        $this->release();

        // first as a guest: actingAs() would stay signed in for every request after it
        $guestPage = $this->content($this->get('/products/xcluade-agent')->assertOk()->getContent());
        $this->assertStringNotContainsString('href="' . route('customer.downloads') . '"', $guestPage);

        $buyerPage = $this->content($this->actingAs($this->buyer())->get('/products/xcluade-agent')->assertOk()->getContent());
        $download = substr($buyerPage, strpos($buyerPage, 'id="download"'));
        $this->assertStringContainsString('href="' . route('customer.downloads') . '"', $download);
        $this->assertStringContainsString('v1.0.0', $download);
    }

    public function test_no_claim_the_apps_code_does_not_back(): void
    {
        foreach ([true, false] as $comingSoon) {
            $this->product->update(['is_coming_soon' => $comingSoon]);
            $main = $this->content($this->get('/products/xcluade-agent')->assertOk()->getContent());

            foreach (['SSL Certificate', 'SSL monitoring', 'Windows Server', 'Windows 10', 'ไม่จำกัดโปรเจกต์', 'Unlimited', 'Auto Production', 'แก้ไข Production โดยตรง'] as $claim) {
                $this->assertStringNotContainsString($claim, $main, $claim);
            }
            $this->assertStringContainsString('ยังไม่แก้ไฟล์บนเซิร์ฟเวอร์เอง', $main);
        }
    }

    private function release(): ProductVersion
    {
        return ProductVersion::create([
            'product_id' => $this->product->id,
            'version' => '1.0.0',
            'download_url' => 'https://downloads.example.test/xcluade-agent-1.0.0.zip',
            'download_filename' => 'xcluade-agent-1.0.0.zip',
            'file_size' => 1024,
            'is_active' => true,
            'synced_at' => now(),
        ]);
    }

    private function buyer(): User
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(), 'user_id' => $user->id, 'customer_name' => $user->name, 'customer_email' => $user->email,
            'customer_phone' => '0800000000', 'subtotal' => 4321, 'total' => 4321, 'status' => 'completed', 'payment_status' => 'paid',
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $this->product->id, 'product_name' => 'XcluadeAgent',
            'price' => 4321, 'quantity' => 1, 'subtotal' => 4321,
        ]);

        return $user;
    }

    /** The page's own part, without the site's header and footer. */
    private function content(string $html): string
    {
        $start = strpos($html, 'class="xp relative overflow-hidden"');
        $this->assertNotFalse($start, 'the product page frame');
        $end = strpos($html, 'application/ld+json', $start);

        return substr($html, $start, $end === false ? null : $end - $start);
    }
}
