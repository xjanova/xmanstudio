<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Usage counters the relay fleet reports on every sync (as deltas).
 *
 * vpn_node_usage_daily — one row per node per day (Asia/Bangkok): the NIC
 * counters the budget is measured against, plus relay/exit totals.
 *
 * vpn_user_usage_daily — one row per device per day per kind (relay | exit).
 * Daily caps are checked against it. Keyed by machine_id because that is what
 * both the relay (network + virtual IP → member) and the exit (WireGuard key →
 * client) resolve to; license_key_id records which license the device was on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vpn_node_usage_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained('vpn_nodes')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('nic_rx')->default(0);
            $table->unsignedBigInteger('nic_tx')->default(0);
            $table->unsignedBigInteger('relay_bytes')->default(0);
            $table->unsignedBigInteger('exit_bytes')->default(0);
            $table->unsignedInteger('peak_clients')->default(0);
            $table->timestamps();

            $table->unique(['node_id', 'date']);
            $table->index('date');
        });

        Schema::create('vpn_user_usage_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('kind', 8);
            $table->string('machine_id', 255);
            $table->foreignId('license_key_id')->nullable()->constrained('license_keys')->nullOnDelete();
            $table->unsignedBigInteger('bytes')->default(0);
            $table->timestamps();

            $table->unique(['date', 'kind', 'machine_id']);
            $table->index('license_key_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vpn_user_usage_daily');
        Schema::dropIfExists('vpn_node_usage_daily');
    }
};
