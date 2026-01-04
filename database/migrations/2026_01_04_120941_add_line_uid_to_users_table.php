<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * เพิ่ม Line UID สำหรับส่งข้อความผ่าน Line Messaging API
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'line_uid')) {
                $table->string('line_uid')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('users', 'line_display_name')) {
                $table->string('line_display_name')->nullable()->after('line_uid');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['line_uid', 'line_display_name']);
        });
    }
};
