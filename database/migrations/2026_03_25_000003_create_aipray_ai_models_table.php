<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aipray_ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('version')->nullable();
            $table->string('base_model')->default('whisper-base');
            $table->foreignId('training_job_id')->nullable()->constrained('aipray_training_jobs')->nullOnDelete();
            $table->string('file_path')->nullable();
            $table->bigInteger('file_size')->default(0);
            $table->float('accuracy')->nullable();
            $table->float('wer')->nullable();
            $table->float('cer')->nullable();
            $table->integer('total_samples_trained')->default(0);
            $table->float('total_hours_trained')->default(0);
            $table->string('onnx_file_path')->nullable();
            $table->json('eval_results')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aipray_ai_models');
    }
};
