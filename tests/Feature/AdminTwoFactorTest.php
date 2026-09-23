<?php

namespace Tests\Feature;

use App\Models\LoginAttempt;
use App\Models\User;
use App\Support\Auth\Totp;
use App\Support\Auth\TwoFactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Two-step sign-in for the admin panel.
 *
 * The rules:
 *   — the codes are RFC 6238 codes, so any authenticator app agrees with us
 *   — an admin who has not enrolled is sent to enrol before any admin page
 *   — an enrolled admin passes the second step once per session, whichever way
 *     the session was signed in; an API token cannot pass it at all
 *   — a code works once; a recovery code works once
 *   — customers are never asked
 */
class AdminTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    /** RFC 6238 appendix B, SHA-1: the ASCII secret "12345678901234567890". */
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['security.two_factor.required_for_admins' => true]);
    }

    // ────────────────────────────────────────────── the algorithm

    public function test_codes_match_the_rfc_6238_vectors(): void
    {
        $vectors = [
            59 => '94287082',
            1111111109 => '07081804',
            1111111111 => '14050471',
            1234567890 => '89005924',
            2000000000 => '69279037',
            20000000000 => '65353130',
        ];

        foreach ($vectors as $time => $expected) {
            $this->assertSame($expected, Totp::at(self::RFC_SECRET, Totp::stepAt($time), 8), "T={$time}");
            $this->assertSame(substr($expected, -6), Totp::at(self::RFC_SECRET, Totp::stepAt($time)), "T={$time}");
        }

        $this->assertSame('12345678901234567890', Totp::base32Decode(self::RFC_SECRET));
        $this->assertSame(self::RFC_SECRET, Totp::base32Encode('12345678901234567890'));
    }

    public function test_a_code_is_accepted_one_step_either_side_and_no_further(): void
    {
        $now = 1_700_000_000;
        $step = Totp::stepAt($now);

        $this->assertSame($step - 1, Totp::verify(self::RFC_SECRET, Totp::at(self::RFC_SECRET, $step - 1), $now));
        $this->assertSame($step + 1, Totp::verify(self::RFC_SECRET, Totp::at(self::RFC_SECRET, $step + 1), $now));
        $this->assertNull(Totp::verify(self::RFC_SECRET, Totp::at(self::RFC_SECRET, $step - 2), $now));
        $this->assertNull(Totp::verify(self::RFC_SECRET, 'abcdef', $now));
    }

    // ────────────────────────────────────────────── enrolment

    public function test_an_admin_who_has_not_enrolled_is_sent_to_enrol(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/users')
            ->assertRedirect(route('admin.security.two-factor.show'));
    }

    public function test_enrolling_turns_it_on_and_lets_the_session_in(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.security.two-factor.show'))
            ->assertOk()
            ->assertSee('<svg', false);

        $secret = session(TwoFactor::PENDING_KEY);
        $this->assertIsString($secret);

        $this->post(route('admin.security.two-factor.confirm'), ['code' => $this->codeFor($secret)])
            ->assertRedirect(route('admin.security.two-factor.show'))
            ->assertSessionHas('two_factor_fresh_codes');

        $admin->refresh();
        $this->assertTrue(TwoFactor::enabled($admin));
        $this->assertSame(TwoFactor::RECOVERY_CODE_COUNT, TwoFactor::remainingRecoveryCodes($admin));
        $this->assertNotSame($secret, $admin->getRawOriginal('two_factor_secret'), 'the secret is stored encrypted');

        $this->get('/admin/users')->assertOk();
    }

    public function test_a_wrong_first_code_does_not_turn_it_on(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.security.two-factor.show'));
        $this->post(route('admin.security.two-factor.confirm'), ['code' => '000000'])->assertSessionHasErrors('code');

        $this->assertFalse(TwoFactor::enabled($admin->refresh()));
    }

    // ────────────────────────────────────────────── the second step

    public function test_an_enrolled_admin_is_asked_once_per_session(): void
    {
        [$admin, $secret] = $this->enrolledAdmin();

        $this->actingAs($admin)->get('/admin/users')->assertRedirect(route('two-factor.challenge'));
        $this->get(route('two-factor.challenge'))->assertOk();

        $this->post(route('two-factor.verify'), ['code' => $this->codeFor($secret)])
            ->assertRedirect('/admin/users');

        $this->get('/admin/users')->assertOk();
        $this->get('/admin/roles')->assertStatus(200);
    }

    public function test_a_wrong_code_is_refused_written_down_and_counted(): void
    {
        [$admin] = $this->enrolledAdmin();

        $this->actingAs($admin)->get('/admin/users');
        $this->post(route('two-factor.verify'), ['code' => '123456'])->assertSessionHasErrors('code');

        $this->get('/admin/users')->assertRedirect(route('two-factor.challenge'));

        $attempt = LoginAttempt::latest('id')->firstOrFail();
        $this->assertSame(LoginAttempt::OUTCOME_FAILED, $attempt->outcome);
        $this->assertSame('2fa', $attempt->method);
    }

    public function test_a_code_cannot_be_used_twice(): void
    {
        [$admin, $secret] = $this->enrolledAdmin();
        $code = $this->codeFor($secret);

        $this->assertTrue(TwoFactor::attempt($admin, $code));
        $this->assertFalse(TwoFactor::attempt($admin, $code), 'a code seen over a shoulder must not work again');
    }

    public function test_a_recovery_code_works_once(): void
    {
        [$admin, , $recovery] = $this->enrolledAdmin();

        $this->actingAs($admin)->get('/admin/users');
        $this->post(route('two-factor.verify'), ['code' => strtoupper($recovery[0])])->assertRedirect('/admin/users');

        $this->assertSame(TwoFactor::RECOVERY_CODE_COUNT - 1, TwoFactor::remainingRecoveryCodes($admin->refresh()));
        $this->assertFalse(TwoFactor::attempt($admin, $recovery[0]));
    }

    public function test_an_api_token_cannot_reach_admin_routes_of_an_enrolled_admin(): void
    {
        [$admin] = $this->enrolledAdmin();
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/sms-payment/notifications')->assertForbidden();
    }

    public function test_sso_asks_an_enrolled_admin_for_the_second_step_first(): void
    {
        [$admin] = $this->enrolledAdmin();

        $this->actingAs($admin)
            ->get('/auth/xdreamer/authorize?redirect_uri=x&state=y&code_challenge=' . str_repeat('a', 43))
            ->assertRedirect(route('two-factor.challenge'));
    }

    public function test_customers_are_never_asked(): void
    {
        $customer = User::create([
            'name' => 'Customer', 'email' => 'c@example.com', 'password' => bcrypt('secret-password'),
            'is_active' => true, 'role' => 'user',
        ]);

        $this->actingAs($customer)->get(route('two-factor.challenge'))->assertRedirect(route('home'));
        $this->assertNull(TwoFactor::gate(request(), $customer));
    }

    // ────────────────────────────────────────────── ways back

    public function test_moving_to_a_new_phone_needs_a_current_code(): void
    {
        [$admin, $secret] = $this->enrolledAdmin();

        $this->actingAs($admin)->get('/admin/users');
        $this->post(route('two-factor.verify'), ['code' => $this->codeFor($secret)]);

        $this->post(route('admin.security.two-factor.reset'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertTrue(TwoFactor::enabled($admin->refresh()));

        Cache::flush(); // the step was just used by the challenge above
        $this->post(route('admin.security.two-factor.reset'), ['code' => $this->codeFor($secret)]);
        $this->assertFalse(TwoFactor::enabled($admin->refresh()));
    }

    public function test_the_server_command_resets_a_locked_out_admin(): void
    {
        [$admin] = $this->enrolledAdmin();

        $this->artisan('security:2fa-reset', ['email' => $admin->email, '--force' => true])->assertSuccessful();

        $this->assertFalse(TwoFactor::enabled($admin->refresh()));
    }

    public function test_switching_the_requirement_off_still_asks_an_enrolled_admin(): void
    {
        config(['security.two_factor.required_for_admins' => false]);

        $this->actingAs($this->admin())->get('/admin/users')->assertOk();

        [$enrolled] = $this->enrolledAdmin();
        $this->actingAs($enrolled)->get('/admin/users')->assertRedirect(route('two-factor.challenge'));
    }

    // ────────────────────────────────────────────── helpers

    private function admin(): User
    {
        return User::create([
            'name' => 'Owner',
            'email' => 'owner' . User::count() . '@example.com',
            'password' => bcrypt('secret-password'),
            'is_active' => true,
            'role' => 'super_admin',
        ]);
    }

    /** @return array{0: User, 1: string, 2: list<string>} */
    private function enrolledAdmin(): array
    {
        $admin = $this->admin();
        $secret = Totp::generateSecret();
        [$plain, $hashes] = TwoFactor::newRecoveryCodes();

        $admin->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $hashes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        return [$admin, $secret, $plain];
    }

    private function codeFor(string $secret): string
    {
        return Totp::at($secret, Totp::stepAt(time()));
    }
}
