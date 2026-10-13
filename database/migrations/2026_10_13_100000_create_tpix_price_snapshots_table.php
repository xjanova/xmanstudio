<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The TPIX price as read from the chain's DEX pool, at most one row a minute.
     *
     * Two jobs: a quote takes the lower of the live pool price and its half-hour average (TWAP on
     * the USD price, so a pump of the thin pool does not buy a cheap quote), and the USD→THB rate
     * of a row read with a LIVE rate (source `dex`) is the "last known rate" a payment may use for
     * up to a day when the FX feeds are down. A row whose rate was itself a fallback is marked
     * `dex_fx_stale` (or `dex_no_fx` with no rate at all) and never serves as that fallback.
     */
    public function up(): void
    {
        if (Schema::hasTable('tpix_price_snapshots')) {
            return;
        }

        Schema::create('tpix_price_snapshots', function (Blueprint $table) {
            $table->id();
            $table->decimal('price_usd', 24, 12);
            $table->decimal('usd_thb', 14, 6)->nullable();
            $table->decimal('price_thb', 24, 12)->nullable();
            $table->decimal('reserve_tpix', 38, 18)->nullable();
            $table->decimal('reserve_usdt', 24, 6)->nullable();
            $table->string('source', 20)->default('dex');
            $table->timestamp('created_at')->nullable()->index();
            $table->index(['source', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpix_price_snapshots');
    }
};
