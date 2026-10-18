<?php

namespace Tests\Feature;

use App\Models\DownloadLog;
use App\Models\GithubSetting;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\ProductDevice;
use App\Models\ProductVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * MacDock (Windows, free) — the product row, the free licence taken on the website, updates and the download
 *
 *   — the product row and its private release setting come from a migration (deploy runs migrate only)
 *   — check-machine never hands out a key by itself: the key is born when a signed-in customer confirms
 *     on /products/macdock/free-license, tied to the account and the PC
 *   — a DEMO key started through the API never becomes the account's key
 *   — update/check points at /macdock/download/{version}, which streams the zip — never a redirect to GitHub
 */
class MacDockProductTest extends TestCase
{
    use RefreshDatabase;

    private const SHA256 = 'bb22cc33dd44ee55ff6600778899aa11bb22cc33dd44ee55ff6600778899aa11';

    private const ZIP_BYTES = "PK\x03\x04macdock-release-package\x00\x01\xfe\xff";

    private const TOKEN = 'github_pat_test_secret_token';

    private const SIGNED_URL = 'https://release-assets.githubusercontent.com/github-production-release-asset/888?X-Amz-Signature=abc';

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->withoutVite();
        Http::preventStrayRequests();

        // created by 2026_10_14_100000_register_macdock_product, as on production after deploy
        $this->product = Product::where('slug', 'macdock')->firstOrFail();
    }

    // ── the product row (migration) ─────────────────────────────────────

    public function test_the_migration_registers_macdock_and_its_private_release_repository(): void
    {
        $this->assertSame('MacDock', $this->product->name);
        $this->assertTrue($this->product->requires_license);
        $this->assertTrue($this->product->is_active);
        $this->assertFalse($this->product->is_coming_soon);
        $this->assertEquals(0, (float) $this->product->price);
        $this->assertSame('system-utilities', $this->product->category->slug);

        $setting = $this->setting();
        $this->assertSame('xjanova/macdock', $setting->full_repo_name);
        $this->assertSame('MacDock-*-win-x64.zip', $setting->asset_pattern);
        $this->assertTrue($setting->is_active);
        $this->assertNull($setting->github_token_decrypted);
    }

    public function test_running_the_migration_again_keeps_the_owners_edits_and_adds_nothing(): void
    {
        $this->setting()->update(['github_token' => self::TOKEN]);
        $this->product->update(['short_description' => 'edited in the admin']);

        $this->migration()->up();

        $this->assertSame(1, Product::where('slug', 'macdock')->count());
        $this->assertSame(1, GithubSetting::where('product_id', $this->product->id)->count());
        $this->assertSame(self::TOKEN, $this->setting()->github_token_decrypted);
        $this->assertSame('edited in the admin', $this->product->fresh()->short_description);
    }

    public function test_rolling_back_never_deletes_a_product_whose_customers_hold_keys(): void
    {
        $this->registerDevice('a');
        $this->claim(User::factory()->create(), 'a');

        $this->migration()->down();

        $this->assertTrue(Product::where('slug', 'macdock')->exists());
    }

    public function test_rolling_back_an_unused_product_removes_it_and_up_brings_it_back(): void
    {
        $this->migration()->down();
        $this->assertFalse(Product::where('slug', 'macdock')->exists());
        $this->assertFalse(GithubSetting::where('github_repo', 'macdock')->exists());

        $this->migration()->up();

        $this->assertSame('xjanova/macdock', Product::where('slug', 'macdock')->sole()->githubSetting->full_repo_name);
    }

    // ── check-machine never hands out a key ─────────────────────────────

    public function test_a_registered_pc_gets_no_key_until_someone_confirms_on_the_website(): void
    {
        $this->registerDevice('a')->assertOk();

        $this->checkMachine('a')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('has_license', false);

        $this->assertSame(0, LicenseKey::where('product_id', $this->product->id)->count());
    }

    // ── the free-licence page ───────────────────────────────────────────

    public function test_a_guest_is_sent_to_sign_in_and_the_page_is_remembered(): void
    {
        $url = route('macdock.free-license', ['machine_id' => $this->machineId('a')]);

        $this->get($url)->assertRedirect(route('login'));
        $this->assertSame($url, session('url.intended'));

        $this->post(route('macdock.free-license.store'), ['machine_id' => $this->machineId('a')])
            ->assertRedirect(route('login'));
        $this->assertSame(0, $this->keys()->count());
    }

    public function test_the_page_tells_each_state_of_the_pc(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($me)->get(route('macdock.free-license'))
            ->assertOk()->assertSee('data-state="no_machine"', false);
        $this->actingAs($me)->get(route('macdock.free-license', ['machine_id' => 'not-a-machine']))
            ->assertOk()->assertSee('data-state="no_machine"', false);

        $this->actingAs($me)->get($this->page('a'))
            ->assertOk()->assertSee('data-state="unknown_device"', false)
            ->assertDontSee('name="machine_id"', false);

        $this->registerDevice('a');
        $this->actingAs($me)->get($this->page('a'))
            ->assertOk()->assertSee('data-state="ready"', false)
            ->assertSee('value="' . $this->machineId('a') . '"', false)
            ->assertSee($me->email);

        $this->claim($me, 'a');
        $this->actingAs($me)->get($this->page('a'))
            ->assertOk()->assertSee('data-state="mine"', false);
        $this->actingAs($other)->get($this->page('a'))
            ->assertOk()->assertSee('data-state="taken"', false)
            ->assertDontSee('name="machine_id"', false);
    }

    public function test_confirming_creates_the_free_key_that_check_machine_then_returns(): void
    {
        $user = User::factory()->create();
        $this->registerDevice('a');

        $this->claim($user, 'a')
            ->assertRedirect($this->page('a'))
            ->assertSessionHas('success');

        $license = LicenseKey::where('product_id', $this->product->id)->sole();
        $this->assertSame(LicenseKey::TYPE_FREE, $license->license_type);
        $this->assertSame($user->id, (int) $license->user_id);
        $this->assertSame($this->machineId('a'), $license->machine_id);
        $this->assertNull($license->expires_at);

        $device = ProductDevice::where('product_id', $this->product->id)->sole();
        $this->assertSame($license->id, (int) $device->license_id);
        $this->assertSame(ProductDevice::STATUS_LICENSED, $device->status);

        $this->checkMachine('a')
            ->assertOk()
            ->assertJsonPath('has_license', true)
            ->assertJsonPath('data.license_key', $license->license_key)
            ->assertJsonPath('data.license_type', LicenseKey::TYPE_FREE);
    }

    public function test_the_account_list_shows_the_key_masked(): void
    {
        $user = User::factory()->create();
        $this->registerDevice('a');
        $this->claim($user, 'a');
        $key = $this->keys()->sole()->license_key;

        $this->actingAs($user)->get($this->page('a'))
            ->assertOk()
            ->assertSee('PC-A')
            ->assertSee(substr($key, 0, 9) . '••••' . substr($key, -4))
            ->assertDontSee($key);
    }

    public function test_the_claimed_key_shows_in_my_licenses_though_it_has_no_order(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $this->registerDevice('a');
        $this->claim($user, 'a');
        $license = $this->keys()->sole();
        $this->assertNull($license->order_id);

        $this->actingAs($user)->get(route('customer.licenses'))
            ->assertOk()
            ->assertSee('MacDock')
            ->assertSee(route('customer.licenses.show', $license));

        $this->actingAs($user)->get(route('customer.licenses.show', $license))->assertOk();

        $this->actingAs($stranger)->get(route('customer.licenses'))
            ->assertOk()
            ->assertDontSee(route('customer.licenses.show', $license));
    }

    public function test_confirming_again_keeps_the_same_key(): void
    {
        $user = User::factory()->create();
        $this->registerDevice('a');

        $this->claim($user, 'a');
        $key = $this->keys()->sole()->license_key;

        $this->claim($user, 'a')->assertSessionHas('success');

        $this->assertSame($key, $this->keys()->sole()->license_key);
    }

    public function test_another_account_cannot_take_a_pc_that_is_already_claimed(): void
    {
        $owner = User::factory()->create();
        $this->registerDevice('a');
        $this->claim($owner, 'a');

        $this->claim(User::factory()->create(), 'a')
            ->assertRedirect($this->page('a'))
            ->assertSessionHas('error');

        $license = $this->keys()->sole();
        $this->assertSame($owner->id, (int) $license->user_id);
    }

    public function test_a_pc_the_app_never_registered_cannot_be_claimed(): void
    {
        $this->claim(User::factory()->create(), 'a')
            ->assertRedirect($this->page('a'))
            ->assertSessionHas('error');

        $this->assertSame(0, $this->keys()->count());
    }

    public function test_a_bad_machine_id_is_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('macdock.free-license.store'), ['machine_id' => 'short; drop'])
            ->assertSessionHasErrors('machine_id');

        $this->assertSame(0, $this->keys()->count());
    }

    public function test_a_demo_key_is_never_what_lands_on_the_account(): void
    {
        $user = User::factory()->create();
        $this->registerDevice('a');

        $demo = LicenseKey::create([
            'product_id' => $this->product->id,
            'license_key' => 'DEMO-' . strtoupper(str_repeat('x', 20)),
            'license_type' => LicenseKey::TYPE_DEMO,
            'status' => LicenseKey::STATUS_ACTIVE,
            'machine_id' => $this->machineId('a'),
            'expires_at' => now()->addDay(),
            'max_activations' => 1,
            'activations' => 1,
        ]);

        $this->actingAs($user)->get($this->page('a'))->assertSee('data-state="ready"', false);
        $this->claim($user, 'a')->assertSessionHas('success');

        $this->assertNull($demo->fresh()->user_id);
        $free = $this->keys()->where('license_type', LicenseKey::TYPE_FREE)->sole();
        $this->assertSame($user->id, (int) $free->user_id);
        $this->assertNull($free->expires_at);
    }

    public function test_signing_up_from_the_free_licence_page_returns_to_it(): void
    {
        $url = $this->page('a');
        $this->get($url)->assertRedirect(route('login'));

        $this->post(route('register'), [
            'name' => 'Dock User',
            'email' => 'dock-user@example.com',
            'password' => 'a-Long-pass-123!',
            'password_confirmation' => 'a-Long-pass-123!',
        ])->assertRedirect($url);

        $this->assertAuthenticated();
    }

    // ── update check and the download ───────────────────────────────────

    public function test_update_check_points_the_app_at_this_site(): void
    {
        $this->makeVersion('1.1.0');

        $response = $this->getJson('/api/v1/product/macdock/update/check?current_version=1.0.0')
            ->assertOk()
            ->assertJsonPath('has_update', true)
            ->assertJsonPath('latest_version', '1.1.0')
            ->assertJsonPath('download_url', route('macdock.download', ['version' => '1.1.0']))
            ->assertJsonPath('sha256', self::SHA256)
            ->assertJsonPath('filename', 'MacDock-1.1.0-win-x64.zip');

        $this->assertStringNotContainsStringIgnoringCase('github', $response->getContent());
    }

    public function test_the_zip_is_streamed_with_no_redirect_and_no_trace_of_github(): void
    {
        $version = $this->makeVersion('1.1.0');
        $this->setting()->update(['github_token' => self::TOKEN]);

        Http::fake([
            'api.github.com/repos/xjanova/macdock/releases/assets/902' => Http::response('', 302, ['Location' => self::SIGNED_URL]),
            'release-assets.githubusercontent.com/*' => fn () => Http::response(self::ZIP_BYTES, 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => (string) strlen(self::ZIP_BYTES),
                'x-github-request-id' => 'ABCD:1234',
            ]),
        ]);

        $response = $this->get('/macdock/download/1.1.0', ['Accept' => '', 'User-Agent' => 'MacDock-Update/1.0.0']);

        $response->assertOk()
            ->assertHeaderMissing('Location')
            ->assertHeader('Content-Type', 'application/octet-stream');
        $this->assertInstanceOf(StreamedResponse::class, $response->baseResponse);
        $this->assertStringContainsString('MacDock-1.1.0-win-x64.zip', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::ZIP_BYTES, $response->streamedContent());
        $this->assertNoTraceOfGithub($response);
        $this->assertStringNotContainsString(self::TOKEN, json_encode($response->headers->all()));

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.github.com/repos/xjanova/macdock/releases/assets/902')
            && $request->hasHeader('Authorization', 'Bearer ' . self::TOKEN));

        $this->assertSame($version->id, DownloadLog::sole()->product_version_id);
    }

    public function test_an_unknown_version_is_a_json_404_for_the_app_and_the_product_page_for_a_browser(): void
    {
        $this->get('/macdock/download/9.9.9', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertJsonPath('success', false);

        $this->get('/macdock/download/9.9.9')
            ->assertRedirect(route('products.show', 'macdock'));
    }

    public function test_the_download_is_404_while_the_product_is_switched_off(): void
    {
        $this->makeVersion('1.1.0');
        $this->product->update(['is_active' => false]);

        $this->get('/macdock/download/1.1.0', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'error' => 'Product not found']);
    }

    // ── the product page ────────────────────────────────────────────────

    public function test_the_product_page_downloads_from_this_site_without_login(): void
    {
        $this->makeVersion('1.1.0');

        $html = $this->get('/products/macdock')->assertOk()->getContent();

        $this->assertStringContainsString('href="' . route('macdock.download') . '"', $html);
        $this->assertStringContainsString('v1.1.0', $html);
        $this->assertStringContainsString('รับไลเซ่นฟรี', $html);
        $this->assertStringNotContainsStringIgnoringCase('github', $html);
    }

    public function test_a_coming_soon_product_shows_no_download_link(): void
    {
        $this->product->update(['is_coming_soon' => true]);

        $html = $this->get('/products/macdock')->assertOk()->getContent();

        $this->assertStringContainsString('data-coming-soon', $html);
        $this->assertStringNotContainsString('href="' . route('macdock.download') . '"', $html);
    }

    // ── helpers ─────────────────────────────────────────────────────────

    private function migration(): object
    {
        return require database_path('migrations/2026_10_14_100000_register_macdock_product.php');
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
            'github_release_id' => 9101,
            'github_release_url' => 'https://api.github.com/repos/xjanova/macdock/releases/assets/902',
            'download_url' => "https://github.com/xjanova/macdock/releases/download/v{$version}/MacDock-{$version}-win-x64.zip",
            'download_filename' => "MacDock-{$version}-win-x64.zip",
            'file_size' => strlen(self::ZIP_BYTES),
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

    /** other migrations leave keys of their own products behind — count MacDock's only */
    private function keys()
    {
        return LicenseKey::where('product_id', $this->product->id);
    }

    private function page(string $machine): string
    {
        return route('macdock.free-license', ['machine_id' => $this->machineId($machine)]);
    }

    private function claim(User $user, string $machine): TestResponse
    {
        return $this->actingAs($user)->post(route('macdock.free-license.store'), [
            'machine_id' => $this->machineId($machine),
        ]);
    }

    private function registerDevice(string $machine): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/macdock/register-device', [
            'machine_id' => $this->machineId($machine),
            'machine_name' => 'PC-' . strtoupper($machine),
            'os_version' => 'Windows 11',
            'app_version' => '1.0.0',
        ]);
    }

    private function checkMachine(string $machine): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/macdock/check-machine', [
            'machine_id' => $this->machineId($machine),
        ]);
    }

    private function fromPc(string $machine): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.' . (ord($machine) - 96)]);
    }

    private function machineId(string $machine): string
    {
        return str_repeat($machine, 64);
    }
}
