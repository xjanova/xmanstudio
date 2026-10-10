<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\DownloadLog;
use App\Models\GithubSetting;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductDevice;
use App\Models\ProductVersion;
use App\Models\User;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * Anti X (Windows server intrusion blocker) on the generic product API and the site cart
 *
 *   — the product row and its release setting come from a migration (deploy runs migrate, not seeders)
 *   — pricing: monthly 199 / yearly 1,990 / lifetime 9,900 from config/licenses.php, bought through the cart
 *   — a 14-day Pro trial, once per hardware (a Windows reinstall is a new machine id, not a new trial)
 *   — a paid key activates, validates, is found again by check-machine and moves; no free key is handed out
 *   — update/check answers like WinXTools: an xman4289.com download URL + sha256 / size / name of the zip
 *   — releases live in a PRIVATE repository: the zip is streamed through this server with the product's
 *     token — never a redirect, and not a single "github" in any answer the app or the customer gets
 */
class AntiXProductTest extends TestCase
{
    use RefreshDatabase;

    private const HARDWARE = '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8';

    private const SHA256 = 'bb22cc33dd44ee55ff6600778899aa11bb22cc33dd44ee55ff6600778899aa11';

    private const ZIP_BYTES = "PK\x03\x04antix-release-package\x00\x01\xfe\xff";

    private const TOKEN = 'github_pat_test_secret_token';

    /** the short-lived link GitHub's asset API redirects to — must never reach the customer */
    private const SIGNED_URL = 'https://release-assets.githubusercontent.com/github-production-release-asset/777?X-Amz-Signature=abc';

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->withoutVite();

        // nothing talks to the real GitHub: an unfaked request fails the test, which also proves which
        // paths (the product page, HEAD) never ask GitHub at all
        Http::preventStrayRequests();

        // created by 2026_10_10_200000_register_anti_x_product, as on production after deploy
        $this->product = Product::where('slug', 'anti-x')->firstOrFail();
    }

    // ── the product row (migration) ─────────────────────────────────────

    public function test_the_migration_registers_anti_x_and_its_private_release_repository(): void
    {
        $this->assertSame('Anti X', $this->product->name);
        $this->assertTrue($this->product->requires_license);
        $this->assertTrue($this->product->is_active);
        $this->assertFalse($this->product->is_coming_soon);
        $this->assertEquals(199, (float) $this->product->price);
        $this->assertSame('network-security', $this->product->category->slug);

        $setting = $this->setting();
        $this->assertSame('xjanova/antix', $setting->full_repo_name);
        $this->assertSame('AntiX-*-win-x64.zip', $setting->asset_pattern);
        $this->assertTrue($setting->is_active);
        $this->assertTrue($setting->auto_sync);
        // the repository is private, but a token never goes into a public migration — the owner adds it in the admin
        $this->assertNull($setting->github_token_decrypted);
    }

    public function test_running_the_migration_again_keeps_the_owners_edits_and_adds_nothing(): void
    {
        $this->setting()->update(['github_token' => self::TOKEN, 'asset_pattern' => 'AntiX-*.zip']);
        $this->product->update(['price' => 250, 'short_description' => 'edited in the admin']);

        $this->migration()->up();

        $this->assertSame(1, Product::where('slug', 'anti-x')->count());
        $this->assertSame(1, GithubSetting::where('product_id', $this->product->id)->count());
        $this->assertSame(self::TOKEN, $this->setting()->github_token_decrypted);
        $this->assertSame('AntiX-*.zip', $this->setting()->asset_pattern);
        $this->assertEquals(250, (float) $this->product->fresh()->price);
        $this->assertSame('edited in the admin', $this->product->fresh()->short_description);
    }

    public function test_rolling_back_never_deletes_a_product_that_has_keys(): void
    {
        $this->paidKey();

        $this->migration()->down();

        $this->assertTrue(Product::where('slug', 'anti-x')->exists());
        $this->assertTrue(GithubSetting::where('product_id', $this->product->id)->exists());
    }

    public function test_rolling_back_an_unsold_product_removes_it_and_up_brings_it_back(): void
    {
        $this->migration()->down();

        $this->assertFalse(Product::where('slug', 'anti-x')->exists());
        $this->assertFalse(GithubSetting::where('github_repo', 'antix')->exists());

        $this->migration()->up();

        $product = Product::where('slug', 'anti-x')->sole();
        $this->assertSame('xjanova/antix', $product->githubSetting->full_repo_name);
    }

    // ── pricing ─────────────────────────────────────────────────────────

    public function test_pricing_sells_three_terms_and_says_where_to_buy(): void
    {
        $this->getJson('/api/v1/product/anti-x/pricing')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'data' => [
                    'product' => ['name' => 'Anti X', 'slug' => 'anti-x'],
                    'plans' => [
                        'monthly' => ['price' => 199, 'currency' => 'THB', 'duration_days' => 30, 'features' => ['all_features', 'standard_support', 'cloud_sync']],
                        'yearly' => ['price' => 1990, 'currency' => 'THB', 'duration_days' => 365, 'features' => ['all_features', 'priority_support', 'cloud_sync', 'priority_updates']],
                        'lifetime' => ['price' => 9900, 'currency' => 'THB', 'duration_days' => null, 'features' => ['all_features', 'priority_support', 'cloud_sync', 'lifetime_updates', 'unlimited_devices']],
                    ],
                    'purchase_url' => url('/products/anti-x'),
                ],
            ]);
    }

    // ── trial ───────────────────────────────────────────────────────────

    public function test_a_new_server_is_offered_the_trial(): void
    {
        $this->registerDevice('a', self::HARDWARE)
            ->assertOk()
            ->assertJsonPath('success', true)
            // a first-time device answers device_status null (the column default is not read back) — clients
            // decide on can_start_trial, as WinXTools and BrainX do
            ->assertJsonPath('data.can_start_trial', true)
            ->assertJsonPath('data.trial_info', null);

        $this->demoCheck('a')
            ->assertOk()
            ->assertJsonPath('data.has_used_demo', false)
            ->assertJsonPath('data.can_start_demo', true);
    }

    public function test_the_pro_trial_lasts_fourteen_days(): void
    {
        $this->freezeSecond();

        $response = $this->demo('a', self::HARDWARE)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'เริ่มใช้งาน Trial 14 วันสำเร็จ')
            ->assertJsonPath('data.days_remaining', 14)
            ->assertJsonPath('data.hours_remaining', 336)
            ->assertJsonPath('data.seconds_remaining', 14 * 24 * 3600)
            ->assertJsonPath('data.expires_at', now()->addDays(14)->toISOString());

        $license = LicenseKey::where('license_key', $response->json('data.license_key'))->sole();
        $this->assertSame($this->product->id, $license->product_id);
        $this->assertSame(LicenseKey::TYPE_DEMO, $license->license_type);
        $this->assertTrue($license->expires_at->equalTo(now()->addDays(14)));

        $this->demoCheck('a')
            ->assertOk()
            ->assertJsonPath('data.is_trial_active', true)
            ->assertJsonPath('data.can_start_demo', false);

        $this->validateKey('a', $license->license_key)->assertOk()->assertJsonPath('is_valid', true);

        $this->travel(14)->days();
        $this->travel(1)->minutes();

        $this->validateKey('a', $license->license_key)->assertOk()->assertJsonPath('is_valid', false);
    }

    public function test_reinstalling_windows_does_not_give_a_second_trial(): void
    {
        $this->registerDevice('a', self::HARDWARE)->assertOk();
        $this->demo('a', self::HARDWARE)->assertOk();

        // a reinstall (or a restored snapshot): new machine id, same hardware
        $this->registerDevice('b', self::HARDWARE)->assertOk();

        $this->demoCheck('b')
            ->assertOk()
            ->assertJsonPath('data.has_used_demo', true)
            ->assertJsonPath('data.can_start_demo', false);

        $this->demo('b', self::HARDWARE)
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'TRIAL_USED_ON_THIS_HARDWARE');

        // told no, not accused of anything
        $device = ProductDevice::where('machine_id', $this->machineId('b'))->sole();
        $this->assertFalse($device->is_suspicious);
        $this->assertNotSame(ProductDevice::STATUS_BLOCKED, $device->status);
        $this->assertSame(1, LicenseKey::where('product_id', $this->product->id)->where('license_type', 'demo')->count());

        // other hardware keeps its own trial
        $this->demo('c', str_repeat('0c', 32))->assertOk()->assertJsonPath('data.days_remaining', 14);
    }

    // ── paid keys ───────────────────────────────────────────────────────

    public function test_a_paid_key_activates_validates_is_found_again_and_moves(): void
    {
        $this->freezeSecond();
        $key = $this->paidKey();

        $this->activate('a', strtolower($key->license_key))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.license_key', $key->license_key)
            ->assertJsonPath('data.license_type', 'yearly');

        $this->validateKey('a', $key->license_key)
            ->assertOk()
            ->assertJsonPath('is_valid', true)
            ->assertJsonPath('data.license_type', 'yearly');

        $this->checkMachine('a')
            ->assertOk()
            ->assertJsonPath('has_license', true)
            ->assertJsonPath('data.license_key', $key->license_key);

        // one key, one machine
        $this->activate('b', $key->license_key)
            ->assertForbidden()
            ->assertJsonPath('error_code', 'ALREADY_ACTIVATED_OTHER_DEVICE');

        $this->deactivate('a', $key->license_key)->assertOk()->assertJsonPath('success', true);
        $this->checkMachine('a')->assertOk()->assertJsonPath('has_license', false);

        $this->activate('b', $key->license_key)->assertOk();
        $this->validateKey('b', $key->license_key)->assertOk()->assertJsonPath('is_valid', true);
    }

    public function test_a_server_without_a_key_is_not_handed_a_free_one(): void
    {
        // not freemium: the app runs its own free tier when there is no paid key
        $this->checkMachine('a')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('has_license', false);

        $this->assertSame(0, LicenseKey::where('product_id', $this->product->id)->count());
    }

    // ── update check ────────────────────────────────────────────────────

    public function test_update_check_points_the_app_at_this_site_with_the_zips_details(): void
    {
        $this->setting()->update(['github_token' => self::TOKEN]);

        Http::fake([
            'api.github.com/repos/xjanova/antix/releases/latest' => Http::response($this->release('1.2.0', [
                $this->asset('1.2.0', 801, 'AntiX-1.2.0-win-x64.zip.sig', 'sha256:' . str_repeat('c', 64), 512),
                $this->asset('1.2.0', 802, 'AntiX-1.2.0-win-x64.zip', 'sha256:' . strtoupper(self::SHA256), 73400320),
            ], "### ใหม่\n- จับสเปรย์รหัสได้เร็วขึ้น\n\n**Full Changelog**: https://github.com/xjanova/antix/compare/v1.1.0...v1.2.0")),
        ]);

        $response = $this->getJson('/api/v1/product/anti-x/update/check?current_version=1.1.0')
            ->assertOk()
            ->assertExactJson([
                'has_update' => true,
                'latest_version' => '1.2.0',
                'download_url' => url('/anti-x/download/1.2.0'),
                'changelog' => "### ใหม่\n- จับสเปรย์รหัสได้เร็วขึ้น",
                'sha256' => self::SHA256,
                'file_size' => 73400320,
                'filename' => 'AntiX-1.2.0-win-x64.zip',
            ]);

        // this site's own route (production: https://xman4289.com/anti-x/download/1.2.0), never a file host
        $this->assertSame(route('anti-x.download', ['version' => '1.2.0']), $response->json('download_url'));
        $this->assertStringNotContainsStringIgnoringCase('github', $response->getContent());

        // the private repository is read with the product's token
        Http::assertSent(fn ($request) => $request->url() === 'https://api.github.com/repos/xjanova/antix/releases/latest'
            && $request->hasHeader('Authorization', 'Bearer ' . self::TOKEN));
    }

    public function test_an_app_that_is_up_to_date_gets_no_download(): void
    {
        $this->makeVersion('1.2.0');

        $this->getJson('/api/v1/product/anti-x/update/check?current_version=1.2.0')
            ->assertOk()
            ->assertExactJson([
                'has_update' => false,
                'latest_version' => '1.2.0',
                'download_url' => '',
                'changelog' => '',
                'sha256' => self::SHA256,
                'file_size' => strlen(self::ZIP_BYTES),
                'filename' => 'AntiX-1.2.0-win-x64.zip',
            ]);
    }

    public function test_before_a_release_or_a_token_exists_the_answer_keeps_its_shape(): void
    {
        // a private repository without a token answers 404 — exactly what the server sees until the owner adds one
        Http::fake(['api.github.com/*' => Http::response(['message' => 'Not Found'], 404)]);

        $response = $this->getJson('/api/v1/product/anti-x/update/check?current_version=1.0.0')
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

        $this->assertStringNotContainsStringIgnoringCase('github', $response->getContent());
    }

    // ── the download ────────────────────────────────────────────────────

    public function test_the_zip_is_streamed_from_the_private_repository_with_no_redirect_and_no_trace_of_github(): void
    {
        $version = $this->makeVersion('1.2.0');
        $this->setting()->update(['github_token' => self::TOKEN]);

        Http::fake([
            'api.github.com/repos/xjanova/antix/releases/assets/802' => Http::response('', 302, ['Location' => self::SIGNED_URL]),
            'release-assets.githubusercontent.com/*' => fn () => Http::response(self::ZIP_BYTES, 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => (string) strlen(self::ZIP_BYTES),
                'ETag' => '"0x8DCB7C0FFEE"',
                'x-github-request-id' => 'ABCD:1234',
            ]),
        ]);

        // what the app's updater sends: no Accept, and it does not follow redirects
        $response = $this->get('/anti-x/download/1.2.0', ['Accept' => '', 'User-Agent' => 'AntiX-Update/1.1.0']);

        $response->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Type', 'application/octet-stream')
            ->assertHeader('Content-Length', (string) strlen(self::ZIP_BYTES));
        $this->assertInstanceOf(StreamedResponse::class, $response->baseResponse);
        $this->assertStringContainsString('AntiX-1.2.0-win-x64.zip', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::ZIP_BYTES, $response->streamedContent());
        $this->assertNoTraceOfGithub($response);
        $this->assertStringNotContainsString(self::TOKEN, json_encode($response->headers->all()));

        // the token reaches GitHub's API only — the redirect to the CDN drops it
        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.github.com/repos/xjanova/antix/releases/assets/802')
            && $request->hasHeader('Authorization', 'Bearer ' . self::TOKEN)
            && $request->hasHeader('Accept', 'application/octet-stream'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'release-assets.githubusercontent.com')
            && ! $request->hasHeader('Authorization'));

        $this->assertSame($version->id, DownloadLog::sole()->product_version_id);
    }

    public function test_without_a_token_the_app_gets_a_clean_error_not_a_github_page(): void
    {
        $this->makeVersion('1.2.0');

        // the public file link of a private repository is a 404 to anyone without access
        Http::fake(['github.com/xjanova/antix/releases/download/*' => Http::response('Not Found', 404, ['Content-Type' => 'text/plain'])]);

        $response = $this->get('/anti-x/download/1.2.0', ['Accept' => '*/*'])
            ->assertStatus(502)
            ->assertHeaderMissing('Location')
            ->assertJsonPath('success', false);

        $this->assertNoTraceOfGithub($response);
        $this->assertSame(0, DownloadLog::count());
    }

    public function test_an_unknown_version_is_a_json_404_for_the_app_and_the_product_page_for_a_browser(): void
    {
        $this->get('/anti-x/download/9.9.9', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'error' => 'Version not found']);

        // the test client's default Accept is a browser's (text/html, …)
        $this->get('/anti-x/download/9.9.9')
            ->assertRedirect(route('products.show', 'anti-x'))
            ->assertSessionHas('error');
    }

    public function test_head_answers_from_the_database_and_never_touches_github(): void
    {
        $this->makeVersion('1.2.0');

        $response = $this->call('HEAD', '/anti-x/download/1.2.0', [], [], [], ['HTTP_ACCEPT' => '*/*']);

        $response->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Length', (string) strlen(self::ZIP_BYTES));
        $this->assertSame(0, DownloadLog::count());
    }

    public function test_the_download_is_404_while_the_product_is_switched_off(): void
    {
        $this->makeVersion('1.2.0');
        $this->product->update(['is_active' => false]);

        $this->get('/anti-x/download/1.2.0', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'error' => 'Product not found']);
    }

    // ── the cart ────────────────────────────────────────────────────────

    public function test_each_term_goes_into_the_cart_at_its_table_price(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['monthly' => 199, 'yearly' => 1990, 'lifetime' => 9900] as $term => $price) {
            CartItem::query()->delete();

            // the form's own price is ignored — the cart charges config/licenses.php
            $this->post(route('cart.add', $this->product), ['quantity' => 1, 'license_type' => $term, 'price' => 1])
                ->assertSessionHas('success');

            $item = CartItem::sole();
            $this->assertEquals($price, (float) $item->price, $term);
            $this->assertSame($term, json_decode($item->custom_requirements, true)['license_type']);
        }
    }

    public function test_buy_now_goes_straight_to_the_cart(): void
    {
        $this->post(route('cart.add', $this->product), ['license_type' => 'lifetime', 'buy_now' => 1])
            ->assertRedirect(route('cart.index'));

        $this->assertEquals(9900, (float) CartItem::sole()->price);
    }

    public function test_a_purchase_that_names_no_term_is_a_monthly_key_at_the_monthly_price(): void
    {
        // the row's price is the monthly 199 — a yearly key for it would be a discount nobody meant to give
        $this->assertSame('monthly', $this->product->defaultLicenseType());

        $this->freezeSecond();
        app(LicenseService::class)->generateLicensesForOrder($this->paidOrder());

        $license = LicenseKey::where('product_id', $this->product->id)->sole();
        $this->assertSame('monthly', $license->license_type);
        $this->assertTrue($license->expires_at->equalTo(now()->addDays(30)));
    }

    // ── the product page ────────────────────────────────────────────────

    public function test_the_product_page_sells_every_term_and_downloads_without_login(): void
    {
        $html = $this->get('/products/anti-x')->assertOk()->getContent();

        $this->assertSame(3, substr_count($html, 'action="' . route('cart.add', $this->product) . '"'));
        foreach (['monthly', 'yearly', 'lifetime'] as $term) {
            $this->assertSame(1, substr_count($html, 'name="license_type" value="' . $term . '"'), $term);
        }
        $this->assertSame(3, substr_count($html, 'name="buy_now" value="1"'));
        $this->assertStringContainsString('฿199', $html);
        $this->assertStringContainsString('฿1,990', $html);
        $this->assertStringContainsString('฿9,900', $html);
        $this->assertStringContainsString('14 วัน', $html);

        // free download from this site (hero, Free card, closing call to action)
        $this->assertSame(3, substr_count($html, 'href="' . route('anti-x.download') . '"'));
        $this->assertStringNotContainsStringIgnoringCase('github', $html);
        $this->assertStringNotContainsString(route('customer.licenses'), $html);
    }

    public function test_a_buyer_can_buy_another_key_and_find_their_keys(): void
    {
        $user = User::factory()->create();
        $this->paidOrder($user)->update(['status' => 'completed']);

        $html = $this->actingAs($user)->get('/products/anti-x')->assertOk()->getContent();

        $this->assertSame(3, substr_count($html, 'name="buy_now" value="1"'));
        $this->assertStringContainsString('ซื้อ License เพิ่ม', $html);
        $this->assertStringContainsString('href="' . route('customer.licenses') . '"', $html);
    }

    public function test_the_page_shows_the_version_this_site_serves(): void
    {
        $this->makeVersion('1.2.0');

        $this->get('/products/anti-x')->assertOk()->assertSee('Anti X 1.2.0');
    }

    // ── helpers ─────────────────────────────────────────────────────────

    private function migration(): object
    {
        return require database_path('migrations/2026_10_10_200000_register_anti_x_product.php');
    }

    private function setting(): GithubSetting
    {
        return GithubSetting::where('product_id', $this->product->id)->sole();
    }

    private function makeVersion(string $version): ProductVersion
    {
        return ProductVersion::create([
            'product_id' => $this->product->id,
            'version' => $version,
            'github_release_id' => 9001,
            'github_release_url' => 'https://api.github.com/repos/xjanova/antix/releases/assets/802',
            'download_url' => "https://github.com/xjanova/antix/releases/download/v{$version}/AntiX-{$version}-win-x64.zip",
            'download_filename' => "AntiX-{$version}-win-x64.zip",
            'file_size' => strlen(self::ZIP_BYTES),
            'sha256' => self::SHA256,
            'is_active' => true,
            'synced_at' => now(),
        ]);
    }

    private function release(string $version, array $assets, string $body = ''): array
    {
        return [
            'id' => 9001,
            'tag_name' => 'v' . $version,
            'html_url' => "https://github.com/xjanova/antix/releases/tag/v{$version}",
            'body' => $body,
            'assets' => $assets,
        ];
    }

    private function asset(string $version, int $id, string $name, ?string $digest, int $size): array
    {
        return array_filter([
            'id' => $id,
            'name' => $name,
            'size' => $size,
            'url' => "https://api.github.com/repos/xjanova/antix/releases/assets/{$id}",
            'browser_download_url' => "https://github.com/xjanova/antix/releases/download/v{$version}/{$name}",
            'digest' => $digest,
        ], fn ($value) => $value !== null);
    }

    /** not a word of GitHub in any header (name or value: Location, x-github-*, signed links) or JSON body */
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

    private function paidKey(): LicenseKey
    {
        return LicenseKey::create([
            'product_id' => $this->product->id,
            'license_key' => LicenseKey::generateKey(),
            'license_type' => LicenseKey::TYPE_YEARLY,
            'status' => LicenseKey::STATUS_ACTIVE,
            'expires_at' => now()->addYear(),
            'max_activations' => 1,
            'activations' => 0,
        ]);
    }

    private function paidOrder(?User $user = null): Order
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
            'product_name' => 'Anti X',
            'price' => 199,
            'quantity' => 1,
            'subtotal' => 199,
        ]);

        return $order;
    }

    private function registerDevice(string $machine, ?string $hardwareHash): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/anti-x/register-device', array_filter([
            'machine_id' => $this->machineId($machine),
            'machine_name' => 'SRV-' . strtoupper($machine),
            'os_version' => 'Windows Server 2022',
            'app_version' => '1.2.0',
            'hardware_hash' => $hardwareHash,
        ]));
    }

    private function demo(string $machine, ?string $hardwareHash): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/anti-x/demo', array_filter([
            'machine_id' => $this->machineId($machine),
            'hardware_hash' => $hardwareHash,
        ]));
    }

    private function demoCheck(string $machine): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/anti-x/demo/check', [
            'machine_id' => $this->machineId($machine),
        ]);
    }

    private function activate(string $machine, string $key): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/anti-x/activate', [
            'license_key' => $key,
            'machine_id' => $this->machineId($machine),
            'machine_fingerprint' => 'fp-' . $machine,
            'app_version' => '1.2.0',
        ]);
    }

    private function validateKey(string $machine, string $key): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/anti-x/validate', [
            'license_key' => $key,
            'machine_id' => $this->machineId($machine),
        ]);
    }

    private function checkMachine(string $machine): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/anti-x/check-machine', [
            'machine_id' => $this->machineId($machine),
        ]);
    }

    private function deactivate(string $machine, string $key): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/anti-x/deactivate', [
            'license_key' => $key,
            'machine_id' => $this->machineId($machine),
        ]);
    }

    /** each server on its own address, so the unrelated same-IP trial rule stays out of these tests */
    private function fromPc(string $machine): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.' . (ord($machine) - 96)]);
    }

    private function machineId(string $machine): string
    {
        return str_repeat($machine, 40);
    }
}
