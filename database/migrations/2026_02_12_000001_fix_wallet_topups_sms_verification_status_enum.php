<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix: ENUM was missing 'rejected' and 'cancelled' values
        // This caused "Data truncated for column 'sms_verification_status'" errors
        DB::statement("ALTER TABLE `wallet_topups` MODIFY COLUMN `sms_verification_status` ENUM('pending', 'matched', 'confirmed', 'rejected', 'cancelled', 'timeout') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `wallet_topups` MODIFY COLUMN `sms_verification_status` ENUM('pending', 'matched', 'confirmed', 'timeout') NOT NULL DEFAULT 'pending'");
    }
};
