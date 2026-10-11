<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An address inside a game's ranges that answers TCP (a login or matchmaking
 * server), so the app can measure the ping through a game exit — the exit
 * forwards nothing outside the game's CIDRs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vpn_game_profiles', function (Blueprint $table) {
            $table->string('probe_host', 32)->nullable()->after('cidrs');
        });
    }

    public function down(): void
    {
        Schema::table('vpn_game_profiles', function (Blueprint $table) {
            $table->dropColumn('probe_host');
        });
    }
};
