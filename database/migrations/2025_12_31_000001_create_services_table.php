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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('name_th')->nullable();
            $table->text('description');
            $table->text('description_th')->nullable();
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            $table->json('features')->nullable();
            $table->json('features_th')->nullable();
            $table->decimal('starting_price', 12, 2)->nullable();
            $table->string('price_unit')->default('โปรเจกต์');
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
