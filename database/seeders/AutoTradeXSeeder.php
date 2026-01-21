<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * AutoTradeX Product Seeder
 *
 * Seeds the AutoTradeX cryptocurrency arbitrage trading bot product.
 */
class AutoTradeXSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🤖 Seeding AutoTradeX product...');

        // Ensure Software category exists
        $category = Category::firstOrCreate(
            ['slug' => 'software'],
            [
                'name' => 'Software',
                'description' => 'ซอฟต์แวร์และแอปพลิเคชัน',
            ]
        );

        // Create or update AutoTradeX product
        $product = Product::updateOrCreate(
            ['slug' => 'autotradex'],
            [
                'category_id' => $category->id,
                'name' => 'AutoTradeX',
                'slug' => 'autotradex',
                'short_description' => 'บอทเทรด Crypto Arbitrage อัตโนมัติ รองรับ 6 Exchange ชั้นนำ',
                'description' => <<<'DESC'
# AutoTradeX - Cross-Exchange Cryptocurrency Arbitrage Trading Bot

AutoTradeX คือซอฟต์แวร์บอทเทรด Cryptocurrency แบบ Arbitrage ที่ช่วยให้คุณสามารถทำกำไรจากความแตกต่างของราคาระหว่าง Exchange ต่างๆ ได้อย่างอัตโนมัติ

## ฟีเจอร์หลัก

### 📊 Real-Time Price Monitoring
- ติดตามราคาแบบ Real-time จาก 6 Exchange ชั้นนำ
- แจ้งเตือนเมื่อพบโอกาส Arbitrage
- Dashboard แสดงผลแบบ Glass Morphism สวยงาม

### 💹 Multi-Exchange Support
- **Binance** - Exchange ที่ใหญ่ที่สุดในโลก
- **KuCoin** - รองรับเหรียญหลากหลาย
- **OKX** - Platform ระดับสากล
- **Bybit** - เน้น Derivatives
- **Gate.io** - เหรียญใหม่ๆ มากมาย
- **Bitkub** - Exchange ของไทย

### 📈 Trading Features
- **Simulation Mode** - ทดลองเทรดไม่เสียเงินจริง
- **Live Trading** - เทรดจริงอัตโนมัติ
- **P&L Tracking** - ติดตามกำไร/ขาดทุน
- **Trade History** - บันทึกประวัติการเทรด
- **Risk Management** - จัดการความเสี่ยง

### 🔒 Security
- เชื่อมต่อผ่าน API อย่างปลอดภัย
- ไม่เก็บ Private Key
- รองรับ 2FA
- ระบบ License ป้องกันการใช้งานโดยไม่ได้รับอนุญาต

## ความต้องการระบบ

- Windows 10/11 (64-bit)
- .NET 8.0 Runtime
- หน่วยความจำ 4GB ขึ้นไป
- การเชื่อมต่ออินเทอร์เน็ต

## คำเตือน

⚠️ **การเทรด Cryptocurrency มีความเสี่ยงสูง** - ราคาสามารถผันผวนได้มาก และคุณอาจสูญเสียเงินทุนทั้งหมด โปรดศึกษาและทำความเข้าใจก่อนลงทุน
DESC,
                'features' => json_encode([
                    'Real-Time Price Monitoring จาก 6 Exchange',
                    'Simulation Mode - ทดลองเทรดไม่เสียเงินจริง',
                    'Live Trading - เทรดจริงอัตโนมัติ',
                    'P&L Tracking พร้อม Charts',
                    'Trade History Logging',
                    'Risk Management Parameters',
                    'Glass Morphism UI',
                    'Dark Theme',
                    'ระบบ License ปลอดภัย',
                ]),
                'price' => 19900.00, // Lifetime price
                'sku' => 'ATX-LIFETIME',
                'image' => null,
                'images' => json_encode([]),
                'is_custom' => false,
                'requires_license' => true,
                'stock' => 999, // Digital product
                'low_stock_threshold' => 0,
                'is_active' => true,
            ]
        );

        $this->command->info("  ✓ AutoTradeX product created/updated (ID: {$product->id})");
        $this->command->info('  ✓ AutoTradeX seeding completed!');
    }
}
