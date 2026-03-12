<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `license_activities` MODIFY `action` ENUM(
            'created',
            'activated',
            'deactivated',
            'validated',
            'expired',
            'revoked',
            'reactivated',
            'extended',
            'machine_reset',
            'failed_activation',
            'suspicious_activity',
            'deleted'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `license_activities` MODIFY `action` ENUM(
            'created',
            'activated',
            'deactivated',
            'validated',
            'expired',
            'revoked',
            'reactivated',
            'extended',
            'machine_reset',
            'failed_activation',
            'suspicious_activity'
        ) NOT NULL");
    }
};
