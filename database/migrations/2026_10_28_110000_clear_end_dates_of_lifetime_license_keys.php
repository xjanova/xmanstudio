<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A lifetime key has no end date, and LicenseKey now keeps it that way on save. This clears the
 * dates such keys already carry from an earlier term, which every `expires_at > now()` lookup
 * read as expired once they passed (BrainX's check-machine answered Free for a key the admin
 * had made lifetime).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('license_keys')
            ->where('license_type', 'lifetime')
            ->whereNotNull('expires_at')
            ->update(['expires_at' => null]);
    }

    public function down(): void
    {
        // The old dates meant nothing for these keys; there is nothing to put back.
    }
};
