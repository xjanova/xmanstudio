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
        Schema::table('banners', function (Blueprint $table) {
            $table->json('crop_data')->nullable()->after('image')
                ->comment('Image crop position data');
            $table->integer('display_width')->nullable()->after('crop_data')
                ->comment('Banner display width in pixels');
            $table->integer('display_height')->nullable()->after('display_width')
                ->comment('Banner display height in pixels');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn(['crop_data', 'display_width', 'display_height']);
        });
    }
};
