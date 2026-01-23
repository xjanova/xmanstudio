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
        if (! Schema::hasColumn('metal_x_videos', 'ai_title_th')) {
            Schema::table('metal_x_videos', function (Blueprint $table) {
                $table->text('ai_title_th')->nullable()->after('title_th');
            });
        }

        if (! Schema::hasColumn('metal_x_videos', 'ai_description_th')) {
            Schema::table('metal_x_videos', function (Blueprint $table) {
                $table->text('ai_description_th')->nullable()->after('description_th');
            });
        }

        if (! Schema::hasColumn('metal_x_videos', 'ai_tags')) {
            Schema::table('metal_x_videos', function (Blueprint $table) {
                $table->json('ai_tags')->nullable()->after('tags');
            });
        }

        if (! Schema::hasColumn('metal_x_videos', 'ai_category')) {
            Schema::table('metal_x_videos', function (Blueprint $table) {
                $table->string('ai_category')->nullable()->after('category');
            });
        }

        if (! Schema::hasColumn('metal_x_videos', 'ai_confidence_score')) {
            Schema::table('metal_x_videos', function (Blueprint $table) {
                $table->decimal('ai_confidence_score', 5, 2)->nullable()->after('ai_category');
            });
        }

        if (! Schema::hasColumn('metal_x_videos', 'ai_generated')) {
            Schema::table('metal_x_videos', function (Blueprint $table) {
                $table->boolean('ai_generated')->default(false)->after('ai_confidence_score');
            });
        }

        if (! Schema::hasColumn('metal_x_videos', 'ai_approved')) {
            Schema::table('metal_x_videos', function (Blueprint $table) {
                $table->boolean('ai_approved')->default(false)->after('ai_generated');
            });
        }

        if (! Schema::hasColumn('metal_x_videos', 'ai_generated_at')) {
            Schema::table('metal_x_videos', function (Blueprint $table) {
                $table->timestamp('ai_generated_at')->nullable()->after('ai_approved');
            });
        }

        if (! Schema::hasColumn('metal_x_videos', 'ai_approved_at')) {
            Schema::table('metal_x_videos', function (Blueprint $table) {
                $table->timestamp('ai_approved_at')->nullable()->after('ai_generated_at');
            });
        }

        if (! Schema::hasColumn('metal_x_videos', 'ai_approved_by')) {
            Schema::table('metal_x_videos', function (Blueprint $table) {
                $table->unsignedBigInteger('ai_approved_by')->nullable()->after('ai_approved_at');
            });
        }

        // Add foreign key constraint if column exists and constraint doesn't
        if (Schema::hasColumn('metal_x_videos', 'ai_approved_by')) {
            try {
                Schema::table('metal_x_videos', function (Blueprint $table) {
                    $table->foreign('ai_approved_by')
                        ->references('id')
                        ->on('users')
                        ->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key already exists, ignore
                if (! str_contains($e->getMessage(), 'Duplicate foreign key')) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key if it exists
        try {
            Schema::table('metal_x_videos', function (Blueprint $table) {
                $table->dropForeign(['ai_approved_by']);
            });
        } catch (\Exception $e) {
            // Foreign key doesn't exist, ignore
        }

        // Drop columns that exist
        $columnsToCheck = [
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
        ];

        $columnsToDrop = [];
        foreach ($columnsToCheck as $column) {
            if (Schema::hasColumn('metal_x_videos', $column)) {
                $columnsToDrop[] = $column;
            }
        }

        if (! empty($columnsToDrop)) {
            Schema::table('metal_x_videos', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
