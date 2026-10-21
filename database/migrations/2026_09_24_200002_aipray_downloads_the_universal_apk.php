<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Aipray — ปุ่มดาวน์โหลดส่ง APK ตัว universal (ติดตั้งได้ทุกเครื่อง) — เจ้าของเลือก 2026-09-24
     *
     * release ของ Aipray มี 4 ไฟล์ (arm32 / arm64 / universal / x86_64) แต่ pattern เดิม `*.apk` หยิบไฟล์แรก
     * DB จึงเก็บ aipray-<ver>-arm32.apk ไว้ ส่วนหน้าเว็บเลี่ยงไปถาม GitHub เองทุกครั้งที่เปิดหน้าแล้วยื่นลิงก์
     * GitHub ของตัว universal ให้ลูกค้า — ตอนนี้ไฟล์ส่งจาก xman4289.com (/apps/aipray/download) ตาม DB
     * DB จึงต้องเก็บตัวที่ควรได้จริง
     *
     * auto_sync เปิดด้วย: เวอร์ชันที่ sync ไว้แล้ว (1.2.4 = arm32) tag ตรงกับ GitHub อยู่ read-through จึงไม่ sync ซ้ำ
     * cron รอบถัดไป (≤10 นาที) sync ตาม pattern ใหม่แล้ว updateOrCreate เขียนแถวเดิมทับเป็นไฟล์ universal
     * ระหว่างรอ ปุ่มยังส่ง arm32 ที่ติดตั้งได้บนมือถือเกือบทุกเครื่อง
     *
     * แก้เฉพาะแถวที่ยังเป็นค่าตั้งต้นของ 2026_03_25_000008 — ถ้าเจ้าของเปลี่ยน pattern เองในหน้า admin แล้ว ไม่แตะ
     */
    public function up(): void
    {
        $productId = DB::table('products')->where('slug', 'aipray')->value('id');

        if ($productId === null) {
            return;
        }

        DB::table('github_settings')
            ->where('product_id', $productId)
            ->where('asset_pattern', '*.apk')
            ->update([
                'asset_pattern' => 'aipray-*-universal.apk',
                'auto_sync' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $productId = DB::table('products')->where('slug', 'aipray')->value('id');

        if ($productId === null) {
            return;
        }

        DB::table('github_settings')
            ->where('product_id', $productId)
            ->where('asset_pattern', 'aipray-*-universal.apk')
            ->update([
                'asset_pattern' => '*.apk',
                'auto_sync' => false,
                'updated_at' => now(),
            ]);
    }
};
