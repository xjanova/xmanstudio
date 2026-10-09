<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * orders.payment_slip — the slip a customer attaches on the order page (POST /orders/{order}/confirm-payment).
 *
 * OrderController::confirmPayment has always written this column, and the order page, the admin
 * order page and the Telegram card all read it, but no migration ever created it: on a database
 * built from the migrations, every slip upload for a cart order (bank transfer / PromptPay)
 * failed with "no such column: payment_slip". Found 2026-10-09 while adding the DGX Spark bundle,
 * whose customers pay ฿265,000 by transfer and must be able to attach the slip.
 *
 * Guarded with hasColumn: a database that got the column some other way is left as it is.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'payment_slip')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_slip')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        // Slips customers already attached would go with the column — nothing to undo safely.
    }
};
