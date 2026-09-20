<?php

namespace Tests\Feature;

use App\Models\BlockedIp;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Support\Auth\LoginLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * The front door: what gets written down, and when an address stops being let in.
 *
 * The throttle in LoginRequest was never the whole answer. It slows one
 * IP+email pair for a minute and then opens again, forever — the right shape
 * for someone who mistyped, the wrong one for a script happy to spend the
 * night at five a minute. These tests cover the part that turns "slow" into
 * "no", and the guards that stop it turning on the owner.
 *
 * The rules:
 *   — every attempt is written down, successful or not
 *   — two shapes of attack trip the blocker: loud (many failures) and quiet
 *     (one try each across an address list)
 *   — an address an admin uses is never blocked automatically
 *   — the block list fails OPEN; a database problem must not 403 the site
 */
class LoginAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        RateLimiter::clear('login');

        config([
            'security.auto_block.enabled' => true,
            'security.auto_block.ip_failures' => 20,
            'security.auto_block.ip_accounts' => 8,
            'security.auto_block.window_minutes' => 15,
            'security.auto_block.block_minutes' => 60,
            'security.auto_block.admin_grace_days' => 30,
            'security.auto_block.never_block' => [],
        ]);
    }

    // ────────────────────────────────────────────── the log

    public function test_a_wrong_password_is_written_down(): void
    {
        $user = $this->member();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);

        $attempt = LoginAttempt::latest('id')->firstOrFail();

        $this->assertSame(LoginAttempt::OUTCOME_FAILED, $attempt->outcome);
        $this->assertSame($user->email, $attempt->email);
        $this->assertSame($user->id, $attempt->user_id);
        $this->assertFalse($attempt->is_admin_target);
    }

    public function test_a_successful_sign_in_is_written_down_once(): void
    {
        $user = $this->member();

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password']);

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, LoginAttempt::where('outcome', LoginAttempt::OUTCOME_SUCCESS)->count(),
            'the Login event and the controller must not each write a row');
    }

    /** The address typed is the signal, even when no such account exists. */
    public function test_an_attempt_on_an_account_that_does_not_exist_is_still_logged(): void
    {
        $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'guess']);

        $attempt = LoginAttempt::latest('id')->firstOrFail();

        $this->assertSame('nobody@example.com', $attempt->email);
        $this->assertNull($attempt->user_id);
    }

    public function test_an_attempt_on_an_admin_account_is_flagged(): void
    {
        $admin = $this->member(['role' => 'admin', 'email' => 'boss@example.com']);

        $this->post('/login', ['email' => $admin->email, 'password' => 'wrong-password']);

        $this->assertTrue(LoginAttempt::latest('id')->firstOrFail()->is_admin_target);
    }

    /** Staff reading the dashboard do not need every customer's full address. */
    public function test_the_logged_address_is_masked_for_display(): void
    {
        $attempt = LoginAttempt::create([
            'email' => 'somchai@example.com',
            'ip' => '203.0.113.1',
            'outcome' => LoginAttempt::OUTCOME_FAILED,
            'method' => 'password',
            'created_at' => now(),
        ]);

        $this->assertSame('so*****@example.com', $attempt->maskedEmail());
        $this->assertStringNotContainsString('somchai@', $attempt->maskedEmail());
    }

    // ────────────────────────────────────────────── the blocker

    public function test_enough_failures_from_one_address_earns_a_block(): void
    {
        $this->failFrom('198.51.100.10', 21, sameAccount: true);

        $block = BlockedIp::findActive('198.51.100.10');

        $this->assertNotNull($block);
        $this->assertSame(BlockedIp::SOURCE_AUTO, $block->source);
        $this->assertTrue($block->expires_at->isFuture());
    }

    /**
     * Credential stuffing spreads thin — one try each across a stolen list —
     * so it stays under any per-account limit while being far more obviously
     * a bot than someone who forgot which password they used.
     */
    public function test_touching_many_accounts_earns_a_block_well_under_the_failure_count(): void
    {
        $this->failFrom('198.51.100.11', 8, sameAccount: false);

        $this->assertNotNull(BlockedIp::findActive('198.51.100.11'));
        $this->assertLessThan(
            config('security.auto_block.ip_failures'),
            LoginAttempt::where('ip', '198.51.100.11')->count(),
            'the stuffing rule has to fire before the raw failure count does'
        );
    }

    public function test_a_handful_of_failures_is_not_an_attack(): void
    {
        $this->failFrom('198.51.100.12', 5, sameAccount: true);

        $this->assertNull(BlockedIp::findActive('198.51.100.12'));
    }

    public function test_failures_outside_the_window_do_not_count(): void
    {
        $this->failFrom('198.51.100.13', 19, sameAccount: true);

        LoginAttempt::where('ip', '198.51.100.13')->update(['created_at' => now()->subHours(2)]);

        $this->failFrom('198.51.100.13', 2, sameAccount: true);

        $this->assertNull(BlockedIp::findActive('198.51.100.13'),
            'an address that failed a lot yesterday is not an attack today');
    }

    public function test_the_blocker_can_be_switched_off_while_the_log_keeps_running(): void
    {
        config(['security.auto_block.enabled' => false]);

        $this->failFrom('198.51.100.14', 25, sameAccount: false);

        $this->assertNull(BlockedIp::findActive('198.51.100.14'));
        $this->assertSame(25, LoginAttempt::where('ip', '198.51.100.14')->count());
    }

    // ────────────────────────────────────────────── not locking the owner out

    public function test_an_address_an_admin_signs_in_from_is_never_blocked_automatically(): void
    {
        $ip = '198.51.100.20';

        LoginAttempt::create([
            'email' => 'boss@example.com',
            'ip' => $ip,
            'outcome' => LoginAttempt::OUTCOME_SUCCESS,
            'is_admin_target' => true,
            'method' => 'password',
            'created_at' => now()->subDay(),
        ]);

        $this->failFrom($ip, 30, sameAccount: false);

        $this->assertNull(BlockedIp::findActive($ip),
            'locking the owner out of their own office is worse than the run continuing');
    }

    public function test_an_old_admin_sign_in_stops_protecting_the_address(): void
    {
        $ip = '198.51.100.21';

        LoginAttempt::create([
            'email' => 'boss@example.com',
            'ip' => $ip,
            'outcome' => LoginAttempt::OUTCOME_SUCCESS,
            'is_admin_target' => true,
            'method' => 'password',
            'created_at' => now()->subDays(90),
        ]);

        $this->failFrom($ip, 30, sameAccount: false);

        $this->assertNotNull(BlockedIp::findActive($ip));
    }

    public function test_a_configured_address_is_never_blocked(): void
    {
        config(['security.auto_block.never_block' => ['198.51.100.22']]);

        $this->failFrom('198.51.100.22', 30, sameAccount: false);

        $this->assertNull(BlockedIp::findActive('198.51.100.22'));
    }

    // ────────────────────────────────────────────── the block itself

    public function test_a_blocked_address_is_refused_the_whole_site(): void
    {
        BlockedIp::block('198.51.100.30', 'test', now()->addHour());

        $response = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.30'])->get('/');

        $response->assertStatus(403);
        $response->assertHeader('Retry-After');
    }

    public function test_a_blocked_address_gets_json_when_it_asks_for_json(): void
    {
        BlockedIp::block('198.51.100.31', 'test', now()->addHour());

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.31'])
            ->getJson('/api/v1/ping')
            ->assertStatus(403)
            ->assertJsonPath('code', 'IP_BLOCKED');
    }

    public function test_an_expired_block_lets_the_visitor_back_in(): void
    {
        BlockedIp::block('198.51.100.32', 'test', now()->addHour());
        BlockedIp::query()->where('ip', '198.51.100.32')->update(['expires_at' => now()->subMinute()]);
        BlockedIp::forget('198.51.100.32');

        // Not assertSuccessful: with no admin user the setup wizard redirects
        // '/' anyway. What matters is that the refusal is gone.
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.32'])
            ->get('/')
            ->assertStatus(302);
    }

    public function test_unblocking_takes_effect_despite_the_cache(): void
    {
        BlockedIp::block('198.51.100.33', 'test', now()->addHour());
        $this->assertNotNull(BlockedIp::findActive('198.51.100.33'));

        BlockedIp::unblock('198.51.100.33');

        $this->assertNull(BlockedIp::findActive('198.51.100.33'),
            'an admin who presses unblock must not be told to wait for a cache');
    }

    /**
     * An operator's decision outranks the counter: an address blocked by hand,
     * for good, must not quietly start expiring in an hour because the
     * automatic rule tripped again.
     */
    public function test_an_automatic_block_never_downgrades_a_manual_one(): void
    {
        BlockedIp::block('198.51.100.34', 'by hand, permanently', null, BlockedIp::SOURCE_MANUAL);
        BlockedIp::block('198.51.100.34', 'automatic, one hour', now()->addHour(), BlockedIp::SOURCE_AUTO);

        $block = BlockedIp::findActive('198.51.100.34');

        $this->assertSame(BlockedIp::SOURCE_MANUAL, $block->source);
        $this->assertTrue($block->isPermanent());
    }

    public function test_a_refused_request_is_counted(): void
    {
        BlockedIp::block('198.51.100.35', 'test', now()->addHour());

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.35'])->get('/');
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.35'])->get('/');

        $this->assertGreaterThanOrEqual(2, BlockedIp::where('ip', '198.51.100.35')->value('hits'));
    }

    // ────────────────────────────────────────────── the spoofing hole

    /**
     * The whole reason any of this works.
     *
     * trustProxies used to be '*', so the client's own X-Forwarded-For was
     * believed: five wrong passwords cost one header change to reset, and
     * every IP in this log was whatever the attacker felt like typing.
     */
    public function test_a_forged_forwarded_header_from_an_untrusted_client_is_ignored(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.40'])
            ->withHeaders(['X-Forwarded-For' => '1.2.3.4'])
            ->post('/login', ['email' => 'nobody@example.com', 'password' => 'guess']);

        $this->assertSame('198.51.100.40', LoginAttempt::latest('id')->firstOrFail()->ip,
            'an untrusted client must not be able to choose the IP we throttle and log');
    }

    /** CF-IPCountry is just a header, and from a direct connection it is a lie. */
    public function test_a_forged_country_header_is_ignored(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.41'])
            ->withHeaders(['CF-IPCountry' => 'ZZ'])
            ->post('/login', ['email' => 'nobody@example.com', 'password' => 'guess']);

        $this->assertNull(LoginAttempt::latest('id')->firstOrFail()->country);
    }

    // ────────────────────────────────────────────── helpers

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

    /**
     * Drive LoginLog directly rather than through the HTTP form: the form's
     * own throttle would stop us long before the blocker's thresholds, which
     * is exactly the gap the blocker exists to close.
     */
    private function failFrom(string $ip, int $times, bool $sameAccount): void
    {
        $request = Request::create('/login', 'POST', [], [], [], ['REMOTE_ADDR' => $ip]);
        app()->instance('request', $request);

        for ($i = 0; $i < $times; $i++) {
            LoginLog::failed($sameAccount ? 'target@example.com' : "target{$i}@example.com", null);
        }
    }
}
