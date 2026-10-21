<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LocalVPN relay fleet — the rented VPSes that carry relay / game-exit / VPN
 * traffic (docs/RELAY_FLEET.md in the LocalVPN app repo).
 *
 * Each node authenticates its sync with an HMAC over its own secret; the
 * secret is stored with Laravel's encrypted cast. last_sync_time is the last
 * accepted X-Node-Time, kept so a captured sync cannot be replayed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vpn_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 32)->unique();
            $table->char('country', 2);
            $table->string('city', 100)->nullable();
            $table->string('provider', 100)->nullable();
            $table->string('host', 255);
            $table->unsignedInteger('relay_port')->default(51821);
            $table->unsignedInteger('wg_port')->default(51820);
            $table->string('wg_public_key', 64)->nullable();
            $table->json('roles');
            $table->string('status', 16)->default('active');
            $table->text('secret');
            $table->decimal('monthly_cost_thb', 12, 2)->default(0);
            $table->unsignedInteger('included_gb')->default(1000);
            $table->decimal('overage_thb_per_gb', 10, 4)->default(0);
            $table->unsignedTinyInteger('billing_day')->default(1);
            $table->string('billing_mode', 8)->default('out');
            $table->boolean('allow_overage')->default(false);
            $table->unsignedInteger('max_clients')->default(2000);
            $table->unsignedInteger('weight')->default(100);
            $table->unsignedInteger('current_clients')->default(0);
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedBigInteger('last_sync_time')->default(0);
            $table->json('last_sync')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vpn_nodes');
    }
};
