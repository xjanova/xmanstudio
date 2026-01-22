<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_coming_soon')->default(false)->after('is_active');
            $table->timestamp('coming_soon_until')->nullable()->after('is_coming_soon');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->boolean('is_coming_soon')->default(false)->after('is_active');
            $table->timestamp('coming_soon_until')->nullable()->after('is_coming_soon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_coming_soon', 'coming_soon_until']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['is_coming_soon', 'coming_soon_until']);
        });
    }
};
