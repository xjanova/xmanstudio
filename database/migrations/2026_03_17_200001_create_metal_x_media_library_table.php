<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metal_x_media_library', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // image, video_clip
            $table->string('file_path');
            $table->string('filename');
            $table->json('tags')->nullable();
            $table->string('source')->default('custom'); // freepik, custom, ai_generated
            $table->string('source_id')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->integer('duration_seconds')->nullable();
            $table->string('resolution')->nullable();
            $table->integer('usage_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metal_x_media_library');
    }
};
