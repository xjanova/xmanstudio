<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A customer's own Cloudflare account, connected by an API token they made.
 *
 * With it the domain page can add the site to their Cloudflare, read the
 * nameservers Cloudflare assigned and switch to them, and lay down a ready
 * setup (VPS, Vercel, Google Workspace…) — instead of the customer copying
 * names between two dashboards. The token is theirs, scoped by them, stored
 * encrypted, and never shown again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cloudflare_connections')) {
            return;
        }

        Schema::create('cloudflare_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('api_token');
            $table->string('token_hint', 8)->nullable();
            $table->string('account_id', 64)->nullable();
            $table->string('account_name')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cloudflare_connections');
    }
};
