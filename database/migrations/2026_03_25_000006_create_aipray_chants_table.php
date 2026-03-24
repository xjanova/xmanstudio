<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aipray_chants', function (Blueprint $table) {
            $table->id();
            $table->string('chant_id')->unique();
            $table->string('title_th');
            $table->string('title_en')->nullable();
            $table->string('category')->default('daily');
            $table->json('lines');
            $table->string('audio_url')->nullable();
            $table->boolean('is_community')->default(false);
            $table->string('author')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('updated_at_token')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aipray_chants');
    }
};
