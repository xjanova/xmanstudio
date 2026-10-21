<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Auth\SocialAuth;
use App\Support\Auth\SocialProviders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sign in with Google, over plain OAuth 2.0.
 *
 * Written out rather than pulled in through Socialite, to match the LINE flow
 * already here and to avoid adding a dependency for two endpoints. The parts
 * that matter are the state check below and the fact that the profile is read
 * from Google's own userinfo endpoint over TLS — never from the id_token's
 * payload without verification, which is the usual shortcut and the usual bug.
 */
class GoogleLoginController extends Controller
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const USERINFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';

    public function redirect(Request $request)
    {
        if (! SocialProviders::googleEnabled()) {
            return redirect()->route('login')->with('error', 'ยังไม่ได้เปิดใช้งานการเข้าสู่ระบบด้วย Google');
        }

        $state = Str::random(40);

        $request->session()->put('google_oauth_state', $state);
        $request->session()->put('google_oauth_link', $request->boolean('link') && Auth::check());

        return redirect(self::AUTH_URL . '?' . http_build_query([
            'client_id' => SocialProviders::googleClientId(),
            'redirect_uri' => route('google.callback'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            // Without this Google silently reuses the last account on a shared
            // browser, which on a family machine signs the wrong person in.
            'prompt' => 'select_account',
        ]));
    }

    public function callback(Request $request)
    {
        $expected = $request->session()->pull('google_oauth_state');
        $linking = (bool) $request->session()->pull('google_oauth_link', false);

        // Constant-time, and refusing an absent state rather than comparing
        // null to null — a callback with no state in session is a callback
        // that did not start here.
        if (! $expected || ! is_string($request->input('state')) || ! hash_equals($expected, $request->input('state'))) {
            return redirect()->route('login')->with('error', 'คำขอไม่ถูกต้องหรือหมดอายุ กรุณาลองใหม่');
        }

        if ($request->has('error')) {
            // access_denied is the user pressing cancel — not worth an error banner.
            return $request->input('error') === 'access_denied'
                ? redirect()->route('login')
                : redirect()->route('login')->with('error', 'Google ปฏิเสธคำขอ: ' . Str::limit((string) $request->input('error'), 60));
        }

        $code = $request->input('code');

        if (! is_string($code) || $code === '') {
            return redirect()->route('login')->with('error', 'ไม่ได้รับรหัสยืนยันจาก Google');
        }

        $profile = $this->fetchProfile($code);

        if (! $profile) {
            return redirect()->route('login')->with('error', 'เชื่อมต่อ Google ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        $data = [
            'name' => $profile['name'] ?? null,
            'email' => $profile['email'] ?? null,
            // Google sends this as a real boolean or the string "true"
            // depending on the endpoint; both must read as verified.
            'email_verified' => filter_var($profile['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'avatar' => $profile['picture'] ?? null,
        ];

        if ($linking && Auth::check()) {
            return SocialAuth::link($request->user(), 'google', (string) $profile['sub'], $data);
        }

        return SocialAuth::login('google', (string) $profile['sub'], $data);
    }

    public function unlink(Request $request)
    {
        return SocialAuth::unlink($request->user(), 'google');
    }

    /**
     * Swap the code for a token, then ask Google who it belongs to.
     *
     * @return array<string,mixed>|null
     */
    private function fetchProfile(string $code): ?array
    {
        try {
            $token = Http::asForm()->timeout(10)->post(self::TOKEN_URL, [
                'code' => $code,
                'client_id' => SocialProviders::googleClientId(),
                'client_secret' => SocialProviders::googleClientSecret(),
                'redirect_uri' => route('google.callback'),
                'grant_type' => 'authorization_code',
            ]);

            if (! $token->successful()) {
                Log::error('Google token exchange failed', ['status' => $token->status(), 'body' => $token->body()]);

                return null;
            }

            $accessToken = $token->json('access_token');

            if (! $accessToken) {
                return null;
            }

            $profile = Http::withToken($accessToken)->timeout(10)->get(self::USERINFO_URL);

            if (! $profile->successful() || ! $profile->json('sub')) {
                Log::error('Google userinfo failed', ['status' => $profile->status()]);

                return null;
            }

            return $profile->json();
        } catch (\Throwable $e) {
            Log::error('Google OAuth exception', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
