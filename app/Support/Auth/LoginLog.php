<?php

namespace App\Support\Auth;

use App\Models\BlockedIp;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Support\Alerts\SecurityAlerts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Writes down every sign-in attempt, and decides when an address has had enough.
 *
 * The alerts in SecurityAlerts already tell the owner in Telegram when someone
 * is guessing; this is the part that remembers it afterwards and the part that
 * shuts the door. Kept apart from the alerting on purpose — one is a message,
 * the other is a decision.
 *
 * Nothing here may throw. It runs inside the auth events, so a failure here
 * would turn a wrong password into a 500 and a successful login into a
 * white screen.
 */
final class LoginLog
{
    /**
     * Whether a success row was already written during this request.
     *
     * The Login event fires for every sign-in, including the ones the social
     * controllers have already written down with the provider they came from.
     * Without this, a Google sign-in would appear twice — once as "Google" and
     * once as "อีเมล + รหัสผ่าน", which is worse than not appearing at all.
     */
    private static bool $recorded = false;

    public static function alreadyRecorded(): bool
    {
        return self::$recorded;
    }

    public static function success(?User $user, string $method = 'password'): void
    {
        self::$recorded = true;

        self::record(
            email: $user?->email,
            user: $user,
            outcome: LoginAttempt::OUTCOME_SUCCESS,
            method: $method,
        );
    }

    public static function failed(?string $email, ?User $account, string $method = 'password'): void
    {
        self::record(
            email: $email,
            user: $account,
            outcome: LoginAttempt::OUTCOME_FAILED,
            method: $method,
        );

        self::considerBlocking();
    }

    public static function lockout(?string $email, string $method = 'password'): void
    {
        self::record(
            email: $email,
            user: null,
            outcome: LoginAttempt::OUTCOME_LOCKOUT,
            method: $method,
        );

        self::considerBlocking();
    }

    public static function turnstileFailed(?string $email, string $method = 'password'): void
    {
        self::record(
            email: $email,
            user: null,
            outcome: LoginAttempt::OUTCOME_TURNSTILE,
            method: $method,
        );
    }

    public static function blocked(?string $email = null): void
    {
        self::record(
            email: $email,
            user: null,
            outcome: LoginAttempt::OUTCOME_BLOCKED,
            method: 'password',
        );
    }

    // ============================================================== the write

    private static function record(?string $email, ?User $user, string $outcome, string $method): void
    {
        try {
            $request = request();

            // The account is looked up only when the caller did not already
            // have it, and only to answer "was an admin the target" — never to
            // tell the visitor whether the address exists.
            if ($user === null && $email) {
                $user = User::where('email', $email)->first();
            }

            LoginAttempt::create([
                'email' => $email ? mb_substr($email, 0, 255) : null,
                'user_id' => $user?->id,
                'ip' => self::ip($request),
                'country' => self::country($request),
                'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 512) : null,
                'outcome' => $outcome,
                'method' => $method,
                'is_admin_target' => (bool) $user?->isAdmin(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('LoginLog write failed', ['error' => $e->getMessage()]);
        }
    }

    // ========================================================== the decision

    /**
     * Has this address done enough to be refused outright?
     *
     * Two ways to trip it, because a break-in run comes in two shapes. One is
     * loud — the same account hammered until something gives. The other is
     * quiet — one try each across a stolen address list, which stays under any
     * per-account limit while being the less excusable of the two.
     */
    private static function considerBlocking(): void
    {
        try {
            $config = config('security.auto_block');

            if (! ($config['enabled'] ?? false)) {
                return;
            }

            $ip = self::ip(request());

            if ($ip === null || $ip === '') {
                return;
            }

            // Already refused — no need to count again, and no second alert.
            if (BlockedIp::findActive($ip)) {
                return;
            }

            if (self::isTrustedAddress($ip)) {
                return;
            }

            $since = now()->subMinutes(max(1, (int) $config['window_minutes']));

            $recent = LoginAttempt::query()
                ->where('ip', $ip)
                ->unsuccessful()
                ->since($since);

            $failures = (clone $recent)->count();
            $accounts = (clone $recent)->whereNotNull('email')->distinct()->count('email');

            $byFailures = $failures >= (int) $config['ip_failures'];
            $byAccounts = $accounts >= (int) $config['ip_accounts'];

            if (! $byFailures && ! $byAccounts) {
                return;
            }

            $reason = $byAccounts && ! $byFailures
                ? "ลองเข้าสู่ระบบ {$accounts} บัญชีต่างกันใน {$config['window_minutes']} นาที"
                : "เข้าสู่ระบบไม่สำเร็จ {$failures} ครั้งใน {$config['window_minutes']} นาที";

            $minutes = max(1, (int) $config['block_minutes']);

            BlockedIp::block(
                ip: $ip,
                reason: $reason,
                until: now()->addMinutes($minutes),
                source: BlockedIp::SOURCE_AUTO,
            );

            SecurityAlerts::ipAutoBlocked($ip, $reason, $minutes);
        } catch (\Throwable $e) {
            Log::warning('LoginLog auto-block failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Addresses the automatic blocker is not allowed to touch.
     *
     * An admin who fat-fingers a password twenty times is indistinguishable
     * from a bot by the counters alone — and locking the owner out of their own
     * admin panel from their own office is a far worse outcome than letting one
     * run of guesses continue for a while. An address an admin has signed in
     * from successfully within the window below is therefore off limits, as is
     * anything an operator listed in config.
     *
     * The alert still fires, so the run is not invisible; only the automatic
     * refusal is withheld. A manual block from the dashboard still works.
     */
    public static function isTrustedAddress(string $ip): bool
    {
        if (in_array($ip, (array) config('security.auto_block.never_block', []), true)) {
            return true;
        }

        return LoginAttempt::query()
            ->where('ip', $ip)
            ->where('outcome', LoginAttempt::OUTCOME_SUCCESS)
            ->where('is_admin_target', true)
            ->where('created_at', '>=', now()->subDays(
                max(1, (int) config('security.auto_block.admin_grace_days', 30))
            ))
            ->exists();
    }

    // ============================================================== the client

    public static function ip(?Request $request): ?string
    {
        if (! $request) {
            return null;
        }

        return $request->ip();
    }

    /**
     * Cloudflare's guess at the visitor's country.
     *
     * Only read when the request actually arrived through a proxy we trust.
     * CF-IPCountry is just a header: from a direct connection it is whatever
     * the client felt like typing, and a flag next to a row in the admin panel
     * should not be something the attacker chooses.
     */
    public static function country(?Request $request): ?string
    {
        if (! $request || ! $request->isFromTrustedProxy()) {
            return null;
        }

        $country = strtoupper(trim((string) $request->header('CF-IPCountry')));

        return preg_match('/^[A-Z]{2}$/', $country) ? $country : null;
    }
}
