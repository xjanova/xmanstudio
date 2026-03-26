<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Insert LocalVPN product for xmanstudio store.
     */
    public function up(): void
    {
        // Find or create the network-tools category
        $categoryId = DB::table('categories')->where('slug', 'network-tools')->value('id');

        if (! $categoryId) {
            $categoryId = DB::table('categories')->insertGetId([
                'name' => 'Network Tools',
                'slug' => 'network-tools',
                'description' => 'เครื่องมือเครือข่ายและ VPN',
                'icon' => 'shield-check',
                'order' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Only insert if not already exists
        $exists = DB::table('products')->where('slug', 'localvpn')->exists();
        if (! $exists) {
            DB::table('products')->insert([
                'category_id' => $categoryId,
                'name' => 'LocalVPN',
                'slug' => 'localvpn',
                'sku' => 'LVPN-001',
                'short_description' => 'แอพสร้างวง LAN เสมือนผ่านอินเทอร์เน็ต ให้มือถือทุกเครื่องเชื่อมต่อกันเหมือนอยู่วงแลนเดียวกัน',
                'description' => 'LocalVPN - Virtual LAN over Internet สร้างเครือข่าย LAN เสมือนให้อุปกรณ์มือถือเชื่อมต่อกันผ่านอินเทอร์เน็ต ไม่ต้องตั้งค่าเครือข่ายใดๆ สแกนหาเครือข่าย เข้าร่วม หรือสร้างเครือข่ายส่วนตัวพร้อมรหัสผ่าน',
                'features' => json_encode([
                    'สร้างวง LAN เสมือนผ่านอินเทอร์เน็ต',
                    'สแกนหาเครือข่ายที่เปิดให้เข้าร่วม',
                    'ตั้งรหัสเครือข่ายเพื่อความส่วนตัว',
                    'เห็นอุปกรณ์ทั้งหมดในวง LAN เสมือน',
                    'ส่งข้อมูลระหว่างอุปกรณ์ได้โดยตรง',
                    'รองรับ Android และ iOS',
                    'เข้ารหัสข้อมูลด้วย WireGuard Protocol',
                    'NAT Traversal — ใช้ได้ทุกเครือข่าย',
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
        DB::table('products')->where('slug', 'localvpn')->delete();
    }
};
