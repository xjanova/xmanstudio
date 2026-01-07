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
        Schema::table('products', function (Blueprint $table) {
            $table->string('short_description', 500)->nullable()->after('description');
            $table->string('sku', 100)->nullable()->unique()->after('slug');
            $table->integer('low_stock_threshold')->nullable()->default(5)->after('stock');

            // Change features from text to JSON
            $table->json('features')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['short_description', 'sku', 'low_stock_threshold']);

            // Revert features back to text
            $table->text('features')->nullable()->change();
        });
    }
};
