<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Insert SmsChecker product for existing deployments (without re-seeding).
     */
    public function up(): void
    {
        // Find or create the mobile-tools category
        $categoryId = DB::table('categories')->where('slug', 'mobile-tools')->value('id');

        if (! $categoryId) {
            $categoryId = DB::table('categories')->insertGetId([
                'name' => 'Mobile Tools',
                'slug' => 'mobile-tools',
                'description' => 'เครื่องมือจัดการอุปกรณ์มือถือ',
                'icon' => 'mobile',
                'order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Only insert if not already exists
        $exists = DB::table('products')->where('slug', 'smschecker')->exists();
        if (! $exists) {
            DB::table('products')->insert([
                'category_id' => $categoryId,
                'name' => 'SmsChecker',
                'slug' => 'smschecker',
                'sku' => 'SMC-001',
                'short_description' => 'ระบบตรวจสอบ SMS อัตโนมัติ รองรับธนาคารไทย 14+ ธนาคาร พร้อมระบบอนุมัติออเดอร์',
                'description' => 'SmsChecker - ระบบตรวจสอบ SMS การชำระเงินอัตโนมัติ สำหรับร้านค้าออนไลน์ รองรับ KBANK, SCB, KTB, BBL, GSB, BAY, TTB, PromptPay และธนาคารอื่นๆ',
                'features' => json_encode([
                    'ตรวจจับ SMS ธนาคารอัตโนมัติ 14+ ธนาคาร',
                    'ระบบอนุมัติออเดอร์แบบ Real-time',
                    'เชื่อมต่อหลายเซิร์ฟเวอร์พร้อมกัน',
                    'WebSocket + FCM Push Notification',
                    'ดักฟัง Notification จากแอพธนาคาร',
                    'เข้ารหัส AES-256-GCM ทุกข้อมูล',
                    'TTS อ่านยอดเงินอัตโนมัติ',
                ]),
                'price' => 499.00,
                'requires_license' => true,
                'stock' => 999,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('products')->where('slug', 'smschecker')->delete();
    }
};
