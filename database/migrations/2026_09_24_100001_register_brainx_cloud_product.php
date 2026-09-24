<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BrainX Cloud — ขายเป็นไลเซนส์รายเดือน ฿399 บนระบบ license กลาง (slug `brainx`)
 *
 * แถวนี้คือทั้งหมดที่ต้องมีให้ขายได้:
 *   - `requires_license = true` เปิด `/api/v1/product/brainx/{status,pricing,...}` ที่มีอยู่แล้ว
 *     เซิร์ฟเวอร์ BrainX Cloud (serverbrain.xman4289.com) ถาม `status/{key}` ทุกครั้งที่ต้องรู้ว่า
 *     คีย์ยังใช้ได้ไหม — ไม่มีแถวนี้ = ทุกคีย์ได้ PRODUCT_NOT_FOUND
 *   - ซื้อซ้ำ = ต่ออายุคีย์เดิม (config/licenses.php) — บัญชีคลาวด์คือคีย์ คีย์ใหม่ = บัญชีว่าง
 *   - ราคาต่อแผนอยู่ที่ CartController::LICENSE_TERM_PRICES และ
 *     ProductLicenseController::getPricingForProduct — ราคาในแถวนี้คือราคาที่หน้ารายการสินค้าแสดง
 *
 * ทำเป็น migration เพราะ deploy รันแค่ `migrate --force` ไม่รัน seeder (เหมือน gpuxmine/localvpn)
 * มีแถวอยู่แล้ว = ไม่แตะเลย ค่าที่เจ้าของแก้ในหน้า admin ไม่ถูกทับ รันซ้ำกี่รอบก็ได้ผลเท่าเดิม
 */
return new class extends Migration
{
    private const SLUG = 'brainx';

    private const SKU = 'BXC-001';

    public function up(): void
    {
        if (DB::table('products')->where('slug', self::SLUG)->exists()) {
            return;
        }

        $categoryId = DB::table('categories')->where('slug', 'cloud-computing')->value('id')
            ?? DB::table('categories')->insertGetId([
                // ค่าเดียวกับ XmanProductsSeeder — ฐานที่ seed แล้ว (production) มีหมวดนี้อยู่แล้ว
                'name' => 'Cloud & Computing',
                'slug' => 'cloud-computing',
                'description' => 'บริการคลาวด์และการประมวลผล',
                'icon' => 'cloud',
                'order' => 7,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('products')->insert([
            'category_id' => $categoryId,
            'name' => 'BrainX Cloud',
            'slug' => self::SLUG,
            // sku ไม่บังคับแต่ต้องไม่ซ้ำ — ชนกับของที่มีอยู่ก็ปล่อยว่าง ดีกว่าให้ migration ล้มกลาง deploy
            'sku' => DB::table('products')->where('sku', self::SKU)->exists() ? null : self::SKU,
            'short_description' => 'สมองที่สองบนคลาวด์ — อัปโหลดโน้ตที่คุณเลือก แล้วให้ Claude ทุกเครื่องใช้ได้ทุกที่ · '
                . 'Your second brain in the cloud: upload the notes you choose and use them from any Claude, anywhere.',
            'description' => 'BrainX Cloud เก็บโฟลเดอร์โน้ตที่คุณเลือกจาก BrainX ไว้ในพื้นที่ส่วนตัวของคุณบนคลาวด์ '
                . 'แล้วให้ Claude ใช้ได้สองทาง: ต่อผ่าน Remote MCP ได้ทันทีโดยไม่ต้องติดตั้งอะไร '
                . 'หรือใช้ brainx-mcp บนเครื่องในโหมดคลาวด์ที่ดึงโน้ตมาเก็บไว้ใช้แบบออฟไลน์ '
                . 'สมองหลักยังอยู่บนเครื่องของคุณเสมอ คลาวด์เก็บเฉพาะสิ่งที่คุณเลือกอัปโหลด '
                . 'ไลเซนส์รายเดือน 399 บาท ต่ออายุแล้วใช้คีย์เดิมตลอดไป'
                . "\n\n"
                . 'BrainX Cloud keeps the note folders you choose from BrainX in your own private cloud space, '
                . 'and lets Claude use them two ways: connect over Remote MCP with nothing to install, '
                . 'or run brainx-mcp locally in cloud mode, which caches your notes for offline use. '
                . 'Your master brain stays on your own machine; the cloud holds only what you upload. '
                . 'Monthly licence, 399 THB — renewing extends the same key, so you keep one key for good.',
            'features' => json_encode([
                'Remote MCP — ใช้กับ Claude ได้ทุกที่ / use it from any Claude',
                'เลือกเองว่าจะอัปโหลดโฟลเดอร์ไหน / you choose the folders',
                'พื้นที่ส่วนตัว 1 GB ต่อบัญชี / 1 GB private space',
                'brainx-mcp โหมดคลาวด์ ใช้ออฟไลน์ได้ / offline cloud mode',
                'ต่ออายุแล้วใช้คีย์เดิม / renewals keep the same key',
            ], JSON_UNESCAPED_UNICODE),
            'price' => 399.00,
            'is_custom' => false,
            'requires_license' => true,
            'stock' => 999,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // ลบได้เฉพาะเมื่อยังไม่เคยขาย — ลบสินค้า = คีย์และรายการในออเดอร์หายตาม (cascade)
        $productId = DB::table('products')->where('slug', self::SLUG)->value('id');

        if ($productId !== null
            && ! DB::table('license_keys')->where('product_id', $productId)->exists()
            && ! DB::table('order_items')->where('product_id', $productId)->exists()) {
            DB::table('products')->where('id', $productId)->delete();
        }
    }
};
