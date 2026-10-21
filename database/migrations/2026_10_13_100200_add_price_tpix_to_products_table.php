<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An optional fixed TPIX price for a product, set by the admin (the shop is the only seller, so
     * honouring it is safe). Empty = the product is priced in THB and converted at the live rate.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'price_tpix')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('price_tpix', 30, 8)->nullable()->after('price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'price_tpix')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('price_tpix');
            });
        }
    }
};
