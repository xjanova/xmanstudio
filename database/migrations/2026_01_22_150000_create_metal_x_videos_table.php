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
        Schema::create('metal_x_videos', function (Blueprint $table) {
            $table->id();
            $table->string('youtube_id')->unique();
            $table->string('title');
            $table->string('title_th')->nullable();
            $table->text('description')->nullable();
            $table->text('description_th')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('thumbnail_medium_url')->nullable();
            $table->string('thumbnail_high_url')->nullable();
            $table->string('channel_id')->nullable();
            $table->string('channel_title')->nullable();
            $table->bigInteger('view_count')->default(0);
            $table->bigInteger('like_count')->default(0);
            $table->bigInteger('comment_count')->default(0);
            $table->string('duration')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->json('tags')->nullable();
            $table->string('category')->nullable();
            $table->enum('privacy_status', ['public', 'private', 'unlisted'])->default('public');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('youtube_id');
            $table->index('is_featured');
            $table->index('is_active');
            $table->index('published_at');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metal_x_videos');
    }
};
