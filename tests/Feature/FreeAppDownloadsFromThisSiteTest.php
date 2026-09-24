<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DownloadLog;
use App\Models\GithubSetting;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * AutoTradeX, Chanthra Studio และ Aipray โหลดได้จาก xman4289.com เท่านั้น ลูกค้าต้องไม่รู้ repo (กฎเจ้าของ 2026-09-24)
 *
 *   — ปุ่มโหลดทั้งสามตัว stream ไฟล์จากเว็บเราเอง ไม่ redirect และไม่ลิงก์ไป GitHub
 *   — หน้าลูกค้า หน้าสินค้า และคู่มือไม่มีลิงก์หรือคำว่า GitHub
 *   — หน้า Aipray ไม่ถาม GitHub ทุกครั้งที่มีคนเปิดอีกแล้ว และ GitHub ล่มหน้าก็ยังเปิดได้
 *   — สิ่งที่เจ้าของเลือก (2026-09-24) อยู่ใน migration: AutoTradeX ให้โหลดฟรีตัว portable,
 *     Aipray ส่ง APK universal, Chanthra Studio เป็นสินค้า "เร็ว ๆ นี้" ที่โหลดได้แต่ยังซื้อไม่ได้
 */
class FreeAppDownloadsFromThisSiteTest extends TestCase
{
    use RefreshDatabase;

    private const FILE_BYTES = "PK\x03\x04release-package-from-the-cdn\x00\xfe";

    private const SIGNED_URL = 'https://release-assets.githubusercontent.com/github-production-release-asset/9?X-Amz-Signature=abc';

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->withoutVite();

        // ห้ามคุยกับ GitHub จริง — คำขอที่ไม่ได้ fake ไว้ทำให้เทสต์ล้มทันที
        Http::preventStrayRequests();

        $this->category = Category::firstOrCreate(
            ['slug' => 'software'],
            ['name' => 'Software', 'description' => 'x']
        );
    }

    // ── AutoTradeX ─────────────────────────────────────────────────────

    public function test_anyone_can_download_autotradex_from_this_site(): void
    {
        $version = $this->release($this->autotradex(), 'autotradex', '0.3.0', 'AutoTradeX-v0.3.0-win-x64-portable.zip');
        $this->fakeGithub('autotradex', '0.3.0');

        $response = $this->get('/autotradex/download')
            ->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Type', 'application/octet-stream')
            ->assertHeader('Content-Length', (string) strlen(self::FILE_BYTES));

        $this->assertStringContainsString('AutoTradeX-v0.3.0-win-x64-portable.zip', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::FILE_BYTES, $response->streamedContent());
        $this->assertNoTraceOfGithub($response);
        $this->assertSame($version->id, DownloadLog::sole()->product_version_id);
    }

    public function test_before_autotradex_has_a_release_a_browser_goes_back_to_the_product_page(): void
    {
        // แบบ production ก่อน deploy นี้: มีสินค้าแต่ไม่เคยผูก GitHub เลยไม่มีเวอร์ชัน
        $this->autotradex(withGithub: false);

        $this->get('/autotradex/download')
            ->assertRedirect(route('products.show', 'autotradex'))
            ->assertSessionHas('error');

        $this->get('/autotradex/download', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'error' => 'Version not found']);
    }

    public function test_the_autotradex_customer_pages_link_to_this_site_and_never_to_github(): void
    {
        $user = User::factory()->create();
        $license = $this->licensedTo($user, $this->autotradex());

        foreach ([route('customer.downloads'), route('customer.licenses.show', $license)] as $page) {
            $html = $this->actingAs($user)->get($page)->assertOk()->getContent();

            $this->assertStringContainsString('href="' . route('autotradex.download') . '"', $html, $page);
            $this->assertStringNotContainsStringIgnoringCase('github.com', $html, $page);
        }
    }

    public function test_the_autotradex_page_offers_the_free_download_to_everyone(): void
    {
        $this->release($this->autotradex(), 'autotradex', '0.3.0', 'AutoTradeX-v0.3.0-win-x64-portable.zip');

        $html = $this->get('/products/autotradex')->assertOk()->getContent();

        $this->assertStringContainsString('href="' . route('autotradex.download') . '"', $html);
        $this->assertStringContainsString('v0.3.0', $html);
        $this->assertStringNotContainsStringIgnoringCase('github.com', $html);
    }

    public function test_the_migration_ties_autotradex_to_the_portable_zip_once(): void
    {
        // ฐานเทสต์ไม่มีสินค้า autotradex ตอน migrate — สร้างก่อนแล้วรัน migration เองแบบที่ production จะรัน
        $product = $this->autotradex(withGithub: false);
        $migration = require database_path('migrations/2026_09_24_200000_insert_autotradex_github_setting.php');

        $migration->up();
        $migration->up();

        $setting = GithubSetting::where('product_id', $product->id)->sole();
        $this->assertSame('xjanova/autotradex', $setting->full_repo_name);
        $this->assertSame('AutoTradeX-*-win-x64-portable.zip', $setting->asset_pattern);
        $this->assertTrue($setting->is_active);
        $this->assertTrue($setting->auto_sync);
        $this->assertNull($setting->github_token_decrypted);
    }

    // ── Chanthra Studio ────────────────────────────────────────────────

    public function test_chanthra_studio_is_registered_as_coming_soon_with_its_release_source(): void
    {
        $product = Product::where('slug', 'chanthra-studio')->sole();

        $this->assertTrue($product->is_active);
        $this->assertTrue($product->is_coming_soon);
        $this->assertTrue($product->requires_license);
        $this->assertStringNotContainsStringIgnoringCase('github', $product->description . json_encode($product->features));

        $setting = $product->githubSetting;
        $this->assertSame('xjanova/chanthra-studio', $setting->full_repo_name);
        $this->assertSame('ChanthraStudio-*-win-x64.zip', $setting->asset_pattern);
        $this->assertTrue($setting->auto_sync);
        $this->assertNull($setting->github_token_decrypted);
    }

    public function test_the_chanthra_download_comes_from_this_site(): void
    {
        $product = Product::where('slug', 'chanthra-studio')->sole();
        $this->release($product, 'chanthra-studio', '0.10.0', 'ChanthraStudio-v0.10.0-win-x64.zip');
        $this->fakeGithub('chanthra-studio', '0.10.0');

        $response = $this->get('/chanthra-studio/download')
            ->assertOk()
            ->assertHeaderMissing('Location');

        $this->assertStringContainsString('ChanthraStudio-v0.10.0-win-x64.zip', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::FILE_BYTES, $response->streamedContent());
        $this->assertNoTraceOfGithub($response);
    }

    public function test_before_chanthra_has_a_release_the_button_goes_back_to_its_page(): void
    {
        // ยัง sync ไม่ทัน / GitHub ไม่ให้ release — ต้องไม่พาลูกค้าไป GitHub แทน
        Http::fake(['api.github.com/repos/xjanova/chanthra-studio/releases/latest' => Http::response(['message' => 'Not Found'], 404)]);

        $this->get('/chanthra-studio/download')
            ->assertRedirect(route('chanthra-studio.detail'))
            ->assertSessionHas('error');
    }

    public function test_the_chanthra_pages_link_to_this_site_and_never_mention_github(): void
    {
        foreach (['/chanthra-studio', '/chanthra-studio/manual', '/chanthra-studio/pricing'] as $page) {
            $html = $this->get($page)->assertOk()->getContent();

            $this->assertStringNotContainsStringIgnoringCase('github', $html, $page);

            if ($page !== '/chanthra-studio/pricing') {
                $this->assertStringContainsString('href="' . route('chanthra-studio.download') . '"', $html, $page);
            }
        }
    }

    public function test_the_product_url_the_chanthra_app_opens_lands_on_the_chanthra_page(): void
    {
        // ปุ่ม "ซื้อ key" ในแอปและ release notes ชี้มาที่ /products/chanthra-studio
        $this->get('/products/chanthra-studio')->assertRedirect(route('chanthra-studio.detail'));
    }

    // ── Aipray ─────────────────────────────────────────────────────────

    public function test_aipray_downloads_the_universal_apk_after_the_migration(): void
    {
        $setting = $this->aipray()->githubSetting;

        $this->assertSame('aipray-*-universal.apk', $setting->asset_pattern);
        $this->assertTrue($setting->auto_sync);
    }

    public function test_the_aipray_page_offers_the_apk_from_this_site_without_asking_github_every_visit(): void
    {
        $this->release($this->aipray(), 'Aipray', '1.2.4', 'aipray-1.2.4-universal.apk', changelog: 'บทสวดใหม่ 3 บท');
        $this->fakeGithub('Aipray', '1.2.4');

        foreach ([1, 2] as $visit) {
            $html = $this->get('/apps/aipray')->assertOk()->getContent();

            $this->assertStringContainsString('href="' . route('aipray.download') . '"', $html);
            $this->assertStringContainsString('v1.2.4', $html);
            $this->assertStringContainsString('บทสวดใหม่ 3 บท', $html);
            $this->assertStringNotContainsStringIgnoringCase('github.com', $html);
        }

        // เดิมถาม GitHub API ทุกครั้งที่เปิดหน้า — ตอนนี้เปิดติดกันถามครั้งเดียว (read-through จำไว้ 5 นาที)
        Http::assertSentCount(1);
    }

    public function test_the_aipray_page_still_opens_when_github_is_unreachable(): void
    {
        $this->release($this->aipray(), 'Aipray', '1.2.4', 'aipray-1.2.4-universal.apk');
        Http::fake(['api.github.com/*' => fn () => throw new ConnectionException('Connection timed out')]);

        $this->get('/apps/aipray')
            ->assertOk()
            ->assertSee('v1.2.4')
            ->assertSee('href="' . route('aipray.download') . '"', false);
    }

    public function test_the_aipray_apk_is_streamed_as_an_android_package(): void
    {
        $version = $this->release($this->aipray(), 'Aipray', '1.2.4', 'aipray-1.2.4-universal.apk');
        $this->fakeGithub('Aipray', '1.2.4');

        $response = $this->get('/apps/aipray/download')
            ->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Type', 'application/vnd.android.package-archive');

        $this->assertStringContainsString('aipray-1.2.4-universal.apk', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::FILE_BYTES, $response->streamedContent());
        $this->assertNoTraceOfGithub($response);
        $this->assertSame($version->id, DownloadLog::sole()->product_version_id);
    }

    public function test_head_on_the_aipray_download_answers_without_fetching_the_file(): void
    {
        $this->release($this->aipray(), 'Aipray', '1.2.4', 'aipray-1.2.4-universal.apk', size: 51016873);
        $this->fakeGithub('Aipray', '1.2.4');

        $this->call('HEAD', '/apps/aipray/download')
            ->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Length', '51016873');

        Http::assertNotSent(fn (ClientRequest $request) => str_contains($request->url(), '/releases/download/'));
        $this->assertSame(0, DownloadLog::count());
    }

    // ── ตัวอัปเดตในแอป (update/check) ──────────────────────────────────

    /**
     * ตัวอัปเดตในแอปโหลดเองโดยไม่มี session — ลิงก์ที่ update/check ส่งต้องเป็นหน้าโหลดสาธารณะของเว็บเรา
     * ระบุเวอร์ชันเป๊ะ ๆ คู่กับ sha256 ของเวอร์ชันนั้น (ไม่ใช่ /download/{slug} ที่ต้องล็อกอิน + ซื้อ และไม่ใช่ GitHub)
     */
    public function test_update_check_hands_each_app_its_exact_version_on_this_site(): void
    {
        $apps = [
            ['product' => $this->autotradex(), 'repo' => 'autotradex', 'version' => '0.3.0', 'file' => 'AutoTradeX-v0.3.0-win-x64-portable.zip', 'route' => 'autotradex.download'],
            ['product' => Product::where('slug', 'chanthra-studio')->sole(), 'repo' => 'chanthra-studio', 'version' => '0.10.0', 'file' => 'ChanthraStudio-v0.10.0-win-x64.zip', 'route' => 'chanthra-studio.download'],
            ['product' => $this->aipray(), 'repo' => 'Aipray', 'version' => '1.2.4', 'file' => 'aipray-1.2.4-universal.apk', 'route' => 'aipray.download'],
        ];

        foreach ($apps as $app) {
            $this->release($app['product'], $app['repo'], $app['version'], $app['file']);
            $this->fakeGithub($app['repo'], $app['version']);
        }

        foreach ($apps as $app) {
            $slug = $app['product']->slug;
            $json = $this->getJson("/api/v1/product/{$slug}/update/check?current_version=0.0.1")
                ->assertOk()
                ->assertJsonPath('has_update', true)
                ->assertJsonPath('latest_version', $app['version'])
                ->assertJsonPath('filename', $app['file'])
                ->assertJsonPath('download_url', route($app['route'], ['version' => $app['version']]))
                ->json();

            $this->assertStringNotContainsStringIgnoringCase('github', json_encode($json), $slug);

            // ไม่ล็อกอิน — แบบเดียวกับตัวอัปเดตในแอป
            $response = $this->get($json['download_url'])->assertOk()->assertHeaderMissing('Location');
            $this->assertStringContainsString($app['file'], $response->headers->get('Content-Disposition'), $slug);
            $this->assertSame(self::FILE_BYTES, $response->streamedContent(), $slug);
        }
    }

    public function test_a_versioned_download_is_that_exact_file_even_after_a_newer_release(): void
    {
        $product = Product::where('slug', 'chanthra-studio')->sole();
        // sync ปิดเวอร์ชันเก่าเมื่อตัวใหม่เข้ามา — แอปที่เพิ่งได้ sha256 ของตัวเก่าจาก update/check ต้องยังโหลดตัวนั้นได้
        $this->release($product, 'chanthra-studio', '0.9.0', 'ChanthraStudio-v0.9.0-win-x64.zip')->update(['is_active' => false]);
        $this->release($product, 'chanthra-studio', '0.10.0', 'ChanthraStudio-v0.10.0-win-x64.zip');
        $this->fakeGithub('chanthra-studio', '0.10.0');

        $response = $this->get('/chanthra-studio/download/0.9.0')->assertOk()->assertHeaderMissing('Location');
        $this->assertStringContainsString('ChanthraStudio-v0.9.0-win-x64.zip', $response->headers->get('Content-Disposition'));

        $this->get('/chanthra-studio/download/9.9.9', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'error' => 'Version not found']);
    }

    // ── helpers ───────────────────────────────────────────────────────

    /** สินค้าแบบใน production (id 1) — GitHub setting ค่าเดียวกับที่ migration 2026_09_24_200000 ใส่ให้ */
    private function autotradex(bool $withGithub = true): Product
    {
        $product = Product::updateOrCreate(['slug' => 'autotradex'], [
            'category_id' => $this->category->id,
            'name' => 'AutoTradeX',
            'description' => 'x',
            'price' => 19900,
            'stock' => 999,
            'requires_license' => true,
            'is_active' => true,
        ]);

        if ($withGithub) {
            GithubSetting::updateOrCreate(['product_id' => $product->id], [
                'github_owner' => 'xjanova',
                'github_repo' => 'autotradex',
                'github_token' => '',
                'asset_pattern' => 'AutoTradeX-*-win-x64-portable.zip',
                'is_active' => true,
                'auto_sync' => true,
            ]);
        }

        return $product;
    }

    /** migration 2026_03_25_000008 สร้าง aipray พร้อม GitHub setting (repo public ไม่มี token) ไว้ในฐานเทสต์แล้ว */
    private function aipray(): Product
    {
        return Product::where('slug', 'aipray')->sole();
    }

    /** เวอร์ชันที่ sync มาจาก GitHub (repo public ไม่มี token — แบบเดียวกับทั้งสามตัวใน production) */
    private function release(Product $product, string $repo, string $version, string $filename, int $size = 79780962, ?string $changelog = null): ProductVersion
    {
        return ProductVersion::create([
            'product_id' => $product->id,
            'version' => $version,
            'github_release_id' => 1,
            'github_release_url' => "https://api.github.com/repos/xjanova/{$repo}/releases/assets/9",
            'download_url' => "https://github.com/xjanova/{$repo}/releases/download/v{$version}/{$filename}",
            'download_filename' => $filename,
            'file_size' => $size,
            'changelog' => $changelog,
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
            'license_key' => 'ATX-TEST-KEY-0001',
            'status' => LicenseKey::STATUS_ACTIVE,
            'license_type' => 'lifetime',
        ]);
    }

    /**
     * release ล่าสุดบน GitHub = เวอร์ชันเดียวกับใน DB (read-through ไม่ sync ซ้ำ) · ลิงก์ไฟล์ของ release
     * → 302 ไป CDN → ไฟล์ พร้อม header ของต้นทางที่ห้ามหลุดถึงลูกค้า
     */
    private function fakeGithub(string $repo, string $version): void
    {
        Http::fake([
            "api.github.com/repos/xjanova/{$repo}/releases/latest" => Http::response(['id' => 1, 'tag_name' => "v{$version}", 'assets' => []]),
            "github.com/xjanova/{$repo}/releases/download/*" => Http::response('', 302, ['Location' => self::SIGNED_URL]),
            'release-assets.githubusercontent.com/*' => fn () => Http::response(self::FILE_BYTES, 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => (string) strlen(self::FILE_BYTES),
                'ETag' => '"0x8DCB7C0FFEE"',
                'x-github-request-id' => 'ABCD:1234',
            ]),
        ]);
    }

    private function assertNoTraceOfGithub(TestResponse $response): void
    {
        foreach ($response->headers->all() as $name => $values) {
            foreach ($values as $value) {
                $this->assertStringNotContainsStringIgnoringCase('github', $name . ': ' . $value);
            }
        }

        if (! $response->baseResponse instanceof StreamedResponse) {
            $this->assertStringNotContainsStringIgnoringCase('github', (string) $response->getContent());
        }
    }
}
