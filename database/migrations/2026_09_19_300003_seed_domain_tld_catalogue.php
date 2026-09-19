<?php

use App\Models\DomainTld;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * A starter catalogue so the shop has something to show on day one.
     *
     * The costs here are placeholders in the right ballpark, NOT real prices.
     * They exist so the pages render and the operator can see the maths work;
     * the first run of `domains:sync-catalogue` overwrites every cost and
     * item id with what the registrar actually charges. Until that runs, the
     * item ids are null and the order path refuses to sell — which is the
     * correct failure: better a "coming soon" than a sale at a made-up price.
     *
     * Prices are USD cents, matching the upstream catalogue's own unit.
     */
    public function up(): void
    {
        $now = now();

        $rows = [
            // tld, cost, renew, featured, default-search, sort, th, en
            ['com', 1099, 1599, true, true, 10, 'นิยมที่สุดในโลก เหมาะกับทุกธุรกิจ', 'The world default — fits any business'],
            ['net', 1399, 1899, true, true, 20, 'ทางเลือกคลาสสิกเมื่อ .com ไม่ว่าง', 'The classic fallback when .com is gone'],
            ['org', 1299, 1799, true, true, 30, 'องค์กร มูลนิธิ และชุมชน', 'Organisations, foundations and communities'],
            ['co', 2999, 3499, true, true, 40, 'สั้น จำง่าย นิยมกับสตาร์ทอัพ', 'Short and memorable — a startup favourite'],
            ['io', 4999, 5999, true, true, 50, 'ยอดนิยมในวงการเทคโนโลยี', 'The tech industry standard'],
            ['dev', 1499, 1999, true, false, 60, 'สำหรับนักพัฒนาและงานสายเทค', 'For developers and technical work'],
            ['app', 1799, 2199, false, false, 70, 'แอปพลิเคชันและบริการออนไลน์', 'Apps and online services'],
            ['shop', 3499, 3999, true, true, 80, 'ร้านค้าออนไลน์', 'Online stores'],
            ['store', 4999, 5999, false, false, 90, 'ร้านค้าและอีคอมเมิร์ซ', 'Retail and ecommerce'],
            ['online', 3999, 4599, false, false, 100, 'ธุรกิจที่เริ่มต้นบนออนไลน์', 'Businesses that start online'],
            ['site', 2999, 3499, false, false, 110, 'เว็บไซต์ทั่วไป', 'General-purpose websites'],
            ['tech', 4999, 5499, false, false, 120, 'บริษัทและบริการด้านเทคโนโลยี', 'Technology companies and services'],
            ['cloud', 2499, 2999, false, false, 130, 'บริการคลาวด์และโครงสร้างพื้นฐาน', 'Cloud services and infrastructure'],
            ['ai', 8999, 9999, true, false, 140, 'ปัญญาประดิษฐ์และผลิตภัณฑ์ AI', 'Artificial intelligence products'],
            ['xyz', 1299, 1499, false, false, 150, 'ราคาประหยัด ใช้ได้ทุกแบบ', 'Cheap and cheerful, fits anything'],
            ['info', 1999, 2399, false, false, 160, 'เว็บข้อมูลและสาระ', 'Information and reference sites'],
            ['biz', 1899, 2299, false, false, 170, 'ธุรกิจขนาดเล็กและกลาง', 'Small and medium business'],
            ['me', 1999, 2399, false, false, 180, 'เว็บส่วนตัวและโปรไฟล์', 'Personal sites and profiles'],
        ];

        foreach ($rows as [$tld, $cost, $renew, $featured, $searchDefault, $sort, $th, $en]) {
            // updateOrCreate so re-running on an environment that already has
            // synced prices does not stamp the placeholders back over them.
            DomainTld::firstOrCreate(
                ['tld' => $tld],
                [
                    'cost_usd_cents' => $cost,
                    'renew_cost_usd_cents' => $renew,
                    'is_active' => true,
                    'is_featured' => $featured,
                    'search_by_default' => $searchDefault,
                    'sort_order' => $sort,
                    'description_th' => $th,
                    'description_en' => $en,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        // Only the rows this migration introduced, and only while they are
        // still untouched by a real sync — a catalogue an operator has since
        // priced by hand is not ours to delete.
        DomainTld::whereNull('synced_at')->whereNull('item_id_register')->delete();
    }
};
