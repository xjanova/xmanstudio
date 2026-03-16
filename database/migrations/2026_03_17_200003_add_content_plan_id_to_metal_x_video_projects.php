<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metal_x_video_projects', function (Blueprint $table) {
            $table->foreignId('content_plan_id')->nullable()->after('channel_id')->constrained('metal_x_content_plans')->nullOnDelete();
            $table->boolean('auto_generated')->default(false)->after('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('metal_x_video_projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('content_plan_id');
            $table->dropColumn('auto_generated');
        });
    }
};
