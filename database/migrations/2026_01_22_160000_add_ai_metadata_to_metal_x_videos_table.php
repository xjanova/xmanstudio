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
        Schema::table('metal_x_videos', function (Blueprint $table) {
            $table->text('ai_title_th')->nullable()->after('title_th');
            $table->text('ai_description_th')->nullable()->after('description_th');
            $table->json('ai_tags')->nullable()->after('tags');
            $table->string('ai_category')->nullable()->after('category');
            $table->decimal('ai_confidence_score', 5, 2)->nullable()->after('ai_category');
            $table->boolean('ai_generated')->default(false)->after('ai_confidence_score');
            $table->boolean('ai_approved')->default(false)->after('ai_generated');
            $table->timestamp('ai_generated_at')->nullable()->after('ai_approved');
            $table->timestamp('ai_approved_at')->nullable()->after('ai_generated_at');
            $table->unsignedBigInteger('ai_approved_by')->nullable()->after('ai_approved_at');

            $table->foreign('ai_approved_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('metal_x_videos', function (Blueprint $table) {
            $table->dropForeign(['ai_approved_by']);
            $table->dropColumn([
                'ai_title_th',
                'ai_description_th',
                'ai_tags',
                'ai_category',
                'ai_confidence_score',
                'ai_generated',
                'ai_approved',
                'ai_generated_at',
                'ai_approved_at',
                'ai_approved_by',
            ]);
        });
    }
};
