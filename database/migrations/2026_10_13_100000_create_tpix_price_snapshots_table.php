<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The TPIX price as read from the chain's DEX pool, at most one row a minute.
     *
     * Two jobs: a quote takes the lower of the live price and the last half hour's average (so a
     * pump of the thin pool does not buy a cheap quote), and the USD→THB rate stored here is the
     * "last known rate" a payment may use for up to a day when the FX feeds are down.
     */
    public function up(): void
    {
        if (Schema::hasTable('tpix_price_snapshots')) {
            return;
        }

        Schema::create('tpix_price_snapshots', function (Blueprint $table) {
            $table->id();
            $table->decimal('price_usd', 24, 12);
            $table->decimal('usd_thb', 14, 6);
            $table->decimal('price_thb', 24, 12);
            $table->decimal('reserve_tpix', 38, 18)->nullable();
            $table->decimal('reserve_usdt', 24, 6)->nullable();
            $table->string('source', 20)->default('dex');
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpix_price_snapshots');
    }
};
