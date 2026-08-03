<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix: ENUM was missing 'rejected' and 'cancelled' values
        // This caused "Data truncated for column 'sms_verification_status'" errors
        if ($this->usesMySql()) {
            DB::statement("ALTER TABLE `wallet_topups` MODIFY COLUMN `sms_verification_status` ENUM('pending', 'matched', 'confirmed', 'rejected', 'cancelled', 'timeout') NOT NULL DEFAULT 'pending'");

            return;
        }

        // SQLite has no ENUM type: Laravel renders it as `varchar check (col in (...))`,
        // so the column must be redefined for the added values to pass the CHECK constraint.
        Schema::table('wallet_topups', function (Blueprint $table) {
            $table->enum('sms_verification_status', ['pending', 'matched', 'confirmed', 'rejected', 'cancelled', 'timeout'])
                ->default('pending')
                ->change();
        });
    }

    public function down(): void
    {
        if ($this->usesMySql()) {
            DB::statement("ALTER TABLE `wallet_topups` MODIFY COLUMN `sms_verification_status` ENUM('pending', 'matched', 'confirmed', 'timeout') NOT NULL DEFAULT 'pending'");

            return;
        }

        Schema::table('wallet_topups', function (Blueprint $table) {
            $table->enum('sms_verification_status', ['pending', 'matched', 'confirmed', 'timeout'])
                ->default('pending')
                ->change();
        });
    }

    private function usesMySql(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
