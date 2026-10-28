<?php

namespace Tests\Feature;

use App\Mail\LicenseRenewalMail;
use App\Mail\PaymentConfirmedMail;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\LicenseKey;
use App\Models\LicenseRenewal;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Services\LicenseRenewalService;
use App\Support\CloudStorage;
use App\Support\ComingSoonPurchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * "ต่ออายุ" on the customer's license page: one more term on the SAME key — from the wallet in
 * one click, through the cart with any payment method, or automatically from the wallet before
 * the key runs out (licenses:renew). Plus the admin's "extend" on a lapsed key, and BrainX's shop
 * row renamed to `brainx-cloud` in production.
 */
class LicenseRenewalTest extends TestCase
{
    use RefreshDatabase;

    private Product $tping;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->withoutVite();
        $this->freezeSecond();

        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);

        // Not renewable by default and not sold in the cart: Tping has a checkout of its own
        $this->tping = Product::updateOrCreate(['slug' => 'tping'], [
            'category_id' => $category->id, 'name' => 'Tping', 'description' => 'x', 'price' => 399,
            'requires_license' => true, 'is_active' => true, 'is_coming_soon' => false,
        ]);
    }

    // ── the offer ─────────────────────────────────────────────────────

    public function test_the_license_page_offers_one_more_term_of_the_same_key_with_vat(): void
    {
        $user = User::factory()->create();
        $key = $this->key($user, 'monthly', now()->addDays(5));
        $this->wallet($user, 1000);

        $this->actingAs($user)->get(route('customer.licenses.show', $key))
            ->assertOk()
            ->assertSee('id="renew"', false)
            ->assertSee('฿' . number_format($this->total(399), 2))
            ->assertSee(now()->addDays(35)->format('d/m/Y'))
            ->assertSee('action="' . route('customer.licenses.renew', $key) . '"', false)
            ->assertSee('name="renew_license" value="' . $key->id . '"', false)
            ->assertSee('action="' . route('customer.licenses.auto-renew', $key) . '"', false);
    }

    public function test_the_license_list_shows_a_renew_button_only_for_renewable_keys(): void
    {
        $user = User::factory()->create();
        $monthly = $this->key($user, 'monthly', now()->addDays(5));
        $lifetime = $this->key($user, 'lifetime', null);

        $html = $this->actingAs($user)->get(route('customer.licenses'))->assertOk()->getContent();

        $this->assertStringContainsString(route('customer.licenses.show', $monthly) . '#renew', $html);
        $this->assertStringNotContainsString(route('customer.licenses.show', $lifetime) . '#renew', $html);
    }

    public function test_no_offer_for_lifetime_revoked_coming_soon_or_unpriced_keys(): void
    {
        $user = User::factory()->create();
        $renewals = app(LicenseRenewalService::class);

        $this->assertNull($renewals->offerFor($this->key($user, 'lifetime', null), $user->id));
        $this->assertNull($renewals->offerFor($this->key($user, 'monthly', now()->addDay(), LicenseKey::STATUS_REVOKED), $user->id));
        $this->assertNull($renewals->offerFor($this->key($user, 'weekly', now()->addDay()), $user->id), 'no weekly price in the table');
        $this->assertNull($renewals->offerFor($this->key($user, 'monthly', now()->addDay()), User::factory()->create()->id), 'not theirs');

        $key = $this->key($user, 'monthly', now()->addDay());
        $this->tping->update(['is_coming_soon' => true]);
        $this->assertNull($renewals->offerFor($key->fresh(), $user->id));
    }

    // ── wallet, one click ─────────────────────────────────────────────

    public function test_renewing_from_the_wallet_extends_the_same_key_and_makes_a_paid_order(): void
    {
        $user = User::factory()->create();
        $key = $this->key($user, 'monthly', now()->addDays(5));
        $this->wallet($user, 1000);

        $this->actingAs($user)
            ->post(route('customer.licenses.renew', $key), ['expected_expiry' => LicenseRenewalService::expiryStamp($key)])
            ->assertRedirect(route('customer.licenses.show', $key))
            ->assertSessionHas('success');

        $key->refresh();
        $this->assertTrue($key->expires_at->equalTo(now()->addDays(35)), 'one month on top of the days left');
        $this->assertSame(LicenseKey::STATUS_ACTIVE, $key->status);
        $this->assertSame(1, LicenseKey::where('product_id', $this->tping->id)->count(), 'no second key');

        $order = Order::where('user_id', $user->id)->sole();
        $this->assertSame('completed', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertEquals($this->total(399), (float) $order->total);
        $this->assertSame(1, LicenseRenewal::where('order_id', $order->id)->where('license_key_id', $key->id)->count());

        $this->assertEqualsWithDelta(1000 - $this->total(399), (float) Wallet::where('user_id', $user->id)->value('balance'), 0.001);
        Mail::assertSent(PaymentConfirmedMail::class);
    }

    public function test_a_lapsed_key_renewed_from_the_wallet_counts_from_today(): void
    {
        $user = User::factory()->create();
        $key = $this->key($user, 'yearly', now()->subDays(40), LicenseKey::STATUS_EXPIRED);
        $this->wallet($user, 5000);

        app(LicenseRenewalService::class)->renewFromWallet($key, $user);

        $key->refresh();
        $this->assertTrue($key->expires_at->equalTo(now()->addYear()));
        $this->assertTrue($key->isValid());
    }

    public function test_a_short_wallet_is_refused_without_charging_anything(): void
    {
        $user = User::factory()->create();
        $key = $this->key($user, 'monthly', now()->addDays(5));
        $this->wallet($user, 100);
        $before = $key->expires_at->copy();

        $this->actingAs($user)
            ->post(route('customer.licenses.renew', $key), ['expected_expiry' => LicenseRenewalService::expiryStamp($key)])
            ->assertSessionHas('error');

        $this->assertTrue($key->fresh()->expires_at->equalTo($before));
        $this->assertSame(0, Order::count());
        $this->assertEquals(100, (float) Wallet::where('user_id', $user->id)->value('balance'));
    }

    public function test_a_second_click_on_the_same_form_does_not_pay_twice(): void
    {
        $user = User::factory()->create();
        $key = $this->key($user, 'monthly', now()->addDays(5));
        $this->wallet($user, 1000);
        $stamp = LicenseRenewalService::expiryStamp($key);

        $this->actingAs($user)->post(route('customer.licenses.renew', $key), ['expected_expiry' => $stamp])->assertSessionHas('success');
        $this->actingAs($user)->post(route('customer.licenses.renew', $key), ['expected_expiry' => $stamp])->assertSessionHas('error');

        $this->assertSame(1, Order::count());
        $this->assertTrue($key->fresh()->expires_at->equalTo(now()->addDays(35)));
    }

    public function test_nobody_renews_or_switches_someone_elses_key(): void
    {
        $owner = User::factory()->create();
        $key = $this->key($owner, 'monthly', now()->addDays(5));
        $stranger = User::factory()->create();
        $this->wallet($stranger, 1000);

        $this->actingAs($stranger)->post(route('customer.licenses.renew', $key), ['expected_expiry' => '0'])->assertForbidden();
        $this->actingAs($stranger)->post(route('customer.licenses.auto-renew', $key), ['auto_renew' => 1])->assertForbidden();

        $this->assertSame(0, Order::count());
        $this->assertFalse($key->fresh()->auto_renew);
    }

    // ── the cart, any payment method ──────────────────────────────────

    public function test_the_cart_renews_a_key_of_a_product_it_does_not_otherwise_sell(): void
    {
        $user = User::factory()->create();
        $key = $this->key($user, 'monthly', now()->addDays(5));

        $this->actingAs($user)->post(route('cart.add', $this->tping), [
            'license_type' => 'monthly', 'renew_license' => $key->id, 'buy_now' => 1,
        ])->assertRedirect(route('cart.index'));

        $item = CartItem::sole();
        $this->assertEquals(399, (float) $item->price);
        $this->assertSame($key->id, json_decode($item->custom_requirements, true)['renew_license_id']);

        // Without a key of theirs to renew, Tping is still sold only by its own checkout
        $this->actingAs(User::factory()->create())->post(route('cart.add', $this->tping), ['license_type' => 'monthly'])
            ->assertSessionHas('error');
    }

    // ── auto-renew ────────────────────────────────────────────────────

    public function test_auto_renew_switches_on_and_off_but_not_for_a_lifetime_key(): void
    {
        $user = User::factory()->create();
        $key = $this->key($user, 'monthly', now()->addDays(20));

        $this->actingAs($user)->post(route('customer.licenses.auto-renew', $key), ['auto_renew' => 1])->assertSessionHas('success');
        $this->assertTrue($key->fresh()->auto_renew);

        $this->actingAs($user)->post(route('customer.licenses.auto-renew', $key), ['auto_renew' => 0])->assertSessionHas('success');
        $this->assertFalse($key->fresh()->auto_renew);

        $lifetime = $this->key($user, 'lifetime', null);
        $this->actingAs($user)->post(route('customer.licenses.auto-renew', $lifetime), ['auto_renew' => 1])->assertSessionHas('error');
        $this->assertFalse($lifetime->fresh()->auto_renew);
    }

    public function test_the_run_charges_a_key_due_for_auto_renewal(): void
    {
        $user = User::factory()->create();
        $key = $this->key($user, 'monthly', now()->addDay(), auto: true);
        $this->wallet($user, 1000);

        $this->artisan('licenses:renew')->assertSuccessful();

        $this->assertTrue($key->fresh()->expires_at->equalTo(now()->addDays(31)));
        $this->assertSame(1, Order::where('user_id', $user->id)->count());
        $this->assertTrue(Order::sole()->metadata['automatic']);

        // Not again the next day: the key is a month out now
        $this->artisan('licenses:renew')->assertSuccessful();
        $this->assertSame(1, Order::count());
    }

    public function test_a_short_wallet_on_auto_renew_is_said_once_and_charges_nothing(): void
    {
        $user = User::factory()->create();
        $key = $this->key($user, 'monthly', now()->addDay(), auto: true);
        $this->wallet($user, 10);

        $this->artisan('licenses:renew')->assertSuccessful();
        $this->artisan('licenses:renew')->assertSuccessful();

        Mail::assertSent(LicenseRenewalMail::class, 1);
        Mail::assertSent(LicenseRenewalMail::class, fn ($mail) => $mail->kind === 'short');
        $this->assertSame(0, Order::count());
        $this->assertTrue($key->fresh()->expires_at->equalTo(now()->addDay()));
    }

    public function test_auto_renew_says_what_it_will_charge_a_week_ahead_once(): void
    {
        $user = User::factory()->create();
        $this->key($user, 'monthly', now()->addDays(6), auto: true);
        $this->wallet($user, 1000);

        $this->artisan('licenses:renew')->assertSuccessful();
        $this->artisan('licenses:renew')->assertSuccessful();

        Mail::assertSent(LicenseRenewalMail::class, 1);
        Mail::assertSent(LicenseRenewalMail::class, fn ($mail) => $mail->kind === 'notice');
        $this->assertSame(0, Order::count(), 'not charged before the charge window');
    }

    public function test_a_key_without_auto_renew_is_reminded_at_a_week_and_a_day(): void
    {
        $user = User::factory()->create();
        $key = $this->key($user, 'monthly', now()->addDays(6));

        $this->artisan('licenses:renew')->assertSuccessful();
        $this->artisan('licenses:renew')->assertSuccessful();
        Mail::assertSent(LicenseRenewalMail::class, 1);
        Mail::assertSent(LicenseRenewalMail::class, fn ($mail) => $mail->kind === 'lapse-7');

        $this->travelTo($key->expires_at->copy()->subHours(20));
        $this->artisan('licenses:renew')->assertSuccessful();
        Mail::assertSent(LicenseRenewalMail::class, fn ($mail) => $mail->kind === 'lapse-1');
        Mail::assertSent(LicenseRenewalMail::class, 2);
        $this->assertSame(0, Order::count());
    }

    public function test_the_run_leaves_lifetime_and_unowned_keys_alone(): void
    {
        $user = User::factory()->create();
        $this->key($user, 'lifetime', null, auto: true);
        $this->key(null, 'monthly', now()->addDay(), auto: true);

        $this->artisan('licenses:renew')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame(0, Order::count());
    }

    // ── Coming Soon: renewals only where the admin allows them ───────

    public function test_coming_soon_stops_renewals_until_the_admin_lets_current_customers_renew(): void
    {
        $user = User::factory()->create();
        $key = $this->key($user, 'monthly', now()->addDays(5));
        $this->wallet($user, 1000);
        $this->tping->update(['is_coming_soon' => true]);
        $renewals = app(LicenseRenewalService::class);

        $this->assertNull($renewals->offerFor($key->fresh(), $user->id));
        $this->actingAs($user)->post(route('cart.add', $this->tping), ['license_type' => 'monthly', 'renew_license' => $key->id])
            ->assertSessionHas('error', ComingSoonPurchase::MESSAGE);

        $this->tping->update(['renew_while_coming_soon' => true]);

        $this->assertNotNull($renewals->offerFor($key->fresh(), $user->id));

        // the cart takes the renewal, and checkout lets it through
        $this->actingAs($user)->post(route('cart.add', $this->tping), [
            'license_type' => 'monthly', 'renew_license' => $key->id, 'buy_now' => 1,
        ])->assertRedirect(route('cart.index'));
        $this->assertNull(ComingSoonPurchase::refusalForCart(CartItem::sole()->cart->load('items.product')));

        // ...and so does the wallet
        $renewals->renewFromWallet($key->fresh(), $user);
        $this->assertTrue($key->fresh()->expires_at->equalTo(now()->addDays(35)));

        // A new buyer still cannot buy it
        $this->actingAs(User::factory()->create())->post(route('cart.add', $this->tping), ['license_type' => 'monthly'])
            ->assertSessionHas('error', ComingSoonPurchase::MESSAGE);
    }

    // ── admin "extend" ────────────────────────────────────────────────

    public function test_the_admin_extending_a_lapsed_key_makes_it_active_from_today(): void
    {
        $key = $this->key(User::factory()->create(), 'monthly', now()->subDays(60), LicenseKey::STATUS_EXPIRED);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.licenses.extend', $key), ['days' => 30])->assertSessionHas('success');

        $key->refresh();
        $this->assertTrue($key->expires_at->equalTo(now()->addDays(30)));
        $this->assertTrue($key->isValid(), 'the page shows Active, not Expired');

        // A running key still gets the days on top of what it has left
        $running = $this->key(User::factory()->create(), 'monthly', now()->addDays(10));
        $this->actingAs($admin)->post(route('admin.licenses.bulk-extend'), ['license_ids' => [$running->id, $key->id], 'days' => 7]);

        $this->assertTrue($running->fresh()->expires_at->equalTo(now()->addDays(17)));
        $this->assertTrue($key->fresh()->expires_at->equalTo(now()->addDays(37)));
    }

    // ── BrainX renamed to brainx-cloud in production ──────────────────

    public function test_brainx_still_works_when_its_shop_row_is_called_brainx_cloud(): void
    {
        $brainx = Product::where('slug', 'brainx')->firstOrFail();
        $brainx->update(['slug' => 'brainx-cloud']);

        $user = User::factory()->create();
        $key = LicenseKey::create([
            'product_id' => $brainx->id, 'user_id' => $user->id, 'license_key' => LicenseKey::generateKey(),
            'license_type' => 'lifetime', 'status' => 'active', 'expires_at' => null, 'max_activations' => 1, 'activations' => 0,
        ]);

        $this->get('/products/brainx')->assertOk();

        $this->getJson('/api/v1/product/brainx/pricing')
            ->assertOk()
            ->assertJsonPath('data.plans.monthly.price', 399);

        $this->getJson("/api/v1/product/brainx/status/{$key->license_key}")
            ->assertOk()
            ->assertJsonPath('data.license_type', 'lifetime')
            ->assertJsonPath('data.is_valid', true);

        $this->assertTrue($brainx->fresh()->isRenewable());
        $this->assertTrue(CloudStorage::offers('brainx-cloud'), 'the cloud space follows the renamed row too');
        $this->assertSame(CloudStorage::baseGb('brainx'), CloudStorage::baseGb('brainx-cloud'));
        $this->assertSame(LicenseKey::TYPE_MONTHLY, $brainx->fresh()->defaultLicenseType());
    }

    // ── helpers ───────────────────────────────────────────────────────

    private function key(?User $user, string $type, $expiresAt, string $status = LicenseKey::STATUS_ACTIVE, bool $auto = false): LicenseKey
    {
        return LicenseKey::create([
            'product_id' => $this->tping->id,
            'user_id' => $user?->id,
            'license_key' => LicenseKey::generateKey(),
            'license_type' => $type,
            'status' => $status,
            'expires_at' => $expiresAt,
            'auto_renew' => $auto,
            'max_activations' => 1,
            'activations' => 0,
        ]);
    }

    private function wallet(User $user, float $balance): Wallet
    {
        $wallet = Wallet::getOrCreateForUser($user->id);
        $wallet->update(['balance' => $balance]);

        return $wallet;
    }

    private function total(float $price): float
    {
        return round($price + round($price * (float) config('app.vat_rate', 0.07), 2), 2);
    }
}
