<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * CluadeX Pro รายปีขึ้นจาก ฿199 เป็น ฿599 (เจ้าของ 2026-11-12 พร้อมคลิปโฆษณา v2b — ราคาจริงอยู่ที่
     * config/licenses.php) แต่ products.price ยังเป็น 199 ซึ่งเป็นตัวเลขที่หน้ารายการสินค้าแสดง และเป็นราคาที่
     * ตะกร้าคิดเมื่อ POST มาโดยไม่มี license_type — ตอนนั้น LicenseService ออกคีย์รายปี
     * (Product::defaultLicenseType) ช่องนี้จึงต้องเท่าราคารายปี ไม่ใช่ราคาเริ่มต้นรายเดือน ฿139
     *
     * แก้เฉพาะแถวที่ยังเป็นค่าเก่า 199 — ราคาที่เจ้าของแก้ในหน้า admin ไปแล้วไม่ถูกทับ รันซ้ำได้
     */
    public function up(): void
    {
        DB::table('products')
            ->where('slug', 'cluadex-ai-coding-assistant')
            ->where('price', 199)
            ->update(['price' => 599, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // ไม่ย้อน — rollback โค้ดไม่ควรเอาราคาเก่ากลับขึ้นหน้าร้านขณะที่ตะกร้าขายราคาใหม่
    }
};
