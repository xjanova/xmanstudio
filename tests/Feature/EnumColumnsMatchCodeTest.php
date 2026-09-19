<?php

namespace Tests\Feature;

use App\Models\Quotation;
use App\Models\RentalPackage;
use App\Models\RentalPayment;
use App\Models\User;
use App\Models\UserRental;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every value the code writes into an enum column has to be one the column accepts.
 *
 * This is the bug class that keeps getting through: SQLite stores whatever it is given, so a
 * mismatch passes the whole suite, while production runs MySQL with STRICT_TRANS_TABLES and throws.
 * It has now cost two customer-facing 500s — a quotation that could not be declined, and a rental
 * that could not be paid with Stripe although the button was on the page.
 *
 * READ THIS BEFORE DELETING ANYTHING HERE AS "a test that cannot fail".
 *
 * Locally it cannot: Laravel renders an enum on SQLite as a plain `varchar`, with no CHECK
 * constraint, so every value below stores fine on a developer machine. CI is where it bites —
 * .github/workflows/ci.yml runs the suite against MySQL 8.0, whose default sql_mode includes
 * STRICT_TRANS_TABLES, and there a value the column does not list is an error. That is the same
 * engine and the same mode production runs, so a red build here means a 500 avoided.
 *
 * Which is also why writing each value matters more than asserting a list against itself: the
 * column is the thing being tested, not the constant.
 */
class EnumColumnsMatchCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_payment_method_the_code_knows_can_be_stored(): void
    {
        $user = User::factory()->create();
        $package = RentalPackage::create([
            'name' => 'test-pack',
            'display_name' => 'แพ็กทดสอบ',
            'price' => 100,
            'duration_type' => 'monthly',
            'duration_value' => 1,
        ]);
        $rental = UserRental::create([
            'user_id' => $user->id,
            'rental_package_id' => $package->id,
            'status' => 'pending',
            'amount_paid' => 100,
        ]);

        $methods = [
            RentalPayment::METHOD_PROMPTPAY,
            RentalPayment::METHOD_BANK_TRANSFER,
            RentalPayment::METHOD_CREDIT_CARD,
            RentalPayment::METHOD_TRUEMONEY,
            RentalPayment::METHOD_LINEPAY,
            RentalPayment::METHOD_MANUAL,
            // The one the checkout offers whenever Stripe is enabled, and the column refused.
            RentalPayment::METHOD_STRIPE,
        ];

        foreach ($methods as $method) {
            $payment = RentalPayment::create([
                'user_id' => $user->id,
                'user_rental_id' => $rental->id,
                'amount' => 100,
                'net_amount' => 100,
                'status' => RentalPayment::STATUS_PENDING,
                'payment_method' => $method,
                'description' => 'test',
            ]);

            $this->assertSame($method, $payment->fresh()->payment_method, "the column rejected {$method}");
        }
    }

    public function test_the_rental_checkout_only_accepts_methods_the_column_holds(): void
    {
        // The validation rule and the column drifted apart once: the rule allowed stripe months
        // before the column did.
        $source = file_get_contents(app_path('Http/Controllers/RentalController.php'));

        preg_match("/'payment_method' => 'required\|in:([^']+)'/", $source, $m);
        $this->assertNotEmpty($m, 'the checkout must validate the payment method');

        $allowed = [
            RentalPayment::METHOD_PROMPTPAY,
            RentalPayment::METHOD_BANK_TRANSFER,
            RentalPayment::METHOD_CREDIT_CARD,
            RentalPayment::METHOD_TRUEMONEY,
            RentalPayment::METHOD_LINEPAY,
            RentalPayment::METHOD_MANUAL,
            RentalPayment::METHOD_STRIPE,
        ];

        foreach (explode(',', $m[1]) as $method) {
            $this->assertContains(trim($method), $allowed, 'the form accepts a method the column does not');
        }
    }

    public function test_every_quotation_status_the_code_knows_can_be_stored(): void
    {
        $quotation = Quotation::create([
            'quote_number' => 'QT-ENUM-TEST',
            'customer_name' => 'ทดสอบ',
            'customer_email' => 'test@example.com',
            'customer_phone' => '0810000000',
            'service_type' => 'web',
            'service_name' => 'เว็บไซต์',
            'service_options' => [],
            'subtotal' => 1000,
            'vat' => 70,
            'grand_total' => 1070,
            'valid_until' => now()->addDays(30),
        ]);

        foreach (Quotation::STATUSES as $status) {
            $quotation->update(['status' => $status]);
            $this->assertSame($status, $quotation->fresh()->status, "the column rejected {$status}");
        }
    }
}
