<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DownloadLog;
use App\Models\GithubSetting;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Services\GithubReleaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * Thai Prompt POS อัปเดตในแอปจาก xman4289.com แบบเดียวกับ Aipray / Chanthra Studio / WinXTools
 *
 *   — update/check ของ thaiprompt-pos (Android) และ thaiprompt-pos-windows (Windows) ให้ลิงก์สาธารณะระบุเวอร์ชัน
 *     บนโฮสต์ที่แอปถามมา (https://xman4289.com ไม่มีพอร์ต) พร้อม sha256 ขนาด ชื่อไฟล์ — JSON ไม่มีคำว่า github
 *   — ลิงก์นั้นส่งไฟล์จากเว็บเราเอง 200 ไม่ redirect ไม่ต้องล็อกอิน ทุก byte ตรง (สัญญาของตัวอัปเดตในแอป)
 *   — sync เก็บไฟล์ของแต่ละแพลตฟอร์มจาก release เดียวกันถูกตัว พร้อมขนาดและ sha256 จาก digest ของ GitHub
 *     และไม่หยิบไฟล์ของอีกแพลตฟอร์มมาแทนช่วงที่ CI ยังแนบไฟล์ไม่ครบ
 *   — แจกฟรี ไม่ขาย: ไม่ขึ้นหน้าร้าน หน้าสินค้า 404 ใส่ตะกร้าไม่ได้
 */
class ThaipromptPosUpdatesTest extends TestCase
{
    use RefreshDatabase;

    /** แอปถามเว็บนี้ที่โดเมนนี้ — ลิงก์ที่ได้กลับไปต้องอยู่บนโดเมนเดียวกัน (แอปไม่รับโฮสต์อื่น) */
    private const SITE = 'https://xman4289.com';

    private const APK_BYTES = "PK\x03\x04android-package-from-the-cdn\x00\xfe";

    private const ZIP_BYTES = "PK\x03\x04windows-package-from-the-cdn\x00\x01\xff";

    // ขนาดและ digest ของ release v2.0.2 จริง
    private const APK_SIZE = 91111883;

    private const APK_SHA256 = 'fe049a8c0f7cd1a482feeb1b5e8a688a6b5c593fcbe44d9bf380c39a5c581300';

    private const ZIP_SIZE = 24437326;

    private const ZIP_SHA256 = '821977dee787a9cda146f87bf7ba71d7e7970cc90351e61c0657e85173ec153d';

    /** body ของ release จริง (CI เขียนให้อ่านในหน้าต่างอัปเดตของแอป) */
    private const BODY = "Thai Prompt POS v2.0.2\n\n"
        . "• ติดตั้ง: เปิดไฟล์ .apk บนเครื่อง Android แล้วอนุญาตการติดตั้งแอป\n"
        . "• ผู้ใช้เดิม: แอปจะแจ้งอัปเดตและติดตั้งทับได้ทันที ข้อมูลร้านยังอยู่ครบ\n\n"
        . 'xman studio · xman4289.com';

    /** release ล่าสุดที่ "GitHub" ตอบตอนนี้ — เทสต์เปลี่ยนค่านี้ได้ระหว่างทาง (CI แนบไฟล์ทีละแพลตฟอร์ม) */
    private ?array $latestRelease = null;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->withoutVite();

        // ห้ามคุยกับ GitHub จริง — คำขอที่ไม่ได้ fake ไว้ทำให้เทสต์ล้มทันที
        Http::preventStrayRequests();

        Http::fake([
            'api.github.com/repos/xjanova/posthaiprompt/releases/latest' => fn () => $this->latestRelease
                ? Http::response($this->latestRelease)
                : Http::response(['message' => 'Not Found'], 404),
            // ลิงก์ไฟล์ของ release → 302 ไป CDN → ไฟล์ พร้อม header ของต้นทางที่ห้ามหลุดถึงลูกค้า
            'github.com/xjanova/posthaiprompt/releases/download/*.apk' => Http::response('', 302, [
                'Location' => 'https://release-assets.githubusercontent.com/apk/609661907?X-Amz-Signature=abc',
            ]),
            'github.com/xjanova/posthaiprompt/releases/download/*.zip' => Http::response('', 302, [
                'Location' => 'https://release-assets.githubusercontent.com/zip/609659758?X-Amz-Signature=abc',
            ]),
            'release-assets.githubusercontent.com/apk/*' => fn () => $this->cdnFile(self::APK_BYTES, 'application/vnd.android.package-archive'),
            'release-assets.githubusercontent.com/zip/*' => fn () => $this->cdnFile(self::ZIP_BYTES, 'application/zip'),
        ]);
    }

    // ── สินค้าในระบบ ───────────────────────────────────────────────────

    public function test_both_apps_are_registered_free_off_the_shop_and_read_their_own_file_from_one_release(): void
    {
        $expected = [
            'thaiprompt-pos' => ['Thai Prompt POS (Android)', 'posthaiprompt-v*.apk'],
            'thaiprompt-pos-windows' => ['Thai Prompt POS (Windows)', 'posthaiprompt-windows-v*.zip'],
        ];

        foreach ($expected as $slug => [$name, $pattern]) {
            $product = Product::where('slug', $slug)->sole();

            $this->assertSame($name, $product->name);
            $this->assertSame('0.00', $product->price);
            $this->assertFalse($product->is_active, 'ปิดไว้ = ไม่ขึ้นหน้าร้านและตะกร้าไม่รับ');
            $this->assertFalse($product->is_coming_soon);
            $this->assertFalse($product->requires_license, 'แอปไม่ส่ง license มา');
            $this->assertStringNotContainsStringIgnoringCase('github', $product->description . $product->short_description . json_encode($product->features));

            $setting = $product->githubSetting;
            $this->assertSame('xjanova/posthaiprompt', $setting->full_repo_name);
            $this->assertSame($pattern, $setting->asset_pattern);
            $this->assertTrue($setting->is_active);
            $this->assertTrue($setting->auto_sync, 'cron ต้องดึง release ใหม่เข้ามาเอง');
            $this->assertNull($setting->github_token_decrypted, 'repo public — token ที่ตายแล้วแย่กว่าไม่มี');
        }
    }

    public function test_the_migration_runs_again_without_touching_what_is_there(): void
    {
        $setting = Product::where('slug', 'thaiprompt-pos')->sole()->githubSetting;
        // ค่าที่เจ้าของตั้งเองในหน้า admin ต้องไม่ถูกเขียนทับตอน deploy รอบหน้า
        $setting->update(['asset_pattern' => 'posthaiprompt-v*-universal.apk']);

        $migration = require database_path('migrations/2026_10_05_100000_register_thaiprompt_pos_products.php');
        $migration->up();
        $migration->up();

        $this->assertSame(1, Product::where('slug', 'thaiprompt-pos')->count());
        $this->assertSame(1, Product::where('slug', 'thaiprompt-pos-windows')->count());
        $this->assertSame(2, GithubSetting::where('github_repo', 'posthaiprompt')->count());
        $this->assertSame('posthaiprompt-v*-universal.apk', $setting->fresh()->asset_pattern);
        $this->assertSame(1, Category::where('slug', 'e-commerce')->count());
    }

    public function test_the_shop_never_lists_or_sells_them(): void
    {
        $this->get('/products')->assertOk()->assertDontSee('Thai Prompt POS');

        foreach (['thaiprompt-pos', 'thaiprompt-pos-windows'] as $slug) {
            $product = Product::where('slug', $slug)->sole();

            $this->get("/products/{$slug}")->assertNotFound();
            $this->post(route('cart.add', $product))->assertNotFound();
        }
    }

    // ── ตัวอัปเดตในแอป (update/check) ──────────────────────────────────

    public function test_update_check_hands_each_platform_its_own_file_on_this_site(): void
    {
        $this->publishedOnGithub('2.0.2', $this->apkAsset('2.0.2'), $this->zipAsset('2.0.2'));
        $this->version('thaiprompt-pos', '2.0.2', 'posthaiprompt-v2.0.2.apk', self::APK_SIZE, self::APK_SHA256);
        $this->version('thaiprompt-pos-windows', '2.0.2', 'posthaiprompt-windows-v2.0.2.zip', self::ZIP_SIZE, self::ZIP_SHA256);

        $apps = [
            'thaiprompt-pos' => [self::APK_SHA256, self::APK_SIZE, 'posthaiprompt-v2.0.2.apk'],
            'thaiprompt-pos-windows' => [self::ZIP_SHA256, self::ZIP_SIZE, 'posthaiprompt-windows-v2.0.2.zip'],
        ];

        foreach ($apps as $slug => [$sha256, $size, $filename]) {
            // แอปไม่ส่ง session/license/Accept มา — แค่ current_version
            $json = $this->getJson(self::SITE . "/api/v1/product/{$slug}/update/check?current_version=2.0.1")
                ->assertOk()
                ->assertExactJson([
                    'has_update' => true,
                    'latest_version' => '2.0.2',
                    'download_url' => self::SITE . "/apps/{$slug}/download/2.0.2",
                    'changelog' => self::BODY,
                    'sha256' => $sha256,
                    'file_size' => $size,
                    'filename' => $filename,
                ])
                ->json();

            // เงื่อนไขที่ตัวอัปเดตในแอปใช้รับลิงก์: https · โฮสต์ xman4289.com เป๊ะ · ไม่มีพอร์ต ไม่มี user info
            $url = parse_url($json['download_url']);
            $this->assertSame('https', $url['scheme']);
            $this->assertSame('xman4289.com', $url['host']);
            $this->assertArrayNotHasKey('port', $url);
            $this->assertArrayNotHasKey('user', $url);

            $this->assertStringNotContainsStringIgnoringCase('github', json_encode($json), $slug);
        }
    }

    public function test_an_up_to_date_app_gets_no_download_but_still_the_file_details(): void
    {
        $this->publishedOnGithub('2.0.2', $this->apkAsset('2.0.2'), $this->zipAsset('2.0.2'));
        $this->version('thaiprompt-pos', '2.0.2', 'posthaiprompt-v2.0.2.apk', self::APK_SIZE, self::APK_SHA256);

        foreach (['2.0.2', '2.0.10'] as $current) {
            $this->getJson(self::SITE . "/api/v1/product/thaiprompt-pos/update/check?current_version={$current}")
                ->assertOk()
                ->assertJsonPath('has_update', false)
                ->assertJsonPath('latest_version', '2.0.2')
                ->assertJsonPath('download_url', '')
                ->assertJsonPath('sha256', self::APK_SHA256)
                ->assertJsonPath('file_size', self::APK_SIZE);
        }
    }

    public function test_before_the_first_release_the_app_gets_a_clean_answer(): void
    {
        // ยังไม่มี release บน GitHub (404) — ห้าม 500 และห้ามพาไป GitHub
        foreach (['thaiprompt-pos', 'thaiprompt-pos-windows'] as $slug) {
            $this->getJson(self::SITE . "/api/v1/product/{$slug}/update/check?current_version=2.0.1")
                ->assertOk()
                ->assertExactJson([
                    'has_update' => false,
                    'latest_version' => '',
                    'download_url' => '',
                    'changelog' => '',
                    'sha256' => null,
                    'file_size' => null,
                    'filename' => null,
                ]);

            $this->get("/apps/{$slug}/download", ['Accept' => '*/*'])
                ->assertNotFound()
                ->assertExactJson(['success' => false, 'error' => 'Version not found']);

            // เบราว์เซอร์กลับหน้าแรกพร้อมข้อความ (แอปนี้ไม่มีหน้าของตัวเองบนเว็บเรา)
            $this->get("/apps/{$slug}/download", ['Accept' => 'text/html'])
                ->assertRedirect(url('/'))
                ->assertSessionHas('error');
        }
    }

    // ── ไฟล์จากเว็บเราเอง ──────────────────────────────────────────────

    public function test_the_android_app_gets_the_apk_from_this_site_with_no_trace_of_github(): void
    {
        $version = $this->version('thaiprompt-pos', '2.0.2', 'posthaiprompt-v2.0.2.apk', strlen(self::APK_BYTES), self::APK_SHA256);

        $response = $this->get(self::SITE . '/apps/thaiprompt-pos/download/2.0.2')
            ->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Type', 'application/vnd.android.package-archive')
            ->assertHeader('Content-Length', (string) strlen(self::APK_BYTES));

        $this->assertStringContainsString('posthaiprompt-v2.0.2.apk', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::APK_BYTES, $response->streamedContent());
        $this->assertNoTraceOfGithub($response);
        $this->assertSame($version->id, DownloadLog::sole()->product_version_id);

        // ลิงก์ระบุเวอร์ชันไม่ถาม GitHub API (ไม่กินโควตา 60 ครั้ง/ชม.) — ดึงไฟล์จากลิงก์ release ตรง ๆ
        Http::assertNotSent(fn (ClientRequest $request) => str_contains($request->url(), 'api.github.com'));
    }

    public function test_the_windows_app_gets_the_zip_from_this_site_with_no_trace_of_github(): void
    {
        $version = $this->version('thaiprompt-pos-windows', '2.0.2', 'posthaiprompt-windows-v2.0.2.zip', strlen(self::ZIP_BYTES), self::ZIP_SHA256);

        $response = $this->get(self::SITE . '/apps/thaiprompt-pos-windows/download/2.0.2')
            ->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Type', 'application/octet-stream')
            ->assertHeader('Content-Length', (string) strlen(self::ZIP_BYTES));

        $this->assertStringContainsString('posthaiprompt-windows-v2.0.2.zip', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::ZIP_BYTES, $response->streamedContent());
        $this->assertNoTraceOfGithub($response);
        $this->assertSame($version->id, DownloadLog::sole()->product_version_id);
    }

    public function test_a_pinned_version_stays_that_exact_file_after_a_newer_release(): void
    {
        // sync ปิดเวอร์ชันเก่าเมื่อตัวใหม่เข้ามา — แอปที่เพิ่งได้ sha256 ของตัวเก่าจาก update/check ต้องยังโหลดตัวนั้นได้
        $this->version('thaiprompt-pos', '2.0.2', 'posthaiprompt-v2.0.2.apk', strlen(self::APK_BYTES), self::APK_SHA256)
            ->update(['is_active' => false]);
        $this->version('thaiprompt-pos', '2.0.3', 'posthaiprompt-v2.0.3.apk', strlen(self::APK_BYTES), str_repeat('a', 64));
        $this->publishedOnGithub('2.0.3', $this->apkAsset('2.0.3'), $this->zipAsset('2.0.3'));

        $pinned = $this->get('/apps/thaiprompt-pos/download/2.0.2')->assertOk()->assertHeaderMissing('Location');
        $this->assertStringContainsString('posthaiprompt-v2.0.2.apk', $pinned->headers->get('Content-Disposition'));

        // ไม่ระบุเวอร์ชัน = ตัวล่าสุด
        $latest = $this->get('/apps/thaiprompt-pos/download')->assertOk()->assertHeaderMissing('Location');
        $this->assertStringContainsString('posthaiprompt-v2.0.3.apk', $latest->headers->get('Content-Disposition'));

        $this->get('/apps/thaiprompt-pos/download/9.9.9', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'error' => 'Version not found']);
    }

    public function test_head_answers_from_the_database_without_fetching_the_file(): void
    {
        $this->version('thaiprompt-pos', '2.0.2', 'posthaiprompt-v2.0.2.apk', self::APK_SIZE, self::APK_SHA256);

        $this->call('HEAD', '/apps/thaiprompt-pos/download/2.0.2')
            ->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Length', (string) self::APK_SIZE);

        Http::assertNothingSent();
        $this->assertSame(0, DownloadLog::count());
    }

    public function test_both_downloads_are_throttled_and_share_the_download_slots_with_the_other_apps(): void
    {
        foreach (['thaiprompt-pos.download' => 'throttle:30,1,thaiprompt-pos-download', 'thaiprompt-pos-windows.download' => 'throttle:30,1,thaiprompt-pos-windows-download'] as $name => $throttle) {
            // prefix ของตัวเอง — throttle เปล่า ๆ นับรวมกับทุก route ที่ไม่มี prefix
            $this->assertContains($throttle, Route::getRoutes()->getByName($name)->gatherMiddleware());
        }

        $this->version('thaiprompt-pos-windows', '2.0.2', 'posthaiprompt-windows-v2.0.2.zip', strlen(self::ZIP_BYTES), self::ZIP_SHA256);
        config()->set('downloads.max_concurrent_streams', 1);
        // ช่องเดียวที่มีถูกแอปอื่นจองอยู่ (ช่องชุดเดียวกับ ReleaseDownloadStreamer ของทุกแอป)
        $held = Cache::lock('downloads:stream-slot:0', 60);
        $this->assertTrue($held->get());

        $this->get('/apps/thaiprompt-pos-windows/download/2.0.2', ['Accept' => '*/*'])
            ->assertStatus(503)
            ->assertHeader('Retry-After');

        $held->release();
    }

    // ── sync จาก GitHub ────────────────────────────────────────────────

    public function test_the_sync_stores_each_platform_its_own_file_with_its_size_and_sha256(): void
    {
        // แบบ release v2.0.2 จริง — GitHub เรียงไฟล์ตามชื่อ APK มาก่อน zip
        $this->publishedOnGithub('2.0.2', $this->apkAsset('2.0.2'), $this->zipAsset('2.0.2'));

        $this->artisan('products:sync-releases')->assertSuccessful();

        $android = Product::where('slug', 'thaiprompt-pos')->sole()->latestVersion();
        $this->assertSame('2.0.2', $android->version);
        $this->assertSame('posthaiprompt-v2.0.2.apk', $android->download_filename);
        $this->assertSame(self::APK_SIZE, $android->file_size);
        $this->assertSame(self::APK_SHA256, $android->sha256);
        $this->assertSame('https://github.com/xjanova/posthaiprompt/releases/download/v2.0.2/posthaiprompt-v2.0.2.apk', $android->download_url);
        $this->assertSame('https://api.github.com/repos/xjanova/posthaiprompt/releases/assets/609661907', $android->github_release_url);
        $this->assertSame(self::BODY, $android->changelog);

        $windows = Product::where('slug', 'thaiprompt-pos-windows')->sole()->latestVersion();
        $this->assertSame('2.0.2', $windows->version);
        $this->assertSame('posthaiprompt-windows-v2.0.2.zip', $windows->download_filename);
        $this->assertSame(self::ZIP_SIZE, $windows->file_size);
        $this->assertSame(self::ZIP_SHA256, $windows->sha256);
        $this->assertSame('https://github.com/xjanova/posthaiprompt/releases/download/v2.0.2/posthaiprompt-windows-v2.0.2.zip', $windows->download_url);
    }

    public function test_while_one_platform_file_is_still_uploading_the_other_platform_is_not_handed_it(): void
    {
        $this->version('thaiprompt-pos', '2.0.2', 'posthaiprompt-v2.0.2.apk', self::APK_SIZE, self::APK_SHA256);
        $this->version('thaiprompt-pos-windows', '2.0.2', 'posthaiprompt-windows-v2.0.2.zip', self::ZIP_SIZE, self::ZIP_SHA256);

        // CI ของ Windows เสร็จก่อนและสร้าง release ไปแล้ว — APK ยัง build อยู่
        $this->publishedOnGithub('2.0.3', $this->zipAsset('2.0.3'));

        $this->getJson(self::SITE . '/api/v1/product/thaiprompt-pos-windows/update/check?current_version=2.0.2')
            ->assertJsonPath('has_update', true)
            ->assertJsonPath('filename', 'posthaiprompt-windows-v2.0.3.zip');

        // แอป Android ต้องไม่ได้ zip ของ Windows ไปติดตั้ง — ยังได้ 2.0.2 ไปก่อน
        $this->getJson(self::SITE . '/api/v1/product/thaiprompt-pos/update/check?current_version=2.0.2')
            ->assertJsonPath('has_update', false)
            ->assertJsonPath('latest_version', '2.0.2')
            ->assertJsonPath('filename', 'posthaiprompt-v2.0.2.apk');
        $this->assertFalse(ProductVersion::where('version', '2.0.3')->where('download_filename', 'like', '%.apk')->exists());
        $this->assertSame(0, ProductVersion::where('download_filename', 'posthaiprompt-windows-v2.0.3.zip')
            ->whereHas('product', fn ($q) => $q->where('slug', 'thaiprompt-pos'))->count());

        // APK ขึ้นแล้ว — เช็ครอบถัดไป (หลังช่วงจำ 5 นาทีของ read-through) ได้ตัวใหม่เอง ไม่ต้องมีใครกด Sync
        $this->publishedOnGithub('2.0.3', $this->apkAsset('2.0.3'), $this->zipAsset('2.0.3'));
        $this->travel(GithubReleaseService::FRESH_TTL_MINUTES + 1)->minutes();

        $this->getJson(self::SITE . '/api/v1/product/thaiprompt-pos/update/check?current_version=2.0.2')
            ->assertJsonPath('has_update', true)
            ->assertJsonPath('latest_version', '2.0.3')
            ->assertJsonPath('filename', 'posthaiprompt-v2.0.3.apk')
            ->assertJsonPath('download_url', self::SITE . '/apps/thaiprompt-pos/download/2.0.3');
    }

    public function test_a_repo_that_feeds_a_single_product_still_falls_back_to_its_first_file(): void
    {
        // ของเดิมของผลิตภัณฑ์อื่นไม่เปลี่ยน: pattern ไม่ตรงไฟล์ไหน = ใช้ไฟล์แรกของ release เหมือนเดิม
        $product = Product::create([
            'category_id' => Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x'])->id,
            'name' => 'Some Desktop App',
            'slug' => 'some-desktop-app',
            'description' => 'x',
            'price' => 990,
            'is_active' => true,
        ]);
        GithubSetting::create([
            'product_id' => $product->id,
            'github_owner' => 'xjanova',
            'github_repo' => 'some-desktop-app',
            'github_token' => '',
            'asset_pattern' => 'SomeDesktopApp-*-win-x64.zip',
            'is_active' => true,
        ]);
        Http::fake(['api.github.com/repos/xjanova/some-desktop-app/releases/latest' => Http::response([
            'id' => 1,
            'tag_name' => 'v1.0.0',
            'html_url' => 'https://github.com/xjanova/some-desktop-app/releases/tag/v1.0.0',
            'body' => '',
            'assets' => [[
                'id' => 77,
                'name' => 'SomeDesktopApp-Setup.exe',
                'size' => 1234,
                'url' => 'https://api.github.com/repos/xjanova/some-desktop-app/releases/assets/77',
                'browser_download_url' => 'https://github.com/xjanova/some-desktop-app/releases/download/v1.0.0/SomeDesktopApp-Setup.exe',
            ]],
        ])]);

        app(GithubReleaseService::class)->syncLatestRelease($product);

        $this->assertSame('SomeDesktopApp-Setup.exe', $product->latestVersion()->download_filename);
    }

    // ── helpers ───────────────────────────────────────────────────────

    /** เวอร์ชันที่ sync มาแล้ว (ค่าแบบที่ GithubReleaseService เก็บ) */
    private function version(string $slug, string $version, string $filename, int $size, string $sha256): ProductVersion
    {
        $assetId = str_ends_with($filename, '.apk') ? 609661907 : 609659758;

        return ProductVersion::create([
            'product_id' => Product::where('slug', $slug)->value('id'),
            'version' => $version,
            'github_release_id' => 402973851,
            'github_release_url' => "https://api.github.com/repos/xjanova/posthaiprompt/releases/assets/{$assetId}",
            'download_url' => "https://github.com/xjanova/posthaiprompt/releases/download/v{$version}/{$filename}",
            'download_filename' => $filename,
            'file_size' => $size,
            'sha256' => $sha256,
            'changelog' => self::BODY,
            'is_active' => true,
            'synced_at' => now(),
        ]);
    }

    private function publishedOnGithub(string $version, array ...$assets): void
    {
        $this->latestRelease = [
            'id' => 402973851,
            'tag_name' => "v{$version}",
            'html_url' => "https://github.com/xjanova/posthaiprompt/releases/tag/v{$version}",
            'body' => self::BODY,
            'assets' => $assets,
        ];
    }

    private function apkAsset(string $version): array
    {
        return $this->asset(609661907, "posthaiprompt-v{$version}.apk", self::APK_SIZE, self::APK_SHA256, $version);
    }

    private function zipAsset(string $version): array
    {
        return $this->asset(609659758, "posthaiprompt-windows-v{$version}.zip", self::ZIP_SIZE, self::ZIP_SHA256, $version);
    }

    private function asset(int $id, string $name, int $size, string $sha256, string $version): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'size' => $size,
            'digest' => 'sha256:' . $sha256,
            'url' => "https://api.github.com/repos/xjanova/posthaiprompt/releases/assets/{$id}",
            'browser_download_url' => "https://github.com/xjanova/posthaiprompt/releases/download/v{$version}/{$name}",
        ];
    }

    private function cdnFile(string $bytes, string $contentType)
    {
        return Http::response($bytes, 200, [
            'Content-Type' => $contentType,
            'Content-Length' => (string) strlen($bytes),
            'ETag' => '"0x8DCB7C0FFEE"',
            'x-github-request-id' => 'ABCD:1234',
        ]);
    }

    /** ลูกค้าต้องไม่เห็นคำว่า github ใน header ไหนเลย ทั้งชื่อและค่า (Location, x-github-*, ลิงก์ที่เซ็นแล้ว) */
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
