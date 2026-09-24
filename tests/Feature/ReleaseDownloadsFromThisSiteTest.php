<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DownloadLog;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * แอปโหลดได้จาก xman4289.com เท่านั้น และลูกค้าต้องไม่รู้ repo (กฎเจ้าของ 2026-09-24)
 *
 *   — ปุ่มดาวน์โหลดฟรีของ CluadeX / GPUxMINE ส่งไฟล์จากเว็บเราเอง ไม่ redirect และไม่ลิงก์ไป GitHub
 *   — ดาวน์โหลดสินค้าที่ซื้อแล้ว (DownloadController) ไม่ redirect ไปไฟล์หรือหน้า release บน GitHub อีก
 *   — ลิงก์ mirror ที่ admin ใส่เองซึ่งไม่ใช่ GitHub ยัง redirect ได้ตามเดิม
 */
class ReleaseDownloadsFromThisSiteTest extends TestCase
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

    // ── CluadeX ────────────────────────────────────────────────────────

    public function test_the_cluadex_free_download_comes_from_this_site(): void
    {
        $version = $this->release($this->product('cluadex-ai-coding-assistant'), 'cluadeX', '3.0.54', 'CluadeX-win-Portable.zip');
        $this->fakeGithubFile();

        $response = $this->get('/cluadex/download')
            ->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Type', 'application/octet-stream')
            ->assertHeader('Content-Length', (string) strlen(self::FILE_BYTES));

        $this->assertStringContainsString('CluadeX-win-Portable.zip', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::FILE_BYTES, $response->streamedContent());
        $this->assertNoTraceOfGithub($response);
        $this->assertSame($version->id, DownloadLog::sole()->product_version_id);
    }

    public function test_before_cluadex_has_a_release_a_browser_goes_back_to_the_cluadex_page(): void
    {
        $this->product('cluadex-ai-coding-assistant');

        $this->get('/cluadex/download')
            ->assertRedirect(route('cluadex.detail'))
            ->assertSessionHas('error');

        $this->get('/cluadex/download', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'error' => 'Version not found']);
    }

    public function test_head_on_the_cluadex_download_never_touches_github(): void
    {
        $this->release($this->product('cluadex-ai-coding-assistant'), 'cluadeX', '3.0.54', 'CluadeX-win-Portable.zip');

        $response = $this->call('HEAD', '/cluadex/download');

        $response->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Length', '477424210');
        $this->assertSame(0, DownloadLog::count());
    }

    public function test_the_cluadex_pages_link_to_this_site_and_never_to_github(): void
    {
        foreach (['/cluadex', '/cluadex/pricing'] as $page) {
            $html = $this->get($page)->assertOk()->getContent();

            $this->assertStringNotContainsStringIgnoringCase('github.com/xjanova', $html, $page);
            $this->assertStringContainsString('href="' . route('cluadex.download') . '"', $html, $page);
        }
    }

    // ── GPUxMINE ───────────────────────────────────────────────────────

    public function test_the_gpuxmine_page_offers_the_client_from_this_site(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/gpuxmine')->assertOk()->getContent();

        $this->assertStringContainsString('href="' . route('gpuxmine.download') . '"', $html);
        $this->assertStringNotContainsStringIgnoringCase('github.com', $html);
    }

    public function test_the_gpuxmine_client_is_streamed_to_a_signed_in_user(): void
    {
        // migration ลงทะเบียน gpuxmine พร้อม GitHub setting (repo public ไม่มี token) ไว้แล้ว
        // → ปุ่มโหลดถาม GitHub ก่อนว่ามีตัวใหม่กว่าที่ DB รู้ไหม
        $this->release($this->product('gpuxmine'), 'GpuXmine', '0.1.17', 'GpuxMine-win-Setup.exe');
        $this->fakeGithubFile([
            'api.github.com/repos/xjanova/GpuXmine/releases/latest' => Http::response(['id' => 1, 'tag_name' => 'v0.1.17', 'assets' => []]),
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get('/gpuxmine/download')
            ->assertOk()
            ->assertHeaderMissing('Location');

        $this->assertStringContainsString('GpuxMine-win-Setup.exe', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::FILE_BYTES, $response->streamedContent());
    }

    public function test_the_gpuxmine_client_needs_a_signed_in_user(): void
    {
        $this->get('/gpuxmine/download')->assertRedirect(route('login'));
    }

    // ── ดาวน์โหลดสินค้าที่ซื้อแล้ว ──────────────────────────────────────

    public function test_a_bought_version_that_lives_on_github_is_streamed_not_redirected(): void
    {
        // เวอร์ชันที่ไม่มี GitHub setting — เดิม redirect ไป github_release_url ตรง ๆ
        $product = $this->product('some-desktop-app', price: 990);
        $version = $this->manualVersion($product, 'https://github.com/xjanova/some-desktop-app/releases/download/v2.0.0/SomeApp-2.0.0.zip');
        $user = $this->buyer($product);
        $this->fakeGithubFile();

        $response = $this->actingAs($user)->get("/download/some-desktop-app/{$version->version}")
            ->assertOk()
            ->assertHeaderMissing('Location');

        $this->assertStringContainsString('SomeApp-2.0.0.zip', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::FILE_BYTES, $response->streamedContent());
        $this->assertNoTraceOfGithub($response);
    }

    public function test_a_bought_version_that_only_points_at_a_github_release_page_is_404_not_a_redirect(): void
    {
        $product = $this->product('some-desktop-app', price: 990);
        $version = $this->manualVersion($product, 'https://github.com/xjanova/some-desktop-app/releases/tag/v2.0.0');

        $this->actingAs($this->buyer($product))
            ->get("/download/some-desktop-app/{$version->version}")
            ->assertNotFound()
            ->assertHeaderMissing('Location');
    }

    public function test_a_mirror_that_is_not_github_is_still_a_redirect(): void
    {
        $product = $this->product('some-desktop-app', price: 990);
        $version = $this->manualVersion($product, 'https://files.example.net/SomeApp-2.0.0.zip');

        $this->actingAs($this->buyer($product))
            ->get("/download/some-desktop-app/{$version->version}")
            ->assertRedirect('https://files.example.net/SomeApp-2.0.0.zip');
    }

    public function test_the_licence_key_download_api_streams_instead_of_handing_out_a_github_link(): void
    {
        $product = $this->product('some-desktop-app', price: 990);
        $this->manualVersion($product, 'https://github.com/xjanova/some-desktop-app/releases/download/v2.0.0/SomeApp-2.0.0.zip');
        $key = LicenseKey::create([
            'product_id' => $product->id,
            'license_key' => 'XMAN-TEST-KEY-0001',
            'status' => 'active',
            'license_type' => 'yearly',
            'expires_at' => now()->addYear(),
        ]);
        $this->fakeGithubFile();

        $response = $this->postJson('/api/download/some-desktop-app', ['license_key' => $key->license_key])
            ->assertOk()
            ->assertHeaderMissing('Location');

        $this->assertSame(self::FILE_BYTES, $response->streamedContent());
        $this->assertNoTraceOfGithub($response);
    }

    // ── Apache (public_html/.htaccess) ────────────────────────────────

    public function test_apache_keeps_the_length_of_every_streamed_download_and_nothing_else(): void
    {
        // production gzip ทุก response และไม่เชื่อ Content-Length จาก PHP-FPM — กฎนี้ปิดทั้งสองอย่างให้ URL
        // ที่ ReleaseDownloadStreamer ส่งไฟล์ · ย้าย route แล้วลืมแก้ .htaccess = ไฟล์กลับไปไม่มีขนาดอีก
        $lines = file(base_path('public_html/.htaccess'), FILE_IGNORE_NEW_LINES);
        $at = collect($lines)->search(fn ($line) => str_starts_with(trim($line), 'RewriteCond %{THE_REQUEST}'));
        $this->assertNotFalse($at, 'public_html/.htaccess ไม่มีกฎของ URL ดาวน์โหลด');

        [, , $pattern, $flags] = preg_split('/\s+/', trim($lines[$at]));
        $this->assertSame('[NC]', $flags);
        $this->assertSame('RewriteRule ^ - [E=no-gzip:1,E=dont-vary:1,E=ap_trust_cgilike_cl:1]', trim($lines[$at + 1]));

        $matches = fn (string $method, string $url) => preg_match(
            '#' . str_replace('#', '\#', $pattern) . '#i',
            $method . ' ' . parse_url($url, PHP_URL_PATH) . (parse_url($url, PHP_URL_QUERY) ? '?' . parse_url($url, PHP_URL_QUERY) : '') . ' HTTP/1.1'
        ) === 1;

        foreach ([
            ['GET', route('winx-tools.download')],
            ['GET', route('winx-tools.download', ['version' => '1.0.2'])],
            ['HEAD', route('winx-tools.download', ['version' => '1.0.2'])],
            ['GET', route('winx-tools.download', ['version' => '1.0.2', 'from' => 'app'])],
            ['GET', route('cluadex.download')],
            ['GET', route('gpuxmine.download')],
            ['GET', route('tping.download.apk')],
            ['HEAD', route('tping.download.apk')],
            ['GET', route('smschecker.download.apk')],
            ['GET', route('localvpn.download.apk')],
            ['GET', route('download.product', ['slug' => 'some-desktop-app', 'version' => '2.0.0'])],
            ['POST', route('download.api', ['slug' => 'some-desktop-app'])],
            ['POST', route('download.api', ['slug' => 'some-desktop-app', 'version' => '2.0.0'])],
        ] as [$method, $url]) {
            $this->assertTrue($matches($method, $url), "{$method} {$url} ต้องได้ Content-Length");
        }

        foreach ([
            route('download.page', ['slug' => 'some-desktop-app']), // หน้า HTML
            '/tping/download', '/smschecker/download', '/localvpn/download', '/tping/install-guide', // หน้าของแอป APK
            '/tping/download/apkx', '/customer/downloads', '/winx-tools/downloads', '/products/winx-tools', '/',
        ] as $url) {
            $this->assertFalse($matches('GET', $url), "GET {$url} ต้องไม่อยู่ในกฎนี้");
        }
    }

    // ── helpers ───────────────────────────────────────────────────────

    /** บางตัว (gpuxmine) migration สร้างไว้แล้ว — ใช้แถวเดิม */
    private function product(string $slug, int $price = 0): Product
    {
        return Product::updateOrCreate(['slug' => $slug], [
            'category_id' => $this->category->id,
            'name' => $slug,
            'description' => 'x',
            'price' => $price,
            'stock' => 0,
            'requires_license' => false,
            'is_active' => true,
        ]);
    }

    /** เวอร์ชันที่ sync มาจาก GitHub (repo public ไม่มี token — แบบเดียวกับ CluadeX/GPUxMINE ใน production) */
    private function release(Product $product, string $repo, string $version, string $filename): ProductVersion
    {
        return ProductVersion::create([
            'product_id' => $product->id,
            'version' => $version,
            'github_release_id' => 1,
            'github_release_url' => "https://api.github.com/repos/xjanova/{$repo}/releases/assets/9",
            'download_url' => "https://github.com/xjanova/{$repo}/releases/download/v{$version}/{$filename}",
            'download_filename' => $filename,
            'file_size' => 477424210,
            'is_active' => true,
            'synced_at' => now(),
        ]);
    }

    /** เวอร์ชันที่ admin สร้างมือ — ลิงก์ที่กรอกเก็บไว้ใน github_release_url */
    private function manualVersion(Product $product, string $link): ProductVersion
    {
        return ProductVersion::create([
            'product_id' => $product->id,
            'version' => '2.0.0',
            'github_release_url' => $link,
            'is_active' => true,
        ]);
    }

    private function buyer(Product $product): User
    {
        $user = User::factory()->create();

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

        return $user;
    }

    /** ลิงก์ไฟล์ของ release ทุกตัว → 302 ไป CDN → ไฟล์ พร้อม header ของต้นทางที่ห้ามหลุดถึงลูกค้า */
    private function fakeGithubFile(array $more = []): void
    {
        Http::fake($more + [
            'github.com/xjanova/*/releases/download/*' => Http::response('', 302, ['Location' => self::SIGNED_URL]),
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
