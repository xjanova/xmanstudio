<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metal_x_music_library', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('file_path');
            $table->string('style')->default('metal');
            $table->json('tags')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->string('source')->default('custom'); // suno, custom
            $table->integer('usage_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['style', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metal_x_music_library');
    }
};
