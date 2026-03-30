<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Remove duplicate free license keys.
     *
     * Bug: validateDeviceAuth() used create() instead of firstOrCreate(),
     * and heartbeat (every 30s) triggered it — creating a new FREE- key
     * on every call when an expired demo key was picked before the valid free key.
     *
     * Fix: Keep only the oldest free license per (product_id, machine_id).
     * Delete all newer duplicates.
     */
    public function up(): void
    {
        // Find all (product_id, machine_id) combos with more than 1 free license
        $duplicates = DB::table('license_keys')
            ->select('product_id', 'machine_id', DB::raw('COUNT(*) as cnt'), DB::raw('MIN(id) as keep_id'))
            ->where('license_type', 'free')
            ->whereNotNull('machine_id')
            ->groupBy('product_id', 'machine_id')
            ->having('cnt', '>', 1)
            ->get();

        $totalDeleted = 0;

        foreach ($duplicates as $dup) {
            $deleted = DB::table('license_keys')
                ->where('product_id', $dup->product_id)
                ->where('machine_id', $dup->machine_id)
                ->where('license_type', 'free')
                ->where('id', '!=', $dup->keep_id)
                ->delete();

            $totalDeleted += $deleted;
        }

        Log::info("[Migration] Cleaned up {$totalDeleted} duplicate free license keys across {$duplicates->count()} devices.");
    }

    /**
     * No rollback — deleted duplicates cannot be restored.
     */
    public function down(): void
    {
        // Cannot restore deleted duplicates
    }
};
