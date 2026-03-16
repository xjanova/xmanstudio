<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metal_x_music_generations', function (Blueprint $table) {
            $table->id();
            $table->text('prompt');
            $table->string('style')->nullable();
            $table->integer('duration_seconds')->default(60);
            $table->string('suno_task_id')->nullable();
            $table->string('status')->default('pending');
            $table->string('audio_url')->nullable();
            $table->string('audio_path')->nullable();
            $table->string('title')->nullable();
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('suno_task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metal_x_music_generations');
    }
};
