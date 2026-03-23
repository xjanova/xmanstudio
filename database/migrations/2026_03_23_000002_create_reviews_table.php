<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('reviewable'); // reviewable_id + reviewable_type
            $table->tinyInteger('rating')->unsigned(); // 1-5
            $table->string('title')->nullable();
            $table->text('comment');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'reviewable_id', 'reviewable_type'], 'reviews_user_reviewable_unique');
            $table->index(['status', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
