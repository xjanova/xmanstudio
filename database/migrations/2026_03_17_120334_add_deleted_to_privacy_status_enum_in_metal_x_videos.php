<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alter enum to include 'deleted' value
        DB::statement("ALTER TABLE metal_x_videos MODIFY COLUMN privacy_status ENUM('public', 'private', 'unlisted', 'deleted') DEFAULT 'public'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First update any 'deleted' rows back to 'private'
        DB::table('metal_x_videos')->where('privacy_status', 'deleted')->update(['privacy_status' => 'private']);

        DB::statement("ALTER TABLE metal_x_videos MODIFY COLUMN privacy_status ENUM('public', 'private', 'unlisted') DEFAULT 'public'");
    }
};
