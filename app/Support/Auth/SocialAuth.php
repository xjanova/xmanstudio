<?php

namespace App\Support\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * The shared half of "sign in with LINE / Google / Telegram".
 *
 * Each provider differs only in how it proves who you are. What happens after
 * — matching a returning user, attaching to an existing account, creating a
 * new one, refusing a disabled one — is identical, and is exactly where the
 * dangerous mistakes live. Keeping one copy means a fix lands for all three.
 *
 * Two rules the providers must not be allowed to break:
 *
 *  1. An account is only ever matched by email when the provider says that
 *     email is verified. Otherwise anyone who can put a victim's address into
 *     a provider profile owns the victim's orders, wallet and licences.
 *
 *  2. A disabled account stays disabled. is_active is checked here because it
 *     was checked nowhere in the LINE flow, which meant banning someone only
 *     closed the door they were least likely to use.
 */
final class SocialAuth
{
    /**
     * @param  string  $provider  line · google · telegram
     * @param  string  $providerId  the provider's stable user id
     * @param  array{name?:string|null,email?:string|null,email_verified?:bool,avatar?:string|null,extra?:array<string,mixed>}  $profile
     */
    public static function login(string $provider, string $providerId, array $profile): RedirectResponse
    {
        $idColumn = self::idColumn($provider);

        $user = User::where($idColumn, $providerId)->first();

        // ── returning user ────────────────────────────────────────────────
        if ($user) {
            if ($denied = self::refuseIfDisabled($user, $provider)) {
                return $denied;
            }

            $user->forceFill(self::providerColumns($provider, $providerId, $profile) + [
                'last_login_at' => now(),
            ])->save();

            return self::completeLogin($user, $provider, 'เข้าสู่ระบบด้วย ' . self::label($provider) . ' สำเร็จ');
        }

        $email = self::usableEmail($profile);

        // ── attach to an account that already exists ──────────────────────
        if ($email) {
            $existing = User::where('email', $email)->first();

            if ($existing) {
                if ($denied = self::refuseIfDisabled($existing, $provider)) {
                    return $denied;
                }

                $existing->forceFill(self::providerColumns($provider, $providerId, $profile) + [
                    'last_login_at' => now(),
                ])->save();

                return self::completeLogin(
                    $existing,
                    $provider,
                    'เชื่อมบัญชี ' . self::label($provider) . ' กับบัญชีเดิมของคุณแล้ว'
                );
            }
        }

        // ── brand new ─────────────────────────────────────────────────────
        $user = new User;

        $user->forceFill(self::providerColumns($provider, $providerId, $profile) + [
            'name' => Str::limit(trim((string) ($profile['name'] ?? '')) ?: self::label($provider) . ' User', 60, ''),
            // A provider that gives no verified address still needs a unique
            // one for the column. It is deliberately at a domain we own and
            // cannot receive mail, so nothing is ever sent to it and nobody
            // can register it elsewhere and claim the account.
            'email' => $email ?: self::placeholderEmail($provider, $providerId),
            'email_verified_at' => $email ? now() : null,
            'password' => bcrypt(Str::random(40)),
            // Null on purpose: the account has no password its owner knows, so
            // unlinking the provider would lock them out. Both the profile page
            // and the unlink routes read this.
            'password_set_at' => null,
            'is_active' => true,
            // Signing up through LINE has always recorded consent at that
            // moment (the column defaults to true either way); keeping the
            // timestamp means the record of when still exists.
            'marketing_consent_at' => $provider === 'line' ? now() : null,
        ])->save();

        return self::completeLogin(
            $user,
            $provider,
            'สร้างบัญชีด้วย ' . self::label($provider) . ' เรียบร้อย',
            firstTime: true
        );
    }

    /**
     * Attach a provider to the account already signed in.
     */
    public static function link(User $user, string $provider, string $providerId, array $profile): RedirectResponse
    {
        $idColumn = self::idColumn($provider);

        $taken = User::where($idColumn, $providerId)->where('id', '!=', $user->id)->exists();

        if ($taken) {
            return redirect()->route('profile.edit')
                ->with('error', 'บัญชี ' . self::label($provider) . ' นี้ถูกเชื่อมกับผู้ใช้อื่นอยู่แล้ว');
        }

        $user->forceFill(self::providerColumns($provider, $providerId, $profile))->save();

        return redirect()->route('profile.edit')
            ->with('success', 'เชื่อมบัญชี ' . self::label($provider) . ' สำเร็จ');
    }

    /**
     * Detach a provider — unless it is the only way the owner can get back in.
     */
    public static function unlink(User $user, string $provider): RedirectResponse
    {
        if (! self::canUnlink($user, $provider)) {
            return redirect()->route('profile.edit')
                ->with('error', 'กรุณาตั้งรหัสผ่าน (หรือเชื่อมช่องทางอื่น) ก่อนยกเลิก ' . self::label($provider)
                    . ' — ไม่เช่นนั้นคุณจะเข้าบัญชีนี้ไม่ได้อีก');
        }

        $user->forceFill(array_fill_keys(self::columnsFor($provider), null))->save();

        return redirect()->route('profile.edit')
            ->with('success', 'ยกเลิกการเชื่อม ' . self::label($provider) . ' แล้ว');
    }

    /**
     * Would the owner still have a way in after removing this provider?
     *
     * A usable password counts, and so does any other linked provider. The
     * password column is never empty — social signups get a random one — so
     * password_set_at is what actually answers "does a human know it".
     */
    public static function canUnlink(User $user, string $provider): bool
    {
        if ($user->password_set_at !== null) {
            return true;
        }

        foreach (['line', 'google', 'telegram'] as $other) {
            if ($other !== $provider && $user->{self::idColumn($other)}) {
                return true;
            }
        }

        return false;
    }

    // ================================================================ internals

    private static function completeLogin(User $user, string $provider, string $message, bool $firstTime = false): RedirectResponse
    {
        // Written before Auth::login so the Login event listener sees it and
        // does not add a second, wrongly-labelled row.
        LoginLog::success($user, $provider);

        Auth::login($user, true);

        // Session fixation: the visitor arrived holding a session id, and a
        // sign-in must not keep it. The password flow does this in
        // AuthenticatedSessionController; the LINE flow never did.
        request()->session()->regenerate();

        $target = $firstTime ? route('profile.edit') : route('customer.dashboard');

        return redirect()->intended($target)->with('success', $message);
    }

    /**
     * A disabled account must not be let in through a side door.
     */
    private static function refuseIfDisabled(User $user, string $provider): ?RedirectResponse
    {
        if ($user->is_active) {
            return null;
        }

        LoginLog::failed($user->email, $user, $provider);

        return redirect()->route('login')
            ->with('error', 'บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อทีมงาน');
    }

    /**
     * The address we are willing to match an existing account on.
     *
     * Unverified is treated as absent. Google will hand over whatever address
     * is on the profile, verified or not, and matching on an unverified one is
     * the whole account-takeover bug in a single line.
     */
    private static function usableEmail(array $profile): ?string
    {
        $email = trim((string) ($profile['email'] ?? ''));

        if ($email === '' || ! ($profile['email_verified'] ?? false)) {
            return null;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? strtolower($email) : null;
    }

    private static function placeholderEmail(string $provider, string $providerId): string
    {
        return $provider . '_' . substr(sha1($providerId), 0, 20) . '@no-reply.' . parse_url(config('app.url'), PHP_URL_HOST);
    }

    /** @return array<string,mixed> */
    private static function providerColumns(string $provider, string $providerId, array $profile): array
    {
        return match ($provider) {
            'google' => [
                'google_id' => $providerId,
                'google_avatar' => $profile['avatar'] ?? null,
            ],
            'telegram' => [
                'telegram_id' => $providerId,
                'telegram_username' => $profile['extra']['username'] ?? null,
                'telegram_avatar' => $profile['avatar'] ?? null,
            ],
            'line' => [
                'line_uid' => $providerId,
                'line_display_name' => $profile['name'] ?? null,
                'line_picture_url' => $profile['avatar'] ?? null,
            ],
            default => [],
        };
    }

    /** @return list<string> */
    private static function columnsFor(string $provider): array
    {
        return match ($provider) {
            'google' => ['google_id', 'google_avatar'],
            'telegram' => ['telegram_id', 'telegram_username', 'telegram_avatar'],
            'line' => ['line_uid', 'line_display_name', 'line_picture_url', 'line_access_token', 'line_refresh_token'],
            default => [],
        };
    }

    public static function idColumn(string $provider): string
    {
        return match ($provider) {
            'google' => 'google_id',
            'telegram' => 'telegram_id',
            default => 'line_uid',
        };
    }

    public static function label(string $provider): string
    {
        return match ($provider) {
            'google' => 'Google',
            'telegram' => 'Telegram',
            'line' => 'LINE',
            default => $provider,
        };
    }
}
