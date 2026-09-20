<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Auth\SocialAuth;
use App\Support\Auth\SocialProviders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sign in with LINE.
 *
 * The account handling used to live in this file and had three problems that
 * moving onto SocialAuth fixed for all providers at once:
 *
 *  - a disabled account (is_active = false) could still sign in here;
 *  - an existing account was matched on the email in LINE's id_token, whose
 *    payload was read without verifying the signature — so an id_token was
 *    trusted purely because it arrived over TLS;
 *  - the session id was not regenerated on login.
 *
 * The email is now taken from the verify endpoint, which checks the signature
 * and the audience for us, and only counts when LINE itself says it is there.
 */
class LineLoginController extends Controller
{
    public function redirect(Request $request)
    {
        if (! SocialProviders::lineEnabled()) {
            return redirect()->route('login')->with('error', 'LINE Login ยังไม่ได้เปิดใช้งาน');
        }

        $state = Str::random(40);

        $request->session()->put('line_login_state', $state);
        $request->session()->put('line_link_account', $request->boolean('link') && Auth::check());

        $authUrl = 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query([
            'response_type' => 'code',
            'client_id' => Setting::getValue('line_login_channel_id'),
            'redirect_uri' => route('line.callback'),
            'state' => $state,
            'scope' => 'profile openid email',
        ]);

        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        $expected = $request->session()->pull('line_login_state');
        $linking = (bool) $request->session()->pull('line_link_account', false);

        if (! $expected || ! is_string($request->input('state')) || ! hash_equals($expected, $request->input('state'))) {
            return redirect()->route('login')->with('error', 'คำขอไม่ถูกต้องหรือหมดอายุ กรุณาลองใหม่');
        }

        if ($request->has('error')) {
            return $request->input('error') === 'access_denied'
                ? redirect()->route('login')
                : redirect()->route('login')->with('error', 'LINE ปฏิเสธคำขอ: ' . Str::limit((string) $request->input('error_description'), 80));
        }

        $code = $request->input('code');

        if (! is_string($code) || $code === '') {
            return redirect()->route('login')->with('error', 'ไม่ได้รับรหัสยืนยันจาก LINE');
        }

        $tokenData = $this->getAccessToken($code);

        if (! $tokenData) {
            return redirect()->route('login')->with('error', 'ไม่สามารถรับ access token จาก LINE ได้');
        }

        $profile = $this->getProfile($tokenData['access_token']);

        if (! $profile || empty($profile['userId'])) {
            return redirect()->route('login')->with('error', 'ไม่สามารถดึงข้อมูลโปรไฟล์จาก LINE ได้');
        }

        $email = $this->verifiedEmail($tokenData['id_token'] ?? null);

        $data = [
            'name' => $profile['displayName'] ?? null,
            'email' => $email,
            // LINE only returns an email at all when the user has verified it
            // with LINE, so its presence in a verified id_token is the check.
            'email_verified' => $email !== null,
            'avatar' => $profile['pictureUrl'] ?? null,
        ];

        if ($linking && Auth::check()) {
            return SocialAuth::link($request->user(), 'line', (string) $profile['userId'], $data);
        }

        return SocialAuth::login('line', (string) $profile['userId'], $data);
    }

    public function unlink(Request $request)
    {
        return SocialAuth::unlink($request->user(), 'line');
    }

    /** @return array<string,mixed>|null */
    private function getAccessToken(string $code): ?array
    {
        try {
            $response = Http::asForm()->timeout(10)->post('https://api.line.me/oauth2/v2.1/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => route('line.callback'),
                'client_id' => Setting::getValue('line_login_channel_id'),
                'client_secret' => Setting::getValue('line_login_channel_secret'),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('LINE token error', ['status' => $response->status(), 'body' => $response->body()]);

            return null;
        } catch (\Throwable $e) {
            Log::error('LINE token exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /** @return array<string,mixed>|null */
    private function getProfile(string $accessToken): ?array
    {
        try {
            $response = Http::withToken($accessToken)->timeout(10)->get('https://api.line.me/v2/profile');

            return $response->successful() ? $response->json() : null;
        } catch (\Throwable $e) {
            Log::error('LINE profile exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * The email from the id_token, verified by LINE rather than by us.
     *
     * This endpoint checks the token's signature, issuer and audience — the
     * previous code base64-decoded the middle segment and believed whatever it
     * found, which would have accepted a token minted by anyone.
     */
    private function verifiedEmail(?string $idToken): ?string
    {
        if (! $idToken) {
            return null;
        }

        try {
            $response = Http::asForm()->timeout(10)->post('https://api.line.me/oauth2/v2.1/verify', [
                'id_token' => $idToken,
                'client_id' => Setting::getValue('line_login_channel_id'),
            ]);

            if (! $response->successful()) {
                Log::warning('LINE id_token verify failed', ['status' => $response->status()]);

                return null;
            }

            $email = $response->json('email');

            return is_string($email) && $email !== '' ? $email : null;
        } catch (\Throwable $e) {
            Log::warning('LINE id_token verify exception', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
