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
        $this->seedQuotations();

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
    protected function seedQuotations(): void
    {
        if (! \Schema::hasTable('quotation_categories') || ! \Schema::hasTable('quotation_options')) {
            $this->command->warn('  ⚠ Quotation tables do not exist, skipping...');

            return;
        }

        $this->command->info('  → Syncing quotation categories and options...');

        $categories = [
            [
                'key' => 'music_ai',
                'name' => 'AI Music Generation',
                'name_th' => 'สร้างเพลงด้วย AI',
                'icon' => '🎵',
                'description' => 'Professional AI-powered music creation services',
                'description_th' => 'บริการสร้างเพลงด้วย AI อย่างมืออาชีพ',
                'order' => 1,
                'is_active' => true,
                'options' => [
                    ['key' => 'music_basic', 'name' => 'AI Background Music', 'name_th' => 'เพลงประกอบ AI (Basic)', 'price' => 50000, 'order' => 1],
                    ['key' => 'music_custom', 'name' => 'Custom AI Music Track', 'name_th' => 'สร้างเพลง AI แบบกำหนดเอง', 'price' => 80000, 'order' => 2],
                    ['key' => 'music_album', 'name' => 'AI Music Album (10 tracks)', 'name_th' => 'อัลบั้มเพลง AI (10 เพลง)', 'price' => 500000, 'order' => 3],
                    ['key' => 'music_voice', 'name' => 'AI Voice Synthesis', 'name_th' => 'สังเคราะห์เสียงร้อง AI', 'price' => 100000, 'order' => 4],
                    ['key' => 'music_cover', 'name' => 'AI Music Cover/Remix', 'name_th' => 'ปรับแต่งเพลงด้วย AI', 'price' => 60000, 'order' => 5],
                    ['key' => 'music_genre', 'name' => 'Multi-Genre AI Music', 'name_th' => 'เพลง AI หลายแนว', 'price' => 90000, 'order' => 6],
                    ['key' => 'music_commercial', 'name' => 'Commercial Music License', 'name_th' => 'ลิขสิทธิ์เพลงเชิงพาณิชย์', 'price' => 150000, 'order' => 7],
                    ['key' => 'music_compose', 'name' => 'AI Music Composition System', 'name_th' => 'ระบบแต่งเพลง AI', 'price' => 300000, 'order' => 8],
                    ['key' => 'music_mastering', 'name' => 'AI Audio Mastering', 'name_th' => 'มาสเตอร์เสียงด้วย AI', 'price' => 40000, 'order' => 9],
                    ['key' => 'music_stem', 'name' => 'AI Stem Separation', 'name_th' => 'แยกแทร็กเพลงด้วย AI', 'price' => 35000, 'order' => 10],
                ],
            ],
            [
                'key' => 'ai_image',
                'name' => 'AI Image Generation',
                'name_th' => 'สร้างภาพด้วย AI',
                'icon' => '🎨',
                'description' => 'Advanced AI image generation and editing services',
                'description_th' => 'บริการสร้างและแก้ไขภาพด้วย AI',
                'order' => 2,
                'is_active' => true,
                'options' => [
                    ['key' => 'gen_image', 'name' => 'AI Image Generation', 'name_th' => 'สร้างภาพด้วย AI', 'price' => 80000, 'order' => 1],
                    ['key' => 'gen_video', 'name' => 'AI Video Generation', 'name_th' => 'สร้างวิดีโอด้วย AI', 'price' => 150000, 'order' => 2],
                    ['key' => 'gen_text', 'name' => 'AI Content Writing', 'name_th' => 'เขียนเนื้อหาด้วย AI', 'price' => 60000, 'order' => 3],
                    ['key' => 'gen_avatar', 'name' => 'AI Avatar/Character', 'name_th' => 'สร้าง Avatar ด้วย AI', 'price' => 100000, 'order' => 4],
                ],
            ],
            [
                'key' => 'ai_chatbot',
                'name' => 'AI Chatbot',
                'name_th' => 'Chatbot อัจฉริยะ',
                'icon' => '💬',
                'description' => 'Intelligent chatbot solutions powered by AI',
                'description_th' => 'โซลูชัน Chatbot อัจฉริยะด้วย AI',
                'order' => 3,
                'is_active' => true,
                'options' => [
                    ['key' => 'chat_basic', 'name' => 'Basic Chatbot', 'name_th' => 'Chatbot พื้นฐาน', 'price' => 50000, 'order' => 1],
                    ['key' => 'chat_gpt', 'name' => 'GPT-powered Chatbot', 'name_th' => 'Chatbot ด้วย GPT', 'price' => 100000, 'order' => 2],
                    ['key' => 'chat_voice', 'name' => 'Voice Assistant', 'name_th' => 'ผู้ช่วยเสียง AI', 'price' => 120000, 'order' => 3],
                    ['key' => 'chat_multi', 'name' => 'Multi-channel Bot', 'name_th' => 'Bot หลายช่องทาง', 'price' => 150000, 'order' => 4],
                    ['key' => 'chat_custom', 'name' => 'Custom AI Agent', 'name_th' => 'AI Agent แบบกำหนดเอง', 'price' => 200000, 'order' => 5],
                ],
            ],
            [
                'key' => 'blockchain',
                'name' => 'Blockchain Development',
                'name_th' => 'พัฒนา Blockchain',
                'icon' => '🔗',
                'description' => 'Comprehensive blockchain and smart contract development',
                'description_th' => 'บริการพัฒนา Blockchain และ Smart Contract',
                'order' => 4,
                'is_active' => true,
                'options' => [
                    ['key' => 'sc_erc20', 'name' => 'ERC-20 Token Contract', 'name_th' => 'Smart Contract ERC-20 Token', 'price' => 50000, 'order' => 1],
                    ['key' => 'sc_erc721', 'name' => 'ERC-721 NFT Contract', 'name_th' => 'Smart Contract NFT ERC-721', 'price' => 80000, 'order' => 2],
                    ['key' => 'sc_erc1155', 'name' => 'ERC-1155 Multi-Token', 'name_th' => 'Smart Contract Multi-Token ERC-1155', 'price' => 100000, 'order' => 3],
                    ['key' => 'sc_staking', 'name' => 'Staking Contract', 'name_th' => 'Smart Contract Staking', 'price' => 120000, 'order' => 4],
                    ['key' => 'nft_marketplace', 'name' => 'NFT Marketplace', 'name_th' => 'ตลาด NFT Marketplace', 'price' => 350000, 'order' => 5],
                    ['key' => 'defi_dex', 'name' => 'DEX (Decentralized Exchange)', 'name_th' => 'DEX ระบบแลกเปลี่ยนกระจายศูนย์', 'price' => 500000, 'order' => 6],
                ],
            ],
            [
                'key' => 'web_development',
                'name' => 'Web Development',
                'name_th' => 'พัฒนาเว็บไซต์',
                'icon' => '🌐',
                'description' => 'Professional web development services',
                'description_th' => 'บริการพัฒนาเว็บไซต์มืออาชีพ',
                'order' => 5,
                'is_active' => true,
                'options' => [
                    ['key' => 'web_landing', 'name' => 'Landing Page', 'name_th' => 'Landing Page (1-5 หน้า)', 'price' => 15000, 'order' => 1],
                    ['key' => 'web_corporate', 'name' => 'Corporate Website', 'name_th' => 'เว็บไซต์องค์กร', 'price' => 45000, 'order' => 2],
                    ['key' => 'web_ecommerce', 'name' => 'E-commerce Website', 'name_th' => 'เว็บไซต์อีคอมเมิร์ซ', 'price' => 80000, 'order' => 3],
                    ['key' => 'web_custom', 'name' => 'Custom Web Application', 'name_th' => 'เว็บแอพพลิเคชั่นแบบกำหนดเอง', 'price' => 100000, 'order' => 4],
                ],
            ],
            [
                'key' => 'iot',
                'name' => 'IoT Solutions',
                'name_th' => 'โซลูชัน IoT',
                'icon' => '⚡',
                'description' => 'Internet of Things solutions for smart devices',
                'description_th' => 'โซลูชัน IoT สำหรับอุปกรณ์อัจฉริยะ',
                'order' => 6,
                'is_active' => true,
                'options' => [
                    ['key' => 'home_automation', 'name' => 'Home Automation System', 'name_th' => 'ระบบอัตโนมัติในบ้าน', 'price' => 150000, 'order' => 1],
                    ['key' => 'farm_monitoring', 'name' => 'Smart Farm Monitoring', 'name_th' => 'ระบบติดตามฟาร์มอัจฉริยะ', 'price' => 180000, 'order' => 2],
                    ['key' => 'iiot_monitoring', 'name' => 'Industrial Monitoring', 'name_th' => 'ระบบติดตามโรงงาน', 'price' => 350000, 'order' => 3],
                    ['key' => 'platform_dashboard', 'name' => 'IoT Dashboard', 'name_th' => 'Dashboard แสดงผล IoT', 'price' => 80000, 'order' => 4],
                ],
            ],
        ];

        $addedCategories = 0;
        $addedOptions = 0;

        foreach ($categories as $categoryData) {
            $options = $categoryData['options'];
            unset($categoryData['options']);

            try {
                $exists = DB::table('quotation_categories')
                    ->where('key', $categoryData['key'])
                    ->exists();

                if (! $exists) {
                    $categoryId = DB::table('quotation_categories')->insertGetId(array_merge($categoryData, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                    $addedCategories++;
                    $this->command->line("    + Added category: {$categoryData['name']}");

                    // Add options for new category
                    foreach ($options as $option) {
                        DB::table('quotation_options')->insert(array_merge($option, [
                            'quotation_category_id' => $categoryId,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]));
                        $addedOptions++;
                    }
                } else {
                    // Category exists, check for missing options
                    $categoryId = DB::table('quotation_categories')
                        ->where('key', $categoryData['key'])
                        ->value('id');

                    foreach ($options as $option) {
                        $optionExists = DB::table('quotation_options')
                            ->where('key', $option['key'])
                            ->exists();

                        if (! $optionExists) {
                            DB::table('quotation_options')->insert(array_merge($option, [
                                'quotation_category_id' => $categoryId,
                                'is_active' => true,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]));
                            $addedOptions++;
                            $this->command->line("    + Added option: {$option['name']}");
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->command->error("    ✗ Failed to add category {$categoryData['name']}: {$e->getMessage()}");
            }
        }

        if ($addedCategories > 0 || $addedOptions > 0) {
            $this->command->info("    ✓ Added {$addedCategories} categories and {$addedOptions} options");
        } else {
            $this->command->info('    ✓ Quotation data already up to date');
        }
    }
}
