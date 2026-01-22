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
                'short_description' => 'แพลตฟอร์มเทรด Cryptocurrency แบบ Arbitrage อัตโนมัติ รองรับ 6 Exchange ชั้นนำ พร้อม UI สวยงามแบบ Glass Morphism และระบบจัดการความเสี่ยง',
                'description' => '<div class="prose max-w-none">
<h2>AutoTrade-X - Cryptocurrency Arbitrage Platform</h2>
<p>แพลตฟอร์มเทรด Cryptocurrency ระดับมืออาชีพที่ออกแบบมาเพื่อค้นหาและใช้ประโยชน์จากโอกาส Arbitrage ระหว่าง Exchange ต่างๆ โดยอัตโนมัติ พัฒนาด้วย .NET 8 และ WPF พร้อม UI ที่สวยงามแบบ Glass Morphism</p>

<h3>Trading Capabilities</h3>
<ul>
<li><strong>Multi-Exchange Connectivity</strong> - เชื่อมต่อ 6 Exchange ชั้นนำในที่เดียว</li>
<li><strong>Live Price Monitoring</strong> - ติดตามราคาแบบ Real-time พร้อมตรวจจับ Arbitrage อัตโนมัติ</li>
<li><strong>Trade History & P&L Analytics</strong> - บันทึกประวัติการเทรดพร้อมวิเคราะห์กำไร/ขาดทุน</li>
<li><strong>Configurable Risk Parameters</strong> - ตั้งค่าพารามิเตอร์ความเสี่ยงและ Safety Thresholds</li>
<li><strong>Simulation Mode</strong> - ทดสอบกลยุทธ์โดยไม่ต้องใช้เงินจริง</li>
</ul>

<h3>Supported Exchanges</h3>
<div class="grid grid-cols-3 gap-2 my-4">
<span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm">Binance</span>
<span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">KuCoin</span>
<span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">OKX</span>
<span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm">Bybit</span>
<span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm">Gate.io</span>
<span class="px-3 py-1 bg-cyan-100 text-cyan-800 rounded-full text-sm">Bitkub</span>
</div>

<h3>User Interface</h3>
<ul>
<li>Dark Theme พร้อม Glass Morphism Aesthetic</li>
<li>Smooth Animation Effects</li>
<li>Animated Splash Screen แบบ Hyperdrive Visual</li>
<li>Chart Integration ด้วย LiveChartsCore และ SkiaSharp</li>
</ul>

<h3>Strategy Settings (ค่าเริ่มต้น)</h3>
<table class="min-w-full">
<tr><td>Minimum Spread Threshold</td><td>0.3%</td></tr>
<tr><td>Minimum Expected Profit</td><td>0.5 Quote Currency</td></tr>
<tr><td>Polling Frequency</td><td>1000ms</td></tr>
</table>

<h3>Risk Management Limits</h3>
<table class="min-w-full">
<tr><td>Maximum Position Size</td><td>100 per transaction</td></tr>
<tr><td>Daily Loss Ceiling</td><td>50 units</td></tr>
<tr><td>Max Daily Transactions</td><td>100 trades</td></tr>
</table>

<h3>Technical Stack</h3>
<ul>
<li><strong>Framework:</strong> .NET 8.0 (Windows)</li>
<li><strong>UI:</strong> WPF with Custom Styling</li>
<li><strong>Charts:</strong> LiveChartsCore + SkiaSharp</li>
<li><strong>Architecture:</strong> Clean Architecture</li>
<li><strong>Database:</strong> SQLite</li>
</ul>

<h3>System Requirements</h3>
<table class="min-w-full">
<tr><td>Operating System</td><td>Windows 10/11 (64-bit)</td></tr>
<tr><td>Runtime</td><td>.NET 8.0 SDK</td></tr>
<tr><td>RAM</td><td>8GB+ (แนะนำ)</td></tr>
<tr><td>Network</td><td>การเชื่อมต่ออินเทอร์เน็ตที่เสถียร</td></tr>
</table>

<div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 my-4">
<p class="font-bold text-yellow-800">คำเตือน</p>
<p class="text-yellow-700">การเทรด Cryptocurrency มีความเสี่ยงสูง อาจสูญเสียเงินทุนทั้งหมด แนะนำให้ทดสอบใน Simulation Mode ก่อนเทรดจริง</p>
</div>
</div>',
                'features' => [
                    'รองรับ 6 Exchange ชั้นนำ (Binance, KuCoin, OKX, Bybit, Gate.io, Bitkub)',
                    'Live Price Monitoring และตรวจจับ Arbitrage อัตโนมัติ',
                    'Trade History และ P&L Analytics พร้อม Charts',
                    'Configurable Risk Parameters และ Safety Thresholds',
                    'Simulation Mode ทดสอบกลยุทธ์โดยไม่ใช้เงินจริง',
                    'UI แบบ Glass Morphism พร้อม Dark Theme',
                    'Animated Splash Screen แบบ Hyperdrive',
                    'Clean Architecture Design Pattern',
                    'SQLite Database สำหรับเก็บข้อมูล',
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
                'short_description' => 'ระบบเครือข่าย P2P Mesh Network แบบกระจายศูนย์ สำหรับการสื่อสารที่ปลอดภัย เข้ารหัส End-to-End รองรับ Cross-Platform',
                'description' => '<div class="prose max-w-none">
<h2>SpiderX - Decentralized P2P Mesh Network</h2>
<p class="text-lg"><em>"Your communication. Your rules. No middleman."</em></p>
<p>แอปพลิเคชันเครือข่าย Mesh แบบ Peer-to-Peer ที่ช่วยให้คุณสื่อสารอย่างปลอดภัยและเป็นส่วนตัว โดยไม่ต้องพึ่งพาเซิร์ฟเวอร์ส่วนกลาง สร้างบน Cryptography สมัยใหม่</p>

<h3>Communication Services</h3>
<ul>
<li><strong>Encrypted Chat</strong> - Private messaging พร้อม Perfect Forward Secrecy</li>
<li><strong>Voice Calls</strong> - การโทรเสียงที่เข้ารหัสคุณภาพสูง</li>
<li><strong>Group Messaging</strong> - กลุ่มส่วนตัวสำหรับสมาชิกที่ได้รับเชิญเท่านั้น</li>
<li><strong>Offline Messages</strong> - ส่งข้อความได้แม้ปลายทางออฟไลน์</li>
</ul>

<h3>File Sharing</h3>
<ul>
<li><strong>P2P File Transfer</strong> - ดาวน์โหลดแบบ BitTorrent-style จากหลาย Peer</li>
<li><strong>File Catalog</strong> - เรียกดูและค้นหาไฟล์จากผู้ติดต่อ</li>
<li><strong>Chunked Transfer</strong> - Resume ดาวน์โหลดที่ค้างได้</li>
<li><strong>Integrity Verification</strong> - ตรวจสอบ SHA-256 Hash ทุกไฟล์</li>
</ul>

<h3>Virtual LAN</h3>
<ul>
<li><strong>Virtual Network Creation</strong> - สร้าง Virtual LAN ข้ามอินเทอร์เน็ต</li>
<li><strong>LAN Games</strong> - เล่นเกม LAN-only กับเพื่อนทั่วโลก</li>
<li><strong>Low Latency</strong> - ปรับแต่งสำหรับ Real-time Gaming</li>
<li><strong>Easy Setup</strong> - One-click enable, กำหนด IP อัตโนมัติ</li>
</ul>

<h3>Discovery & Connectivity</h3>
<ul>
<li><strong>LAN Discovery</strong> - ค้นหา Peer ในเครือข่ายท้องถิ่นอัตโนมัติ</li>
<li><strong>DHT Lookup</strong> - ค้นหา Peer ทั่วโลกผ่าน Distributed Hash Table</li>
<li><strong>NAT Traversal</strong> - เชื่อมต่อผ่าน Firewall อัตโนมัติ</li>
<li><strong>QR Code Sharing</strong> - เพิ่มผู้ติดต่อด้วยการสแกน QR Code</li>
</ul>

<h3>Cryptographic Security</h3>
<table class="min-w-full">
<thead><tr><th>Feature</th><th>Implementation</th></tr></thead>
<tbody>
<tr><td>Digital Signatures</td><td>Ed25519</td></tr>
<tr><td>Key Exchange</td><td>X25519 (ECDH)</td></tr>
<tr><td>Encryption</td><td>AES-256-GCM</td></tr>
<tr><td>Integrity</td><td>SHA-256 & BLAKE2b</td></tr>
<tr><td>Forward Secrecy</td><td>Ephemeral Keys</td></tr>
<tr><td>Zero Knowledge</td><td>ไม่ต้องลงทะเบียน/อีเมล/เบอร์โทร</td></tr>
</tbody>
</table>

<h3>Identity System</h3>
<p>ระบบ Cryptographic Identity คล้าย Cryptocurrency Wallet (format: spx1...)</p>
<ol>
<li>Seed Phrase Generation</li>
<li>Ed25519 Private Key Derivation</li>
<li>Public Key Generation</li>
<li>SHA-256 + RIPEMD-160 Hashing</li>
<li>Address Encoding</li>
</ol>

<h3>Network Ports</h3>
<table class="min-w-full">
<tr><td>UDP 45678</td><td>P2P Communication, DHT</td></tr>
<tr><td>TCP 45679</td><td>Large File Transfers</td></tr>
<tr><td>UDP 5353</td><td>mDNS LAN Discovery</td></tr>
</table>

<h3>Platform Support</h3>
<div class="flex flex-wrap gap-2 my-4">
<span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full">Windows</span>
<span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full">macOS</span>
<span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full">Linux</span>
<span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full">iOS</span>
<span class="px-3 py-1 bg-green-100 text-green-800 rounded-full">Android</span>
</div>

<h3>Technical Stack</h3>
<ul>
<li><strong>Framework:</strong> .NET 9.0 + MAUI</li>
<li><strong>P2P Engine:</strong> SpiderNode + Kademlia DHT</li>
<li><strong>Transport:</strong> UDP, TCP, WiFi Direct, Bluetooth</li>
</ul>

<h3>Use Cases</h3>
<ul>
<li><strong>บุคคลทั่วไป:</strong> Private messaging, File sharing, LAN Gaming</li>
<li><strong>องค์กร:</strong> Secure internal communication, Field operations</li>
<li><strong>นักพัฒนา:</strong> P2P application foundation, IoT mesh networking</li>
</ul>

<h3>Roadmap</h3>
<ul>
<li><strong>v1.0 (ปัจจุบัน):</strong> Core engine, encryption, chat, file sharing, virtual LAN</li>
<li><strong>v1.1:</strong> Voice calls, video calls, group enhancements</li>
<li><strong>v2.0:</strong> Screen sharing, Tor integration, browser extension</li>
</ul>
</div>',
                'features' => [
                    'เครือข่าย Decentralized ไม่มี Central Server',
                    'End-to-End Encryption (X25519 + AES-256-GCM)',
                    'Perfect Forward Secrecy ด้วย Ephemeral Keys',
                    'Cryptographic Identity (SpiderId format: spx1...)',
                    'Zero-Knowledge Design ไม่ต้องลงทะเบียน',
                    'Encrypted Chat และ Voice Calls',
                    'Group Messaging สำหรับสมาชิกที่ได้รับเชิญ',
                    'P2P File Sharing แบบ BitTorrent-style',
                    'Virtual LAN สำหรับ Gaming ข้ามอินเทอร์เน็ต',
                    'NAT Traversal และ LAN Discovery อัตโนมัติ',
                    'QR Code Contact Sharing',
                    'รองรับ Cross-Platform (Win/Mac/Linux/iOS/Android)',
                    'พัฒนาด้วย .NET 9.0 + MAUI',
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
                'short_description' => 'GitHub Sync Service พร้อม AI-Powered Auto-Fix รองรับ 6 โหมด AI จัดการหลายโปรเจค แจ้งเตือน 5 ช่องทาง',
                'description' => '<div class="prose max-w-none">
<h2>XcluadeAgent - AI-Powered GitHub Sync Service</h2>
<p>ระบบ Sync GitHub Releases อัตโนมัติพร้อมความสามารถ AI ในการวิเคราะห์และแก้ไขปัญหา จัดการหลายโปรเจคผ่าน Dashboard ส่วนกลาง</p>

<h3>Core Features</h3>
<ul>
<li><strong>GitHub Release Sync</strong> - ดาวน์โหลดและ Deploy อัตโนมัติ</li>
<li><strong>Multi-Project Dashboard</strong> - จัดการไม่จำกัดโปรเจค</li>
<li><strong>AI Error Analysis</strong> - วิเคราะห์ Error อัจฉริยะ</li>
<li><strong>Auto-Rollback</strong> - ย้อนกลับอัตโนมัติเมื่อมีปัญหา</li>
<li><strong>Backup System</strong> - สำรองข้อมูลก่อนทุก Sync</li>
</ul>

<h3>AI Assistant Modes (6 โหมด)</h3>
<table class="min-w-full">
<thead><tr><th>Mode</th><th>Description</th><th>Risk</th></tr></thead>
<tbody>
<tr><td>Disabled</td><td>ปิดใช้งาน AI</td><td>-</td></tr>
<tr><td>Smart Alert</td><td>แจ้งเตือนอัจฉริยะ</td><td>LOW</td></tr>
<tr><td>Suggest Fix</td><td>แนะนำวิธีแก้ไข</td><td>LOW</td></tr>
<tr><td>Approval Required</td><td>สร้าง Fix รอการอนุมัติ</td><td>MEDIUM</td></tr>
<tr><td>Staging Test</td><td>ทดสอบใน Staging ก่อน</td><td>MEDIUM</td></tr>
<tr><td>Auto Production</td><td>แก้ไข Production โดยตรง</td><td class="text-red-600">HIGH</td></tr>
</tbody>
</table>

<h3>AI Providers รองรับ</h3>
<div class="flex flex-wrap gap-2 my-4">
<span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full">Ollama (Local)</span>
<span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full">Claude</span>
<span class="px-3 py-1 bg-green-100 text-green-800 rounded-full">OpenAI</span>
</div>

<h3>Framework Support</h3>
<ul>
<li><strong>Laravel:</strong> composer install, artisan commands</li>
<li><strong>Node.js:</strong> npm install, npm run build</li>
<li><strong>React/Vue:</strong> Build processes</li>
<li><strong>Django:</strong> pip install, migrations</li>
<li><strong>.NET:</strong> dotnet restore, dotnet build</li>
</ul>

<h3>Notification Channels (5 ช่องทาง)</h3>
<ul>
<li>Discord Webhooks</li>
<li>Telegram Bot API</li>
<li>LINE Official Account Messaging API</li>
<li>Slack Webhooks</li>
<li>SMTP Email</li>
<li>Custom Webhooks</li>
</ul>

<h3>Monitoring Features</h3>
<ul>
<li>Health Checks</li>
<li>Disk Usage Monitoring</li>
<li>SSL Certificate Tracking</li>
<li>Real-time Status Dashboard</li>
</ul>

<h3>Security Features</h3>
<ul>
<li>JWT Authentication</li>
<li>Role-Based Access Control</li>
<li>Two-Factor Authentication (2FA)</li>
</ul>

<h3>Licensing</h3>
<table class="min-w-full">
<tr><td>Community</td><td>ฟรี</td><td>3 โปรเจค</td></tr>
<tr><td>Professional</td><td>Paid</td><td>10 โปรเจค + Advanced Features</td></tr>
<tr><td>Enterprise</td><td>Paid</td><td>Unlimited + Priority Support</td></tr>
</table>

<h3>System Requirements</h3>
<ul>
<li><strong>Runtime:</strong> .NET 8.0</li>
<li><strong>OS:</strong> Linux (Ubuntu 20.04+) หรือ Windows Server 2019+</li>
<li><strong>RAM:</strong> 1GB (แนะนำ 2GB)</li>
<li><strong>Database:</strong> SQLite หรือ PostgreSQL</li>
</ul>

<h3>Installation Methods</h3>
<ul>
<li>Ubuntu/Debian Shell Script</li>
<li>Docker Container</li>
<li>Docker Compose</li>
</ul>
</div>',
                'features' => [
                    'GitHub Release Sync อัตโนมัติ',
                    'Multi-Project Dashboard จัดการไม่จำกัดโปรเจค',
                    'AI Assistant 6 โหมด (Disabled to Auto Production)',
                    'รองรับ AI Providers: Ollama, Claude, OpenAI',
                    'Auto-Rollback เมื่อตรวจพบปัญหา',
                    'Backup อัตโนมัติก่อนทุก Sync',
                    'รองรับ Laravel, Node.js, React/Vue, Django, .NET',
                    'แจ้งเตือน 5 ช่องทาง: Discord, Telegram, LINE, Slack, Email',
                    'Web Dashboard พร้อม Dark Mode',
                    'CLI Tool (syncctl)',
                    'JWT + Role-Based Access + 2FA',
                    'Health Checks, Disk, SSL Monitoring',
                    '3 License Tiers: Community, Professional, Enterprise',
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
                'short_description' => 'เครื่องมือจัดการอุปกรณ์ Android ครบวงจร Flash ROM, Backup, FRP Bypass, IMEI Tools รองรับ Qualcomm, MTK, Samsung, Xiaomi',
                'description' => '<div class="prose max-w-none">
<h2>PhoneX Manager - Universal Android Device Management</h2>
<p>เครื่องมือจัดการอุปกรณ์ Android บน Windows แบบครบวงจร รองรับการ Flash ROM, Backup, Recovery และเครื่องมือ Engineering สำหรับหลายแบรนด์</p>

<h3>Device Management</h3>
<ul>
<li><strong>Auto-Detection</strong> - ตรวจจับอุปกรณ์อัตโนมัติผ่าน ADB/Fastboot</li>
<li><strong>Multi-Brand Support</strong> - รองรับ Qualcomm, MediaTek, Samsung, Xiaomi</li>
<li><strong>Real-time Monitoring</strong> - ติดตามสถานะอุปกรณ์แบบ Real-time</li>
</ul>

<h3>ROM Operations</h3>
<ul>
<li><strong>Full Backup</strong> - สำรองข้อมูลทั้งระบบพร้อมเลือก Partition</li>
<li><strong>Multi-Mode Flashing</strong> - Flash ROM หลายโหมด</li>
<li><strong>Boot Image Editor</strong> - แก้ไข Boot Image</li>
<li><strong>Partition Management</strong> - จัดการ Partition (GPT/MBR)</li>
</ul>

<h3>Engineering Tools</h3>
<table class="min-w-full">
<tr><td><strong>FRP Bypass</strong></td><td>ปลดล็อค Factory Reset Protection</td></tr>
<tr><td><strong>Screen Lock Bypass</strong></td><td>ปลดล็อคหน้าจอ</td></tr>
<tr><td><strong>IMEI Operations</strong></td><td>Read/Write/Repair IMEI</td></tr>
<tr><td><strong>Network Unlock</strong></td><td>ปลดล็อคเครือข่าย</td></tr>
<tr><td><strong>Bloatware Removal</strong></td><td>ลบแอพที่ติดมากับเครื่อง</td></tr>
<tr><td><strong>Recovery Management</strong></td><td>จัดการ Recovery</td></tr>
</table>

<h3>Utility Features</h3>
<ul>
<li><strong>Hex Editor</strong> - แก้ไขไฟล์ในรูปแบบ Hexadecimal</li>
<li><strong>ADB Terminal</strong> - เทอร์มินัลสำหรับคำสั่ง ADB</li>
<li><strong>Screen Mirroring</strong> - แสดงหน้าจอผ่าน Scrcpy</li>
<li><strong>Logcat Viewer</strong> - ดู Log ของอุปกรณ์</li>
<li><strong>Checksum Calculator</strong> - คำนวณ Checksum</li>
</ul>

<h3>Supported Chipsets</h3>
<div class="flex flex-wrap gap-2 my-4">
<span class="px-3 py-1 bg-red-100 text-red-800 rounded-full">Qualcomm</span>
<span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full">MediaTek (MTK)</span>
<span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full">Samsung Exynos</span>
<span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full">Xiaomi</span>
</div>

<h3>Auto Setup</h3>
<ul>
<li>ดาวน์โหลด Android Platform Tools อัตโนมัติ</li>
<li>ติดตั้ง Driver: Google USB, Qualcomm, MTK, Samsung</li>
</ul>

<h3>System Requirements</h3>
<table class="min-w-full">
<tr><td>Operating System</td><td>Windows 10/11 (64-bit)</td></tr>
<tr><td>Runtime</td><td>.NET 8.0</td></tr>
<tr><td>Device Requirement</td><td>USB Debugging Enabled</td></tr>
</table>

<div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 my-4">
<p class="font-bold text-yellow-800">คำเตือน</p>
<p class="text-yellow-700">แนะนำให้สำรองข้อมูลก่อนทำการ Flash ROM ทุกครั้ง ซอฟต์แวร์นี้ให้มาตามสภาพ (as-is) โดยไม่มีการรับประกัน</p>
</div>
</div>',
                'features' => [
                    'Auto-Detection ผ่าน ADB/Fastboot',
                    'รองรับ Qualcomm, MediaTek, Samsung, Xiaomi',
                    'Full Backup พร้อมเลือก Partition',
                    'Multi-Mode ROM Flashing',
                    'Boot Image Editor',
                    'Partition Management (GPT/MBR)',
                    'FRP Bypass',
                    'Screen Lock Bypass',
                    'IMEI Read/Write/Repair',
                    'Network Unlock',
                    'Bloatware Removal',
                    'Integrated Hex Editor',
                    'ADB Terminal',
                    'Screen Mirroring ผ่าน Scrcpy',
                    'Logcat Viewer',
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
                'name' => 'Live x Shop Pro',
                'slug' => 'livexshop-pro',
                'sku' => 'LXS-001',
                'short_description' => 'แพลตฟอร์ม Live Shopping ครบวงจร รวมแชทจาก Facebook/TikTok/LINE ตรวจสลิปด้วย AI OCR เชื่อมต่อขนส่ง Kerry, Flash, J&T',
                'description' => '<div class="prose max-w-none">
<h2>Live x Shop Pro - Live Shopping Platform</h2>
<p>แพลตฟอร์ม Live Shopping ครบวงจรสำหรับผู้ประกอบการไทย ออกแบบมาเพื่อการขายผ่าน Facebook Live, TikTok Live และ LINE</p>

<h3>Multi-Platform Chat Aggregation</h3>
<ul>
<li>รวมแชทจาก <strong>Facebook Live</strong>, <strong>TikTok Live</strong>, <strong>LINE OA</strong> ไว้ในที่เดียว</li>
<li>Browser Bot และ Mobile App</li>
<li>Real-time Chat Monitoring ทุกแพลตฟอร์มพร้อมกัน</li>
</ul>

<h3>Financial Management System</h3>
<ul>
<li><strong>AI-Powered OCR</strong> - อ่านสลิปโอนเงินอัตโนมัติ</li>
<li><strong>Bank SMS Verification</strong> - ยืนยันยอดเงินเข้าอัตโนมัติ</li>
<li><strong>Fraud Detection</strong> - ตรวจจับสลิปปลอมพร้อมโหมดป้องกัน</li>
<li><strong>Real-time Sales Reporting</strong> - สรุปยอดขาย Live Dashboard</li>
</ul>

<h3>Inventory & Shipping</h3>
<ul>
<li><strong>POS System</strong> - ขายหน้าร้าน</li>
<li><strong>Stock Management</strong> - จัดการสต็อก</li>
<li><strong>Barcode Printing/Scanning</strong> - พิมพ์และสแกนบาร์โค้ด</li>
<li><strong>Shipping Integration</strong> - เชื่อมต่อ Kerry, Flash, J&T</li>
</ul>

<h3>Multi-Screen Operations</h3>
<ul>
<li><strong>Real-time Order Display</strong> - หน้าจอแสดงออเดอร์สำหรับแพ็คสินค้า</li>
<li><strong>LAN/VPN Connectivity</strong> - ทำงานระยะไกล</li>
<li><strong>OBS Overlay</strong> - แสดงยอดขายระหว่าง Live</li>
</ul>

<h3>Technical Architecture</h3>
<p>Clean Architecture 4 Layers:</p>
<ol>
<li><strong>Core</strong> - Domain Entities/Interfaces</li>
<li><strong>Application</strong> - Business Logic/CQRS</li>
<li><strong>Infrastructure</strong> - Databases/APIs</li>
<li><strong>Desktop UI</strong> - WPF Interface</li>
</ol>

<h3>Technology Stack</h3>
<table class="min-w-full">
<tr><td>Framework</td><td>.NET 10</td></tr>
<tr><td>Desktop UI</td><td>WPF + MaterialDesignInXAML + MahApps.Metro</td></tr>
<tr><td>Mobile</td><td>.NET MAUI</td></tr>
<tr><td>Database</td><td>SQLite + Entity Framework Core</td></tr>
<tr><td>Real-time</td><td>SignalR</td></tr>
<tr><td>Web Server</td><td>EmbedIO (Embedded)</td></tr>
</table>

<h3>System Requirements</h3>
<table class="min-w-full">
<thead><tr><th>Spec</th><th>Minimum</th><th>Recommended</th></tr></thead>
<tbody>
<tr><td>OS</td><td>Windows 10 (1903+)</td><td>Windows 11</td></tr>
<tr><td>RAM</td><td>4 GB</td><td>8 GB</td></tr>
<tr><td>Storage</td><td>500 MB</td><td>2 GB</td></tr>
<tr><td>Display</td><td>1366x768</td><td>1920x1080</td></tr>
</tbody>
</table>

<h3>เหมาะสำหรับ</h3>
<ul>
<li>ร้านค้าออนไลน์ที่ขายผ่าน Live</li>
<li>แม่ค้าออนไลน์ที่ต้องการจัดการหลายแพลตฟอร์ม</li>
<li>ธุรกิจ SME ที่ต้องการระบบ POS + Live Shopping</li>
</ul>
</div>',
                'features' => [
                    'รวมแชทจาก Facebook Live, TikTok Live, LINE OA',
                    'AI-Powered OCR อ่านสลิปโอนเงินอัตโนมัติ',
                    'Bank SMS Verification ยืนยันยอดเงินเข้า',
                    'Fraud Detection ตรวจจับสลิปปลอม',
                    'Real-time Sales Dashboard',
                    'POS System ขายหน้าร้าน',
                    'Stock Management จัดการสต็อก',
                    'Barcode Printing/Scanning',
                    'เชื่อมต่อขนส่ง Kerry, Flash, J&T',
                    'Multi-Screen Operations สำหรับแพ็คสินค้า',
                    'OBS Overlay แสดงยอดขายระหว่าง Live',
                    'LAN/VPN Support ทำงานระยะไกล',
                    'Desktop (WPF) + Mobile (MAUI)',
                    'SignalR Real-time Communication',
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
                'short_description' => 'เครื่องมือจัดการเครือข่ายและระบบ Windows ตรวจสอบ Bandwidth แยกโปรเซส ควบคุมระดับ Kernel ด้วย WFP',
                'description' => '<div class="prose max-w-none">
<h2>WinXTools - Advanced Windows Network & System Management</h2>
<p>เครื่องมือระดับ Professional สำหรับ Windows ที่รวมการตรวจสอบเครือข่าย, ควบคุม Bandwidth, จัดการแอปพลิเคชัน และทำความสะอาดระบบไว้ในแอปเดียว</p>

<h3>Network Monitoring</h3>
<ul>
<li><strong>Real-time Bandwidth Tracking</strong> - แยกตามโปรเซส</li>
<li><strong>TCP/UDP Connection Monitoring</strong> - ติดตามการเชื่อมต่อทุกประเภท</li>
<li><strong>Live Performance Charts</strong> - กราฟแสดงความเร็ว Real-time</li>
<li><strong>Historical Data Logging</strong> - บันทึกประวัติ (เก็บ 30 วัน)</li>
</ul>

<h3>Bandwidth Control (2 โหมด)</h3>
<table class="min-w-full">
<thead><tr><th>Mode</th><th>Technology</th><th>Capability</th></tr></thead>
<tbody>
<tr><td><strong>Basic</strong></td><td>Windows Firewall API</td><td>Block/Allow เท่านั้น</td></tr>
<tr><td><strong>Advanced</strong></td><td>Windows Filtering Platform (WFP)</td><td>Kernel-level Packet Filtering + Custom Limits + Priority</td></tr>
</tbody>
</table>

<h3>Application Management</h3>
<ul>
<li><strong>Deep Uninstaller</strong> - ลบแอพพร้อมล้าง Registry</li>
<li><strong>Leftover Detection</strong> - ตรวจจับไฟล์เหลือค้าง</li>
<li><strong>Batch Uninstall</strong> - ลบหลายแอพพร้อมกัน</li>
</ul>

<h3>System Cleanup</h3>
<ul>
<li>Temporary Files</li>
<li>Browser Caches</li>
<li>Windows Update Cache</li>
<li>Recycle Bin</li>
<li>Thumbnails</li>
<li>System Logs</li>
<li>Legacy Windows Folders (Windows.old)</li>
</ul>

<h3>RAM Optimizer</h3>
<ul>
<li>ล้าง Memory ที่ไม่ใช้</li>
<li>Process Management</li>
<li>Memory Usage Monitoring</li>
</ul>

<h3>Customization</h3>
<ul>
<li><strong>Language:</strong> English / ภาษาไทย</li>
<li><strong>Theme:</strong> Dark / Light</li>
<li><strong>System Tray Integration</strong></li>
<li><strong>Auto-Start Option</strong></li>
<li><strong>Notification Settings</strong></li>
</ul>

<h3>Technical Stack</h3>
<table class="min-w-full">
<tr><td>Framework</td><td>.NET 10.0</td></tr>
<tr><td>UI</td><td>WPF with MVVM</td></tr>
<tr><td>Charts</td><td>LiveCharts2 + SkiaSharp</td></tr>
<tr><td>Database</td><td>SQLite</td></tr>
<tr><td>Network API</td><td>Windows IP Helper (iphlpapi.dll)</td></tr>
<tr><td>Bandwidth Control</td><td>Windows Filtering Platform (WFP)</td></tr>
<tr><td>Build</td><td>Self-contained, Portable</td></tr>
</table>

<h3>System Requirements</h3>
<table class="min-w-full">
<thead><tr><th>Spec</th><th>Minimum</th><th>Recommended</th></tr></thead>
<tbody>
<tr><td>OS</td><td>Windows 10 (1903+)</td><td>Windows 11</td></tr>
<tr><td>CPU</td><td>Dual-core 1.5 GHz</td><td>Quad-core 2.0+ GHz</td></tr>
<tr><td>RAM</td><td>2 GB</td><td>4+ GB</td></tr>
<tr><td>Storage</td><td>100 MB</td><td>200 MB</td></tr>
<tr><td>Runtime</td><td>.NET 10.0</td><td>.NET 10.0</td></tr>
</tbody>
</table>

<h3>Default Settings</h3>
<ul>
<li>Refresh Interval: 1000ms</li>
<li>Data Retention: 30 days</li>
<li>Bandwidth Mode: Basic</li>
</ul>
</div>',
                'features' => [
                    'Real-time Bandwidth Tracking แยกตามโปรเซส',
                    'TCP/UDP Connection Monitoring',
                    'Live Performance Charts',
                    'Historical Data Logging (30 วัน)',
                    'Basic Mode: Windows Firewall API',
                    'Advanced Mode: Kernel-level WFP Control',
                    'Per-Application Bandwidth Limits',
                    'Priority Management',
                    'Deep Application Uninstaller + Registry Cleanup',
                    'Leftover Detection และ Batch Uninstall',
                    'System Cleaner (Temp, Cache, Logs, Windows.old)',
                    'RAM Optimizer และ Process Management',
                    'รองรับ 2 ภาษา (English/Thai)',
                    'Dark/Light Theme',
                    'System Tray Integration',
                    'Self-contained Portable Build',
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
                'short_description' => 'ระบบ AI Brand Promotion โพสต์อัตโนมัติ 9 แพลตฟอร์ม พร้อม Self-Learning Web Automation และ AI Content Generation',
                'description' => '<div class="prose max-w-none">
<h2>PostXAgent - AI Brand Promotion Manager</h2>
<p>ระบบจัดการโปรโมทแบรนด์ด้วย AI ที่ปฏิวัติวงการ รวมการสร้างคอนเทนต์อัจฉริยะ, จัดการบัญชี และ Web Automation แบบเรียนรู้ได้เอง</p>

<h3>Intelligent Account Pool Management</h3>
<ul>
<li>จัดการหลายบัญชี Social Media</li>
<li>Rotation Strategies: Round-robin, Random, Least-used, Priority-based</li>
<li>Multiple Brand Pools</li>
<li>Smart Rotation และ Auto-Failover</li>
</ul>

<h3>Auto-Failover System</h3>
<table class="min-w-full">
<thead><tr><th>Error Type</th><th>Action</th></tr></thead>
<tbody>
<tr><td>Rate Limiting (429)</td><td>Cooldown + Switch Account</td></tr>
<tr><td>Unauthorized (401)</td><td>Token Refresh</td></tr>
<tr><td>Account Disabled</td><td>Switch Account + Admin Alert</td></tr>
<tr><td>Spam Detection</td><td>Cooldown + Review</td></tr>
</tbody>
</table>

<h3>AI-Powered Web Automation</h3>
<ul>
<li><strong>Teaching Mode</strong> - บันทึกการกระทำของคน</li>
<li><strong>Autonomous Mode</strong> - Deep Learning Implementation</li>
<li><strong>Multi-Selector Strategy</strong> - ID, CSS, XPath, Text, ARIA (Confidence สูงสุด 99%)</li>
<li><strong>Self-Repair</strong> - ตรวจจับและแก้ไข Workflow อัตโนมัติ</li>
</ul>

<h3>Dual Posting Mode</h3>
<table class="min-w-full">
<tr><td><strong>API-Based</strong></td><td>เร็ว, น่าเชื่อถือ, Feature-rich, มี Rate Limit</td></tr>
<tr><td><strong>WebView</strong></td><td>Human-like, Bypass Restrictions, ช้ากว่า, ไม่ต้องใช้ API</td></tr>
</table>

<h3>AI Content Generation</h3>
<ul>
<li><strong>Multi-Provider Support</strong> - เรียงลำดับ: Ollama (Free) → Gemini (Free) → GPT-4 → Claude</li>
<li>สร้าง Text, Images, Video</li>
<li>Platform-specific Optimization</li>
<li>Hashtag Generation, Emoji Insertion, Length Adjustment</li>
</ul>

<h3>Supported Platforms (9)</h3>
<div class="grid grid-cols-3 gap-2 my-4">
<span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">Facebook</span>
<span class="px-3 py-1 bg-pink-100 text-pink-800 rounded-full text-sm">Instagram</span>
<span class="px-3 py-1 bg-black text-white rounded-full text-sm">TikTok</span>
<span class="px-3 py-1 bg-gray-800 text-white rounded-full text-sm">Twitter/X</span>
<span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">LINE</span>
<span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">YouTube</span>
<span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm">Threads</span>
<span class="px-3 py-1 bg-blue-700 text-white rounded-full text-sm">LinkedIn</span>
<span class="px-3 py-1 bg-red-600 text-white rounded-full text-sm">Pinterest</span>
</div>

<h3>System Architecture</h3>
<ul>
<li><strong>Web Control Panel:</strong> Laravel + Vue.js (Port 8000)</li>
<li><strong>AI Manager Core:</strong> C# Backend (Ports 5000-5002)</li>
<li><strong>SignalR Hub:</strong> Real-time Updates</li>
<li><strong>Process Orchestrator:</strong> รองรับ 40+ CPU Cores</li>
<li><strong>Workflow Learning Engine</strong></li>
<li><strong>Platform Workers:</strong> 6+ Social Networks</li>
</ul>

<h3>Analytics Dashboard</h3>
<ul>
<li>Total Daily Posts</li>
<li>Active Accounts Count</li>
<li>Success Rate Percentage</li>
<li>Platform Distribution</li>
<li>Engagement Trends</li>
<li>Activity Log with Timestamps</li>
</ul>

<h3>Security</h3>
<ul>
<li>API Gateway with Rate Limiting</li>
<li>JWT Token Authentication</li>
<li>OAuth 2.0 Integration</li>
<li>Encrypted Credential Storage</li>
<li>Redis Session Management</li>
</ul>

<h3>System Requirements</h3>
<table class="min-w-full">
<thead><tr><th>Component</th><th>Minimum</th><th>Recommended</th></tr></thead>
<tbody>
<tr><td>CPU</td><td>8 cores</td><td>40+ cores</td></tr>
<tr><td>RAM</td><td>16 GB</td><td>64 GB</td></tr>
<tr><td>Storage</td><td>100 GB SSD</td><td>500 GB NVMe</td></tr>
<tr><td>OS</td><td>Windows Server 2019</td><td>Windows Server 2022</td></tr>
<tr><td>.NET</td><td>8.0</td><td>8.0</td></tr>
<tr><td>PHP</td><td>8.2</td><td>8.3</td></tr>
<tr><td>Redis</td><td>7.0</td><td>7.2</td></tr>
<tr><td>MySQL</td><td>8.0</td><td>8.0</td></tr>
</tbody>
</table>
</div>',
                'features' => [
                    'Intelligent Account Pool Management',
                    'Rotation Strategies: Round-robin, Random, Least-used, Priority',
                    'Auto-Failover System (Rate Limit, Auth, Spam Detection)',
                    'AI-Powered Web Automation แบบ Self-Learning',
                    'Teaching Mode บันทึกการกระทำ',
                    'Multi-Selector Strategy (Confidence 99%)',
                    'Self-Repair Workflow อัตโนมัติ',
                    'Dual Posting Mode (API + WebView)',
                    'AI Content Generation (Ollama, Gemini, GPT-4, Claude)',
                    'Platform-specific Optimization',
                    'รองรับ 9 แพลตฟอร์ม',
                    'Analytics Dashboard Real-time',
                    'JWT + OAuth 2.0 + Encrypted Storage',
                    'รองรับ 40+ CPU Cores',
                    'Laravel + Vue.js + C# Backend',
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
                'short_description' => 'แพลตฟอร์มแชร์ GPU แบบกระจายศูนย์ สร้างรายได้ 90% จากการ์ดจอของคุณ พร้อมระบบ Anti-Cheat',
                'description' => '<div class="prose max-w-none">
<h2>GPUsharX - Distributed GPU Sharing Platform</h2>
<p>ระบบแชร์ GPU แบบกระจายศูนย์ที่ให้ผู้ใช้สร้างรายได้จากการ์ดจอของตัวเอง โดยเข้าร่วม Pool สำหรับงาน Image และ Video Generation ออกแบบตามแนวคิด Mining Pool</p>

<h3>Revenue Model</h3>
<div class="bg-green-50 border border-green-200 rounded-lg p-4 my-4">
<p class="text-2xl font-bold text-green-800">Workers รับ 90%</p>
<p class="text-green-600">Platform Fee เพียง 10% - แบ่งตาม GPU Power และงานที่ทำสำเร็จ</p>
</div>

<h3>Web Platform (Laravel 12)</h3>
<ul>
<li><strong>User Registration</strong> - ระบบลงทะเบียนและยืนยันตัวตน</li>
<li><strong>Admin Dashboard</strong> - จัดการแพลตฟอร์ม</li>
<li><strong>GPU Node Inventory</strong> - จัดการและติดตามสถานะ GPU</li>
<li><strong>Job Queue Distribution</strong> - กระจายงานอัจฉริยะ</li>
<li><strong>Earnings Calculation</strong> - คำนวณรายได้ตาม GPU Contribution</li>
<li><strong>Withdrawal/Payout</strong> - ระบบถอนเงิน</li>
<li><strong>Referral Program</strong> - โปรแกรมแนะนำเพื่อน</li>
</ul>

<h3>Windows Client (Python + PyQt6)</h3>
<ul>
<li><strong>Desktop GUI</strong> - ใช้งานง่าย</li>
<li><strong>Auto Hardware Detection</strong> - ตรวจจับ GPU อัตโนมัติ</li>
<li><strong>Performance Benchmarking</strong> - ทดสอบประสิทธิภาพ</li>
<li><strong>Autonomous Job Processing</strong> - รับและประมวลผลงานอัตโนมัติ</li>
<li><strong>Real-time Earnings Tracking</strong> - ติดตามรายได้ Real-time</li>
</ul>

<h3>Anti-Cheat Verification System</h3>
<table class="min-w-full">
<tr><td><strong>Hardware Fingerprinting</strong></td><td>Machine ID สำหรับระบุอุปกรณ์</td></tr>
<tr><td><strong>Random Verification</strong></td><td>Task สุ่มตรวจสอบ</td></tr>
<tr><td><strong>Proof-of-Work</strong></td><td>Challenge Implementation</td></tr>
<tr><td><strong>Benchmark Validation</strong></td><td>ตรวจสอบผลทดสอบ</td></tr>
<tr><td><strong>Anomaly Detection</strong></td><td>ตรวจจับกิจกรรมผิดปกติ</td></tr>
</table>

<h3>API Endpoints</h3>
<h4>Authentication</h4>
<ul>
<li><code>POST /api/register</code> - สร้างบัญชี</li>
<li><code>POST /api/login</code> - เข้าสู่ระบบ</li>
<li><code>GET /api/me</code> - ข้อมูลผู้ใช้</li>
</ul>

<h4>Node Management</h4>
<ul>
<li><code>POST /api/nodes/register</code> - ลงทะเบียน GPU</li>
<li><code>POST /api/nodes/heartbeat</code> - Liveness Signal</li>
<li><code>POST /api/nodes/benchmark</code> - ส่งผลทดสอบ</li>
<li><code>POST /api/nodes/verification</code> - ตอบ Anti-Cheat</li>
</ul>

<h4>Task Distribution</h4>
<ul>
<li><code>GET /api/jobs/work</code> - รับงาน</li>
<li><code>POST /api/jobs/submit</code> - ส่งงานที่เสร็จ</li>
</ul>

<h4>Compensation</h4>
<ul>
<li><code>GET /api/earnings/summary</code> - ดูรายได้</li>
<li><code>POST /api/payouts/request</code> - ขอถอนเงิน</li>
</ul>

<h3>Workflow</h3>
<ol>
<li>ลงทะเบียนและติดตั้ง Windows Client</li>
<li>ลงทะเบียน GPU พร้อม Machine ID</li>
<li>Benchmark วัดประสิทธิภาพ</li>
<li>เริ่มรับงาน Render</li>
<li>ประมวลผลและส่งงาน</li>
<li>สะสม Credit ตามงานที่ทำ</li>
<li>ผ่าน Verification Challenges</li>
<li>ขอถอนเงินเมื่อถึงขั้นต่ำ</li>
</ol>

<h3>Technical Stack</h3>
<table class="min-w-full">
<tr><td>Backend</td><td>Laravel 12 + PHP 8.3</td></tr>
<tr><td>Frontend</td><td>Blade + TailwindCSS</td></tr>
<tr><td>Client</td><td>Python 3.10+ + PyQt6</td></tr>
<tr><td>GPU Libraries</td><td>GPUtil + WMI</td></tr>
</table>

<h3>Use Cases</h3>
<ul>
<li><strong>เกมเมอร์:</strong> สร้างรายได้จากการ์ดจอเมื่อไม่ได้เล่นเกม</li>
<li><strong>นักขุด:</strong> เปลี่ยนจากขุด Crypto มาทำงาน AI</li>
<li><strong>สตูดิโอ:</strong> เข้าถึง GPU Power ราคาประหยัด</li>
</ul>
</div>',
                'features' => [
                    'Workers รับ 90% ของรายได้',
                    'Platform Fee เพียง 10%',
                    'Web Platform (Laravel 12) + Admin Dashboard',
                    'GPU Node Inventory Management',
                    'Intelligent Job Queue Distribution',
                    'Earnings Calculation ตาม GPU Contribution',
                    'Withdrawal/Payout System',
                    'Referral Program',
                    'Windows Client (Python + PyQt6)',
                    'Auto Hardware Detection',
                    'Performance Benchmarking',
                    'Real-time Earnings Tracking',
                    'Anti-Cheat: Hardware Fingerprinting',
                    'Anti-Cheat: Random Verification Tasks',
                    'Anti-Cheat: Proof-of-Work Challenges',
                    'Anti-Cheat: Anomaly Detection',
                ],
                'price' => 0.00,
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

        $this->command->info('Successfully seeded 8 Xman Studio products with comprehensive details!');
    }
}
