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
        Schema::create('github_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('github_owner', 100);
            $table->string('github_repo', 100);
            $table->text('github_token'); // encrypted
            $table->string('asset_pattern', 100)->default('*.exe'); // e.g., *.exe, *.zip, *.msi
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('github_settings');
    }
};
