<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add drm_id column to product_devices and license_keys tables.
 *
 * MediaDrm (Widevine) device ID is stable across app reinstall and factory reset.
 * Used as a secondary lookup key for license auto-activation when machine_id
 * changes (e.g. HWID formula migration from ANDROID_ID to MediaDrm).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_devices', function (Blueprint $table) {
            $table->string('drm_id', 128)->nullable()->after('hardware_hash')->index();
        });

        Schema::table('license_keys', function (Blueprint $table) {
            $table->string('drm_id', 128)->nullable()->after('machine_fingerprint')->index();
        });
    }

    public function down(): void
    {
        Schema::table('product_devices', function (Blueprint $table) {
            $table->dropColumn('drm_id');
        });

        Schema::table('license_keys', function (Blueprint $table) {
            $table->dropColumn('drm_id');
        });
    }
};
