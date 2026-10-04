<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Thai Prompt POS — ลงทะเบียนแอปขายหน้าร้านของร้านค้า Thai Prompt ให้อัปเดตในแอปจาก xman4289.com ได้
 *
 * แอปมีสองตัว แต่ละตัวเช็คอัปเดตด้วย slug ของตัวเอง:
 *   — Android: GET /api/v1/product/thaiprompt-pos/update/check → APK (posthaiprompt-v<ver>.apk)
 *   — Windows: GET /api/v1/product/thaiprompt-pos-windows/update/check → zip (posthaiprompt-windows-v<ver>.zip)
 * ไฟล์ทั้งสองอยู่ใน release เดียวกันของ repo เดียวกัน — GitHub setting หนึ่งแถวเลือกได้ไฟล์เดียว (asset_pattern)
 * จึงเป็นสินค้าสองแถว · ไฟล์ส่งจากเว็บเราเอง (/apps/thaiprompt-pos[-windows]/download/{version}) ลูกค้าต้องไม่เห็น
 * GitHub ทั้งลิงก์ redirect และชื่อ repo (กฎเจ้าของ 2026-09-24)
 *
 * แจกฟรี ไม่ขาย: is_active = false โดยตั้งใจ — สินค้าที่เปิดอยู่ขึ้นหน้ารวมสินค้า หน้าแรก (สินค้าใหม่ 6 ตัว)
 * และคำตอบของผู้ช่วย AI พร้อมปุ่มใส่ตะกร้า ฿0 · ปิดไว้ = ไม่โผล่ที่ไหนเลย หน้า /products/<slug> เป็น 404
 * และตะกร้าไม่รับ (CartController::add) ส่วน update/check, cron sync และ route ดาวน์โหลดของแอปนี้ไม่ดู
 * is_active จึงแจกไฟล์ได้ปกติ · requires_license = false เพราะแอปไม่ส่ง license มาเลย
 * อย่าเปิด is_active ในหน้า admin — จะกลายเป็นสินค้า ฿0 มีปุ่มซื้อ
 *
 * ทำเป็น migration เพราะ deploy รันแค่ `migrate --force` ไม่รัน seeder (เหมือน chanthra-studio/gpuxmine)
 * มีแถวสินค้าอยู่แล้ว = ไม่แตะแถวนั้น แค่เติม GitHub setting ถ้ายังไม่มี · รันซ้ำกี่รอบก็ได้ผลเท่าเดิม
 * repo เป็น public ไม่ต้องมี token (token ที่ตายแล้วแย่กว่าไม่มี — ดู 2026_09_18_000002)
 * auto_sync เปิด: cron (products:sync-releases) ดึง release ล่าสุดเข้ามาเอง หรือทันทีที่แอปเช็คอัปเดต (read-through)
 */
return new class extends Migration
{
    private const REPO_OWNER = 'xjanova';

    private const REPO = 'posthaiprompt';

    private const APPS = [
        [
            'slug' => 'thaiprompt-pos',
            'name' => 'Thai Prompt POS (Android)',
            'sku' => 'TPPOS-AND',
            'short_description' => 'แอปขายหน้าร้านสำหรับร้านค้า Thai Prompt บนมือถือและแท็บเล็ต Android — ใช้ฟรี',
            'platform' => 'Android',
            // CI ของแอปแนบ APK ตัว universal ไฟล์เดียวต่อ release
            'asset_pattern' => 'posthaiprompt-v*.apk',
        ],
        [
            'slug' => 'thaiprompt-pos-windows',
            'name' => 'Thai Prompt POS (Windows)',
            'sku' => 'TPPOS-WIN',
            'short_description' => 'แอปขายหน้าร้านสำหรับร้านค้า Thai Prompt บนคอมพิวเตอร์ Windows — ใช้ฟรี',
            'platform' => 'Windows',
            'asset_pattern' => 'posthaiprompt-windows-v*.zip',
        ],
    ];

    public function up(): void
    {
        foreach (self::APPS as $app) {
            $productId = DB::table('products')->where('slug', $app['slug'])->value('id')
                ?? $this->insertProduct($app);

            if (DB::table('github_settings')->where('product_id', $productId)->exists()) {
                continue;
            }

            DB::table('github_settings')->insert([
                'product_id' => $productId,
                'github_owner' => self::REPO_OWNER,
                'github_repo' => self::REPO,
                'github_token' => '',
                'asset_pattern' => $app['asset_pattern'],
                'is_active' => true,
                'auto_sync' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        foreach (self::APPS as $app) {
            $productId = DB::table('products')->where('slug', $app['slug'])->value('id');

            // ลบได้เฉพาะเมื่อไม่เคยมีคีย์หรือออเดอร์ผูกอยู่ — ลบสินค้า = ของที่ผูกไว้หายตาม
            if ($productId === null
                || DB::table('license_keys')->where('product_id', $productId)->exists()
                || DB::table('order_items')->where('product_id', $productId)->exists()) {
                continue;
            }

            // download_logs หายตามเวอร์ชัน (cascade)
            DB::table('product_versions')->where('product_id', $productId)->delete();
            DB::table('github_settings')->where('product_id', $productId)->delete();
            DB::table('products')->where('id', $productId)->delete();
        }
    }

    /**
     * @param  array{slug: string, name: string, sku: string, short_description: string, platform: string}  $app
     */
    private function insertProduct(array $app): int
    {
        return DB::table('products')->insertGetId([
            'category_id' => $this->categoryId(),
            'name' => $app['name'],
            'slug' => $app['slug'],
            // sku ไม่บังคับแต่ต้องไม่ซ้ำ — ชนกับของที่มีอยู่ก็ปล่อยว่าง ดีกว่าให้ migration ล้มกลาง deploy
            'sku' => DB::table('products')->where('sku', $app['sku'])->exists() ? null : $app['sku'],
            'short_description' => $app['short_description'],
            'description' => '<p>Thai Prompt POS — แอปขายหน้าร้านสำหรับร้านค้าในระบบ Thai Prompt '
                . "บน {$app['platform']} ขายสินค้า ออกใบเสร็จ และจัดการสต็อก "
                . 'แจกฟรีให้ร้านค้า Thai Prompt และอัปเดตเวอร์ชันใหม่ให้เองภายในแอป</p>',
            'features' => json_encode([
                'ขายหน้าร้าน ออกใบเสร็จ',
                'เครื่องพิมพ์ใบเสร็จ ลิ้นชักเก็บเงิน และจอแสดงผลฝั่งลูกค้า',
                'จัดการสินค้าและสต็อก ซิงก์กับร้านบน Thai Prompt',
                'อัปเดตเวอร์ชันใหม่อัตโนมัติภายในแอป',
            ], JSON_UNESCAPED_UNICODE),
            'price' => 0,
            'is_custom' => false,
            'requires_license' => false,
            'stock' => 0,
            // ไม่ขาย — ดูหมายเหตุด้านบน
            'is_active' => false,
            'is_coming_soon' => false,
            'coming_soon_until' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function categoryId(): int
    {
        return DB::table('categories')->where('slug', 'e-commerce')->value('id')
            ?? DB::table('categories')->insertGetId([
                // ค่าเดียวกับ XmanProductsSeeder
                'name' => 'E-Commerce',
                'slug' => 'e-commerce',
                'description' => 'แพลตฟอร์มและเครื่องมือสำหรับอีคอมเมิร์ซ',
                'icon' => 'shopping-cart',
                'order' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }
};
