<?php

namespace Tests\Feature;

use App\Models\BlockedIp;
use App\Models\Category;
use App\Models\LicenseKey;
use App\Models\LoginAttempt;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * /api/v1/product/{slug}/app-auth/{health,login,free-license} and the linking rules behind every
 * LinkResult (installer contract 1.1, 1.2, 1.4, 1.5). The device-code path is AppDeviceLoginTest.
 */
class AppAuthApiTest extends TestCase
{
    use RefreshDatabase;

    private const CLUADEX = 'cluadex-ai-coding-assistant';

    private const MACHINE = 'a1b2c3d4e5f60718293a4b5c6d7e8f90a1b2c3d4e5f60718293a4b5c6d7e8f90';

    private const OTHER_MACHINE = 'ffffeeeeddddccccbbbbaaaa99998888ffffeeeeddddccccbbbbaaaa99998888';

    private Product $cluadex;

    private Product $brainx;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['security.auto_block.enabled' => false]);

        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $this->cluadex = Product::firstOrCreate(['slug' => self::CLUADEX], [
            'category_id' => $category->id, 'name' => 'CluadeX', 'description' => 'x', 'price' => 0,
            'requires_license' => true, 'is_active' => true,
        ]);

        // created by the 2026_09_24_100001 migration, as on production
        $this->brainx = Product::where('slug', 'brainx')->firstOrFail();
    }

    // ── health ──────────────────────────────────────────────────────────

    public function test_health_tells_the_app_what_this_server_offers(): void
    {
        $this->travelTo('2026-11-08 21:03:11');

        $this->getJson('/api/v1/product/brainx/app-auth/health')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'สำเร็จ',
                'error_code' => null,
                'data' => [
                    'server_time' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
                    'product' => ['slug' => 'brainx', 'name' => $this->brainx->name],
                    'capabilities' => ['password_login' => true, 'device_login' => true, 'free_license' => false],
                ],
            ]);

        $this->getJson('/api/v1/product/' . self::CLUADEX . '/app-auth/health')
            ->assertOk()
            ->assertJsonPath('data.capabilities.free_license', true);
    }

    public function test_the_shop_slug_of_a_renamed_product_still_answers_as_the_apps_slug(): void
    {
        $this->brainx->update(['slug' => 'brainx-cloud']);

        $this->getJson('/api/v1/product/brainx/app-auth/health')
            ->assertOk()
            ->assertJsonPath('data.product.slug', 'brainx');
    }

    public function test_an_unknown_or_unlicensed_product_is_product_not_found_on_every_route(): void
    {
        $this->cluadex->update(['requires_license' => false]);

        foreach (['get' => ['health'], 'post' => ['login', 'device/start', 'device/poll', 'free-license']] as $method => $routes) {
            foreach ($routes as $route) {
                foreach (['no-such-app', self::CLUADEX] as $slug) {
                    $this->json($method, "/api/v1/product/{$slug}/app-auth/{$route}", $this->loginBody())
                        ->assertNotFound()
                        ->assertJsonPath('success', false)
                        ->assertJsonPath('error_code', 'PRODUCT_NOT_FOUND');
                }
            }
        }
    }

    // ── login ───────────────────────────────────────────────────────────

    public function test_a_missing_field_is_validation_failed(): void
    {
        $this->postJson($this->url('login'), ['login' => 'a@example.com'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['data' => ['errors' => ['password', 'machine_id']]]);

        $this->postJson($this->url('login'), $this->loginBody(['machine_id' => 'short']))
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_FAILED');
    }

    public function test_a_wrong_password_and_an_unknown_address_get_the_same_answer_and_are_written_down(): void
    {
        $user = $this->member();

        $wrongPassword = $this->postJson($this->url('login'), $this->loginBody(['login' => $user->email, 'password' => 'nope']))
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'INVALID_CREDENTIALS');

        $noAccount = $this->postJson($this->url('login'), $this->loginBody(['login' => 'nobody@example.com', 'password' => 'nope']))
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'INVALID_CREDENTIALS');

        $this->assertSame($wrongPassword->json(), $noAccount->json());
        $this->assertSame('อีเมลหรือรหัสผ่านไม่ถูกต้อง', $noAccount->json('message'));

        $attempts = LoginAttempt::orderBy('id')->get();
        $this->assertCount(2, $attempts);
        $this->assertSame([LoginAttempt::OUTCOME_FAILED, LoginAttempt::OUTCOME_FAILED], $attempts->pluck('outcome')->all());
        $this->assertSame(['app', 'app'], $attempts->pluck('method')->all());
        $this->assertSame($user->id, $attempts[0]->user_id);
    }

    public function test_an_account_without_a_password_cannot_be_signed_into_with_an_empty_guess(): void
    {
        // signed up with Google: no password anybody knows (an empty hash never matches)
        $user = $this->member(['password' => '', 'password_set_at' => null, 'google_id' => 'g-1']);

        $this->postJson($this->url('login'), $this->loginBody(['login' => $user->email, 'password' => 'anything']))
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'INVALID_CREDENTIALS');
    }

    public function test_a_suspended_account_is_told_so_only_with_the_right_password(): void
    {
        $user = $this->member(['is_active' => false]);

        $this->postJson($this->url('login'), $this->loginBody(['login' => $user->email, 'password' => 'wrong']))
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'INVALID_CREDENTIALS');

        $this->postJson($this->url('login'), $this->loginBody(['login' => $user->email]))
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'ACCOUNT_DISABLED')
            ->assertJsonPath('data', null);
    }

    public function test_guessing_through_the_app_feeds_the_same_ip_auto_block_as_the_sign_in_form(): void
    {
        config([
            'security.auto_block.enabled' => true,
            'security.auto_block.ip_failures' => 3,
            'security.auto_block.ip_accounts' => 8,
            'security.auto_block.window_minutes' => 15,
            'security.auto_block.block_minutes' => 60,
            'security.auto_block.admin_grace_days' => 30,
            'security.auto_block.never_block' => [],
        ]);
        $user = $this->member();

        foreach (range(1, 3) as $i) {
            $this->postJson($this->url('login'), $this->loginBody(['login' => $user->email, 'password' => "wrong-{$i}"]));
        }

        $this->assertNotNull(BlockedIp::findActive('127.0.0.1'));
    }

    public function test_five_tries_a_minute_per_address_and_account_then_rate_limit_exceeded(): void
    {
        $user = $this->member();

        foreach (range(1, 5) as $i) {
            $this->postJson($this->url('login'), $this->loginBody(['login' => $user->email]))->assertOk();
        }

        $this->postJson($this->url('login'), $this->loginBody(['login' => strtoupper($user->email)]))
            ->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'RATE_LIMIT_EXCEEDED');

        // another account from the same address has its own five
        $this->postJson($this->url('login'), $this->loginBody(['login' => 'someone-else@example.com']))
            ->assertStatus(401);

        // a minute later the first account may try again
        $this->travel(61)->seconds();
        $this->postJson($this->url('login'), $this->loginBody(['login' => $user->email]))->assertOk();
    }

    public function test_the_app_auth_routes_do_not_spend_each_others_budgets(): void
    {
        foreach (range(1, 10) as $i) {
            $this->postJson($this->url('device/start'), ['machine_id' => self::MACHINE])->assertOk();
        }
        $this->postJson($this->url('device/start'), ['machine_id' => self::MACHINE])
            ->assertStatus(429)->assertJsonPath('error_code', 'RATE_LIMIT_EXCEEDED');

        $this->postJson($this->url('free-license'), ['machine_id' => self::MACHINE])->assertOk();
        $this->getJson($this->url('health'))->assertOk();
        $this->postJson($this->url('login'), $this->loginBody(['login' => 'x@example.com']))->assertStatus(401);
        $this->postJson($this->url('device/poll'), ['device_code' => str_repeat('0', 64), 'machine_id' => self::MACHINE])
            ->assertOk()->assertJsonPath('error_code', 'EXPIRED_TOKEN');
    }

    public function test_a_password_sign_in_returns_the_account_and_its_paid_key_without_activating_it(): void
    {
        $user = $this->member(['name' => 'Boonnarat', 'email' => 'xjanova@gmail.com']);
        $key = $this->key($this->brainx, ['user_id' => $user->id, 'license_type' => 'yearly', 'expires_at' => now()->addYear()]);

        $this->postJson('/api/v1/product/brainx/app-auth/login', $this->loginBody(['login' => 'xjanova@gmail.com', 'app_version' => '2.10.579']))
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'เชื่อมบัญชีแล้ว',
                'error_code' => null,
                'data' => [
                    'account' => ['name' => 'Boonnarat', 'email_masked' => 'xj***@gmail.com'],
                    'account_linked' => true,
                    'license' => [
                        'license_key' => $key->license_key,
                        'license_type' => 'yearly',
                        'expires_at' => $key->expires_at->utc()->format('Y-m-d\TH:i:s\Z'),
                        'source' => 'purchased',
                        'bound_to_other_device' => false,
                    ],
                    'purchase_url' => route('products.show', 'brainx'),
                ],
            ]);

        // activation stays with /activate
        $key->refresh();
        $this->assertNull($key->machine_id);
        $this->assertNull($key->activated_at);

        $this->assertSame(LoginAttempt::OUTCOME_SUCCESS, LoginAttempt::latest('id')->firstOrFail()->outcome);
    }

    public function test_a_key_bought_on_the_accounts_order_counts_even_without_user_id_on_the_key(): void
    {
        $user = $this->member();
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(), 'user_id' => $user->id, 'customer_name' => $user->name, 'customer_email' => $user->email,
            'customer_phone' => '0800000000', 'subtotal' => 399, 'total' => 399, 'status' => 'completed', 'payment_status' => 'paid',
        ]);
        $key = $this->key($this->brainx, ['order_id' => $order->id, 'license_type' => 'lifetime']);

        $this->login($user, 'brainx')
            ->assertJsonPath('data.license.license_key', $key->license_key)
            ->assertJsonPath('data.license.expires_at', null)
            ->assertJsonPath('data.license.source', 'purchased');
    }

    public function test_lifetime_wins_then_the_latest_end_and_dead_or_trial_keys_never_count(): void
    {
        $user = $this->member();
        $this->key($this->brainx, ['user_id' => $user->id, 'license_type' => 'monthly', 'expires_at' => now()->addDays(20)]);
        $latest = $this->key($this->brainx, ['user_id' => $user->id, 'license_type' => 'yearly', 'expires_at' => now()->addDays(300)]);
        $this->key($this->brainx, ['user_id' => $user->id, 'license_type' => 'yearly', 'expires_at' => now()->subDay()]);
        $this->key($this->brainx, ['user_id' => $user->id, 'license_type' => 'lifetime', 'status' => LicenseKey::STATUS_REVOKED]);
        $this->key($this->brainx, ['user_id' => $user->id, 'license_type' => 'demo', 'expires_at' => now()->addDays(5), 'machine_id' => self::OTHER_MACHINE]);
        // another product's key is not this product's
        $this->key($this->cluadex, ['user_id' => $user->id, 'license_type' => 'lifetime']);

        $this->login($user, 'brainx')->assertJsonPath('data.license.license_key', $latest->license_key);

        $lifetime = $this->key($this->brainx, ['user_id' => $user->id, 'license_type' => 'lifetime']);

        $this->login($user, 'brainx')
            ->assertJsonPath('data.license.license_key', $lifetime->license_key)
            ->assertJsonPath('data.license.license_type', 'lifetime');
    }

    public function test_a_key_already_on_this_machine_is_kept_rather_than_moving_another_one_here(): void
    {
        $user = $this->member();
        $elsewhere = $this->key($this->brainx, ['user_id' => $user->id, 'license_type' => 'lifetime', 'machine_id' => self::OTHER_MACHINE]);

        $this->login($user, 'brainx')
            ->assertJsonPath('data.license.license_key', $elsewhere->license_key)
            ->assertJsonPath('data.license.bound_to_other_device', true);

        $here = $this->key($this->brainx, ['user_id' => $user->id, 'license_type' => 'yearly', 'expires_at' => now()->addMonth(), 'machine_id' => self::MACHINE]);

        $this->login($user, 'brainx')
            ->assertJsonPath('data.license.license_key', $here->license_key)
            ->assertJsonPath('data.license.bound_to_other_device', false);
    }

    public function test_the_machines_unowned_trial_is_claimed_for_the_account(): void
    {
        $user = $this->member();
        $trial = $this->key($this->brainx, ['license_type' => 'demo', 'expires_at' => now()->addDays(7), 'machine_id' => self::MACHINE]);

        $this->login($user, 'brainx')
            ->assertJsonPath('data.license.license_key', $trial->license_key)
            ->assertJsonPath('data.license.license_type', 'demo')
            ->assertJsonPath('data.license.source', 'trial')
            ->assertJsonPath('data.license.bound_to_other_device', false);

        $this->assertSame($user->id, (int) $trial->fresh()->user_id);

        // signing in again finds it as this account's
        $this->login($user, 'brainx')->assertJsonPath('data.license.license_key', $trial->license_key);
    }

    public function test_a_machine_key_held_by_another_account_is_never_handed_over(): void
    {
        $owner = $this->member();
        $intruder = $this->member();
        $trial = $this->key($this->brainx, ['user_id' => $owner->id, 'license_type' => 'demo', 'expires_at' => now()->addDays(7), 'machine_id' => self::MACHINE]);
        $free = $this->key($this->cluadex, ['user_id' => $owner->id, 'license_type' => 'free', 'machine_id' => self::MACHINE]);

        $this->login($intruder, 'brainx')
            ->assertJsonPath('data.account_linked', true)
            ->assertJsonPath('data.license', null);

        // CluadeX: freeFor() finds the same FREE key of this machine — still the owner's
        $this->login($intruder, self::CLUADEX)->assertJsonPath('data.license', null);

        $this->assertSame($owner->id, (int) $trial->fresh()->user_id);
        $this->assertSame($owner->id, (int) $free->fresh()->user_id);
        $this->assertSame(1, LicenseKey::where('product_id', $this->cluadex->id)->count());
    }

    public function test_of_two_accounts_claiming_one_machine_key_at_once_the_loser_gets_nothing(): void
    {
        $winner = $this->member();
        $loser = $this->member();
        $trial = $this->key($this->brainx, ['license_type' => 'demo', 'expires_at' => now()->addDays(7), 'machine_id' => self::MACHINE]);

        // The winner's request commits right after the loser's read and before its update
        $raced = false;
        LicenseKey::retrieved(function (LicenseKey $key) use (&$raced, $trial, $winner) {
            if (! $raced && $key->id === $trial->id && $key->user_id === null) {
                $raced = true;
                DB::table('license_keys')->where('id', $trial->id)->update(['user_id' => $winner->id]);
            }
        });

        $this->login($loser, 'brainx')->assertJsonPath('data.license', null);

        $this->assertTrue($raced);
        $this->assertSame($winner->id, (int) $trial->fresh()->user_id);
    }

    public function test_cluadex_without_any_key_gets_this_machines_free_key_on_the_account(): void
    {
        $user = $this->member();

        $response = $this->login($user, self::CLUADEX)
            ->assertJsonPath('data.license.license_type', 'free')
            ->assertJsonPath('data.license.expires_at', null)
            ->assertJsonPath('data.license.source', 'free')
            ->assertJsonPath('data.license.bound_to_other_device', false)
            ->assertJsonPath('data.purchase_url', route('products.show', self::CLUADEX));

        $key = LicenseKey::where('license_key', $response->json('data.license.license_key'))->firstOrFail();
        $this->assertStringStartsWith('FREE-', $key->license_key);
        $this->assertSame(self::MACHINE, $key->machine_id);
        $this->assertSame($user->id, (int) $key->user_id);
    }

    public function test_brainx_without_any_key_links_the_account_with_no_licence(): void
    {
        $this->login($this->member(), 'brainx')
            ->assertJsonPath('data.account_linked', true)
            ->assertJsonPath('data.license', null)
            ->assertJsonPath('data.purchase_url', route('products.show', 'brainx'));

        $this->assertSame(0, $this->keys()->count());
    }

    public function test_admins_may_link_an_app_like_any_customer(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'password' => bcrypt('secret-password')]);
        $key = $this->key($this->brainx, ['user_id' => $admin->id, 'license_type' => 'lifetime']);

        $this->login($admin, 'brainx')->assertJsonPath('data.license.license_key', $key->license_key);
    }

    // ── free-license ────────────────────────────────────────────────────

    public function test_cluadex_hands_this_machine_its_free_key_once(): void
    {
        $first = $this->postJson($this->url('free-license'), ['machine_id' => self::MACHINE, 'machine_name' => 'DESKTOP-1', 'app_version' => '3.0.270'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.account_linked', false)
            ->assertJsonPath('data.license.license_type', 'free')
            ->assertJsonPath('data.license.expires_at', null)
            ->assertJsonPath('data.license.source', 'free');

        $this->assertStringStartsWith('FREE-', $first->json('data.license.license_key'));
        $this->assertSame(['license_key', 'license_type', 'expires_at', 'source'], array_keys($first->json('data.license')));

        $this->postJson($this->url('free-license'), ['machine_id' => self::MACHINE])
            ->assertOk()
            ->assertJsonPath('data.license.license_key', $first->json('data.license.license_key'));

        $this->assertSame(1, $this->keys()->count());
        $this->assertNull($this->keys()->first()->user_id);
    }

    public function test_a_product_off_the_allowlist_has_no_free_licence(): void
    {
        $this->postJson('/api/v1/product/brainx/app-auth/free-license', ['machine_id' => self::MACHINE])
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'FREE_LICENSE_NOT_AVAILABLE');

        $this->assertSame(0, $this->keys()->count());
    }

    public function test_free_license_checks_the_machine_id_and_its_own_rate(): void
    {
        $this->postJson($this->url('free-license'), [])
            ->assertStatus(422)->assertJsonPath('error_code', 'VALIDATION_FAILED');

        foreach (range(2, 10) as $i) {
            $this->postJson($this->url('free-license'), ['machine_id' => self::MACHINE]);
        }

        $this->postJson($this->url('free-license'), ['machine_id' => self::MACHINE])
            ->assertStatus(429)->assertJsonPath('error_code', 'RATE_LIMIT_EXCEEDED');
    }

    // ── helpers ─────────────────────────────────────────────────────────

    /** Keys of the two apps — a migration seeds an unrelated product's key. */
    private function keys()
    {
        return LicenseKey::whereIn('product_id', [$this->cluadex->id, $this->brainx->id]);
    }

    private function url(string $route, string $slug = self::CLUADEX): string
    {
        return "/api/v1/product/{$slug}/app-auth/{$route}";
    }

    private function loginBody(array $overrides = []): array
    {
        return array_merge([
            'login' => 'member@example.com',
            'password' => 'secret-password',
            'machine_id' => self::MACHINE,
            'machine_name' => 'DESKTOP-1',
        ], $overrides);
    }

    private function login(User $user, string $slug): TestResponse
    {
        // a fresh minute each time: the helper is about the answer, not the rate limit
        $this->travel(61)->seconds();

        return $this->postJson($this->url('login', $slug), $this->loginBody(['login' => $user->email]))
            ->assertOk()
            ->assertJsonPath('success', true);
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

    private function key(Product $product, array $attributes): LicenseKey
    {
        return LicenseKey::create(array_merge([
            'product_id' => $product->id,
            'license_key' => LicenseKey::generateKey(),
            'status' => LicenseKey::STATUS_ACTIVE,
            'license_type' => 'yearly',
            'max_activations' => 1,
            'activations' => 0,
        ], $attributes));
    }
}
