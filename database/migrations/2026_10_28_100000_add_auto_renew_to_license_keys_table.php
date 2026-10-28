<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Automatic renewal of a license from the customer's wallet (licenses:renew).
 *
 *   auto_renew     — the customer switched it on in /my-account/licenses/{id}; off for every
 *                    existing key, so nobody is charged for something they did not ask for.
 *   renewal_mails  — the renewal e-mails already sent, each tagged with the expiry it was about
 *                    ("notice:2026-11-02"). Once the key is renewed its expiry moves, the tags no
 *                    longer match, and the next period starts with nothing sent — no reset needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('license_keys', function (Blueprint $table) {
            $table->boolean('auto_renew')->default(false)->after('expires_at');
            $table->json('renewal_mails')->nullable()->after('auto_renew');
            $table->index(['auto_renew', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('license_keys', function (Blueprint $table) {
            $table->dropIndex(['auto_renew', 'expires_at']);
            $table->dropColumn(['auto_renew', 'renewal_mails']);
        });
    }
};
