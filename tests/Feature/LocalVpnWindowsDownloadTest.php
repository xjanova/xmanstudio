<?php

namespace Tests\Feature;

use App\Models\DownloadLog;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Services\GithubReleaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * LocalVPN บน Windows (2026-10-11) — release v{X} ของแอปมีสามไฟล์: LocalVPN-v{X}.apk, LocalVPN-Setup-v{X}.exe (Inno Setup)
 * และ LocalVPN-windows-v{X}.zip
 *
 *   — update/check?platform=windows ชี้ไปที่ /localvpn/download/windows/{X} พร้อม sha256/ขนาด/ชื่อของตัวติดตั้ง
 *     (แอปรันตัวติดตั้งได้เฉพาะลิงก์ที่ลงท้าย .exe หรือมี /download/windows — นอกนั้นเปิดหน้าโหลดในเบราว์เซอร์แทน)
 *   — ไม่ส่ง platform = APK เหมือนเดิมทุกอย่าง
 *   — route ส่งตัวติดตั้งผ่าน ReleaseDownloadStreamer จากเว็บเราเอง ไม่ redirect ไม่มีร่องรอย GitHub (กฎเจ้าของ 2026-09-24)
 *   — release ที่ยังไม่มีตัวติดตั้ง = Windows ยังไม่มีอะไรให้อัปเดต · ไฟล์ที่ขึ้นตามมาทีหลังถูกเก็บในการถาม GitHub รอบถัดไป
 */
class LocalVpnWindowsDownloadTest extends TestCase
{
    use RefreshDatabase;

    private const APK_BYTES = "PK\x03\x04android-package-from-the-cdn\x00\xfe";

    private const EXE_BYTES = "MZ\x90\x00inno-setup-installer-from-the-cdn\x00\xff";

    private const ZIP_BYTES = "PK\x03\x04portable-windows-build\x00";

    private const SIGNED_URL = 'https://release-assets.githubusercontent.com/github-production-release-asset/9/%s?X-Amz-Signature=abc';

    /** release ล่าสุดที่ GitHub ตอบตอนนี้ — เปลี่ยนกลางเทสต์ได้ (stub ของ Http::fake ตัวแรกชนะเสมอ ลงทะเบียนซ้ำไม่ได้) */
    private ?array $latest = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // ห้ามคุยกับ GitHub จริง — คำขอที่ไม่ได้ fake ไว้ทำให้เทสต์ล้มทันที
        Http::preventStrayRequests();
    }

    // ── update/check ──────────────────────────────────────────────────

    public function test_windows_is_pointed_at_the_installer_with_its_own_sha256(): void
    {
        // DB รู้แค่ 1.0.39 · GitHub มี 1.0.40 ครบสามไฟล์ — update/check ดึงเข้ามาเองแล้วตอบด้วยตัวติดตั้ง ไม่ใช่ APK
        $this->release('1.0.39');
        $this->fakeGithub($this->githubRelease('1.0.40'));

        $update = $this->getJson('/api/v1/product/localvpn/update/check?current_version=1.0.39&platform=windows')
            ->assertOk()
            ->assertJsonPath('has_update', true)
            ->assertJsonPath('latest_version', '1.0.40')
            ->assertJsonPath('download_url', route('localvpn.download.windows', ['version' => '1.0.40']))
            ->assertJsonPath('filename', 'LocalVPN-Setup-v1.0.40.exe')
            ->assertJsonPath('file_size', strlen(self::EXE_BYTES))
            ->assertJsonPath('sha256', hash('sha256', self::EXE_BYTES))
            ->json();

        $this->assertStringNotContainsStringIgnoringCase('github', json_encode($update));
        // แอปรันได้เฉพาะลิงก์ที่ลงท้าย .exe หรือมี /download/windows (update_service.dart canInstallInApp)
        $this->assertStringContainsString('/download/windows', $update['download_url']);

        // ตัวอัปเดตในแอปโหลดเองโดยไม่มี session และไม่ส่ง Accept แบบเบราว์เซอร์ — ได้ไฟล์ตรงกับที่ประกาศทุก byte
        $response = $this->get($update['download_url'], ['Accept' => '*/*'])
            ->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Type', 'application/octet-stream');

        $this->assertStringContainsString($update['filename'], $response->headers->get('Content-Disposition'));
        $this->assertSame((string) $update['file_size'], $response->headers->get('Content-Length'));
        $this->assertSame($update['sha256'], hash('sha256', $response->streamedContent()));
        $this->assertNoTraceOfGithub($response);
    }

    public function test_without_a_platform_the_update_is_still_the_apk(): void
    {
        $this->release('1.0.39');
        $this->fakeGithub($this->githubRelease('1.0.40'));

        foreach (['', '&platform=android'] as $platform) {
            $this->getJson("/api/v1/product/localvpn/update/check?current_version=1.0.39{$platform}")
                ->assertOk()
                ->assertJsonPath('has_update', true)
                ->assertJsonPath('latest_version', '1.0.40')
                ->assertJsonPath('download_url', route('localvpn.download.apk'))
                ->assertJsonPath('filename', 'LocalVPN-v1.0.40.apk')
                ->assertJsonPath('file_size', strlen(self::APK_BYTES))
                ->assertJsonPath('sha256', hash('sha256', self::APK_BYTES));
        }

        $response = $this->get(route('localvpn.download.apk'), ['Accept' => '*/*'])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.android.package-archive');

        $this->assertSame(self::APK_BYTES, $response->streamedContent());
    }

    public function test_the_windows_platform_only_changes_localvpn(): void
    {
        // แอปอื่นไม่มีตัวติดตั้งแยก — platform=windows ต้องไม่เปลี่ยนคำตอบของมัน
        $tping = Product::where('slug', 'tping')->sole();
        ProductVersion::create([
            'product_id' => $tping->id,
            'version' => '1.2.102',
            'download_filename' => 'Tping-v1.2.102.apk',
            'file_size' => 100,
            'sha256' => str_repeat('a', 64),
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/product/tping/update/check?current_version=1.0.0&platform=windows')
            ->assertOk()
            ->assertJsonPath('has_update', true)
            ->assertJsonPath('download_url', route('tping.download.apk'))
            ->assertJsonPath('filename', 'Tping-v1.2.102.apk')
            ->assertJsonPath('sha256', str_repeat('a', 64));
    }

    public function test_windows_hears_of_no_update_until_the_release_carries_an_installer(): void
    {
        // release ถูกสร้างแล้วแต่ตัวติดตั้งยังอัปโหลดไม่เสร็จ (state "open") — ห้ามประกาศ APK ให้ Windows
        $this->release('1.0.39');
        $this->fakeGithub($this->githubRelease('1.0.40', exeState: 'open'));

        $this->getJson('/api/v1/product/localvpn/update/check?current_version=1.0.39&platform=windows')
            ->assertOk()
            ->assertJsonPath('has_update', false)
            ->assertJsonPath('latest_version', '1.0.40')
            ->assertJsonPath('download_url', '')
            ->assertJsonPath('sha256', null)
            ->assertJsonPath('file_size', null)
            ->assertJsonPath('filename', null);

        // Android ไม่ได้รับผลกระทบ
        $this->getJson('/api/v1/product/localvpn/update/check?current_version=1.0.39')
            ->assertJsonPath('has_update', true)
            ->assertJsonPath('filename', 'LocalVPN-v1.0.40.apk');
    }

    public function test_an_installer_that_lands_after_the_first_sync_is_picked_up_on_the_next_github_check(): void
    {
        // sync รอบแรกตอนตัวติดตั้งยังขึ้นไม่เสร็จ — tag เดิมไม่ใช่เหตุให้ข้าม ไฟล์ที่ขึ้นครบแล้วต้องถูกเก็บในรอบถัดไป
        $this->fakeGithub($this->githubRelease('1.0.40', exeState: 'open'));
        $this->getJson('/api/v1/product/localvpn/update/check?current_version=1.0.39&platform=windows')
            ->assertJsonPath('has_update', false);

        Cache::forget(app(GithubReleaseService::class)->freshCacheKey($this->localvpn()));
        $this->latest = $this->githubRelease('1.0.40');

        $this->getJson('/api/v1/product/localvpn/update/check?current_version=1.0.39&platform=windows')
            ->assertJsonPath('has_update', true)
            ->assertJsonPath('download_url', route('localvpn.download.windows', ['version' => '1.0.40']))
            ->assertJsonPath('sha256', hash('sha256', self::EXE_BYTES));

        $this->assertSame(1, ProductVersion::where('product_id', $this->localvpn()->id)->count());
    }

    public function test_a_version_synced_before_files_were_recorded_learns_them_from_github(): void
    {
        // แถวใน production ที่ sync ไว้ก่อนมีคอลัมน์ assets (null) — ถาม GitHub รอบถัดไปได้รายการไฟล์ ไม่ต้องรอ release ใหม่
        $this->release('1.0.40', assets: null);
        $this->fakeGithub($this->githubRelease('1.0.40'));

        $this->getJson('/api/v1/product/localvpn/update/check?current_version=1.0.39&platform=windows')
            ->assertOk()
            ->assertJsonPath('has_update', true)
            ->assertJsonPath('filename', 'LocalVPN-Setup-v1.0.40.exe')
            ->assertJsonPath('sha256', hash('sha256', self::EXE_BYTES));
    }

    // ── /localvpn/download/windows ────────────────────────────────────

    public function test_the_windows_route_streams_the_installer_of_the_active_version(): void
    {
        $version = $this->release('1.0.40');
        $this->fakeGithub();

        $response = $this->get('/localvpn/download/windows')
            ->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Type', 'application/octet-stream')
            ->assertHeader('Content-Length', (string) strlen(self::EXE_BYTES));

        $this->assertStringContainsString('LocalVPN-Setup-v1.0.40.exe', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::EXE_BYTES, $response->streamedContent());
        $this->assertNoTraceOfGithub($response);
        $this->assertSame($version->id, DownloadLog::sole()->product_version_id);

        // repo public ไม่มี token — โหลดจากลิงก์ไฟล์ของ release ตัวติดตั้ง ไม่ใช่ APK
        Http::assertSent(fn (ClientRequest $request) => str_contains($request->url(), 'github.com/xjanova/localvpn/releases/download/v1.0.40/LocalVPN-Setup-v1.0.40.exe'));
        Http::assertNotSent(fn (ClientRequest $request) => str_ends_with($request->url(), '.apk'));
        Http::assertNotSent(fn (ClientRequest $request) => $request->hasHeader('Authorization'));

        // แถวของเวอร์ชันยังเป็น APK — การส่งตัวติดตั้งไม่เขียนอะไรทับ
        $this->assertSame('LocalVPN-v1.0.40.apk', $version->fresh()->download_filename);
    }

    public function test_a_pinned_version_gets_exactly_that_installer(): void
    {
        // update/check ประกาศ sha256 ของ 1.0.39 ไปแล้ว — ระหว่างนั้น 1.0.40 ออก ลิงก์เดิมต้องยังได้ไฟล์ 1.0.39
        $this->release('1.0.39', active: false);
        $this->release('1.0.40');
        $this->fakeGithub();

        $response = $this->get('/localvpn/download/windows/1.0.39', ['Accept' => '*/*'])->assertOk();

        $this->assertStringContainsString('LocalVPN-Setup-v1.0.39.exe', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::EXE_BYTES, $response->streamedContent());
        Http::assertSent(fn (ClientRequest $request) => str_contains($request->url(), '/releases/download/v1.0.39/LocalVPN-Setup-v1.0.39.exe'));
    }

    public function test_head_answers_from_what_was_recorded_without_fetching_or_counting(): void
    {
        $this->release('1.0.40');

        $this->call('HEAD', '/localvpn/download/windows/1.0.40', [], [], [], ['HTTP_ACCEPT' => '*/*'])
            ->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Type', 'application/octet-stream')
            ->assertHeader('Content-Length', (string) strlen(self::EXE_BYTES));

        $this->assertSame(0, DownloadLog::count());
    }

    public function test_without_an_installer_the_app_gets_an_error_and_a_browser_goes_back_to_the_download_page(): void
    {
        $this->release('1.0.40', assets: [$this->asset('LocalVPN-v1.0.40.apk', self::APK_BYTES, '1.0.40')]);

        $this->get('/localvpn/download/windows', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'error' => 'Version not found']);

        $this->get('/localvpn/download/windows')
            ->assertRedirect(route('localvpn.download'))
            ->assertSessionHas('error');

        // เวอร์ชันที่ไม่มีอยู่จริง
        $this->get('/localvpn/download/windows/9.9.9', ['Accept' => '*/*'])->assertNotFound();

        $this->assertSame(0, DownloadLog::count());
    }

    public function test_the_windows_route_counts_its_own_hits(): void
    {
        // throttle มี prefix ของตัวเอง — ไม่แย่งตัวนับกับ APK
        for ($hit = 1; $hit <= 30; $hit++) {
            $this->get('/localvpn/download/windows', ['Accept' => '*/*'])->assertNotFound();
        }

        $this->get('/localvpn/download/windows', ['Accept' => '*/*'])->assertStatus(429);
        $this->get('/localvpn/download/apk', ['Accept' => '*/*'])->assertNotFound();
    }

    // ── /localvpn/download ────────────────────────────────────────────

    public function test_the_download_page_offers_both_files(): void
    {
        $this->release('1.0.40');

        $this->get('/localvpn/download')
            ->assertOk()
            ->assertSee('LocalVPN for Android')
            ->assertSee('LocalVPN for Windows')
            ->assertSee('href="' . route('localvpn.download.apk') . '"', false)
            ->assertSee('href="' . route('localvpn.download.windows') . '"', false)
            ->assertSee('ดาวน์โหลดตัวติดตั้ง')
            ->assertDontSee('github', false);
    }

    public function test_the_download_page_does_not_offer_an_installer_the_release_lacks(): void
    {
        $this->release('1.0.40', assets: [$this->asset('LocalVPN-v1.0.40.apk', self::APK_BYTES, '1.0.40')]);

        $this->get('/localvpn/download')
            ->assertOk()
            ->assertSee('href="' . route('localvpn.download.apk') . '"', false)
            ->assertDontSee('href="' . route('localvpn.download.windows') . '"', false)
            ->assertSee('ยังไม่พร้อมดาวน์โหลด');
    }

    // ── helpers ───────────────────────────────────────────────────────

    /** migration 2026_03_26_000001/000003 สร้าง LocalVPN + GitHub setting ที่ไม่มี token ไว้แล้ว — แบบเดียวกับ production */
    private function localvpn(): Product
    {
        return Product::where('slug', 'localvpn')->sole();
    }

    /**
     * เวอร์ชันที่ sync มาจาก GitHub — APK เป็นไฟล์หลัก (asset_pattern *.apk) และรายการไฟล์ทั้งสามของ release
     *
     * @param  array|string|null  $assets  'all' = ครบสามไฟล์ · null = แถวที่ sync ไว้ก่อนมีคอลัมน์ assets
     */
    private function release(string $version, bool $active = true, array|string|null $assets = 'all'): ProductVersion
    {
        return ProductVersion::create([
            'product_id' => $this->localvpn()->id,
            'version' => $version,
            'github_release_id' => 1,
            'github_release_url' => 'https://api.github.com/repos/xjanova/localvpn/releases/assets/9',
            'download_url' => "https://github.com/xjanova/localvpn/releases/download/v{$version}/LocalVPN-v{$version}.apk",
            'download_filename' => "LocalVPN-v{$version}.apk",
            'file_size' => strlen(self::APK_BYTES),
            'sha256' => hash('sha256', self::APK_BYTES),
            'assets' => $assets === 'all' ? array_map(
                fn (array $asset) => [
                    'name' => $asset['name'],
                    'size' => $asset['size'],
                    'sha256' => substr($asset['digest'], 7),
                    'download_url' => $asset['browser_download_url'],
                    'api_url' => $asset['url'],
                ],
                $this->githubAssets($version),
            ) : $assets,
            'is_active' => $active,
            'synced_at' => now(),
        ]);
    }

    /** ไฟล์ที่บันทึกไว้ในรูปเดียวกับที่ GithubReleaseService เก็บ */
    private function asset(string $name, string $bytes, string $version): array
    {
        return [
            'name' => $name,
            'size' => strlen($bytes),
            'sha256' => hash('sha256', $bytes),
            'download_url' => "https://github.com/xjanova/localvpn/releases/download/v{$version}/{$name}",
            'api_url' => 'https://api.github.com/repos/xjanova/localvpn/releases/assets/' . crc32($name),
        ];
    }

    /** release ตามที่ API ของ GitHub ตอบ — สามไฟล์ตามที่ release.yml ของแอปแนบ */
    private function githubRelease(string $version, string $exeState = 'uploaded'): array
    {
        return [
            'id' => 2,
            'tag_name' => "v{$version}",
            'html_url' => "https://github.com/xjanova/localvpn/releases/tag/v{$version}",
            'body' => "## LocalVPN v{$version}",
            'assets' => $this->githubAssets($version, $exeState),
        ];
    }

    private function githubAssets(string $version, string $exeState = 'uploaded'): array
    {
        $files = [
            "LocalVPN-v{$version}.apk" => [self::APK_BYTES, 'uploaded'],
            "LocalVPN-Setup-v{$version}.exe" => [self::EXE_BYTES, $exeState],
            "LocalVPN-windows-v{$version}.zip" => [self::ZIP_BYTES, 'uploaded'],
        ];

        return collect($files)->map(fn (array $file, string $name) => [
            'url' => 'https://api.github.com/repos/xjanova/localvpn/releases/assets/' . crc32($name),
            'browser_download_url' => "https://github.com/xjanova/localvpn/releases/download/v{$version}/{$name}",
            'name' => $name,
            'state' => $file[1],
            'size' => strlen($file[0]),
            'digest' => 'sha256:' . hash('sha256', $file[0]),
        ])->values()->all();
    }

    /**
     * ลิงก์ไฟล์ของ release → 302 ไป CDN ที่เซ็นแยกตามไฟล์ → เนื้อไฟล์นั้น พร้อม header ของต้นทางที่ห้ามหลุดถึงลูกค้า
     * $latest = release ล่าสุดที่ read-through ถาม GitHub
     */
    private function fakeGithub(?array $latest = null): void
    {
        $bytes = fn (string $url) => match (true) {
            str_contains($url, '.exe') => self::EXE_BYTES,
            str_contains($url, '.zip') => self::ZIP_BYTES,
            default => self::APK_BYTES,
        };

        $stubs = [
            'github.com/xjanova/localvpn/releases/download/*' => fn (ClientRequest $request) => Http::response('', 302, [
                'Location' => sprintf(self::SIGNED_URL, basename(parse_url($request->url(), PHP_URL_PATH))),
            ]),
            'release-assets.githubusercontent.com/*' => fn (ClientRequest $request) => Http::response($bytes($request->url()), 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => (string) strlen($bytes($request->url())),
                'x-github-request-id' => 'ABCD:1234',
            ]),
        ];

        if ($latest !== null) {
            $this->latest = $latest;
            $stubs = ['api.github.com/repos/xjanova/localvpn/releases/latest' => fn () => Http::response($this->latest)] + $stubs;
        }

        Http::fake($stubs);
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
