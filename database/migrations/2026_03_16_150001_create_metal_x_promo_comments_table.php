<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metal_x_promo_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained('metal_x_videos')->cascadeOnDelete();
            $table->text('comment_text');
            $table->string('youtube_comment_id')->nullable();
            $table->boolean('generated_by_ai')->default(true);
            $table->string('status')->default('draft'); // draft, scheduled, posted, failed
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index('video_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metal_x_promo_comments');
    }
};
