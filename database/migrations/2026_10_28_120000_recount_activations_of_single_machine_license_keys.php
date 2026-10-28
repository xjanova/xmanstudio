<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Re-entering a key on its own machine used to add one to `activations` each time, so
 * single-machine keys read 19/1 while bound to one PC, and deactivate answered
 * can_reactivate false. A key of one machine holds that machine or none.
 * (Keys of several machines are kept in step by LicenseSeats::recount.)
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('license_keys')
            ->where('max_activations', 1)
            ->where('activations', '>', 1)
            ->update(['activations' => DB::raw('CASE WHEN machine_id IS NULL THEN 0 ELSE 1 END')]);
    }

    public function down(): void
    {
        // The old counts were the number of times a key was entered, not machines; nothing to put back.
    }
};
