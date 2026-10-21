<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PDPA support for voice uploaded by the Aipray app (1.2.5+).
 *
 * Each install sends a random contributor_id and the SHA-256 of a secret that
 * never leaves the phone except in an erase request, so only that phone can
 * erase its own voice. client_sample_id makes a retried upload idempotent.
 *
 * Dated 2026-09-12 but first deployed after many later migrations, so each
 * step checks before it acts; every added column is nullable, which strict
 * MySQL/MariaDB accept on a table that already has rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('aipray_contributors')) {
            Schema::create('aipray_contributors', function (Blueprint $table) {
                $table->id();
                $table->string('contributor_id', 36)->unique();
                // Bound by the first upload that carries this contributor_id.
                $table->string('key_hash', 64);
                $table->timestamps();
            });
        }

        $missing = fn (string $column) => ! Schema::hasColumn('aipray_audio_samples', $column);

        Schema::table('aipray_audio_samples', function (Blueprint $table) use ($missing) {
            if ($missing('client_sample_id')) {
                $table->string('client_sample_id', 36)->nullable()->unique();
            }
            if ($missing('contributor_id')) {
                $table->string('contributor_id', 36)->nullable()->index();
            }
            if ($missing('consent_version')) {
                $table->string('consent_version', 32)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('aipray_audio_samples', 'client_sample_id')) {
            Schema::table('aipray_audio_samples', function (Blueprint $table) {
                $table->dropUnique(['client_sample_id']);
                $table->dropIndex(['contributor_id']);
                $table->dropColumn(['client_sample_id', 'contributor_id', 'consent_version']);
            });
        }

        Schema::dropIfExists('aipray_contributors');
    }
};
