<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every AI call the GigGok app makes through our server.
 *
 * This exists to protect the bill, not for analytics. The app authenticates
 * with its own license key and our OpenAI key does the talking, so anyone who
 * extracts a license key can spend our money until something stops them. The
 * daily cap is counted from these rows.
 *
 * Deliberately a table rather than a cache counter: a cache can be flushed by
 * a deploy, a restart, or someone clearing it during unrelated work, and every
 * one of those silently resets everybody's quota to zero. A row that is already
 * written cannot be un-spent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_ai_usages', function (Blueprint $table) {
            $table->id();

            // Nullable for the same reason download_logs is: a license can be
            // deleted later, and losing the row would lose the spend record.
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('license_key_id')->nullable()->constrained('license_keys')->onDelete('set null');

            // Kept as plain text too, so the count still works after the
            // license row is gone - the FK above goes null, this does not.
            $table->string('license_key', 191)->nullable();

            $table->string('provider', 32)->nullable();
            $table->string('model', 128)->nullable();
            $table->unsignedInteger('message_count')->default(0);
            $table->unsignedInteger('chars_in')->default(0);
            $table->unsignedInteger('chars_out')->default(0);
            $table->boolean('ok')->default(true);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            // The quota query is "rows for this license since midnight".
            $table->index(['license_key', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_ai_usages');
    }
};
