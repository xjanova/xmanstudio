<?php

namespace Tests\Feature;

use App\Models\RentalInvoice;
use App\Models\RentalPackage;
use App\Models\RentalPayment;
use App\Models\User;
use App\Models\UserRental;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Confirming and refusing rental payments from the admin rental payments page.
 *
 * The page's buttons posted to /admin/rentals/payments/{id}/verify|reject — URLs no route
 * answered — while the route that existed pointed at a controller method that did not, so no
 * rental payment could be confirmed from the website at all. And the rental detail page linked a
 * route name that did not exist, so a suspended rental's page could not even be opened.
 */
class AdminRentalPaymentsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:UserRental,1:RentalPayment} */
    private function pendingRental(string $paymentStatus = RentalPayment::STATUS_PROCESSING): array
    {
        $customer = User::factory()->create();
        $package = RentalPackage::create([
            'name' => 'Pro Monthly', 'name_th' => 'โปรรายเดือน', 'price' => 990,
            'duration_type' => 'monthly', 'duration_value' => 1, 'is_active' => true,
        ]);
        $rental = UserRental::create([
            'user_id' => $customer->id, 'rental_package_id' => $package->id,
            'status' => UserRental::STATUS_PENDING, 'amount_paid' => 990, 'payment_method' => 'bank_transfer',
        ]);
        $payment = RentalPayment::create([
            'user_id' => $customer->id, 'user_rental_id' => $rental->id,
            'amount' => 990, 'payment_method' => RentalPayment::METHOD_BANK_TRANSFER,
            'status' => $paymentStatus, 'transfer_slip_url' => 'payment-slips/slip.jpg',
        ]);

        return [$rental, $payment];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_the_payments_page_posts_to_routes_that_exist(): void
    {
        [, $payment] = $this->pendingRental();

        $this->actingAs($this->admin())->get(route('admin.rentals.payments'))
            ->assertOk()
            ->assertSee('showVerifyModal(' . $payment->id . ')', false)
            ->assertSee(str_replace('/', '\/', route('admin.rentals.payments.verify', ['payment' => '__PAYMENT__'])), false);
    }

    public function test_an_admin_confirms_a_payment_and_the_rental_starts(): void
    {
        [$rental, $payment] = $this->pendingRental();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.rentals.payments.verify', $payment), ['notes' => 'ยอดตรง'])
            ->assertSessionHas('success');

        $payment->refresh();
        $this->assertSame(RentalPayment::STATUS_COMPLETED, $payment->status);
        $this->assertSame($admin->id, (int) $payment->verified_by);
        $this->assertSame(UserRental::STATUS_ACTIVE, $rental->fresh()->status);
        $this->assertSame(1, RentalInvoice::where('rental_payment_id', $payment->id)->count());
    }

    public function test_confirming_twice_issues_one_receipt(): void
    {
        [, $payment] = $this->pendingRental();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.rentals.payments.verify', $payment))->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.rentals.payments.verify', $payment))->assertSessionHas('error');

        $this->assertSame(1, RentalInvoice::where('rental_payment_id', $payment->id)->count());
    }

    public function test_an_admin_rejects_a_payment_with_a_reason_and_the_rental_is_cancelled(): void
    {
        [$rental, $payment] = $this->pendingRental();

        $this->actingAs($this->admin())
            ->post(route('admin.rentals.payments.reject', $payment), ['reason' => 'ไม่พบยอดโอน'])
            ->assertSessionHas('success');

        $this->assertSame(RentalPayment::STATUS_FAILED, $payment->fresh()->status);
        $this->assertSame(UserRental::STATUS_CANCELLED, $rental->fresh()->status);
        $this->assertStringContainsString('ไม่พบยอดโอน', (string) $rental->fresh()->notes);
    }

    public function test_rejecting_needs_a_reason(): void
    {
        [, $payment] = $this->pendingRental();

        $this->actingAs($this->admin())
            ->post(route('admin.rentals.payments.reject', $payment), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame(RentalPayment::STATUS_PROCESSING, $payment->fresh()->status);
    }

    public function test_a_completed_payment_cannot_be_rejected(): void
    {
        [$rental, $payment] = $this->pendingRental();
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.rentals.payments.verify', $payment));

        $this->actingAs($admin)
            ->post(route('admin.rentals.payments.reject', $payment), ['reason' => 'กดผิด'])
            ->assertSessionHas('error');

        $this->assertSame(RentalPayment::STATUS_COMPLETED, $payment->fresh()->status);
        $this->assertSame(UserRental::STATUS_ACTIVE, $rental->fresh()->status, 'a paid, running rental is not cancelled');
    }

    public function test_non_admins_cannot_confirm_or_reject(): void
    {
        [, $payment] = $this->pendingRental();
        $member = User::factory()->create(['role' => 'user']);

        $this->actingAs($member)->post(route('admin.rentals.payments.verify', $payment))->assertForbidden();
        $this->actingAs($member)->post(route('admin.rentals.payments.reject', $payment), ['reason' => 'x'])->assertForbidden();

        $this->assertSame(RentalPayment::STATUS_PROCESSING, $payment->fresh()->status);
    }

    public function test_a_suspended_rental_page_opens_and_can_be_reactivated(): void
    {
        [$rental] = $this->pendingRental();
        $rental->update(['status' => UserRental::STATUS_SUSPENDED, 'starts_at' => now(), 'expires_at' => now()->addMonth()]);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.rentals.show', $rental))
            ->assertOk()
            ->assertSee(route('admin.rentals.reactivate', $rental), false);

        $this->actingAs($admin)->post(route('admin.rentals.reactivate', $rental))->assertSessionHas('success');
        $this->assertSame(UserRental::STATUS_ACTIVE, $rental->fresh()->status);
    }
}
