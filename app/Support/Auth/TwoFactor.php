<?php

namespace App\Support\Auth;

use App\Models\TwoFactorTrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;
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

    /** Cookie on a browser the admin asked us to remember: "{device id}|{random token}". */
    public const TRUST_COOKIE = 'xm_2fa_trust';

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

    /**
     * Whether this session may use an admin power now: it has passed the second step, or the step
     * is not required of an admin who has not set it up. The answer gate() gives, as a yes/no for
     * an admin power reached outside /admin (opening a customer's payment slip).
     */
    public static function passed(Request $request, User $user): bool
    {
        return self::enabled($user) ? self::verified($request, $user) : ! self::required();
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
            if (self::verified($request, $user) || self::passedOnTrustedDevice($request, $user)) {
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

    // ────────────────────────────────────────────── remembered browsers

    /** Days a remembered browser may skip the second step; 0 turns the option off. */
    public static function trustDays(): int
    {
        return max(0, (int) config('security.two_factor.trust_days', 30));
    }

    /**
     * Remember this browser after a passed second step. The cookie carries a
     * random token; only its hash is stored, tied to the current authenticator.
     */
    public static function trustDevice(Request $request, User $user): SymfonyCookie
    {
        $token = Str::random(64);
        $days = self::trustDays();

        $device = TwoFactorTrustedDevice::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'secret_fingerprint' => self::secretFingerprint($user),
            'label' => self::deviceLabel((string) $request->userAgent()),
            'ip' => LoginLog::ip($request),
            'last_used_at' => now(),
            'expires_at' => now()->addDays($days),
        ]);

        return Cookie::make(
            self::TRUST_COOKIE,
            $device->id . '|' . $token,
            $days * 24 * 60,
            '/',
            null,
            config('session.secure') ?? $request->isSecure(),
            true,
            false,
            'lax',
        );
    }

    /**
     * A browser remembered for this user, still in date, passed with the
     * authenticator the account uses now — counts as the second step for this
     * session. Anything else in the cookie is ignored.
     */
    public static function passedOnTrustedDevice(Request $request, User $user): bool
    {
        $device = self::trustedDevice($request, $user);

        if (! $device || ! $request->hasSession()) {
            return false;
        }

        $device->forceFill(['last_used_at' => now(), 'ip' => LoginLog::ip($request)])->save();
        self::markVerified($request, $user);
        LoginLog::success($user, '2fa-device');

        return true;
    }

    /** The remembered-device row this request's cookie points at, if it is still good. */
    public static function trustedDevice(Request $request, User $user): ?TwoFactorTrustedDevice
    {
        $cookie = $request->cookie(self::TRUST_COOKIE);

        if (! is_string($cookie) || ! preg_match('/^(\d+)\|([A-Za-z0-9]{64})$/', $cookie, $m)) {
            return null;
        }

        $device = TwoFactorTrustedDevice::query()
            ->whereKey((int) $m[1])
            ->where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->first();

        if (! $device
            || ! hash_equals($device->token_hash, hash('sha256', $m[2]))
            || ! hash_equals($device->secret_fingerprint, self::secretFingerprint($user))) {
            return null;
        }

        return $device;
    }

    /** Forget every remembered browser of this user (new phone, lost laptop, reset). */
    public static function forgetDevices(User $user): int
    {
        return TwoFactorTrustedDevice::where('user_id', $user->id)->delete();
    }

    /** Changes whenever the authenticator secret changes, without storing the secret again. */
    private static function secretFingerprint(User $user): string
    {
        return hash_hmac('sha256', (string) $user->two_factor_secret, (string) config('app.key'));
    }

    /** "Chrome · Windows" — enough for the owner to recognise the row. */
    private static function deviceLabel(string $userAgent): string
    {
        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') => 'Opera',
            str_contains($userAgent, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'เบราว์เซอร์',
        };

        $os = match (true) {
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS X') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => null,
        };

        return $os ? "{$browser} · {$os}" : $browser;
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
