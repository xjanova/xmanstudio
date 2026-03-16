<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metal_x_automation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action_type');
            $table->foreignId('video_id')->nullable()->constrained('metal_x_videos')->nullOnDelete();
            $table->unsignedBigInteger('comment_id')->nullable();
            $table->string('status'); // success, failed, skipped
            $table->json('details')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['action_type', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metal_x_automation_logs');
    }
};
