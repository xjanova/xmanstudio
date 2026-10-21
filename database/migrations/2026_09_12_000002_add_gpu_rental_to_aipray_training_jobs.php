<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Training on a rented GPU (SimplePod) instead of a permanent ML server.
 * AiprayGpuTrainingRunner drives these columns from the scheduler.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aipray_training_jobs', function (Blueprint $table) {
            $table->string('runner', 16)->default('local');
            $table->string('gpu_state', 24)->nullable()->index();
            $table->string('gpu_instance_id', 64)->nullable();
            $table->string('gpu_offer', 255)->nullable();
            $table->string('gpu_endpoint', 512)->nullable();
            $table->text('gpu_token')->nullable();
            $table->decimal('gpu_price_per_hour', 8, 4)->nullable();
            $table->timestamp('gpu_rented_at')->nullable();
            $table->timestamp('gpu_ready_at')->nullable();
            $table->timestamp('gpu_deadline_at')->nullable();
            $table->timestamp('gpu_terminated_at')->nullable();
            $table->timestamp('gpu_last_seen_at')->nullable();
            $table->decimal('estimated_cost_usd', 8, 2)->nullable();
            $table->decimal('max_cost_usd', 8, 2)->nullable();
            $table->decimal('actual_cost_usd', 8, 2)->nullable();
            $table->string('dataset_path')->nullable();
            $table->integer('dataset_samples')->nullable();
            $table->float('dataset_seconds')->nullable();
            $table->string('artifact_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('aipray_training_jobs', function (Blueprint $table) {
            $table->dropIndex(['gpu_state']);
            $table->dropColumn([
                'runner', 'gpu_state', 'gpu_instance_id', 'gpu_offer', 'gpu_endpoint', 'gpu_token',
                'gpu_price_per_hour', 'gpu_rented_at', 'gpu_ready_at', 'gpu_deadline_at',
                'gpu_terminated_at', 'gpu_last_seen_at', 'estimated_cost_usd', 'max_cost_usd',
                'actual_cost_usd', 'dataset_path', 'dataset_samples', 'dataset_seconds', 'artifact_path',
            ]);
        });
    }
};
