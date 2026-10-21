<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MacDock — Dock แบบ macOS สำหรับ Windows ใช้ฟรี · ไลเซ่นฟรีที่ผูกกับบัญชีและเครื่อง ใช้ปลดการอัปเดตอัตโนมัติ
 *
 * แถวนี้คือทั้งหมดที่แอปต้องมีบนเว็บ:
 *   — `/api/v1/product/macdock/{register-device,check-machine}` (getProduct() หาเฉพาะสินค้าที่ requires_license)
 *   — `/products/macdock/free-license` ลูกค้าล็อกอินแล้วกดยืนยัน = ได้คีย์ FREE ผูกกับบัญชี + เครื่อง (MacDockController)
 *   — `/api/v1/product/macdock/update/check` + `/macdock/download/{version}` ส่ง zip จาก xman4289.com เอง
 *
 * ไม่ใช่ FREEMIUM_PRODUCTS: check-machine ไม่แจกคีย์เอง — คีย์เกิดจากการกดยืนยันบนเว็บเท่านั้น (เจ้าของกำหนด 2026-10-14)
 *
 * GitHub setting: release มาจาก repo **private** xjanova/macdock (CI แนบ MacDock-<version>-win-x64.zip)
 *   — เจ้าของใส่ token อ่านอย่างเดียวเฉพาะ repo นี้ในหน้า admin · ห้ามใส่ token ใน migration (xmanstudio เป็น public)
 *   — ก่อนมี token: update/check ตอบ "ไม่มีอัปเดต" และ /macdock/download ตอบ JSON 404/502 — ไม่มีอะไรพัง
 *
 * ทำเป็น migration เพราะ deploy รันแค่ `migrate --force` · มีแถว/มี setting แล้ว = ไม่แตะ · รันซ้ำได้
 *
 * ⚠️ ชื่อสินค้าต้องเป็น "MacDock" ต่อไป — หน้า admin สร้าง slug ใหม่จากชื่อทุกครั้งที่บันทึก (Str::slug('MacDock') = 'macdock')
 */
return new class extends Migration
{
    private const SLUG = 'macdock';

    private const SKU = 'MDK-001';

    public function up(): void
    {
        $productId = DB::table('products')->where('slug', self::SLUG)->value('id')
            ?? $this->insertProduct();

        if (DB::table('github_settings')->where('product_id', $productId)->exists()) {
            return;
        }

        DB::table('github_settings')->insert([
            'product_id' => $productId,
            'github_owner' => 'xjanova',
            'github_repo' => 'macdock',
            // repo private — เจ้าของใส่ token เองในหน้า admin (คอลัมน์ NOT NULL: ว่าง = ยังไม่มี)
            'github_token' => '',
            'asset_pattern' => 'MacDock-*-win-x64.zip',
            'is_active' => true,
            'auto_sync' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $productId = DB::table('products')->where('slug', self::SLUG)->value('id');

        // มีคีย์แล้ว (ลูกค้ารับไลเซ่นไปแล้ว) = ไม่ลบ — ลบสินค้า = คีย์ของลูกค้าหายตาม
        if ($productId === null
            || DB::table('license_keys')->where('product_id', $productId)->exists()
            || DB::table('order_items')->where('product_id', $productId)->exists()) {
            return;
        }

        DB::table('product_devices')->where('product_id', $productId)->delete();
        DB::table('product_versions')->where('product_id', $productId)->delete();
        DB::table('github_settings')->where('product_id', $productId)->delete();
        DB::table('products')->where('id', $productId)->delete();
    }

    private function insertProduct(): int
    {
        $categoryId = DB::table('categories')->where('slug', 'system-utilities')->value('id')
            ?? DB::table('categories')->insertGetId([
                // ค่าเดียวกับ XmanProductsSeeder — production มีหมวดนี้อยู่แล้ว
                'name' => 'System Utilities',
                'slug' => 'system-utilities',
                'description' => 'เครื่องมือจัดการระบบและยูทิลิตี้',
                'icon' => 'cog',
                'order' => 6,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'MacDock',
            'slug' => self::SLUG,
            'sku' => DB::table('products')->where('sku', self::SKU)->exists() ? null : self::SKU,
            'short_description' => 'Dock สไตล์ macOS สำหรับ Windows — ไอคอนขยายตามเมาส์ ปักหมุดแอป ดูหน้าต่างที่เปิดอยู่ '
                . 'ใช้ฟรี · รับไลเซ่นฟรีเพื่อเปิดการอัปเดตอัตโนมัติ',
            'description' => <<<'HTML'
<div class="prose max-w-none">
<h2>MacDock — Dock แบบ macOS บน Windows</h2>
<p>แถบ Dock ลอยด้านล่างจอ ไอคอนขยายตามเมาส์แบบ macOS ปักหมุดแอปที่ใช้บ่อย เห็นแอปที่เปิดอยู่ และสลับหน้าต่างได้ในคลิกเดียว
ไฟล์เดียว เปิดใช้ได้ทันที ไม่ต้องติดตั้ง</p>
<h3>ใช้ฟรี · ไลเซ่นฟรีเปิดการอัปเดตอัตโนมัติ</h3>
<ul>
<li>ตัวโปรแกรมใช้ฟรีทุกฟีเจอร์</li>
<li>สมัครสมาชิก / เข้าสู่ระบบ แล้วกดยืนยันจากในโปรแกรม = ได้ไลเซ่นฟรีผูกกับบัญชีและเครื่องนี้</li>
<li>มีไลเซ่นแล้ว โปรแกรมอัปเดตตัวเองจาก xman4289.com และตรวจไฟล์ก่อนติดตั้งทุกครั้ง</li>
</ul>
</div>
HTML,
            'features' => json_encode([
                'Dock สไตล์ macOS ไอคอนขยายตามเมาส์',
                'ปักหมุดแอป · เห็นแอปที่เปิดอยู่',
                'ไฟล์เดียว ไม่ต้องติดตั้ง',
                'ใช้ฟรีทุกฟีเจอร์',
                'ไลเซ่นฟรี เปิดการอัปเดตอัตโนมัติ',
            ], JSON_UNESCAPED_UNICODE),
            'price' => 0.00,
            'is_custom' => false,
            'requires_license' => true,
            'stock' => 999,
            'is_active' => true,
            'is_coming_soon' => false,
            'coming_soon_until' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
