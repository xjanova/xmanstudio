<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Anti X — ระบบกันการเจาะเซิร์ฟเวอร์ Windows (บริการ Windows + หน้าจอ WPF) ขายแบบฟรีพื้นฐาน + Pro
 *
 * แถวนี้คือทั้งหมดที่แอปต้องมีบนเว็บ:
 *   — `/api/v1/product/anti-x/{register-device,demo,demo/check,activate,validate,check-machine,deactivate,pricing}`
 *     (ProductLicenseController::getProduct() หาเฉพาะสินค้าที่ requires_license — ไม่มีแถว = PRODUCT_NOT_FOUND)
 *   — `/api/v1/product/anti-x/update/check` + `/anti-x/download/{version}` ส่ง zip จาก xman4289.com เอง
 *   — `/products/anti-x` หน้าขาย (แอปเปิดหน้านี้เมื่อกดซื้อ Pro) ราคาอยู่ที่ config/licenses.php เท่านั้น
 *
 * ราคาในแถว = ฿199 ของรายเดือน (Product::MONTHLY_LICENSE_SLUGS) — การ์ดในหน้ารวมสินค้าแสดงราคานี้
 *
 * GitHub setting: release มาจาก repo **private** xjanova/antix (CI แนบ AntiX-<version>-win-x64.zip)
 *   — repo private อ่าน release และโหลดไฟล์ไม่ได้ถ้าไม่มี token ⇒ เจ้าของต้องใส่ token ในหน้า admin
 *     (สินค้า → เวอร์ชัน → GitHub Settings) เป็น fine-grained token อ่านอย่างเดียว (Contents: Read) เฉพาะ repo นี้
 *   — ห้ามใส่ token ใน migration: repo xmanstudio เป็น public
 *   — ก่อนมี token: update/check ตอบ "ไม่มีอัปเดต" และ /anti-x/download ตอบ 404/502 เป็น JSON — ไม่มีอะไรพัง
 *     ลูกค้าไม่เห็นชื่อ repo ในทุกกรณี (ReleaseDownloadStreamer ดึงไฟล์ผ่านเซิร์ฟเวอร์ ไม่ redirect)
 *   — pattern เจาะจง zip เพราะ findMatchingAsset() หาไม่เจอแล้วถอยไปหยิบไฟล์แรก
 *
 * ทำเป็น migration เพราะ deploy รันแค่ `migrate --force` ไม่รัน seeder (แบบเดียวกับ giggok / chanthra-studio)
 * มีแถวอยู่แล้ว = ไม่แตะแถวสินค้า · มี GitHub setting แล้ว = ไม่แตะ (token/pattern ที่เจ้าของตั้งเองอยู่ครบ)
 * รันซ้ำกี่รอบก็ได้ผลเท่าเดิม
 *
 * ⚠️ ชื่อสินค้าต้องเป็น "Anti X" ต่อไป — หน้า admin สร้าง slug ใหม่จากชื่อทุกครั้งที่กดบันทึก
 *    (Str::slug('Anti X') = 'anti-x') เปลี่ยนชื่อ = slug เปลี่ยน = license และอัปเดตของแอปพังทั้งหมด
 */
return new class extends Migration
{
    private const SLUG = 'anti-x';

    private const SKU = 'ANX-001';

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
            'github_repo' => 'antix',
            // repo private — เจ้าของใส่ token เองในหน้า admin (คอลัมน์ NOT NULL: ว่าง = ยังไม่มี)
            'github_token' => '',
            'asset_pattern' => 'AntiX-*-win-x64.zip',
            'is_active' => true,
            'auto_sync' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $productId = DB::table('products')->where('slug', self::SLUG)->value('id');

        // ลบได้เฉพาะเมื่อยังไม่เคยขายและยังไม่มีเครื่องไหนได้คีย์ (รวมคีย์ทดลอง) — ลบสินค้า = คีย์และรายการในออเดอร์หายตาม
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
                // ค่าเดียวกับ XmanProductsSeeder — production มีหมวดนี้อยู่แล้ว
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
            'name' => 'Anti X',
            'slug' => self::SLUG,
            // sku ไม่บังคับแต่ต้องไม่ซ้ำ — ชนกับของที่มีอยู่ก็ปล่อยว่าง ดีกว่าให้ migration ล้มกลาง deploy
            'sku' => DB::table('products')->where('sku', self::SKU)->exists() ? null : self::SKU,
            'short_description' => 'ระบบกันการเจาะเซิร์ฟเวอร์ Windows แบบเรียลไทม์ — บล็อกการเดารหัส RDP/SSH/ฐานข้อมูล '
                . 'สแกนพอร์ต ยิงหาช่องโหว่ และ IP อันตราย พร้อมจับเครื่องที่ถูกเจาะ (ฟรีพื้นฐาน · Pro ปลดตัวจับขั้นสูงทั้งหมด)',
            'description' => <<<'HTML'
<div class="prose max-w-none">
<h2>Anti X — กันการเจาะเซิร์ฟเวอร์ Windows แบบเรียลไทม์</h2>
<p>บริการ Windows ที่ทำงานตลอดเวลา (แม้ปิดหน้าจอหรือตัด RDP) อ่าน event log และทราฟฟิกของเครื่องสด ๆ
แล้วบล็อก IP ที่เดารหัส RDP / SSH / ฐานข้อมูล สแกนพอร์ต แตะพอร์ตอันตราย ยิงหาช่องโหว่เว็บ หรืออยู่ในรายชื่อ IP อันตราย
ด้วย Windows Firewall ทีละ IP — ไม่ปิดพอร์ต RDP ทั้งพอร์ต คนที่กำลังต่อ RDP อยู่ไม่มีวันโดนบล็อก</p>

<h3>ฟรีพื้นฐาน · Pro ปลดตัวจับขั้นสูงทั้งหมด</h3>
<ul>
<li>ฟรี: บล็อกการเดารหัส สแกนพอร์ต และการแตะพอร์ตอันตรายบนเครื่องนี้ พร้อมหน้าจอดูการเชื่อมต่อสด แบล็คลิสต์ ไวท์ลิสต์</li>
<li>Pro: ตัวจับขั้นสูงทั้งหมด — สเปรย์รหัส เดารหัสแบบค่อย ๆ ยิง หลาย IP รุมบัญชีเดียว สแกนแบบซ่อนตัว ยิงหาช่องโหว่เว็บ
รายชื่อ IP อันตราย จับเครื่องที่ถูกเจาะไปแล้ว และดูแล VM บนเครื่องเดียวกัน</li>
<li>ทดลอง Pro ฟรี 14 วัน (ครั้งเดียวต่อเครื่อง) · 1 คีย์ใช้ได้ 1 เครื่อง</li>
<li>อัปเดตจาก xman4289.com เท่านั้น ทุกแพ็กเกจมีลายเซ็นดิจิทัลและถูกตรวจก่อนติดตั้ง</li>
</ul>
</div>
HTML,
            'features' => json_encode([
                'บล็อกเดารหัส RDP / SSH / ฐานข้อมูล',
                'บล็อกสแกนพอร์ตและการแตะพอร์ตอันตราย',
                'ไม่ปิดพอร์ต RDP · ไม่บล็อกคนที่กำลังต่อ RDP',
                'ยิงหาช่องโหว่เว็บ · รายชื่อ IP อันตราย (Pro)',
                'จับเครื่องที่ถูกเจาะไปแล้ว (Pro)',
                'ดูแล VM บนเครื่องเดียวกันผ่าน SSH (Pro)',
                'อัปเดตในตัว ตรวจลายเซ็นก่อนติดตั้ง',
            ], JSON_UNESCAPED_UNICODE),
            'price' => 199.00,
            'is_custom' => false,
            // ProductLicenseController::getProduct() หาเฉพาะสินค้าที่ requires_license
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
