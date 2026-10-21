<?php

namespace App\Support\Auth;

use App\Models\Setting;

/**
 * One place that decides whether a social sign-in button is real.
 *
 * Same shape, and same reason, as App\Support\Turnstile: the login page, the
 * register page, the profile page and each controller all need the answer, and
 * a button that appears while its controller refuses — or a controller that
 * works while no button exists — is the kind of drift nobody notices until a
 * customer reports it.
 *
 * Everything is dormant until keys are saved, so a fresh install shows no
 * social buttons rather than three broken ones.
 */
final class SocialProviders
{
    public const ALL = ['line', 'google', 'telegram'];

    // ───────────────────────────────────────────────────────────────── LINE

    public static function lineEnabled(): bool
    {
        return (bool) Setting::getValue('line_login_enabled', false)
            && trim((string) Setting::getValue('line_login_channel_id', '')) !== ''
            && trim((string) Setting::getValue('line_login_channel_secret', '')) !== '';
    }

    // ─────────────────────────────────────────────────────────────── Google

    public static function googleEnabled(): bool
    {
        return (bool) Setting::getValue('google_login_enabled', false)
            && self::googleClientId() !== ''
            && self::googleClientSecret() !== '';
    }

    public static function googleClientId(): string
    {
        return trim((string) Setting::getValue('google_login_client_id', ''));
    }

    public static function googleClientSecret(): string
    {
        return trim((string) Setting::getValue('google_login_client_secret', ''));
    }

    // ───────────────────────────────────────────────────────────── Telegram

    public static function telegramEnabled(): bool
    {
        return (bool) Setting::getValue('telegram_login_enabled', false)
            && self::telegramBotToken() !== ''
            && self::telegramBotUsername() !== '';
    }

    /**
     * The bot whose token signs the login payload.
     *
     * Falls back to the alerts bot, because most installs will have exactly
     * one bot and having to paste the same token twice invites pasting a
     * different one by mistake — at which point every signature fails and the
     * error says nothing useful.
     *
     * The bot still needs its domain registered with @BotFather (/setdomain)
     * before the widget will render at all.
     */
    public static function telegramBotToken(): string
    {
        $own = trim((string) Setting::getValue('telegram_login_bot_token', ''));

        return $own !== '' ? $own : trim((string) Setting::getValue('telegram_bot_token', ''));
    }

    public static function telegramBotUsername(): string
    {
        return ltrim(trim((string) Setting::getValue('telegram_login_bot_username', '')), '@');
    }

    // ───────────────────────────────────────────────────────────────── both

    public static function enabled(string $provider): bool
    {
        return match ($provider) {
            'line' => self::lineEnabled(),
            'google' => self::googleEnabled(),
            'telegram' => self::telegramEnabled(),
            default => false,
        };
    }

    /** True when at least one provider is usable — the login page hides the whole divider otherwise. */
    public static function anyEnabled(): bool
    {
        foreach (self::ALL as $provider) {
            if (self::enabled($provider)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    public static function enabledList(): array
    {
        return array_values(array_filter(self::ALL, fn (string $p) => self::enabled($p)));
    }
}
