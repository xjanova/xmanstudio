<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add android_id column to product_devices and license_keys tables.
 *
 * android_id = Android ANDROID_ID (Settings.Secure.ANDROID_ID)
 * Stable across app reinstall within the same signing key (Android 8+).
 * Used as tertiary fallback lookup key in check-machine, alongside drm_id.
 *
 * Lookup priority: machine_id → drm_id → android_id
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_devices', function (Blueprint $table) {
            // Android ANDROID_ID — stable across reinstall, scoped to signing key
            $table->string('android_id', 64)->nullable()->after('drm_id')->index();
        });

        Schema::table('license_keys', function (Blueprint $table) {
            // Store android_id for cross-HWID fallback lookup
            $table->string('android_id', 64)->nullable()->after('drm_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('product_devices', function (Blueprint $table) {
            $table->dropIndex(['android_id']);
            $table->dropColumn('android_id');
        });

        Schema::table('license_keys', function (Blueprint $table) {
            $table->dropIndex(['android_id']);
            $table->dropColumn('android_id');
        });
    }
};
