<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\SmsPaymentController;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\DownloadLog;
use App\Models\GithubSetting;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\User;
use App\Services\GithubReleaseService;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * WinXTools (Windows) ขายและแจกผ่านระบบเดียวกับแอปอื่นของสตูดิโอ
 *
 *   — แอปอัปเดตตัวเอง: update/check ชี้ไปหน้าโหลดสาธารณะแบบระบุเวอร์ชัน พร้อม sha256 ขนาด ชื่อไฟล์
 *   — หน้าโหลด redirect ไปที่ไฟล์บน GitHub (ไม่ stream ผ่าน PHP) และไม่ต้องล็อกอิน เพราะมีรุ่นฟรี
 *   — ขายแค่ Pro ฿199 จ่ายครั้งเดียว: pricing มีแผนเดียว ตะกร้าคิด 199 license ไม่มีวันหมดอายุ
 *   — ทดลอง Pro 48 ชั่วโมงตามที่แอปบอกลูกค้าไว้
 *   — แอปอื่น (smschecker ฯลฯ) ต้องได้คำตอบเหมือนเดิม
 */
class WinXToolsDistributionTest extends TestCase
{
    use RefreshDatabase;

    private const SHA256 = 'aa11bb22cc33dd44ee55ff6600778899aa11bb22cc33dd44ee55ff6600778899';

    private const ZIP_SIZE = 73400320;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->withoutVite();

        // ห้ามคุยกับ GitHub จริงระหว่างเทสต์ — เทสต์ที่ต้องใช้ fake URL ของตัวเอง
        // ยิงออกไปโดยไม่ได้ fake = เทสต์ล้มทันที จึงพิสูจน์ได้ด้วยว่าเส้นทางไหนไม่ถาม GitHub เลย
        Http::preventStrayRequests();

        $category = Category::firstOrCreate(
            ['slug' => 'system-utilities'],
            ['name' => 'System Utilities', 'description' => 'x']
        );

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'WinXTools',
            'slug' => 'winx-tools',
            'description' => 'x',
            'price' => 199,
            'stock' => 999,
            'requires_license' => true,
            'is_active' => true,
        ]);
    }

    // ── update check ───────────────────────────────────────────────────

    public function test_update_check_hands_the_app_a_public_versioned_download_and_the_file_details(): void
    {
        $this->makeVersion('1.2.0');

        $this->getJson('/api/v1/product/winx-tools/update/check?current_version=1.1.0')
            ->assertOk()
            ->assertExactJson([
                'has_update' => true,
                'latest_version' => '1.2.0',
                'download_url' => route('winx-tools.download', ['version' => '1.2.0']),
                'changelog' => '',
                'sha256' => self::SHA256,
                'file_size' => self::ZIP_SIZE,
                'filename' => 'WinXTools-v1.2.0-win-x64.zip',
            ]);

        $this->assertSame(url('/winx-tools/download/1.2.0'), route('winx-tools.download', ['version' => '1.2.0']));
    }

    public function test_an_app_that_is_up_to_date_gets_no_download_but_still_the_file_details(): void
    {
        $this->makeVersion('1.2.0');

        $this->getJson('/api/v1/product/winx-tools/update/check?current_version=1.2.0')
            ->assertOk()
            ->assertExactJson([
                'has_update' => false,
                'latest_version' => '1.2.0',
                'download_url' => '',
                'changelog' => '',
                'sha256' => self::SHA256,
                'file_size' => self::ZIP_SIZE,
                'filename' => 'WinXTools-v1.2.0-win-x64.zip',
            ]);
    }

    public function test_before_the_first_release_the_update_check_answers_in_the_same_shape(): void
    {
        $this->getJson('/api/v1/product/winx-tools/update/check?current_version=1.0.0')
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
    }

    public function test_the_older_check_update_endpoint_points_at_the_public_download_too(): void
    {
        $this->makeVersion('1.2.0');

        $this->postJson('/api/v1/products/winx-tools/check-update', ['current_version' => '1.0.0'])
            ->assertOk()
            ->assertJsonPath('has_update', true)
            ->assertJsonPath('update.download_url', route('winx-tools.download', ['version' => '1.2.0']))
            ->assertJsonPath('update.sha256', self::SHA256);
    }

    public function test_the_other_apps_keep_their_download_links(): void
    {
        // สร้างโดย migration 2026_03_21_000001 — ไม่มี GitHub setting ในฐานทดสอบ จึงไม่ถาม GitHub
        $checker = Product::where('slug', 'smschecker')->firstOrFail();
        ProductVersion::create([
            'product_id' => $checker->id,
            'version' => '2.0.200',
            'download_filename' => 'SmsChecker-v2.0.200.apk',
            'file_size' => 1000,
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/product/smschecker/update/check?current_version=2.0.1')
            ->assertOk()
            ->assertJsonPath('has_update', true)
            ->assertJsonPath('latest_version', '2.0.200')
            ->assertJsonPath('download_url', url('/smschecker/download/apk'))
            ->assertJsonPath('changelog', '')
            ->assertJsonPath('sha256', null)
            ->assertJsonPath('filename', 'SmsChecker-v2.0.200.apk');

        // สินค้าอื่นที่ไม่มีหน้าโหลดของตัวเอง ยังชี้ไปหน้าโหลดที่ต้องล็อกอิน + ซื้อก่อนเหมือนเดิม
        $other = $this->otherLicensedProduct('some-desktop-app');
        ProductVersion::create(['product_id' => $other->id, 'version' => '3.0.0', 'is_active' => true]);

        $this->getJson('/api/v1/product/some-desktop-app/update/check?current_version=1.0.0')
            ->assertOk()
            ->assertJsonPath('download_url', route('download.product', ['slug' => 'some-desktop-app', 'version' => '3.0.0']));

        $this->postJson('/api/v1/products/some-desktop-app/check-update', ['current_version' => '1.0.0'])
            ->assertJsonPath('update.download_url', route('download.product', ['slug' => 'some-desktop-app', 'version' => '3.0.0']));
    }

    public function test_update_check_pulls_a_new_github_release_in_by_itself(): void
    {
        $this->githubSetting();
        $this->makeVersion('1.2.0');

        $newSha = str_repeat('d', 64);
        Http::fake([
            'api.github.com/repos/xjanova/winxtools/releases/latest' => Http::response($this->release('1.3.0', [
                $this->asset('1.3.0', 902, 'WinXTools-v1.3.0-win-x64.zip', 'sha256:' . $newSha),
            ])),
        ]);

        $this->getJson('/api/v1/product/winx-tools/update/check?current_version=1.2.0')
            ->assertOk()
            ->assertJsonPath('has_update', true)
            ->assertJsonPath('latest_version', '1.3.0')
            ->assertJsonPath('download_url', route('winx-tools.download', ['version' => '1.3.0']))
            ->assertJsonPath('sha256', $newSha)
            ->assertJsonPath('file_size', self::ZIP_SIZE)
            ->assertJsonPath('filename', 'WinXTools-v1.3.0-win-x64.zip');
    }

    // ── GitHub sync ───────────────────────────────────────────────────

    public function test_github_sync_keeps_the_zip_with_its_sha256_and_its_public_link(): void
    {
        $this->githubSetting();

        // ตัวอัปเดตในแอปรับแค่ zip ที่เซ็นแล้ว — ถ้า release มีไฟล์อื่นด้วย (เช่น WinXTools.exe ที่ workflow
        // เคยอัปโหลด) ต้องได้ zip ไม่ใช่ตัวแรกในรายการ
        Http::fake([
            'api.github.com/repos/xjanova/winxtools/releases/latest' => Http::response($this->release('1.3.0', [
                $this->asset('1.3.0', 901, 'WinXTools.exe', 'sha256:' . str_repeat('c', 64)),
                $this->asset('1.3.0', 902, 'WinXTools-v1.3.0-win-x64.zip', 'sha256:' . strtoupper(self::SHA256)),
            ])),
        ]);

        app(GithubReleaseService::class)->syncLatestRelease($this->product);

        $version = ProductVersion::where('product_id', $this->product->id)->where('version', '1.3.0')->firstOrFail();

        $this->assertSame('WinXTools-v1.3.0-win-x64.zip', $version->download_filename);
        $this->assertSame(self::ZIP_SIZE, $version->file_size);
        $this->assertSame(self::SHA256, $version->sha256, 'stored lower-case, without the sha256: prefix');
        $this->assertSame('https://github.com/xjanova/winxtools/releases/download/v1.3.0/WinXTools-v1.3.0-win-x64.zip', $version->download_url);
        $this->assertSame('https://api.github.com/repos/xjanova/winxtools/releases/assets/902', $version->github_release_url);
    }

    public function test_a_resync_of_an_asset_without_a_digest_keeps_the_known_sha256(): void
    {
        $this->githubSetting();
        $this->makeVersion('1.3.0');

        Http::fake([
            'api.github.com/repos/xjanova/winxtools/releases/latest' => Http::response($this->release('1.3.0', [
                $this->asset('1.3.0', 902, 'WinXTools-v1.3.0-win-x64.zip', null),
            ])),
        ]);

        app(GithubReleaseService::class)->syncLatestRelease($this->product);

        $this->assertSame(self::SHA256, ProductVersion::where('version', '1.3.0')->firstOrFail()->sha256);
    }

    // ── the public download ───────────────────────────────────────────

    public function test_with_nothing_released_the_app_gets_a_clean_json_404(): void
    {
        $this->get('/winx-tools/download', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'error' => 'Version not found']);

        $this->makeVersion('1.2.0');

        $this->get('/winx-tools/download/9.9.9', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'error' => 'Version not found']);

        $this->assertSame(0, DownloadLog::count());
    }

    public function test_a_browser_is_sent_back_to_the_product_page_with_a_message(): void
    {
        // ค่า Accept ตั้งต้นของ test client คือของเบราว์เซอร์ (text/html,…)
        $this->get('/winx-tools/download')
            ->assertRedirect(route('products.show', 'winx-tools'))
            ->assertSessionHas('error');
    }

    public function test_a_version_without_any_file_is_404_not_a_github_failure(): void
    {
        $this->makeVersion('1.2.0', [
            'github_release_url' => 'https://github.com/xjanova/winxtools/releases/tag/v1.2.0',
            'download_url' => null,
            'download_filename' => null,
        ]);
        $this->githubSetting(['github_token' => 'ghp_test_secret_token']);

        $this->get('/winx-tools/download/1.2.0', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'error' => 'No file available for this version']);
    }

    public function test_without_a_token_it_redirects_straight_to_the_public_file(): void
    {
        $version = $this->makeVersion('1.2.0');
        $this->githubSetting();

        $this->get('/winx-tools/download/1.2.0', ['Accept' => '*/*'])
            ->assertStatus(302)
            ->assertRedirect($version->download_url);

        // ไม่ถาม GitHub API เลย (ถ้าถาม preventStrayRequests จะทำให้ล้ม) และบันทึกการโหลดไว้
        $log = DownloadLog::sole();
        $this->assertSame($version->id, $log->product_version_id);
        $this->assertNull($log->user_id);
    }

    public function test_without_a_version_it_serves_the_latest(): void
    {
        $this->makeVersion('1.1.0', ['is_active' => false]);
        $latest = $this->makeVersion('1.2.0');

        $this->get('/winx-tools/download', ['Accept' => '*/*'])
            ->assertRedirect($latest->download_url);
    }

    public function test_when_github_cannot_be_reached_the_free_download_still_works(): void
    {
        $latest = $this->makeVersion('1.2.0');
        $this->githubSetting();

        Http::fake(['api.github.com/*' => Http::failedConnection()]);

        // ปุ่ม "เริ่มใช้ฟรี" ถาม GitHub ว่ามีตัวใหม่ไหมก่อน — ต่อไม่ติดต้องได้ตัวที่ DB รู้ ไม่ใช่หน้า 500
        $this->get('/winx-tools/download', ['Accept' => '*/*'])
            ->assertRedirect($latest->download_url);
    }

    public function test_an_older_version_stays_downloadable_by_its_number(): void
    {
        // update/check บอก sha256 ของ 1.1.0 ไปแล้ว ถ้าระหว่างนั้น 1.2.0 ออก ไฟล์ที่ได้ต้องยังเป็น 1.1.0
        $old = $this->makeVersion('1.1.0', ['is_active' => false]);
        $this->makeVersion('1.2.0');

        $this->get('/winx-tools/download/1.1.0', ['Accept' => '*/*'])
            ->assertRedirect($old->download_url);
    }

    public function test_with_a_token_it_redirects_to_the_link_github_signs_and_never_leaks_the_token(): void
    {
        $this->makeVersion('1.2.0');
        $this->githubSetting(['github_token' => 'ghp_test_secret_token']);

        $signed = 'https://release-assets.githubusercontent.com/github-production-release-asset/555?X-Amz-Signature=abc';
        Http::fake([
            'api.github.com/repos/xjanova/winxtools/releases/assets/555' => Http::response('', 302, ['Location' => $signed]),
        ]);

        $response = $this->get('/winx-tools/download/1.2.0', ['Accept' => '*/*'])
            ->assertStatus(302)
            ->assertRedirect($signed);

        $this->assertStringNotContainsString('ghp_test_secret_token', (string) $response->headers->get('Location'));
        $this->assertStringNotContainsString('ghp_test_secret_token', (string) $response->getContent());

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer ghp_test_secret_token')
            && $request->hasHeader('Accept', 'application/octet-stream'));
    }

    public function test_a_version_synced_before_download_url_existed_is_still_served_through_github(): void
    {
        $this->makeVersion('1.2.0', ['download_url' => null]);
        $this->githubSetting();

        Http::fake([
            'api.github.com/repos/xjanova/winxtools/releases/assets/555' => Http::response('', 302, [
                'Location' => 'https://release-assets.githubusercontent.com/signed',
            ]),
        ]);

        $this->get('/winx-tools/download/1.2.0', ['Accept' => '*/*'])
            ->assertRedirect('https://release-assets.githubusercontent.com/signed');

        Http::assertSent(fn ($request) => ! $request->hasHeader('Authorization'));
    }

    public function test_when_github_will_not_sign_a_link_it_falls_back_to_the_public_file(): void
    {
        $version = $this->makeVersion('1.2.0');
        $this->githubSetting(['github_token' => 'ghp_test_secret_token']);

        Http::fake(['api.github.com/*' => Http::response(['message' => 'Server Error'], 500)]);

        $this->get('/winx-tools/download/1.2.0', ['Accept' => '*/*'])
            ->assertRedirect($version->download_url);
    }

    public function test_when_github_fails_and_there_is_no_public_file_the_app_gets_a_502(): void
    {
        $this->makeVersion('1.2.0', ['download_url' => null]);
        $this->githubSetting(['github_token' => 'ghp_test_secret_token']);

        Http::fake(['api.github.com/*' => Http::response(['message' => 'Server Error'], 500)]);

        $this->get('/winx-tools/download/1.2.0', ['Accept' => '*/*'])
            ->assertStatus(502)
            ->assertJsonPath('success', false);

        $this->assertSame(0, DownloadLog::count());
    }

    public function test_the_download_is_404_while_the_product_is_switched_off(): void
    {
        $this->makeVersion('1.2.0');
        $this->product->forceFill(['is_active' => false])->save();

        $this->get('/winx-tools/download/1.2.0', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'error' => 'Product not found']);

        $this->get('/winx-tools/download/1.2.0')->assertNotFound();
    }

    // ── pricing ───────────────────────────────────────────────────────

    public function test_pricing_sells_only_pro_lifetime_at_199_and_says_where_to_buy_it(): void
    {
        $this->getJson('/api/v1/product/winx-tools/pricing')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'data' => [
                    'product' => ['name' => 'WinXTools', 'slug' => 'winx-tools'],
                    'plans' => [
                        'lifetime' => [
                            'price' => 199,
                            'currency' => 'THB',
                            'duration_days' => null,
                            'features' => ['all_features', 'priority_support', 'cloud_sync', 'lifetime_updates', 'unlimited_devices'],
                        ],
                    ],
                    'purchase_url' => url('/products/winx-tools'),
                ],
            ]);
    }

    public function test_pricing_of_the_other_products_has_not_moved(): void
    {
        $plans = fn (int $monthly, int $yearly, int $lifetime) => [
            'monthly' => ['price' => $monthly, 'currency' => 'THB', 'duration_days' => 30, 'features' => ['all_features', 'standard_support', 'cloud_sync']],
            'yearly' => ['price' => $yearly, 'currency' => 'THB', 'duration_days' => 365, 'features' => ['all_features', 'priority_support', 'cloud_sync', 'priority_updates']],
            'lifetime' => ['price' => $lifetime, 'currency' => 'THB', 'duration_days' => null, 'features' => ['all_features', 'priority_support', 'cloud_sync', 'lifetime_updates', 'unlimited_devices']],
        ];

        $this->otherLicensedProduct('some-desktop-app');

        // assertSame = คีย์ ลำดับ และชนิดตรงกันทุกตัว — สิ่งที่แอปเดิม parse อยู่
        $this->assertSame($plans(499, 4990, 29000), $this->getJson('/api/v1/product/smschecker/pricing')->assertOk()->json('data.plans'));
        $this->assertSame($plans(399, 2500, 5000), $this->getJson('/api/v1/product/localvpn/pricing')->assertOk()->json('data.plans'));
        $this->assertSame($plans(399, 2500, 5000), $this->getJson('/api/v1/product/some-desktop-app/pricing')->assertOk()->json('data.plans'));
    }

    // ── trial ─────────────────────────────────────────────────────────

    public function test_the_pro_trial_lasts_48_hours(): void
    {
        $this->freezeSecond();

        $response = $this->postJson('/api/v1/product/winx-tools/demo', ['machine_id' => str_repeat('a1', 16)])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'เริ่มใช้งาน Trial 2 วันสำเร็จ')
            ->assertJsonPath('data.days_remaining', 2)
            ->assertJsonPath('data.hours_remaining', 48)
            ->assertJsonPath('data.seconds_remaining', 172800)
            ->assertJsonPath('data.expires_at', now()->addHours(48)->toISOString());

        $license = LicenseKey::where('license_key', $response->json('data.license_key'))->firstOrFail();
        $this->assertTrue($license->expires_at->equalTo(now()->addHours(48)));
    }

    public function test_other_products_still_get_a_24_hour_trial(): void
    {
        $this->freezeSecond();
        $this->otherLicensedProduct('some-desktop-app');

        $this->postJson('/api/v1/product/some-desktop-app/demo', ['machine_id' => str_repeat('b2', 16)])
            ->assertOk()
            ->assertJsonPath('message', 'เริ่มใช้งาน Trial 1 วันสำเร็จ')
            ->assertJsonPath('data.hours_remaining', 24)
            ->assertJsonPath('data.expires_at', now()->addHours(24)->toISOString());
    }

    // ── buying Pro ────────────────────────────────────────────────────

    public function test_buying_pro_puts_the_lifetime_licence_in_the_cart_at_199(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('cart.add', $this->product), ['quantity' => 1, 'license_type' => 'lifetime'])
            ->assertSessionHas('success');

        $item = CartItem::firstOrFail();
        $this->assertEquals(199, (float) $item->price);
        $this->assertSame('lifetime', json_decode($item->custom_requirements, true)['license_type']);
    }

    public function test_winx_tools_is_not_sold_by_the_month_or_the_year(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['monthly', 'yearly'] as $term) {
            $this->post(route('cart.add', $this->product), ['license_type' => $term])->assertSessionHas('error');
        }

        $this->assertSame(0, CartItem::count());
    }

    public function test_a_plain_purchase_gives_a_licence_that_never_expires(): void
    {
        app(LicenseService::class)->generateLicensesForOrder($this->paidOrder());

        $license = LicenseKey::where('product_id', $this->product->id)->sole();
        $this->assertSame('lifetime', $license->license_type);
        $this->assertNull($license->expires_at);
    }

    public function test_the_sms_payment_api_issues_the_same_lifetime_licence(): void
    {
        // Api\V1\SmsPaymentController มีสำเนาตัวออก license ของตัวเอง (private) ที่เคยตั้งต้นเป็นรายปีเสมอ
        // เรียกผ่าน reflection เพราะเส้นทางจริงต้องมีลายเซ็นของเครื่อง SmsChecker; ตัวมันไม่ใช้ dependency
        // ที่ constructor รับมา จึงสร้างโดยไม่เรียก constructor ได้
        $controller = (new \ReflectionClass(SmsPaymentController::class))->newInstanceWithoutConstructor();
        (new \ReflectionMethod($controller, 'generateLicensesForOrder'))->invoke($controller, $this->paidOrder());

        $license = LicenseKey::where('product_id', $this->product->id)->sole();
        $this->assertSame('lifetime', $license->license_type);
        $this->assertNull($license->expires_at);
    }

    public function test_a_licence_type_named_on_the_order_item_still_wins(): void
    {
        app(LicenseService::class)->generateLicensesForOrder($this->paidOrder(['license_type' => 'monthly']));

        $license = LicenseKey::where('product_id', $this->product->id)->sole();
        $this->assertSame('monthly', $license->license_type);
        $this->assertNotNull($license->expires_at);
    }

    public function test_other_products_still_default_to_a_yearly_licence(): void
    {
        $this->assertSame('lifetime', $this->product->defaultLicenseType());
        $this->assertSame('yearly', $this->otherLicensedProduct('some-desktop-app')->defaultLicenseType());
    }

    // ── the product page ──────────────────────────────────────────────

    public function test_the_product_page_sells_pro_through_the_cart_and_downloads_without_login(): void
    {
        $html = $this->get('/products/winx-tools')->assertOk()->getContent();

        // ปุ่ม "ซื้อ Pro — ฿199" ทั้งสามจุดเป็นฟอร์มใส่ตะกร้าแบบ lifetime ไม่ใช่ลิงก์ไปหน้ารวมสินค้า
        $this->assertSame(3, substr_count($html, 'action="' . route('cart.add', $this->product) . '"'));
        $this->assertSame(3, substr_count($html, 'name="license_type" value="lifetime"'));

        // "เริ่มใช้ฟรี" โหลดได้เลย ไม่ต้องล็อกอินหรือซื้อก่อน
        $this->assertStringContainsString('href="' . route('winx-tools.download') . '"', $html);
    }

    public function test_a_buyer_sees_download_buttons_that_need_no_customer_portal(): void
    {
        $user = User::factory()->create();
        $order = $this->paidOrder(null, $user);
        $order->update(['status' => 'completed']);

        $html = $this->actingAs($user)->get('/products/winx-tools')->assertOk()->getContent();

        // ฮีโร่ + การ์ด Pro + ท้ายหน้า + การ์ด Free
        $this->assertSame(4, substr_count($html, 'href="' . route('winx-tools.download') . '"'));
        $this->assertSame(0, substr_count($html, 'name="license_type" value="lifetime"'));
    }

    // ── the GitHub setting migration ─────────────────────────────────

    public function test_the_migration_wires_winx_tools_to_its_public_repo_once_and_leaves_the_owners_setting_alone(): void
    {
        $migration = require database_path('migrations/2026_09_23_120100_insert_winx_tools_github_setting.php');

        $migration->up();
        $migration->up();

        $setting = GithubSetting::where('product_id', $this->product->id)->sole();
        $this->assertSame('xjanova/winxtools', $setting->full_repo_name);
        $this->assertSame('WinXTools-*-win-x64.zip', $setting->asset_pattern);
        $this->assertTrue($setting->is_active);
        $this->assertTrue($setting->auto_sync);
        $this->assertNull($setting->github_token_decrypted);

        // ค่าที่เจ้าของแก้เองในหน้า admin ไม่ถูกทับ และ down() ไม่ลบแถวที่ไม่ใช่ของมัน
        $setting->github_token = 'ghp_owner_token';
        $setting->asset_pattern = '*.zip';
        $setting->save();

        $migration->up();
        $this->assertSame('*.zip', $setting->fresh()->asset_pattern);

        $migration->down();
        $this->assertTrue(GithubSetting::where('product_id', $this->product->id)->exists());
    }

    public function test_the_migration_does_nothing_where_winx_tools_is_not_sold(): void
    {
        $this->product->delete();

        $migration = require database_path('migrations/2026_09_23_120100_insert_winx_tools_github_setting.php');
        $migration->up();

        $this->assertFalse(GithubSetting::whereHas('product', fn ($q) => $q->where('slug', 'winx-tools'))->exists());
    }

    // ── helpers ───────────────────────────────────────────────────────

    private function makeVersion(string $version, array $attributes = []): ProductVersion
    {
        return ProductVersion::create($attributes + [
            'product_id' => $this->product->id,
            'version' => $version,
            'github_release_id' => 7001,
            'github_release_url' => 'https://api.github.com/repos/xjanova/winxtools/releases/assets/555',
            'download_url' => "https://github.com/xjanova/winxtools/releases/download/v{$version}/WinXTools-v{$version}-win-x64.zip",
            'download_filename' => "WinXTools-v{$version}-win-x64.zip",
            'file_size' => self::ZIP_SIZE,
            'sha256' => self::SHA256,
            'is_active' => true,
            'synced_at' => now(),
        ]);
    }

    private function githubSetting(array $attributes = []): GithubSetting
    {
        return GithubSetting::create($attributes + [
            'product_id' => $this->product->id,
            'github_owner' => 'xjanova',
            'github_repo' => 'winxtools',
            'github_token' => '',
            'asset_pattern' => 'WinXTools-*-win-x64.zip',
            'is_active' => true,
            'auto_sync' => true,
        ]);
    }

    private function release(string $version, array $assets): array
    {
        return [
            'id' => 7001,
            'tag_name' => 'v' . $version,
            'html_url' => "https://github.com/xjanova/winxtools/releases/tag/v{$version}",
            'body' => '',
            'assets' => $assets,
        ];
    }

    private function asset(string $version, int $id, string $name, ?string $digest): array
    {
        $asset = [
            'id' => $id,
            'name' => $name,
            'size' => self::ZIP_SIZE,
            'url' => "https://api.github.com/repos/xjanova/winxtools/releases/assets/{$id}",
            'browser_download_url' => "https://github.com/xjanova/winxtools/releases/download/v{$version}/{$name}",
        ];

        // asset ที่อัปโหลดก่อนกลางปี 2025 ไม่มีฟิลด์ digest เลย
        if ($digest !== null) {
            $asset['digest'] = $digest;
        }

        return $asset;
    }

    private function otherLicensedProduct(string $slug): Product
    {
        return Product::create([
            'category_id' => $this->product->category_id,
            'name' => 'Some desktop app',
            'slug' => $slug,
            'description' => 'x',
            'price' => 990,
            'stock' => 0,
            'requires_license' => true,
            'is_active' => true,
        ]);
    }

    private function paidOrder(?array $requirements = null, ?User $user = null): Order
    {
        $user ??= User::factory()->create();

        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(),
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '0800000000',
            'subtotal' => 199,
            'total' => 199,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => 'WinXTools',
            'price' => 199,
            'quantity' => 1,
            'subtotal' => 199,
            'custom_requirements' => $requirements ? json_encode($requirements) : null,
        ]);

        return $order;
    }
}
