<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * GigGok — ลงทะเบียนแอป (เลขาสาว 3D บน Android) เป็นสินค้าฟรี พร้อมผูก release ให้ส่ง APK จาก xman4289.com เอง
 *
 * แอปพึ่งสินค้าแถวนี้อยู่สามทาง แต่ production ไม่เคยมีแถวนี้เลย:
 *   — /api/v1/product/giggok/check-machine แจก license ฟรีให้ทุกเครื่อง (getProduct() ต้องการ requires_license)
 *   — /api/v1/product/giggok/update/check ส่ง APK + sha256 ให้ตัวอัปเดตในแอป (ไม่มีแถว = 404 แอปถอยไปอ่าน GitHub เอง)
 *   — ร้านชุด (/api/packs/mine) และพร็อกซี AI ยืนยันตัวด้วย license ของสินค้านี้ (config/packs.php app_product_slug)
 *
 * เจ้าของเลือก (2026-10-05): ตัวแอปฟรี ราคา 0 ทุกเครื่องได้ license ฟรีอัตโนมัติ ของที่ขายคือชุดตัวมายด์ (หมวด giggok-packs)
 * ตัวแอปจึงอยู่หมวด mobile-tools ที่เปิดให้เห็นบนเว็บ (หมวดเดียวกับ Tping) ไม่ใช่ giggok-packs ที่ซ่อนจากหน้าร้าน
 *
 * ทำเป็น migration เพราะ deploy รันแค่ `migrate --force` ไม่รัน seeder
 * มีแถว giggok อยู่แล้ว (เช่นสร้างในหน้า admin) = ไม่แตะแถวสินค้า แค่เติม GitHub setting ถ้ายังไม่มี · รันซ้ำกี่รอบก็ได้ผลเท่าเดิม
 *
 * GitHub setting: repo xjanova/videogirl เป็น public ไม่ต้องมี token (token ที่ตายแล้วแย่กว่าไม่มี — ดู 2026_09_18_000002)
 * release มีสามไฟล์ (giggok-X.Y.Z.apk, SHA256SUMS.txt, debug-symbols-X.Y.Z.zip) — pattern ต้องเจาะจง APK
 * เพราะ findMatchingAsset() หาไม่เจอแล้วถอยไปหยิบไฟล์แรก ซึ่งอาจเป็น zip ของ debug symbols
 *
 * ⚠️ ชื่อสินค้าต้องเป็น "GigGok" ต่อไป — หน้า admin สร้าง slug ใหม่จากชื่อทุกครั้งที่กดบันทึก
 *    (Admin\ProductController::update) เปลี่ยนชื่อ = slug เปลี่ยน = license / อัปเดต / ร้านชุดของแอปพังทั้งหมด
 */
return new class extends Migration
{
    private const SLUG = 'giggok';

    private const SKU = 'GGK-001';

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
            'github_repo' => 'videogirl',
            'github_token' => '',
            'asset_pattern' => 'giggok-*.apk',
            'is_active' => true,
            'auto_sync' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $productId = DB::table('products')->where('slug', self::SLUG)->value('id');

        // ลบได้เฉพาะเมื่อยังไม่มีเครื่องไหนได้ license และไม่เคยอยู่ในออเดอร์ — ลบสินค้า = คีย์ของทุกเครื่องหายตาม
        if ($productId === null
            || DB::table('license_keys')->where('product_id', $productId)->exists()
            || DB::table('order_items')->where('product_id', $productId)->exists()) {
            return;
        }

        DB::table('product_versions')->where('product_id', $productId)->delete();
        DB::table('github_settings')->where('product_id', $productId)->delete();
        DB::table('products')->where('id', $productId)->delete();
    }

    private function insertProduct(): int
    {
        $categoryId = DB::table('categories')->where('slug', 'mobile-tools')->value('id')
            ?? DB::table('categories')->insertGetId([
                // ค่าเดียวกับ 2026_03_04_000001_insert_tping_product — production มีหมวดนี้อยู่แล้ว
                'name' => 'Mobile Tools',
                'slug' => 'mobile-tools',
                'description' => 'เครื่องมือจัดการอุปกรณ์มือถือ',
                'icon' => 'mobile',
                'order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'GigGok',
            'slug' => self::SLUG,
            // sku ไม่บังคับแต่ต้องไม่ซ้ำ — ชนกับของที่มีอยู่ก็ปล่อยว่าง ดีกว่าให้ migration ล้มกลาง deploy
            'sku' => DB::table('products')->where('sku', self::SKU)->exists() ? null : self::SKU,
            'short_description' => 'เลขาสาว 3D ผู้ช่วยส่วนตัวบนมือถือ คุยได้ พูดได้ รับสายแทนได้ '
                . 'คิดด้วยสมองในเครื่องเป็นหลัก ข้อมูลไม่ออกนอกเครื่อง',
            'description' => <<<'HTML'
<div class="prose max-w-none">
<h2>GigGok — เลขาสาว 3D บนมือถือ</h2>
<p>ผู้ช่วยส่วนตัวที่มีตัวตนจริงบนหน้าจอ คุยด้วยเสียงได้ ตอบกลับเป็นเสียง ช่วยรับสายและรับฝากเรื่องแทนคุณ
สมองหลักทำงานในเครื่อง (Gemma) ใช้ GPU ของมือถือได้ บทสนทนาและข้อมูลส่วนตัวไม่ต้องส่งออกนอกเครื่อง</p>

<h3>ใช้ฟรี</h3>
<ul>
<li>ดาวน์โหลดและใช้งานได้ฟรี ทุกเครื่องได้ License ฟรีอัตโนมัติตอนเปิดแอปครั้งแรก ไม่ต้องสมัครสมาชิก</li>
<li>ชุดตัวมายด์ซื้อแยกได้ในร้านชุดในแอป — ผูกเครื่องกับบัญชี XMAN แล้วชุดที่ซื้อบนเว็บจะขึ้นในแอปทันที</li>
<li>แอปอัปเดตตัวเองจาก xman4289.com และตรวจไฟล์ด้วย SHA-256 ก่อนติดตั้งทุกครั้ง</li>
</ul>
</div>
HTML,
            'features' => json_encode([
                'อวาตาร์ 3D ขยับปากตามเสียงจริง',
                'สมองในเครื่อง (Gemma) ใช้ GPU ได้ ไม่ต้องต่อเน็ต',
                'ไมค์ถอดเสียงในเครื่อง',
                'รับสายแทน รับฝากเรื่อง',
                'อ่านปฏิทินจริงของเครื่อง',
                'ร้านชุดตัวมายด์',
                'สตูดิโอวิดีโอ: แชร์จอเข้าวิดีโอคอล ฉากเขียวสำหรับไลฟ์ อัดคลิปลงเครื่อง',
                'อัปเดตในตัว ตรวจ SHA-256',
            ], JSON_UNESCAPED_UNICODE),
            'price' => 0.00,
            'is_custom' => false,
            // ProductLicenseController::getProduct() หาเฉพาะสินค้าที่ requires_license — ไม่ตั้ง = check-machine ตอบ 404
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
