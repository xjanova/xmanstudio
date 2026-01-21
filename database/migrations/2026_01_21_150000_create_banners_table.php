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
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image')->comment('Path to banner image');
            $table->string('link_url')->nullable()->comment('URL to redirect when clicked');
            $table->boolean('target_blank')->default(true)->comment('Open link in new tab');
            $table->boolean('enabled')->default(false);
            $table->string('position')->default('header')->comment('header, sidebar, in-content, footer, between-products');
            $table->json('pages')->nullable()->comment('Pages where this banner should appear');
            $table->integer('priority')->default(0)->comment('Display priority (higher = more important)');
            $table->dateTime('start_date')->nullable()->comment('Start date/time for display');
            $table->dateTime('end_date')->nullable()->comment('End date/time for display');
            $table->integer('views')->default(0)->comment('Number of times displayed');
            $table->integer('clicks')->default(0)->comment('Number of times clicked');
            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('enabled');
            $table->index('position');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
