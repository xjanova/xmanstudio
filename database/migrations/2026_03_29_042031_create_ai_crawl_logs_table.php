<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_crawl_logs', function (Blueprint $table) {
            $table->id();
            $table->string('bot_name', 100)->index();
            $table->string('bot_category', 50)->default('unknown');
            $table->string('ip_address', 45)->nullable();
            $table->string('url', 2048);
            $table->string('method', 10)->default('GET');
            $table->integer('status_code')->default(200);
            $table->string('user_agent', 1024)->nullable();
            $table->boolean('was_blocked')->default(false);
            $table->timestamps();

            $table->index('created_at');
            $table->index(['bot_name', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_crawl_logs');
    }
};
