<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Browsers an admin ticked "remember this device" on at the second step
 * (App\Support\Auth\TwoFactor::trustDevice). The browser keeps a random token
 * in a cookie; only its hash is kept here, so a copy of this table opens nothing.
 * secret_fingerprint ties the row to the authenticator it was passed with —
 * moving to a new phone quietly retires every remembered browser.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('two_factor_trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('secret_fingerprint', 64);
            $table->string('label', 120)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('last_used_at')->nullable();
            // dateTime, not timestamp: a NOT NULL timestamp with no default is refused by
            // production MySQL ("Invalid default value") — SQLite in the tests never notices.
            $table->dateTime('expires_at');
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('two_factor_trusted_devices');
    }
};
