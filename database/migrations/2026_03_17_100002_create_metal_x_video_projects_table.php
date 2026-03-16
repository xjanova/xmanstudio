<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metal_x_video_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->nullable()->constrained('metal_x_channels')->nullOnDelete();
            $table->foreignId('music_generation_id')->nullable()->constrained('metal_x_music_generations')->nullOnDelete();
            $table->foreignId('video_id')->nullable()->constrained('metal_x_videos')->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->string('privacy_status')->default('private');
            $table->string('template')->default('visualizer');
            $table->json('template_settings')->nullable();
            $table->json('images')->nullable();
            $table->string('status')->default('draft');
            $table->string('video_file_path')->nullable();
            $table->integer('video_duration_seconds')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('ai_metadata_generated')->default(false);
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('channel_id');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metal_x_video_projects');
    }
};
