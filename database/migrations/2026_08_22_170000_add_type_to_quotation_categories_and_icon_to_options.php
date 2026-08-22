<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Let quotation_categories hold add-on groups (support, delivery, hosting,
     * design, seo_marketing) alongside the main service categories, so the
     * add-ons stop living in a hard-coded PHP array and become editable from
     * the existing admin CRUD.
     */
    public function up(): void
    {
        Schema::table('quotation_categories', function (Blueprint $table) {
            // 'service' = main service category, 'addon' = optional extras group.
            // Deliberately a string, not an enum: widening an enum later needs
            // driver-specific SQL and aborts local sqlite migrations.
            $table->string('type', 20)->default('service')->after('key');
            $table->index('type');
        });

        Schema::table('quotation_options', function (Blueprint $table) {
            // Add-on rows carry their own emoji; service options inherit the
            // category icon and leave this null.
            $table->string('icon', 50)->nullable()->after('name_th');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_categories', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });

        Schema::table('quotation_options', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};
