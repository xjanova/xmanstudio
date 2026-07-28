<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-07-28 — เจ้าของสั่ง: "ติ๊ก sync ไว้เฉพาะโปรดักที่รองรับเท่านั้นก่อน"
 *
 * migration ก่อนหน้า (2026_07_28_000001) ตั้ง auto_sync default = true ให้ทุกตัว
 * ซึ่งกว้างเกินไป — ผลิตภัณฑ์ที่ยังไม่เคย sync สำเร็จ (token ผิด / asset_pattern
 * ไม่ตรง / repo ยังไม่มี release) จะโดน cron ยิง GitHub ทุก 10 นาทีแล้ว log error เปล่า ๆ
 *
 * รอบนี้เปลี่ยนเป็น opt-in:
 *   - default = false → ผลิตภัณฑ์ใหม่ต้องเข้าไปติ๊กเปิดเองในหน้า admin
 *   - ของเดิมปิดทั้งหมด แล้วเปิดคืนเฉพาะตัวที่ "รองรับ" พิสูจน์แล้ว =
 *     GitHub setting เปิดอยู่ + เคย sync สำเร็จจนมีแถวใน product_versions จริง
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('github_settings', function (Blueprint $table) {
            $table->boolean('auto_sync')->default(false)->change();
        });

        // ปิดก่อนทั้งหมด แล้วค่อยเปิดคืนเฉพาะตัวที่ผ่านเกณฑ์
        DB::table('github_settings')->update(['auto_sync' => false]);

        DB::table('github_settings')
            ->where('is_active', true)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('product_versions')
                    ->whereColumn('product_versions.product_id', 'github_settings.product_id')
                    ->whereNotNull('product_versions.synced_at');
            })
            ->update(['auto_sync' => true]);
    }

    public function down(): void
    {
        Schema::table('github_settings', function (Blueprint $table) {
            $table->boolean('auto_sync')->default(true)->change();
        });
    }
};
