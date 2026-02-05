<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\LicenseKey;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * SMS Payment Checker WordPress Plugin Seeder for XManStudio
 *
 * Usage: php artisan db:seed --class=SmsPaymentCheckerSeeder
 */
class SmsPaymentCheckerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ============================================
        // 1. Create Category (if not exists)
        // ============================================
        $category = Category::firstOrCreate(
            ['slug' => 'wordpress-plugins'],
            [
                'name' => 'WordPress Plugins',
                'slug' => 'wordpress-plugins',
                'description' => 'ปลั๊กอิน WordPress สำหรับเพิ่มความสามารถเว็บไซต์',
                'icon' => 'puzzle-piece',
                'order' => 10,
                'is_active' => true,
            ]
        );

        $this->command->info("Category created: {$category->name}");

        // ============================================
        // 2. Create Product: SMS Payment Checker
        // ============================================
        $product = Product::updateOrCreate(
            ['slug' => 'sms-payment-checker'],
            [
                'category_id' => $category->id,
                'name' => 'SMS Payment Checker for WordPress',
                'slug' => 'sms-payment-checker',
                'description' => $this->getFullDescription(),
                'short_description' => 'ระบบตรวจสอบการชำระเงินผ่าน SMS อัตโนมัติสำหรับ WooCommerce รองรับธนาคารไทย 15+ แห่ง เข้ารหัส AES-256-GCM จับคู่ยอดเงินอัตโนมัติ',
                'features' => $this->getFeatures(),
                'price' => 990.00,
                'image' => null,
                'images' => [],
                'sku' => 'SPC-WP-001',
                'is_custom' => false,
                'requires_license' => true,
                'stock' => 9999,
                'low_stock_threshold' => 0,
                'is_active' => true,
            ]
        );

        $this->command->info("Product created: {$product->name} (ID: {$product->id})");

        // ============================================
        // 3. Pricing Structure
        // ============================================
        $pricingInfo = [
            'monthly' => [
                'price' => 990.00,
                'currency' => 'THB',
                'duration_days' => 30,
                'license_type' => LicenseKey::TYPE_MONTHLY,
            ],
            'yearly' => [
                'price' => 9900.00,
                'currency' => 'THB',
                'duration_days' => 365,
                'license_type' => LicenseKey::TYPE_YEARLY,
                'savings' => '17%',
            ],
            'lifetime' => [
                'price' => 29900.00,
                'currency' => 'THB',
                'duration_days' => null,
                'license_type' => LicenseKey::TYPE_LIFETIME,
                'savings' => 'Best Value',
            ],
        ];

        $this->command->info('Pricing structure:');
        foreach ($pricingInfo as $type => $info) {
            $this->command->info("  - {$type}: {$info['price']} {$info['currency']}");
        }

        // ============================================
        // 4. Create Demo License Keys (for testing)
        // ============================================
        $demoKeys = [
            'DEMO-SPC-0001-TEST',
            'DEMO-SPC-0002-TEST',
        ];

        foreach ($demoKeys as $key) {
            LicenseKey::firstOrCreate(
                ['license_key' => $key],
                [
                    'product_id' => $product->id,
                    'order_id' => null,
                    'license_key' => $key,
                    'status' => LicenseKey::STATUS_ACTIVE,
                    'license_type' => LicenseKey::TYPE_DEMO,
                    'activated_at' => null,
                    'expires_at' => now()->addDays(7),
                    'max_activations' => 1,
                    'activations' => 0,
                    'metadata' => json_encode([
                        'is_test_key' => true,
                        'created_by' => 'seeder',
                    ]),
                ]
            );
        }

        $this->command->info('Demo license keys created: ' . count($demoKeys));

        // ============================================
        // 5. Product Configuration
        // ============================================
        $this->command->newLine();
        $this->command->info('=== SMS Payment Checker Configuration ===');
        $this->command->info('Product Slug: sms-payment-checker');
        $this->command->info('API Base URL: https://xmanstudio.com/api/v1');
        $this->command->newLine();
        $this->command->info('WordPress Plugin API Endpoints:');
        $this->command->info('  POST /product/sms-payment-checker/activate    - Activate license');
        $this->command->info('  POST /product/sms-payment-checker/validate    - Validate license');
        $this->command->info('  POST /product/sms-payment-checker/deactivate  - Deactivate license');
        $this->command->newLine();
        $this->command->info('License Types: monthly (30d), yearly (365d), lifetime (forever)');
        $this->command->info('Max Activations: 1 site per license');
    }

    /**
     * Get full product description
     */
    private function getFullDescription(): string
    {
        return <<<'HTML'
<h2>SMS Payment Checker - ระบบตรวจสอบการชำระเงินอัตโนมัติ</h2>

<p>ปลั๊กอิน WordPress/WooCommerce สำหรับตรวจสอบการชำระเงินผ่าน SMS จากธนาคารไทยอัตโนมัติ ทำงานร่วมกับแอป SmsChecker บน Android เพื่อส่งต่อ SMS แจ้งเงินเข้าไปยังเว็บไซต์ แล้วจับคู่กับคำสั่งซื้อโดยอัตโนมัติ</p>

<h3>วิธีการทำงาน</h3>
<ol>
    <li>ลูกค้าสั่งซื้อสินค้าบน WooCommerce แล้วโอนเงิน</li>
    <li>ธนาคารส่ง SMS แจ้งเงินเข้ามายังมือถือ</li>
    <li>แอป SmsChecker อ่าน SMS แล้วส่งข้อมูลไปยังเว็บไซต์</li>
    <li>ปลั๊กอินจับคู่ยอดเงินกับคำสั่งซื้ออัตโนมัติ</li>
    <li>อัปเดตสถานะคำสั่งซื้อเป็น "ชำระเงินแล้ว" ทันที</li>
</ol>

<h3>ธนาคารที่รองรับ (15+ แห่ง)</h3>
<ul>
    <li>กสิกรไทย (KBANK)</li>
    <li>ไทยพาณิชย์ (SCB)</li>
    <li>กรุงไทย (KTB)</li>
    <li>กรุงเทพ (BBL)</li>
    <li>ออมสิน (GSB)</li>
    <li>กรุงศรีอยุธยา (BAY)</li>
    <li>ทีเอ็มบีธนชาต (TTB)</li>
    <li>พร้อมเพย์ (PromptPay)</li>
    <li>CIMB Thai, KKP, LH Bank, TISCO, UOB, ICBC Thai, ธ.ก.ส.</li>
</ul>

<h3>ความปลอดภัย</h3>
<ul>
    <li><strong>AES-256-GCM Encryption</strong> - เข้ารหัสข้อมูลทุกการสื่อสาร</li>
    <li><strong>HMAC Authentication</strong> - ยืนยันตัวตนทุก Request</li>
    <li><strong>Nonce Protection</strong> - ป้องกัน Replay Attack</li>
    <li><strong>Rate Limiting</strong> - จำกัดจำนวน Request ป้องกัน DDoS</li>
</ul>

<h3>ความต้องการระบบ</h3>
<ul>
    <li>WordPress 5.8+</li>
    <li>WooCommerce 6.0+</li>
    <li>PHP 8.0+</li>
    <li>Android มือถือ + แอป SmsChecker (ฟรี)</li>
</ul>
HTML;
    }

    /**
     * Get product features array
     */
    private function getFeatures(): array
    {
        return [
            [
                'icon' => 'device-phone-mobile',
                'title' => 'SMS อัตโนมัติ',
                'description' => 'รับ SMS แจ้งเงินเข้าจากธนาคารผ่านแอป Android อัตโนมัติ',
            ],
            [
                'icon' => 'building-library',
                'title' => 'รองรับ 15+ ธนาคาร',
                'description' => 'KBANK, SCB, KTB, BBL, GSB, BAY, TTB, PromptPay และอื่นๆ',
            ],
            [
                'icon' => 'link',
                'title' => 'จับคู่อัตโนมัติ',
                'description' => 'จับคู่ยอดโอนกับคำสั่งซื้อ WooCommerce อัตโนมัติ',
            ],
            [
                'icon' => 'lock-closed',
                'title' => 'AES-256-GCM',
                'description' => 'เข้ารหัสข้อมูลระดับธนาคาร พร้อม HMAC Authentication',
            ],
            [
                'icon' => 'shield-check',
                'title' => 'ป้องกัน Replay Attack',
                'description' => 'ระบบ Nonce และ Rate Limiting ป้องกันการโจมตี',
            ],
            [
                'icon' => 'shopping-cart',
                'title' => 'WooCommerce Integration',
                'description' => 'รองรับ HPOS และ Payment Gateway แบบ Native',
            ],
            [
                'icon' => 'clock',
                'title' => 'Real-time Sync',
                'description' => 'อัปเดตสถานะคำสั่งซื้อทันทีเมื่อตรวจพบยอดโอน',
            ],
            [
                'icon' => 'cog-6-tooth',
                'title' => 'ตั้งค่าง่าย',
                'description' => 'ติดตั้งปลั๊กอิน ใส่ License Key แล้วเริ่มใช้งานได้ทันที',
            ],
        ];
    }
}
