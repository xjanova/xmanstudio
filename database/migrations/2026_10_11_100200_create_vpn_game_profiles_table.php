<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Game server address ranges for the "game" exit (ping booster).
 *
 * The app routes only these CIDRs through the exit, and each game node's
 * firewall forwards only to them. Left empty on purpose: the admin fills it in
 * from LocalVPN → Fleet → Policy, so nothing unverified ships as a default.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vpn_game_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 64)->unique();
            $table->string('region', 64)->nullable();
            $table->text('cidrs');
            $table->boolean('enabled')->default(true);
            $table->integer('sort')->default(0);
            $table->timestamps();

            $table->index(['enabled', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vpn_game_profiles');
    }
};
