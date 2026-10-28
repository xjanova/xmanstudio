<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reverse X — สตูดิโอแกะโปรแกรมด้วย AI บน Windows (หน้าจอ WPF ครอบ REA) ขายแบบฟรีพื้นฐาน + Pro
 *
 * แถวนี้คือทั้งหมดที่แอปต้องมีบนเว็บ (แบบเดียวกับ Anti X):
 *   — `/api/v1/product/reverse-x/{register-device,demo,demo/check,activate,validate,check-machine,deactivate,pricing}`
 *     (ProductLicenseController::getProduct() หาเฉพาะสินค้าที่ requires_license — ไม่มีแถว = PRODUCT_NOT_FOUND
 *     แอปติดหน้าลงทะเบียนด้วย "ลงทะเบียนไม่สำเร็จ: ไม่พบผลิตภัณฑ์")
 *   — `/api/v1/product/reverse-x/update/check` + `/reverse-x/download/{version}` ส่ง zip จาก xman4289.com เอง
 *   — `/products/reverse-x` หน้าขาย (แอปเปิดหน้านี้เมื่อกดซื้อ Pro) ราคาอยู่ที่ config/licenses.php เท่านั้น
 *
 * ราคาในแถว = ฿199 ของรายเดือน (Product::MONTHLY_LICENSE_SLUGS) — การ์ดในหน้ารวมสินค้าแสดงราคานี้
 *
 * GitHub setting: release มาจาก repo **public** xjanova/reverse-x (CI แนบ ReverseX-<version>-win-x64.zip)
 *   — repo public อ่าน release ได้โดยไม่มี token (60 ครั้ง/ชม.) — token ในหน้า admin ใส่ทีหลังได้ถ้าต้องการโควตามากขึ้น
 *   — ห้ามใส่ token ใน migration: repo xmanstudio เป็น public
 *   — ลูกค้าไม่เห็นชื่อ repo ในทุกกรณี (ReleaseDownloadStreamer ดึงไฟล์ผ่านเซิร์ฟเวอร์ ไม่ redirect)
 *   — pattern เจาะจง zip เพราะ findMatchingAsset() หาไม่เจอแล้วถอยไปหยิบไฟล์แรก (release มี .sig / .sha256 ด้วย)
 *
 * ทำเป็น migration เพราะ deploy รันแค่ `migrate --force` ไม่รัน seeder (แบบเดียวกับ anti-x / macdock)
 * มีแถวอยู่แล้ว = ไม่แตะแถวสินค้า · มี GitHub setting แล้ว = ไม่แตะ (token/pattern ที่เจ้าของตั้งเองอยู่ครบ)
 * รันซ้ำกี่รอบก็ได้ผลเท่าเดิม
 *
 * ⚠️ ชื่อสินค้าต้องเป็น "Reverse X" ต่อไป — หน้า admin สร้าง slug ใหม่จากชื่อทุกครั้งที่กดบันทึก
 *    (Str::slug('Reverse X') = 'reverse-x') เปลี่ยนชื่อ = slug เปลี่ยน = license และอัปเดตของแอปพังทั้งหมด
 */
return new class extends Migration
{
    private const SLUG = 'reverse-x';

    private const SKU = 'RVX-001';

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
            'github_repo' => 'reverse-x',
            // repo public ไม่ต้องมี token (คอลัมน์ NOT NULL: ว่าง = ไม่มี) — ห้ามใส่ token ที่นี่
            'github_token' => '',
            'asset_pattern' => 'ReverseX-*-win-x64.zip',
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
        $categoryId = DB::table('categories')->where('slug', 'ai-automation')->value('id')
            ?? DB::table('categories')->insertGetId([
                // ค่าเดียวกับ XmanProductsSeeder — production มีหมวดนี้อยู่แล้ว
                'name' => 'AI & Automation',
                'slug' => 'ai-automation',
                'description' => 'ซอฟต์แวร์ AI และระบบอัตโนมัติ',
                'icon' => 'robot',
                'order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'Reverse X',
            'slug' => self::SLUG,
            // sku ไม่บังคับแต่ต้องไม่ซ้ำ — ชนกับของที่มีอยู่ก็ปล่อยว่าง ดีกว่าให้ migration ล้มกลาง deploy
            'sku' => DB::table('products')->where('sku', self::SKU)->exists() ? null : self::SKU,
            'short_description' => 'สตูดิโอแกะโปรแกรมด้วย AI บน Windows — ลากไฟล์ EXE/DLL, .NET, แอป Electron หรือ APK เข้ามา '
                . 'แล้วเห็นว่าโปรแกรมทำงานอย่างไรพร้อมหลักฐาน (ฟรีทุกหน้าวิเคราะห์ · Pro ปลดผู้ช่วย AI)',
            'description' => <<<'HTML'
<div class="prose max-w-none">
<h2>Reverse X — สตูดิโอแกะโปรแกรมด้วย AI</h2>
<p>โปรแกรม Windows ที่ลากไฟล์หรือแอปเข้ามาแล้วเห็นว่ามันทำงานอย่างไร — ไบนารี EXE/DLL, .NET assembly, แอป JS/Electron
และ Android APK ทุกอย่างวิเคราะห์ในเครื่องของคุณเอง ไม่อัปโหลดไฟล์ไปที่ไหน ทุกข้อสรุปบอกหลักฐานและระดับความมั่นใจ
(เห็นจริง · อนุมาน · ยังไม่รู้)</p>

<h3>ฟรีทุกหน้าวิเคราะห์ · Pro ปลดผู้ช่วย AI</h3>
<ul>
<li>ฟรี: ทุกหน้าวิเคราะห์ — ไบนารี, .NET (CIL + metadata), แอป JS/Electron (โมดูล, IPC, preload), APK,
เว็บ·เน็ตเวิร์ก, ฐานข้อมูล·ไฟล์ และสมุดหลักฐาน</li>
<li>Pro: ผู้ช่วย AI ที่สืบฟีเจอร์ให้พร้อมหลักฐาน และเสนอโค้ดแบบเดียวกันลงโปรเจกต์ของคุณ (ใช้ API key ของคุณเอง)</li>
<li>ทดลอง Pro ฟรี 14 วัน (ครั้งเดียวต่อเครื่อง) · 1 คีย์ใช้ได้ 1 เครื่อง</li>
<li>อัปเดตจาก xman4289.com เท่านั้น ทุกแพ็กเกจมีลายเซ็นดิจิทัลและถูกตรวจก่อนติดตั้ง</li>
</ul>
<p>ใช้กับซอฟต์แวร์ที่คุณมีสิทธิ์วิเคราะห์เท่านั้น</p>
</div>
HTML,
            'features' => json_encode([
                'วิเคราะห์ EXE / DLL (PE, ELF, Mach-O)',
                '.NET assembly — CIL และ metadata',
                'แอป JS / Electron — โมดูล, IPC, preload',
                'Android APK',
                'ทุกข้อสรุปมีหลักฐาน · วิเคราะห์ในเครื่อง',
                'ผู้ช่วย AI สืบฟีเจอร์ (Pro)',
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
