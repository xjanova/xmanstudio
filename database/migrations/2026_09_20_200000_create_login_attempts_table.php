<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every sign-in attempt, kept.
 *
 * The Telegram alerts already shout when someone is guessing, but a card in a
 * chat is gone the moment it scrolls. There was no way to answer "who has been
 * trying to get into the admin account this week" after the fact, and no way to
 * see an attempt that never crossed an alert threshold.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();

            // What was typed, not who it resolved to — a run of attempts on an
            // address that does not exist is itself the signal.
            $table->string('email')->nullable()->index();

            // Set only when the address matched a real account, so the log can
            // survive that account being deleted.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('ip', 45)->nullable();

            // Cloudflare's CF-IPCountry. Two letters, or XX when Cloudflare
            // could not tell (and null when the request did not come through it).
            $table->string('country', 2)->nullable();

            $table->string('user_agent', 512)->nullable();

            // success · failed · lockout · blocked · turnstile
            $table->string('outcome', 20)->index();

            // password · line · google · telegram · sso
            $table->string('method', 20)->default('password');

            // Was the target an admin account? Denormalised on purpose: the
            // dashboard filters on it constantly and the user row may be gone.
            $table->boolean('is_admin_target')->default(false);

            $table->timestamp('created_at')->nullable();

            // The two questions the dashboard and the auto-blocker ask:
            // "what has this address been doing lately" and "what happened lately".
            $table->index(['ip', 'created_at']);
            $table->index(['outcome', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
