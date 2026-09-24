<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GithubSetting;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\RentalPackage;
use App\Models\User;
use App\Models\UserRental;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ศูนย์ดาวน์โหลดของลูกค้า (my-account/downloads) — การ์ดของสินค้าที่ลูกค้ามี license ต้องกดโหลดได้จริง
 *
 *   — เดิมมีแต่ AutoTradeX ที่เป็นลิงก์ ตัวอื่น (CluadeX, WinXTools, GPUxMINE, ...) เป็น <button> ที่กดแล้วไม่เกิดอะไร
 *   — แต่ละแอปไปที่ route ดาวน์โหลดของมันเองบน xman4289.com (config/downloads.php → app_routes)
 *   — สินค้าที่ไม่มี route ของตัวเองไปหน้า /download/{slug} เมื่อมีเวอร์ชันให้โหลด ไม่มีก็ไม่แสดงปุ่ม
 *   — ทั้งหน้าไม่มี github.com (กฎเจ้าของ 2026-09-24) ทั้งที่ลิงก์ของเวอร์ชันใน DB ชี้ไป GitHub ทุกตัว
 *   — เวอร์ชันบนการ์ดคือตัวที่เว็บนี้ส่งให้ (Product::latestVersion()) อ่านจาก DB อย่างเดียว เปิดหน้านี้ต้องไม่ถาม GitHub
 *   — แพลตฟอร์มมาจาก config/downloads.php → app_platforms · ไม่รู้ก็ไม่แสดงแถว
 *     (เดิมทุกการ์ดขึ้น 1.0.0 / Windows ตายตัว แม้แต่แอป Android)
 *   — การ์ดแพ็กเกจเช่าไม่มีปุ่มที่กดแล้วไม่ไปไหน (เอกสาร / API Keys)
 */
class CustomerDownloadCenterTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->withoutVite();

        // เปิดหน้านี้ต้องไม่ถาม GitHub — คำขอที่ไม่ได้ fake ไว้ทำให้หน้าพังและเทสต์ล้มทันที
        Http::preventStrayRequests();

        $this->category = Category::firstOrCreate(
            ['slug' => 'software'],
            ['name' => 'Software', 'description' => 'x']
        );
    }

    /**
     * แอปที่มี route ดาวน์โหลดของตัวเอง → ที่ปุ่มต้องพาไป
     *
     * เขียนเป็น path ตรง ๆ ไม่ใช่ชื่อ route: ชื่อที่พิมพ์ผิดใน config ถูกข้ามไปเงียบ ๆ (Route::has) จึงต้องมีที่จับได้
     */
    public static function appDownloads(): array
    {
        return [
            'AutoTradeX' => ['autotradex', '/autotradex/download'],
            'Chanthra Studio' => ['chanthra-studio', '/chanthra-studio/download'],
            'CluadeX' => ['cluadex-ai-coding-assistant', '/cluadex/download'],
            'GPUxMINE' => ['gpuxmine', '/gpuxmine/download'],
            'WinXTools' => ['winx-tools', '/winx-tools/download'],
            'Aipray' => ['aipray', '/apps/aipray/download'],
            'LocalVPN' => ['localvpn', '/localvpn/download/apk'],
            'SMS Checker' => ['smschecker', '/smschecker/download/apk'],
            'Tping' => ['tping', '/tping/download/apk'],
        ];
    }

    /** @dataProvider appDownloads */
    public function test_an_app_card_links_to_that_apps_own_download_on_this_site(string $slug, string $path): void
    {
        $user = User::factory()->create();
        $this->licensedTo($user, $this->productWithRelease($slug));

        $card = $this->licensedSoftware($user);

        $this->assertSame(1, substr_count($card, 'href="' . url($path) . '"'));
        $this->assertStringNotContainsString('<button', $card);
    }

    public function test_every_card_on_one_account_gets_its_own_link(): void
    {
        $user = User::factory()->create();

        foreach (self::appDownloads() as [$slug]) {
            $this->licensedTo($user, $this->productWithRelease($slug));
        }
        // ตัวที่ไม่มีอะไรให้โหลดอยู่ปนด้วย — ลิงก์ของการ์ดใบก่อนต้องไม่ติดมาที่ใบนี้
        $this->licensedTo($user, $this->product('spiderx'));

        $section = $this->licensedSoftware($user);

        foreach (self::appDownloads() as $name => [$slug, $path]) {
            $this->assertSame(1, substr_count($section, 'href="' . url($path) . '"'), $name);
        }
        // ปุ่มโหลดการ์ดละหนึ่ง + คู่มือของ AutoTradeX · การ์ดที่ไม่มีอะไรให้โหลดไม่มีลิงก์เลย
        $this->assertSame(count(self::appDownloads()) + 1, substr_count($section, '<a '));
        $this->assertStringNotContainsString('<button', $section);
    }

    public function test_brainx_gets_its_own_download_once_this_site_serves_one(): void
    {
        $user = User::factory()->create();
        $this->licensedTo($user, $this->product('brainx'));

        $card = $this->licensedSoftware($user);

        // /brainx/download มากับ PR #427 — ก่อนนั้นยังไม่มีอะไรของ BrainX ให้โหลดจากเว็บนี้ การ์ดจึงไม่มีปุ่ม
        if (Route::has('brainx.download')) {
            $this->assertSame(1, substr_count($card, 'href="' . url('/brainx/download') . '"'));
        } else {
            $this->assertStringNotContainsString('<a ', $card);
        }
        $this->assertStringNotContainsString('<button', $card);
    }

    public function test_a_product_without_a_route_of_its_own_goes_to_the_download_page_when_it_has_a_version(): void
    {
        $user = User::factory()->create();
        $this->licensedTo($user, $this->productWithRelease('spiderx'));

        $card = $this->licensedSoftware($user);

        $this->assertSame(1, substr_count($card, 'href="' . route('download.page', 'spiderx') . '"'));
        $this->assertStringNotContainsString('<button', $card);

        // และหน้านั้นเปิดได้จริงสำหรับคนที่ซื้อแล้ว ไม่ใช่ปลายทางตันอีกแบบ
        $this->actingAs($user)->get(route('download.page', 'spiderx'))->assertOk();
    }

    public function test_a_product_with_nothing_to_download_shows_no_button(): void
    {
        $user = User::factory()->create();
        $product = $this->product('spiderx');
        // เวอร์ชันที่ถูกปิดไว้ไม่นับ — หน้า /download/{slug} ก็ตอบ 404 กับมัน
        $this->release($product, '1.0.0')->update(['is_active' => false]);
        $this->licensedTo($user, $product);

        $card = $this->licensedSoftware($user);

        $this->assertStringContainsString($product->name, $card);
        $this->assertStringNotContainsString('<a ', $card);
        $this->assertStringNotContainsString('<button', $card);
    }

    public function test_a_route_this_site_does_not_register_falls_back_instead_of_breaking_the_page(): void
    {
        config()->set('downloads.app_routes.spiderx', 'spiderx.download');

        $user = User::factory()->create();
        $this->licensedTo($user, $this->productWithRelease('spiderx'));

        $card = $this->licensedSoftware($user);

        $this->assertSame(1, substr_count($card, 'href="' . route('download.page', 'spiderx') . '"'));
    }

    /** แอปที่มีปุ่มโหลดของตัวเอง → ระบบที่ไฟล์นั้นใช้ได้ (config/downloads.php → app_platforms) */
    public static function appPlatforms(): array
    {
        return [
            'AutoTradeX' => ['autotradex', 'Windows'],
            'BrainX' => ['brainx', 'Windows'],
            'Chanthra Studio' => ['chanthra-studio', 'Windows'],
            'CluadeX' => ['cluadex-ai-coding-assistant', 'Windows'],
            'GPUxMINE' => ['gpuxmine', 'Windows'],
            'WinXTools' => ['winx-tools', 'Windows'],
            'Aipray' => ['aipray', 'Android'],
            'LocalVPN' => ['localvpn', 'Android'],
            'SMS Checker' => ['smschecker', 'Android'],
            'Tping' => ['tping', 'Android'],
        ];
    }

    /** @dataProvider appPlatforms */
    public function test_an_app_card_shows_its_real_version_and_what_it_runs_on(string $slug, string $platform): void
    {
        $user = User::factory()->create();
        $product = $this->product($slug);
        $this->release($product, '2.3.4');
        $this->licensedTo($user, $product);

        $card = $this->licensedSoftware($user);

        $this->assertSame('2.3.4', $this->cardRow($card, 'Version'));
        $this->assertSame($platform, $this->cardRow($card, 'Platform'));
    }

    public function test_the_version_shown_is_the_active_one_added_last_never_a_text_sort(): void
    {
        $user = User::factory()->create();
        $product = $this->product('winx-tools');

        // สองตัวเปิดพร้อมกันได้เมื่อแอดมินกดเปิดตัวเก่ากลับมา — ตัวที่เพิ่มทีหลังชนะ
        // เรียงเป็นข้อความจะได้ "1.2.99" เพราะ "9" > "1" ทั้งที่ 1.2.102 ใหม่กว่า
        $this->travel(-2)->hours();
        $this->release($product, '1.2.99');
        $this->travelBack();
        $this->release($product, '1.2.102');

        // ตัวที่ปิดไว้ไม่นับ ต่อให้เพิ่มมาทีหลังสุด
        $this->travel(1)->hours();
        $this->release($product, '1.3.0')->update(['is_active' => false]);
        $this->travelBack();

        $this->licensedTo($user, $product);

        $card = $this->licensedSoftware($user);

        $this->assertSame('1.2.102', $this->cardRow($card, 'Version'));
        // ตัวเดียวกับที่ /winx-tools/download และ /download/{slug} ส่งให้
        $this->assertSame($product->latestVersion()->version, $this->cardRow($card, 'Version'));
    }

    public function test_opening_the_page_never_asks_github_for_a_newer_release(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $product = $this->product('winx-tools');
        $this->release($product, '1.0.1');
        // GitHub setting ที่เปิดอยู่ = GithubReleaseService::latestVersionFresh() จะยิงไปถาม GitHub ทันที
        GithubSetting::create([
            'product_id' => $product->id,
            'github_owner' => 'xjanova',
            'github_repo' => 'winxtools',
            'github_token' => '',
            'asset_pattern' => 'WinXTools-*-win-x64.zip',
            'is_active' => true,
            'auto_sync' => true,
        ]);
        $this->licensedTo($user, $product);

        $card = $this->licensedSoftware($user);

        $this->assertSame('1.0.1', $this->cardRow($card, 'Version'));
        Http::assertNothingSent();
    }

    public static function cardsThatKnowLess(): array
    {
        return [
            // ไม่มีในรายการแพลตฟอร์ม — เดิมขึ้น Windows ให้ทุกตัว
            'a version, platform unknown' => ['spiderx', '2.3.4', null],
            // AutoTradeX ก่อน sync ครั้งแรก — บน production ยังไม่มีแถวเวอร์ชันเลย (2026-09-24)
            'platform known, no version yet' => ['autotradex', null, 'Windows'],
            'neither' => ['spiderx', null, null],
        ];
    }

    /** @dataProvider cardsThatKnowLess */
    public function test_a_card_leaves_out_what_this_site_does_not_know(string $slug, ?string $version, ?string $platform): void
    {
        $user = User::factory()->create();
        $product = $this->product($slug);
        if ($version !== null) {
            $this->release($product, $version);
        }
        $this->licensedTo($user, $product);

        $card = $this->licensedSoftware($user);

        $this->assertSame($version, $this->cardRow($card, 'Version'));
        $this->assertSame($platform, $this->cardRow($card, 'Platform'));
        // ไม่รู้ค่า = ไม่มีทั้งแถว ไม่ใช่ป้ายเปล่า ๆ หรือค่าที่เดาเอา (เดิมทุกการ์ดขึ้น 1.0.0 / Windows)
        $this->assertSame($version !== null, str_contains($card, '>Version<'));
        $this->assertSame($platform !== null, str_contains($card, '>Platform<'));
    }

    public function test_a_subscription_has_no_buttons_that_lead_nowhere(): void
    {
        $user = User::factory()->create();
        $package = RentalPackage::create([
            'name' => 'Professional',
            'name_th' => 'แพ็กเกจมืออาชีพ',
            'price' => 2490,
            'duration_type' => 'monthly',
            'duration_value' => 1,
            'is_active' => true,
        ]);
        UserRental::create([
            'user_id' => $user->id,
            'rental_package_id' => $package->id,
            'status' => UserRental::STATUS_ACTIVE,
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'amount_paid' => 2490,
        ]);

        $section = $this->subscriptionResources($user);

        $this->assertStringContainsString(e($package->display_name), $section);
        // เอกสาร / API Keys เคยเป็น <button> ที่กดแล้วไม่เกิดอะไร — แพ็กเกจเช่ายังไม่มีเอกสาร
        // และเว็บนี้ไม่มีระบบ API key ให้ลูกค้า จึงไม่มีที่ให้ลิงก์ไป
        $this->assertStringNotContainsString('<button', $section);
        $this->assertStringNotContainsString('>Documentation<', $section);
        $this->assertStringNotContainsString('API Keys', $section);
    }

    // ── helpers ───────────────────────────────────────────────────────

    /** ส่วน "ซอฟต์แวร์ที่มีใบอนุญาต" ของหน้า */
    private function licensedSoftware(User $user): string
    {
        return $this->pageSection($user, '<!-- Licensed Products -->', '<!-- Subscription Products -->');
    }

    /** ส่วน "ทรัพยากรจากการสมัครสมาชิก" (แพ็กเกจเช่า) ของหน้า */
    private function subscriptionResources(User $user): string
    {
        return $this->pageSection($user, '<!-- Subscription Products -->', '<!-- No Downloads Available -->');
    }

    /** ส่วนหนึ่งของหน้า — ทั้งหน้าต้องไม่มี github.com */
    private function pageSection(User $user, string $from, string $to): string
    {
        $html = $this->actingAs($user)->get(route('customer.downloads'))->assertOk()->getContent();

        $this->assertStringNotContainsStringIgnoringCase('github.com', $html);
        $this->assertStringContainsString($from, $html);
        $this->assertStringContainsString($to, $html);

        return Str::between($html, $from, $to);
    }

    /**
     * ค่าในแถว "เวอร์ชัน / Version" หรือ "แพลตฟอร์ม / Platform" ของการ์ด — null เมื่อการ์ดไม่มีแถวนั้น
     *
     * ป้ายของแถวคือ x-bi แบบ inline ที่ปิดท้ายด้วย …>Version</span></span> ขึ้นบรรทัดใหม่ แล้วตามด้วย ":" และ span ของค่า
     */
    private function cardRow(string $card, string $label): ?string
    {
        return preg_match('#>' . preg_quote($label, '#') . '</span></span>\s*:</span>\s*<span[^>]*>([^<]*)</span>#', $card, $m) === 1
            ? $m[1]
            : null;
    }

    /** ใช้แถวที่ migration สร้างไว้แล้วถ้ามี (Aipray, Tping, GPUxMINE, BrainX, ...) */
    private function product(string $slug): Product
    {
        return Product::firstOrCreate(['slug' => $slug], [
            'category_id' => $this->category->id,
            'name' => Str::headline($slug),
            'description' => 'x',
            'price' => 990,
            'stock' => 999,
            'requires_license' => true,
            'is_active' => true,
        ]);
    }

    private function productWithRelease(string $slug): Product
    {
        $product = $this->product($slug);
        $this->release($product, '1.0.0');

        return $product;
    }

    /** เวอร์ชันที่ sync มาจาก GitHub — ลิงก์ใน DB ชี้ไป GitHub เสมอ หน้าลูกค้าต้องไม่ยื่นให้ใครเห็น */
    private function release(Product $product, string $version): ProductVersion
    {
        return ProductVersion::create([
            'product_id' => $product->id,
            'version' => $version,
            'github_release_id' => 1,
            'github_release_url' => "https://api.github.com/repos/xjanova/{$product->slug}/releases/assets/9",
            'download_url' => "https://github.com/xjanova/{$product->slug}/releases/download/v{$version}/{$product->slug}.zip",
            'download_filename' => "{$product->slug}.zip",
            'file_size' => 1024,
            'is_active' => true,
            'synced_at' => now(),
        ]);
    }

    private function licensedTo(User $user, Product $product): LicenseKey
    {
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(),
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '0800000000',
            'subtotal' => $product->price,
            'total' => $product->price,
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'subtotal' => $product->price,
        ]);

        return LicenseKey::create([
            'product_id' => $product->id,
            'order_id' => $order->id,
            'user_id' => $user->id,
            'license_key' => LicenseKey::generateKey(),
            'status' => LicenseKey::STATUS_ACTIVE,
            'license_type' => 'yearly',
            'expires_at' => now()->addYear(),
        ]);
    }
}
