<?php

namespace Tests\Feature;

use App\Models\AvatarPack;
use App\Models\Category;
use App\Models\DownloadLog;
use App\Models\GithubSetting;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\User;
use App\Services\GithubReleaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * GigGok — แอป Android ฟรี (เลขาสาว 3D) ที่ขายชุดตัวมายด์แยก
 *
 *   — migration ลงทะเบียนสินค้าฟรีพร้อม release source (xjanova/videogirl, giggok-*.apk) รันซ้ำได้ ไม่ทับของที่มีอยู่
 *   — check-machine แจก FREE license ให้ทุกเครื่องแบบ LocalVPN (LocalVPN เองต้องตอบเหมือนเดิมทุก byte)
 *   — APK โหลดได้ทุกคนจาก xman4289.com ไม่ผ่าน GitHub · update/check ชี้มาที่เวอร์ชันนั้นเป๊ะ ๆ คู่กับ sha256
 *   — /giggok/link ผูก license ของเครื่องเข้ากับบัญชี ชุดที่ซื้อบนเว็บจึงขึ้นในแอป (PackController อ่าน user_id)
 */
class GigGokAppTest extends TestCase
{
    use RefreshDatabase;

    private const FILE_BYTES = "PK\x03\x04android-package-from-the-cdn\x00\xfe";

    private const SIGNED_URL = 'https://release-assets.githubusercontent.com/github-production-release-asset/9?X-Amz-Signature=abc';

    /** แอปส่งรหัสเครื่องเป็น SHA-256 hex เสมอ (lib/license/mind_license.dart) */
    private const MACHINE = '9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08';

    private const OTHER_MACHINE = '60303ae22b998861bce3b28f33eec1be758a213c86c93c076dbe9f558c11c752';

    private const MIGRATION = 'migrations/2026_10_05_100000_register_giggok_product.php';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->withoutVite();

        // ห้ามคุยกับ GitHub จริง — คำขอที่ไม่ได้ fake ไว้ทำให้เทสต์ล้มทันที
        Http::preventStrayRequests();

        config(['packs.app_product_slug' => 'giggok']);
    }

    // ── migration ─────────────────────────────────────────────────────

    public function test_the_migration_registers_giggok_as_a_free_app_with_its_apk_release_source(): void
    {
        $product = $this->giggok();

        $this->assertSame('GigGok', $product->name);
        $this->assertSame(0.0, (float) $product->price);
        $this->assertTrue($product->requires_license);
        $this->assertTrue($product->is_active);
        $this->assertFalse($product->is_coming_soon);
        $this->assertSame('mobile-tools', $product->category->slug);
        $this->assertNotContains($product->category->slug, Category::APP_ONLY_SLUGS);
        $this->assertSame(
            'เลขาสาว 3D ผู้ช่วยส่วนตัวบนมือถือ คุยได้ พูดได้ รับสายแทนได้ คิดด้วยสมองในเครื่องเป็นหลัก ข้อมูลไม่ออกนอกเครื่อง',
            $product->short_description
        );
        $this->assertCount(8, $product->features);
        $this->assertContains('สตูดิโอวิดีโอ: แชร์จอเข้าวิดีโอคอล ฉากเขียวสำหรับไลฟ์ อัดคลิปลงเครื่อง', $product->features);
        $this->assertContains('ร้านชุดตัวมายด์', $product->features);
        $this->assertStringNotContainsStringIgnoringCase('github', $product->description . json_encode($product->features));

        $setting = $product->githubSetting;
        $this->assertSame('xjanova/videogirl', $setting->full_repo_name);
        $this->assertSame('giggok-*.apk', $setting->asset_pattern);
        $this->assertTrue($setting->is_active);
        $this->assertTrue($setting->auto_sync);
        $this->assertNull($setting->github_token_decrypted);

        // หมวดที่เปิดให้เห็น — แอปขึ้นในรายการสินค้าบนเว็บ (ชุดตัวมายด์ไม่ขึ้น)
        $this->get('/products?search=GigGok')->assertOk()->assertSee(route('products.show', 'giggok'), false);
    }

    public function test_running_the_migration_again_changes_nothing(): void
    {
        $migration = require database_path(self::MIGRATION);

        $migration->up();
        $migration->up();

        $product = $this->giggok();
        $this->assertSame(1, GithubSetting::where('product_id', $product->id)->count());
    }

    public function test_a_giggok_made_in_admin_keeps_its_row_and_only_gets_the_release_source(): void
    {
        // แบบที่ production อาจมี: สร้างในหน้า admin ก่อน deploy นี้ ไม่มี GitHub setting
        $this->giggok()->delete();
        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'GigGok',
            'slug' => 'giggok',
            'description' => 'เขียนเองในหน้า admin',
            'price' => 0,
            'stock' => 0,
            'requires_license' => true,
            'is_active' => true,
        ]);

        (require database_path(self::MIGRATION))->up();

        $this->assertSame($product->id, $this->giggok()->id);
        $this->assertSame('เขียนเองในหน้า admin', $this->giggok()->description);
        $this->assertSame('software', $this->giggok()->category->slug);
        $this->assertSame('giggok-*.apk', $this->giggok()->githubSetting->asset_pattern);
    }

    public function test_rolling_back_never_takes_the_licenses_of_devices_with_it(): void
    {
        $migration = require database_path(self::MIGRATION);
        $key = $this->checkMachine()->json('data.license_key');

        $migration->down();

        $this->assertNotNull(Product::where('slug', 'giggok')->first());
        $this->assertNotNull(LicenseKey::where('license_key', $key)->first());

        // ยังไม่มีเครื่องไหนได้ license = ลบออกได้หมดจด
        LicenseKey::where('license_key', $key)->forceDelete();
        $productId = $this->giggok()->id;

        $migration->down();

        $this->assertNull(Product::where('slug', 'giggok')->first());
        $this->assertSame(0, GithubSetting::where('product_id', $productId)->count());
    }

    public function test_sync_takes_the_apk_not_the_debug_symbols_or_the_checksums(): void
    {
        $product = $this->giggok();
        $digest = str_repeat('ab', 32);

        // ลำดับเดียวกับ release จริง: ไฟล์แรกไม่ใช่ APK — pattern ที่หาไม่เจอจะถอยไปหยิบตัวนี้
        $version = app(GithubReleaseService::class)->syncLatestRelease($product, [
            'id' => 77,
            'tag_name' => 'v1.2.0',
            'html_url' => 'https://github.com/xjanova/videogirl/releases/tag/v1.2.0',
            'body' => 'แก้เสียงหาย',
            'assets' => [
                $this->asset(1, 'debug-symbols-1.2.0.zip', 'sha256:' . str_repeat('cd', 32)),
                $this->asset(2, 'SHA256SUMS.txt', null),
                $this->asset(3, 'giggok-1.2.0.apk', 'sha256:' . $digest),
            ],
        ]);

        $this->assertSame('giggok-1.2.0.apk', $version->download_filename);
        $this->assertSame('https://api.github.com/repos/xjanova/videogirl/releases/assets/3', $version->github_release_url);
        $this->assertSame($digest, $version->sha256);
    }

    // ── check-machine ─────────────────────────────────────────────────

    public function test_check_machine_gives_a_new_device_a_free_license_and_the_same_key_after(): void
    {
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
                // ทั้งแอปฟรี ไม่ใช่ชุดทดลอง ('trial_mode')
                'features' => ['all_features', 'pack_store', 'in_app_updates'],
            ],
        ]);

        // เปิดแอปรอบถัดไป / ลงแอปใหม่บนเครื่องเดิม = คีย์เดิม ไม่ใช่คีย์ใหม่ทุกครั้ง
        $this->checkMachine()
            ->assertOk()
            ->assertJsonPath('has_license', true)
            ->assertJsonPath('data.license_key', $key)
            ->assertJsonPath('data.license_type', 'free')
            ->assertJsonPath('data.features', ['all_features', 'pack_store', 'in_app_updates']);

        $license = LicenseKey::where('product_id', $this->giggok()->id)->sole();
        $this->assertSame(self::MACHINE, $license->machine_id);
        // คีย์ของเครื่อง ไม่ใช่ของคน — ยังไม่ผูกกับบัญชีใด
        $this->assertNull($license->user_id);

        $other = $this->checkMachine(self::OTHER_MACHINE)->assertOk()->json('data.license_key');
        $this->assertNotSame($key, $other);
    }

    public function test_localvpn_check_machine_answers_exactly_as_before(): void
    {
        $response = $this->postJson('/api/v1/product/localvpn/check-machine', ['machine_id' => self::MACHINE])->assertOk();

        $response->assertExactJson([
            'success' => true,
            'has_license' => true,
            'data' => [
                'license_key' => $response->json('data.license_key'),
                'license_type' => 'free',
                'status' => 'active',
                'expires_at' => null,
                'days_remaining' => null,
                'features' => ['basic_features', 'trial_mode'],
            ],
        ]);
        $this->assertMatchesRegularExpression('/^FREE-[A-Z0-9]{20}$/', $response->json('data.license_key'));
    }

    public function test_a_paid_app_still_gets_no_license_from_check_machine(): void
    {
        $this->postJson('/api/v1/product/tping/check-machine', ['machine_id' => self::MACHINE])
            ->assertOk()
            ->assertJsonPath('has_license', false);

        $this->assertSame(0, LicenseKey::where('machine_id', self::MACHINE)->count());
    }

    // ── APK ───────────────────────────────────────────────────────────

    /**
     * ตัวอัปเดตในแอปโหลดเองโดยไม่มี session — ลิงก์ต้องเป็นหน้าโหลดสาธารณะของเว็บเรา
     * ระบุเวอร์ชันเป๊ะ ๆ คู่กับ sha256 ของเวอร์ชันนั้น (ไม่ใช่ /download/{slug} ที่ต้องล็อกอิน + ซื้อ และไม่ใช่ GitHub)
     */
    public function test_update_check_hands_the_app_its_exact_apk_on_this_site(): void
    {
        $version = $this->release('1.2.0', sha256: str_repeat('ef', 32));
        $this->fakeGithub('1.2.0');

        $json = $this->getJson('/api/v1/product/giggok/update/check?current_version=1.0.0')
            ->assertOk()
            ->assertJsonPath('has_update', true)
            ->assertJsonPath('latest_version', '1.2.0')
            ->assertJsonPath('download_url', url('/giggok/download/1.2.0'))
            ->assertJsonPath('sha256', str_repeat('ef', 32))
            ->assertJsonPath('filename', 'giggok-1.2.0.apk')
            ->assertJsonPath('file_size', 83886080)
            ->json();

        $this->assertStringNotContainsStringIgnoringCase('github', json_encode($json));

        // แบบเดียวกับตัวอัปเดตในแอป: ไม่ล็อกอิน ไม่ส่ง Accept ของเบราว์เซอร์
        $response = $this->get($json['download_url'], ['Accept' => '*/*'])
            ->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Type', 'application/vnd.android.package-archive');

        $this->assertSame(self::FILE_BYTES, $response->streamedContent());
        $this->assertSame($version->id, DownloadLog::sole()->product_version_id);
    }

    public function test_an_app_already_on_the_latest_version_is_told_so(): void
    {
        $this->release('1.2.0');
        $this->fakeGithub('1.2.0');

        $this->getJson('/api/v1/product/giggok/update/check?current_version=1.2.0')
            ->assertOk()
            ->assertJsonPath('has_update', false)
            ->assertJsonPath('download_url', '');
    }

    public function test_anyone_can_download_the_latest_apk_from_this_site(): void
    {
        $this->release('1.2.0');
        $this->fakeGithub('1.2.0');

        $response = $this->get('/giggok/download')
            ->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Type', 'application/vnd.android.package-archive')
            ->assertHeader('Content-Length', (string) strlen(self::FILE_BYTES));

        $this->assertStringContainsString('giggok-1.2.0.apk', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::FILE_BYTES, $response->streamedContent());
        $this->assertNoTraceOfGithub($response);
    }

    public function test_a_versioned_download_is_that_exact_file_even_after_a_newer_release(): void
    {
        // sync ปิดเวอร์ชันเก่าเมื่อตัวใหม่เข้ามา — แอปที่เพิ่งได้ sha256 ของตัวเก่าต้องยังโหลดตัวนั้นได้
        $this->release('1.1.0')->update(['is_active' => false]);
        $this->release('1.2.0');
        $this->fakeGithub('1.2.0');

        $response = $this->get('/giggok/download/1.1.0')->assertOk();
        $this->assertStringContainsString('giggok-1.1.0.apk', $response->headers->get('Content-Disposition'));

        $this->get('/giggok/download/9.9.9', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'error' => 'Version not found']);
    }

    public function test_before_the_first_release_a_browser_goes_back_to_the_giggok_page(): void
    {
        Http::fake(['api.github.com/repos/xjanova/videogirl/releases/latest' => Http::response(['message' => 'Not Found'], 404)]);

        $this->get('/giggok/download')
            ->assertRedirect(route('products.show', 'giggok'))
            ->assertSessionHas('error');
    }

    public function test_the_apk_is_not_served_while_the_app_is_off_sale(): void
    {
        $this->release('1.2.0');
        $this->giggok()->update(['is_active' => false]);

        $this->get('/giggok/download', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'error' => 'Product not found']);
    }

    // ── product page ──────────────────────────────────────────────────

    public function test_the_product_page_offers_the_apk_to_everyone_and_the_link_page(): void
    {
        $this->release('1.2.0', changelog: 'เสียงไม่หายแล้ว');

        $html = $this->get('/products/giggok')
            ->assertOk()
            ->assertViewIs('products.giggok')
            ->getContent();

        $this->assertStringContainsString('href="' . route('giggok.download') . '"', $html);
        $this->assertStringContainsString('ดาวน์โหลด APK', $html);
        $this->assertStringContainsString('v1.2.0', $html);
        $this->assertStringContainsString('เสียงไม่หายแล้ว', $html);
        $this->assertStringContainsString('href="' . route('giggok.link') . '"', $html);
        $this->assertStringContainsString('ร้านชุดตัวมายด์', $html);
        // แอปฟรี — ไม่มีตะกร้า ฿0 และ "ซื้อ License" ของหน้าสินค้าทั่วไป
        $this->assertStringNotContainsString(route('cart.add', $this->giggok()), $html);
        $this->assertStringNotContainsStringIgnoringCase('github', $html);

        // เปิดหน้านี้ไม่ถาม GitHub
        Http::assertNothingSent();
    }

    // ── /giggok/link ──────────────────────────────────────────────────

    public function test_a_guest_signs_in_before_linking(): void
    {
        $this->get('/giggok/link')->assertRedirect(route('login'));
        $this->post('/giggok/link', ['license_key' => 'FREE-WHATEVER'])->assertRedirect(route('login'));
    }

    public function test_a_signed_in_user_links_the_device_key_to_their_account(): void
    {
        $user = User::factory()->create();
        $key = $this->checkMachine()->json('data.license_key');

        $this->actingAs($user)->get(route('giggok.link'))
            ->assertOk()
            ->assertSee('ยังไม่มีเครื่องที่ผูกไว้');

        $this->actingAs($user)
            ->post(route('giggok.link.store'), ['license_key' => $key])
            ->assertRedirect(route('giggok.link'))
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        $this->assertSame($user->id, (int) LicenseKey::where('license_key', $key)->sole()->user_id);

        // หน้าเดิมแสดงเครื่องที่ผูกแล้ว (คีย์ปิดบางส่วน) และพากลับหน้า GigGok ได้
        $this->actingAs($user)->get(route('giggok.link'))
            ->assertOk()
            ->assertSee(substr($key, 0, 9) . '••••' . substr($key, -4))
            ->assertDontSee($key)
            ->assertSee('href="' . route('products.show', 'giggok') . '"', false);
    }

    public function test_a_key_typed_in_lower_case_with_spaces_still_links(): void
    {
        $user = User::factory()->create();
        $key = $this->checkMachine()->json('data.license_key');

        $this->actingAs($user)
            ->post(route('giggok.link.store'), ['license_key' => '  ' . strtolower($key) . ' '])
            ->assertSessionHasNoErrors();

        $this->assertSame($user->id, (int) LicenseKey::where('license_key', $key)->sole()->user_id);
    }

    public function test_linking_your_own_key_again_changes_nothing(): void
    {
        $user = User::factory()->create();
        $key = $this->checkMachine()->json('data.license_key');

        foreach ([1, 2] as $attempt) {
            $this->actingAs($user)
                ->post(route('giggok.link.store'), ['license_key' => $key])
                ->assertRedirect(route('giggok.link'))
                ->assertSessionHas('success')
                ->assertSessionHasNoErrors();
        }

        $this->assertSame($user->id, (int) LicenseKey::where('license_key', $key)->sole()->user_id);
    }

    public function test_a_key_already_on_someone_elses_account_stays_theirs(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $key = $this->checkMachine()->json('data.license_key');
        LicenseKey::where('license_key', $key)->update(['user_id' => $owner->id]);

        $this->actingAs($intruder)
            ->post(route('giggok.link.store'), ['license_key' => $key])
            ->assertRedirect(route('giggok.link'))
            ->assertSessionHasErrors('license_key');

        $this->assertSame($owner->id, (int) LicenseKey::where('license_key', $key)->sole()->user_id);
    }

    public function test_an_unknown_expired_revoked_or_other_product_key_is_refused(): void
    {
        $user = User::factory()->create();
        $giggok = $this->giggok();
        $tping = Product::where('slug', 'tping')->sole();

        $keys = [
            'FREE-DOESNOTEXIST0000000',
            $this->license($giggok, 'FREE-EXPIRED00000000000', ['expires_at' => now()->subDay()])->license_key,
            $this->license($giggok, 'FREE-REVOKED00000000000', ['status' => LicenseKey::STATUS_REVOKED])->license_key,
            // คีย์ของสินค้าอื่น (หรือของชุดตัวมายด์) ไม่ใช่คีย์ของเครื่อง
            $this->license($tping, 'TPG-AAAA-BBBB-CCCC')->license_key,
        ];

        foreach ($keys as $key) {
            $this->actingAs($user)
                ->post(route('giggok.link.store'), ['license_key' => $key])
                ->assertRedirect(route('giggok.link'))
                ->assertSessionHasErrors('license_key');
        }

        $this->assertSame(0, LicenseKey::where('user_id', $user->id)->count());
    }

    public function test_the_link_form_is_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user)
                ->post(route('giggok.link.store'), ['license_key' => 'FREE-NOPE' . $i])
                ->assertRedirect(route('giggok.link'));
        }

        $this->actingAs($user)
            ->post(route('giggok.link.store'), ['license_key' => 'FREE-NOPE'])
            ->assertStatus(429);
    }

    /**
     * ทั้งหมดนี้มีไว้เพื่อสิ่งนี้: ซื้อชุดบนเว็บ → ผูกเครื่อง → แอปเห็นว่าเป็นของเรา
     */
    public function test_a_pack_bought_on_the_web_shows_up_in_the_app_once_the_device_is_linked(): void
    {
        $user = User::factory()->create();
        $key = $this->checkMachine()->json('data.license_key');

        $packCategory = Category::firstOrCreate(['slug' => 'giggok-packs'], ['name' => 'GigGok', 'description' => 'Avatar packs']);
        $packProduct = Product::create([
            'category_id' => $packCategory->id,
            'name' => 'Nana office',
            'slug' => 'pack-nana-office',
            'description' => 'x',
            'price' => 149,
            'stock' => 0,
            'is_active' => true,
        ]);
        AvatarPack::create([
            'product_id' => $packProduct->id,
            'pack_id' => 'nana-office',
            'kind' => AvatarPack::KIND_OUTFIT,
            'name_en' => 'Nana office',
            'is_active' => true,
        ]);
        // ผลของการซื้อบนเว็บ: license ของชุดผูกกับบัญชีของผู้ซื้อ
        $this->license($packProduct, 'PACK-NANA-0001', ['user_id' => $user->id, 'license_type' => 'lifetime']);

        // ก่อนผูก: คีย์ของเครื่องพิสูจน์ได้แค่เครื่อง ยังไม่รู้ว่าเป็นของใคร
        $this->withToken($key)->getJson('/api/packs/mine')->assertOk()->assertExactJson(['owned' => []]);

        $this->actingAs($user)->post(route('giggok.link.store'), ['license_key' => $key])->assertSessionHasNoErrors();

        $this->withToken($key)->getJson('/api/packs/mine')->assertOk()->assertExactJson(['owned' => ['nana-office']]);
    }

    // ── helpers ───────────────────────────────────────────────────────

    private function giggok(): Product
    {
        return Product::where('slug', 'giggok')->sole();
    }

    private function checkMachine(string $machine = self::MACHINE): TestResponse
    {
        // แบบเดียวกับที่แอปส่ง: รหัสเครื่องหลัก + Widevine / ANDROID_ID ที่แฮชแล้ว
        return $this->postJson('/api/v1/product/giggok/check-machine', [
            'machine_id' => $machine,
            'drm_id' => $machine,
            'android_id' => strrev($machine),
        ]);
    }

    private function license(Product $product, string $key, array $extra = []): LicenseKey
    {
        return LicenseKey::create(array_merge([
            'product_id' => $product->id,
            'license_key' => $key,
            'status' => LicenseKey::STATUS_ACTIVE,
            'license_type' => 'free',
        ], $extra));
    }

    /** เวอร์ชันที่ sync มาจาก GitHub — ลิงก์ใน DB ชี้ไป GitHub เสมอ ลูกค้าต้องไม่เห็น */
    private function release(string $version, ?string $sha256 = null, ?string $changelog = null): ProductVersion
    {
        $filename = "giggok-{$version}.apk";

        return ProductVersion::create([
            'product_id' => $this->giggok()->id,
            'version' => $version,
            'github_release_id' => 1,
            'github_release_url' => 'https://api.github.com/repos/xjanova/videogirl/releases/assets/9',
            'download_url' => "https://github.com/xjanova/videogirl/releases/download/v{$version}/{$filename}",
            'download_filename' => $filename,
            'file_size' => 83886080,
            'sha256' => $sha256,
            'changelog' => $changelog,
            'is_active' => true,
            'synced_at' => now(),
        ]);
    }

    private function asset(int $id, string $name, ?string $digest): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'size' => 1000 + $id,
            'url' => "https://api.github.com/repos/xjanova/videogirl/releases/assets/{$id}",
            'browser_download_url' => "https://github.com/xjanova/videogirl/releases/download/v1.2.0/{$name}",
            'digest' => $digest,
        ];
    }

    /**
     * release ล่าสุดบน GitHub = เวอร์ชันเดียวกับใน DB (read-through ไม่ sync ซ้ำ) · ลิงก์ไฟล์ของ release
     * → 302 ไป CDN → ไฟล์ พร้อม header ของต้นทางที่ห้ามหลุดถึงลูกค้า
     */
    private function fakeGithub(string $version): void
    {
        Http::fake([
            'api.github.com/repos/xjanova/videogirl/releases/latest' => Http::response(['id' => 1, 'tag_name' => "v{$version}", 'assets' => []]),
            'github.com/xjanova/videogirl/releases/download/*' => Http::response('', 302, ['Location' => self::SIGNED_URL]),
            'release-assets.githubusercontent.com/*' => fn () => Http::response(self::FILE_BYTES, 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => (string) strlen(self::FILE_BYTES),
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
