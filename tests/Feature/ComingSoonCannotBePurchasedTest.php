<?php

namespace Tests\Feature;

use App\Models\AiprayAiModel;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Support\ComingSoonPurchase;
use App\Support\DgxSparkCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Owner's rule (2026-10-12): "สินค้าไหน coming soon ต้องยังดาวโหลดไม่ได้ ซื้อไม่ได้" — the buying half.
 * (Downloads: ComingSoonDownloadsTest.)
 *
 * What these pin, for a product that is switched on but still Coming Soon:
 *   — each app's own checkout (AutoTradeX, Tping, SMS Checker, LocalVPN) refuses both the page and
 *     a hand-made POST: no order, no licence, no money out of the wallet
 *   — the cart checkout refuses a product that turned Coming Soon after it went into the cart,
 *     names it, and leaves the cart as it was
 *   — the cart, the DGX Spark bundle and the pricing API refuse / offer nothing to buy
 *   — a browser goes back to the page with the Coming Soon label and a Thai + English message,
 *     an app gets 403 { success: false, coming_soon: true }
 *   — a licence bought before keeps validating, and once coming_soon_until has passed the
 *     checkout opens again by itself
 */
class ComingSoonCannotBePurchasedTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Http::preventStrayRequests();
        $this->withoutVite();
        // Settings are cached under fixed keys that outlive a test's database.
        Cache::flush();

        // Without an admin every page redirects to the setup wizard.
        User::factory()->create(['role' => 'admin']);

        $this->category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
    }

    // ── each app's own checkout ───────────────────────────────────────

    /** @return array<string, array{0: string, 1: string, 2: string, 3: string}> slug, checkout path, payment method, the page a browser goes back to */
    public static function appCheckouts(): array
    {
        return [
            'AutoTradeX' => ['autotradex', '/autotradex/checkout/monthly', 'bank_transfer', '/products/autotradex'],
            'Tping' => ['tping', '/tping/checkout/monthly', 'wallet', '/tping'],
            'SMS Checker' => ['smschecker', '/smschecker/checkout/monthly', 'wallet', '/smschecker'],
            'LocalVPN' => ['localvpn', '/localvpn/checkout/monthly', 'wallet', '/products/localvpn'],
        ];
    }

    /** @dataProvider appCheckouts */
    public function test_an_apps_own_checkout_refuses_a_coming_soon_product(string $slug, string $checkout, string $method, string $backTo): void
    {
        $this->product($slug, comingSoon: true);
        $buyer = $this->buyerWithMoney();
        $keys = LicenseKey::count(); // the migrations register a few keys of their own

        // The page that starts the checkout
        $this->actingAs($buyer)->get($checkout)
            ->assertRedirect(url($backTo))
            ->assertSessionHas('error', ComingSoonPurchase::MESSAGE);
        $this->actingAs($buyer)->getJson($checkout)
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('coming_soon', true);

        // A hand-made POST, filled in correctly, with the money in the wallet
        $this->actingAs($buyer)->post($checkout, $this->checkoutForm($method))
            ->assertRedirect(url($backTo))
            ->assertSessionHas('error', ComingSoonPurchase::MESSAGE);
        $this->actingAs($buyer)->postJson($checkout, $this->checkoutForm($method))
            ->assertForbidden()
            ->assertJsonPath('coming_soon', true);

        $this->assertSame(0, Order::count());
        $this->assertSame($keys, LicenseKey::count());
        $this->assertEquals(5000, (float) Wallet::where('user_id', $buyer->id)->value('balance'));

        // Coming Soon is the only thing in the way: switched off, the same page opens
        Product::where('slug', $slug)->update(['is_coming_soon' => false]);
        $this->actingAs($buyer)->get($checkout)->assertOk();
    }

    public function test_the_checkout_opens_again_by_itself_once_its_date_has_passed(): void
    {
        $product = $this->product('tping', comingSoon: true);
        $product->update(['coming_soon_until' => now()->addDay()]);
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->get('/tping/checkout/monthly')->assertRedirect(url('/tping'));

        $product->update(['coming_soon_until' => now()->subMinute()]);
        $this->actingAs($buyer)->get('/tping/checkout/monthly')->assertOk();
    }

    // ── the cart ───────────────────────────────────────────────────

    public function test_the_cart_refuses_a_coming_soon_product_with_json_an_app_can_read(): void
    {
        $product = $this->product('livexshop-pro', comingSoon: true, license: false);

        $this->postJson(route('cart.add', $product), ['quantity' => 1])
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('coming_soon', true)
            ->assertJsonPath('message', ComingSoonPurchase::MESSAGE);
        $this->post(route('cart.add', $product), ['quantity' => 1])
            ->assertRedirect(route('products.show', 'livexshop-pro'))
            ->assertSessionHas('error', ComingSoonPurchase::MESSAGE);

        $this->assertSame(0, CartItem::count());
    }

    public function test_a_product_that_turned_coming_soon_in_the_cart_cannot_be_checked_out(): void
    {
        $buyer = $this->buyerWithMoney();
        $later = $this->product('livexshop-pro', comingSoon: false, license: false);
        $onSale = $this->product('on-sale-tool', comingSoon: false, license: false);
        $keys = LicenseKey::count(); // the migrations register a few keys of their own

        $this->actingAs($buyer)->post(route('cart.add', $later), ['quantity' => 1])->assertSessionHas('success');
        $this->actingAs($buyer)->post(route('cart.add', $onSale), ['quantity' => 1])->assertSessionHas('success');
        $this->assertSame(2, CartItem::count());

        // The owner sets it to Coming Soon while it sits in the cart
        $later->update(['is_coming_soon' => true]);

        $this->actingAs($buyer)->get(route('orders.checkout'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, $later->name)
                && str_contains($message, 'Coming Soon')
                && str_contains($message, 'not on sale')
                && ! str_contains($message, $onSale->name));

        // The form posted anyway — by bank transfer, and from the wallet that could pay it
        foreach (['bank_transfer', 'wallet'] as $method) {
            $this->actingAs($buyer)->post(route('orders.store'), $this->checkoutForm($method))
                ->assertRedirect(route('cart.index'))
                ->assertSessionHas('error');
        }

        $this->assertSame(0, Order::count());
        $this->assertSame($keys, LicenseKey::count());
        $this->assertEquals(5000, (float) Wallet::where('user_id', $buyer->id)->value('balance'));
        // Nothing was taken out of the cart for the customer
        $this->assertSame(2, CartItem::count());

        // Taken out, the rest of the cart checks out
        $this->actingAs($buyer)->delete(route('cart.remove', CartItem::where('product_id', $later->id)->sole()));
        $this->actingAs($buyer)->get(route('orders.checkout'))->assertOk();
    }

    public function test_a_product_switched_off_while_in_the_cart_cannot_be_checked_out(): void
    {
        $buyer = $this->buyerWithMoney();
        $closed = $this->product('livexshop-pro', comingSoon: false, license: false);

        $this->actingAs($buyer)->post(route('cart.add', $closed), ['quantity' => 1])->assertSessionHas('success');

        // CartController::add refuses a product switched off in the admin; checkout must too
        $closed->update(['is_active' => false]);

        $this->actingAs($buyer)->get(route('orders.checkout'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, $closed->name));
        $this->actingAs($buyer)->post(route('orders.store'), $this->checkoutForm('wallet'))
            ->assertRedirect(route('cart.index'));

        $this->assertSame(0, Order::count());
        $this->assertEquals(5000, (float) Wallet::where('user_id', $buyer->id)->value('balance'));
    }

    // ── the DGX Spark bundle ─────────────────────────────────────────

    public function test_the_dgx_bundle_set_to_coming_soon_takes_no_order(): void
    {
        Product::create([
            'category_id' => $this->category->id,
            'name' => 'CluadeX',
            'slug' => 'cluadex-ai-coding-assistant',
            'description' => 'x',
            'price' => 1999,
            'requires_license' => true,
            'is_active' => true,
        ]);
        DgxSparkCampaign::product()->update(['is_coming_soon' => true]);

        $this->get('/dgx-spark')->assertOk()->assertSee('This product is not on sale yet');
        $this->actingAs(User::factory()->create())
            ->post(route('campaign.dgx-spark.order'), [
                'customer_name' => 'สมชาย ใจดี',
                'customer_email' => 'buyer@example.test',
                'customer_phone' => '081-234-5678',
                'shipping_address' => '99/1 ถ.สุขุมวิท แขวงคลองเตย เขตคลองเตย',
                'shipping_province' => 'กรุงเทพมหานคร',
                'shipping_postcode' => '10110',
                'payment_method' => 'bank_transfer',
                'expected_price' => DgxSparkCampaign::price(),
                'accept_terms' => '1',
            ])
            ->assertSessionHas('error', ComingSoonPurchase::MESSAGE);

        $this->assertSame(0, Order::count());
    }

    // ── the apps' API ───────────────────────────────────────────────

    public function test_the_pricing_api_offers_no_place_to_buy_a_coming_soon_product(): void
    {
        $this->product('postx-agent', comingSoon: true);

        $this->getJson('/api/v1/product/postx-agent/pricing')
            ->assertOk()
            ->assertJsonPath('data.purchase_url', null);

        Product::where('slug', 'postx-agent')->update(['is_coming_soon' => false]);
        $this->getJson('/api/v1/product/postx-agent/pricing')
            ->assertJsonPath('data.purchase_url', route('products.show', 'postx-agent'));
    }

    public function test_a_licence_bought_before_keeps_validating(): void
    {
        $product = $this->product('postx-agent', comingSoon: true);
        $machine = str_repeat('ab', 16);
        LicenseKey::create([
            'product_id' => $product->id,
            'license_key' => 'CS-POSTX-PAID-0001',
            'status' => 'active',
            'license_type' => 'lifetime',
            'machine_id' => $machine,
            'max_activations' => 1,
            'activations' => 1,
            'activated_at' => now()->subMonth(),
        ]);

        $this->postJson('/api/v1/product/postx-agent/validate', ['license_key' => 'CS-POSTX-PAID-0001', 'machine_id' => $machine])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_valid', true);
    }

    public function test_aipray_set_to_coming_soon_hands_out_no_model_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('aipray/models/v1.onnx', 'onnx');
        $model = AiprayAiModel::create(['name' => 'Aipray', 'version' => '1', 'onnx_file_path' => 'aipray/models/v1.onnx']);
        $url = URL::temporarySignedRoute('aipray.model.download', now()->addMinutes(5), ['model' => $model->id]);

        Product::where('slug', 'aipray')->firstOrFail()->update(['is_coming_soon' => true]);
        $this->getJson($url)->assertForbidden()->assertJsonPath('coming_soon', true);

        Product::where('slug', 'aipray')->update(['is_coming_soon' => false]);
        $this->get($url)->assertOk();
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function product(string $slug, bool $comingSoon, bool $license = true): Product
    {
        $product = Product::firstOrCreate(['slug' => $slug], [
            'category_id' => $this->category->id,
            'name' => str($slug)->headline()->toString(),
            'description' => 'x',
            'price' => 990,
            'requires_license' => $license,
            'is_active' => true,
        ]);
        $product->update(['is_active' => true, 'is_coming_soon' => $comingSoon, 'coming_soon_until' => null]);

        return $product->fresh();
    }

    private function buyerWithMoney(): User
    {
        $buyer = User::factory()->create();
        Wallet::getOrCreateForUser($buyer->id)->update(['balance' => 5000]);

        return $buyer;
    }

    private function checkoutForm(string $method): array
    {
        return [
            'customer_name' => 'สมชาย ใจดี',
            'customer_email' => 'buyer@example.test',
            'customer_phone' => '0812345678',
            'payment_method' => $method,
        ];
    }
}
