<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Insert Tping product for existing deployments (without re-seeding).
     */
    public function up(): void
    {
        // Find or create the mobile-tools category
        $categoryId = DB::table('categories')->where('slug', 'mobile-tools')->value('id');

        if (!$categoryId) {
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
        $exists = DB::table('products')->where('slug', 'tping')->exists();
        if (!$exists) {
            DB::table('products')->insert([
                'category_id' => $categoryId,
                'name' => 'Tping',
                'slug' => 'tping',
                'sku' => 'TPG-001',
                'short_description' => 'แอพช่วยพิมพ์อัตโนมัติสำหรับ Android บันทึกขั้นตอนแล้วเล่นซ้ำ รองรับเกมและแอพทั่วไป',
                'description' => 'Tping - Auto-Typing Assistant for Android. ช่วยพิมพ์สำหรับผู้ที่ใช้นิ้วไม่สะดวก',
                'features' => json_encode([
                    'บันทึกขั้นตอนการใช้งานอัตโนมัติ',
                    'เล่นซ้ำขั้นตอน (1-999 รอบ)',
                    'จัดการชุดข้อมูล (Data Profiles)',
                    'โหมดเกม — Crosshair Overlay',
                    'Resolution Scaling',
                    'Floating Overlay ใช้ได้ทุกแอพ',
                    'Accessibility Shortcut',
                ]),
                'price' => 299.00,
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
        DB::table('products')->where('slug', 'tping')->delete();
    }
};
