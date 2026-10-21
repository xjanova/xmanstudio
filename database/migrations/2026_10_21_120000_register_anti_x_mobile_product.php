<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Anti X Mobile — แอปมือถือคู่หูของ Anti X (Android) แจกฟรีทุกคน แม้ไม่ได้ซื้อ Anti X (เจ้าของกำหนด 2026-10-21)
 *
 * แถวนี้คือทั้งหมดที่แอปต้องมีบนเว็บ:
 *   — `/api/v1/product/anti-x-mobile/{register-device,check-machine}` เครื่องใหม่ได้คีย์ FREE อัตโนมัติ
 *     (FREEMIUM_PRODUCTS ใน ProductLicenseController · getProduct() หาเฉพาะสินค้าที่ requires_license)
 *   — `/api/v1/product/anti-x-mobile/update/check` + `/anti-x-mobile/download/{version}` ส่ง APK จาก xman4289.com เอง
 *   — ปุ่มดาวน์โหลดสาธารณะในหน้า /products/anti-x (#mobile) · /products/anti-x-mobile พาไปที่นั่น
 *
 * GitHub setting: release มาจาก repo **private** xjanova/antix-mobile (CI แนบ AntiX-Mobile-<version>.apk แบบ universal)
 *   — token: ใช้ token อ่านอย่างเดียวตัวเดียวกับ Anti X ถ้ามี (คัดลอกค่าที่เข้ารหัสไว้ในฐานข้อมูล ไม่มี token ในโค้ด)
 *     เจ้าของต้องเพิ่ม repo antix-mobile ให้ token นั้นใน GitHub (fine-grained PAT → Repository access) หรือใส่ token ใหม่ในหน้า admin
 *   — ก่อนมี token ที่เห็น repo: update/check ตอบ "ไม่มีเวอร์ชัน" และ /anti-x-mobile/download ตอบ JSON 404/502 — ไม่มีอะไรพัง
 *
 * ทำเป็น migration เพราะ deploy รันแค่ `migrate --force` · มีแถว/มี setting แล้ว = ไม่แตะ · รันซ้ำได้
 *
 * ⚠️ ชื่อสินค้าต้องเป็น "Anti X Mobile" ต่อไป — หน้า admin สร้าง slug ใหม่จากชื่อทุกครั้งที่บันทึก
 *    (Str::slug('Anti X Mobile') = 'anti-x-mobile') เปลี่ยนชื่อ = ไลเซนส์และการอัปเดตของแอปพังทั้งหมด
 */
return new class extends Migration
{
    private const SLUG = 'anti-x-mobile';

    private const SKU = 'ATXM-001';

    public function up(): void
    {
        $productId = DB::table('products')->where('slug', self::SLUG)->value('id')
            ?? $this->insertProduct();

        if (DB::table('github_settings')->where('product_id', $productId)->exists()) {
            return;
        }

        $antiX = DB::table('products')->where('slug', 'anti-x')->value('id');
        $sharedToken = $antiX === null ? '' : (string) DB::table('github_settings')->where('product_id', $antiX)->value('github_token');

        DB::table('github_settings')->insert([
            'product_id' => $productId,
            'github_owner' => 'xjanova',
            'github_repo' => 'antix-mobile',
            // ค่าที่เข้ารหัสแล้ว (Crypt ด้วย APP_KEY เดียวกัน) — ว่าง = ยังไม่มี token (คอลัมน์ NOT NULL)
            'github_token' => $sharedToken,
            // ต้องเจาะจง .apk: ไม่เจอแล้ว findMatchingAsset() ถอยไปหยิบไฟล์แรกของ release
            'asset_pattern' => 'AntiX-Mobile-*.apk',
            'is_active' => true,
            'auto_sync' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $productId = DB::table('products')->where('slug', self::SLUG)->value('id');

        // มีคีย์แล้ว (มือถือได้ไลเซนส์ฟรีไปแล้ว) = ไม่ลบ — ลบสินค้า = คีย์ของทุกเครื่องหายตาม
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
        $categoryId = DB::table('categories')->where('slug', 'network-security')->value('id')
            ?? DB::table('categories')->insertGetId([
                // ค่าเดียวกับ XmanProductsSeeder / migration ของ Anti X — production มีหมวดนี้อยู่แล้ว
                'name' => 'Network & Security',
                'slug' => 'network-security',
                'description' => 'ซอฟต์แวร์ด้านเครือข่ายและความปลอดภัย',
                'icon' => 'shield',
                'order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'Anti X Mobile',
            'slug' => self::SLUG,
            'sku' => DB::table('products')->where('sku', self::SKU)->exists() ? null : self::SKU,
            'short_description' => 'แอปมือถือคู่หูของ Anti X — ดูสถานะการป้องกัน การโจมตี รายการบล็อก และเหตุการณ์ของเซิร์ฟเวอร์ได้จากทุกที่ '
                . 'จับคู่ด้วย QR · ฟรีทุกคน',
            'description' => <<<'HTML'
<div class="prose max-w-none">
<h2>Anti X Mobile — ดูเซิร์ฟเวอร์ที่ Anti X ดูแลจากมือถือ</h2>
<p>ภาพรวมการป้องกัน การโจมตี 24 ชั่วโมง รายการ IP ที่ถูกบล็อก เหตุการณ์ล่าสุด และรายละเอียดของแต่ละ IP
จับคู่กับเครื่องด้วยการสแกน QR จากหน้า "อุปกรณ์มือถือ" ในโปรแกรม Anti X บนคอม</p>
<h3>ฟรีทุกคน</h3>
<ul>
<li>ดาวน์โหลดได้เลย ไม่ต้องล็อกอิน ไม่ต้องซื้อ Anti X</li>
<li>เปิดแอปครั้งแรกได้ไลเซนส์ฟรีอัตโนมัติ</li>
<li>แอปอัปเดตตัวเองจาก xman4289.com และตรวจไฟล์ก่อนติดตั้งทุกครั้ง</li>
</ul>
</div>
HTML,
            'features' => json_encode([
                'ภาพรวมการป้องกันและการโจมตี 24 ชม.',
                'รายการบล็อก · เหตุการณ์ · รายละเอียด IP',
                'จับคู่ด้วย QR · กุญแจอยู่ในชิปของมือถือ',
                'ล็อกแอปด้วยลายนิ้วมือ / ใบหน้า',
                'ฟรีทุกคน · อัปเดตตัวเองจาก xman4289.com',
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
