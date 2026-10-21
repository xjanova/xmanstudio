<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * WinXTools ขายแล้ว — Pro ฿199 ต่อปี แต่แถวสินค้าบน production ยังเป็นค่าก่อนเปิดขาย
     * (price 990, is_coming_soon = 1 ไม่มีใครแตะตั้งแต่ 2026-01-25) หน้ารายการสินค้าและหน้าแรก
     * ของร้านจึงขึ้น ฿990 พร้อมป้าย Coming Soon ขณะที่หน้า /products/winx-tools, pricing API
     * (ProductLicenseController::getPricingForProduct) และ CartController::LICENSE_TERM_PRICES
     * ขาย ฿199 ต่อปีอยู่แล้ว — ราคาในแถวนี้คือราคาที่หน้ารายการแสดง และเป็นราคาที่ตะกร้าคิด
     * เมื่อ POST มาโดยไม่มี license_type
     *
     * แก้ทีละช่อง เฉพาะช่องที่ยังเป็นค่าเก่าอยู่ — ราคาหรือสถานะที่เจ้าของแก้ในหน้า admin ไปแล้ว
     * ไม่ถูกทับ รันซ้ำกี่รอบก็ได้ผลเท่าเดิม ไม่มีสินค้า winx-tools ก็ไม่ทำอะไร
     * (deploy รันแค่ `migrate --force` ไม่รัน seeder — XmanProductsSeeder ตั้ง 199 ไว้อยู่แล้ว)
     */
    public function up(): void
    {
        DB::table('products')
            ->where('slug', 'winx-tools')
            ->where('price', 990)
            ->update(['price' => 199, 'updated_at' => now()]);

        DB::table('products')
            ->where('slug', 'winx-tools')
            ->where('is_coming_soon', true)
            ->update(['is_coming_soon' => false, 'coming_soon_until' => null, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // ไม่ย้อน — rollback โค้ดไม่ควรเอาราคาที่ผิด (฿990 + Coming Soon) กลับขึ้นหน้าร้าน
    }
};
