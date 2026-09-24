<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\SmsPaymentController;
use App\Mail\PaymentConfirmedMail;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\LicenseActivity;
use App\Models\LicenseKey;
use App\Models\LicenseRenewal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * BrainX Cloud is sold as product `brainx`: a monthly licence at ฿399, where buying again
 * EXTENDS the key the customer already holds.
 *
 * The cloud account is the key — its id is a hash of the key — so a second key would be a
 * second, empty account. The BrainX cloud server asks GET /api/v1/product/brainx/status/{key}
 * whether a key is still good, and the BrainX app links to the product page to buy and renew.
 */
class BrainXCloudLicenseTest extends TestCase
{
    use RefreshDatabase;

    private Product $brainx;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->withoutVite();
        $this->freezeSecond();

        // Created by the 2026_09_24_100001 migration, exactly as production gets it on deploy
        $this->brainx = Product::where('slug', 'brainx')->firstOrFail();
    }

    // ── the product ────────────────────────────────────────────────────

    public function test_the_migration_creates_brainx_cloud_as_a_monthly_licensed_product(): void
    {
        $this->assertSame('BrainX Cloud', $this->brainx->name);
        $this->assertTrue($this->brainx->requires_license);
        $this->assertTrue($this->brainx->is_active);
        $this->assertEquals(399, (float) $this->brainx->price);
        $this->assertSame('cloud-computing', $this->brainx->category->slug);
        $this->assertSame(LicenseKey::TYPE_MONTHLY, $this->brainx->defaultLicenseType());
        $this->assertTrue($this->brainx->isRenewable());
        $this->assertNotEmpty($this->brainx->short_description);
    }

    public function test_the_product_migration_can_run_again_without_touching_what_the_admin_changed(): void
    {
        $this->brainx->update(['price' => 450, 'name' => 'BrainX Cloud (edited)']);

        $migration = require database_path('migrations/2026_09_24_100001_register_brainx_cloud_product.php');
        $migration->up();

        $this->assertSame(1, Product::where('slug', 'brainx')->count());
        $this->assertEquals(450, (float) $this->brainx->fresh()->price);
        $this->assertSame('BrainX Cloud (edited)', $this->brainx->fresh()->name);
    }

    public function test_pricing_offers_only_the_monthly_plan_at_399_with_the_page_to_buy_it(): void
    {
        $this->getJson('/api/v1/product/brainx/pricing')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.product.slug', 'brainx')
            ->assertJsonPath('data.plans.monthly.price', 399)
            ->assertJsonPath('data.plans.monthly.currency', 'THB')
            ->assertJsonPath('data.plans.monthly.duration_days', 30)
            ->assertJsonPath('data.purchase_url', url('/products/brainx'))
            ->assertJsonCount(1, 'data.plans');

        $this->assertContains(
            'cloud_sync',
            $this->getJson('/api/v1/product/brainx/pricing')->json('data.plans.monthly.features')
        );
    }

    public function test_the_product_page_sells_the_monthly_licence(): void
    {
        $this->assertSame(url('/products/brainx'), route('products.show', 'brainx'));

        $this->get('/products/brainx')
            ->assertOk()
            ->assertSee('BrainX')
            ->assertSee('Remote MCP')
            ->assertSee('฿399', false)
            ->assertSee('action="' . route('cart.add', $this->brainx) . '"', false)
            ->assertSee('name="license_type" value="monthly"', false)
            ->assertDontSee('name="renew_license"', false);
    }

    public function test_the_product_page_offers_renewal_of_the_key_the_customer_holds(): void
    {
        $user = User::factory()->create();
        $key = $this->firstPurchase($user);

        // The key shows masked — the page can be on a shared screen; the full key is in My Account
        $this->actingAs($user)->get('/products/brainx')
            ->assertOk()
            ->assertSee('name="renew_license" value="' . $key->id . '"', false)
            ->assertSee(substr($key->license_key, 0, 4) . '-••••-••••-' . substr($key->license_key, -4))
            ->assertDontSee($key->license_key);
    }

    // ── the cart ───────────────────────────────────────────────────────

    public function test_the_cart_charges_399_for_a_month_whatever_the_form_says(): void
    {
        $this->post(route('cart.add', $this->brainx), ['license_type' => 'monthly', 'price' => 1, 'buy_now' => 1])
            ->assertRedirect(route('cart.index'));

        $item = CartItem::sole();
        $this->assertEquals(399, (float) $item->price);
        $this->assertSame('monthly', json_decode($item->custom_requirements, true)['license_type']);
    }

    public function test_the_cart_refuses_a_term_brainx_is_not_sold_by(): void
    {
        foreach (['yearly', 'lifetime'] as $term) {
            $this->postJson(route('cart.add', $this->brainx), ['license_type' => $term])->assertStatus(422);
        }

        $this->assertSame(0, CartItem::count());
    }

    public function test_renew_remembers_the_customers_own_key_and_ignores_anyone_elses(): void
    {
        $owner = User::factory()->create();
        $key = $this->firstPurchase($owner);
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->post(route('cart.add', $this->brainx), ['license_type' => 'monthly', 'renew_license' => $key->id]);
        $this->assertArrayNotHasKey('renew_license_id', json_decode(CartItem::sole()->custom_requirements, true));

        CartItem::query()->delete();

        $this->actingAs($owner)
            ->post(route('cart.add', $this->brainx), ['license_type' => 'monthly', 'renew_license' => $key->id]);
        $this->assertSame($key->id, json_decode(CartItem::sole()->custom_requirements, true)['renew_license_id']);
    }

    // ── the status endpoint the cloud server calls ────────────────────

    public function test_status_says_a_running_brainx_key_is_valid(): void
    {
        $key = $this->firstPurchase(User::factory()->create());

        $this->getJson("/api/v1/product/brainx/status/{$key->license_key}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.license_key', $key->license_key)
            ->assertJsonPath('data.license_type', 'monthly')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.is_valid', true)
            ->assertJsonPath('data.is_expired', false)
            ->assertJsonPath('data.days_remaining', 30)
            ->assertJsonPath('data.expires_at', now()->addDays(30)->toISOString());
    }

    public function test_status_says_a_lapsed_brainx_key_is_not_valid(): void
    {
        $key = $this->firstPurchase(User::factory()->create());
        $key->update(['expires_at' => now()->subDay()]);

        $this->getJson('/api/v1/product/brainx/status/' . strtolower($key->license_key))
            ->assertOk()
            ->assertJsonPath('data.is_valid', false)
            ->assertJsonPath('data.is_expired', true)
            ->assertJsonPath('data.days_remaining', 0);
    }

    public function test_status_only_answers_for_brainx_keys(): void
    {
        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $other = Product::create([
            'category_id' => $category->id, 'name' => 'Other app', 'slug' => 'other-app',
            'description' => 'x', 'price' => 100, 'requires_license' => true, 'is_active' => true,
        ]);
        $otherKey = LicenseKey::create([
            'product_id' => $other->id, 'license_key' => LicenseKey::generateKey(), 'license_type' => 'yearly',
            'status' => 'active', 'expires_at' => now()->addYear(),
        ]);

        foreach ([$otherKey->license_key, 'XXXX-XXXX-XXXX-XXXX'] as $unknown) {
            $this->getJson("/api/v1/product/brainx/status/{$unknown}")
                ->assertNotFound()
                ->assertJsonPath('error_code', 'INVALID_LICENSE');
        }
    }

    // ── buying: first purchase ────────────────────────────────────────

    public function test_a_first_purchase_issues_one_monthly_key_and_mails_it(): void
    {
        $user = User::factory()->create();
        $order = $this->paidOrder($user);

        app(LicenseService::class)->generateLicensesForOrder($order);

        $key = LicenseKey::where('product_id', $this->brainx->id)->sole();
        $this->assertSame('monthly', $key->license_type);
        $this->assertSame($user->id, $key->user_id);
        $this->assertSame($order->id, $key->order_id);
        $this->assertTrue($key->expires_at->equalTo(now()->addDays(30)));
        $this->assertSame('completed', $order->fresh()->status);
        Mail::assertSent(PaymentConfirmedMail::class, function ($mail) use ($key) {
            $html = $mail->render();

            // ...and the line's own price: the template read a `total` order_items does not have
            return str_contains($html, $key->license_key)
                && str_contains($html, 'text-align: right;">฿399.00</td>');
        });
    }

    public function test_two_months_bought_at_once_are_one_key_for_sixty_days(): void
    {
        app(LicenseService::class)->generateLicensesForOrder($this->paidOrder(User::factory()->create(), 2));

        $key = LicenseKey::where('product_id', $this->brainx->id)->sole();
        $this->assertTrue($key->expires_at->equalTo(now()->addDays(60)));
    }

    public function test_a_first_purchase_confirmed_twice_issues_one_key(): void
    {
        $order = $this->paidOrder(User::factory()->create());

        app(LicenseService::class)->generateLicensesForOrder($order);
        app(LicenseService::class)->generateLicensesForOrder($order);

        $this->assertSame(1, LicenseKey::where('product_id', $this->brainx->id)->count());
        Mail::assertSent(PaymentConfirmedMail::class, 1);
    }

    // ── buying again: renewal ─────────────────────────────────────────

    public function test_buying_again_extends_the_same_key_from_its_expiry(): void
    {
        $user = User::factory()->create();
        $key = $this->firstPurchase($user);
        $key->update(['expires_at' => now()->addDays(10)]);

        $renewal = $this->paidOrder($user);
        app(LicenseService::class)->generateLicensesForOrder($renewal);

        $this->assertSame(1, LicenseKey::where('product_id', $this->brainx->id)->count(), 'no second key');
        $key->refresh();
        $this->assertTrue($key->expires_at->equalTo(now()->addDays(40)));
        $this->assertSame('active', $key->status);

        $row = LicenseRenewal::sole();
        $this->assertSame($key->id, $row->license_key_id);
        $this->assertSame($renewal->id, $row->order_id);
        $this->assertSame(30, $row->days_added);
        $this->assertTrue($row->previous_expires_at->equalTo(now()->addDays(10)));
        $this->assertSame('completed', $renewal->fresh()->status);
        $this->assertTrue(LicenseActivity::where('license_id', $key->id)->where('action', 'extended')->exists());

        $this->getJson("/api/v1/product/brainx/status/{$key->license_key}")
            ->assertJsonPath('data.days_remaining', 40)
            ->assertJsonPath('data.is_valid', true);
    }

    public function test_renewing_a_lapsed_key_counts_from_the_day_it_is_paid(): void
    {
        $user = User::factory()->create();
        $key = $this->firstPurchase($user);
        $key->update(['expires_at' => now()->subDays(5), 'status' => LicenseKey::STATUS_EXPIRED]);

        app(LicenseService::class)->generateLicensesForOrder($this->paidOrder($user));

        $key->refresh();
        $this->assertTrue($key->expires_at->equalTo(now()->addDays(30)));
        $this->assertSame('active', $key->status);
        $this->assertTrue($key->isValid());
        $this->assertSame(1, LicenseKey::where('product_id', $this->brainx->id)->count());
    }

    public function test_two_months_in_a_renewal_add_sixty_days(): void
    {
        $user = User::factory()->create();
        $key = $this->firstPurchase($user);
        $key->update(['expires_at' => now()->addDays(3)]);

        app(LicenseService::class)->generateLicensesForOrder($this->paidOrder($user, 2));

        $this->assertTrue($key->fresh()->expires_at->equalTo(now()->addDays(63)));
        $this->assertSame(2, LicenseRenewal::sole()->units);
        $this->assertSame(60, LicenseRenewal::sole()->days_added);
    }

    public function test_a_renewal_confirmed_twice_extends_the_key_once(): void
    {
        $user = User::factory()->create();
        $key = $this->firstPurchase($user);
        Mail::fake(); // forget the first purchase's e-mail

        $renewal = $this->paidOrder($user);
        app(LicenseService::class)->generateLicensesForOrder($renewal);
        app(LicenseService::class)->generateLicensesForOrder($renewal);

        $this->assertTrue($key->fresh()->expires_at->equalTo(now()->addDays(60)));
        $this->assertSame(1, LicenseRenewal::count());
        Mail::assertSent(PaymentConfirmedMail::class, 1);
    }

    public function test_a_delivery_that_missed_the_first_renewal_still_cannot_extend_twice(): void
    {
        // Two deliveries racing: the second read the renewals table before the first wrote its
        // row. The unique order_item_id stops it at the insert, before the key moves.
        $user = User::factory()->create();
        $key = $this->firstPurchase($user);
        $renewal = $this->paidOrder($user);
        app(LicenseService::class)->generateLicensesForOrder($renewal);

        $service = app(LicenseService::class);
        $extended = (new \ReflectionMethod($service, 'extendLicense'))->invoke(
            $service, $key->fresh(), $renewal, $renewal->items()->sole(), 'monthly', 1
        );

        $this->assertFalse($extended);
        $this->assertTrue($key->fresh()->expires_at->equalTo(now()->addDays(60)));
        $this->assertSame(1, LicenseRenewal::count());
    }

    public function test_a_long_user_agent_cannot_break_a_renewal(): void
    {
        // The renewal's activity row is written in the same transaction as the renewal, and
        // license_activities.user_agent is varchar(255): MySQL strict refused a longer header,
        // and the renewal with it. In-app browsers (LINE, Facebook) send long ones.
        $user = User::factory()->create();
        $key = $this->firstPurchase($user);
        request()->headers->set('User-Agent', str_repeat('Mozilla/5.0 (Linux; Android 14; wv) FB_IAB/FB4A ', 12));

        app(LicenseService::class)->generateLicensesForOrder($this->paidOrder($user));

        $activity = LicenseActivity::where('license_id', $key->id)->where('action', 'extended')->sole();
        $this->assertSame(255, mb_strlen($activity->user_agent));
        $this->assertTrue($key->fresh()->expires_at->equalTo(now()->addDays(60)));
    }

    public function test_a_revoked_key_is_not_renewed_the_customer_gets_a_new_one(): void
    {
        $user = User::factory()->create();
        $revoked = $this->firstPurchase($user);
        $revoked->update(['status' => LicenseKey::STATUS_REVOKED, 'expires_at' => now()->addDays(10)]);

        $order = $this->paidOrder($user);
        app(LicenseService::class)->generateLicensesForOrder($order);

        $revoked->refresh();
        $this->assertSame('revoked', $revoked->status);
        $this->assertTrue($revoked->expires_at->equalTo(now()->addDays(10)));

        $fresh = LicenseKey::where('order_id', $order->id)->sole();
        $this->assertNotSame($revoked->license_key, $fresh->license_key);
        $this->assertTrue($fresh->expires_at->equalTo(now()->addDays(30)));
        $this->assertSame(0, LicenseRenewal::count());
    }

    public function test_a_guest_order_gets_a_new_key(): void
    {
        $someone = User::factory()->create();
        $theirs = $this->firstPurchase($someone);

        $order = $this->paidOrder(null);
        app(LicenseService::class)->generateLicensesForOrder($order);

        $guestKey = LicenseKey::where('order_id', $order->id)->sole();
        $this->assertNull($guestKey->user_id);
        $this->assertTrue($theirs->fresh()->expires_at->equalTo(now()->addDays(30)), 'nobody else is renewed');
    }

    public function test_another_customers_purchase_never_renews_your_key(): void
    {
        $key = $this->firstPurchase(User::factory()->create());

        $order = $this->paidOrder(User::factory()->create());
        app(LicenseService::class)->generateLicensesForOrder($order);

        $this->assertTrue($key->fresh()->expires_at->equalTo(now()->addDays(30)));
        $this->assertSame(1, LicenseKey::where('order_id', $order->id)->count());
    }

    public function test_renew_on_one_key_extends_that_key_not_the_one_expiring_last(): void
    {
        $user = User::factory()->create();
        $first = $this->firstPurchase($user);
        $first->update(['expires_at' => now()->addDays(20)]);
        $second = LicenseKey::create([
            'product_id' => $this->brainx->id, 'user_id' => $user->id, 'license_key' => LicenseKey::generateKey(),
            'license_type' => 'monthly', 'status' => 'active', 'expires_at' => now()->addDays(5),
        ]);

        app(LicenseService::class)->generateLicensesForOrder(
            $this->paidOrder($user, 1, ['license_type' => 'monthly', 'renew_license_id' => $second->id])
        );

        $this->assertTrue($second->fresh()->expires_at->equalTo(now()->addDays(35)));
        $this->assertTrue($first->fresh()->expires_at->equalTo(now()->addDays(20)));

        // Without a named key, the one running longest is the one extended
        app(LicenseService::class)->generateLicensesForOrder($this->paidOrder($user));
        $this->assertTrue($second->fresh()->expires_at->equalTo(now()->addDays(65)));
        $this->assertTrue($first->fresh()->expires_at->equalTo(now()->addDays(20)));
    }

    public function test_the_sms_payment_api_renews_the_same_key(): void
    {
        // Api\V1\SmsPaymentController had its own copy of the key issuer; it calls the service now.
        // Reflection, because the real route needs an SmsChecker device signature.
        $user = User::factory()->create();
        $key = $this->firstPurchase($user);

        $controller = (new \ReflectionClass(SmsPaymentController::class))->newInstanceWithoutConstructor();
        (new \ReflectionMethod($controller, 'generateLicensesForOrder'))->invoke($controller, $this->paidOrder($user));

        $this->assertSame(1, LicenseKey::where('product_id', $this->brainx->id)->count());
        $this->assertTrue($key->fresh()->expires_at->equalTo(now()->addDays(60)));
    }

    public function test_a_product_that_is_not_renewable_still_gets_a_new_key_each_time(): void
    {
        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $app = Product::create([
            'category_id' => $category->id, 'name' => 'WinXTools', 'slug' => 'winx-tools',
            'description' => 'x', 'price' => 199, 'requires_license' => true, 'is_active' => true,
        ]);
        $user = User::factory()->create();

        foreach ([1, 2] as $_) {
            app(LicenseService::class)->generateLicensesForOrder(
                $this->paidOrder($user, 1, ['license_type' => 'yearly'], $app)
            );
        }

        $this->assertSame(2, LicenseKey::where('product_id', $app->id)->count());
        $this->assertSame(0, LicenseRenewal::count());
    }

    // ── where the customer sees the renewed key ───────────────────────

    public function test_the_renewal_order_shows_and_mails_the_extended_key(): void
    {
        $user = User::factory()->create();
        $key = $this->firstPurchase($user);
        Mail::fake();

        $renewal = $this->paidOrder($user);
        app(LicenseService::class)->generateLicensesForOrder($renewal);

        Mail::assertSent(PaymentConfirmedMail::class, function ($mail) use ($key) {
            $html = $mail->render();

            return str_contains($html, $key->license_key) && str_contains($html, 'Renewed');
        });

        $this->actingAs($user)->get(route('orders.show', $renewal))
            ->assertOk()
            ->assertSee($key->license_key)
            ->assertSee('ต่ออายุคีย์เดิมแล้ว');

        $download = $this->actingAs($user)->get(route('orders.download', $renewal));
        $download->assertOk();
        $this->assertStringContainsString($key->license_key, $download->getContent());
    }

    public function test_the_license_page_offers_renewal_of_a_brainx_key_but_not_a_revoked_one(): void
    {
        $user = User::factory()->create();
        $key = $this->firstPurchase($user);

        $this->actingAs($user)->get(route('customer.licenses.show', $key))
            ->assertOk()
            ->assertSee('name="renew_license" value="' . $key->id . '"', false)
            ->assertSee('action="' . route('cart.add', $this->brainx) . '"', false);

        $key->update(['status' => LicenseKey::STATUS_REVOKED]);

        $this->actingAs($user)->get(route('customer.licenses.show', $key))
            ->assertOk()
            ->assertDontSee('name="renew_license"', false);
    }

    // ── helpers ────────────────────────────────────────────────────────

    /** A customer's first BrainX Cloud purchase, paid — returns the key it issued. */
    private function firstPurchase(User $user): LicenseKey
    {
        $order = $this->paidOrder($user);
        app(LicenseService::class)->generateLicensesForOrder($order);

        return LicenseKey::where('order_id', $order->id)->sole();
    }

    private function paidOrder(?User $user, int $quantity = 1, ?array $requirements = ['license_type' => 'monthly'], ?Product $product = null): Order
    {
        $product ??= $this->brainx;
        $price = (float) $product->price;

        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(),
            'user_id' => $user?->id,
            'customer_name' => $user->name ?? 'Guest',
            'customer_email' => $user->email ?? 'guest@example.com',
            'customer_phone' => '0800000000',
            'subtotal' => $price * $quantity,
            'total' => $price * $quantity,
            'payment_method' => 'bank_transfer',
            'status' => 'processing',
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $price,
            'quantity' => $quantity,
            'subtotal' => $price * $quantity,
            'custom_requirements' => $requirements ? json_encode($requirements) : null,
        ]);

        return $order;
    }
}
