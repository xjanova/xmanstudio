<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aipray_audio_samples', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('original_name')->nullable();
            $table->string('file_path');
            $table->string('chant_id')->nullable();
            $table->integer('line_index')->nullable();
            $table->string('category')->default('daily');
            $table->string('label')->nullable();
            $table->text('transcript')->nullable();
            $table->float('duration')->nullable();
            $table->integer('sample_rate')->default(16000);
            $table->string('format')->default('wav');
            $table->integer('file_size')->default(0);
            $table->string('status')->default('unlabeled');
            $table->string('device_info')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('chant_id');
            $table->index('category');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aipray_audio_samples');
    }
};
