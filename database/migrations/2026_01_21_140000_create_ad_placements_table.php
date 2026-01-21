<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ad_placements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('code')->nullable()->comment('Google AdSense code');
            $table->boolean('enabled')->default(false);
            $table->string('position')->default('sidebar')->comment('header, sidebar, in-content, footer, between-products');
            $table->json('pages')->nullable()->comment('Pages where this ad should appear');
            $table->integer('priority')->default(0)->comment('Display priority (higher = more important)');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default ad placements
        $defaultAds = [
            [
                'name' => 'Header Top',
                'slug' => 'header-top',
                'position' => 'header',
                'description' => 'แสดงที่ด้านบนสุดของเว็บไซต์ (ก่อน navigation)',
                'pages' => json_encode(['all']),
                'priority' => 10,
            ],
            [
                'name' => 'Sidebar Right',
                'slug' => 'sidebar-right',
                'position' => 'sidebar',
                'description' => 'แสดงที่ sidebar ด้านขวา',
                'pages' => json_encode(['home', 'products', 'services']),
                'priority' => 5,
            ],
            [
                'name' => 'In Content',
                'slug' => 'in-content',
                'position' => 'in-content',
                'description' => 'แสดงระหว่างเนื้อหา (กลางหน้า)',
                'pages' => json_encode(['products', 'services', 'support']),
                'priority' => 8,
            ],
            [
                'name' => 'Footer Above',
                'slug' => 'footer-above',
                'position' => 'footer',
                'description' => 'แสดงก่อน footer',
                'pages' => json_encode(['all']),
                'priority' => 3,
            ],
            [
                'name' => 'Between Products',
                'slug' => 'between-products',
                'position' => 'between-products',
                'description' => 'แสดงระหว่างรายการสินค้า/บริการ',
                'pages' => json_encode(['products', 'services']),
                'priority' => 7,
            ],
        ];

        foreach ($defaultAds as $ad) {
            DB::table('ad_placements')->insert(array_merge($ad, [
                'enabled' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_placements');
    }
};
