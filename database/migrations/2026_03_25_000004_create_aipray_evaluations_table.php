<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aipray_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_model_id')->constrained('aipray_ai_models')->cascadeOnDelete();
            $table->string('eval_type')->default('live');
            $table->text('recognized_text')->nullable();
            $table->text('reference_text')->nullable();
            $table->float('accuracy')->nullable();
            $table->float('wer')->nullable();
            $table->float('cer')->nullable();
            $table->integer('latency_ms')->nullable();
            $table->string('audio_file')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aipray_evaluations');
    }
};
