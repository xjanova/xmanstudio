<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmationMail;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\LicenseKey;
use App\Models\LicenseRenewal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\ThaiPaymentService;
use App\Services\ThemeService;
use App\Support\DgxSparkCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The DGX Spark bundle (/dgx-spark, owner's decision 2026-10-09): 20 sets of NVIDIA DGX Spark +
 * lifetime CluadeX + lifetime BrainX Cloud, priced at the JIB price + ฿20,000, paid by bank
 * transfer / PromptPay before the owner buys the unit.
 *
 * What these pin:
 *   — the 20-set cap counts paid orders and reservations still inside their hold, and nothing else
 *   — a reservation past its hold gives its set back; a late slip is taken only while a set is free
 *   — the order is VAT-inclusive, carries no coupon / wallet / card / commission, and its total is
 *     the price the customer saw (a price changed in between is refused, not charged)
 *   — approving the payment issues both licenses for life, and turns a BrainX key the buyer
 *     already pays monthly for into the lifetime one instead of opening an empty cloud account
 *   — the bundle can't be bought through the cart, even if someone switches the product on
 */
class DgxSparkCampaignTest extends TestCase
{
    use RefreshDatabase;

    private Product $cluadex;

    private Product $brainx;

    private int $orders = 0;

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

        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);

        $this->cluadex = Product::create([
            'category_id' => $category->id,
            'name' => 'CluadeX',
            'slug' => 'cluadex-ai-coding-assistant',
            'description' => 'x',
            'price' => 1999,
            'requires_license' => true,
            'is_active' => true,
        ]);

        // Registered by its own migration (2026_09_24_100001), as on production
        $this->brainx = Product::where('slug', 'brainx')->firstOrFail();
    }

    // ── the page ─────────────────────────────────────────────────────

    public function test_the_page_shows_the_price_its_jib_reference_and_the_real_count(): void
    {
        $this->get('/dgx-spark')
            ->assertOk()
            ->assertSee('฿265,000')
            ->assertSee('รวม VAT')
            ->assertSee('฿245,000')
            ->assertSee('9 ต.ค. 2569')
            ->assertSee('เหลือ <b>20</b> จาก 20 ชุด', false)
            ->assertSee('ผลทดสอบจากแหล่งอ้างอิง')
            ->assertSee('developer.nvidia.com/blog/how-nvidia-dgx-sparks-performance-enables-intensive-ai-tasks', false)
            ->assertSee('NVIDIA, DGX และ DGX Spark เป็นเครื่องหมายการค้าของ NVIDIA Corporation');
    }

    public function test_the_page_offers_ordering_only_from_us(): void
    {
        $html = $this->get('/dgx-spark')->assertOk()->getContent();

        // Owner's rules: no GitHub, no other store to buy from. JIB appears only as the price reference.
        $this->assertStringNotContainsStringIgnoringCase('github', $html);
        foreach (['lazada', 'shopee', 'amazon.', 'advice.co.th', 'bnn.in.th'] as $store) {
            $this->assertStringNotContainsStringIgnoringCase($store, $html);
        }
        $this->assertSame(1, substr_count($html, 'href="https://www.jib.co.th/'), 'JIB is linked once, as the citation');
        $this->assertStringContainsString('nofollow', $this->hrefTag($html, 'https://www.jib.co.th/'));
    }

    public function test_missing_media_falls_back_and_the_video_block_is_hidden(): void
    {
        // Point every media slot at a file that is not there (the real images ship with the page)
        foreach (array_keys(config('campaigns.dgx_spark.media')) as $slot) {
            config(["campaigns.dgx_spark.media.{$slot}" => "images/campaign/dgx-spark/missing-{$slot}.jpg"]);
        }

        $this->get('/dgx-spark')
            ->assertOk()
            ->assertDontSee('<video', false)
            ->assertSee('class="dgx-device"', false);
    }

    public function test_the_media_appear_once_their_files_are_there(): void
    {
        // The real video lives in storage on the server; here a stand-in next to the images
        config(['campaigns.dgx_spark.media.video' => 'images/campaign/dgx-spark/promo.mp4']);
        $dir = public_path('images/campaign/dgx-spark');
        $files = ['hero-16x9.jpg', 'square-1x1.jpg', 'story-9x16.jpg', 'promo.mp4', 'promo-poster.jpg'];
        $made = [];

        try {
            foreach ($files as $file) {
                if (! is_file("{$dir}/{$file}")) {
                    file_put_contents("{$dir}/{$file}", 'x');
                    $made[] = "{$dir}/{$file}";
                }
            }

            $this->get('/dgx-spark')
                ->assertOk()
                ->assertSee('<video', false)
                ->assertSee('/images/campaign/dgx-spark/promo.mp4?v=', false)
                ->assertSee('poster="' . url('/images/campaign/dgx-spark/promo-poster.jpg'), false)
                ->assertSee('/images/campaign/dgx-spark/square-1x1.jpg?v=', false)
                ->assertSee('--dgx-hero-img-tall', false)
                ->assertDontSee('class="dgx-device__box"', false);

            $this->classicHome()->assertSee('/images/campaign/dgx-spark/hero-16x9.jpg?v=', false);
        } finally {
            array_map('unlink', $made);
        }
    }

    public function test_the_page_shows_the_real_screens_the_service_promised_in_the_promo_and_the_chapters(): void
    {
        config(['campaigns.dgx_spark.media.video' => 'images/campaign/dgx-spark/promo.mp4']);
        $video = public_path('images/campaign/dgx-spark/promo.mp4');
        $made = ! is_file($video) && file_put_contents($video, 'x') !== false;

        try {
            $html = $this->get('/dgx-spark')->assertOk()->getContent();
        } finally {
            if ($made) {
                unlink($video);
            }
        }

        // Real screens (frames of the promo's recordings, shipped with the page)
        foreach (['screen-cluadex-app.webp', 'screen-universe.webp', 'screen-continue.webp', 'screen-cowork.webp'] as $file) {
            $this->assertStringContainsString("/images/campaign/dgx-spark/{$file}?v=", $html);
        }
        $this->assertStringContainsString('ต่องานเมื่อวาน', $html);

        // The promo's fine print sends viewers here for the service scope and conditions (p6)
        $this->assertStringContainsString('id="service"', $html);
        $this->assertStringContainsString('<h3>ประกันงานติดตั้ง + ซอฟต์แวร์ 1 ปีเต็ม</h3>', $html);
        $this->assertStringContainsString('บริการพิเศษตลอดอายุการใช้งาน', $html);
        $this->assertStringContainsString('<h3>Hot service 24 ชม.</h3>', $html);
        $this->assertStringContainsString('฿900', $html);
        $this->assertStringContainsString('ปกติ ฿3,000/เดือน', $html);
        $this->assertStringContainsString('ลด 70%', $html);

        // Chapters beside the video, and the product gallery is marked as illustration
        $this->assertStringContainsString('data-t="83"', $html);
        $this->assertStringContainsString('ภาพประกอบเพื่อการโฆษณา', $html);
    }

    public function test_the_showcase_rows_disappear_without_their_screens(): void
    {
        foreach (['screen_cluadex_app', 'screen_universe', 'screen_continue', 'screen_cowork'] as $slot) {
            config(["campaigns.dgx_spark.media.{$slot}" => "images/campaign/dgx-spark/missing-{$slot}.webp"]);
        }

        $this->get('/dgx-spark')->assertOk()->assertDontSee('id="real"', false)->assertSee('id="service"', false);
    }

    public function test_a_guest_is_asked_to_sign_in_and_cannot_post_an_order(): void
    {
        $this->get('/dgx-spark')->assertOk()->assertSee('เข้าสู่ระบบเพื่อสั่งจอง')->assertDontSee('name="shipping_address"', false);

        $this->get('/dgx-spark?signin=login')->assertRedirect(route('login'));
        $this->assertSame(route('campaign.dgx-spark') . '#order', session('url.intended'));

        $this->post(route('campaign.dgx-spark.order'), $this->form())->assertRedirect(route('login'));
        $this->assertSame(0, Order::count());
    }

    // ── ordering ─────────────────────────────────────────────────────

    public function test_a_customer_reserves_a_set_at_the_vat_inclusive_price(): void
    {
        $buyer = User::factory()->create();

        $response = $this->actingAs($buyer)->post(route('campaign.dgx-spark.order'), $this->form());

        $order = Order::sole();
        $response->assertRedirect(route('orders.show', $order));

        $this->assertStringStartsWith('DGX', $order->order_number);
        $this->assertSame($buyer->id, $order->user_id);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('bank_transfer', $order->payment_method);
        // ฿265,000 is what the customer pays; the order stores it the site's way: subtotal + VAT = total
        $this->assertEquals(265000.00, (float) $order->total);
        $this->assertEquals(247663.55, (float) $order->subtotal);
        $this->assertEquals(17336.45, (float) $order->tax);
        $this->assertEquals(0, (float) $order->discount);
        // None of the cart's extras that would eat into the margin
        $this->assertNull($order->affiliate_id);
        $this->assertNull($order->coupon_id);
        $this->assertNull($order->unique_payment_amount_id);
        $this->assertStringContainsString('10110', $order->customer_address);
        $this->assertSame(DgxSparkCampaign::KEY, $order->metadata['campaign']);
        $this->assertSame(245000, $order->metadata['price']['reference_price']);

        $items = $order->items()->get()->keyBy('product_id');
        $this->assertCount(3, $items);
        $this->assertEquals(247663.55, (float) $items[DgxSparkCampaign::product()->id]->price);
        foreach ([$this->cluadex, $this->brainx] as $licensed) {
            $line = $items[$licensed->id];
            $this->assertEquals(0, (float) $line->price);
            $this->assertSame('lifetime', json_decode($line->custom_requirements, true)['license_type']);
        }

        $this->assertSame(19, DgxSparkCampaign::availability()['remaining']);
        Mail::assertSent(OrderConfirmationMail::class, fn ($mail) => $mail->hasTo('buyer@example.test'));

        // The order page shows the deadline and the shipping address
        $this->actingAs($buyer)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('โอนเงินและแนบสลิปภายใน')
            ->assertSee('ถ.สุขุมวิท');
    }

    public function test_the_21st_set_is_refused(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->reservation();
        }

        $this->get('/dgx-spark')->assertOk()->assertSee('เหลือ <b>0</b> จาก 20 ชุด', false)->assertSee('ถูกจองครบแล้ว');

        $this->actingAs(User::factory()->create())
            ->post(route('campaign.dgx-spark.order'), $this->form())
            ->assertRedirect(route('campaign.dgx-spark') . '#order')
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'จองครบ'));

        $this->assertSame(20, Order::count());
    }

    public function test_paid_sold_out_reads_as_sold_out(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->reservation(payment: 'paid', status: 'completed');
        }

        $this->assertSame(['cap' => 20, 'sold' => 20, 'reserved' => 0, 'remaining' => 0], DgxSparkCampaign::availability());
        $this->get('/dgx-spark')->assertOk()->assertSee('จำหน่ายครบ 20 ชุดแล้ว');
    }

    public function test_a_reservation_past_its_hold_gives_its_set_back(): void
    {
        $stale = $this->reservation(createdHoursAgo: 49);
        $fresh = $this->reservation(createdHoursAgo: 47);
        // A slip under review holds its set however old the reservation is
        $verifying = $this->reservation(payment: 'verifying', createdHoursAgo: 72);

        $this->assertSame(['cap' => 20, 'sold' => 0, 'reserved' => 2, 'remaining' => 18], DgxSparkCampaign::availability());

        // The next look at the page cancels it, so the admin lists stop showing it as waiting
        $this->get('/dgx-spark')->assertOk();
        $this->assertSame('cancelled', $stale->fresh()->status);
        $this->assertSame('expired', $stale->fresh()->payment_status);
        $this->assertSame('pending', $fresh->fresh()->payment_status);
        $this->assertSame('verifying', $verifying->fresh()->payment_status);
    }

    public function test_rejected_and_cancelled_orders_hold_nothing(): void
    {
        $this->reservation(payment: 'rejected', status: 'cancelled');
        $this->reservation(payment: 'paid', status: 'cancelled'); // refunded by the owner
        $this->reservation(payment: 'failed', status: 'pending'); // a payment refused without touching the order status
        $this->reservation(payment: 'paid', status: 'completed');

        $this->assertSame(['cap' => 20, 'sold' => 1, 'reserved' => 0, 'remaining' => 19], DgxSparkCampaign::availability());
    }

    public function test_a_second_press_lands_on_the_open_reservation(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->post(route('campaign.dgx-spark.order'), $this->form());
        $this->actingAs($buyer)->post(route('campaign.dgx-spark.order'), $this->form())
            ->assertRedirect(route('orders.show', Order::sole()))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'มีการจอง'));

        $this->assertSame(1, Order::count());
        Mail::assertSent(OrderConfirmationMail::class, 1);
    }

    public function test_a_price_changed_since_the_page_was_read_is_not_charged(): void
    {
        Setting::setValue(DgxSparkCampaign::SETTING_REFERENCE_PRICE, 250000, 'integer');

        $this->actingAs(User::factory()->create())
            ->post(route('campaign.dgx-spark.order'), $this->form(['expected_price' => 265000]))
            ->assertSessionHas('error', fn ($message) => str_contains($message, '฿270,000'));

        $this->assertSame(0, Order::count());
    }

    public function test_card_wallet_and_stripe_are_not_accepted(): void
    {
        // Switched on for the site's cart: the bundle must still refuse them (a card fee on
        // ฿265,000 is ~฿8,000 of the ฿20,000 margin)
        PaymentSetting::set('card_payment_enabled', '1', ['type' => 'boolean', 'label' => 'card']);
        PaymentSetting::set('stripe_enabled', '1', ['type' => 'boolean', 'label' => 'stripe']);
        $active = collect(app(ThaiPaymentService::class)->getSupportedMethods())->where('is_active', true)->pluck('id');
        $this->assertTrue($active->contains('stripe') && $active->contains('credit_card'));

        $buyer = User::factory()->create();

        $this->get('/dgx-spark')->assertDontSee('value="stripe"', false)->assertDontSee('value="credit_card"', false);

        foreach (['credit_card', 'wallet', 'stripe'] as $method) {
            $this->actingAs($buyer)
                ->post(route('campaign.dgx-spark.order'), $this->form(['payment_method' => $method]))
                ->assertSessionHasErrors('payment_method');
        }

        $this->assertSame(0, Order::count());
    }

    public function test_the_form_needs_an_address_a_postcode_and_the_terms(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('campaign.dgx-spark.order'), $this->form([
                'shipping_address' => '',
                'shipping_postcode' => '1011',
                'accept_terms' => null,
            ]))
            ->assertSessionHasErrors(['shipping_address', 'shipping_postcode', 'accept_terms']);

        $this->assertSame(0, Order::count());
    }

    public function test_switched_off_the_campaign_takes_no_order_and_leaves_the_home_page(): void
    {
        Setting::setValue(DgxSparkCampaign::SETTING_ENABLED, '0', 'boolean');

        $this->get('/dgx-spark')->assertOk()->assertSee('ปิดรับคำสั่งซื้อ');
        $this->actingAs(User::factory()->create())
            ->post(route('campaign.dgx-spark.order'), $this->form())
            ->assertSessionHas('error');
        $this->assertSame(0, Order::count());

        $this->classicHome()->assertDontSee('href="' . route('campaign.dgx-spark') . '"', false);
    }

    public function test_without_the_cluadex_product_no_order_is_taken(): void
    {
        $this->cluadex->delete();

        $this->actingAs(User::factory()->create())
            ->post(route('campaign.dgx-spark.order'), $this->form())
            ->assertSessionHas('error');

        $this->assertSame(0, Order::count());
    }

    // ── payment → licenses ───────────────────────────────────────────

    public function test_approving_the_payment_issues_both_licenses_for_life(): void
    {
        $buyer = User::factory()->create();
        $this->actingAs($buyer)->post(route('campaign.dgx-spark.order'), $this->form());
        $order = Order::sole();

        $this->approve($order);
        $this->approve($order); // a second click — or the Telegram button after the web page

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('completed', $order->status);

        $keys = LicenseKey::where('order_id', $order->id)->get();
        $this->assertCount(2, $keys);
        foreach ([$this->cluadex, $this->brainx] as $licensed) {
            $key = $keys->firstWhere('product_id', $licensed->id);
            $this->assertNotNull($key, "{$licensed->slug} key issued");
            $this->assertSame('lifetime', $key->license_type);
            $this->assertNull($key->expires_at);
            $this->assertSame($buyer->id, $key->user_id);
            $this->assertTrue($key->isValid());
        }
        $this->assertSame(['cap' => 20, 'sold' => 1, 'reserved' => 0, 'remaining' => 19], DgxSparkCampaign::availability());

        // The receipt (subtotal + VAT = ฿265,000) is there once the order is paid
        $this->actingAs($buyer)->get(route('orders.receipt', $order))->assertOk();

        // BrainX Cloud's server asks this endpoint about the key
        $brainxKey = $keys->firstWhere('product_id', $this->brainx->id);
        $this->getJson("/api/v1/product/brainx/status/{$brainxKey->license_key}")
            ->assertOk()
            ->assertJsonPath('data.is_valid', true)
            ->assertJsonPath('data.license_type', 'lifetime');
    }

    public function test_a_brainx_key_already_paid_monthly_becomes_the_lifetime_one(): void
    {
        $buyer = User::factory()->create();
        $monthly = LicenseKey::create([
            'product_id' => $this->brainx->id,
            'user_id' => $buyer->id,
            'license_key' => 'BXCL-AAAA-BBBB-CCCC',
            'license_type' => 'monthly',
            'status' => 'active',
            'expires_at' => now()->addDays(12),
            'max_activations' => 1,
            'activations' => 0,
        ]);

        $this->actingAs($buyer)->post(route('campaign.dgx-spark.order'), $this->form());
        $order = Order::sole();
        $this->approve($order);
        $this->approve($order);

        $monthly->refresh();
        $this->assertSame('lifetime', $monthly->license_type);
        $this->assertNull($monthly->expires_at);
        // The same key — the cloud account is a hash of it — and no second BrainX key
        $this->assertSame(1, LicenseKey::where('product_id', $this->brainx->id)->count());
        $this->assertSame(1, LicenseRenewal::where('order_id', $order->id)->count());
        $this->assertSame(1, LicenseKey::where('order_id', $order->id)->where('product_id', $this->cluadex->id)->count());

        // The order page and the key download both find the upgraded key
        $this->actingAs($buyer)->get(route('orders.show', $order))->assertSee('BXCL-AAAA-BBBB-CCCC');
        $this->assertStringContainsString('BXCL-AAAA-BBBB-CCCC', $this->actingAs($buyer)->get(route('orders.download', $order))->getContent());
    }

    // ── slips ────────────────────────────────────────────────────────

    public function test_a_late_slip_is_taken_while_a_set_is_free(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create();
        $order = $this->reservation(buyer: $buyer, createdHoursAgo: 50);

        $this->actingAs($buyer)
            ->post(route('orders.confirm-payment', $order), ['payment_slip' => UploadedFile::fake()->image('slip.jpg')])
            ->assertSessionHas('success');

        $this->assertSame('verifying', $order->fresh()->payment_status);
        $this->assertSame(1, DgxSparkCampaign::availability()['reserved']);
    }

    public function test_a_late_slip_is_refused_once_the_sets_went_to_others(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create();
        $order = $this->reservation(buyer: $buyer, createdHoursAgo: 50);
        for ($i = 0; $i < 20; $i++) {
            $this->reservation(payment: 'paid', status: 'completed');
        }

        $this->actingAs($buyer)
            ->post(route('orders.confirm-payment', $order), ['payment_slip' => UploadedFile::fake()->image('slip.jpg')])
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'คืนเงินเต็มจำนวน'));

        $this->assertSame('expired', $order->fresh()->payment_status);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertNull($order->fresh()->payment_slip);
    }

    public function test_nobody_can_put_a_slip_on_someone_elses_order(): void
    {
        Storage::fake('public');
        $order = $this->reservation();

        $this->actingAs(User::factory()->create())
            ->post(route('orders.confirm-payment', $order), ['payment_slip' => UploadedFile::fake()->image('slip.jpg')])
            ->assertForbidden();

        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    // ── the cart is not a way around any of this ─────────────────────

    public function test_the_cart_refuses_the_bundle_even_when_its_product_is_switched_on(): void
    {
        $bundle = DgxSparkCampaign::product();
        $this->assertFalse($bundle->is_active, 'registered off the shop');

        $this->post(route('cart.add', $bundle))->assertNotFound();

        $bundle->update(['is_active' => true]);
        $this->post(route('cart.add', $bundle))->assertRedirect(route('campaign.dgx-spark'));
        $this->assertSame(0, CartItem::count());

        $this->get('/products/' . $bundle->slug)->assertRedirect(route('campaign.dgx-spark'));
    }

    // ── admin ────────────────────────────────────────────────────────

    public function test_the_admin_moves_the_price_with_jib_and_the_page_follows(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put(route('admin.campaigns.dgx-spark.update'), [
            'enabled' => '1',
            'reference_price' => 250000,
            'reference_checked_at' => '2026-10-08',
            'markup' => 20000,
        ])->assertSessionHas('success');

        $this->assertSame(270000, DgxSparkCampaign::price());
        $this->get('/dgx-spark')->assertSee('฿270,000')->assertSee('฿250,000')->assertSee('8 ต.ค. 2569');

        // Selling under the reference price is refused
        $this->actingAs($admin)->put(route('admin.campaigns.dgx-spark.update'), [
            'enabled' => '1',
            'reference_price' => 250000,
            'reference_checked_at' => '2026-10-08',
            'markup' => -1,
        ])->assertSessionHasErrors('markup');
        $this->assertSame(270000, DgxSparkCampaign::price());

        $this->actingAs(User::factory()->create())->get(route('admin.campaigns.dgx-spark'))->assertForbidden();
    }

    public function test_the_unit_is_marked_ordered_only_after_the_money_cleared(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create();
        $order = $this->reservation(buyer: $buyer);

        $this->actingAs($admin)
            ->put(route('admin.campaigns.dgx-spark.fulfillment', $order), ['status' => 'ordered'])
            ->assertSessionHas('error');
        $this->assertSame('awaiting_payment', DgxSparkCampaign::fulfillment($order->fresh())['status']);

        $this->approve($order);
        $this->actingAs($admin)
            ->put(route('admin.campaigns.dgx-spark.fulfillment', $order), ['status' => 'shipped', 'tracking' => 'TH0123456789'])
            ->assertSessionHas('success');

        $this->assertSame('shipped', DgxSparkCampaign::fulfillment($order->fresh())['status']);
        $this->actingAs($admin)->get(route('admin.campaigns.dgx-spark'))->assertOk()->assertSee('TH0123456789');
        $this->actingAs($buyer)->get(route('orders.show', $order))->assertSee('TH0123456789')->assertSee('จัดส่งแล้ว');
        $this->actingAs($buyer)->get(route('customer.orders.show', $order))->assertOk()->assertSee('TH0123456789');
    }

    // ── home page ────────────────────────────────────────────────────

    public function test_the_home_page_leads_with_the_campaign(): void
    {
        $link = 'href="' . route('campaign.dgx-spark') . '"';

        $this->classicHome()
            ->assertOk()
            ->assertSee($link, false)
            ->assertSee('เหลือ <b>20</b> จาก 20 ชุด', false)
            // the rest of the home page is still there
            ->assertSee('nova-hero', false);

        // Every theme's own home page carries the banner
        foreach (['classic', 'premium', 'retro'] as $theme) {
            ThemeService::setTheme($theme);
            Cache::forget('site_theme');
            $this->get('/?view=classic')->assertOk()->assertSee($link, false);
        }

        // The 3D home: the promo slot in the HUD and a link on the first stop
        ThemeService::setTheme('nova');
        Cache::forget('site_theme');
        $universe = $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36')
            ->get('/')
            ->assertOk()
            ->assertSee('id="xu-config"', false)
            ->getContent();
        $this->assertSame(2, substr_count($universe, $link));
        $this->assertStringContainsString('xu-promo--campaign', $universe);
    }

    // ── helpers ──────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function form(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'สมชาย ใจดี',
            'customer_email' => 'buyer@example.test',
            'customer_phone' => '081-234-5678',
            'shipping_address' => '99/1 ถ.สุขุมวิท แขวงคลองเตย เขตคลองเตย',
            'shipping_province' => 'กรุงเทพมหานคร',
            'shipping_postcode' => '10110',
            'payment_method' => 'bank_transfer',
            'expected_price' => 265000,
            'accept_terms' => '1',
        ], $overrides);
    }

    /** An order of the campaign, made directly (the way the controller stores one). */
    private function reservation(string $payment = 'pending', string $status = 'pending', ?User $buyer = null, int $createdHoursAgo = 0): Order
    {
        $buyer ??= User::factory()->create();
        $totals = DgxSparkCampaign::totals(265000);

        $order = Order::create([
            'user_id' => $buyer->id,
            'order_number' => 'DGXTEST-' . (++$this->orders),
            'customer_name' => $buyer->name,
            'customer_email' => $buyer->email,
            'customer_phone' => '0800000000',
            'customer_address' => "1 ถนนทดสอบ\nกรุงเทพมหานคร 10110",
            'subtotal' => $totals['subtotal'],
            'tax' => $totals['tax'],
            'total' => $totals['total'],
            'payment_method' => 'bank_transfer',
            'payment_status' => $payment,
            'status' => $status,
            'paid_at' => $payment === 'paid' ? now() : null,
            'metadata' => ['campaign' => DgxSparkCampaign::KEY],
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => DgxSparkCampaign::product()->id,
            'product_name' => 'DGX Spark bundle',
            'price' => $totals['subtotal'],
            'quantity' => 1,
            'subtotal' => $totals['subtotal'],
        ]);

        foreach ([$this->cluadex, $this->brainx] as $licensed) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $licensed->id,
                'product_name' => $licensed->name,
                'price' => 0,
                'quantity' => 1,
                'subtotal' => 0,
                'custom_requirements' => json_encode(['license_type' => 'lifetime', 'bundle' => DgxSparkCampaign::KEY]),
            ]);
        }

        if ($createdHoursAgo > 0) {
            Order::whereKey($order->id)->update(['created_at' => now()->subHours($createdHoursAgo)]);
            $order->refresh();
        }

        return $order;
    }

    private function approve(Order $order): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.orders.update-payment-status', $order), ['payment_status' => 'paid'])
            ->assertSessionHas('success');
    }

    private function classicHome()
    {
        ThemeService::setTheme('nova');
        Cache::forget('site_theme');

        return $this->get('/?view=classic');
    }

    private function hrefTag(string $html, string $hrefStart): string
    {
        preg_match('#<a[^>]+href="' . preg_quote($hrefStart, '#') . '[^>]*>#', $html, $m);

        return $m[0] ?? '';
    }
}
