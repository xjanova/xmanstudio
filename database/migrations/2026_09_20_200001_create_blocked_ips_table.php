<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Addresses that are refused outright, automatically or by hand.
 *
 * The login throttle stops five wrong passwords a minute and then opens the
 * door again, forever. That is the right shape for someone who mistyped, and
 * the wrong shape for a script that is happy to spend all night at five a
 * minute. A block is what turns "slow" into "no".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->unique();

            $table->string('reason', 255)->nullable();

            // auto · manual — an operator's block must not be lifted by the
            // automatic expiry sweep, and the dashboard colours them apart.
            $table->string('source', 10)->default('auto');

            // Null means permanent. Automatic blocks always set one.
            $table->timestamp('expires_at')->nullable()->index();

            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();

            // Who pressed the button, when it was by hand.
            $table->foreignId('blocked_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_ips');
    }
};
