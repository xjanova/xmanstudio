<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row each time a paid order item adds cloud space to a customer's key
 * (a pack from config/licenses.php → cloud_storage.*.addons). The space stays
 * with the key for as long as the key lives (owner, 2026-10-19), so the rows
 * are never expired — App\Support\CloudStorage adds them to the plan's base.
 *
 * The unique order_item_id is what keeps a payment confirmed twice (the
 * Stripe webhook and the checkout page's own poll, an SMS match and an admin
 * click) from adding the space twice, exactly like license_renewals.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_storage_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_key_id')->constrained('license_keys')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('pack_gb');
            $table->unsignedSmallInteger('units');
            $table->unsignedInteger('gb_added');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_storage_addons');
    }
};
