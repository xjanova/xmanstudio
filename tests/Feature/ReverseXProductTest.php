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
 * Reverse X (AI-assisted reverse engineering studio for Windows) on the generic product API and the site cart
 *
 *   — the product row and its release setting come from a migration (deploy runs migrate, not seeders);
 *     without the row every call answers PRODUCT_NOT_FOUND and the app is stuck on "ลงทะเบียนไม่สำเร็จ: ไม่พบผลิตภัณฑ์"
 *   — the app (D:\Code\ReverseX, src/ReverseX.Licensing) asks, in this order: register-device → validate (saved key)
 *     → check-machine → demo/check → demo. Free = every analysis page with no key at all, so check-machine must
 *     not hand out a FREE key (the app would take it as "licensed" and never ask for the trial)
 *   — pricing: monthly 199 / yearly 1,990 / lifetime 9,900 from config/licenses.php, bought through the cart
 *   — a 14-day Pro trial, once per hardware (a Windows reinstall is a new machine id, not a new trial)
 *   — update/check: an xman4289.com download URL + sha256 / size / name of the zip, for every tier
 *   — releases live in a PUBLIC repository: read and streamed without a token — never a redirect, and not a
 *     single "github" in any answer the app or the customer gets
 */
class ReverseXProductTest extends TestCase
{
    use RefreshDatabase;

    private const HARDWARE = '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8';

    private const SHA256 = 'bb22cc33dd44ee55ff6600778899aa11bb22cc33dd44ee55ff6600778899aa11';

    private const ZIP_BYTES = "PK\x03\x04reversex-release-package\x00\x01\xfe\xff";

    /** the short-lived link a public release file redirects to — must never reach the app or the customer */
    private const SIGNED_URL = 'https://release-assets.githubusercontent.com/github-production-release-asset/888?X-Amz-Signature=abc';

    /** what the app's updater sends (UpdateService.DownloadClient): JSON Accept, its own agent, no redirects */
    private const UPDATER = ['Accept' => 'application/json', 'User-Agent' => 'ReverseX-Update/1.1.0'];

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->withoutVite();

        // nothing talks to the real GitHub: an unfaked request fails the test, which also proves which
        // paths (the product page, HEAD) never ask GitHub at all
        Http::preventStrayRequests();

        // created by 2026_10_28_200000_register_reverse_x_product, as on production after deploy
        $this->product = Product::where('slug', 'reverse-x')->firstOrFail();
    }

    // ── the product row (migration) ─────────────────────────────────────

    public function test_the_migration_registers_reverse_x_and_its_public_release_repository(): void
    {
        $this->assertSame('Reverse X', $this->product->name);
        $this->assertTrue($this->product->requires_license);
        $this->assertTrue($this->product->is_active);
        $this->assertFalse($this->product->is_coming_soon);
        $this->assertEquals(199, (float) $this->product->price);
        $this->assertSame('ai-automation', $this->product->category->slug);

        $setting = $this->setting();
        $this->assertSame('xjanova/reverse-x', $setting->full_repo_name);
        $this->assertSame('ReverseX-*-win-x64.zip', $setting->asset_pattern);
        $this->assertTrue($setting->is_active);
        $this->assertTrue($setting->auto_sync);
        // the repository is public — no token, and a token never goes into a public migration anyway
        $this->assertNull($setting->github_token_decrypted);
    }

    public function test_running_the_migration_again_keeps_the_owners_edits_and_adds_nothing(): void
    {
        $this->setting()->update(['github_token' => 'github_pat_added_in_the_admin', 'asset_pattern' => 'ReverseX-*.zip']);
        $this->product->update(['price' => 250, 'short_description' => 'edited in the admin']);

        $this->migration()->up();

        $this->assertSame(1, Product::where('slug', 'reverse-x')->count());
        $this->assertSame(1, GithubSetting::where('product_id', $this->product->id)->count());
        $this->assertSame('github_pat_added_in_the_admin', $this->setting()->github_token_decrypted);
        $this->assertSame('ReverseX-*.zip', $this->setting()->asset_pattern);
        $this->assertEquals(250, (float) $this->product->fresh()->price);
        $this->assertSame('edited in the admin', $this->product->fresh()->short_description);
    }

    public function test_rolling_back_never_deletes_a_product_that_has_keys_or_orders(): void
    {
        $key = $this->paidKey();

        $this->migration()->down();
        $this->assertTrue(Product::where('slug', 'reverse-x')->exists());

        // an order without a key yet (paid, keys not generated) holds it too
        $key->delete();
        $this->paidOrder();

        $this->migration()->down();
        $this->assertTrue(Product::where('slug', 'reverse-x')->exists());
        $this->assertTrue(GithubSetting::where('product_id', $this->product->id)->exists());
    }

    public function test_rolling_back_an_unsold_product_removes_it_and_up_brings_it_back(): void
    {
        $this->migration()->down();

        $this->assertFalse(Product::where('slug', 'reverse-x')->exists());
        $this->assertFalse(GithubSetting::where('github_repo', 'reverse-x')->exists());

        $this->migration()->up();

        $product = Product::where('slug', 'reverse-x')->sole();
        $this->assertSame('xjanova/reverse-x', $product->githubSetting->full_repo_name);
    }

    // ── pricing ─────────────────────────────────────────────────────────

    public function test_pricing_sells_three_terms_and_says_where_to_buy(): void
    {
        $this->getJson('/api/v1/product/reverse-x/pricing')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'data' => [
                    'product' => ['name' => 'Reverse X', 'slug' => 'reverse-x'],
                    'plans' => [
                        'monthly' => ['price' => 199, 'currency' => 'THB', 'duration_days' => 30, 'features' => ['all_features', 'standard_support', 'cloud_sync']],
                        'yearly' => ['price' => 1990, 'currency' => 'THB', 'duration_days' => 365, 'features' => ['all_features', 'priority_support', 'cloud_sync', 'priority_updates']],
                        'lifetime' => ['price' => 9900, 'currency' => 'THB', 'duration_days' => null, 'features' => ['all_features', 'priority_support', 'cloud_sync', 'lifetime_updates', 'unlimited_devices']],
                    ],
                    // XmanApi.ProductPageUrl in the app
                    'purchase_url' => url('/products/reverse-x'),
                ],
            ]);
    }

    // ── first start, as the app asks (LicenseService.RefreshAsync) ──────

    public function test_the_apps_first_start_registers_the_pc_and_starts_the_fourteen_day_trial(): void
    {
        $this->freezeSecond();

        // RegisterCoreAsync: registered = success && data.device_status != "blocked"
        $this->registerDevice('a', self::HARDWARE)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.can_start_trial', true)
            ->assertJsonPath('data.trial_info', null);
        $this->assertNotSame(ProductDevice::STATUS_BLOCKED, ProductDevice::where('machine_id', $this->machineId('a'))->sole()->status);

        // CheckMachineCoreAsync: no key yet — not freemium, so no FREE key is handed out
        $this->checkMachine('a')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('has_license', false);
        $this->assertSame(0, LicenseKey::where('product_id', $this->product->id)->count());

        // TrialCoreAsync: demo/check carries the hardware hash, then demo
        $this->demoCheck('a', self::HARDWARE)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.has_used_demo', false)
            ->assertJsonPath('data.can_start_demo', true);

        $response = $this->demo('a', self::HARDWARE)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'เริ่มใช้งาน Trial 14 วันสำเร็จ')
            ->assertJsonPath('data.days_remaining', 14)
            ->assertJsonPath('data.seconds_remaining', 14 * 24 * 3600)
            ->assertJsonPath('data.expires_at', now()->addDays(14)->toISOString());

        $license = LicenseKey::where('license_key', $response->json('data.license_key'))->sole();
        $this->assertSame($this->product->id, $license->product_id);
        $this->assertSame(LicenseKey::TYPE_DEMO, $license->license_type);
        $this->assertTrue($license->expires_at->equalTo(now()->addDays(14)));

        // the next round validates the saved trial key: still the "demo" type the app shows as Trial
        $this->validateKey('a', $license->license_key)
            ->assertOk()
            ->assertJsonPath('is_valid', true)
            ->assertJsonPath('data.license_type', 'demo');
    }

    public function test_when_the_trial_ends_the_app_falls_back_to_free_and_never_gets_a_second_one(): void
    {
        $this->registerDevice('a', self::HARDWARE)->assertOk();
        $key = $this->demo('a', self::HARDWARE)->assertOk()->json('data.license_key');

        $this->travel(14)->days();
        $this->travel(1)->minutes();

        // ValidateCoreAsync → not valid ("expired"); CheckMachineCoreAsync → nothing; the app shows Free (TrialEnded)
        $this->validateKey('a', $key)->assertOk()->assertJsonPath('is_valid', false);
        $this->checkMachine('a')->assertOk()->assertJsonPath('has_license', false);

        // and the server refuses another trial on its own, whatever the app remembers
        $this->demoCheck('a', self::HARDWARE)
            ->assertOk()
            ->assertJsonPath('data.has_used_demo', true)
            ->assertJsonPath('data.can_start_demo', false);
        $this->demo('a', self::HARDWARE)
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'TRIAL_ALREADY_USED');
        $this->assertSame(1, LicenseKey::where('product_id', $this->product->id)->count());

        // told no, not accused of anything — a paid key still activates on this PC
        $device = ProductDevice::where('machine_id', $this->machineId('a'))->sole();
        $this->assertFalse($device->is_suspicious);
        $this->assertNotSame(ProductDevice::STATUS_BLOCKED, $device->status);
    }

    public function test_deleting_the_apps_license_file_long_after_the_trial_does_not_start_another(): void
    {
        // the app asks for the trial once per install (TrialTried in its sealed file): a deleted or
        // edited file starts over as a new install, so this answer is the server's alone
        $this->registerDevice('a', self::HARDWARE)->assertOk();
        $this->demo('a', self::HARDWARE)->assertOk();

        // well past the generic "expired less than 14 days ago" abuse window
        $this->travel(60)->days();

        $this->registerDevice('a', self::HARDWARE)->assertOk()->assertJsonPath('success', true);
        $this->checkMachine('a')->assertOk()->assertJsonPath('has_license', false);
        $this->demoCheck('a', self::HARDWARE)->assertOk()->assertJsonPath('data.can_start_demo', false);
        $this->demo('a', self::HARDWARE)
            ->assertForbidden()
            ->assertJsonPath('error_code', 'TRIAL_ALREADY_USED');

        // a VM with no SMBIOS sends no hardware hash — still one trial for the machine
        $this->demo('v', null)->assertOk();
        $this->travel(15)->days();
        $this->demo('v', null)->assertForbidden()->assertJsonPath('error_code', 'TRIAL_ALREADY_USED');

        $this->assertSame(2, LicenseKey::where('product_id', $this->product->id)->where('license_type', 'demo')->count());
        $this->assertSame(0, ProductDevice::where('product_id', $this->product->id)->where('is_suspicious', true)->count());
    }

    public function test_a_running_trial_is_found_again_after_the_license_file_is_lost(): void
    {
        $this->freezeSecond();
        $this->registerDevice('a', self::HARDWARE)->assertOk();
        $key = $this->demo('a', self::HARDWARE)->assertOk()->json('data.license_key');

        $this->travel(3)->days();

        // CheckMachineCoreAsync takes the running trial back, with its own end date — no new trial
        $this->checkMachine('a')
            ->assertOk()
            ->assertJsonPath('has_license', true)
            ->assertJsonPath('data.license_key', $key)
            ->assertJsonPath('data.license_type', 'demo');
        $this->demoCheck('a', self::HARDWARE)->assertOk()->assertJsonPath('data.can_start_demo', false);
        $this->assertSame(1, LicenseKey::where('product_id', $this->product->id)->count());
    }

    public function test_reinstalling_windows_does_not_give_a_second_trial(): void
    {
        $this->registerDevice('a', self::HARDWARE)->assertOk();
        $this->demo('a', self::HARDWARE)->assertOk();

        // a reinstall: new MachineGuid → new machine id, same SMBIOS → same hardware hash
        $this->registerDevice('b', self::HARDWARE)->assertOk();

        $this->demoCheck('b', self::HARDWARE)
            ->assertOk()
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

        // ActivateAsync upper-cases the key; the server takes either
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
            ->assertJsonPath('data.license_key', $key->license_key)
            ->assertJsonPath('data.license_type', 'yearly');

        // one key, one PC — the app shows "used on another PC"
        $this->activate('b', $key->license_key)
            ->assertForbidden()
            ->assertJsonPath('error_code', 'ALREADY_ACTIVATED_OTHER_DEVICE');

        $this->deactivate('a', $key->license_key)->assertOk()->assertJsonPath('success', true);
        $this->checkMachine('a')->assertOk()->assertJsonPath('has_license', false);

        $this->activate('b', $key->license_key)->assertOk();
        $this->validateKey('b', $key->license_key)->assertOk()->assertJsonPath('is_valid', true);
    }

    public function test_an_unknown_key_is_refused_with_the_code_the_app_maps(): void
    {
        $this->activate('a', 'RVX-NOPE-NOPE-NOPE')
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'INVALID_LICENSE');
    }

    // ── update check (UpdateService.CheckAsync) ─────────────────────────

    public function test_update_check_points_the_app_at_this_site_with_the_zips_details(): void
    {
        Http::fake([
            'api.github.com/repos/xjanova/reverse-x/releases/latest' => Http::response($this->release('1.2.0', [
                $this->asset('1.2.0', 901, 'ReverseX-1.2.0-win-x64.zip.sig', 'sha256:' . str_repeat('c', 64), 512),
                $this->asset('1.2.0', 902, 'ReverseX-1.2.0-win-x64.zip', 'sha256:' . strtoupper(self::SHA256), 83886080),
                $this->asset('1.2.0', 903, 'ReverseX-1.2.0-win-x64.zip.sha256', 'sha256:' . str_repeat('d', 64), 96),
            ], "### ใหม่\n- หน้า APK อ่านใบรับรองได้\n\n**Full Changelog**: https://github.com/xjanova/reverse-x/compare/v1.1.0...v1.2.0")),
        ]);

        $response = $this->getJson('/api/v1/product/reverse-x/update/check?current_version=1.1.0')
            ->assertOk()
            ->assertExactJson([
                'has_update' => true,
                'latest_version' => '1.2.0',
                'download_url' => url('/reverse-x/download/1.2.0'),
                'changelog' => "### ใหม่\n- หน้า APK อ่านใบรับรองได้",
                'sha256' => self::SHA256,
                'file_size' => 83886080,
                'filename' => 'ReverseX-1.2.0-win-x64.zip',
            ]);

        // this site's own route (production: https://xman4289.com/reverse-x/download/1.2.0 — XmanApi.IsXmanUrl), never a file host
        $this->assertSame(route('reverse-x.download', ['version' => '1.2.0']), $response->json('download_url'));
        $this->assertStringNotContainsStringIgnoringCase('github', $response->getContent());

        // a public repository is read without a token
        Http::assertSent(fn ($request) => $request->url() === 'https://api.github.com/repos/xjanova/reverse-x/releases/latest'
            && ! $request->hasHeader('Authorization'));
    }

    public function test_an_app_that_is_up_to_date_gets_no_download(): void
    {
        $this->makeVersion('1.2.0');

        $this->getJson('/api/v1/product/reverse-x/update/check?current_version=1.2.0')
            ->assertOk()
            ->assertExactJson([
                'has_update' => false,
                'latest_version' => '1.2.0',
                'download_url' => '',
                'changelog' => '',
                'sha256' => self::SHA256,
                'file_size' => strlen(self::ZIP_BYTES),
                'filename' => 'ReverseX-1.2.0-win-x64.zip',
            ]);
    }

    public function test_before_the_first_release_the_answer_keeps_its_shape(): void
    {
        Http::fake(['api.github.com/*' => Http::response(['message' => 'Not Found'], 404)]);

        $response = $this->getJson('/api/v1/product/reverse-x/update/check?current_version=1.0.0')
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

    // ── the download (UpdateService.DownloadAsync) ──────────────────────

    public function test_the_zip_is_streamed_to_the_updater_with_no_redirect_and_no_trace_of_github(): void
    {
        $version = $this->makeVersion('1.2.0');

        Http::fake([
            'github.com/xjanova/reverse-x/releases/download/*' => Http::response('', 302, ['Location' => self::SIGNED_URL]),
            'release-assets.githubusercontent.com/*' => fn () => Http::response(self::ZIP_BYTES, 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => (string) strlen(self::ZIP_BYTES),
                'ETag' => '"0x8DCB7C0FFEE"',
                'x-github-request-id' => 'ABCD:1234',
            ]),
        ]);

        $response = $this->get('/reverse-x/download/1.2.0', self::UPDATER);

        // the updater refuses a 3xx, text/* or JSON, and a Content-Length other than file_size from update/check
        $response->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Type', 'application/octet-stream')
            ->assertHeader('Content-Length', (string) strlen(self::ZIP_BYTES));
        $this->assertInstanceOf(StreamedResponse::class, $response->baseResponse);
        $this->assertStringContainsString('ReverseX-1.2.0-win-x64.zip', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::ZIP_BYTES, $response->streamedContent());
        $this->assertNoTraceOfGithub($response);

        // the public file link, fetched by the server without any token
        Http::assertSent(fn ($request) => str_contains($request->url(), 'github.com/xjanova/reverse-x/releases/download/v1.2.0/ReverseX-1.2.0-win-x64.zip')
            && ! $request->hasHeader('Authorization'));

        $this->assertSame($version->id, DownloadLog::sole()->product_version_id);
    }

    public function test_head_answers_from_the_database_and_never_touches_github(): void
    {
        $this->makeVersion('1.2.0');

        $response = $this->call('HEAD', '/reverse-x/download/1.2.0', [], [], [], ['HTTP_ACCEPT' => '*/*']);

        $response->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Length', (string) strlen(self::ZIP_BYTES))
            ->assertHeader('ETag', '"' . self::SHA256 . '"');
        $this->assertSame(0, DownloadLog::count());
    }

    public function test_when_the_file_cannot_be_fetched_the_app_gets_a_clean_error_not_a_github_page(): void
    {
        $this->makeVersion('1.2.0');

        Http::fake(['github.com/xjanova/reverse-x/releases/download/*' => Http::response('Not Found', 404, ['Content-Type' => 'text/plain'])]);

        $response = $this->get('/reverse-x/download/1.2.0', self::UPDATER)
            ->assertStatus(502)
            ->assertHeaderMissing('Location')
            ->assertJsonPath('success', false);

        $this->assertNoTraceOfGithub($response);
        $this->assertSame(0, DownloadLog::count());
    }

    public function test_an_unknown_version_is_a_json_404_for_the_app_and_the_product_page_for_a_browser(): void
    {
        $this->get('/reverse-x/download/9.9.9', self::UPDATER)
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'error' => 'Version not found']);

        // the test client's default Accept is a browser's (text/html, …)
        $this->get('/reverse-x/download/9.9.9')
            ->assertRedirect(route('products.show', 'reverse-x'))
            ->assertSessionHas('error');
    }

    public function test_the_download_is_404_while_the_product_is_switched_off(): void
    {
        $this->makeVersion('1.2.0');
        $this->product->update(['is_active' => false]);

        $this->get('/reverse-x/download/1.2.0', self::UPDATER)
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
        $html = $this->get('/products/reverse-x')->assertOk()->getContent();

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
        $this->assertSame(3, substr_count($html, 'href="' . route('reverse-x.download') . '"'));
        $this->assertStringNotContainsStringIgnoringCase('github', $html);
        $this->assertStringNotContainsString(route('customer.licenses'), $html);
    }

    public function test_the_page_shows_the_apps_real_screens_and_every_picture_exists(): void
    {
        $html = $this->get('/products/reverse-x')->assertOk()->getContent();

        preg_match_all('#' . preg_quote(asset('images/products/reverse-x') . '/', '#') . '([a-z0-9-]+\.webp)#', $html, $m);
        $files = array_unique($m[1]);

        foreach (['binary.webp', 'electron.webp', 'ai.webp', 'home.webp', 'logo.webp'] as $shot) {
            $this->assertContains($shot, $files);
        }
        foreach ($files as $file) {
            $this->assertFileExists(public_path('images/products/reverse-x/' . $file));
        }

        // the card picture every listing shows until the admin uploads one
        $this->assertSame(asset('artwork/product/reverse-x.webp'), $this->product->artwork_url);
    }

    public function test_a_buyer_can_buy_another_key_and_find_their_keys(): void
    {
        $user = User::factory()->create();
        $this->paidOrder($user)->update(['status' => 'completed']);

        $html = $this->actingAs($user)->get('/products/reverse-x')->assertOk()->getContent();

        $this->assertSame(3, substr_count($html, 'name="buy_now" value="1"'));
        $this->assertStringContainsString('ซื้อ License เพิ่ม', $html);
        $this->assertStringContainsString('href="' . route('customer.licenses') . '"', $html);
    }

    public function test_the_page_shows_the_version_this_site_serves(): void
    {
        $this->makeVersion('1.2.0');

        $this->get('/products/reverse-x')->assertOk()->assertSee('Reverse X 1.2.0');
    }

    public function test_coming_soon_turns_every_download_button_into_a_label(): void
    {
        $this->product->update(['is_coming_soon' => true]);

        $html = $this->get('/products/reverse-x')->assertOk()->getContent();

        $this->assertStringNotContainsString('href="' . route('reverse-x.download') . '"', $html);
        $this->assertSame(3, substr_count($html, 'data-coming-soon'));
    }

    // ── helpers ─────────────────────────────────────────────────────────

    private function migration(): object
    {
        return require database_path('migrations/2026_10_28_200000_register_reverse_x_product.php');
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
            'github_release_id' => 9002,
            'github_release_url' => 'https://api.github.com/repos/xjanova/reverse-x/releases/assets/902',
            'download_url' => "https://github.com/xjanova/reverse-x/releases/download/v{$version}/ReverseX-{$version}-win-x64.zip",
            'download_filename' => "ReverseX-{$version}-win-x64.zip",
            'file_size' => strlen(self::ZIP_BYTES),
            'sha256' => self::SHA256,
            'is_active' => true,
            'synced_at' => now(),
        ]);
    }

    private function release(string $version, array $assets, string $body = ''): array
    {
        return [
            'id' => 9002,
            'tag_name' => 'v' . $version,
            'html_url' => "https://github.com/xjanova/reverse-x/releases/tag/v{$version}",
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
            'url' => "https://api.github.com/repos/xjanova/reverse-x/releases/assets/{$id}",
            'browser_download_url' => "https://github.com/xjanova/reverse-x/releases/download/v{$version}/{$name}",
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
            'product_name' => 'Reverse X',
            'price' => 199,
            'quantity' => 1,
            'subtotal' => 199,
        ]);

        return $order;
    }

    /** the body LicenseService.RegisterCoreAsync sends */
    private function registerDevice(string $machine, ?string $hardwareHash): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/reverse-x/register-device', array_filter([
            'machine_id' => $this->machineId($machine),
            'machine_name' => 'PC-' . strtoupper($machine),
            'os_version' => 'Microsoft Windows NT 10.0.26100.0',
            'app_version' => '1.1.0',
            'hardware_hash' => $hardwareHash,
        ]));
    }

    /** LicenseService.TrialCoreAsync */
    private function demo(string $machine, ?string $hardwareHash): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/reverse-x/demo', array_filter([
            'machine_id' => $this->machineId($machine),
            'machine_name' => 'PC-' . strtoupper($machine),
            'hardware_hash' => $hardwareHash,
            'app_version' => '1.1.0',
        ]));
    }

    private function demoCheck(string $machine, ?string $hardwareHash): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/reverse-x/demo/check', array_filter([
            'machine_id' => $this->machineId($machine),
            'hardware_hash' => $hardwareHash,
        ]));
    }

    /** LicenseService.ActivateAsync */
    private function activate(string $machine, string $key): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/reverse-x/activate', [
            'license_key' => $key,
            'machine_id' => $this->machineId($machine),
            'machine_fingerprint' => self::HARDWARE,
            'machine_name' => 'PC-' . strtoupper($machine),
            'app_version' => '1.1.0',
        ]);
    }

    private function validateKey(string $machine, string $key): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/reverse-x/validate', [
            'license_key' => $key,
            'machine_id' => $this->machineId($machine),
            'app_version' => '1.1.0',
        ]);
    }

    private function checkMachine(string $machine): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/reverse-x/check-machine', [
            'machine_id' => $this->machineId($machine),
        ]);
    }

    private function deactivate(string $machine, string $key): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/reverse-x/deactivate', [
            'license_key' => $key,
            'machine_id' => $this->machineId($machine),
        ]);
    }

    /** each PC on its own address, so the unrelated same-IP trial rule stays out of these tests */
    private function fromPc(string $machine): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.' . (ord($machine) - 96)]);
    }

    /** the app sends a 64-hex SHA-256 (MachineIdentity) */
    private function machineId(string $machine): string
    {
        return str_repeat($machine, 64);
    }
}
