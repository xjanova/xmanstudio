<?php

namespace App\Support\Telegram;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * Which Telegram accounts may press the bot's buttons and use its commands — and which website admin
 * each one acts as.
 *
 * Being in the alert chat is NOT enough: a group has members who should see alerts but must not
 * approve payments, and a Telegram client can forge a button press. So every command and button is
 * checked against this list, and an account gets on it only by proving it belongs to a logged-in
 * admin: the admin page mints a one-time code, the admin opens t.me/<bot>?start=link_<code> in their
 * own Telegram, and the bot binds that Telegram user id to that admin.
 *
 * The binding is to a User, and the User must STILL be an admin at the moment of each action — take
 * someone's admin role away on the website and their Telegram buttons stop working with it.
 *
 * Stored in the `telegram_admins` setting: { "<telegram user id>": {user_id, name, tg, linked_at} }.
 */
final class TelegramAdmins
{
    private const CODE_MINUTES = 10;

    /** @return array<string,array{user_id:int,name:string,tg:string,linked_at:string}> */
    public static function all(): array
    {
        $raw = Setting::getValue('telegram_admins', []);
        $raw = is_array($raw) ? $raw : (json_decode((string) $raw, true) ?: []);

        return array_filter($raw, fn ($v, $k) => is_array($v) && isset($v['user_id']) && preg_match('/^\d{1,20}$/', (string) $k), ARRAY_FILTER_USE_BOTH);
    }

    /** The website admin a Telegram user acts as — null unless linked AND still an admin. */
    public static function user(int|string|null $telegramUserId): ?User
    {
        if ($telegramUserId === null || ! preg_match('/^\d{1,20}$/', (string) $telegramUserId)) {
            return null;
        }
        $entry = self::all()[(string) $telegramUserId] ?? null;
        if ($entry === null) {
            return null;
        }
        try {
            $user = User::find((int) $entry['user_id']);
        } catch (Throwable) {
            return null;
        }

        return self::stillAllowed($user) ? $user : null;
    }

    /** An admin whose account has not been switched off. */
    private static function stillAllowed(?User $user): bool
    {
        // is_active may come back as bool, int or "0" depending on the driver — (bool) handles all.
        return $user !== null && $user->isAdmin() && ($user->is_active === null || (bool) $user->is_active);
    }

    public static function link(int|string $telegramUserId, User $user, string $telegramName): void
    {
        $all = self::all();
        $all[(string) $telegramUserId] = [
            'user_id' => (int) $user->id,
            'name' => mb_substr((string) $user->name, 0, 80),
            'tg' => mb_substr($telegramName, 0, 80),
            'linked_at' => now()->toIso8601String(),
        ];
        Setting::setValue('telegram_admins', $all, 'json', 'telegram');
    }

    public static function unlink(string $telegramUserId): void
    {
        $all = self::all();
        unset($all[$telegramUserId]);
        Setting::setValue('telegram_admins', $all, 'json', 'telegram');
    }

    /**
     * A one-time code for $user, good for 10 minutes. Only its hash is stored, so the cache holds
     * nothing that could be replayed from a dump.
     */
    public static function issueCode(User $user): string
    {
        $code = Str::random(24);
        Cache::put('telegram:link:' . hash('sha256', $code), (int) $user->id, now()->addMinutes(self::CODE_MINUTES));

        return $code;
    }

    /** Spend a code: the admin it was issued to, or null (unknown, expired, used, or no longer admin). */
    public static function redeem(string $code): ?User
    {
        if (! preg_match('/^[A-Za-z0-9]{24}$/', $code)) {
            return null;
        }
        $userId = Cache::pull('telegram:link:' . hash('sha256', $code));
        if (! is_numeric($userId)) {
            return null;
        }
        $user = User::find((int) $userId);

        return self::stillAllowed($user) ? $user : null;
    }

    /** The link that opens the bot with the code — Telegram sends it back as "/start link_<code>". */
    public static function deepLink(string $code): ?string
    {
        $bot = TelegramBot::username();

        return $bot !== '' ? 'https://t.me/' . $bot . '?start=link_' . $code : null;
    }
}
