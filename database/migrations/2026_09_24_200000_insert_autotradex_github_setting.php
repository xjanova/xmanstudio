<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * AutoTradeX — ผูก GitHub release ให้ /autotradex/download มีไฟล์ส่งจาก xman4289.com ได้ทันทีหลัง deploy
     *
     * เดิมหน้าลูกค้า (ศูนย์ดาวน์โหลด, รายละเอียด license) ลิงก์ไปหน้า releases บน GitHub ตรง ๆ = บอกลูกค้าว่า repo
     * อยู่ไหน (กฎเจ้าของ 2026-09-24 ห้ามเด็ดขาด) · สินค้า autotradex มีอยู่แล้วแต่ไม่เคยผูก GitHub จึงไม่มี
     * ProductVersion ให้ส่งไฟล์เลย (update/check ตอบ latest_version "")
     *
     * repo xjanova/autotradex เป็น public ไม่ต้องมี token · auto_sync เปิดไว้ → cron ดึง release แรกเข้ามาภายใน
     * 10 นาที หรือทันทีที่มีคนกดดาวน์โหลด (read-through ของ GithubReleaseService)
     * asset_pattern = ตัว portable ที่ไม่ต้องลง .NET — เจ้าของเลือก 2026-09-24 · อีกไฟล์ใน release (…-win-x64.zip)
     * ลูกค้าต้องติดตั้ง .NET 8 Desktop Runtime เองก่อน
     *
     * ใส่ให้เฉพาะเมื่อมีสินค้าแล้วและยังไม่เคยตั้งค่า GitHub — ค่าที่เจ้าของตั้งเองในหน้า admin ไม่ถูกแตะ
     * รันซ้ำกี่รอบก็ได้ผลเท่าเดิม (แบบเดียวกับ 2026_09_23_120100 ของ winx-tools)
     */
    public function up(): void
    {
        $product = DB::table('products')->where('slug', 'autotradex')->first();

        if (! $product || DB::table('github_settings')->where('product_id', $product->id)->exists()) {
            return;
        }

        DB::table('github_settings')->insert([
            'product_id' => $product->id,
            'github_owner' => 'xjanova',
            'github_repo' => 'autotradex',
            'github_token' => '', // repo public ไม่ต้องใช้ — token ที่ตายแล้วแย่กว่าไม่มี (ดู 2026_09_18_000002)
            'asset_pattern' => 'AutoTradeX-*-win-x64-portable.zip',
            'is_active' => true,
            'auto_sync' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $product = DB::table('products')->where('slug', 'autotradex')->first();

        if (! $product) {
            return;
        }

        // ลบเฉพาะแถวที่ migration นี้ใส่เอง — ถ้าเจ้าของใส่ token หรือเปลี่ยน repo ไปแล้ว ปล่อยไว้
        DB::table('github_settings')
            ->where('product_id', $product->id)
            ->where('github_owner', 'xjanova')
            ->where('github_repo', 'autotradex')
            ->where('github_token', '')
            ->delete();
    }
};
