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
        Schema::create('metal_x_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained('metal_x_videos')->onDelete('cascade');
            $table->string('comment_id')->unique();
            $table->string('parent_id')->nullable(); // For replies
            $table->string('author_name');
            $table->string('author_channel_id')->nullable();
            $table->string('author_profile_image')->nullable();
            $table->text('text');
            $table->integer('like_count')->default(0);
            $table->boolean('can_reply')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('updated_at_youtube')->nullable();

            // AI Reply fields
            $table->boolean('ai_replied')->default(false);
            $table->text('ai_reply_text')->nullable();
            $table->decimal('ai_reply_confidence', 5, 2)->nullable();
            $table->timestamp('ai_replied_at')->nullable();
            $table->string('ai_reply_comment_id')->nullable();

            // Engagement fields
            $table->boolean('liked_by_channel')->default(false);
            $table->timestamp('liked_at')->nullable();

            // Sentiment analysis
            $table->string('sentiment')->nullable(); // positive, negative, neutral, question
            $table->decimal('sentiment_score', 5, 2)->nullable();

            // Filtering
            $table->boolean('requires_attention')->default(false);
            $table->boolean('is_spam')->default(false);
            $table->boolean('is_hidden')->default(false);

            $table->timestamps();

            $table->index(['video_id', 'published_at']);
            $table->index(['sentiment', 'ai_replied']);
            $table->index('requires_attention');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metal_x_comments');
    }
};
