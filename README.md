# XMAN Studio

[![Website](https://img.shields.io/badge/Website-xmanstudio.com-blue?style=flat-square&logo=google-chrome)](https://xmanstudio.com)
[![Products](https://img.shields.io/badge/Products-Browse%20All-green?style=flat-square&logo=shopping-bag)](https://xmanstudio.com/products)
[![Services](https://img.shields.io/badge/Services-Web%20%7C%20Mobile%20%7C%20AI-purple?style=flat-square&logo=code)](https://xmanstudio.com/services)

ระบบจัดการธุรกิจครบวงจร สำหรับการขายผลิตภัณฑ์ดิจิทัล, ระบบเช่า, License Management และ Metal-X YouTube Channel

## Products & Services

### Digital Products
- **[SMS Payment Checker](https://xmanstudio.com/products/sms-payment-checker)** - WordPress/WooCommerce plugin สำหรับตรวจสอบการชำระเงินผ่าน SMS อัตโนมัติ (15+ ธนาคารไทย)
- **[Skidrow Killer](https://xmanstudio.com/products/skidrow-killer)** - Anti-malware scanner พร้อม Real-time Protection
- **[AutoTradeX](https://xmanstudio.com/products/autotradex)** - ระบบเทรดอัตโนมัติ
- **[PostXAgent](https://xmanstudio.com/products/postx-agent)** - AI Brand Promotion Manager (9 แพลตฟอร์ม)
- **[SpiderX](https://xmanstudio.com/products/spiderx)** - Decentralized P2P Mesh Network
- **[WinXTools](https://xmanstudio.com/products/winx-tools)** - Windows Network & System Management

### Development Services
- **[Web Development](https://xmanstudio.com/services)** - Landing Page, Corporate Website, E-commerce, Web Apps, Backend/APIs
- **[Mobile Development](https://xmanstudio.com/services)** - Android, iOS, Flutter Cross-Platform
- **[AI & Automation](https://xmanstudio.com/services)** - AI Integration, Chatbot, Data Analysis
- **[Custom Software](https://xmanstudio.com/services)** - Desktop Apps, System Utilities

### Support & Quotation
- **[Support](https://xmanstudio.com/support)** - ติดต่อสอบถามหรือขอใบเสนอราคา

## Tech Stack

- **Framework:** Laravel 11
- **Frontend:** Blade + Tailwind CSS + Vite
- **Database:** MySQL/SQLite
- **Authentication:** Laravel Breeze

## Project Structure

```
app/
├── Http/Controllers/
│   ├── Admin/           # Admin panel controllers
│   │   ├── MetalXVideoController.php      # YouTube video management
│   │   ├── MetalXPlaylistController.php   # Playlist management
│   │   ├── MetalXAnalyticsController.php  # Analytics dashboard
│   │   ├── MetalXTeamController.php       # Team members
│   │   ├── MetalXSettingsController.php   # Channel settings
│   │   ├── ProductController.php          # Product management
│   │   ├── LicenseController.php          # License management
│   │   ├── RentalController.php           # Rental system
│   │   └── ...
│   └── ...              # Frontend controllers
├── Models/
│   ├── MetalXVideo.php      # YouTube video model
│   ├── MetalXPlaylist.php   # Playlist model
│   ├── MetalXTeamMember.php # Team member model
│   ├── Product.php          # Product model
│   ├── LicenseKey.php       # License key model
│   ├── Setting.php          # Dynamic settings
│   └── ...
├── Services/
│   ├── YouTubeService.php           # YouTube Data API v3 integration
│   └── MarketingNotificationService.php  # LINE notifications
└── ...

resources/views/
├── admin/
│   ├── metal-x/
│   │   ├── analytics.blade.php     # Dashboard with stats
│   │   ├── videos/                 # Video management views
│   │   ├── playlists/              # Playlist management views
│   │   ├── settings.blade.php      # Channel settings
│   │   ├── index.blade.php         # Team members list
│   │   ├── create.blade.php        # Add team member
│   │   └── edit.blade.php          # Edit team member
│   ├── products/          # Product management
│   ├── licenses/          # License management
│   ├── rentals/           # Rental system
│   └── ...
├── layouts/
│   ├── admin.blade.php    # Admin layout with sidebar
│   └── app.blade.php      # Frontend layout
└── ...
```

## Main Features

### 1. Product & License Management
- จัดการผลิตภัณฑ์ดิจิทัล (ซอฟต์แวร์, ปลั๊กอิน)
- ระบบ License Key พร้อม Machine ID validation
- GitHub integration สำหรับ version management
- Download tracking

### 2. Rental System
- แพ็กเกจเช่าซอฟต์แวร์รายเดือน/ปี
- การชำระเงินผ่านธนาคาร
- ระบบต่ออายุอัตโนมัติ

### 3. Metal-X YouTube Management
- **Dashboard:** สถิติรวม, Top videos, กราฟตามเดือน
- **Video Management:** นำเข้าจาก YouTube API, sync อัตโนมัติ
- **Playlist Management:** สร้าง/นำเข้าเพลย์ลิสต์
- **Team Members:** จัดการสมาชิกทีม
- **YouTube API Integration:** ดึงข้อมูลวิดีโอ, สถิติ, thumbnails

### 4. LINE Integration
- LINE Login OAuth
- LINE Messaging API (ส่งข้อความหาลูกค้า)
- Marketing notifications

### 5. Admin Settings
- Branding (Logo, Favicon)
- Payment settings (ธนาคาร)
- SEO & Google Search Console
- Google Ads placements
- Custom tracking code
- Ads.txt management

## Development Notes for Claude

### Code Style
- ใช้ **Laravel Pint** สำหรับ code formatting
- รัน `./vendor/bin/pint` ก่อน commit เสมอ
- CI จะ fail ถ้า Pint ไม่ผ่าน

### Database
- Migrations อยู่ใน `database/migrations/`
- ใช้ `php artisan migrate` เพื่อรัน migrations
- Settings เก็บใน `settings` table ผ่าน `Setting::getValue()` / `Setting::setValue()`

### Routes
- Admin routes: `routes/web.php` (prefix: `/admin`, middleware: `auth`, `admin`)
- ทุก admin route ใช้ชื่อ `admin.*`

### Views
- Admin views: `resources/views/admin/`
- Layout: `resources/views/layouts/admin.blade.php`
- ใช้ Tailwind CSS classes

### Key Models & Relationships

```php
// MetalXVideo - YouTube video
MetalXVideo::active()->featured()->latest()->get();
$video->playlists; // belongsToMany
$video->youtube_url; // accessor
$video->formatted_view_count; // accessor

// MetalXPlaylist - Playlist
$playlist->videos; // belongsToMany with position
$playlist->updateVideoCount();

// Setting - Dynamic settings
Setting::getValue('key', 'default');
Setting::setValue('key', 'value', 'type', 'group');
```

### YouTubeService

```php
$youtube = app(YouTubeService::class);

// Check if API configured
$youtube->isConfigured();

// Import single video
$youtube->importVideo('VIDEO_ID');

// Sync all videos from channel
$youtube->syncChannelVideos('CHANNEL_ID', 100);

// Update statistics
$youtube->updateVideoStatistics();

// Import playlist with videos
$youtube->importPlaylist('PLAYLIST_ID');

// Extract IDs from URLs
YouTubeService::extractVideoId($url);
YouTubeService::extractPlaylistId($url);
```

### Admin Menu Structure

เมนูอยู่ใน `resources/views/layouts/admin.blade.php`:
- Analytics Dashboard
- การเช่า (Rentals)
- จัดการเนื้อหา (Services)
- ผลิตภัณฑ์ & โปรแกรม (Products)
- License
- Line OA
- **Metal-X YouTube** (Dashboard, วิดีโอ, เพลย์ลิสต์, สมาชิกทีม, ตั้งค่า)
- ใบสั่งงาน & ราคา (Quotations)
- Support
- การตั้งค่า (Settings)

### Common Patterns

**Adding new admin feature:**
1. สร้าง Migration: `php artisan make:migration create_xxx_table`
2. สร้าง Model: `app/Models/Xxx.php`
3. สร้าง Controller: `app/Http/Controllers/Admin/XxxController.php`
4. เพิ่ม Routes ใน `routes/web.php` (ใน admin group)
5. สร้าง Views: `resources/views/admin/xxx/`
6. เพิ่มเมนูใน `resources/views/layouts/admin.blade.php`
7. รัน `./vendor/bin/pint` แล้ว commit

**Controller imports ที่ต้องเพิ่มใน web.php:**
```php
use App\Http\Controllers\Admin\XxxController;
```

### Recent Changes (Jan 2026)

1. **Metal-X YouTube System** - ระบบจัดการช่อง YouTube ครบวงจร
2. **LINE Login OAuth** - ล็อกอินด้วย LINE
3. **AI Marketing Notifications** - ระบบแจ้งเตือนการตลาด
4. **License Management** - ระบบจัดการ License ที่ละเอียดขึ้น
5. **Product Versions** - GitHub integration สำหรับ releases

## Environment Variables

```env
# YouTube API (สำหรับ Metal-X)
# ตั้งค่าผ่าน Admin > Metal-X > ตั้งค่า Channel

# LINE (สำหรับ LINE Login & Messaging)
LINE_CHANNEL_ID=
LINE_CHANNEL_SECRET=
LINE_MESSAGING_ACCESS_TOKEN=
```

## Commands

```bash
# Install dependencies
composer install
npm install

# Run migrations
php artisan migrate

# Build assets
npm run build

# Development
npm run dev

# Code style check
./vendor/bin/pint --test

# Fix code style
./vendor/bin/pint

# Clear cache
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

## License

MIT License
