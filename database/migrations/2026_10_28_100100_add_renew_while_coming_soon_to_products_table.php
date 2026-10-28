<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Coming Soon closes a product to buyers (ComingSoonPurchase). This switch, set per product in
 * the admin, lets customers who already hold a key still renew it meanwhile — from the license
 * page, through the cart, and by auto-renew. Off for every product, so nothing changes until the
 * owner turns it on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('renew_while_coming_soon')->default(false)->after('coming_soon_until');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('renew_while_coming_soon');
        });
    }
};
