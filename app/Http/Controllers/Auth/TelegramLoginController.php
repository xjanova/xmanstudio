<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Auth\SocialAuth;
use App\Support\Auth\SocialProviders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Sign in with Telegram.
 *
 * Not OAuth. Telegram's Login Widget sends the visitor's profile straight to
 * our callback as query parameters, signed with a key derived from the bot
 * token. There is no redirect to a provider and back, so there is nothing to
 * "exchange" — the whole security of this flow is the signature check in
 * verify() below, and if that check is wrong then anyone can sign in as
 * anyone by typing a URL.
 *
 * Three things the check must do, and all three have been the subject of real
 * bugs in other people's implementations:
 *
 *  1. Build the data-check string from every field except `hash`, sorted by
 *     key. Skipping an unexpected field lets an attacker add one.
 *  2. Compare with hash_equals, against HMAC-SHA256 keyed by SHA256(bot token).
 *  3. Reject anything older than a few minutes. Without this the signed URL
 *     from a browser history or a server log is a permanent password.
 */
class TelegramLoginController extends Controller
{
    /** How old a signed payload may be. Telegram's own examples use a day; that is far too long. */
    private const MAX_AGE_SECONDS = 300;

    public function callback(Request $request)
    {
        if (! SocialProviders::telegramEnabled()) {
            return redirect()->route('login')->with('error', 'ยังไม่ได้เปิดใช้งานการเข้าสู่ระบบด้วย Telegram');
        }

        $data = $request->query();

        $failure = $this->verify($data, SocialProviders::telegramBotToken());

        if ($failure !== null) {
            return redirect()->route('login')->with('error', $failure);
        }

        $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

        $profile = [
            'name' => $name !== '' ? $name : ($data['username'] ?? null),
            // Telegram never gives an email. SocialAuth makes a placeholder
            // and, because nothing is verified, will not attach this login to
            // an existing account by address.
            'email' => null,
            'email_verified' => false,
            'avatar' => $data['photo_url'] ?? null,
            'extra' => ['username' => $data['username'] ?? null],
        ];

        if ($request->session()->pull('telegram_link', false) && Auth::check()) {
            return SocialAuth::link($request->user(), 'telegram', (string) $data['id'], $profile);
        }

        return SocialAuth::login('telegram', (string) $data['id'], $profile);
    }

    /**
     * Ask to attach Telegram to the account that is already signed in.
     *
     * The widget posts straight to the callback, so the intent has to be
     * parked in the session beforehand.
     */
    public function linkStart(Request $request)
    {
        $request->session()->put('telegram_link', true);

        return redirect()->route('profile.edit')->with('info', 'กดปุ่ม Telegram เพื่อเชื่อมบัญชี');
    }

    public function unlink(Request $request)
    {
        return SocialAuth::unlink($request->user(), 'telegram');
    }

    /**
     * @param  array<string,mixed>  $data
     * @return string|null an error message, or null when the payload is genuine
     */
    private function verify(array $data, string $botToken): ?string
    {
        if ($botToken === '') {
            return 'ยังไม่ได้ตั้งค่าบอท Telegram';
        }

        if (empty($data['id']) || empty($data['hash']) || ! is_string($data['hash'])) {
            return 'ข้อมูลจาก Telegram ไม่ครบถ้วน';
        }

        $hash = $data['hash'];
        unset($data['hash']);

        // Every remaining field, sorted, as "key=value" joined by newlines —
        // including fields we do not use, because they were signed too.
        ksort($data);

        $pairs = [];

        foreach ($data as $key => $value) {
            // Nested values cannot appear in a genuine payload and would make
            // the string ambiguous, so their presence is itself a rejection.
            if (is_array($value)) {
                return 'ข้อมูลจาก Telegram ไม่ถูกต้อง';
            }

            $pairs[] = $key . '=' . $value;
        }

        $expected = hash_hmac('sha256', implode("\n", $pairs), hash('sha256', $botToken, true));

        if (! hash_equals($expected, $hash)) {
            return 'ลายเซ็นจาก Telegram ไม่ถูกต้อง';
        }

        $authDate = (int) ($data['auth_date'] ?? 0);

        // Future-dated as well as stale: a clock the attacker controls must not
        // buy an indefinitely valid payload.
        if ($authDate <= 0 || abs(time() - $authDate) > self::MAX_AGE_SECONDS) {
            return 'ลิงก์เข้าสู่ระบบหมดอายุแล้ว กรุณากดปุ่ม Telegram ใหม่อีกครั้ง';
        }

        return null;
    }
}
