<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An AI-credit order (ai.xman4289.com credit packages, AiCreditOrders) has one line for a virtual
 * product — there is no row in `products` for it, so its order_items.product_id is NULL. The column
 * was created NOT NULL, which refused that line: every AI-credit checkout stopped at "insert into
 * order_items" with the order row already written. Nullable lets the line exist; the foreign key
 * (cascade on product delete) stays as it was for every real product.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Lines without a product cannot go back under NOT NULL; leave the column as it is then
        if (DB::table('order_items')->whereNull('product_id')->exists()) {
            return;
        }

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });
    }
};
