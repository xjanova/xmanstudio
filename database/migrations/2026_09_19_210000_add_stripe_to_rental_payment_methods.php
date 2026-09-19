<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Let a rental actually be paid with Stripe.
     *
     * Everything else was already in place: the checkout offers the button whenever
     * StripeService::isEnabled() (it is, on production), the controller validates
     * `in:...,stripe`, RentalPayment has METHOD_STRIPE with a Thai label, and the payment page
     * knows how to confirm the intent. The column was the one thing never widened, so
     * RentalPayment::create(['payment_method' => 'stripe']) hit a value the enum does not contain —
     * a 500 on MySQL under STRICT_TRANS_TABLES, and a silent success on SQLite, which is why no
     * test ever saw it.
     *
     * Found by sweeping every enum column in the live schema against the literals the code writes.
     */
    public function up(): void
    {
        $this->setMethodEnum([
            'promptpay', 'bank_transfer', 'credit_card', 'truemoney', 'linepay', 'manual', 'stripe',
        ]);
    }

    public function down(): void
    {
        // Anything already taken through Stripe is a real payment; call it a card payment rather
        // than dropping the row's method on the floor.
        DB::table('rental_payments')->where('payment_method', 'stripe')->update(['payment_method' => 'credit_card']);

        $this->setMethodEnum(['promptpay', 'bank_transfer', 'credit_card', 'truemoney', 'linepay', 'manual']);
    }

    /**
     * @param  list<string>  $methods
     */
    private function setMethodEnum(array $methods): void
    {
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $values = collect($methods)->map(fn (string $m) => "'{$m}'")->implode(', ');

            DB::statement("ALTER TABLE rental_payments MODIFY COLUMN payment_method ENUM({$values}) NOT NULL");

            return;
        }

        // SQLite has no ENUM: Laravel renders it as `varchar check (col in (...))`, so the column
        // has to be redefined for the added value to pass the CHECK constraint.
        Schema::table('rental_payments', function (Blueprint $table) use ($methods) {
            $table->enum('payment_method', $methods)->change();
        });
    }
};
