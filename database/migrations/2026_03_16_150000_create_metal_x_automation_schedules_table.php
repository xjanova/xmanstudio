<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metal_x_automation_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->nullable()->constrained('metal_x_videos')->nullOnDelete();
            $table->string('action_type'); // auto_reply, auto_like, auto_moderate, promo_comment, sync_comments
            $table->boolean('is_enabled')->default(true);
            $table->integer('frequency_minutes')->default(360); // default: every 6 hours
            $table->integer('max_actions_per_run')->default(10);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->integer('run_count')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['is_enabled', 'next_run_at']);
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metal_x_automation_schedules');
    }
};
