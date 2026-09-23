<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The VPS plans we resell, mirrored from the supplier's catalogue.
     *
     * Like the domain TLD table, this stores what a plan COSTS us and never
     * what we charge: the selling price is derived from cost × margin every
     * time it is shown, so the plan page, the order form and the renewal
     * notice cannot quote three different numbers.
     *
     * `prices` holds one entry per billing period the catalogue offers
     * ("1m", "1y", "2y"), each with its own catalogue price id — the id is what
     * a purchase must send, and it is per period, not per plan. The first
     * period and the renewals are priced separately because they are: the
     * first month of a KVM 2 costs less than half of every month after it, and
     * a shop that charges the first-month price forever loses money from month
     * two.
     *
     * `remote_name` is the supplier's name for the plan ("KVM 2"). It is kept
     * for the admin page and never shown to a customer — `name` is ours.
     */
    public function up(): void
    {
        Schema::create('vps_plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('remote_item_id', 120);
            $table->string('remote_name', 120)->nullable();
            $table->string('name', 120);
            // 'vps' or 'game' — game-panel machines are the same hardware
            // sold with a panel, and arrive switched off.
            $table->string('category', 16)->default('vps');

            $table->unsignedSmallInteger('cpus')->default(1);
            $table->unsignedInteger('memory_mb')->default(0);
            $table->unsignedInteger('disk_mb')->default(0);
            $table->unsignedBigInteger('bandwidth_mb')->default(0);
            $table->unsignedInteger('network_mbps')->nullable();

            $table->json('prices')->nullable();
            $table->decimal('margin_percent', 6, 2)->nullable();

            $table->boolean('is_active')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(100);
            $table->text('description_th')->nullable();
            $table->text('description_en')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vps_plans');
    }
};
