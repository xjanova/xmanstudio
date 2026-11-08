<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Device-code sign-in (installer contract 1.3): the app's app-auth/device/{start,poll} and the
 * /device page where a signed-in person confirms the code.
 */
class AppDeviceLoginTest extends TestCase
{
    use RefreshDatabase;

    private const CLUADEX = 'cluadex-ai-coding-assistant';

    private const MACHINE = 'a1b2c3d4e5f60718293a4b5c6d7e8f90a1b2c3d4e5f60718293a4b5c6d7e8f90';

    private const OTHER_MACHINE = 'ffffeeeeddddccccbbbbaaaa99998888ffffeeeeddddccccbbbbaaaa99998888';

    private const CODE_PATTERN = '/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{4}-[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{4}$/';

    private Product $brainx;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->withoutVite();

        // Without an admin the setup wizard takes over every page.
        User::factory()->create(['role' => 'super_admin']);

        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        Product::firstOrCreate(['slug' => self::CLUADEX], [
            'category_id' => $category->id, 'name' => 'CluadeX', 'description' => 'x', 'price' => 0,
            'requires_license' => true, 'is_active' => true,
        ]);
        $this->brainx = Product::where('slug', 'brainx')->firstOrFail();
    }

    // ── start ───────────────────────────────────────────────────────────

    public function test_start_hands_out_a_secret_device_code_and_a_short_user_code(): void
    {
        $data = $this->start()->json('data');

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $data['device_code']);
        $this->assertMatchesRegularExpression(self::CODE_PATTERN, $data['user_code']);
        $this->assertSame(url('/device'), $data['verification_uri']);
        $this->assertSame(url('/device') . '?code=' . $data['user_code'], $data['verification_uri_complete']);
        $this->assertSame(600, $data['expires_in']);
        $this->assertSame(3, $data['interval']);

        // only the hash of the device_code is kept
        $stored = serialize((fn () => $this->storage)->call(Cache::getStore()));
        $this->assertStringNotContainsString($data['device_code'], $stored);
        $this->assertStringContainsString(hash('sha256', $data['device_code']), $stored);
    }

    public function test_every_start_gets_its_own_codes(): void
    {
        $a = $this->start()->json('data');
        $b = $this->start()->json('data');

        $this->assertNotSame($a['device_code'], $b['device_code']);
        $this->assertNotSame($a['user_code'], $b['user_code']);
    }

    public function test_start_and_poll_validate_their_input(): void
    {
        $this->postJson($this->url('device/start'), ['machine_id' => 'short'])
            ->assertStatus(422)->assertJsonPath('error_code', 'VALIDATION_FAILED');

        $this->postJson($this->url('device/poll'), ['machine_id' => self::MACHINE])
            ->assertStatus(422)->assertJsonPath('error_code', 'VALIDATION_FAILED');
    }

    // ── poll ────────────────────────────────────────────────────────────

    public function test_poll_waits_then_slows_a_hasty_app_down(): void
    {
        $codes = $this->start()->json('data');

        $this->poll($codes['device_code'])
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'AUTHORIZATION_PENDING');

        // one second later — sooner than the interval
        $this->travel(1)->seconds();
        $this->poll($codes['device_code'], wait: false)
            ->assertOk()->assertJsonPath('error_code', 'SLOW_DOWN');

        // the app waited interval + 2 s
        $this->travel(5)->seconds();
        $this->poll($codes['device_code'], wait: false)
            ->assertOk()->assertJsonPath('error_code', 'AUTHORIZATION_PENDING');
    }

    public function test_an_approved_code_returns_the_link_result_once(): void
    {
        $user = $this->member(['name' => 'Boonnarat', 'email' => 'xjanova@gmail.com']);
        $key = LicenseKey::create([
            'product_id' => $this->brainx->id, 'user_id' => $user->id, 'license_key' => LicenseKey::generateKey(),
            'status' => LicenseKey::STATUS_ACTIVE, 'license_type' => 'lifetime', 'max_activations' => 1, 'activations' => 0,
        ]);
        $codes = $this->start('brainx')->json('data');

        $this->actingAs($user)->post('/device', ['user_code' => $codes['user_code'], 'action' => 'approve'])
            ->assertRedirect(route('app-device.show'))
            ->assertSessionHas('device_result', 'approved');

        $this->poll($codes['device_code'], slug: 'brainx')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.account.name', 'Boonnarat')
            ->assertJsonPath('data.account.email_masked', 'xj***@gmail.com')
            ->assertJsonPath('data.account_linked', true)
            ->assertJsonPath('data.license.license_key', $key->license_key)
            ->assertJsonPath('data.license.source', 'purchased')
            ->assertJsonPath('data.license.bound_to_other_device', false)
            ->assertJsonPath('data.purchase_url', route('products.show', 'brainx'));

        // single use
        $this->poll($codes['device_code'], slug: 'brainx')->assertOk()->assertJsonPath('error_code', 'EXPIRED_TOKEN');
        $this->flushSession();
        $this->actingAs($user)->get('/device?code=' . $codes['user_code'])->assertOk()->assertViewHas('state', 'invalid');
    }

    public function test_cluadex_approval_claims_the_machines_free_key_for_the_account(): void
    {
        $user = $this->member();
        $codes = $this->start()->json('data');

        $this->approve($user, $codes['user_code']);

        $response = $this->poll($codes['device_code'])
            ->assertJsonPath('data.license.source', 'free')
            ->assertJsonPath('data.license.license_type', 'free');

        $this->assertSame($user->id, (int) LicenseKey::where('license_key', $response->json('data.license.license_key'))->value('user_id'));
    }

    public function test_cancelling_on_the_phone_is_access_denied(): void
    {
        $user = $this->member();
        $codes = $this->start()->json('data');

        $this->actingAs($user)->post('/device', ['user_code' => $codes['user_code'], 'action' => 'deny'])
            ->assertRedirect(route('app-device.show'))
            ->assertSessionHas('device_result', 'denied');

        $this->poll($codes['device_code'])
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'ACCESS_DENIED');

        $this->assertSame(0, $this->keys()->count());
    }

    public function test_another_machine_or_another_product_cannot_take_the_approval(): void
    {
        $user = $this->member();
        $codes = $this->start()->json('data');
        $this->approve($user, $codes['user_code']);

        $this->poll($codes['device_code'], machine: self::OTHER_MACHINE)
            ->assertOk()->assertJsonPath('error_code', 'EXPIRED_TOKEN');
        $this->poll($codes['device_code'], slug: 'brainx')
            ->assertOk()->assertJsonPath('error_code', 'EXPIRED_TOKEN');

        // …and did not use it up for the machine that asked
        $this->poll($codes['device_code'])->assertOk()->assertJsonPath('success', true);
        $this->assertSame(0, LicenseKey::where('machine_id', self::OTHER_MACHINE)->count());
    }

    public function test_an_unknown_device_code_is_expired_token(): void
    {
        $this->poll(str_repeat('ab', 32))->assertOk()->assertJsonPath('error_code', 'EXPIRED_TOKEN');
    }

    public function test_after_ten_minutes_the_code_is_gone_everywhere(): void
    {
        $user = $this->member();
        $codes = $this->start()->json('data');

        $this->travel(601)->seconds();

        $this->poll($codes['device_code'], wait: false)->assertOk()->assertJsonPath('error_code', 'EXPIRED_TOKEN');
        $this->actingAs($user)->get('/device?code=' . $codes['user_code'])->assertOk()->assertViewHas('state', 'invalid')
            ->assertSee('โค้ดหมดอายุหรือไม่ถูกต้อง');
        $this->actingAs($user)->post('/device', ['user_code' => $codes['user_code'], 'action' => 'approve'])
            ->assertSessionHas('device_result', 'invalid');
    }

    public function test_an_approver_switched_off_before_the_app_asks_links_nothing(): void
    {
        $user = $this->member();
        $codes = $this->start()->json('data');
        $this->approve($user, $codes['user_code']);

        $user->update(['is_active' => false]);

        $this->poll($codes['device_code'])->assertOk()->assertJsonPath('error_code', 'ACCESS_DENIED');
        $this->assertSame(0, $this->keys()->count());
    }

    public function test_poll_has_its_own_rate_limit(): void
    {
        $codes = $this->start()->json('data');

        foreach (range(1, 40) as $i) {
            $this->poll($codes['device_code'], wait: false)->assertOk();
        }

        $this->poll($codes['device_code'], wait: false)
            ->assertStatus(429)->assertJsonPath('error_code', 'RATE_LIMIT_EXCEEDED');
    }

    // ── /device ─────────────────────────────────────────────────────────

    public function test_a_guest_signs_in_first_and_comes_back_to_the_code(): void
    {
        $codes = $this->start()->json('data');
        $link = $codes['verification_uri_complete'];

        $this->get($link)->assertRedirect(route('login'));

        $user = $this->member();
        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertRedirect($link);
    }

    public function test_a_new_account_made_from_the_code_link_comes_back_to_it(): void
    {
        $codes = $this->start()->json('data');
        $link = $codes['verification_uri_complete'];

        $this->get($link)->assertRedirect(route('login'));

        $this->post('/register', [
            'name' => 'New Person',
            'email' => 'new-person@example.com',
            'password' => 'a-strong-Passw0rd!',
            'password_confirmation' => 'a-strong-Passw0rd!',
        ])->assertRedirect($link);
    }

    public function test_the_page_shows_what_is_asking_and_accepts_the_code_in_any_case_or_dash(): void
    {
        $user = $this->member();
        $codes = $this->start('brainx', ['machine_name' => 'DESKTOP-BOON', 'app_version' => '2.10.579'])->json('data');
        $typed = strtolower(str_replace('-', '', $codes['user_code']));

        $this->actingAs($user)->get('/device?code=' . $typed)
            ->assertOk()
            ->assertViewHas('state', 'confirm')
            ->assertSee($codes['user_code'])
            ->assertSee($this->brainx->name)
            ->assertSee('DESKTOP-BOON')
            ->assertSee('2.10.579')
            ->assertSee('ยืนยัน เชื่อมเครื่องนี้กับบัญชีของฉัน')
            ->assertSee('ไม่ใช่ฉัน ยกเลิก')
            ->assertSee('name="_token"', false);

        $this->actingAs($user)->post('/device', ['user_code' => ' ' . $typed . ' ', 'action' => 'approve'])
            ->assertSessionHas('device_result', 'approved');
        $this->actingAs($user)->get('/device')->assertSee('เชื่อมแล้ว กลับไปที่โปรแกรมได้เลย');
    }

    public function test_without_a_code_the_page_asks_for_one(): void
    {
        $this->actingAs($this->member())->get('/device')
            ->assertOk()
            ->assertViewHas('state', 'enter')
            ->assertSee('กรอกโค้ด 8 ตัวที่แสดงในโปรแกรม');

        $this->actingAs($this->member())->get('/device?code=NOPE-NOPE')
            ->assertOk()
            ->assertViewHas('state', 'invalid');
    }

    public function test_a_code_answered_by_one_account_cannot_be_taken_over_by_another(): void
    {
        $owner = $this->member();
        $other = $this->member();
        $codes = $this->start()->json('data');

        $this->approve($owner, $codes['user_code']);

        $this->actingAs($other)->post('/device', ['user_code' => $codes['user_code'], 'action' => 'approve'])
            ->assertSessionHas('device_result', 'invalid');
        $this->actingAs($other)->post('/device', ['user_code' => $codes['user_code'], 'action' => 'deny'])
            ->assertSessionHas('device_result', 'invalid');
        $this->flushSession();
        $this->actingAs($other)->get('/device?code=' . $codes['user_code'])->assertViewHas('state', 'invalid');

        // pressing approve twice is harmless
        $this->actingAs($owner)->post('/device', ['user_code' => $codes['user_code'], 'action' => 'approve'])
            ->assertSessionHas('device_result', 'approved');

        $this->poll($codes['device_code'])->assertJsonPath('data.account.email_masked', 'me***@example.com');
        $this->assertSame($owner->id, (int) $this->keys()->firstOrFail()->user_id);
    }

    public function test_an_admin_may_confirm_a_code_for_their_own_apps(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $codes = $this->start()->json('data');

        $this->approve($admin, $codes['user_code']);

        $this->poll($codes['device_code'])->assertJsonPath('success', true);
    }

    public function test_guessing_codes_is_limited_to_ten_a_minute_per_account(): void
    {
        $guesser = $this->member();

        foreach (range(1, 10) as $i) {
            $this->actingAs($guesser)->post('/device', ['user_code' => 'AAAA-AAA' . ($i % 8 + 2), 'action' => 'approve'])
                ->assertSessionHas('device_result', 'invalid');
        }

        $this->actingAs($guesser)->post('/device', ['user_code' => 'AAAA-AAAA', 'action' => 'approve'])
            ->assertStatus(429);

        // another account is not held back by it
        $codes = $this->start()->json('data');
        $this->approve($this->member(), $codes['user_code']);
    }

    public function test_the_confirm_form_is_csrf_protected_and_rejects_a_bad_action(): void
    {
        $route = Route::getRoutes()->match(request()->create('/device', 'POST'));
        $this->assertContains(ValidateCsrfToken::class, Route::gatherRouteMiddleware($route));
        $this->assertContains('auth', $route->middleware());

        $codes = $this->start()->json('data');
        $this->actingAs($this->member())->post('/device', ['user_code' => $codes['user_code'], 'action' => 'steal'])
            ->assertSessionHasErrors('action');
        $this->poll($codes['device_code'])->assertJsonPath('error_code', 'AUTHORIZATION_PENDING');
    }

    // ── helpers ─────────────────────────────────────────────────────────

    /** Keys of the two apps — a migration seeds an unrelated product's key. */
    private function keys()
    {
        return LicenseKey::whereIn('product_id', Product::whereIn('slug', [self::CLUADEX, 'brainx'])->select('id'));
    }

    private function url(string $route, string $slug = self::CLUADEX): string
    {
        return "/api/v1/product/{$slug}/app-auth/{$route}";
    }

    private function start(string $slug = self::CLUADEX, array $extra = []): TestResponse
    {
        return $this->postJson($this->url('device/start', $slug), array_merge(['machine_id' => self::MACHINE], $extra))
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    private function poll(string $deviceCode, string $machine = self::MACHINE, string $slug = self::CLUADEX, bool $wait = true): TestResponse
    {
        if ($wait) {
            // the app waits `interval` seconds between polls
            $this->travel(3)->seconds();
        }

        return $this->postJson($this->url('device/poll', $slug), ['device_code' => $deviceCode, 'machine_id' => $machine]);
    }

    private function approve(User $user, string $userCode): void
    {
        $this->actingAs($user)->post('/device', ['user_code' => $userCode, 'action' => 'approve'])
            ->assertSessionHas('device_result', 'approved');
    }

    private function member(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Member',
            'email' => 'member' . User::count() . '@example.com',
            'password' => bcrypt('secret-password'),
            'password_set_at' => now(),
            'is_active' => true,
            'role' => 'user',
        ], $overrides));
    }
}
