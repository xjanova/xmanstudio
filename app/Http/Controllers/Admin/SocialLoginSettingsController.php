<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Auth\SocialProviders;
use Illuminate\Http\Request;

/**
 * Keys and switches for "sign in with Google / LINE / Telegram".
 *
 * Secrets follow the same rule as every other key page here: the field is
 * rendered empty with a masked placeholder, and an empty submission keeps
 * what is stored. Echoing bullets back as the value means the next save
 * overwrites the real key with the bullets.
 */
class SocialLoginSettingsController extends Controller
{
    public function index()
    {
        return view('admin.social-login.index', [
            'settings' => [
                'google_login_enabled' => (bool) Setting::getValue('google_login_enabled', false),
                'google_login_client_id' => (string) Setting::getValue('google_login_client_id', ''),
                'google_has_secret' => SocialProviders::googleClientSecret() !== '',

                'line_login_enabled' => (bool) Setting::getValue('line_login_enabled', false),
                'line_login_channel_id' => (string) Setting::getValue('line_login_channel_id', ''),
                'line_has_secret' => trim((string) Setting::getValue('line_login_channel_secret', '')) !== '',

                'telegram_login_enabled' => (bool) Setting::getValue('telegram_login_enabled', false),
                'telegram_login_bot_username' => (string) Setting::getValue('telegram_login_bot_username', ''),
                'telegram_has_own_token' => trim((string) Setting::getValue('telegram_login_bot_token', '')) !== '',
                'telegram_alerts_token' => trim((string) Setting::getValue('telegram_bot_token', '')) !== '',
            ],
            // What each provider must be told our callback is. Copy-paste
            // targets: a redirect URI that differs by one character fails with
            // an error message that names neither URI.
            'callbacks' => [
                'google' => route('google.callback'),
                'line' => route('line.callback'),
                'telegram' => parse_url(route('telegram.callback'), PHP_URL_HOST),
            ],
            // The live answer, not the stored switch — a provider can be
            // switched on and still be dormant for want of a key.
            'live' => [
                'google' => SocialProviders::googleEnabled(),
                'line' => SocialProviders::lineEnabled(),
                'telegram' => SocialProviders::telegramEnabled(),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'google_login_client_id' => ['nullable', 'string', 'max:255'],
            'google_login_client_secret' => ['nullable', 'string', 'max:255'],
            'line_login_channel_id' => ['nullable', 'string', 'max:64'],
            'line_login_channel_secret' => ['nullable', 'string', 'max:255'],
            // Telegram usernames are 5–32 of [A-Za-z0-9_] and bot names end in
            // "bot". Validated because a wrong one renders no widget at all
            // and gives no error to read.
            'telegram_login_bot_username' => ['nullable', 'string', 'regex:/^@?[A-Za-z0-9_]{5,32}$/'],
            'telegram_login_bot_token' => ['nullable', 'string', 'max:255', 'regex:/^\d{6,}:[A-Za-z0-9_-]{30,}$/'],
        ], [
            'telegram_login_bot_username.regex' => 'ชื่อบอทต้องเป็นตัวอักษร ตัวเลข หรือ _ ความยาว 5-32 ตัว',
            'telegram_login_bot_token.regex' => 'รูปแบบ Bot Token ไม่ถูกต้อง (ต้องเป็น 123456789:AA... จาก @BotFather)',
        ]);

        foreach (['google_login_enabled', 'line_login_enabled', 'telegram_login_enabled'] as $flag) {
            Setting::setValue($flag, $request->boolean($flag) ? '1' : '0', 'boolean', 'social_login');
        }

        foreach (['google_login_client_id', 'line_login_channel_id'] as $key) {
            Setting::setValue($key, $validated[$key] ?? '', 'string', 'social_login');
        }

        Setting::setValue(
            'telegram_login_bot_username',
            ltrim($validated['telegram_login_bot_username'] ?? '', '@'),
            'string',
            'social_login'
        );

        // Secrets: written only when a new one was typed, so saving the page
        // without touching them keeps the stored value.
        foreach (['google_login_client_secret', 'line_login_channel_secret', 'telegram_login_bot_token'] as $secret) {
            if (! empty($validated[$secret])) {
                Setting::setValue($secret, $validated[$secret], 'string', 'social_login');
            }
        }

        // Clearing a secret has to be possible too, and an empty box cannot
        // mean both "keep it" and "remove it".
        foreach ((array) $request->input('clear', []) as $key) {
            if (in_array($key, ['google_login_client_secret', 'line_login_channel_secret', 'telegram_login_bot_token'], true)) {
                Setting::setValue($key, '', 'string', 'social_login');
            }
        }

        return redirect()->route('admin.social-login.index')
            ->with('success', 'บันทึกการตั้งค่าการเข้าสู่ระบบด้วยโซเชียลแล้ว');
    }
}
