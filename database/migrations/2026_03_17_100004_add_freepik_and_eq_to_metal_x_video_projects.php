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
        Schema::table('metal_x_video_projects', function (Blueprint $table) {
            $table->json('video_clips')->nullable()->after('images');
            $table->string('media_mode')->default('images')->after('video_clips');
            $table->json('eq_settings')->nullable()->after('template_settings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('metal_x_video_projects', function (Blueprint $table) {
            $table->dropColumn(['video_clips', 'media_mode', 'eq_settings']);
        });
    }
};
