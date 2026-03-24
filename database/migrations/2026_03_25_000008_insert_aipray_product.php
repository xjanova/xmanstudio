<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Insert Aipray as a product
        $productId = DB::table('products')->insertGetId([
            'category_id' => DB::table('categories')->where('slug', 'apps')->value('id')
                ?? DB::table('categories')->first()?->id
                ?? 1,
            'name' => 'Aipray - Buddhist Chanting Companion',
            'slug' => 'aipray',
            'description' => 'แอปสวดมนต์อัจฉริยะ ใช้ AI ติดตามเสียงสวดมนต์ นับรอบอัตโนมัติ พร้อมบทสวดมนต์กว่า 20 บท รองรับภาษาไทย ใช้งานฟรีตลอดไป พัฒนาโดย XMAN Studio',
            'short_description' => 'AI-Powered Buddhist Chanting Companion - ฟรีตลอดไป',
            'features' => json_encode([
                'บทสวดมนต์ 20+ บท พร้อมตัวอักษรไทย',
                'AI ฟังเสียงสวดจับตำแหน่งอัตโนมัติ',
                'นับรอบอัตโนมัติ พร้อมสั่นเตือน',
                'บันทึกประวัติการสวดมนต์',
                'อัพเดทอัตโนมัติภายในแอพ',
                'ใช้งานออฟไลน์ได้',
                'ร่วมบริจาคเสียงเพื่อพัฒนา AI',
            ]),
            'price' => 0.00,
            'is_active' => true,
            'requires_license' => true,
            'is_coming_soon' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create GitHub setting for auto version sync
        if (DB::getSchemaBuilder()->hasTable('github_settings')) {
            DB::table('github_settings')->insert([
                'product_id' => $productId,
                'github_owner' => 'xjanova',
                'github_repo' => 'Aipray',
                'github_token' => '',
                'asset_pattern' => '*.apk',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create free-forever license key
        DB::table('license_keys')->insert([
            'product_id' => $productId,
            'license_key' => 'AIPRAY-FREE-FOREVER',
            'license_type' => 'lifetime',
            'status' => 'active',
            'max_activations' => 999999,
            'activations' => 0,
            'expires_at' => null,
            'metadata' => json_encode([
                'description' => 'Universal free license for Aipray',
                'created_by' => 'system',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $product = DB::table('products')->where('slug', 'aipray')->first();
        if ($product) {
            DB::table('license_keys')->where('product_id', $product->id)->delete();
            DB::table('github_settings')->where('product_id', $product->id)->delete();
            DB::table('aipray_donations')->where('product_id', $product->id)->delete();
            DB::table('products')->where('id', $product->id)->delete();
        }
    }
};
