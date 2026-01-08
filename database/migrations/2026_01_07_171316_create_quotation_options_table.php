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
        Schema::create('quotation_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_category_id')->constrained()->onDelete('cascade');
            $table->string('key'); // e.g., 'image_basic', 'music_basic'
            $table->string('name'); // English name
            $table->string('name_th')->nullable(); // Thai name
            $table->text('description')->nullable(); // English description
            $table->text('description_th')->nullable(); // Thai description
            $table->decimal('price', 10, 2)->default(0); // Price in THB
            $table->string('image')->nullable(); // Option image
            $table->integer('order')->default(0); // Display order
            $table->boolean('is_active')->default(true); // Active status
            $table->timestamps();

            $table->unique(['quotation_category_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_options');
    }
};
