<?php

namespace App\Support\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Two-step sign-in for the admin panel.
 *
 * The check lives on the admin gate, not on the login form. Every way into a
 * session — the password form, Google/LINE/Telegram, a remember-me cookie —
 * ends up at the same admin middleware, so a session reaches the panel only
 * after this session has also passed the second step. Customers are never
 * asked for it.
 */
final class TwoFactor
{
    /** Session key holding the id of the user who passed the second step. */
    private const SESSION_KEY = 'two_factor.verified_user';

    /** Session key holding a secret shown on the setup page, not yet confirmed. */
    public const PENDING_KEY = 'two_factor.pending_secret';

    public const RECOVERY_CODE_COUNT = 8;

    public static function required(): bool
    {
        return (bool) config('security.two_factor.required_for_admins', true);
    }

    public static function enabled(User $user): bool
    {
        return $user->two_factor_confirmed_at !== null && filled($user->two_factor_secret);
    }

    public static function verified(Request $request, User $user): bool
    {
        return $request->hasSession()
            && (int) $request->session()->get(self::SESSION_KEY) === (int) $user->id;
    }

    public static function markVerified(Request $request, User $user): void
    {
        // A new session id for the new level of trust, as at sign-in.
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, $user->id);
    }

    /**
     * Where an admin request has to go first, or null when it may pass.
     *
     * A request without a session (an API token) cannot have passed the second
     * step, so admin powers are not reachable that way at all.
     */
    public static function gate(Request $request, User $user): ?Response
    {
        if (! $user->isAdmin()) {
            return null;
        }

        if (self::enabled($user)) {
            if (self::verified($request, $user)) {
                return null;
            }

            return $request->expectsJson() || ! $request->hasSession()
                ? response()->json(['message' => 'ต้องยืนยันตัวตนสองขั้นผ่านหน้าเว็บก่อน'], 403)
                : redirect()->guest(route('two-factor.challenge'));
        }

        if (! self::required() || $request->routeIs('admin.security.two-factor.*')) {
            return null;
        }

        return $request->expectsJson() || ! $request->hasSession()
            ? response()->json(['message' => 'บัญชีผู้ดูแลต้องเปิดการยืนยันตัวตนสองขั้นก่อน'], 403)
            : redirect()->guest(route('admin.security.two-factor.show'));
    }

    /**
     * Check a code from the app, or a recovery code, against a user's settings.
     * A recovery code is spent by using it; an app code cannot be used twice.
     */
    public static function attempt(User $user, string $input): bool
    {
        $input = trim($input);

        if (preg_match('/^\d[\d\s]*$/', $input)) {
            return self::acceptStep($user, Totp::verify((string) $user->two_factor_secret, $input));
        }

        return self::spendRecoveryCode($user, $input);
    }

    /** Remember the step so the same six digits are refused next time. */
    public static function acceptStep(User $user, ?int $step): bool
    {
        if ($step === null) {
            return false;
        }

        $key = 'two_factor.last_step.' . $user->id;

        if ($step <= (int) Cache::get($key, -1)) {
            return false;
        }

        Cache::put($key, $step, now()->addMinutes(5));

        return true;
    }

    /**
     * Fresh recovery codes: the plain text goes to the screen once, only their
     * hashes are kept.
     *
     * @return array{0: list<string>, 1: list<string>} [plain codes, stored hashes]
     */
    public static function newRecoveryCodes(): array
    {
        $plain = [];

        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $plain[] = Str::lower(Str::random(5)) . '-' . Str::lower(Str::random(5));
        }

        return [$plain, array_map(fn ($code) => self::hashRecoveryCode($code), $plain)];
    }

    public static function remainingRecoveryCodes(User $user): int
    {
        return count((array) $user->two_factor_recovery_codes);
    }

    private static function spendRecoveryCode(User $user, string $input): bool
    {
        $hash = self::hashRecoveryCode($input);
        $codes = (array) $user->two_factor_recovery_codes;

        foreach ($codes as $index => $stored) {
            if (is_string($stored) && hash_equals($stored, $hash)) {
                unset($codes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    private static function hashRecoveryCode(string $code): string
    {
        return hash('sha256', strtolower(preg_replace('/\s+/', '', $code)));
    }
}
