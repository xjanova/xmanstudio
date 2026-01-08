<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quotation_categories', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g., 'ai_image', 'music_ai'
            $table->string('name'); // English name
            $table->string('name_th')->nullable(); // Thai name
            $table->string('icon')->nullable(); // Emoji or icon class
            $table->text('description')->nullable(); // English description
            $table->text('description_th')->nullable(); // Thai description
            $table->string('image')->nullable(); // Category image
            $table->integer('order')->default(0); // Display order
            $table->boolean('is_active')->default(true); // Active status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_categories');
    }
};
