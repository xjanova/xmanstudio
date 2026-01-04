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

        foreach ($settings as $setting) {
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
                'icon' => 'globe',
                'base_price' => 30000,
                'is_active' => true,
            ],
            [
                'name' => 'Mobile App Development',
                'name_th' => 'พัฒนาแอปมือถือ',
                'slug' => 'mobile-app',
                'description' => 'iOS and Android application development',
                'icon' => 'device-mobile',
                'base_price' => 50000,
                'is_active' => true,
            ],
            [
                'name' => 'UI/UX Design',
                'name_th' => 'ออกแบบ UI/UX',
                'slug' => 'ui-ux-design',
                'description' => 'User interface and experience design',
                'icon' => 'color-swatch',
                'base_price' => 20000,
                'is_active' => true,
            ],
            [
                'name' => 'IT Consulting',
                'name_th' => 'ที่ปรึกษาไอที',
                'slug' => 'it-consulting',
                'description' => 'Technology consulting and strategy',
                'icon' => 'light-bulb',
                'base_price' => 15000,
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
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
                'slug' => 'starter',
                'description' => 'แพ็กเกจเริ่มต้นสำหรับธุรกิจขนาดเล็ก',
                'price' => 990,
                'duration_days' => 30,
                'features' => json_encode([
                    '5 Users',
                    '10GB Storage',
                    'Email Support',
                    'Basic Analytics',
                ]),
                'is_active' => true,
                'is_popular' => false,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'แพ็กเกจสำหรับธุรกิจที่กำลังเติบโต',
                'price' => 2490,
                'duration_days' => 30,
                'features' => json_encode([
                    '25 Users',
                    '50GB Storage',
                    'Priority Support',
                    'Advanced Analytics',
                    'API Access',
                ]),
                'is_active' => true,
                'is_popular' => true,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'แพ็กเกจสำหรับองค์กรขนาดใหญ่',
                'price' => 9990,
                'duration_days' => 30,
                'features' => json_encode([
                    'Unlimited Users',
                    '500GB Storage',
                    '24/7 Support',
                    'Custom Analytics',
                    'Full API Access',
                    'Dedicated Account Manager',
                ]),
                'is_active' => true,
                'is_popular' => false,
            ],
        ];

        foreach ($packages as $package) {
            $exists = DB::table('rental_packages')
                ->where('slug', $package['slug'])
                ->exists();

            if (! $exists) {
                DB::table('rental_packages')->insert(array_merge($package, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
                $this->command->line("    + Added package: {$package['name']}");
            }
        }
    }
}
