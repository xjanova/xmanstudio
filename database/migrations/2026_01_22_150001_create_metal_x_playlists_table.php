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
        Schema::create('metal_x_playlists', function (Blueprint $table) {
            $table->id();
            $table->string('youtube_id')->unique()->nullable();
            $table->string('title');
            $table->string('title_th')->nullable();
            $table->text('description')->nullable();
            $table->text('description_th')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->integer('video_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_synced')->default(false);
            $table->integer('order')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('youtube_id');
            $table->index('is_featured');
            $table->index('is_active');
            $table->index('order');
        });

        // Pivot table for playlist videos
        Schema::create('metal_x_playlist_video', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playlist_id')->constrained('metal_x_playlists')->onDelete('cascade');
            $table->foreignId('video_id')->constrained('metal_x_videos')->onDelete('cascade');
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->unique(['playlist_id', 'video_id']);
            $table->index('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metal_x_playlist_video');
        Schema::dropIfExists('metal_x_playlists');
    }
};
