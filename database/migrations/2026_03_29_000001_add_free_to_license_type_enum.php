<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE license_keys MODIFY COLUMN license_type ENUM('demo', 'daily', 'weekly', 'monthly', 'yearly', 'lifetime', 'product', 'free') DEFAULT 'product'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE license_keys MODIFY COLUMN license_type ENUM('demo', 'daily', 'weekly', 'monthly', 'yearly', 'lifetime', 'product') DEFAULT 'product'");
    }
};
