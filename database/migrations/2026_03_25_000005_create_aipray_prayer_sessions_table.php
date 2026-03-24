<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aipray_prayer_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_uuid')->nullable();
            $table->string('device_id')->nullable();
            $table->string('chant_id');
            $table->string('chant_title');
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->integer('rounds_completed')->default(0);
            $table->integer('total_lines')->default(0);
            $table->integer('lines_reached')->default(0);
            $table->boolean('used_voice_tracking')->default(false);
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('chant_id');
            $table->index('device_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aipray_prayer_sessions');
    }
};
