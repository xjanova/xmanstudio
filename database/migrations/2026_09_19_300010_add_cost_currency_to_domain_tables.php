<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remember which currency the registrar actually charged us in.
     *
     * The catalogue was assumed to be priced in USD, so everything downstream
     * multiplies the stored cost by the USD→THB rate. Our account is a Thai
     * one: every one of the 1,276 price rows comes back in THB, the sync
     * discarded all of them for having the wrong currency, and the result was a
     * catalogue where no TLD had an item id — which means nothing could be sold
     * at all.
     *
     * Storing the currency is what lets the same code serve both: a THB cost is
     * already in baht and must not be multiplied again, and a USD one still is.
     *
     * The column keeps its cost_usd_cents name. Renaming it would rewrite the
     * purchase history's meaning, and the value has always been minor units of
     * whatever we were billed — only the assumption about which currency was
     * wrong.
     */
    public function up(): void
    {
        Schema::table('domain_tlds', function (Blueprint $table) {
            $table->string('cost_currency', 3)->default('USD')->after('transfer_cost_usd_cents');
        });

        Schema::table('domain_registrations', function (Blueprint $table) {
            // On a registration this is history: what we paid, in what money,
            // at what rate. The margin report reads all three.
            $table->string('cost_currency', 3)->default('USD')->after('cost_usd_cents');
        });
    }

    public function down(): void
    {
        Schema::table('domain_tlds', function (Blueprint $table) {
            $table->dropColumn('cost_currency');
        });

        Schema::table('domain_registrations', function (Blueprint $table) {
            $table->dropColumn('cost_currency');
        });
    }
};
