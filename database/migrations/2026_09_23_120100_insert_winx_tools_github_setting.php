<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * WinXTools (Windows) — ผูก GitHub release ให้แอปอัปเดตตัวเองได้ทันทีหลัง deploy
     *
     * repo xjanova/winxtools เป็น public จึงไม่ต้องมี token: GithubReleaseService อ่าน release
     * แบบไม่ล็อกอินได้ และ /winx-tools/download ส่งลูกค้าไปที่ลิงก์ตรงของ GitHub
     * (เหมือนที่ 2026_03_26_000003 ทำให้ localvpn)
     *
     * ใส่ให้เฉพาะเมื่อมีสินค้า winx-tools แล้วและยังไม่เคยตั้งค่า GitHub — ค่าที่เจ้าของตั้งเอง
     * ในหน้า admin ไม่ถูกแตะ รันซ้ำกี่รอบก็ได้ผลเท่าเดิม
     *
     * auto_sync เปิดไว้เลย (2026_07_28_000002 ทำให้เป็น opt-in) — repo นี้อ่านได้แน่นอน
     * ไม่มี token ที่จะผิด และ asset_pattern ตรงกับไฟล์ที่ release workflow ของแอปอัปโหลด
     * (WinXTools-v<version>-win-x64.zip ที่เซ็นแล้ว — ตัวอัปเดตในแอปรับแค่ไฟล์นี้ ถ้าวันหน้ามีไฟล์อื่น
     * ใน release ด้วย เช่น WinXTools.exe ที่ workflow เคยอัปโหลด pattern นี้กันไม่ให้ถูกเลือกแทน)
     */
    public function up(): void
    {
        $product = DB::table('products')->where('slug', 'winx-tools')->first();

        if (! $product || DB::table('github_settings')->where('product_id', $product->id)->exists()) {
            return;
        }

        DB::table('github_settings')->insert([
            'product_id' => $product->id,
            'github_owner' => 'xjanova',
            'github_repo' => 'winxtools',
            'github_token' => '', // repo public ไม่ต้องใช้ — ใส่ได้ภายหลังในหน้า admin
            'asset_pattern' => 'WinXTools-*-win-x64.zip',
            'is_active' => true,
            'auto_sync' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $product = DB::table('products')->where('slug', 'winx-tools')->first();

        if (! $product) {
            return;
        }

        // ลบเฉพาะแถวที่ migration นี้ใส่เอง — ถ้าเจ้าของใส่ token หรือเปลี่ยน repo ไปแล้ว ปล่อยไว้
        DB::table('github_settings')
            ->where('product_id', $product->id)
            ->where('github_owner', 'xjanova')
            ->where('github_repo', 'winxtools')
            ->where('github_token', '')
            ->delete();
    }
};
