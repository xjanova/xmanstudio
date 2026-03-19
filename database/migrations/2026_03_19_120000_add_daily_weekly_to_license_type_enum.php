<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * เพิ่ม daily (1 วัน) และ weekly (7 วัน) ในประเภท license
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE license_keys MODIFY COLUMN license_type ENUM('demo', 'daily', 'weekly', 'monthly', 'yearly', 'lifetime', 'product') DEFAULT 'product'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE license_keys MODIFY COLUMN license_type ENUM('demo', 'monthly', 'yearly', 'lifetime', 'product') DEFAULT 'product'");
    }
};
