<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ระบบให้เช่าแพ็กเกจ - Rental Packages
     */
    public function up(): void
    {
        Schema::create('rental_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_th')->nullable();
            $table->text('description')->nullable();
            $table->text('description_th')->nullable();
            $table->enum('duration_type', ['hourly', 'daily', 'weekly', 'monthly', 'yearly'])->default('monthly');
            $table->integer('duration_value')->default(1);
            $table->decimal('price', 10, 2);
            $table->decimal('original_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('THB');

            // Features & Limits (JSON for flexibility)
            $table->json('features')->nullable();
            $table->json('limits')->nullable();

            // Status & Display
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_popular')->default(false);
            $table->integer('sort_order')->default(0);

            // Trial
            $table->boolean('has_trial')->default(false);
            $table->integer('trial_days')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_packages');
    }
};
