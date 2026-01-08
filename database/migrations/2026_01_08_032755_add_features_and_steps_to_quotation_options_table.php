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
        Schema::table('quotation_options', function (Blueprint $table) {
            $table->json('features')->nullable()->after('description_th'); // คุณสมบัติ/Features
            $table->json('features_th')->nullable()->after('features'); // คุณสมบัติภาษาไทย
            $table->json('steps')->nullable()->after('features_th'); // ขั้นตอน/Steps
            $table->json('steps_th')->nullable()->after('steps'); // ขั้นตอนภาษาไทย
            $table->text('long_description')->nullable()->after('steps_th'); // รายละเอียดแบบยาว (EN)
            $table->text('long_description_th')->nullable()->after('long_description'); // รายละเอียดแบบยาว (TH)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotation_options', function (Blueprint $table) {
            $table->dropColumn(['features', 'features_th', 'steps', 'steps_th', 'long_description', 'long_description_th']);
        });
    }
};
