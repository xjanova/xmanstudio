<?php

namespace Tests\Feature;

use App\Models\DownloadLog;
use App\Models\GithubSetting;
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
 * Anti X Mobile — the phone companion of Anti X, free for everyone (owner, 2026-10-21)
 *
 *   — the product row and its private release setting come from a migration; the read-only GitHub token of
 *     Anti X is reused when there is one (copied encrypted, never written in code)
 *   — check-machine hands every new phone a FREE key by itself (FREEMIUM_PRODUCTS), the same key after
 *   — anyone downloads the APK from this site, no session, no Anti X purchase — never a redirect to GitHub
 *   — update/check points the app's updater at /anti-x-mobile/download/{version} with that file's sha256
 *   — the Anti X page shows the free download button to every visitor, buyer or not
 */
class AntiXMobileAppTest extends TestCase
{
    use RefreshDatabase;

    private const SHA256 = 'aa11bb22cc33dd44ee55ff6600778899aa11bb22cc33dd44ee55ff6600778899';

    private const APK_BYTES = "PK\x03\x04antix-mobile-apk\x00\x01\xfe\xff";

    private const TOKEN = 'github_pat_test_secret_token';

    private const SIGNED_URL = 'https://release-assets.githubusercontent.com/github-production-release-asset/777?X-Amz-Signature=abc';

    /** the app sends SHA-256 hex of ANDROID_ID + its own salt (lib/services/license_service.dart) */
    private const MACHINE = '3a6eb0790f39ac87c94f3856b2dd2c5d110e6811602261a9a923d3bb23adc8b7';

    private const OTHER_MACHINE = 'b5bb9d8014a0f9b1d61e21e796d78dccdf1352f23cd32812f4850b878ae4944c';

    private const MIGRATION = 'migrations/2026_10_21_120000_register_anti_x_mobile_product.php';

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->withoutVite();
        Http::preventStrayRequests();

        // created by the migration, as on production after deploy
        $this->product = Product::where('slug', 'anti-x-mobile')->firstOrFail();
    }

    // ── the product row (migration) ─────────────────────────────────────

    public function test_the_migration_registers_a_free_app_with_its_private_apk_release_source(): void
    {
        $this->assertSame('Anti X Mobile', $this->product->name);
        $this->assertSame(0.0, (float) $this->product->price);
        $this->assertTrue($this->product->requires_license);
        $this->assertTrue($this->product->is_active);
        $this->assertFalse($this->product->isComingSoon());

        $setting = $this->setting();
        $this->assertSame('xjanova/antix-mobile', $setting->full_repo_name);
        $this->assertSame('AntiX-Mobile-*.apk', $setting->asset_pattern);
        $this->assertTrue($setting->is_active);
        $this->assertTrue($setting->auto_sync);
        // no Anti X token in a fresh database: nothing to share yet
        $this->assertNull($setting->github_token_decrypted);
    }

    public function test_the_read_only_token_of_anti_x_is_shared_when_the_owner_already_set_one(): void
    {
        $antiX = Product::where('slug', 'anti-x')->firstOrFail();
        GithubSetting::where('product_id', $antiX->id)->sole()->update(['github_token' => self::TOKEN]);

        $this->migration()->down();
        $this->assertFalse(Product::where('slug', 'anti-x-mobile')->exists());
        $this->migration()->up();

        $mobile = Product::where('slug', 'anti-x-mobile')->sole();
        $this->assertSame(self::TOKEN, GithubSetting::where('product_id', $mobile->id)->sole()->github_token_decrypted);
    }

    public function test_running_the_migration_again_keeps_the_owners_edits(): void
    {
        $this->setting()->update(['github_token' => 'another-token', 'asset_pattern' => 'AntiX-Mobile-*-universal.apk']);

        $this->migration()->up();

        $this->assertSame(1, Product::where('slug', 'anti-x-mobile')->count());
        $this->assertSame('another-token', $this->setting()->github_token_decrypted);
        $this->assertSame('AntiX-Mobile-*-universal.apk', $this->setting()->asset_pattern);
    }

    public function test_rolling_back_never_takes_the_free_keys_of_phones_with_it(): void
    {
        $this->checkMachine()->assertOk();

        $this->migration()->down();

        $this->assertTrue(Product::where('slug', 'anti-x-mobile')->exists());
        $this->assertSame(1, LicenseKey::where('product_id', $this->product->id)->count());
    }

    // ── the free licence ────────────────────────────────────────────────

    public function test_check_machine_gives_a_new_phone_a_free_licence_and_the_same_key_after(): void
    {
        $this->postJson('/api/v1/product/anti-x-mobile/register-device', [
            'machine_id' => self::MACHINE,
            'machine_name' => 'Pixel 8',
            'os_version' => 'Android 15',
            'app_version' => '0.1.0',
        ])->assertOk();

        $first = $this->checkMachine()->assertOk();
        $key = $first->json('data.license_key');

        $this->assertMatchesRegularExpression('/^FREE-[A-Z0-9]{20}$/', $key);
        $first->assertExactJson([
            'success' => true,
            'has_license' => true,
            'data' => [
                'license_key' => $key,
                'license_type' => 'free',
                'status' => 'active',
                'expires_at' => null,
                'days_remaining' => null,
                // the whole app is free — not the demo set ('trial_mode')
                'features' => ['all_features', 'in_app_updates'],
            ],
        ]);

        // next start / reinstall on the same phone = the same key, not a new one each time
        $this->checkMachine()->assertOk()->assertJsonPath('data.license_key', $key);

        $license = LicenseKey::where('product_id', $this->product->id)->sole();
        $this->assertSame(self::MACHINE, $license->machine_id);
        $this->assertNull($license->user_id);

        $this->assertNotSame($key, $this->checkMachine(self::OTHER_MACHINE)->json('data.license_key'));
    }

    public function test_anti_x_itself_still_hands_out_no_free_key(): void
    {
        $this->postJson('/api/v1/product/anti-x/check-machine', ['machine_id' => self::MACHINE])
            ->assertOk()
            ->assertJsonPath('has_license', false);
    }

    // ── update check and the download ───────────────────────────────────

    public function test_update_check_points_the_app_at_this_site(): void
    {
        $this->makeVersion('0.2.0');

        $response = $this->getJson('/api/v1/product/anti-x-mobile/update/check?current_version=0.1.0')
            ->assertOk()
            ->assertJsonPath('has_update', true)
            ->assertJsonPath('latest_version', '0.2.0')
            ->assertJsonPath('download_url', route('anti-x-mobile.download', ['version' => '0.2.0']))
            ->assertJsonPath('sha256', self::SHA256)
            ->assertJsonPath('file_size', strlen(self::APK_BYTES))
            ->assertJsonPath('filename', 'AntiX-Mobile-0.2.0.apk');

        $this->assertStringNotContainsStringIgnoringCase('github', $response->getContent());
    }

    public function test_anyone_downloads_the_apk_from_this_site_with_no_redirect_and_no_trace_of_github(): void
    {
        $version = $this->makeVersion('0.2.0');
        $this->setting()->update(['github_token' => self::TOKEN]);

        Http::fake([
            'api.github.com/repos/xjanova/antix-mobile/releases/assets/903' => Http::response('', 302, ['Location' => self::SIGNED_URL]),
            'release-assets.githubusercontent.com/*' => fn () => Http::response(self::APK_BYTES, 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => (string) strlen(self::APK_BYTES),
                'x-github-request-id' => 'ABCD:1234',
            ]),
        ]);

        // like the app's updater: no session, no browser Accept
        $response = $this->get('/anti-x-mobile/download/0.2.0', ['Accept' => '*/*']);

        $response->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Type', 'application/vnd.android.package-archive');
        $this->assertInstanceOf(StreamedResponse::class, $response->baseResponse);
        $this->assertStringContainsString('AntiX-Mobile-0.2.0.apk', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::APK_BYTES, $response->streamedContent());
        $this->assertNoTraceOfGithub($response);
        $this->assertStringNotContainsString(self::TOKEN, json_encode($response->headers->all()));

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.github.com/repos/xjanova/antix-mobile/releases/assets/903')
            && $request->hasHeader('Authorization', 'Bearer ' . self::TOKEN));

        $this->assertSame($version->id, DownloadLog::sole()->product_version_id);
    }

    public function test_an_unknown_version_is_a_json_404_for_the_app_and_the_anti_x_page_for_a_browser(): void
    {
        $this->get('/anti-x-mobile/download/9.9.9', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertJsonPath('success', false);

        $this->get('/anti-x-mobile/download/9.9.9')
            ->assertRedirect(route('products.show', 'anti-x') . '#mobile');
    }

    public function test_the_download_is_404_while_the_app_is_switched_off(): void
    {
        $this->makeVersion('0.2.0');
        $this->product->update(['is_active' => false]);

        $this->get('/anti-x-mobile/download/0.2.0', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'error' => 'Product not found']);
    }

    // ── the page ────────────────────────────────────────────────────────

    public function test_every_visitor_sees_the_free_download_on_the_anti_x_page(): void
    {
        $this->makeVersion('0.2.0');

        // a guest, who has bought nothing
        $html = $this->get('/products/anti-x')->assertOk()->getContent();
        $this->assertStringContainsString('id="mobile"', $html);
        $this->assertStringContainsString('href="' . route('anti-x-mobile.download') . '"', $html);
        $this->assertStringContainsString('เวอร์ชันล่าสุด 0.2.0', $html);
        $this->assertStringNotContainsStringIgnoringCase('github', $html);

        // a signed-in customer who never bought Anti X sees the same button
        $this->actingAs(User::factory()->create());
        $this->assertStringContainsString('href="' . route('anti-x-mobile.download') . '"', $this->get('/products/anti-x')->getContent());
    }

    public function test_before_the_first_release_the_page_says_so_instead_of_a_dead_link(): void
    {
        $html = $this->get('/products/anti-x')->assertOk()->getContent();

        $this->assertStringContainsString('data-mobile-pending', $html);
        $this->assertStringNotContainsString('href="' . route('anti-x-mobile.download') . '"', $html);
    }

    public function test_a_coming_soon_app_shows_no_download_link(): void
    {
        $this->makeVersion('0.2.0');
        $this->product->update(['is_coming_soon' => true]);

        $html = $this->get('/products/anti-x')->assertOk()->getContent();

        $this->assertStringNotContainsString('href="' . route('anti-x-mobile.download') . '"', $html);
    }

    public function test_the_apps_own_product_url_goes_to_the_mobile_section(): void
    {
        $this->get('/products/anti-x-mobile')->assertRedirect(route('products.show', 'anti-x') . '#mobile');
    }

    public function test_the_download_page_of_the_app_needs_no_sign_in_and_no_purchase(): void
    {
        $this->makeVersion('0.3.0');

        // ก่อนแก้: /download/anti-x-mobile พาไปหน้า login (owner, 2026-10-21)
        $html = $this->get('/download/anti-x-mobile')->assertOk()->getContent();
        $this->assertStringContainsString('data-free-download', $html);
        $this->assertStringContainsString('href="' . route('anti-x-mobile.download', '0.3.0') . '"', $html);
        $this->assertStringNotContainsString('เข้าสู่ระบบเพื่อดาวน์โหลด', $html);
        $this->assertStringNotContainsStringIgnoringCase('github', $html);

        $this->actingAs(User::factory()->create());
        $this->assertStringContainsString('data-free-download', $this->get('/download/anti-x-mobile')->assertOk()->getContent());
    }

    public function test_an_anti_x_buyer_finds_the_app_on_the_anti_x_download_page_and_in_the_download_center(): void
    {
        $this->makeVersion('0.3.0');
        $antiX = Product::where('slug', 'anti-x')->firstOrFail();
        ProductVersion::create([
            'product_id' => $antiX->id, 'version' => '1.14.0', 'github_release_id' => 9300,
            'download_url' => 'https://github.com/xjanova/antix/releases/download/v1.14.0/AntiX-1.14.0-win-x64.zip',
            'download_filename' => 'AntiX-1.14.0-win-x64.zip', 'file_size' => 1024, 'is_active' => true, 'synced_at' => now(),
        ]);
        $buyer = User::factory()->create();
        $this->buyAntiX($buyer, $antiX);

        $page = $this->actingAs($buyer)->get('/download/anti-x')->assertOk()->getContent();
        $this->assertStringContainsString('data-mobile-companion', $page);
        $this->assertStringContainsString('href="' . route('anti-x-mobile.download', '0.3.0') . '"', $page);
        $this->assertStringContainsString('เวอร์ชัน 0.3.0', $page);

        $center = $this->actingAs($buyer)->get(route('customer.downloads'))->assertOk()->getContent();
        $this->assertStringContainsString('Anti X Mobile', $center);
        $this->assertStringContainsString('href="' . route('anti-x-mobile.download') . '"', $center);

        // แอปปิดอยู่ (เร็วๆ นี้) = ไม่มีลิงก์
        $this->product->update(['is_coming_soon' => true]);
        $page = $this->actingAs($buyer)->get('/download/anti-x')->assertOk()->getContent();
        $this->assertStringContainsString('data-mobile-companion', $page);
        $this->assertStringNotContainsString(route('anti-x-mobile.download', '0.3.0'), $page);
    }

    // ── helpers ─────────────────────────────────────────────────────────

    private function buyAntiX(User $user, Product $antiX): void
    {
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(), 'user_id' => $user->id, 'customer_name' => $user->name, 'customer_email' => $user->email,
            'customer_phone' => '0800000000', 'subtotal' => $antiX->price, 'total' => $antiX->price, 'status' => 'completed', 'payment_status' => 'paid',
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $antiX->id, 'product_name' => $antiX->name,
            'price' => $antiX->price, 'quantity' => 1, 'subtotal' => $antiX->price,
        ]);
        LicenseKey::create([
            'product_id' => $antiX->id, 'order_id' => $order->id, 'user_id' => $user->id, 'license_key' => LicenseKey::generateKey(),
            'status' => LicenseKey::STATUS_ACTIVE, 'license_type' => 'yearly', 'expires_at' => now()->addYear(),
        ]);
    }

    private function migration(): object
    {
        return require database_path(self::MIGRATION);
    }

    private function setting(): GithubSetting
    {
        return GithubSetting::where('product_id', $this->product->id)->sole();
    }

    private function checkMachine(string $machine = self::MACHINE): TestResponse
    {
        return $this->postJson('/api/v1/product/anti-x-mobile/check-machine', [
            'machine_id' => $machine,
            'android_id' => substr(strrev($machine), 0, 64),
        ]);
    }

    private function makeVersion(string $version): ProductVersion
    {
        return ProductVersion::create([
            'product_id' => $this->product->id,
            'version' => $version,
            'github_release_id' => 9201,
            'github_release_url' => 'https://api.github.com/repos/xjanova/antix-mobile/releases/assets/903',
            'download_url' => "https://github.com/xjanova/antix-mobile/releases/download/v{$version}/AntiX-Mobile-{$version}.apk",
            'download_filename' => "AntiX-Mobile-{$version}.apk",
            'file_size' => strlen(self::APK_BYTES),
            'sha256' => self::SHA256,
            'is_active' => true,
            'synced_at' => now(),
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
