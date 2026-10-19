<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BrainX Cloud — พื้นที่เพิ่ม: สินค้าที่ตะกร้าใช้ขายแพ็กพื้นที่คลาวด์ให้คีย์ brainx ของลูกค้าเอง
 *
 *   - ราคาต่อแพ็ก (+4 / +12 / +28 GB) อยู่ที่ config/licenses.php → cloud_storage.brainx.addons
 *     ราคาในแถวนี้คือแพ็กเล็กสุด ไว้ให้หน้า admin แสดงเท่านั้น ตะกร้าคิดตามแพ็กที่เลือก
 *   - requires_license = false: ไม่ออกคีย์ใหม่ — LicenseService เพิ่มพื้นที่ให้คีย์ที่ลูกค้าเลือก
 *   - ไม่ขึ้นหน้าร้าน (Product::scopeOnWebsite) และหน้าสินค้าพาไปหน้า License ของลูกค้า
 *     เพราะต้องเลือกคีย์ก่อนซื้อ
 *
 * ทำเป็น migration เพราะ deploy รันแค่ `migrate --force` (เหมือน brainx) มีแถวอยู่แล้ว = ไม่แตะ
 */
return new class extends Migration
{
    private const SLUG = 'brainx-cloud-storage';

    private const SKU = 'BXC-STORAGE';

    public function up(): void
    {
        if (DB::table('products')->where('slug', self::SLUG)->exists()) {
            return;
        }

        $categoryId = DB::table('categories')->where('slug', 'cloud-computing')->value('id')
            ?? DB::table('categories')->insertGetId([
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
            'name' => 'BrainX Cloud — พื้นที่เพิ่ม',
            'slug' => self::SLUG,
            'sku' => DB::table('products')->where('sku', self::SKU)->exists() ? null : self::SKU,
            'short_description' => 'เพิ่มพื้นที่คลาวด์ให้คีย์ BrainX Cloud ของคุณ จ่ายครั้งเดียว อยู่กับคีย์ตลอด · '
                . 'More cloud space for your BrainX Cloud key — paid once, stays with the key.',
            'description' => 'แพ็กพื้นที่เพิ่มสำหรับคีย์ BrainX Cloud ที่คุณถืออยู่ ซื้อได้จากหน้า License ของคุณ '
                . 'พื้นที่บวกเข้ากับ 4 GB ของแพ็กเกจ และอยู่กับคีย์นั้นตลอด ต่ออายุกี่ครั้งก็ยังอยู่'
                . "\n\n"
                . 'Extra space for a BrainX Cloud key you hold, bought from your license page. '
                . 'It adds to the plan\'s 4 GB and stays with that key through every renewal.',
            'price' => 99.00,
            'is_custom' => false,
            'requires_license' => false,
            'stock' => 999,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // ลบได้เฉพาะเมื่อยังไม่เคยขาย
        $productId = DB::table('products')->where('slug', self::SLUG)->value('id');

        if ($productId !== null && ! DB::table('order_items')->where('product_id', $productId)->exists()) {
            DB::table('products')->where('id', $productId)->delete();
        }
    }
};
