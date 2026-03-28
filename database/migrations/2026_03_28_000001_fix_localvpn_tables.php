<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix critical LocalVPN schema issues:
 * 1. owner_user_id must be nullable (free users have no user_id)
 * 2. vpn_traffic_logs.network_id must be nullable + SET NULL on delete
 *    (preserve audit history when networks are deleted)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Fix 1: owner_user_id must be nullable for free users (device-only)
        Schema::table('vpn_networks', function (Blueprint $table) {
            $table->foreignId('owner_user_id')->nullable()->change();
        });

        // Fix 2: network_id must be nullable in traffic logs
        // Change from cascadeOnDelete to nullOnDelete to preserve audit history
        Schema::table('vpn_traffic_logs', function (Blueprint $table) {
            $table->dropForeign(['network_id']);
            $table->foreignId('network_id')->nullable()->change();
            $table->foreign('network_id')
                ->references('id')
                ->on('vpn_networks')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vpn_networks', function (Blueprint $table) {
            $table->foreignId('owner_user_id')->nullable(false)->change();
        });

        Schema::table('vpn_traffic_logs', function (Blueprint $table) {
            $table->dropForeign(['network_id']);
            $table->foreignId('network_id')->nullable(false)->change();
            $table->foreign('network_id')
                ->references('id')
                ->on('vpn_networks')
                ->cascadeOnDelete();
        });
    }
};
