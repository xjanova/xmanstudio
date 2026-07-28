<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-07-28 — ติ๊กเลือกได้ว่าผลิตภัณฑ์ไหนให้ cron ดึง release จาก GitHub อัตโนมัติ
 *
 * default = true เพราะเดิมต้องกดปุ่ม Sync ในหน้า admin เองทุกครั้ง ถ้าลืมกด
 * API เช็คอัพเดทจะค้างเวอร์ชันเก่า แอปลูกค้าเลยไม่เห็นอัพเดททั้งที่ GitHub มีของใหม่แล้ว
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('github_settings', function (Blueprint $table) {
            $table->boolean('auto_sync')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('github_settings', function (Blueprint $table) {
            $table->dropColumn('auto_sync');
        });
    }
};
