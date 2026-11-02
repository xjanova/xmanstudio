<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One line under the pack's name in the app's shop - what a knowledge
     * pack covers, or what an outfit looks like. Products carry a single
     * description in one language; the app asks for Thai and English.
     */
    public function up(): void
    {
        Schema::table('avatar_packs', function (Blueprint $table) {
            $table->string('summary_th')->nullable()->after('name_en');
            $table->string('summary_en')->nullable()->after('summary_th');
        });
    }

    public function down(): void
    {
        Schema::table('avatar_packs', function (Blueprint $table) {
            $table->dropColumn(['summary_th', 'summary_en']);
        });
    }
};
