<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puzzle_debug_images', function (Blueprint $table) {
            $table->id();
            $table->string('machine_id', 100)->index();
            $table->string('app_version', 20)->nullable();
            $table->string('detection_method', 30)->nullable(); // diff, static, none
            $table->integer('gap_x')->nullable();
            $table->integer('slider_x')->nullable();
            $table->integer('drag_dist')->nullable();
            $table->integer('track_width')->nullable();
            $table->boolean('success')->nullable(); // null = unknown, updated later
            $table->integer('actual_gap_x')->nullable(); // manual label for training
            $table->json('image_paths')->nullable(); // array of stored file paths
            $table->json('metadata')->nullable(); // any extra info
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['machine_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puzzle_debug_images');
    }
};
