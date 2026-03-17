<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('puzzle_debug_images', function (Blueprint $table) {
            // Track who labeled: 'human', 'auto_success', or null (unlabeled)
            $table->string('labeled_by', 30)->nullable()->after('actual_gap_x');
        });

        // Reset all records that were auto-labeled by app feedback.
        // These records had actual_gap_x set by the app (not human),
        // which poisoned the training data. Clear them so humans can re-label.
        // Records without image_paths are feedback-only records — also reset.
        DB::table('puzzle_debug_images')
            ->whereNotNull('actual_gap_x')
            ->whereNull('labeled_by')
            ->update([
                'actual_gap_x' => null,
                'labeled_by' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('puzzle_debug_images', function (Blueprint $table) {
            $table->dropColumn('labeled_by');
        });
    }
};
