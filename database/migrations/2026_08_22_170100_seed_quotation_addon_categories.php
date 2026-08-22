<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add-on groups and their options, moved out of
     * QuotationController::$additionalOptions so admins can edit the prices
     * without a code deploy. Data is inlined here rather than read from the
     * controller so this migration keeps working if that array is later removed.
     */
    protected array $groups = [
        'support' => [
            'name' => 'Support & Maintenance',
            'name_th' => 'ซัพพอร์ตและดูแลรักษา',
            'icon' => '🛠️',
            'options' => [
                'priority' => ['name' => 'Priority Support 24/7', 'name_th' => 'ซัพพอร์ตเร่งด่วน 24/7', 'price' => 30000, 'icon' => '⚡'],
                'warranty_1y' => ['name' => '1 Year Warranty', 'name_th' => 'รับประกัน 1 ปี', 'price' => 30000, 'icon' => '🛡️'],
                'warranty_2y' => ['name' => '2 Year Warranty', 'name_th' => 'รับประกัน 2 ปี', 'price' => 50000, 'icon' => '🛡️'],
                'maintenance' => ['name' => 'Annual Maintenance', 'name_th' => 'ดูแลระบบรายปี', 'price' => 60000, 'icon' => '🔧'],
                'bug_fix' => ['name' => 'Bug Fix Package (10 issues)', 'name_th' => 'แพ็คแก้บั๊ก 10 รายการ', 'price' => 25000, 'icon' => '🐛'],
                'monitoring' => ['name' => 'Uptime Monitoring/Year', 'name_th' => 'ตรวจสอบระบบ 24/7/ปี', 'price' => 18000, 'icon' => '📡'],
            ],
        ],
        'delivery' => [
            'name' => 'Delivery & Docs',
            'name_th' => 'ส่งมอบและเอกสาร',
            'icon' => '📦',
            'options' => [
                'source_code' => ['name' => 'Full Source Code', 'name_th' => 'Source Code ทั้งหมด', 'price' => 50000, 'icon' => '💾'],
                'documentation' => ['name' => 'Technical Documentation', 'name_th' => 'เอกสารเทคนิคครบถ้วน', 'price' => 25000, 'icon' => '📝'],
                'training' => ['name' => 'User Training (8 hrs)', 'name_th' => 'อบรมการใช้งาน 8 ชม.', 'price' => 20000, 'icon' => '👨‍🏫'],
                'video_guide' => ['name' => 'Video User Guide', 'name_th' => 'วิดีโอสอนการใช้งาน', 'price' => 15000, 'icon' => '🎬'],
                'user_manual' => ['name' => 'User Manual (Thai)', 'name_th' => 'คู่มือการใช้งาน (ภาษาไทย)', 'price' => 10000, 'icon' => '📖'],
                'api_docs' => ['name' => 'API Documentation', 'name_th' => 'เอกสาร API (Swagger/Postman)', 'price' => 15000, 'icon' => '📋'],
            ],
        ],
        'hosting' => [
            'name' => 'Hosting & Domain',
            'name_th' => 'Hosting และโดเมน',
            'icon' => '☁️',
            'options' => [
                'hosting_basic' => ['name' => 'Cloud Hosting Basic/Year', 'name_th' => 'Cloud Hosting พื้นฐาน/ปี', 'price' => 12000, 'icon' => '🌐'],
                'hosting_pro' => ['name' => 'Cloud Hosting Pro/Year', 'name_th' => 'Cloud Hosting Pro/ปี', 'price' => 36000, 'icon' => '🚀'],
                'hosting_enterprise' => ['name' => 'Cloud Hosting Enterprise/Year', 'name_th' => 'Cloud Hosting Enterprise/ปี', 'price' => 72000, 'icon' => '🏢'],
                'ssl' => ['name' => 'SSL Certificate/Year', 'name_th' => 'ใบรับรอง SSL/ปี', 'price' => 3000, 'icon' => '🔐'],
                'domain' => ['name' => 'Domain Registration/Year', 'name_th' => 'จดโดเมน 1 ปี', 'price' => 500, 'icon' => '🌍'],
                'email' => ['name' => 'Business Email/Year', 'name_th' => 'อีเมลธุรกิจ/ปี', 'price' => 6000, 'icon' => '📧'],
                'cdn' => ['name' => 'CDN Service/Year', 'name_th' => 'บริการ CDN/ปี', 'price' => 15000, 'icon' => '⚡'],
                'backup' => ['name' => 'Daily Backup/Year', 'name_th' => 'สำรองข้อมูลรายวัน/ปี', 'price' => 12000, 'icon' => '💿'],
            ],
        ],
        'design' => [
            'name' => 'Design & Branding',
            'name_th' => 'ออกแบบและแบรนด์',
            'icon' => '🎨',
            'options' => [
                'ui_design' => ['name' => 'UI/UX Design', 'name_th' => 'ออกแบบ UI/UX', 'price' => 35000, 'icon' => '🖌️'],
                'logo' => ['name' => 'Logo Design', 'name_th' => 'ออกแบบโลโก้', 'price' => 8000, 'icon' => '✨'],
                'brand_identity' => ['name' => 'Brand Identity Package', 'name_th' => 'แพ็คเกจอัตลักษณ์แบรนด์', 'price' => 25000, 'icon' => '🏷️'],
                'banner' => ['name' => 'Banner & Social Media', 'name_th' => 'แบนเนอร์และ Social Media', 'price' => 5000, 'icon' => '🖼️'],
                'favicon' => ['name' => 'Favicon & App Icon', 'name_th' => 'Favicon และ App Icon', 'price' => 2000, 'icon' => '📱'],
            ],
        ],
        'seo_marketing' => [
            'name' => 'SEO & Marketing',
            'name_th' => 'SEO และการตลาด',
            'icon' => '📈',
            'options' => [
                'seo_basic' => ['name' => 'Basic SEO Setup', 'name_th' => 'ตั้งค่า SEO พื้นฐาน', 'price' => 15000, 'icon' => '🔍'],
                'seo_monthly' => ['name' => 'Monthly SEO/Month', 'name_th' => 'ดูแล SEO รายเดือน', 'price' => 12000, 'icon' => '📊'],
                'google_ads' => ['name' => 'Google Ads Setup', 'name_th' => 'ตั้งค่า Google Ads', 'price' => 10000, 'icon' => '🎯'],
                'analytics' => ['name' => 'Analytics & Tracking', 'name_th' => 'ติดตั้ง Analytics & Tracking', 'price' => 8000, 'icon' => '📉'],
                'sitemap' => ['name' => 'Sitemap & Schema Markup', 'name_th' => 'Sitemap และ Schema Markup', 'price' => 5000, 'icon' => '🗺️'],
            ],
        ],
    ];

    public function up(): void
    {
        $now = now();
        // Continue after the existing service categories so add-ons sort last.
        $baseOrder = (int) DB::table('quotation_categories')->max('order') + 10;
        $groupOrder = 0;

        foreach ($this->groups as $groupKey => $group) {
            $groupOrder++;

            // updateOrInsert keyed on the unique `key` keeps this safe to re-run
            // and avoids clobbering prices an admin already edited.
            $existing = DB::table('quotation_categories')->where('key', $groupKey)->first();

            if ($existing) {
                DB::table('quotation_categories')
                    ->where('id', $existing->id)
                    ->update(['type' => 'addon', 'updated_at' => $now]);
                $categoryId = $existing->id;
            } else {
                $categoryId = DB::table('quotation_categories')->insertGetId([
                    'key' => $groupKey,
                    'type' => 'addon',
                    'name' => $group['name'],
                    'name_th' => $group['name_th'],
                    'icon' => $group['icon'],
                    'order' => $baseOrder + $groupOrder,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $optionOrder = 0;
            foreach ($group['options'] as $optionKey => $option) {
                $optionOrder++;

                $alreadyThere = DB::table('quotation_options')
                    ->where('quotation_category_id', $categoryId)
                    ->where('key', $optionKey)
                    ->exists();

                if ($alreadyThere) {
                    continue;
                }

                DB::table('quotation_options')->insert([
                    'quotation_category_id' => $categoryId,
                    'key' => $optionKey,
                    'name' => $option['name'],
                    'name_th' => $option['name_th'],
                    'icon' => $option['icon'],
                    'price' => $option['price'],
                    'order' => $optionOrder,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Options go with the category via the cascading foreign key.
        DB::table('quotation_categories')
            ->whereIn('key', array_keys($this->groups))
            ->where('type', 'addon')
            ->delete();
    }
};
