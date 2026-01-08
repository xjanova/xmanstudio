<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Smart Database Seeder
 *
 * This seeder intelligently handles data seeding:
 * - Skips if data already exists
 * - Updates existing records if needed
 * - Adds new records without duplicating
 * - Safe for production deployments
 */
class SmartDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Running Smart Database Seeder...');

        $this->seedPaymentSettings();
        $this->seedDefaultCategories();
        $this->seedDefaultServices();
        $this->seedRentalPackages();
        $this->seedQuotationData();

        $this->command->info('✅ Smart seeding completed!');
    }

    /**
     * Seed payment settings with upsert logic
     */
    protected function seedPaymentSettings(): void
    {
        $this->command->info('  → Syncing payment settings...');

        $settings = [
            [
                'key' => 'promptpay_enabled',
                'group' => 'promptpay',
                'value' => 'true',
                'type' => 'boolean',
                'label' => 'เปิดใช้งาน PromptPay',
                'description' => 'เปิด/ปิด การชำระเงินผ่าน PromptPay',
            ],
            [
                'key' => 'promptpay_number',
                'group' => 'promptpay',
                'value' => '',
                'type' => 'string',
                'label' => 'หมายเลข PromptPay',
                'description' => 'หมายเลขโทรศัพท์หรือเลขประจำตัวผู้เสียภาษี',
            ],
            [
                'key' => 'promptpay_name',
                'group' => 'promptpay',
                'value' => '',
                'type' => 'string',
                'label' => 'ชื่อบัญชี PromptPay',
                'description' => 'ชื่อที่จะแสดงให้ลูกค้าเห็น',
            ],
            [
                'key' => 'bank_transfer_enabled',
                'group' => 'bank',
                'value' => 'true',
                'type' => 'boolean',
                'label' => 'เปิดใช้งานโอนเงินธนาคาร',
                'description' => 'เปิด/ปิด การชำระเงินผ่านการโอนเงิน',
            ],
            [
                'key' => 'card_payment_enabled',
                'group' => 'card',
                'value' => 'false',
                'type' => 'boolean',
                'label' => 'เปิดใช้งานบัตรเครดิต/เดบิต',
                'description' => 'เปิด/ปิด การชำระเงินผ่านบัตร',
            ],
            [
                'key' => 'payment_timeout_hours',
                'group' => 'general',
                'value' => '24',
                'type' => 'string',
                'label' => 'เวลาหมดอายุการชำระ (ชั่วโมง)',
                'description' => 'จำนวนชั่วโมงก่อนที่การชำระเงินจะหมดอายุ',
            ],
            [
                'key' => 'auto_cancel_unpaid',
                'group' => 'general',
                'value' => 'true',
                'type' => 'boolean',
                'label' => 'ยกเลิกอัตโนมัติเมื่อไม่ชำระ',
                'description' => 'ยกเลิกคำสั่งซื้ออัตโนมัติเมื่อหมดเวลาชำระ',
            ],
        ];

        if (! \Schema::hasTable('payment_settings')) {
            $this->command->warn('  ⚠ Table payment_settings does not exist, skipping...');

            return;
        }

        foreach ($settings as $setting) {
            try {
                $exists = DB::table('payment_settings')
                    ->where('key', $setting['key'])
                    ->exists();

                if (! $exists) {
                    DB::table('payment_settings')->insert(array_merge($setting, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                    $this->command->line("    + Added: {$setting['key']}");
                }
            } catch (\Exception $e) {
                $this->command->error("    ✗ Failed to add setting {$setting['key']}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Seed default categories
     */
    protected function seedDefaultCategories(): void
    {
        if (! \Schema::hasTable('categories')) {
            return;
        }

        $this->command->info('  → Syncing categories...');

        $categories = [
            ['name' => 'Software', 'slug' => 'software', 'description' => 'ซอฟต์แวร์และแอปพลิเคชัน'],
            ['name' => 'Services', 'slug' => 'services', 'description' => 'บริการพัฒนาและให้คำปรึกษา'],
            ['name' => 'Templates', 'slug' => 'templates', 'description' => 'เทมเพลตและธีม'],
        ];

        foreach ($categories as $category) {
            try {
                $exists = DB::table('categories')
                    ->where('slug', $category['slug'])
                    ->exists();

                if (! $exists) {
                    DB::table('categories')->insert(array_merge($category, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                    $this->command->line("    + Added category: {$category['name']}");
                }
            } catch (\Exception $e) {
                $this->command->error("    ✗ Failed to add category {$category['name']}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Seed default services
     */
    protected function seedDefaultServices(): void
    {
        if (! \Schema::hasTable('services')) {
            return;
        }

        $this->command->info('  → Syncing services...');

        $services = [
            [
                'name' => 'Web Development',
                'name_th' => 'พัฒนาเว็บไซต์',
                'slug' => 'web-development',
                'description' => 'Custom web application development',
                'description_th' => 'พัฒนาเว็บแอปพลิเคชันตามความต้องการ',
                'icon' => 'globe',
                'starting_price' => 30000,
                'price_unit' => 'โปรเจกต์',
                'is_active' => true,
                'is_featured' => true,
                'order' => 1,
            ],
            [
                'name' => 'Mobile App Development',
                'name_th' => 'พัฒนาแอปมือถือ',
                'slug' => 'mobile-app',
                'description' => 'iOS and Android application development',
                'description_th' => 'พัฒนาแอปพลิเคชันสำหรับ iOS และ Android',
                'icon' => 'device-mobile',
                'starting_price' => 50000,
                'price_unit' => 'โปรเจกต์',
                'is_active' => true,
                'is_featured' => true,
                'order' => 2,
            ],
            [
                'name' => 'UI/UX Design',
                'name_th' => 'ออกแบบ UI/UX',
                'slug' => 'ui-ux-design',
                'description' => 'User interface and experience design',
                'description_th' => 'ออกแบบส่วนติดต่อผู้ใช้และประสบการณ์การใช้งาน',
                'icon' => 'color-swatch',
                'starting_price' => 20000,
                'price_unit' => 'โปรเจกต์',
                'is_active' => true,
                'is_featured' => false,
                'order' => 3,
            ],
            [
                'name' => 'IT Consulting',
                'name_th' => 'ที่ปรึกษาไอที',
                'slug' => 'it-consulting',
                'description' => 'Technology consulting and strategy',
                'description_th' => 'ที่ปรึกษาด้านเทคโนโลยีและกลยุทธ์',
                'icon' => 'light-bulb',
                'starting_price' => 15000,
                'price_unit' => 'วัน',
                'is_active' => true,
                'is_featured' => false,
                'order' => 4,
            ],
        ];

        foreach ($services as $service) {
            try {
                $exists = DB::table('services')
                    ->where('slug', $service['slug'])
                    ->exists();

                if (! $exists) {
                    DB::table('services')->insert(array_merge($service, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                    $this->command->line("    + Added service: {$service['name']}");
                }
            } catch (\Exception $e) {
                $this->command->error("    ✗ Failed to add service {$service['name']}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Seed rental packages
     */
    protected function seedRentalPackages(): void
    {
        if (! \Schema::hasTable('rental_packages')) {
            return;
        }

        $this->command->info('  → Syncing rental packages...');

        $packages = [
            [
                'name' => 'Starter',
                'name_th' => 'แพ็กเกจเริ่มต้น',
                'description' => 'Starter package for small businesses',
                'description_th' => 'แพ็กเกจเริ่มต้นสำหรับธุรกิจขนาดเล็ก',
                'price' => 990,
                'duration_type' => 'monthly',
                'duration_value' => 1,
                'features' => json_encode([
                    '5 Users',
                    '10GB Storage',
                    'Email Support',
                    'Basic Analytics',
                ]),
                'is_active' => true,
                'is_featured' => false,
                'is_popular' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Professional',
                'name_th' => 'แพ็กเกจมืออาชีพ',
                'description' => 'Package for growing businesses',
                'description_th' => 'แพ็กเกจสำหรับธุรกิจที่กำลังเติบโต',
                'price' => 2490,
                'duration_type' => 'monthly',
                'duration_value' => 1,
                'features' => json_encode([
                    '25 Users',
                    '50GB Storage',
                    'Priority Support',
                    'Advanced Analytics',
                    'API Access',
                ]),
                'is_active' => true,
                'is_featured' => true,
                'is_popular' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'name_th' => 'แพ็กเกจองค์กร',
                'description' => 'Package for large organizations',
                'description_th' => 'แพ็กเกจสำหรับองค์กรขนาดใหญ่',
                'price' => 9990,
                'duration_type' => 'monthly',
                'duration_value' => 1,
                'features' => json_encode([
                    'Unlimited Users',
                    '500GB Storage',
                    '24/7 Support',
                    'Custom Analytics',
                    'Full API Access',
                    'Dedicated Account Manager',
                ]),
                'is_active' => true,
                'is_featured' => false,
                'is_popular' => false,
                'sort_order' => 3,
            ],
        ];

        foreach ($packages as $package) {
            $exists = DB::table('rental_packages')
                ->where('name', $package['name'])
                ->exists();

            if (! $exists) {
                try {
                    DB::table('rental_packages')->insert(array_merge($package, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                    $this->command->line("    + Added package: {$package['name']}");
                } catch (\Exception $e) {
                    $this->command->error("    ✗ Failed to add package {$package['name']}: {$e->getMessage()}");
                }
            }
        }
    }

    /**
     * Seed quotation categories and options
     */
    protected function seedQuotationData(): void
    {
        if (! \Schema::hasTable('quotation_categories') || ! \Schema::hasTable('quotation_options')) {
            $this->command->warn('  ⚠ Quotation tables do not exist, skipping...');

            return;
        }

        $this->command->info('  → Running QuotationSeeder...');

        try {
            $this->call(QuotationSeeder::class);
        } catch (\Exception $e) {
            $this->command->error("    ✗ Failed to run QuotationSeeder: {$e->getMessage()}");
        }
    }
}
