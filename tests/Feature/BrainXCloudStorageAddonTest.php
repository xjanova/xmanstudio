<?php

namespace Tests\Feature;

use App\Mail\PaymentConfirmedMail;
use App\Models\CartItem;
use App\Models\LicenseActivity;
use App\Models\LicenseKey;
use App\Models\LicenseStorageAddon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Services\LicenseService;
use App\Support\CloudStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * BrainX Cloud space the customer buys for themselves: a pack (+4 / +12 / +28 GB, priced in
 * config/licenses.php) bought through the cart from the license page of one of their own
 * keys. Paid once, the space stays with that key for as long as the key lives (owner,
 * 2026-10-19), and the status API's cloud_storage_bytes carries it to the cloud server.
 */
class BrainXCloudStorageAddonTest extends TestCase
{
    use RefreshDatabase;

    private const GB = 1024 * 1024 * 1024;

    private Product $brainx;

    private Product $pack;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Cache::flush();
        $this->withoutVite();
        // Without an admin every page redirects to the setup wizard.
        User::factory()->create(['role' => 'admin']);

        // Both created by migrations, exactly as production gets them on deploy
        $this->brainx = Product::where('slug', 'brainx')->firstOrFail();
        $this->pack = Product::where('slug', 'brainx-cloud-storage')->firstOrFail();
    }

    // ── what is sold ───────────────────────────────────────────────────

    public function test_the_owner_prices_the_packs_in_the_config(): void
    {
        $this->assertSame([4 => 99, 12 => 249, 28 => 499], CloudStorage::addons('brainx'));
        $this->assertSame(249, CloudStorage::addonPrice('brainx', 12));
        $this->assertSame(249, CloudStorage::addonPrice('brainx', '12'));
        $this->assertNull(CloudStorage::addonPrice('brainx', 5));
        $this->assertNull(CloudStorage::addonPrice('brainx', '12.5'));
        $this->assertSame([], CloudStorage::addons('tping'));
        $this->assertSame('brainx', CloudStorage::productForAddon('brainx-cloud-storage'));
        $this->assertNull(CloudStorage::productForAddon('brainx'));
    }

    public function test_the_pack_product_is_off_the_store_and_its_page_leads_to_the_license_page(): void
    {
        $this->assertFalse($this->pack->requires_license);
        $this->assertTrue($this->pack->is_active);
        $this->assertFalse(Product::onWebsite()->whereKey($this->pack->id)->exists());
        $this->assertTrue(Product::onWebsite()->whereKey($this->brainx->id)->exists());

        $this->get('/products')->assertOk()->assertDontSee('BrainX Cloud — พื้นที่เพิ่ม');
        $this->get('/products/brainx-cloud-storage')->assertRedirect(route('customer.licenses'));
    }

    // ── the cart ───────────────────────────────────────────────────────

    public function test_the_cart_charges_the_configured_price_for_the_customers_own_key(): void
    {
        $user = User::factory()->create();
        $key = $this->brainxKey($user);

        $this->actingAs($user)
            ->post(route('cart.add', $this->pack), ['storage_gb' => 12, 'license' => $key->id, 'price' => 1, 'buy_now' => 1])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('success');

        $line = CartItem::sole();
        $this->assertEquals(249, (float) $line->price);
        $this->assertSame(1, $line->quantity);
        $this->assertSame(['storage_gb' => 12, 'license_id' => $key->id], json_decode($line->custom_requirements, true));

        $this->actingAs($user)->get(route('cart.index'))->assertOk()->assertSee('+12 GB');
    }

    public function test_a_guest_is_sent_to_log_in_first(): void
    {
        $key = $this->brainxKey(User::factory()->create());

        $this->post(route('cart.add', $this->pack), ['storage_gb' => 4, 'license' => $key->id])
            ->assertRedirect(route('login'));

        $this->assertSame(0, CartItem::count());
    }

    public function test_someone_elses_key_a_revoked_key_or_another_products_key_is_refused(): void
    {
        $user = User::factory()->create();
        $theirs = $this->brainxKey(User::factory()->create());
        $revoked = $this->brainxKey($user, ['status' => LicenseKey::STATUS_REVOKED]);
        $otherProduct = LicenseKey::create([
            'product_id' => Product::create([
                'category_id' => $this->brainx->category_id, 'name' => 'Other app', 'slug' => 'other-app',
                'description' => 'x', 'price' => 100, 'requires_license' => true, 'is_active' => true,
            ])->id,
            'user_id' => $user->id,
            'license_key' => LicenseKey::generateKey(),
            'license_type' => LicenseKey::TYPE_YEARLY,
            'status' => LicenseKey::STATUS_ACTIVE,
            'expires_at' => now()->addYear(),
        ]);

        foreach ([$theirs, $revoked, $otherProduct] as $key) {
            $this->actingAs($user)
                ->post(route('cart.add', $this->pack), ['storage_gb' => 4, 'license' => $key->id])
                ->assertSessionHas('error');
        }

        $this->assertSame(0, CartItem::count());
    }

    public function test_a_lapsed_key_can_still_get_space(): void
    {
        $user = User::factory()->create();
        $key = $this->brainxKey($user, ['status' => LicenseKey::STATUS_EXPIRED, 'expires_at' => now()->subDay()]);

        $this->actingAs($user)
            ->post(route('cart.add', $this->pack), ['storage_gb' => 4, 'license' => $key->id])
            ->assertSessionHas('success');

        $this->assertSame(1, CartItem::count());
    }

    public function test_a_pack_size_that_is_not_sold_is_refused(): void
    {
        $user = User::factory()->create();
        $key = $this->brainxKey($user);

        foreach ([5, 0, -4, 'lots', '', null] as $bad) {
            $response = $this->actingAs($user)
                ->post(route('cart.add', $this->pack), ['storage_gb' => $bad, 'license' => $key->id]);
            $this->assertTrue(
                session()->has('error') || session('errors')?->has('storage_gb'),
                'storage_gb ' . var_export($bad, true) . ' was accepted'
            );
            $response->assertRedirect();
        }

        $this->assertSame(0, CartItem::count());
    }

    public function test_the_same_pack_again_is_one_line_another_size_is_another_line(): void
    {
        $user = User::factory()->create();
        $key = $this->brainxKey($user);

        foreach ([4, 4, 12] as $gb) {
            $this->actingAs($user)->post(route('cart.add', $this->pack), ['storage_gb' => $gb, 'license' => $key->id]);
        }

        $this->assertSame(2, CartItem::count());
        $this->assertSame(2, CartItem::where('price', 99)->sole()->quantity);
        $this->assertSame(1, CartItem::where('price', 249)->sole()->quantity);
    }

    public function test_the_cart_refuses_space_past_the_keys_ceiling(): void
    {
        $user = User::factory()->create();
        // 1,024 GB is the most one key carries on top of its base; the admin already gave 1,016
        $key = $this->brainxKey($user, ['extra_storage_gb' => 1016]);

        $this->actingAs($user)->post(route('cart.add', $this->pack), ['storage_gb' => 12, 'license' => $key->id])
            ->assertSessionHas('error', 'คีย์นี้เพิ่มพื้นที่ได้อีก 8 GB');
        $this->actingAs($user)->post(route('cart.add', $this->pack), ['storage_gb' => 4, 'license' => $key->id])
            ->assertSessionHas('success');
        $this->actingAs($user)->post(route('cart.add', $this->pack), ['storage_gb' => 4, 'license' => $key->id])
            ->assertSessionHas('success');
        // What the cart already holds for the key counts too
        $this->actingAs($user)->post(route('cart.add', $this->pack), ['storage_gb' => 4, 'license' => $key->id])
            ->assertSessionHas('error', 'คีย์นี้เพิ่มพื้นที่ได้อีก 0 GB');

        $this->assertSame(2, CartItem::sole()->quantity);
    }

    public function test_raising_a_lines_quantity_past_the_ceiling_is_refused(): void
    {
        $user = User::factory()->create();
        $key = $this->brainxKey($user, ['extra_storage_gb' => 1000]);

        $this->actingAs($user)->post(route('cart.add', $this->pack), ['storage_gb' => 12, 'license' => $key->id]);
        $line = CartItem::sole();

        $this->actingAs($user)->patch(route('cart.update', $line), ['quantity' => 3])->assertSessionHas('error');
        $this->assertSame(1, $line->fresh()->quantity);

        $this->actingAs($user)->patch(route('cart.update', $line), ['quantity' => 2])->assertSessionHas('success');
        $this->assertSame(2, $line->fresh()->quantity);
    }

    // ── paying ─────────────────────────────────────────────────────────

    public function test_checking_out_with_the_wallet_adds_the_space_to_the_key(): void
    {
        $user = User::factory()->create();
        Wallet::getOrCreateForUser($user->id)->update(['balance' => 5000]);
        $key = $this->brainxKey($user);

        $this->actingAs($user)->post(route('cart.add', $this->pack), ['storage_gb' => 12, 'license' => $key->id]);
        $this->actingAs($user)->post(route('orders.store'), [
            'customer_name' => 'สมชาย ใจดี',
            'customer_email' => 'buyer@example.test',
            'customer_phone' => '0812345678',
            'payment_method' => 'wallet',
        ])->assertRedirect();

        $order = Order::sole();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(12, LicenseStorageAddon::where('license_key_id', $key->id)->sole()->gb_added);
        // No key of its own is issued for a pack
        $this->assertFalse(LicenseKey::where('order_id', $order->id)->exists());
        $this->assertSame(1, LicenseKey::where('user_id', $user->id)->count());

        $this->getJson("/api/v1/product/brainx/status/{$key->license_key}")
            ->assertOk()
            ->assertJsonPath('data.cloud_storage_gb', 16)
            ->assertJsonPath('data.cloud_storage_bytes', 16 * self::GB);

        Mail::assertSent(PaymentConfirmedMail::class);
    }

    public function test_paying_adds_the_space_once_and_logs_it(): void
    {
        $user = User::factory()->create();
        $key = $this->brainxKey($user);
        $order = $this->paidPackOrder($user, $key, 28);

        app(LicenseService::class)->generateLicensesForOrder($order);
        // The webhook and the checkout poll both confirm the same payment
        app(LicenseService::class)->generateLicensesForOrder($order);

        $addon = LicenseStorageAddon::sole();
        $this->assertSame([$key->id, 28, 1, 28], [$addon->license_key_id, $addon->pack_gb, $addon->units, $addon->gb_added]);
        $this->assertSame(32, CloudStorage::totalGb($key->fresh()));

        $log = LicenseActivity::where('license_id', $key->id)->where('action', LicenseActivity::ACTION_STORAGE_CHANGED)->sole();
        $this->assertSame(LicenseActivity::ACTOR_SYSTEM, $log->actor_type);
        $this->assertEquals(['order_id' => $order->id, 'pack_gb' => 28, 'units' => 1, 'gb_added' => 28, 'previous_total_gb' => 4, 'total_gb' => 32], $log->metadata);

        Mail::assertSent(PaymentConfirmedMail::class, 1);
    }

    public function test_two_packs_on_one_line_add_twice_the_space(): void
    {
        $user = User::factory()->create();
        $key = $this->brainxKey($user);

        app(LicenseService::class)->generateLicensesForOrder($this->paidPackOrder($user, $key, 12, 2));

        $this->assertSame(28, CloudStorage::totalGb($key->fresh()));
    }

    public function test_the_space_stays_through_a_renewal_and_after_the_key_lapses(): void
    {
        $user = User::factory()->create();
        $key = $this->brainxKey($user);
        app(LicenseService::class)->generateLicensesForOrder($this->paidPackOrder($user, $key, 12));

        // Renewing the key (a month of brainx) keeps the bought space
        app(LicenseService::class)->generateLicensesForOrder($this->paidOrder($user, $this->brainx, 399, ['license_type' => 'monthly']));
        $this->assertTrue($key->fresh()->expires_at->isAfter(now()->addDays(50)));
        $this->assertSame(16, CloudStorage::totalGb($key->fresh()));

        $this->travel(90)->days();

        $this->getJson("/api/v1/product/brainx/status/{$key->license_key}")
            ->assertOk()
            ->assertJsonPath('data.is_valid', false)
            ->assertJsonPath('data.cloud_storage_gb', 16);
    }

    public function test_a_key_revoked_before_payment_sends_the_space_to_the_buyers_other_key(): void
    {
        $user = User::factory()->create();
        $picked = $this->brainxKey($user);
        $other = $this->brainxKey($user, ['expires_at' => now()->addDays(10)]);
        $order = $this->paidPackOrder($user, $picked, 4);

        $picked->update(['status' => LicenseKey::STATUS_REVOKED]);
        app(LicenseService::class)->generateLicensesForOrder($order);

        $this->assertSame($other->id, LicenseStorageAddon::sole()->license_key_id);
    }

    public function test_with_no_key_left_nothing_is_added_and_the_order_waits_for_the_admin(): void
    {
        $user = User::factory()->create();
        $key = $this->brainxKey($user);
        $order = $this->paidPackOrder($user, $key, 4);

        $key->update(['status' => LicenseKey::STATUS_REVOKED]);
        app(LicenseService::class)->generateLicensesForOrder($order);

        $this->assertSame(0, LicenseStorageAddon::count());
        $this->assertSame('processing', $order->fresh()->status);
    }

    public function test_a_pack_paid_on_someone_elses_key_goes_to_the_buyers_own_key_only(): void
    {
        $buyer = User::factory()->create();
        $victim = $this->brainxKey(User::factory()->create());
        $own = $this->brainxKey($buyer);

        // A hand-built order line naming another customer's key
        app(LicenseService::class)->generateLicensesForOrder($this->paidPackOrder($buyer, $victim, 28));

        $this->assertSame($own->id, LicenseStorageAddon::sole()->license_key_id);
        $this->assertSame(4, CloudStorage::totalGb($victim->fresh()));
    }

    // ── the admin and the customer's pages ────────────────────────────

    public function test_the_admin_sets_only_their_own_extra_and_never_takes_bought_space(): void
    {
        $admin = User::where('role', 'admin')->first();
        $user = User::factory()->create();
        $key = $this->brainxKey($user, ['extra_storage_gb' => 4]);
        app(LicenseService::class)->generateLicensesForOrder($this->paidPackOrder($user, $key, 12));

        $this->actingAs($admin)->get(route('admin.licenses.show', $key))
            ->assertOk()
            ->assertSee('ลูกค้าซื้อเพิ่ม 12 GB')
            ->assertSee('20 GB');

        $this->actingAs($admin)->post(route('admin.licenses.storage', $key), ['extra_storage_gb' => 0])->assertRedirect();
        $this->assertSame(16, CloudStorage::totalGb($key->fresh()));

        // Bought and admin-added together stay within 1,024 GB
        $this->actingAs($admin)
            ->from(route('admin.licenses.show', $key))
            ->post(route('admin.licenses.storage', $key), ['extra_storage_gb' => 1013])
            ->assertSessionHasErrors('extra_storage_gb');
        $this->actingAs($admin)->post(route('admin.licenses.storage', $key), ['extra_storage_gb' => 1012])->assertSessionHas('success');
        $this->assertSame(1028, CloudStorage::totalGb($key->fresh()));
    }

    public function test_the_license_page_shows_base_plus_add_ons_and_sells_the_packs(): void
    {
        $user = User::factory()->create();
        $key = $this->brainxKey($user, ['extra_storage_gb' => 2]);
        app(LicenseService::class)->generateLicensesForOrder($this->paidPackOrder($user, $key, 12));

        $page = $this->actingAs($user)->get(route('customer.licenses.show', $key))->assertOk();

        $page->assertSee('18 GB')
            ->assertSee('data-cloud-breakdown', false)
            ->assertSee('action="' . route('cart.add', $this->pack) . '"', false)
            ->assertSee('name="license" value="' . $key->id . '"', false);
        foreach (['+4 GB' => '฿99', '+12 GB' => '฿249', '+28 GB' => '฿499'] as $gb => $price) {
            $page->assertSee($gb)->assertSee($price);
        }
        $this->assertMatchesRegularExpression('/4 GB\s*\+.*12 GB\s*\+.*2 GB/su', strip_tags($page->getContent()));
    }

    public function test_the_license_page_offers_only_packs_that_still_fit_and_none_for_a_revoked_key(): void
    {
        $user = User::factory()->create();
        $key = $this->brainxKey($user, ['extra_storage_gb' => 1010]);

        $this->actingAs($user)->get(route('customer.licenses.show', $key))
            ->assertOk()
            ->assertSee('name="storage_gb" value="4"', false)
            ->assertSee('name="storage_gb" value="12"', false)
            ->assertDontSee('name="storage_gb" value="28"', false);

        $key->update(['status' => LicenseKey::STATUS_REVOKED]);

        $this->actingAs($user)->get(route('customer.licenses.show', $key))
            ->assertOk()
            ->assertDontSee('data-cloud-packs', false);
    }

    // ── helpers ────────────────────────────────────────────────────────

    private function brainxKey(User $user, array $attributes = []): LicenseKey
    {
        return LicenseKey::create($attributes + [
            'product_id' => $this->brainx->id,
            'user_id' => $user->id,
            'license_key' => LicenseKey::generateKey(),
            'license_type' => LicenseKey::TYPE_MONTHLY,
            'status' => LicenseKey::STATUS_ACTIVE,
            'expires_at' => now()->addDays(30),
        ]);
    }

    private function paidPackOrder(User $user, LicenseKey $key, int $gb, int $quantity = 1): Order
    {
        return $this->paidOrder($user, $this->pack, CloudStorage::addonPrice('brainx', $gb), ['storage_gb' => $gb, 'license_id' => $key->id], $quantity);
    }

    private function paidOrder(User $user, Product $product, float $price, array $requirements, int $quantity = 1): Order
    {
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(),
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
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
            'custom_requirements' => json_encode($requirements),
        ]);

        return $order;
    }
}
