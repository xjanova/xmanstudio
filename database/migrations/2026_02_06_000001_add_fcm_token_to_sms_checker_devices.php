<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม fcm_token ให้ sms_checker_devices
 * เพื่อรองรับ Firebase Cloud Messaging push notifications
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_checker_devices', function (Blueprint $table) {
            $table->string('fcm_token', 255)->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('sms_checker_devices', function (Blueprint $table) {
            $table->dropColumn('fcm_token');
        });
    }
};
