<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metal_x_videos', function (Blueprint $table) {
            $table->foreignId('metal_x_channel_id')->nullable()->after('channel_title')->constrained('metal_x_channels')->nullOnDelete();
            $table->string('source')->default('synced')->after('metal_x_channel_id');
        });
    }

    public function down(): void
    {
        Schema::table('metal_x_videos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('metal_x_channel_id');
            $table->dropColumn('source');
        });
    }
};
