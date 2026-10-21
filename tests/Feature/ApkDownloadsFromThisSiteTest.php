<?php

namespace Tests\Feature;

use App\Models\DownloadLog;
use App\Models\GithubSetting;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * APK ของ Tping, SMS Checker และ LocalVPN ส่งจาก xman4289.com ผ่านตัวส่งไฟล์กลาง เหมือนแอปอื่น (กฎเจ้าของ 2026-09-24)
 *
 *   — ไม่ redirect ไม่มีร่องรอย GitHub ใน header และ Content-Type เป็น APK มือถือจึงเสนอติดตั้งได้ทันที
 *   — จองที่ในช่องส่งไฟล์ร่วมของทั้งเว็บ (config/downloads.php) เต็มแล้วตอบ 503 ไม่ปล่อยให้ PHP-FPM worker หมด
 *   — แต่ละ route มี throttle ของตัวเอง ไม่แย่งตัวนับกับ route อื่น
 *   — เวอร์ชันที่ส่งยังเป็นกฎเดิมของแต่ละแอป: SMS Checker = ตัวที่ update/check ประกาศ · Tping / LocalVPN = ตัวที่ active
 *   — /smschecker/download/apk ยังเปิดแม้ปิดขาย ตัวเช็คอัปเดตในแอปส่งลูกค้าที่ถือ license มาที่นี่
 */
class ApkDownloadsFromThisSiteTest extends TestCase
{
    use RefreshDatabase;

    private const FILE_BYTES = "PK\x03\x04android-package-from-the-cdn\x00\xfe";

    private const SIGNED_URL = 'https://release-assets.githubusercontent.com/github-production-release-asset/9?X-Amz-Signature=abc';

    private const TOKEN = 'ghp_test_secret_token';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->withoutVite();

        // ห้ามคุยกับ GitHub จริง — คำขอที่ไม่ได้ fake ไว้ทำให้เทสต์ล้มทันที
        Http::preventStrayRequests();
    }

    // ── Tping ──────────────────────────────────────────────────────────

    public function test_the_tping_apk_is_streamed_from_this_site_as_an_android_package(): void
    {
        $version = $this->release($this->tping(), 'tping', '1.2.102', 'Tping-v1.2.102.apk');
        $this->fakeGithub('tping');

        $response = $this->get('/tping/download/apk')
            ->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Type', 'application/vnd.android.package-archive')
            ->assertHeader('Content-Length', (string) strlen(self::FILE_BYTES));

        $this->assertStringContainsString('Tping-v1.2.102.apk', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::FILE_BYTES, $response->streamedContent());
        $this->assertNoTraceOfGithub($response);
        $this->assertSame($version->id, DownloadLog::sole()->product_version_id);

        // repo มี token (แบบ production) — token ไปถึง API ของ GitHub เท่านั้น ไม่ถึง CDN และไม่ถึงลูกค้า
        Http::assertSent(fn (ClientRequest $request) => str_contains($request->url(), 'api.github.com/repos/xjanova/tping/releases/assets/9')
            && $request->hasHeader('Authorization', 'Bearer ' . self::TOKEN));
        Http::assertNotSent(fn (ClientRequest $request) => str_contains($request->url(), 'release-assets.githubusercontent.com')
            && $request->hasHeader('Authorization'));
        $this->assertStringNotContainsString(self::TOKEN, json_encode($response->headers->all()));
    }

    public function test_tping_keeps_serving_its_active_version(): void
    {
        // admin ปิดตัวใหม่ที่มีปัญหาไว้ — ลูกค้าได้ตัวที่ active อยู่ (กฎเดิมของ Tping) ไม่ใช่เลขเวอร์ชันสูงสุด
        $product = $this->tping();
        $this->release($product, 'tping', '1.2.102', 'Tping-v1.2.102.apk');
        $this->release($product, 'tping', '1.2.103', 'Tping-v1.2.103.apk', active: false);
        $this->fakeGithub('tping');

        $response = $this->get('/tping/download/apk', ['Accept' => '*/*'])->assertOk();

        $this->assertStringContainsString('Tping-v1.2.102.apk', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::FILE_BYTES, $response->streamedContent());
    }

    public function test_before_tping_has_a_release_a_browser_goes_back_to_the_tping_download_page(): void
    {
        $this->tping();

        $this->get('/tping/download/apk')
            ->assertRedirect(route('tping.download'))
            ->assertSessionHas('error');

        // ตัวอัปเดตในแอปไม่ส่ง Accept แบบเบราว์เซอร์ — ได้ JSON ที่อ่านออก ไม่ใช่หน้าเว็บ
        $this->get('/tping/download/apk', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'error' => 'Version not found']);
    }

    public function test_a_version_without_a_file_name_is_still_sent_as_an_apk(): void
    {
        // เวอร์ชันที่สร้างมือในหน้า admin มีแค่ลิงก์ API ของ asset — ถ้าหลุดเป็นชื่อ "download-1.2.103"
        // ที่ไม่มีนามสกุล ไฟล์จะไปเป็น octet-stream และมือถือไม่เสนอติดตั้ง
        ProductVersion::create([
            'product_id' => $this->tping()->id,
            'version' => '1.2.103',
            'github_release_url' => 'https://api.github.com/repos/xjanova/tping/releases/assets/9',
            'is_active' => true,
        ]);
        $this->fakeGithub('tping');

        $response = $this->get('/tping/download/apk', ['Accept' => '*/*'])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.android.package-archive');

        $this->assertStringContainsString('Tping-v1.2.103.apk', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::FILE_BYTES, $response->streamedContent());
    }

    public function test_the_real_file_name_wins_over_the_made_up_one(): void
    {
        // รู้ชื่อไฟล์จากลิงก์ของ release — ใช้ชื่อนั้น ชื่อ Tping-v{เวอร์ชัน}.apk มีไว้เมื่อไม่รู้อะไรเลยเท่านั้น
        ProductVersion::create([
            'product_id' => $this->tping()->id,
            'version' => '1.2.104',
            'github_release_url' => 'https://api.github.com/repos/xjanova/tping/releases/assets/9',
            'download_url' => 'https://github.com/xjanova/tping/releases/download/v1.2.104/tping-release.apk',
            'is_active' => true,
        ]);
        $this->fakeGithub('tping');

        $response = $this->get('/tping/download/apk', ['Accept' => '*/*'])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.android.package-archive');

        $this->assertStringContainsString('tping-release.apk', $response->headers->get('Content-Disposition'));
        $this->assertStringNotContainsString('Tping-v1.2.104.apk', $response->headers->get('Content-Disposition'));
    }

    public function test_head_on_an_apk_answers_without_fetching_the_file_or_holding_a_place(): void
    {
        // curl -I / link preview ไม่รัน callback ของ StreamedResponse — ต้องไม่แตะ GitHub และไม่จองช่องค้างไว้
        config(['downloads.max_concurrent_streams' => 1]);
        $this->release($this->tping(), 'tping', '1.2.102', 'Tping-v1.2.102.apk');

        $this->call('HEAD', '/tping/download/apk', [], [], [], ['HTTP_ACCEPT' => '*/*'])
            ->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Type', 'application/vnd.android.package-archive')
            ->assertHeader('Content-Length', '56263561');

        $this->assertSame(0, DownloadLog::count());
        $this->assertTrue(Cache::lock('downloads:stream-slot:0', 10)->get());
    }

    public function test_when_github_will_not_hand_over_the_apk_the_app_gets_an_error_not_a_broken_file(): void
    {
        // proxy เดิมตอบ 200 ไปก่อนแล้วส่งข้อความ error ของต้นทางเป็นเนื้อไฟล์ — แอปได้ไฟล์เสียไปติดตั้ง
        $this->release($this->tping(), 'tping', '1.2.102', 'Tping-v1.2.102.apk');
        Http::fake([
            'api.github.com/*' => Http::response(['message' => 'Bad credentials'], 401),
            'github.com/*' => Http::response('Not Found', 404),
        ]);

        $this->get('/tping/download/apk', ['Accept' => '*/*'])
            ->assertStatus(502)
            ->assertJsonPath('success', false);

        $this->get('/tping/download/apk')
            ->assertRedirect(route('tping.download'))
            ->assertSessionHas('error');

        $this->assertSame(0, DownloadLog::count());
    }

    // ── SMS Checker ────────────────────────────────────────────────────

    public function test_the_smschecker_apk_still_downloads_while_the_product_is_off_sale(): void
    {
        $version = $this->release($this->smschecker(), 'smschecker', '2.0.189', 'SmsChecker-v2.0.189.apk');
        $this->fakeGithub('smschecker', latest: $this->githubRelease('smschecker', '2.0.189', 'SmsChecker-v2.0.189.apk'));

        // หน้าขายปิดอยู่…
        $this->get('/smschecker/download')->assertNotFound();

        // …แต่ APK ยังโหลดได้ แบบเดียวกับตัวเช็คอัปเดตในแอป (OkHttp ไม่ส่ง Accept แบบเบราว์เซอร์)
        $response = $this->get('/smschecker/download/apk', ['Accept' => '*/*'])
            ->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Type', 'application/vnd.android.package-archive');

        $this->assertStringContainsString('SmsChecker-v2.0.189.apk', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::FILE_BYTES, $response->streamedContent());
        $this->assertNoTraceOfGithub($response);
        $this->assertSame($version->id, DownloadLog::sole()->product_version_id);
    }

    public function test_the_smschecker_apk_is_exactly_what_its_update_check_announced(): void
    {
        // DB รู้แค่ 2.0.188 แต่ GitHub มี 2.0.189 แล้ว — update/check ดึงเข้ามาเองแล้วประกาศ 2.0.189
        // ปุ่มโหลดต้องส่งตัวนั้นเป๊ะ ไม่งั้นแอปได้ไฟล์เก่ากว่าที่บอกไว้ และ Android ไม่ยอม downgrade
        $this->release($this->smschecker(), 'smschecker', '2.0.188', 'SmsChecker-v2.0.188.apk');
        $this->fakeGithub('smschecker', latest: $this->githubRelease('smschecker', '2.0.189', 'SmsChecker-v2.0.189.apk'));

        $update = $this->getJson('/api/v1/product/smschecker/update/check?current_version=2.0.188')
            ->assertOk()
            ->assertJsonPath('has_update', true)
            ->assertJsonPath('latest_version', '2.0.189')
            ->assertJsonPath('download_url', route('smschecker.download.apk'))
            ->json();

        $response = $this->get($update['download_url'], ['Accept' => '*/*'])->assertOk();

        $this->assertStringContainsString($update['filename'], $response->headers->get('Content-Disposition'));
        $this->assertSame((string) $update['file_size'], $response->headers->get('Content-Length'));
        $this->assertSame($update['sha256'], hash('sha256', $response->streamedContent()));
    }

    public function test_the_smschecker_apk_catches_up_with_github_before_any_update_check_asks(): void
    {
        // ลิงก์ APK ถูกเปิดก่อนที่แอปเครื่องไหนจะเช็คอัปเดต — ต้องได้ตัวที่ update/check จะประกาศ ไม่ใช่ตัวเก่าใน DB
        $this->release($this->smschecker(), 'smschecker', '2.0.188', 'SmsChecker-v2.0.188.apk');
        $this->fakeGithub('smschecker', latest: $this->githubRelease('smschecker', '2.0.189', 'SmsChecker-v2.0.189.apk'));

        $response = $this->get('/smschecker/download/apk', ['Accept' => '*/*'])->assertOk();

        $this->assertStringContainsString('SmsChecker-v2.0.189.apk', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::FILE_BYTES, $response->streamedContent());

        $this->getJson('/api/v1/product/smschecker/update/check?current_version=2.0.188')
            ->assertJsonPath('latest_version', '2.0.189')
            ->assertJsonPath('filename', 'SmsChecker-v2.0.189.apk');
    }

    public function test_smschecker_serves_the_newest_release_even_when_it_sorts_lower_as_text(): void
    {
        // เปิดค้างไว้สองเวอร์ชัน — เรียงแบบข้อความ "2.0.99" มาก่อน "2.0.176" (บั๊กที่หน้า SMS Checker เคยเจอ)
        // update/check ประกาศตัวที่ออกล่าสุด ปุ่มโหลดต้องส่งตัวเดียวกัน
        $product = $this->smschecker(withGithub: false);
        $this->release($product, 'smschecker', '2.0.99', 'SmsChecker-v2.0.99.apk')
            ->forceFill(['created_at' => now()->subDay()])
            ->save();
        $this->release($product, 'smschecker', '2.0.176', 'SmsChecker-v2.0.176.apk');
        $this->fakeGithub('smschecker');

        $this->getJson('/api/v1/product/smschecker/update/check?current_version=2.0.1')
            ->assertOk()
            ->assertJsonPath('latest_version', '2.0.176');

        $response = $this->get('/smschecker/download/apk', ['Accept' => '*/*'])->assertOk();

        $this->assertStringContainsString('SmsChecker-v2.0.176.apk', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::FILE_BYTES, $response->streamedContent());
    }

    public function test_while_smschecker_is_off_sale_a_browser_that_gets_no_file_lands_on_a_page_that_opens(): void
    {
        // ยังไม่มีไฟล์ให้ส่ง · ระหว่างปิดขาย /smschecker/download ตอบ 404 — ห้ามพาเบราว์เซอร์ไปตายที่นั่น
        $this->smschecker(withGithub: false);

        $this->get('/smschecker/download/apk')
            ->assertRedirect(route('home'))
            ->assertSessionHas('error');

        // คนที่ล็อกอินอยู่มาจากศูนย์ดาวน์โหลด (ปุ่มเดียวที่ยังชี้มาที่นี่) — กลับไปที่นั่น
        $this->actingAs(User::factory()->create())
            ->get('/smschecker/download/apk')
            ->assertRedirect(route('customer.downloads'))
            ->assertSessionHas('error');

        $this->get('/smschecker/download/apk', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'error' => 'Version not found']);
    }

    public function test_while_smschecker_is_on_sale_a_browser_goes_back_to_its_download_page(): void
    {
        $this->smschecker(withGithub: false)->forceFill(['is_active' => true])->save();

        $this->get('/smschecker/download/apk')
            ->assertRedirect(route('smschecker.download'))
            ->assertSessionHas('error');
    }

    // ── LocalVPN ───────────────────────────────────────────────────────

    public function test_the_localvpn_apk_comes_from_the_public_release_file_without_a_token(): void
    {
        // GitHub setting ของ LocalVPN ไม่มี token (แบบ production) — โหลดจากลิงก์ไฟล์ของ release ไม่กินโควตา API
        $version = $this->release($this->localvpn(), 'localvpn', '1.0.39', 'LocalVPN-v1.0.39.apk');
        $this->fakeGithub('localvpn');

        $response = $this->get('/localvpn/download/apk')
            ->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Type', 'application/vnd.android.package-archive');

        $this->assertStringContainsString('LocalVPN-v1.0.39.apk', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::FILE_BYTES, $response->streamedContent());
        $this->assertNoTraceOfGithub($response);
        $this->assertSame($version->id, DownloadLog::sole()->product_version_id);

        Http::assertSent(fn (ClientRequest $request) => str_contains($request->url(), 'github.com/xjanova/localvpn/releases/download/v1.0.39/LocalVPN-v1.0.39.apk'));
        Http::assertNotSent(fn (ClientRequest $request) => $request->hasHeader('Authorization'));
    }

    public function test_before_localvpn_has_a_release_a_browser_goes_back_to_the_localvpn_download_page(): void
    {
        $this->localvpn();

        $this->get('/localvpn/download/apk')
            ->assertRedirect(route('localvpn.download'))
            ->assertSessionHas('error');

        $this->get('/localvpn/download/apk', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'error' => 'Version not found']);
    }

    // ── ทั้งสามตัว ──────────────────────────────────────────────────────

    /**
     * ตัวอัปเดตในแอปโหลดเองโดยไม่มี session — ลิงก์ที่ update/check ส่งต้องได้ APK จากเว็บเรา ชื่อไฟล์ตรงกับที่ประกาศ
     */
    public function test_each_apps_update_check_leads_to_its_apk_on_this_site(): void
    {
        $apps = [
            ['product' => $this->tping(), 'repo' => 'tping', 'version' => '1.2.102', 'file' => 'Tping-v1.2.102.apk', 'route' => 'tping.download.apk'],
            ['product' => $this->smschecker(), 'repo' => 'smschecker', 'version' => '2.0.189', 'file' => 'SmsChecker-v2.0.189.apk', 'route' => 'smschecker.download.apk'],
            ['product' => $this->localvpn(), 'repo' => 'localvpn', 'version' => '1.0.39', 'file' => 'LocalVPN-v1.0.39.apk', 'route' => 'localvpn.download.apk'],
        ];

        foreach ($apps as $app) {
            $this->release($app['product'], $app['repo'], $app['version'], $app['file']);
            $this->fakeGithub($app['repo'], latest: ['id' => 1, 'tag_name' => "v{$app['version']}", 'assets' => []]);
        }

        foreach ($apps as $app) {
            $slug = $app['product']->slug;
            $json = $this->getJson("/api/v1/product/{$slug}/update/check?current_version=0.0.1")
                ->assertOk()
                ->assertJsonPath('has_update', true)
                ->assertJsonPath('latest_version', $app['version'])
                ->assertJsonPath('filename', $app['file'])
                ->assertJsonPath('download_url', route($app['route']))
                ->json();

            $this->assertStringNotContainsStringIgnoringCase('github', json_encode($json), $slug);

            // ไม่ล็อกอินและไม่ส่ง Accept แบบเบราว์เซอร์ — แบบเดียวกับตัวอัปเดตในแอป
            $response = $this->get($json['download_url'], ['Accept' => '*/*'])
                ->assertOk()
                ->assertHeaderMissing('Location')
                ->assertHeader('Content-Type', 'application/vnd.android.package-archive');

            $this->assertStringContainsString($app['file'], $response->headers->get('Content-Disposition'), $slug);
            $this->assertSame(self::FILE_BYTES, $response->streamedContent(), $slug);
        }
    }

    public function test_every_apk_takes_a_place_in_the_shared_download_pool(): void
    {
        // APK 40–56 MB จอง PHP-FPM worker ไว้ตลอดเวลาที่ลูกค้าโหลด เหมือนไฟล์ของแอปอื่น — proxy เดิมไม่เคยจองที่
        // ช่องเต็มแล้วต้องตอบ 503 ให้แอปลองใหม่ ไม่ใช่ส่งต่อไปจนทั้งเว็บไม่มี worker เหลือ
        config(['downloads.max_concurrent_streams' => 1]);
        $this->release($this->tping(), 'tping', '1.2.102', 'Tping-v1.2.102.apk');
        $this->release($this->smschecker(withGithub: false), 'smschecker', '2.0.189', 'SmsChecker-v2.0.189.apk');
        $this->release($this->localvpn(), 'localvpn', '1.0.39', 'LocalVPN-v1.0.39.apk');

        $this->assertTrue(Cache::lock('downloads:stream-slot:0', 60)->get());

        foreach (['/tping/download/apk', '/smschecker/download/apk', '/localvpn/download/apk'] as $url) {
            $this->get($url, ['Accept' => '*/*'])
                ->assertStatus(503)
                ->assertHeader('Retry-After', '60')
                ->assertJsonPath('success', false);
        }

        $this->get('/tping/download/apk')
            ->assertRedirect(route('tping.download'))
            ->assertSessionHas('error');

        // ไม่ได้ต่อ GitHub (preventStrayRequests) และไม่นับเป็นการโหลด
        $this->assertSame(0, DownloadLog::count());
    }

    public function test_the_place_is_held_while_the_apk_flows_and_given_back_after(): void
    {
        config(['downloads.max_concurrent_streams' => 1]);
        $this->release($this->localvpn(), 'localvpn', '1.0.39', 'LocalVPN-v1.0.39.apk');
        $this->fakeGithub('localvpn');

        $response = $this->get('/localvpn/download/apk', ['Accept' => '*/*'])->assertOk();

        // header ส่งแล้ว เนื้อไฟล์ยังไม่ไหล — ช่องยังถูกจองอยู่
        $this->assertFalse(Cache::lock('downloads:stream-slot:0', 10)->get());

        $response->streamedContent();

        $this->assertTrue(Cache::lock('downloads:stream-slot:0', 10)->get());
    }

    public function test_each_apk_route_counts_its_own_hits(): void
    {
        // throttle ที่ไม่มี prefix ใช้ตัวนับเดียวกันทั้งเว็บ — ของแต่ละ route ต้องแยกกัน และยังจำกัดได้จริง
        // ยังไม่มีไฟล์ให้โหลด คำขอจึงจบเร็ว แต่ถูกนับทุกครั้งเหมือนกัน
        foreach (['/tping/download/apk', '/smschecker/download/apk', '/localvpn/download/apk'] as $url) {
            for ($hit = 1; $hit <= 30; $hit++) {
                $this->get($url, ['Accept' => '*/*'])->assertNotFound();
            }

            $this->get($url, ['Accept' => '*/*'])->assertStatus(429);
        }

        // ปุ่มโหลดของแอปอื่นไม่โดนตัวนับของ APK
        $this->get('/winx-tools/download', ['Accept' => '*/*'])->assertNotFound();
    }

    // ── helpers ───────────────────────────────────────────────────────

    /** migration 2026_03_04_000001 สร้าง tping ไว้ในฐานเทสต์แล้ว — GitHub setting แบบ production: repo มี token */
    private function tping(): Product
    {
        $product = Product::where('slug', 'tping')->sole();
        $this->githubSetting($product, 'tping', self::TOKEN);

        return $product;
    }

    /** migration 2026_03_21_000001 สร้างไว้แล้ว — ปิดขายอยู่แบบ production (2026-09-23) · repo มี token */
    private function smschecker(bool $withGithub = true): Product
    {
        $product = Product::where('slug', 'smschecker')->sole();
        $product->forceFill(['is_active' => false])->save();

        if ($withGithub) {
            $this->githubSetting($product, 'smschecker', self::TOKEN);
        }

        return $product;
    }

    /** migration 2026_03_26_000003 ใส่ GitHub setting ที่ไม่มี token ไว้แล้ว — แบบเดียวกับ production */
    private function localvpn(): Product
    {
        return Product::where('slug', 'localvpn')->sole();
    }

    private function githubSetting(Product $product, string $repo, string $token): void
    {
        GithubSetting::updateOrCreate(['product_id' => $product->id], [
            'github_owner' => 'xjanova',
            'github_repo' => $repo,
            'github_token' => $token,
            'asset_pattern' => '*.apk',
            'is_active' => true,
            'auto_sync' => true,
        ]);
    }

    /** เวอร์ชันที่ sync มาจาก GitHub — รูปเดียวกับแถวใน production (Tping 1.2.102, SMS Checker 2.0.189, LocalVPN 1.0.39) */
    private function release(Product $product, string $repo, string $version, string $filename, bool $active = true): ProductVersion
    {
        return ProductVersion::create([
            'product_id' => $product->id,
            'version' => $version,
            'github_release_id' => 1,
            'github_release_url' => "https://api.github.com/repos/xjanova/{$repo}/releases/assets/9",
            'download_url' => "https://github.com/xjanova/{$repo}/releases/download/v{$version}/{$filename}",
            'download_filename' => $filename,
            'file_size' => 56263561,
            'is_active' => $active,
            'synced_at' => now(),
        ]);
    }

    /** release บน GitHub ตามที่ API ตอบ — APK ตัวเดียว ขนาดและ sha256 ตรงกับไฟล์ที่ CDN ส่งจริง */
    private function githubRelease(string $repo, string $version, string $filename): array
    {
        return [
            'id' => 2,
            'tag_name' => "v{$version}",
            'html_url' => "https://github.com/xjanova/{$repo}/releases/tag/v{$version}",
            'body' => "เวอร์ชัน {$version}",
            'assets' => [[
                'url' => "https://api.github.com/repos/xjanova/{$repo}/releases/assets/10",
                'browser_download_url' => "https://github.com/xjanova/{$repo}/releases/download/v{$version}/{$filename}",
                'name' => $filename,
                'size' => strlen(self::FILE_BYTES),
                'digest' => 'sha256:' . hash('sha256', self::FILE_BYTES),
            ]],
        ];
    }

    /**
     * ต้นทางทั้งสองทาง — API ของ asset (repo มี token) และลิงก์ไฟล์ของ release (ไม่มี token) → 302 ไป CDN → ไฟล์
     * พร้อม header ของต้นทางที่ห้ามหลุดถึงลูกค้า · $latest = release ล่าสุดที่ read-through ถาม GitHub
     */
    private function fakeGithub(string $repo, ?array $latest = null): void
    {
        $stubs = [
            "api.github.com/repos/xjanova/{$repo}/releases/assets/*" => Http::response('', 302, ['Location' => self::SIGNED_URL]),
            "github.com/xjanova/{$repo}/releases/download/*" => Http::response('', 302, ['Location' => self::SIGNED_URL]),
            'release-assets.githubusercontent.com/*' => fn () => Http::response(self::FILE_BYTES, 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => (string) strlen(self::FILE_BYTES),
                'ETag' => '"0x8DCB7C0FFEE"',
                'x-github-request-id' => 'ABCD:1234',
            ]),
        ];

        if ($latest !== null) {
            $stubs = ["api.github.com/repos/xjanova/{$repo}/releases/latest" => Http::response($latest)] + $stubs;
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
