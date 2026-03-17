<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix active trials that were given 7 days instead of 24 hours.
     * Set their trial_expires_at to 24 hours from now.
     */
    public function up(): void
    {
        DB::table('product_devices')
            ->where('status', 'trial')
            ->where('trial_expires_at', '>', now())
            ->update([
                'trial_expires_at' => now()->addHours(24),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse — original expiry dates are lost
    }
};
