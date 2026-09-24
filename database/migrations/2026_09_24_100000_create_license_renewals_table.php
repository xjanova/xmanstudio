<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row each time a paid order item extends a key the customer already holds
 * (a renewable product — config/licenses.php).
 *
 * The unique order_item_id is what keeps a payment callback that arrives twice
 * (the Stripe webhook and the checkout page's own poll, an SMS match and an
 * admin click) from extending the key twice: the second insert fails and that
 * delivery does nothing. The rows also let the order page and the confirmation
 * e-mail show the key an order renewed, since that key's order_id stays the
 * order that first issued it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_key_id')->constrained('license_keys')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // The term bought (monthly …), not an enum: nothing here needs the column to police it
            $table->string('license_type', 20);
            $table->unsignedSmallInteger('units');
            $table->unsignedInteger('days_added');
            $table->timestamp('previous_expires_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_renewals');
    }
};
