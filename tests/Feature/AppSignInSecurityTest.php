<?php

namespace Tests\Feature;

use App\Models\BlockedIp;
use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The app's side doors into a web session.
 *
 * Two routes turn something other than the password form into a signed-in
 * browser: /api/v1/auth/login hands out an API token for a password, and
 * web-login-token + /auth/device-login/{token} turn any API token into a web
 * session. A device token itself is minted from a licence key plus a machine
 * id, so before these rules an admin who owned a licence could be signed into
 * the admin panel by anyone holding that key — and a password could be guessed
 * through the API with none of the front door's counting or blocking.
 *
 * The rules:
 *   — a link from the app never starts an admin session, nor a suspended one
 *   — a customer's link still works, once
 *   — a wrong password through the API is counted exactly like one on the form
 */
class AppSignInSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config([
            'security.auto_block.enabled' => true,
            'security.auto_block.ip_failures' => 3,
            'security.auto_block.ip_accounts' => 8,
            'security.auto_block.window_minutes' => 15,
            'security.auto_block.block_minutes' => 60,
            'security.auto_block.admin_grace_days' => 30,
            'security.auto_block.never_block' => [],
        ]);
    }

    // ────────────────────────────────────────────── the link from the app

    public function test_a_customer_link_signs_the_customer_in_once(): void
    {
        $user = $this->member();
        Cache::put('web_login_token:good-token', $user->id, now()->addMinutes(5));

        $this->get('/auth/device-login/good-token')->assertRedirect('/my-account/tping-workflows');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, LoginAttempt::where('outcome', LoginAttempt::OUTCOME_SUCCESS)->count());
        $this->assertSame('device', LoginAttempt::firstOrFail()->method);

        auth()->logout();
        $this->get('/auth/device-login/good-token')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_a_link_never_starts_an_admin_session(): void
    {
        foreach (['admin', 'super_admin'] as $role) {
            $admin = $this->member(['role' => $role]);
            Cache::put("web_login_token:{$role}-token", $admin->id, now()->addMinutes(5));

            $this->get("/auth/device-login/{$role}-token")
                ->assertRedirect(route('login'))
                ->assertSessionHasErrors('email');

            $this->assertGuest();
        }

        $this->assertSame(0, LoginAttempt::where('outcome', LoginAttempt::OUTCOME_SUCCESS)->count());
    }

    /** Promoted after the link was minted — the redemption is what counts. */
    public function test_the_role_is_checked_when_the_link_is_used(): void
    {
        $user = $this->member();
        Cache::put('web_login_token:late', $user->id, now()->addMinutes(5));

        $user->forceFill(['role' => 'admin'])->save();

        $this->get('/auth/device-login/late')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_a_suspended_account_cannot_use_a_link(): void
    {
        $user = $this->member(['is_active' => false]);
        Cache::put('web_login_token:banned', $user->id, now()->addMinutes(5));

        $this->get('/auth/device-login/banned')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_an_admin_token_cannot_mint_a_link(): void
    {
        Sanctum::actingAs($this->member(['role' => 'super_admin']));

        $this->postJson('/api/v1/auth/web-login-token')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_a_suspended_account_cannot_mint_a_link(): void
    {
        Sanctum::actingAs($this->member(['is_active' => false]));

        $this->postJson('/api/v1/auth/web-login-token')->assertStatus(403);
    }

    public function test_a_customer_token_still_mints_a_working_link(): void
    {
        $user = $this->member();
        Sanctum::actingAs($user);

        $url = $this->postJson('/api/v1/auth/web-login-token')
            ->assertOk()
            ->json('data.url');

        $token = basename(parse_url($url, PHP_URL_PATH));
        $this->assertSame($user->id, Cache::get("web_login_token:{$token}"));
    }

    // ────────────────────────────────────────────── the sign-in form

    public function test_a_switched_off_account_cannot_sign_in_with_its_password(): void
    {
        $user = $this->member(['is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(LoginAttempt::OUTCOME_FAILED, LoginAttempt::latest('id')->firstOrFail()->outcome);
    }

    // ────────────────────────────────────────────── the API password door

    public function test_a_wrong_password_through_the_api_is_written_down(): void
    {
        $user = $this->member();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'device_name' => 'phone',
        ])->assertStatus(422);

        $attempt = LoginAttempt::latest('id')->firstOrFail();
        $this->assertSame(LoginAttempt::OUTCOME_FAILED, $attempt->outcome);
        $this->assertSame('api', $attempt->method);
        $this->assertSame($user->id, $attempt->user_id);
    }

    public function test_guessing_through_the_api_gets_the_address_blocked(): void
    {
        $user = $this->member();

        foreach (range(1, 3) as $i) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => "wrong-{$i}",
                'device_name' => 'phone',
            ]);
        }

        $this->assertNotNull(BlockedIp::findActive('127.0.0.1'),
            'API failures must feed the same auto-block as the sign-in form');
    }

    public function test_a_correct_password_through_the_api_is_written_down(): void
    {
        $user = $this->member();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret-password',
            'device_name' => 'phone',
        ])->assertOk()->assertJsonPath('success', true);

        $attempt = LoginAttempt::latest('id')->firstOrFail();
        $this->assertSame(LoginAttempt::OUTCOME_SUCCESS, $attempt->outcome);
        $this->assertSame('api', $attempt->method);
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
