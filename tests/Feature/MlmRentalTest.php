<?php

namespace Tests\Feature;

use App\Http\Controllers\StripeWebhookController;
use App\Jobs\SendMlmWebhook;
use App\Models\RentalAddon;
use App\Models\RentalPackage;
use App\Models\RentalPayment;
use App\Models\User;
use App\Models\UserRental;
use App\Models\UserRentalAddon;
use App\Services\Rentals\RentalAddonPricing;
use App\Services\RentalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Stripe\PaymentIntent;
use Tests\TestCase;

/**
 * Renting XMAN MLM (mlm.xman4289.com) through the existing rental system: one product package
 * (monthly or yearly), add-ons bought with it at full price or later at a price for the days the
 * package has left, renewals that carry the add-ons, and one running rental per product.
 *
 * Money is satang throughout; the spec's worked example is the anchor:
 * yearly paid 1 Jan 2027, ฿2,900/year add-on bought 1 Jul 2027 → 184 days → ฿1,461.91; 3 Jul → ฿1,446.02.
 */
class MlmRentalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        // The migration ships the MLM packages switched off; the admin turns them on.
        RentalPackage::where('product_key', 'mlm')->update(['is_active' => true]);

        config([
            'services.mlm.webhook_url' => 'https://mlm.example.test/api/xman/webhook',
            'services.mlm.webhook_secret' => 'test-secret',
        ]);
        Queue::fake();
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Freeze the clock at a Thai wall-clock time (the app itself runs in UTC). */
    private function at(string $bangkok): void
    {
        Carbon::setTestNow(Carbon::parse($bangkok, 'Asia/Bangkok')->utc());
    }

    private function service(): RentalService
    {
        return app(RentalService::class);
    }

    /** Rent MLM through the real flow: checkout, then the payment confirmed. */
    private function rentMlm(User $user, string $cycle = 'monthly', array $addons = [], ?string $reservation = null): UserRental
    {
        $package = RentalPackage::findForCheckout('mlm-core', $cycle);
        $result = $this->service()->createRental($user, $package, null, 'bank_transfer', ['addons' => $addons, 'reservation' => $reservation]);
        $this->assertTrue($result['success'], $result['error'] ?? '');
        $this->service()->processPaymentCompletion($result['payment']);

        return $result['rental']->fresh();
    }

    private function legacyPackage(): RentalPackage
    {
        // fresh(): the currency default lives in the database, as it does for every real package.
        return RentalPackage::create([
            'name' => 'Pro Monthly', 'name_th' => 'โปรรายเดือน', 'price' => 990,
            'duration_type' => 'monthly', 'duration_value' => 1, 'is_active' => true,
        ])->fresh();
    }

    // ── the catalogue the migration ships ───────────────────────────────────────────────────────

    public function test_the_migration_ships_the_mlm_catalogue_in_satang_marked_as_a_proposal(): void
    {
        $monthly = RentalPackage::findForCheckout('mlm-core', 'monthly');
        $yearly = RentalPackage::findForCheckout('mlm-core', 'yearly');
        $this->assertSame('690.00', (string) $monthly->price);
        $this->assertSame('6900.00', (string) $yearly->price);
        $this->assertTrue($monthly->price_is_proposal);

        $addons = RentalAddon::where('product_key', 'mlm')->pluck('price_monthly_satang', 'code')->all();
        $this->assertSame([
            'mlm-extra-structure' => 19000, 'mlm-members-5k' => 19000, 'mlm-members-20k' => 49000,
            'mlm-members-unlimited' => 99000, 'mlm-custom-domain' => 9000, 'mlm-auto-verify' => 29000,
            'mlm-page-builder' => 19000, 'mlm-ocpb-kit' => 19000, 'mlm-flowaccount' => 19000, 'mlm-gateway' => 19000,
        ], array_map('intval', $addons));
        $this->assertSame(290000, RentalAddon::where('code', 'mlm-auto-verify')->value('price_yearly_satang'));
    }

    // ── proration ───────────────────────────────────────────────────────────────────────────────

    public function test_prorate_is_floor_in_satang(): void
    {
        $this->assertSame(146191, RentalAddonPricing::prorate(290000, 1, 184, 365)); // ฿1,461.91
        $this->assertSame(144602, RentalAddonPricing::prorate(290000, 1, 182, 365)); // ฿1,446.02
        $this->assertSame(290000, RentalAddonPricing::prorate(290000, 1, 400, 365), 'never more than a full cycle');
        $this->assertSame(0, RentalAddonPricing::prorate(290000, 1, 0, 365));
        $this->assertSame(2 * 19000, RentalAddonPricing::prorate(19000, 2, 30, 30));
    }

    public function test_an_addon_bought_mid_year_costs_the_days_left_and_is_repriced_on_the_day_it_is_paid(): void
    {
        $user = User::factory()->create();

        $this->at('2027-01-01 10:00');
        $rental = $this->rentMlm($user, 'yearly');
        $this->assertSame(365, RentalAddonPricing::cycleDays($rental));

        // 1 Jul 2027: 184 days to 1 Jan 2028.
        $this->at('2027-07-01 12:00');
        $this->assertSame(184, RentalAddonPricing::daysLeft($rental));
        $result = $this->service()->createAddonPurchase($user, $rental, ['mlm-auto-verify' => 1], 'bank_transfer');
        $this->assertTrue($result['success'], $result['error'] ?? '');
        $payment = $result['payment']->fresh();
        $this->assertSame('1461.91', (string) $payment->amount);
        $this->assertSame('2027-07-01', $payment->metadata['quote_date']);

        // Same day: the quote stands.
        $this->assertSame('unchanged', $this->service()->refreshAddonQuote($payment));

        // Paid on 3 Jul instead: priced again for 182 days.
        $this->at('2027-07-03 09:00');
        $this->assertSame('repriced', $this->service()->refreshAddonQuote($payment->fresh()));
        $payment->refresh();
        $this->assertSame('1446.02', (string) $payment->amount);
        $row = UserRentalAddon::where('rental_payment_id', $payment->id)->first();
        $this->assertSame(144602, (int) $row->price_satang);
        $this->assertSame(182, (int) $row->days_charged);
        $this->assertSame(365, (int) $row->cycle_days);

        // The money arrives: the add-on runs until the package's expiry.
        $this->service()->processPaymentCompletion($payment);
        $row->refresh();
        $this->assertSame(UserRentalAddon::STATUS_ACTIVE, $row->status);
        $this->assertTrue($row->expires_at->equalTo($rental->fresh()->expires_at));
        Queue::assertPushed(SendMlmWebhook::class, fn (SendMlmWebhook $job) => $job->event === 'addon.activated'
            && $job->payload['rental']['addons'] === [['code' => 'mlm-auto-verify', 'quantity' => 1, 'expires_at' => $row->expires_at->toIso8601String()]]);
    }

    public function test_an_addon_paid_through_yesterdays_stripe_intent_is_still_delivered(): void
    {
        $user = User::factory()->create();
        $this->at('2027-01-01 10:00');
        $rental = $this->rentMlm($user, 'yearly');

        $this->at('2027-07-01 12:00');
        $payment = $this->service()->createAddonPurchase($user, $rental, ['mlm-auto-verify' => 1], 'stripe')['payment'];
        $payment->update(['stripe_payment_intent_id' => 'pi_day_one']);
        $otherUser = User::factory()->create();
        $other = $this->service()->createAddonPurchase($otherUser, $this->rentMlm($otherUser, 'yearly'), ['mlm-custom-domain' => 1], 'stripe')['payment'];

        // Re-priced on 3 Jul: the day-one intent is no longer offered, but it is remembered.
        $this->at('2027-07-03 09:00');
        $this->assertSame('repriced', $this->service()->refreshAddonQuote($payment->fresh()));
        $payment->refresh();
        $this->assertNull($payment->stripe_payment_intent_id);
        $this->assertSame(['pi_day_one'], $payment->metadata['stripe_superseded_intents']);

        config(['stripe.secret_key' => 'sk_test_unused']); // the controller's StripeService is built, never called
        $controller = app(StripeWebhookController::class);
        $confirm = new \ReflectionMethod($controller, 'confirmRentalPayment');
        $intent = fn (string $id, int $paymentId) => PaymentIntent::constructFrom([
            'id' => $id, 'amount_received' => 146191, 'payment_method' => 'pm_test',
            'metadata' => ['type' => 'rental', 'payment_id' => (string) $paymentId],
        ]);

        // An intent that never belonged to a payment does not complete it…
        $confirm->invoke($controller, $intent('pi_not_ours', $other->id));
        $this->assertSame(RentalPayment::STATUS_PENDING, $other->fresh()->status);

        // …the superseded one does: the customer paid, the add-on runs.
        $confirm->invoke($controller, $intent('pi_day_one', $payment->id));
        $this->assertSame(RentalPayment::STATUS_COMPLETED, $payment->fresh()->status);
        $this->assertSame(UserRentalAddon::STATUS_ACTIVE, UserRentalAddon::where('rental_payment_id', $payment->id)->value('status'));
    }

    public function test_days_left_counts_thai_calendar_days_not_utc_days(): void
    {
        $user = User::factory()->create();
        $this->at('2027-01-01 10:00');
        $rental = $this->rentMlm($user, 'yearly');

        // 23:30 on 1 Jul in Bangkok is still 1 Jul: 184 days.
        $this->at('2027-07-01 23:30');
        $this->assertSame(184, RentalAddonPricing::daysLeft($rental));
        // 00:30 on 2 Jul in Bangkok is 1 Jul 17:30 UTC — but it is already 2 Jul here: 183 days.
        $this->at('2027-07-02 00:30');
        $this->assertSame(183, RentalAddonPricing::daysLeft($rental));
    }

    public function test_a_quote_left_unpaid_until_the_package_runs_out_is_cancelled(): void
    {
        $user = User::factory()->create();
        $this->at('2027-01-01 10:00');
        $rental = $this->rentMlm($user, 'monthly');

        $this->at('2027-01-20 10:00');
        $payment = $this->service()->createAddonPurchase($user, $rental, ['mlm-custom-domain' => 1], 'bank_transfer')['payment'];

        $this->at('2027-02-01 11:00');
        $this->assertSame('lapsed', $this->service()->refreshAddonQuote($payment->fresh()));
        $this->assertSame(RentalPayment::STATUS_CANCELLED, $payment->fresh()->status);
        $this->assertSame(UserRentalAddon::STATUS_CANCELLED, UserRentalAddon::where('rental_payment_id', $payment->id)->value('status'));
    }

    // ── checkout from the MLM site's link ───────────────────────────────────────────────────────

    public function test_checkout_link_with_cycle_addons_and_reservation_shows_them(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/rental/checkout/mlm-core?cycle=yearly&addons[]=mlm-members-5k&mlm_reservation=shop_abc-123')
            ->assertOk()
            ->assertSee('ตัวเลือกเสริม')
            ->assertSee('value="shop_abc-123"', false);
    }

    public function test_checkout_stores_the_cycle_addons_and_reservation_and_charges_the_sum(): void
    {
        $user = User::factory()->create();
        $yearly = RentalPackage::findForCheckout('mlm-core', 'yearly');

        $response = $this->actingAs($user)->post(route('rental.process'), [
            'package_id' => $yearly->id,
            'payment_method' => 'bank_transfer',
            'addons' => ['mlm-members-5k' => 1, 'mlm-gateway' => 2, 'mlm-ocpb-kit' => 0],
            'mlm_reservation' => 'shop_abc-123',
        ]);

        $rental = UserRental::where('user_id', $user->id)->firstOrFail();
        $payment = $rental->payments()->firstOrFail();
        $response->assertRedirect(route('rental.payment', $payment->uuid));

        // 6,900 + 1,900 + 2 × 1,900 (yearly add-on = 10 × monthly).
        $this->assertSame('12600.00', (string) $payment->amount);
        $this->assertSame('shop_abc-123', $rental->metadata['mlm_reservation']);
        $this->assertSame('yearly', $rental->metadata['billing_cycle']);
        $this->assertSame(UserRental::STATUS_PENDING, $rental->status);
        $this->assertSame(
            ['mlm-gateway' => 2, 'mlm-members-5k' => 1],
            $rental->addons()->with('addon')->get()->mapWithKeys(fn ($r) => [$r->addon->code => (int) $r->quantity])->sortKeys()->all()
        );
        $this->assertSame(2, $rental->addons()->where('status', UserRentalAddon::STATUS_PENDING)->count());

        $this->actingAs($user)->get(route('rental.payment', $payment->uuid))
            ->assertOk()
            ->assertSee('฿12,600');

        // Paid: the rental, its add-ons, and one rental.activated for the MLM site.
        $this->service()->processPaymentCompletion($payment);
        $rental->refresh();
        $this->assertSame(UserRental::STATUS_ACTIVE, $rental->status);
        $this->assertSame(0, $rental->addons()->where('status', '!=', UserRentalAddon::STATUS_ACTIVE)->count());
        Queue::assertPushed(SendMlmWebhook::class, 1);
        Queue::assertPushed(SendMlmWebhook::class, fn (SendMlmWebhook $job) => $job->event === 'rental.activated'
            && $job->payload['tenant']['reservation'] === 'shop_abc-123');
    }

    public function test_a_reservation_token_outside_the_allowed_characters_is_refused(): void
    {
        $user = User::factory()->create();
        $monthly = RentalPackage::findForCheckout('mlm-core', 'monthly');

        foreach (['bad token', str_repeat('a', 65), 'a/b', '<script>'] as $token) {
            $this->actingAs($user)->post(route('rental.process'), [
                'package_id' => $monthly->id, 'payment_method' => 'bank_transfer', 'mlm_reservation' => $token,
            ])->assertSessionHasErrors('mlm_reservation');
        }
        $this->assertSame(0, UserRental::count());
    }

    public function test_two_tiers_of_the_same_group_cannot_be_bought_together(): void
    {
        $user = User::factory()->create();
        $result = $this->service()->createRental(
            $user, RentalPackage::findForCheckout('mlm-core', 'monthly'), null, 'bank_transfer',
            ['addons' => ['mlm-members-5k' => 1, 'mlm-members-20k' => 1]]
        );

        $this->assertFalse($result['success']);
        $this->assertSame(0, UserRental::count());
    }

    public function test_quantity_is_capped_at_the_addon_maximum_and_charged_for_what_is_kept(): void
    {
        $user = User::factory()->create();
        $package = RentalPackage::findForCheckout('mlm-core', 'monthly');

        $this->assertFalse($this->service()->createRental($user, $package, null, 'bank_transfer', ['addons' => ['no-such-addon' => 1]])['success']);

        // Gateway allows 5 units, custom domain 1: a crafted post asking for more gets the maximum.
        $result = $this->service()->createRental($user, $package, null, 'bank_transfer', ['addons' => ['mlm-gateway' => 6, 'mlm-custom-domain' => 2]]);
        $this->assertTrue($result['success'], $result['error'] ?? '');
        $this->assertSame('1730.00', (string) $result['payment']->fresh()->amount); // 690 + 5 × 190 + 90
        $this->assertSame(
            ['mlm-custom-domain' => 1, 'mlm-gateway' => 5],
            $result['rental']->addons()->with('addon')->get()->mapWithKeys(fn ($r) => [$r->addon->code => (int) $r->quantity])->sortKeys()->all()
        );
    }

    // ── one running rental per product ──────────────────────────────────────────────────────────

    public function test_one_active_rental_per_product_and_the_generic_packages_keep_their_own_rule(): void
    {
        $user = User::factory()->create();
        $legacy = $this->legacyPackage();

        // A generic rental running does not stop the MLM one…
        $legacyResult = $this->service()->createRental($user, $legacy, null, 'bank_transfer');
        $this->service()->processPaymentCompletion($legacyResult['payment']);
        $this->rentMlm($user);

        // …a second MLM one is refused, and so is a second generic one (the old rule).
        $this->assertFalse($this->service()->createRental($user, RentalPackage::findForCheckout('mlm-core', 'yearly'), null, 'bank_transfer')['success']);
        $this->assertFalse($this->service()->createRental($user, $legacy, null, 'bank_transfer')['success']);
    }

    public function test_the_checkout_link_for_a_product_already_rented_goes_to_its_addon_page(): void
    {
        $user = User::factory()->create();
        $rental = $this->rentMlm($user);

        $this->actingAs($user)->get('/rental/checkout/mlm-core?addons[]=mlm-flowaccount')
            ->assertRedirect(route('rental.addons', ['rental' => $rental->id, 'addons' => ['mlm-flowaccount' => 1]]));

        $this->actingAs($user)->get(route('rental.addons', ['rental' => $rental->id, 'addons' => ['mlm-flowaccount']]))
            ->assertOk()
            ->assertSee('FlowAccount');
    }

    public function test_an_addon_already_held_cannot_be_bought_again(): void
    {
        $user = User::factory()->create();
        $rental = $this->rentMlm($user, 'monthly', ['mlm-members-5k' => 1]);

        $this->assertFalse($this->service()->createAddonPurchase($user, $rental, ['mlm-members-5k' => 1], 'bank_transfer')['success']);
        $this->assertFalse($this->service()->createAddonPurchase($user, $rental, ['mlm-members-20k' => 1], 'bank_transfer')['success'], 'tier change goes through the team');
        $this->assertTrue($this->service()->createAddonPurchase($user, $rental, ['mlm-custom-domain' => 1], 'bank_transfer')['success']);
    }

    public function test_another_users_rental_pages_are_closed(): void
    {
        $owner = User::factory()->create();
        $rental = $this->rentMlm($owner);
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('rental.addons', $rental))->assertForbidden();
        $this->actingAs($stranger)->get(route('rental.renew', $rental))->assertForbidden();
        $this->actingAs($stranger)->post(route('rental.addons.process', $rental), [
            'payment_method' => 'bank_transfer', 'addons' => ['mlm-custom-domain' => 1],
        ])->assertForbidden();
        $this->assertSame(0, RentalPayment::where('user_id', $stranger->id)->count());
    }

    // ── renewal ─────────────────────────────────────────────────────────────────────────────────

    public function test_renewal_charges_package_plus_addons_and_extends_both_once(): void
    {
        $user = User::factory()->create();
        $this->at('2027-03-10 10:00');
        $rental = $this->rentMlm($user, 'monthly', ['mlm-custom-domain' => 1]);
        $firstExpiry = $rental->expires_at->copy();

        $this->actingAs($user)->get(route('rental.renew', $rental))->assertOk()->assertSee('฿780');

        $this->at('2027-04-05 10:00');
        $result = $this->service()->createRenewal($user, $rental, 'bank_transfer');
        $this->assertTrue($result['success'], $result['error'] ?? '');
        $payment = $result['payment'];
        $this->assertSame('780.00', (string) $payment->fresh()->amount); // 690 + 90

        $this->service()->processPaymentCompletion($payment);
        $payment->fresh()->markAsCompleted(); // a second confirmation must not renew again

        $rental->refresh();
        $this->assertTrue($rental->expires_at->equalTo($firstExpiry->copy()->addMonth()), 'paid early: the new month starts at the old expiry');
        $this->assertTrue($rental->period_starts_at->equalTo($firstExpiry));
        $addon = $rental->addons()->first();
        $this->assertTrue($addon->expires_at->equalTo($rental->expires_at));
        Queue::assertPushed(SendMlmWebhook::class, fn (SendMlmWebhook $job) => $job->event === 'rental.renewed');
        $this->assertCount(1, Queue::pushed(SendMlmWebhook::class, fn (SendMlmWebhook $job) => $job->event === 'rental.renewed'));
    }

    public function test_renewing_monthly_as_yearly_switches_the_package_and_prices_addons_yearly(): void
    {
        $user = User::factory()->create();
        $rental = $this->rentMlm($user, 'monthly', ['mlm-gateway' => 2]);

        $result = $this->service()->createRenewal($user, $rental, 'bank_transfer', 'yearly');
        $this->assertSame('10700.00', (string) $result['payment']->fresh()->amount); // 6,900 + 2 × 1,900

        $this->service()->processPaymentCompletion($result['payment']);
        $this->assertSame('yearly', $rental->fresh()->billingCycle());
    }

    public function test_a_refused_renewal_slip_leaves_the_running_rental_alone(): void
    {
        $user = User::factory()->create();
        $rental = $this->rentMlm($user);
        $payment = $this->service()->createRenewal($user, $rental, 'bank_transfer')['payment'];

        $this->assertTrue($this->service()->rejectPayment($payment, 'ยอดไม่ตรง'));

        $this->assertSame(UserRental::STATUS_ACTIVE, $rental->fresh()->status);
        Queue::assertNotPushed(SendMlmWebhook::class, fn (SendMlmWebhook $job) => $job->event === 'rental.cancelled');
    }

    public function test_an_expired_rental_can_be_renewed_from_today(): void
    {
        $user = User::factory()->create();
        $this->at('2027-01-01 10:00');
        $rental = $this->rentMlm($user);

        $this->at('2027-03-01 10:00');
        $this->artisan('rentals:expire')->assertSuccessful();
        $this->assertSame(UserRental::STATUS_EXPIRED, $rental->fresh()->status);

        $payment = $this->service()->createRenewal($user, $rental->fresh(), 'bank_transfer')['payment'];
        $this->service()->processPaymentCompletion($payment);

        $rental->refresh();
        $this->assertSame(UserRental::STATUS_ACTIVE, $rental->status);
        $this->assertTrue($rental->expires_at->equalTo(now()->addMonth()));
    }

    // ── expiry and the pages ────────────────────────────────────────────────────────────────────

    public function test_rentals_expire_marks_only_rentals_past_their_date(): void
    {
        $user = User::factory()->create();
        $this->at('2027-01-01 10:00');
        $mlm = $this->rentMlm($user);
        $legacyResult = $this->service()->createRental(User::factory()->create(), $this->legacyPackage(), null, 'bank_transfer');
        $this->service()->processPaymentCompletion($legacyResult['payment']);

        $this->at('2027-01-20 10:00');
        $this->artisan('rentals:expire')->assertSuccessful();
        $this->assertSame(UserRental::STATUS_ACTIVE, $mlm->fresh()->status);

        $this->at('2027-02-02 10:00');
        $this->artisan('rentals:expire')->expectsOutput('Expired 2 rental(s).')->assertSuccessful();
        $this->assertSame(UserRental::STATUS_EXPIRED, $mlm->fresh()->status);
        $this->assertSame(UserRental::STATUS_EXPIRED, $legacyResult['rental']->fresh()->status);
        $this->assertCount(1, Queue::pushed(SendMlmWebhook::class, fn (SendMlmWebhook $job) => $job->event === 'rental.expired'));
    }

    public function test_the_status_and_package_pages_show_the_mlm_rental(): void
    {
        $user = User::factory()->create();
        $this->rentMlm($user, 'monthly', ['mlm-custom-domain' => 1]);

        $this->actingAs($user)->get(route('rental.status'))
            ->assertOk()
            ->assertSee('ต่ออายุ')
            ->assertSee('โดเมนของคุณเอง');

        $this->get(route('rental.index'))->assertOk()->assertSee('XMAN MLM');
    }

    public function test_the_admin_pages_show_the_mlm_package_and_an_extension_moves_the_addons_too(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $rental = $this->rentMlm($customer, 'monthly', ['mlm-custom-domain' => 1], 'resv_9');
        $monthly = RentalPackage::findForCheckout('mlm-core', 'monthly');

        $this->actingAs($admin)->get(route('admin.rentals.packages'))->assertOk()->assertSee('mlm-core');
        $this->actingAs($admin)->get(route('admin.rentals.packages.edit', $monthly))->assertOk()->assertSee('ยังเป็นราคาเสนอรอบแรก');
        $this->actingAs($admin)->get(route('admin.rentals.show', $rental))->assertOk()->assertSee('resv_9')->assertSee($rental->uuid);

        // Clearing the proposal box on the package form settles the price.
        $this->actingAs($admin)->put(route('admin.rentals.packages.update', $monthly), [
            'name' => $monthly->name, 'name_th' => $monthly->name_th, 'price' => '690',
            'duration_type' => 'monthly', 'duration_value' => 1, 'price_is_proposal' => '0',
        ])->assertRedirect(route('admin.rentals.packages'));
        $this->assertFalse($monthly->fresh()->price_is_proposal);

        $before = $rental->expires_at->copy();
        $this->actingAs($admin)->post(route('admin.rentals.extend', $rental), ['days' => 7, 'reason' => 'ชดเชยระบบล่ม'])->assertSessionHasNoErrors();
        $rental->refresh();
        $this->assertTrue($rental->expires_at->equalTo($before->copy()->addDays(7)));
        $this->assertTrue($rental->addons()->first()->expires_at->equalTo($rental->expires_at));
        Queue::assertPushed(SendMlmWebhook::class, fn (SendMlmWebhook $job) => $job->event === 'rental.renewed');
    }

    public function test_the_admin_edits_an_addon_price_in_baht_and_it_is_stored_in_satang(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $addon = RentalAddon::where('code', 'mlm-gateway')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.rentals.addons'))->assertOk()->assertSee('mlm-gateway');

        $this->actingAs($admin)->put(route('admin.rentals.addons.update', $addon), [
            'name_th' => 'ช่องทางรับเงินเพิ่ม',
            'price_monthly' => '250.50',
            'price_yearly' => '2,505',
            'max_quantity' => 5,
            'sort_order' => 10,
            'is_active' => '1',
        ])->assertSessionHas('success');

        $addon->refresh();
        $this->assertSame(25050, (int) $addon->price_monthly_satang);
        $this->assertSame(250500, (int) $addon->price_yearly_satang);
        $this->assertFalse($addon->price_is_proposal, 'unticked = price confirmed');
    }
}
