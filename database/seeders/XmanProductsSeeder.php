<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class XmanProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create categories if they don't exist
        $tradingCategory = Category::firstOrCreate(
            ['slug' => 'trading-software'],
            [
                'name' => 'Trading Software',
                'description' => 'ซอฟต์แวร์สำหรับการเทรดและวิเคราะห์ตลาด',
                'icon' => 'chart-line',
                'order' => 1,
                'is_active' => true,
            ]
        );

        $networkCategory = Category::firstOrCreate(
            ['slug' => 'network-security'],
            [
                'name' => 'Network & Security',
                'description' => 'ซอฟต์แวร์ด้านเครือข่ายและความปลอดภัย',
                'icon' => 'shield',
                'order' => 2,
                'is_active' => true,
            ]
        );

        $aiCategory = Category::firstOrCreate(
            ['slug' => 'ai-automation'],
            [
                'name' => 'AI & Automation',
                'description' => 'ซอฟต์แวร์ AI และระบบอัตโนมัติ',
                'icon' => 'robot',
                'order' => 3,
                'is_active' => true,
            ]
        );

        $mobileCategory = Category::firstOrCreate(
            ['slug' => 'mobile-tools'],
            [
                'name' => 'Mobile Tools',
                'description' => 'เครื่องมือจัดการอุปกรณ์มือถือ',
                'icon' => 'mobile',
                'order' => 4,
                'is_active' => true,
            ]
        );

        $ecommerceCategory = Category::firstOrCreate(
            ['slug' => 'e-commerce'],
            [
                'name' => 'E-Commerce',
                'description' => 'แพลตฟอร์มและเครื่องมือสำหรับอีคอมเมิร์ซ',
                'icon' => 'shopping-cart',
                'order' => 5,
                'is_active' => true,
            ]
        );

        $systemCategory = Category::firstOrCreate(
            ['slug' => 'system-utilities'],
            [
                'name' => 'System Utilities',
                'description' => 'เครื่องมือจัดการระบบและยูทิลิตี้',
                'icon' => 'cog',
                'order' => 6,
                'is_active' => true,
            ]
        );

        $cloudCategory = Category::firstOrCreate(
            ['slug' => 'cloud-computing'],
            [
                'name' => 'Cloud & Computing',
                'description' => 'บริการคลาวด์และการประมวลผล',
                'icon' => 'cloud',
                'order' => 7,
                'is_active' => true,
            ]
        );

        // Products data
        $products = [
            // 1. AutoTradeX
            [
                'category_id' => $tradingCategory->id,
                'name' => 'AutoTrade-X',
                'slug' => 'autotrade-x',
                'sku' => 'ATX-001',
                'short_description' => 'แพลตฟอร์มเทรด Cryptocurrency แบบ Arbitrage อัตโนมัติ รองรับหลาย Exchange พร้อม UI สวยงามแบบ Glass Morphism',
                'description' => '<div class="prose max-w-none">
<h2>AutoTrade-X - Cryptocurrency Arbitrage Platform</h2>
<p>แพลตฟอร์มเทรด Cryptocurrency ระดับมืออาชีพที่ออกแบบมาเพื่อค้นหาและใช้ประโยชน์จากโอกาส Arbitrage ระหว่าง Exchange ต่างๆ โดยอัตโนมัติ พัฒนาด้วย .NET 8 และ WPF พร้อม UI ที่สวยงามแบบ Glass Morphism</p>

<h3>ทำไมต้องเลือก AutoTrade-X?</h3>
<ul>
<li><strong>Multi-Exchange Support</strong> - รองรับ Binance, KuCoin, OKX, Bybit, Gate.io, Bitkub</li>
<li><strong>Real-time Monitoring</strong> - ติดตามราคาและโอกาส Arbitrage แบบเรียลไทม์</li>
<li><strong>Smart Execution</strong> - ระบบประมวลผลและเทรดอัตโนมัติ</li>
<li><strong>Risk Management</strong> - ตั้งค่าพารามิเตอร์ความเสี่ยงได้</li>
</ul>

<h3>คุณสมบัติเด่น</h3>
<ul>
<li>Dark Theme พร้อม Glass Morphism Effects</li>
<li>Trade History และ P&L Charts</li>
<li>Animated Splash Screen แบบ Hyperdrive Effect</li>
<li>Simulation Mode สำหรับทดสอบกลยุทธ์</li>
</ul>

<h3>System Requirements</h3>
<ul>
<li>Windows 10/11 (64-bit)</li>
<li>.NET 8.0 Runtime</li>
<li>RAM: 8GB+ แนะนำ</li>
<li>การเชื่อมต่ออินเทอร์เน็ตที่เสถียร</li>
</ul>
</div>',
                'features' => [
                    'รองรับ 6 Exchange ชั้นนำ (Binance, KuCoin, OKX, Bybit, Gate.io, Bitkub)',
                    'ค้นหาโอกาส Arbitrage แบบ Real-time',
                    'UI สวยงามแบบ Glass Morphism พร้อม Dark Theme',
                    'Trade History และ P&L Tracking พร้อม Charts',
                    'Simulation Mode สำหรับทดสอบกลยุทธ์',
                    'ตั้งค่า Risk Management Parameters ได้',
                    'Animated Splash Screen แบบ Hyperdrive',
                    'พัฒนาด้วย .NET 8.0 และ WPF',
                ],
                'price' => 4990.00,
                'image' => 'https://raw.githubusercontent.com/xjanova/autotradex/main/src/AutoTradeX.UI/Assets/logo2.png',
                'images' => [
                    'https://raw.githubusercontent.com/xjanova/autotradex/main/src/AutoTradeX.UI/Assets/logo2.png',
                ],
                'requires_license' => true,
                'stock' => 999,
                'is_active' => true,
            ],

            // 2. SpiderX
            [
                'category_id' => $networkCategory->id,
                'name' => 'SpiderX',
                'slug' => 'spiderx',
                'sku' => 'SPX-001',
                'short_description' => 'ระบบเครือข่าย P2P Mesh Network แบบกระจายศูนย์ สำหรับการสื่อสารที่ปลอดภัยและเป็นส่วนตัว',
                'description' => '<div class="prose max-w-none">
<h2>SpiderX - Decentralized P2P Mesh Network</h2>
<p>แอปพลิเคชันเครือข่าย Mesh แบบ Peer-to-Peer ที่ช่วยให้คุณสื่อสารอย่างปลอดภัยและเป็นส่วนตัว โดยไม่ต้องพึ่งพาเซิร์ฟเวอร์ส่วนกลาง สร้างบน Cryptography สมัยใหม่</p>

<h3>ทำไมต้องเลือก SpiderX?</h3>
<ul>
<li><strong>Zero Trust Architecture</strong> - ไม่ต้องลงทะเบียน ไม่มีเซิร์ฟเวอร์กลาง</li>
<li><strong>End-to-End Encryption</strong> - เข้ารหัสด้วย X25519 + AES-256-GCM</li>
<li><strong>Cross-Platform</strong> - รองรับ Windows, macOS, Linux, iOS, Android</li>
<li><strong>Perfect Forward Secrecy</strong> - ความปลอดภัยสูงสุดสำหรับทุกข้อความ</li>
</ul>

<h3>คุณสมบัติเด่น</h3>
<ul>
<li>Cryptographic Identity (SpiderId format: spx1...)</li>
<li>P2P File Sharing แบบ BitTorrent-style</li>
<li>Virtual LAN สำหรับเล่นเกม</li>
<li>LAN Discovery และ NAT Traversal</li>
<li>QR Code Contact Sharing</li>
</ul>

<h3>Technology Stack</h3>
<ul>
<li>.NET 9.0 + MAUI</li>
<li>Ed25519 Digital Signatures</li>
<li>Kademlia DHT Implementation</li>
<li>UDP/TCP Transport Layers</li>
</ul>
</div>',
                'features' => [
                    'เครือข่าย Decentralized ไม่มี Central Server',
                    'End-to-End Encryption (X25519 + AES-256-GCM)',
                    'Cryptographic Identity (SpiderId)',
                    'Zero-Knowledge Design ไม่ต้องลงทะเบียน',
                    'รองรับ Cross-Platform (Win/Mac/Linux/iOS/Android)',
                    'Encrypted Messaging พร้อม Perfect Forward Secrecy',
                    'P2P File Sharing แบบ BitTorrent-style',
                    'Virtual LAN สำหรับ Gaming',
                    'NAT Traversal และ LAN Discovery',
                ],
                'price' => 2990.00,
                'image' => 'https://raw.githubusercontent.com/xjanova/SpiderX/main/docs/logo.png',
                'images' => [
                    'https://raw.githubusercontent.com/xjanova/SpiderX/main/docs/logo.png',
                ],
                'requires_license' => true,
                'stock' => 999,
                'is_active' => true,
            ],

            // 3. XcluadeAgent
            [
                'category_id' => $aiCategory->id,
                'name' => 'XcluadeAgent',
                'slug' => 'xcluade-agent',
                'sku' => 'XCA-001',
                'short_description' => 'GitHub Sync Service พร้อม AI-Powered Auto-Fix จัดการหลายโปรเจคผ่าน Dashboard เดียว',
                'description' => '<div class="prose max-w-none">
<h2>XcluadeAgent - AI-Powered GitHub Sync Service</h2>
<p>ระบบ Sync GitHub Releases อัตโนมัติพร้อมความสามารถ AI ในการวิเคราะห์และแก้ไขปัญหา จัดการหลายโปรเจคผ่าน Dashboard ส่วนกลาง</p>

<h3>ทำไมต้องเลือก XcluadeAgent?</h3>
<ul>
<li><strong>Automatic Sync</strong> - ดาวน์โหลดและ Deploy จาก GitHub Releases อัตโนมัติ</li>
<li><strong>AI Assistant</strong> - วิเคราะห์ Error และแนะนำวิธีแก้ไขอัจฉริยะ</li>
<li><strong>Multi-Project</strong> - จัดการหลายโปรเจคจาก Dashboard เดียว</li>
<li><strong>Auto-Rollback</strong> - ย้อนกลับอัตโนมัติเมื่อตรวจพบ Error</li>
</ul>

<h3>คุณสมบัติเด่น</h3>
<ul>
<li>Backup System อัตโนมัติก่อน Sync</li>
<li>แจ้งเตือนหลายช่องทาง (Discord, Telegram, LINE, Email, Slack)</li>
<li>Web Dashboard Responsive พร้อม Dark Mode</li>
<li>CLI Tool (syncctl) สำหรับควบคุมผ่าน Command Line</li>
<li>JWT Authentication + Role-Based Access + 2FA</li>
<li>System Monitoring (Health, Disk, SSL)</li>
</ul>

<h3>AI Providers รองรับ</h3>
<ul>
<li>Ollama (Local AI)</li>
<li>Claude (Anthropic)</li>
<li>OpenAI (GPT)</li>
</ul>
</div>',
                'features' => [
                    'GitHub Release Sync อัตโนมัติ',
                    'Multi-Project Dashboard จัดการไม่จำกัดโปรเจค',
                    'AI Assistant วิเคราะห์ Error (Ollama/Claude/OpenAI)',
                    'Auto-Rollback เมื่อตรวจพบปัญหา',
                    'Backup อัตโนมัติก่อนทุก Sync',
                    'แจ้งเตือน Discord, Telegram, LINE, Email, Slack',
                    'Web Dashboard พร้อม Dark Mode',
                    'CLI Tool (syncctl)',
                    'JWT + Role-Based Access + 2FA',
                    'System Monitoring (Health, Disk, SSL)',
                ],
                'price' => 3490.00,
                'image' => null,
                'images' => [],
                'requires_license' => true,
                'stock' => 999,
                'is_active' => true,
            ],

            // 4. PhoneX Manager
            [
                'category_id' => $mobileCategory->id,
                'name' => 'PhoneX Manager',
                'slug' => 'phonex-manager',
                'sku' => 'PXM-001',
                'short_description' => 'เครื่องมือจัดการอุปกรณ์ Android ครบวงจร รองรับ Flash ROM, Backup, Recovery และ Engineering Tools',
                'description' => '<div class="prose max-w-none">
<h2>PhoneX Manager - Universal Android Device Management</h2>
<p>เครื่องมือจัดการอุปกรณ์ Android บน Windows แบบครบวงจร รองรับการ Flash ROM, Backup, Recovery และเครื่องมือ Engineering สำหรับหลายแบรนด์</p>

<h3>ทำไมต้องเลือก PhoneX Manager?</h3>
<ul>
<li><strong>Universal Support</strong> - รองรับ Qualcomm, MediaTek, Samsung, Xiaomi</li>
<li><strong>Complete Toolkit</strong> - ครบทุกเครื่องมือในแอปเดียว</li>
<li><strong>Auto Detection</strong> - ตรวจจับอุปกรณ์อัตโนมัติผ่าน ADB/Fastboot</li>
<li><strong>Professional Grade</strong> - เครื่องมือระดับมืออาชีพ</li>
</ul>

<h3>Device Management</h3>
<ul>
<li>ตรวจจับอุปกรณ์อัตโนมัติ</li>
<li>รองรับหลายแบรนด์และ Chipset</li>
</ul>

<h3>ROM Operations</h3>
<ul>
<li>Full Backup ทั้งระบบ</li>
<li>Flash ROM หลายโหมด</li>
<li>Boot Image Editor</li>
<li>Partition Management</li>
</ul>

<h3>Engineering Tools</h3>
<ul>
<li>FRP Bypass</li>
<li>Screen Lock Bypass</li>
<li>IMEI Operations</li>
<li>Network Unlock</li>
<li>Bloatware Removal</li>
<li>Recovery Management</li>
</ul>

<h3>Utilities</h3>
<ul>
<li>Integrated Hex Editor</li>
<li>ADB Terminal</li>
<li>Screen Mirroring (Scrcpy)</li>
<li>Logcat Viewer</li>
<li>Checksum Calculator</li>
</ul>
</div>',
                'features' => [
                    'Auto-Detection ผ่าน ADB/Fastboot',
                    'รองรับ Qualcomm, MediaTek, Samsung, Xiaomi',
                    'Full Backup และ Multi-Mode Flashing',
                    'Boot Image Editor และ Partition Management',
                    'FRP Bypass และ Screen Lock Bypass',
                    'IMEI Operations และ Network Unlock',
                    'Bloatware Removal',
                    'Integrated Hex Editor',
                    'ADB Terminal และ Logcat Viewer',
                    'Screen Mirroring ผ่าน Scrcpy',
                    'Auto Driver Installation',
                ],
                'price' => 1990.00,
                'image' => null,
                'images' => [],
                'requires_license' => true,
                'stock' => 999,
                'is_active' => true,
            ],

            // 5. LiveXShopPro
            [
                'category_id' => $ecommerceCategory->id,
                'name' => 'LiveXShopPro',
                'slug' => 'livexshop-pro',
                'sku' => 'LXS-001',
                'short_description' => 'แพลตฟอร์ม Live Shopping ครบวงจร สำหรับการขายสินค้าแบบ Real-time พร้อมระบบจัดการสต็อกและคำสั่งซื้อ',
                'description' => '<div class="prose max-w-none">
<h2>LiveXShopPro - Live Shopping Platform</h2>
<p>แพลตฟอร์ม Live Shopping ครบวงจรสำหรับการขายสินค้าแบบ Real-time รวม Live Streaming, จัดการสินค้า และระบบสั่งซื้อไว้ในที่เดียว</p>

<h3>ทำไมต้องเลือก LiveXShopPro?</h3>
<ul>
<li><strong>All-in-One</strong> - รวมทุกอย่างสำหรับ Live Shopping</li>
<li><strong>Real-time</strong> - ขายสดและรับออเดอร์ทันที</li>
<li><strong>Inventory Sync</strong> - สต็อกอัพเดทอัตโนมัติ</li>
<li><strong>Easy Setup</strong> - ตั้งค่าง่าย ใช้งานได้ทันที</li>
</ul>

<h3>คุณสมบัติเด่น</h3>
<ul>
<li>Live Streaming/Broadcasting</li>
<li>Product Management System</li>
<li>Real-time Ordering</li>
<li>Inventory Management</li>
<li>Customer Management</li>
<li>Sales Analytics</li>
<li>Multi-Channel Support</li>
</ul>

<h3>เหมาะสำหรับ</h3>
<ul>
<li>ร้านค้าออนไลน์ที่ต้องการขายผ่าน Live</li>
<li>Influencers และ Content Creators</li>
<li>ธุรกิจ SME ที่ต้องการเข้าถึงลูกค้าแบบ Real-time</li>
</ul>
</div>',
                'features' => [
                    'Live Streaming/Broadcasting ในตัว',
                    'ระบบจัดการสินค้าครบวงจร',
                    'Real-time Ordering รับออเดอร์ทันที',
                    'Inventory Management อัพเดทสต็อกอัตโนมัติ',
                    'Customer Management',
                    'Sales Analytics และรายงาน',
                    'Multi-Channel Support',
                    'UI Components Library',
                    'TypeScript-based',
                ],
                'price' => 5990.00,
                'image' => null,
                'images' => [],
                'requires_license' => true,
                'stock' => 999,
                'is_active' => true,
            ],

            // 6. WinXTools
            [
                'category_id' => $systemCategory->id,
                'name' => 'WinXTools',
                'slug' => 'winx-tools',
                'sku' => 'WXT-001',
                'short_description' => 'เครื่องมือจัดการเครือข่ายและระบบ Windows ระดับ Professional ตรวจสอบ Bandwidth, ควบคุมแอป, ทำความสะอาดระบบ',
                'description' => '<div class="prose max-w-none">
<h2>WinXTools - Advanced Windows Network & System Management</h2>
<p>เครื่องมือระดับ Professional สำหรับ Windows ที่รวมการตรวจสอบเครือข่าย, ควบคุม Bandwidth, จัดการแอปพลิเคชัน และทำความสะอาดระบบไว้ในแอปเดียว</p>

<h3>Network Monitoring</h3>
<ul>
<li>Real-time Bandwidth Tracking แยกตามโปรเซส</li>
<li>TCP/UDP Connection Monitoring</li>
<li>Live Performance Charts</li>
<li>Historical Data Logging</li>
</ul>

<h3>Bandwidth Control</h3>
<ul>
<li><strong>Basic Mode</strong> - ใช้ Windows Firewall API</li>
<li><strong>Advanced Mode</strong> - Kernel-level Packet Filtering ผ่าน WFP</li>
<li>จำกัด Bandwidth แยกตามแอป</li>
<li>Priority Management</li>
</ul>

<h3>System Utilities</h3>
<ul>
<li>Deep Application Uninstaller พร้อมล้าง Registry</li>
<li>System Cleaner (Temp, Cache, Logs)</li>
<li>RAM Optimizer</li>
<li>Process Management</li>
</ul>

<h3>Customization</h3>
<ul>
<li>รองรับ 2 ภาษา (English/Thai)</li>
<li>Theme Options</li>
<li>System Tray Integration</li>
<li>Auto-start</li>
</ul>
</div>',
                'features' => [
                    'Real-time Bandwidth Tracking แยกตามโปรเซส',
                    'TCP/UDP Connection Monitoring',
                    'Live Performance Charts',
                    'Bandwidth Control ระดับ Kernel (WFP)',
                    'Per-Application Bandwidth Limits',
                    'Deep Application Uninstaller + Registry Cleanup',
                    'System Cleaner (Temp, Cache, Logs)',
                    'RAM Optimizer และ Process Management',
                    'รองรับ 2 ภาษา (English/Thai)',
                    'Dark/Light Theme',
                    'System Tray Integration',
                ],
                'price' => 990.00,
                'image' => null,
                'images' => [],
                'requires_license' => true,
                'stock' => 999,
                'is_active' => true,
            ],

            // 7. PostXAgent
            [
                'category_id' => $aiCategory->id,
                'name' => 'PostXAgent',
                'slug' => 'postx-agent',
                'sku' => 'PXA-001',
                'short_description' => 'ระบบ AI Brand Promotion Manager โพสต์อัตโนมัติหลายแพลตฟอร์ม พร้อม AI สร้างคอนเทนต์',
                'description' => '<div class="prose max-w-none">
<h2>PostXAgent - AI Brand Promotion Manager</h2>
<p>ระบบจัดการโปรโมทแบรนด์ด้วย AI ที่ปฏิวัติวงการ รวมการสร้างคอนเทนต์อัจฉริยะ, จัดการบัญชี และ Web Automation แบบเรียนรู้ได้เอง</p>

<h3>Intelligent Account Pool</h3>
<ul>
<li>จัดการหลายบัญชี Social Media</li>
<li>Smart Rotation และ Auto-Failover</li>
<li>Error Classification อัตโนมัติ</li>
</ul>

<h3>AI-Powered Features</h3>
<ul>
<li>Self-Learning Web Automation</li>
<li>Teaching Mode สอนระบบเอง</li>
<li>Smart Element Recognition</li>
<li>Self-Repair เมื่อเจอปัญหา</li>
</ul>

<h3>Dual Posting Mode</h3>
<ul>
<li>API-Based Posting (เร็วและแม่นยำ)</li>
<li>Human-Like Web Automation (เลียนแบบคน)</li>
</ul>

<h3>AI Content Generation</h3>
<ul>
<li>Multi-Provider Support</li>
<li>Ollama, Gemini, GPT-4, Claude</li>
<li>Smart Content Pipeline</li>
</ul>

<h3>Supported Platforms</h3>
<p>Facebook, Instagram, TikTok, Twitter/X, LINE, YouTube, Threads, LinkedIn, Pinterest</p>
</div>',
                'features' => [
                    'Intelligent Account Pool Management',
                    'Auto-Failover System',
                    'AI-Powered Web Automation แบบ Self-Learning',
                    'Teaching Mode สอนระบบเอง',
                    'Dual Posting Mode (API + Web Automation)',
                    'AI Content Generation (Ollama, Gemini, GPT-4, Claude)',
                    'Analytics Dashboard Real-time',
                    'รองรับ 9 แพลตฟอร์ม',
                    'Facebook, Instagram, TikTok, Twitter/X',
                    'LINE, YouTube, Threads, LinkedIn, Pinterest',
                ],
                'price' => 7990.00,
                'image' => null,
                'images' => [],
                'requires_license' => true,
                'stock' => 999,
                'is_active' => true,
            ],

            // 8. GPUsharX
            [
                'category_id' => $cloudCategory->id,
                'name' => 'GPUsharX',
                'slug' => 'gpusharx',
                'sku' => 'GSX-001',
                'short_description' => 'แพลตฟอร์มแชร์ GPU แบบกระจายศูนย์ สร้างรายได้จากการ์ดจอของคุณ สำหรับงาน AI Image/Video Generation',
                'description' => '<div class="prose max-w-none">
<h2>GPUsharX - Distributed GPU Sharing Platform</h2>
<p>ระบบแชร์ GPU แบบกระจายศูนย์ที่ให้ผู้ใช้สร้างรายได้จากการ์ดจอของตัวเอง โดยเข้าร่วม Pool สำหรับงาน Image และ Video Generation ออกแบบตามแนวคิด Mining Pool</p>

<h3>Web Platform (Laravel 12)</h3>
<ul>
<li>ระบบลงทะเบียนและยืนยันตัวตน</li>
<li>Admin Dashboard สำหรับจัดการแพลตฟอร์ม</li>
<li>GPU Node Inventory Management</li>
<li>Job Queue Distribution System</li>
<li>Earnings Calculation ตาม GPU Contribution</li>
<li>Withdrawal/Payout Processing</li>
<li>Referral Program</li>
</ul>

<h3>Windows Client (Python + PyQt6)</h3>
<ul>
<li>Desktop GUI สำหรับแชร์ GPU</li>
<li>Auto Hardware Detection และ Benchmarking</li>
<li>Real-time Earnings Tracking</li>
<li>Automated Job Processing</li>
</ul>

<h3>Anti-Cheat System</h3>
<ul>
<li>Hardware Fingerprinting (Machine ID)</li>
<li>Random Verification Challenges</li>
<li>Proof-of-Work Validation</li>
<li>Performance Benchmark Verification</li>
<li>Suspicious Activity Flagging</li>
</ul>

<h3>Revenue Model</h3>
<p>Platform Fee: 10% | Workers Receive: 90% (แบ่งตาม GPU Power และงานที่ทำสำเร็จ)</p>
</div>',
                'features' => [
                    'สร้างรายได้จากการ์ดจอของคุณ',
                    'Web Platform (Laravel 12) พร้อม Admin Dashboard',
                    'GPU Node Inventory Management',
                    'Job Queue Distribution System',
                    'Windows Client (Python + PyQt6)',
                    'Auto Hardware Detection และ Benchmarking',
                    'Real-time Earnings Tracking',
                    'Anti-Cheat System (Hardware Fingerprinting)',
                    'Proof-of-Work Validation',
                    'Referral Program',
                    'Workers รับ 90% ของรายได้',
                ],
                'price' => 0.00, // Free platform, earns from usage
                'image' => null,
                'images' => [],
                'requires_license' => false,
                'stock' => 999,
                'is_active' => true,
            ],
        ];

        foreach ($products as $productData) {
            Product::updateOrCreate(
                ['slug' => $productData['slug']],
                $productData
            );
        }

        $this->command->info('Successfully seeded 8 Xman Studio products!');
    }
}
