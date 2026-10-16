<?php

namespace Tests\Feature;

use App\Http\Controllers\TpixPayController;
use App\Models\Category;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\TpixPayment;
use App\Models\TpixPriceSnapshot;
use App\Models\User;
use App\Models\WalletTopup;
use App\Services\TpixPay\TpixMath;
use App\Services\TpixPay\TpixPaymentService;
use App\Services\TpixPay\TpixPaySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\FakesTpixChain;
use Tests\TestCase;

/**
 * TPIX on the site: offered at checkout with the live calculation, the pay page, the price tag on the
 * product pages, the app API, the admin page — and only the owner of a quote can see or use it.
 */
class TpixPayWebTest extends TestCase
{
    use FakesTpixChain;
    use RefreshDatabase;

    private Product $product;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Cache::flush();
        $this->withoutVite();
        $this->fakeTpixChain();
        $this->enableTpixPayments();
        $this->seedTwap();

        // Without an admin every page redirects to the setup wizard
        $this->admin = User::factory()->create(['role' => 'admin']);

        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Some Desktop App',
            'slug' => 'some-desktop-app',
            'description' => 'x',
            'price' => 1000,
            'stock' => 0,
            'requires_license' => true,
            'is_active' => true,
        ]);
    }

    // ── checkout → pay page → paid ───────────────────────────────────

    public function test_the_cart_checkout_offers_tpix_and_the_pay_page_takes_the_payment(): void
    {
        $buyer = User::factory()->create();
        $this->actingAs($buyer)->post(route('cart.add', $this->product), ['quantity' => 1]);

        // ฿1000 + 7% VAT = ฿1070 → 1070 / 3.25 = 329.2308 TPIX
        $this->actingAs($buyer)->get(route('orders.checkout'))
            ->assertOk()
            ->assertSee('เหรียญ TPIX')
            ->assertSee('329.23 TPIX')
            ->assertSee('ราคาจาก TPIX DEX');

        $response = $this->actingAs($buyer)->post(route('orders.store'), $this->checkoutForm('tpix'));

        $order = Order::sole();
        $payment = TpixPayment::sole();
        $response->assertRedirect(route('tpix-pay.show', $payment));
        $this->assertSame('tpix', $order->payment_method);
        $this->assertSame('1070.00', TpixMath::thb($payment->amount_thb));
        $this->assertTrue($payment->payable->is($order));

        $this->actingAs($buyer)->get(route('tpix-pay.show', $payment))
            ->assertOk()
            ->assertSee($payment->tpixDisplay())
            ->assertSee($this->shopAddress)
            ->assertSee('จ่ายด้วย MetaMask')
            ->assertSee('<svg', false);

        $hash = self::txHash('web-pay');
        $this->mineTransfer($hash, $payment->amount_wei, 990, now()->timestamp);

        $this->actingAs($buyer)->postJson(route('tpix-pay.submit', $payment), ['tx_hash' => $hash])
            ->assertOk()
            ->assertJsonPath('result', 'paid')
            ->assertJsonPath('settled', true)
            ->assertJsonPath('redirect', route('orders.show', $order));

        $this->assertSame('paid', $order->fresh()->payment_status);

        $this->actingAs($buyer)->getJson(route('tpix-pay.status', $payment))
            ->assertOk()
            ->assertJsonPath('status', 'paid');
    }

    public function test_the_checkout_refuses_tpix_while_it_is_switched_off(): void
    {
        $this->enableTpixPayments(enabled: false);
        $buyer = User::factory()->create();
        $this->actingAs($buyer)->post(route('cart.add', $this->product), ['quantity' => 1]);

        $this->actingAs($buyer)->get(route('orders.checkout'))->assertOk()->assertDontSee('value="tpix"', false);
        $this->actingAs($buyer)->post(route('orders.store'), $this->checkoutForm('tpix'))
            ->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Order::count());
    }

    public function test_only_the_owner_can_see_poll_or_submit_a_quote(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('tpix-pay.show', $payment))->assertForbidden();
        $this->actingAs($stranger)->getJson(route('tpix-pay.status', $payment))->assertForbidden();
        $this->actingAs($stranger)->postJson(route('tpix-pay.submit', $payment), ['tx_hash' => self::txHash('x')])->assertForbidden();
        $this->actingAs($stranger)->post(route('orders.tpix-quote', $order))->assertForbidden();

        // An admin may look (support), the owner may do everything
        $this->actingAs($this->admin)->get(route('tpix-pay.show', $payment))->assertOk();
        $this->actingAs($order->user)->get(route('tpix-pay.show', $payment))->assertOk();
    }

    public function test_a_bad_transaction_hash_is_answered_not_believed(): void
    {
        [$order, $payment] = $this->quotedOrder();

        $this->actingAs($order->user)->postJson(route('tpix-pay.submit', $payment), ['tx_hash' => 'not-a-hash'])
            ->assertOk()
            ->assertJsonPath('result', 'rejected')
            ->assertJsonPath('reason', 'bad_hash');

        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_an_expired_quote_can_be_quoted_again_at_the_current_price(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $payment->update(['expires_at' => now()->subMinute()]);

        $response = $this->actingAs($order->user)->post(route('orders.tpix-quote', $order));

        $new = TpixPayment::latest('id')->first();
        $this->assertNotSame($payment->id, $new->id);
        $response->assertRedirect(route('tpix-pay.show', $new));
        // The old quote keeps its amount reserved: a transfer made in time is still honoured
        $this->assertNotNull($payment->fresh()->open_key);

        // While a quote is still open, asking again just goes back to it
        $this->actingAs($order->user)->post(route('orders.tpix-quote', $order))
            ->assertRedirect(route('tpix-pay.show', $new));
    }

    public function test_the_order_page_links_a_tpix_order_to_its_pay_page(): void
    {
        [$order, $payment] = $this->quotedOrder();

        $this->actingAs($order->user)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee(route('tpix-pay.show', $payment))
            ->assertDontSee('แนบหลักฐานการชำระเงิน');
    }

    public function test_a_wallet_top_up_can_be_paid_in_tpix(): void
    {
        $buyer = User::factory()->create();

        $response = $this->actingAs($buyer)->post(route('user.wallet.submit-topup'), ['amount' => 500, 'payment_method' => 'tpix']);

        $topup = WalletTopup::sole();
        $payment = TpixPayment::sole();
        $response->assertRedirect(route('tpix-pay.show', $payment));
        $this->assertSame('tpix', $topup->payment_method);
        $this->assertSame(TpixPayment::PURPOSE_WALLET_TOPUP, $payment->purpose);
        $this->assertSame('500.00', TpixMath::thb($payment->amount_thb));

        $this->actingAs($buyer)->get(route('user.wallet.topup'))->assertOk()->assertSee('เหรียญ TPIX');
    }

    public function test_an_apps_own_checkout_sends_a_tpix_order_to_the_pay_page(): void
    {
        // The migrations register Tping already; make sure it is on sale
        Product::updateOrCreate(['slug' => 'tping'], [
            'category_id' => $this->product->category_id,
            'name' => 'Tping',
            'description' => 'x',
            'price' => 990,
            'requires_license' => true,
            'is_active' => true,
            'is_coming_soon' => false,
        ]);
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->get('/tping/checkout/monthly')->assertOk()->assertSee('value="tpix"', false);

        $response = $this->actingAs($buyer)->post('/tping/checkout/monthly', $this->checkoutForm('tpix'));

        $order = Order::sole();
        $payment = TpixPayment::sole();
        $response->assertRedirect(route('tpix-pay.show', $payment));
        $this->assertSame(route('tping.payment-success', $order->id), $payment->meta['return_url']);

        // The product's own payment page sends a TPIX order back to where it can be paid
        $this->actingAs($buyer)->get(route('tping.payment', $order))->assertRedirect(route('orders.show', $order));
    }

    /**
     * A TPIX payment is approved automatically through OrderPaymentService, so what the app's own
     * checkout put on the order must be enough to deliver the right key: the plan's term, the
     * plan's seats, and the device that started the purchase.
     *
     * @return array{0: Order, 1: LicenseKey}
     */
    private function payAppCheckoutInTpix(string $slug, string $path, string $machineId, string $hashSeed): array
    {
        // A deep pool, so a lifetime plan fits under the per-quote limit (same 0.10 USD price)
        $this->reserveTpixWei = '1999000000000000000000000';
        $this->reserveUsdtUnits = '199900000000';

        Product::updateOrCreate(['slug' => $slug], [
            'category_id' => $this->product->category_id,
            'name' => ucfirst($slug),
            'description' => 'x',
            'price' => 990,
            'requires_license' => true,
            'is_active' => true,
            'is_coming_soon' => false,
        ]);
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->post($path, $this->checkoutForm('tpix') + ['machine_id' => $machineId])
            ->assertRedirect(route('tpix-pay.show', TpixPayment::sole()));

        $payment = TpixPayment::sole();
        $hash = self::txHash($hashSeed);
        $this->mineTransfer($hash, $payment->amount_wei, 990, now()->timestamp);
        $this->actingAs($buyer)->postJson(route('tpix-pay.submit', $payment), ['tx_hash' => $hash])
            ->assertOk()->assertJsonPath('result', 'paid');

        $order = Order::sole();

        return [$order, LicenseKey::where('order_id', $order->id)->sole()];
    }

    public function test_tping_lifetime_paid_in_tpix_gets_a_lifetime_key_with_three_seats_on_the_buying_device(): void
    {
        [$order, $key] = $this->payAppCheckoutInTpix('tping', '/tping/checkout/lifetime', 'HWID-TPING-1', 'tping-life');

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('lifetime', $key->license_type);
        $this->assertNull($key->expires_at);
        $this->assertSame(3, (int) $key->max_activations);
        $this->assertSame('HWID-TPING-1', $key->machine_id);
    }

    public function test_autotradex_monthly_paid_in_tpix_gets_a_monthly_key_not_a_yearly_one(): void
    {
        [, $key] = $this->payAppCheckoutInTpix('autotradex', '/autotradex/checkout/monthly', 'HWID-ATX-1', 'atx-month');

        $this->assertSame('monthly', $key->license_type);
        $this->assertTrue($key->expires_at->between(now()->addDays(29), now()->addDays(31)));
        $this->assertSame('HWID-ATX-1', $key->machine_id);
    }

    public function test_the_pay_page_offers_to_open_itself_in_the_metamask_app(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $buyer = $order->user;

        $this->actingAs($buyer)->get(route('tpix-pay.show', $payment))
            ->assertOk()
            ->assertSee('เปิดในแอป MetaMask')
            ->assertSee('metamask.app.link', false);

        $request = Request::create(route('tpix-pay.show', $payment));
        $this->assertSame(
            'https://metamask.app.link/dapp/' . $request->getHttpHost() . '/pay/tpix/' . $payment->reference,
            TpixPayController::metamaskDeepLink($request),
        );
    }

    public function test_the_autotradex_pricing_api_carries_tpix_prices_and_says_whether_tpix_is_payable(): void
    {
        $this->getJson('/api/v1/autotradex/pricing')
            ->assertOk()
            ->assertJsonPath('data.tpix.chain_id', 4289)
            ->assertJsonPath('data.tpix.payments_enabled', true)
            ->assertJsonStructure(['data' => ['plans' => ['monthly' => ['price', 'price_tpix'], 'yearly' => ['price_tpix'], 'lifetime' => ['price_tpix']]]]);

        $this->enableTpixPayments(enabled: false);
        $this->app->forgetScopedInstances();
        $this->getJson('/api/v1/catalog')->assertOk()->assertJsonPath('tpix.payments_enabled', false);
    }

    // ── the price tag ────────────────────────────────────────────────

    public function test_product_pages_show_the_tpix_price_and_hide_it_without_a_trustworthy_price(): void
    {
        // ฿1000 / ฿3.25 = 307.6924
        $this->get(route('products.show', $this->product->slug))
            ->assertOk()
            ->assertSee('data-tpix-price', false)
            ->assertSee('≈ 307.69 TPIX')
            ->assertSee('1 TPIX = ฿3.2500');

        $this->get(route('products.index'))->assertOk()->assertSee('≈ 307.69 TPIX');

        // No USD→THB rate anywhere: no price tag (and nothing breaks)
        $this->fxDown = true;
        Cache::flush();
        TpixPriceSnapshot::query()->delete(); // and no rate fetched within the last day either
        $this->app->forgetScopedInstances(); // a new request (the price is memoised per request)
        $this->get(route('products.show', $this->product->slug))
            ->assertOk()
            ->assertDontSee('data-tpix-price', false);
    }

    public function test_the_price_tag_is_hidden_when_the_admin_turns_tpix_prices_off(): void
    {
        $this->enableTpixPayments(showPrices: false);

        $this->get(route('products.show', $this->product->slug))
            ->assertOk()
            ->assertDontSee('data-tpix-price', false);
    }

    public function test_tpix_prices_show_by_default_even_before_payments_are_switched_on(): void
    {
        // Payments off, and nobody has ever saved the price switch (as on production today)
        $this->enableTpixPayments(enabled: false);
        PaymentSetting::where('key', 'tpix_show_prices')->delete();
        app(TpixPaySettings::class)->forget();

        $this->get(route('products.show', $this->product->slug))
            ->assertOk()
            ->assertSee('data-tpix-price', false)
            ->assertSee('≈ 307.69 TPIX');

        $this->getJson('/api/v1/tpix/price')
            ->assertOk()
            ->assertJsonPath('data.prices_shown', true)
            ->assertJsonPath('data.payments_enabled', false)
            ->assertJsonPath('data.receive_address', null);

        // Showing a price does not mean taking payment: the checkout does not offer TPIX
        $buyer = User::factory()->create();
        $this->actingAs($buyer)->post(route('cart.add', $this->product), ['quantity' => 1]);
        $this->actingAs($buyer)->get(route('orders.checkout'))->assertOk()->assertDontSee('value="tpix"', false);
        $this->actingAs($buyer)->post(route('orders.store'), $this->checkoutForm('tpix'))->assertSessionHasErrors('payment_method');
    }

    public function test_the_admin_turns_the_tpix_prices_off_and_on(): void
    {
        $form = ['tpix_enabled' => '0', 'tpix_receive_address' => '', 'tpix_confirmations' => 3, 'tpix_quote_ttl_minutes' => 30, 'tpix_max_pool_ratio' => 5];

        $this->actingAs($this->admin)->get(route('admin.tpix-payments.index'))
            ->assertOk()
            ->assertSee('แสดงราคาเป็น TPIX บนหน้าสินค้าทุกหน้า');

        $this->actingAs($this->admin)->put(route('admin.tpix-payments.settings.update'), $form + ['tpix_show_prices' => '0'])
            ->assertSessionHasNoErrors();
        $this->assertFalse(app(TpixPaySettings::class)->showsPrices());
        $this->get(route('products.show', $this->product->slug))->assertOk()->assertDontSee('data-tpix-price', false);

        $this->actingAs($this->admin)->put(route('admin.tpix-payments.settings.update'), $form + ['tpix_show_prices' => '1'])
            ->assertSessionHasNoErrors();
        $settings = app(TpixPaySettings::class);
        $this->assertTrue($settings->showsPrices());
        $this->assertFalse($settings->acceptsPayments());
        $this->get(route('products.show', $this->product->slug))->assertOk()->assertSee('data-tpix-price', false);

        $this->actingAs($this->admin)->put(route('admin.tpix-payments.settings.update'), $form + ['tpix_show_prices' => 'maybe'])
            ->assertSessionHasErrors('tpix_show_prices');
    }

    public function test_a_fixed_tpix_price_is_shown_as_is_when_it_is_not_below_the_market(): void
    {
        $this->product->update(['price_tpix' => '400']);

        $this->get(route('products.show', $this->product->slug))
            ->assertOk()
            ->assertSee('400.00 TPIX')
            ->assertDontSee('≈ 400.00 TPIX')
            ->assertSee('ราคาคงที่');

        // Below the market (฿1000 ≈ 307.69 TPIX) the tag shows the market price instead
        $this->product->update(['price_tpix' => '250']);
        $this->get(route('products.show', $this->product->slug))
            ->assertOk()
            ->assertSee('≈ 307.69 TPIX')
            ->assertDontSee('250.00 TPIX');
    }

    public function test_a_checkout_larger_than_the_tpix_market_is_refused_before_any_order_exists(): void
    {
        $this->product->update(['price' => 5000]); // ฿5,350 with VAT > ฿3,248.37 (5 × 19.99 USDT × 32.5)
        $buyer = User::factory()->create();
        $this->actingAs($buyer)->post(route('cart.add', $this->product), ['quantity' => 1]);

        $this->actingAs($buyer)->post(route('orders.store'), $this->checkoutForm('tpix'))
            ->assertRedirect()
            ->assertSessionHas('error', 'ยอดนี้เกินสภาพคล่องของตลาด TPIX ตอนนี้ — ชำระด้วย TPIX ได้สูงสุด 3,248.37 บาทต่อรายการ');

        $this->assertSame(0, Order::count());
        $this->assertSame(0, TpixPayment::count());
    }

    public function test_the_price_tag_reads_the_chain_once_per_minute_not_once_per_tag(): void
    {
        foreach (range(1, 5) as $i) {
            Product::create([
                'category_id' => $this->product->category_id, 'name' => "App {$i}", 'slug' => "app-{$i}",
                'description' => 'x', 'price' => 100 * $i, 'is_active' => true,
            ]);
        }

        $this->get(route('products.index'))->assertOk();
        $this->get(route('products.index'))->assertOk();

        // One price read = getReserves + token0
        Http::assertSentCount(2 + 1); // + the FX feed
    }

    // ── apps ─────────────────────────────────────────────────────────

    public function test_the_app_api_gives_the_price_and_the_address_only_while_payments_are_on(): void
    {
        $this->getJson('/api/v1/tpix/price')
            ->assertOk()
            ->assertJsonPath('data.price_usd', '0.100000000000')
            ->assertJsonPath('data.price_thb', '3.250000000000')
            ->assertJsonPath('data.chain_id', 4289)
            ->assertJsonPath('data.receive_address', strtolower($this->shopAddress));

        $this->enableTpixPayments(enabled: false);
        $this->getJson('/api/v1/tpix/price')
            ->assertOk()
            ->assertJsonPath('data.prices_shown', true)
            ->assertJsonPath('data.payments_enabled', false)
            ->assertJsonPath('data.receive_address', null);

        $this->fxDown = true;
        Cache::flush();
        TpixPriceSnapshot::query()->delete();
        $this->app->forgetScopedInstances();
        $this->getJson('/api/v1/tpix/price')->assertStatus(503)->assertJsonPath('success', false);
    }

    public function test_the_catalog_and_pricing_apis_carry_tpix_prices(): void
    {
        $response = $this->getJson('/api/v1/catalog')->assertOk()->assertJsonPath('tpix.chain_id', 4289);
        $entry = collect($response->json('products'))->firstWhere('slug', $this->product->slug);
        $this->assertSame('307.6924', $entry['price_tpix']);
        $this->assertFalse($entry['price_tpix_is_fixed']);

        // A product sold by licence term: every term in TPIX too
        $plans = collect($response->json('products'))->first(fn ($p) => ! empty((array) $p['plans']));
        if ($plans) {
            $this->assertNotEmpty((array) $plans['plans_tpix']);
            $slug = $plans['slug'];
            $this->getJson("/api/v1/product/{$slug}/pricing")
                ->assertOk()
                ->assertJsonPath('data.tpix.chain_id', 4289)
                ->assertJsonStructure(['data' => ['plans' => ['*' => ['price', 'price_tpix']]]]);
        }
    }

    // ── admin ────────────────────────────────────────────────────────

    public function test_the_admin_sets_the_switch_and_a_valid_address(): void
    {
        $this->actingAs($this->admin)->get(route('admin.tpix-payments.index'))->assertOk()->assertSee('1 TPIX = ฿3.2500');

        $this->actingAs($this->admin)->put(route('admin.tpix-payments.settings.update'), [
            'tpix_enabled' => '1', 'tpix_receive_address' => '0x123', 'tpix_confirmations' => 3, 'tpix_quote_ttl_minutes' => 30, 'tpix_max_pool_ratio' => 5,
        ])->assertSessionHasErrors('tpix_receive_address');

        $checksummed = '0x52908400098527886E0F7030069857D2E4169EE7';
        $this->actingAs($this->admin)->put(route('admin.tpix-payments.settings.update'), [
            'tpix_enabled' => '1', 'tpix_show_prices' => '1', 'tpix_receive_address' => $checksummed, 'tpix_confirmations' => 5, 'tpix_quote_ttl_minutes' => 20, 'tpix_max_pool_ratio' => 2.5,
        ])->assertSessionHasNoErrors();

        $settings = app(TpixPaySettings::class);
        $settings->forget();
        $this->assertSame(strtolower($checksummed), $settings->receiveAddress());
        $this->assertSame(5, $settings->confirmations());
        $this->assertSame(20, $settings->quoteTtlMinutes());
        $this->assertSame(2.5, $settings->maxPoolRatio());

        $this->actingAs(User::factory()->create())->get(route('admin.tpix-payments.index'))->assertForbidden();
    }

    public function test_the_admin_accepts_a_late_transfer(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $hash = self::txHash('admin-late');
        $this->mineTransfer($hash, $payment->amount_wei, 990, $payment->expires_at->copy()->addMinutes(3)->timestamp);
        app(TpixPaymentService::class)->submitTx($payment, $hash);
        $this->assertSame('late', $payment->fresh()->status);

        $this->actingAs($this->admin)->post(route('admin.tpix-payments.accept-late', $payment))->assertSessionHas('success');

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function quotedOrder(): array
    {
        $buyer = User::factory()->create();
        $order = Order::create([
            'order_number' => 'TPIXWEB-' . $buyer->id,
            'user_id' => $buyer->id,
            'customer_name' => $buyer->name,
            'customer_email' => $buyer->email,
            'subtotal' => 1000,
            'total' => 1000,
            'payment_method' => 'tpix',
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
        $order->items()->create([
            'product_id' => $this->product->id, 'product_name' => $this->product->name,
            'price' => 1000, 'quantity' => 1, 'subtotal' => 1000,
        ]);

        $payment = app(TpixPaymentService::class)->createQuote(TpixPayment::PURPOSE_ORDER, $order, $buyer, '1000.00', null, [
            'return_url' => route('orders.show', $order),
        ]);

        return [$order, $payment];
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
