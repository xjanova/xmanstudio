<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metal_x_content_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('metal_x_channels')->cascadeOnDelete();
            $table->string('name');
            $table->text('topic_prompt');
            $table->text('music_prompt')->nullable();
            $table->string('music_style')->default('metal');
            $table->integer('music_duration')->default(60);
            $table->string('template')->default('visualizer');
            $table->json('template_settings')->nullable();
            $table->json('eq_settings')->nullable();
            $table->string('media_mode')->default('images');
            $table->string('privacy_status')->default('private');
            $table->json('media_pool_tags')->nullable();
            $table->integer('media_count')->default(10);
            $table->integer('schedule_frequency_hours')->default(24);
            $table->integer('preferred_publish_hour')->default(18);
            $table->json('preferred_publish_days')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->integer('max_queue_size')->default(3);
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamp('next_generation_at')->nullable();
            $table->integer('total_generated')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metal_x_content_plans');
    }
};
