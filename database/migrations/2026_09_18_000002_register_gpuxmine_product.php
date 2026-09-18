<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ลงทะเบียน GPUxMINE เป็นผลิตภัณฑ์หนึ่งใน XMAN Studio
 *
 * ทำแค่นี้ก็ได้ทั้งสามอย่างที่ต้องการ โดยไม่ต้องเขียนระบบใหม่เลย:
 *   1. **ไลเซนส์** — `requires_license = true` เปิด API ชุด
 *      `/api/v1/product/gpuxmine/{register-device,activate,validate,check-machine}`
 *      ที่มีอยู่แล้ว ไคลเอนต์ผูกเครื่องกับบัญชี XMAN ผ่านเส้นทางเดียวกับ
 *      ผลิตภัณฑ์เดิมทุกตัว
 *   2. **sync จาก GitHub อัตโนมัติ** — แถวใน `github_settings` + `auto_sync`
 *      ทำให้ cron ที่ยิง `scripts/sync-product-releases.sh` ทุก 10 นาที
 *      (ตั้งไว้ตั้งแต่ 2026-08-31) ดึง release ใหม่มาเองโดยไม่ต้องตั้ง cron เพิ่ม
 *   3. **หน้าโหลด + เช็กเวอร์ชัน** — `/api/v1/products/gpuxmine/version`
 *      และ `check-update` ใช้ได้ทันทีเมื่อมี `ProductVersion` แถวแรก
 *
 * ⚠️ `github_token` ตั้งเป็น **สตริงว่าง** ไม่ใช่ token จริง
 * รีโปเป็น public จึงอ่าน release ได้โดยไม่ต้องยืนยันตัวตน และบทเรียนวันที่
 * 2026-08-31 คือ **token ที่ตายแล้วแย่กว่าไม่มี token** — PAT ที่ถูก revoke
 * ทำให้ GitHub ตอบ 401 ทั้งที่รีโปเปิดสาธารณะ และลากทุกเส้นทางรวม
 * downloadAsset() ล้มตามไปด้วย จนลูกค้ากดโหลดไฟล์ไม่ได้ ค้างอยู่ 26 วัน
 * กว่าจะมีคนเห็น
 */
return new class extends Migration
{
    private const SLUG = 'gpuxmine';

    public function up(): void
    {
        if (! $this->tablesReady()) {
            return;
        }

        if (DB::table('products')->where('slug', self::SLUG)->exists()) {
            return;
        }

        $categoryId = DB::table('categories')->value('id');
        if ($categoryId === null) {
            // ไม่มีหมวดหมู่ให้ผูก = ฐานข้อมูลยังไม่ได้ seed ปล่อยไว้ให้รอบหน้า
            // ดีกว่าสร้างหมวดหมู่ผีขึ้นมาเองให้แอดมินงง
            return;
        }

        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'GPUxMINE Worker',
            'slug' => self::SLUG,
            'description' => 'โปรแกรมแบ่งปันการ์ดจอที่บ้านให้รับงาน AI แล้วได้ค่าตอบแทนเข้ากระเป๋า XMAN Studio '
                . 'ตั้งเวลาได้ว่าจะแชร์ช่วงไหนเท่าไร คืนการ์ดให้ทันทีเมื่อเจ้าของกลับมาใช้เครื่อง',
            'features' => json_encode([
                'รับงาน AI อัตโนมัติตามสเปกการ์ดจอของคุณ',
                'กำหนดเพดานกำลังและตารางเวลาแชร์ได้เอง',
                'คืนการ์ดให้ทันทีเมื่อเปิดเกมหรือกลับมาใช้เครื่อง',
                'ดูรายได้เทียบค่าไฟแบบเรียลไทม์',
                'อัปเดตตัวเองอัตโนมัติ',
            ], JSON_UNESCAPED_UNICODE),
            // ตัวโปรแกรมแจกฟรี — คนติดตั้งคือคนที่มาแบ่งปันเครื่องให้เรา
            // ที่ขายคือแพ็กเกจ Pro Miner ซึ่งออกเป็น license key บนสินค้าตัวนี้
            'price' => 0,
            'is_custom' => false,
            'requires_license' => true,
            'stock' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('github_settings')->insert([
            'product_id' => $productId,
            'github_owner' => 'xjanova',
            'github_repo' => 'GpuXmine',
            // ว่างโดยตั้งใจ — ดูหมายเหตุด้านบน
            'github_token' => '',
            // Velopack ออก Setup.exe เป็นตัวติดตั้งหลัก
            'asset_pattern' => '*.exe',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (DB::getSchemaBuilder()->hasColumn('github_settings', 'auto_sync')) {
            DB::table('github_settings')
                ->where('product_id', $productId)
                ->update(['auto_sync' => true]);
        }
    }

    public function down(): void
    {
        if (! $this->tablesReady()) {
            return;
        }

        $productId = DB::table('products')->where('slug', self::SLUG)->value('id');
        if ($productId === null) {
            return;
        }

        // github_settings หายไปเองด้วย cascade ส่วนเวอร์ชันที่ sync มาแล้วลบตรง ๆ
        DB::table('product_versions')->where('product_id', $productId)->delete();
        DB::table('products')->where('id', $productId)->delete();
    }

    private function tablesReady(): bool
    {
        $schema = DB::getSchemaBuilder();

        return $schema->hasTable('products')
            && $schema->hasTable('github_settings')
            && $schema->hasTable('categories');
    }
};
