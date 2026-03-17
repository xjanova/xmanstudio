<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metal_x_videos', function (Blueprint $table) {
            $table->string('video_type', 20)->default('standard')->after('duration_seconds');
            $table->index('video_type');
        });
    }

    public function down(): void
    {
        Schema::table('metal_x_videos', function (Blueprint $table) {
            $table->dropIndex(['video_type']);
            $table->dropColumn('video_type');
        });
    }
};
