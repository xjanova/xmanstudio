<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ชุดแคมเปญ NVIDIA DGX Spark + CluadeX + BrainX ตลอดชีพ — แถวสินค้าที่รายการ "ตัวเครื่อง" ในออเดอร์ชี้ไป
 * (ขายที่ /dgx-spark เท่านั้น — App\Support\DgxSparkCampaign)
 *
 *   - is_active = false โดยตั้งใจ: ไม่ขึ้นหน้าร้าน ไม่เข้าตะกร้า (ตะกร้าบวก VAT ทับ รับบัตร คูปอง
 *     ค่าคอม affiliate และไม่มีเพดาน 20 ชุด) — CartController ปฏิเสธสินค้านี้ซ้ำอีกชั้นแม้มีคนเปิดใช้ในหน้า admin
 *   - requires_license = false: ตัวเครื่องไม่มี license — license ของ CluadeX และ BrainX ออกจากรายการ ฿0
 *     ของสินค้าทั้งสองตัวในออเดอร์เดียวกัน
 *   - ราคาในแถวนี้แค่ค่าแสดง ราคาที่คิดจริง = ราคา JIB + ส่วนต่าง ที่หน้า /admin/campaigns/dgx-spark
 *   - จำนวนที่เหลือนับจากออเดอร์จริง ไม่ได้อ่านจาก stock
 *
 * ⚠️ อย่าเปลี่ยนชื่อสินค้าในหน้า admin: Admin\ProductController::update สร้าง slug ใหม่จากชื่อทุกครั้ง
 *    (แคมเปญยังหาเจอด้วย SKU DGX-SPARK-BUNDLE แต่อย่าแก้ทั้งสองอย่าง)
 *
 * ทำเป็น migration เพราะ deploy รันแค่ `migrate --force` ไม่รัน seeder · มีแถวแล้ว = ไม่แตะ
 */
return new class extends Migration
{
    private const SLUG = 'dgx-spark-bundle';

    private const SKU = 'DGX-SPARK-BUNDLE';

    public function up(): void
    {
        if (DB::table('products')->where('slug', self::SLUG)->orWhere('sku', self::SKU)->exists()) {
            return;
        }

        $categoryId = DB::table('categories')->where('slug', 'cloud-computing')->value('id')
            ?? DB::table('categories')->insertGetId([
                // ค่าเดียวกับ XmanProductsSeeder / 2026_09_24_100001 — production มีหมวดนี้อยู่แล้ว
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
            'name' => 'NVIDIA DGX Spark + CluadeX & BrainX ตลอดชีพ (ชุดแคมเปญ)',
            'slug' => self::SLUG,
            'sku' => self::SKU,
            'short_description' => 'NVIDIA DGX Spark (Leadtek, 128 GB unified memory, 4 TB NVMe, ประกันศูนย์ไทย 1 ปี) '
                . 'พร้อม CluadeX และ BrainX Cloud แบบตลอดชีพ — จำกัด 20 ชุด สั่งได้ที่ /dgx-spark เท่านั้น',
            'description' => 'ชุดแคมเปญ: เครื่อง NVIDIA DGX Spark (Leadtek) 1 เครื่อง + CluadeX License ตลอดชีพ '
                . '+ BrainX Cloud License ตลอดชีพ ราคาอ้างอิงจากราคาเครื่องที่ JIB บวกส่วนต่างคงที่ '
                . 'ชำระด้วยการโอนเงินหรือพร้อมเพย์ License ออกให้ทันทีเมื่อยืนยันการชำระเงิน',
            'features' => json_encode([
                'NVIDIA DGX Spark (Leadtek) 128 GB / 4 TB',
                'CluadeX License ตลอดชีพ',
                'BrainX Cloud License ตลอดชีพ',
                'ประกันศูนย์ไทย 1 ปี ผ่านผู้จัดจำหน่าย',
            ], JSON_UNESCAPED_UNICODE),
            'price' => 265000.00,
            'is_custom' => false,
            'requires_license' => false,
            'stock' => 20,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // ลบได้เฉพาะเมื่อยังไม่มีออเดอร์ — ลบสินค้า = รายการในออเดอร์หายตาม (cascade)
        $productId = DB::table('products')->where('slug', self::SLUG)->value('id');

        if ($productId !== null && ! DB::table('order_items')->where('product_id', $productId)->exists()) {
            DB::table('products')->where('id', $productId)->delete();
        }
    }
};
