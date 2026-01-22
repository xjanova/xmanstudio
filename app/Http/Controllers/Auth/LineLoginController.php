<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LineLoginController extends Controller
{
    /**
     * Redirect to LINE authorization.
     */
    public function redirect(Request $request)
    {
        if (! Setting::getValue('line_login_enabled', false)) {
            return redirect()->route('login')
                ->with('error', 'LINE Login ยังไม่ได้เปิดใช้งาน');
        }

        $channelId = Setting::getValue('line_login_channel_id');

        if (empty($channelId)) {
            return redirect()->route('login')
                ->with('error', 'ยังไม่ได้ตั้งค่า LINE Login Channel');
        }

        // Generate state for CSRF protection
        $state = Str::random(40);
        $request->session()->put('line_login_state', $state);

        // Store redirect URL if linking account
        if ($request->has('link')) {
            $request->session()->put('line_link_account', true);
        }

        $redirectUri = route('line.callback');
        $scope = 'profile openid email';

        $authUrl = 'https://access.line.me/oauth2/v2.1/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $channelId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => $scope,
        ]);

        return redirect($authUrl);
    }

    /**
     * Handle LINE callback.
     */
    public function callback(Request $request)
    {
        // Verify state
        $state = $request->session()->pull('line_login_state');
        if (empty($state) || $state !== $request->input('state')) {
            return redirect()->route('login')
                ->with('error', 'Invalid state parameter');
        }

        // Check for errors
        if ($request->has('error')) {
            Log::error('LINE Login error', [
                'error' => $request->input('error'),
                'description' => $request->input('error_description'),
            ]);

            return redirect()->route('login')
                ->with('error', 'LINE Login ล้มเหลว: '.$request->input('error_description'));
        }

        // Get access token
        $code = $request->input('code');
        $tokenData = $this->getAccessToken($code);

        if (! $tokenData) {
            return redirect()->route('login')
                ->with('error', 'ไม่สามารถรับ access token จาก LINE ได้');
        }

        // Get user profile
        $profile = $this->getProfile($tokenData['access_token']);

        if (! $profile) {
            return redirect()->route('login')
                ->with('error', 'ไม่สามารถดึงข้อมูลโปรไฟล์จาก LINE ได้');
        }

        // Check if linking existing account
        $linkAccount = $request->session()->pull('line_link_account', false);

        if ($linkAccount && Auth::check()) {
            return $this->linkAccount($tokenData, $profile);
        }

        // Find or create user
        return $this->findOrCreateUser($tokenData, $profile);
    }

    /**
     * Get access token from LINE.
     */
    private function getAccessToken(string $code): ?array
    {
        try {
            $response = Http::asForm()->post('https://api.line.me/oauth2/v2.1/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => route('line.callback'),
                'client_id' => Setting::getValue('line_login_channel_id'),
                'client_secret' => Setting::getValue('line_login_channel_secret'),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('LINE token error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('LINE token exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Get user profile from LINE.
     */
    private function getProfile(string $accessToken): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
            ])->get('https://api.line.me/v2/profile');

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('LINE profile exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Link LINE account to existing user.
     */
    private function linkAccount(array $tokenData, array $profile)
    {
        $user = Auth::user();

        // Check if LINE account already linked to another user
        $existingUser = User::where('line_uid', $profile['userId'])->first();
        if ($existingUser && $existingUser->id !== $user->id) {
            return redirect()->route('profile.edit')
                ->with('error', 'บัญชี LINE นี้เชื่อมต่อกับบัญชีอื่นแล้ว');
        }

        $user->update([
            'line_uid' => $profile['userId'],
            'line_display_name' => $profile['displayName'] ?? null,
            'line_picture_url' => $profile['pictureUrl'] ?? null,
            'line_access_token' => $tokenData['access_token'],
            'line_refresh_token' => $tokenData['refresh_token'] ?? null,
        ]);

        return redirect()->route('profile.edit')
            ->with('success', 'เชื่อมต่อบัญชี LINE สำเร็จ');
    }

    /**
     * Find existing user or create new one.
     */
    private function findOrCreateUser(array $tokenData, array $profile)
    {
        // First try to find by LINE UID
        $user = User::where('line_uid', $profile['userId'])->first();

        if ($user) {
            // Update LINE tokens
            $user->update([
                'line_display_name' => $profile['displayName'] ?? $user->line_display_name,
                'line_picture_url' => $profile['pictureUrl'] ?? $user->line_picture_url,
                'line_access_token' => $tokenData['access_token'],
                'line_refresh_token' => $tokenData['refresh_token'] ?? null,
                'last_login_at' => now(),
            ]);

            Auth::login($user, true);

            return redirect()->intended(route('customer.dashboard'))
                ->with('success', 'เข้าสู่ระบบด้วย LINE สำเร็จ');
        }

        // Get email from ID token if available
        $email = null;
        if (isset($tokenData['id_token'])) {
            $email = $this->getEmailFromIdToken($tokenData['id_token']);
        }

        // Check if user exists with same email
        if ($email) {
            $existingUser = User::where('email', $email)->first();
            if ($existingUser) {
                // Link LINE to existing account
                $existingUser->update([
                    'line_uid' => $profile['userId'],
                    'line_display_name' => $profile['displayName'] ?? null,
                    'line_picture_url' => $profile['pictureUrl'] ?? null,
                    'line_access_token' => $tokenData['access_token'],
                    'line_refresh_token' => $tokenData['refresh_token'] ?? null,
                    'last_login_at' => now(),
                ]);

                Auth::login($existingUser, true);

                return redirect()->intended(route('customer.dashboard'))
                    ->with('success', 'เชื่อมต่อบัญชี LINE และเข้าสู่ระบบสำเร็จ');
            }
        }

        // Create new user
        $user = User::create([
            'name' => $profile['displayName'] ?? 'LINE User',
            'email' => $email ?? 'line_'.$profile['userId'].'@line.local',
            'password' => bcrypt(Str::random(32)),
            'line_uid' => $profile['userId'],
            'line_display_name' => $profile['displayName'] ?? null,
            'line_picture_url' => $profile['pictureUrl'] ?? null,
            'line_access_token' => $tokenData['access_token'],
            'line_refresh_token' => $tokenData['refresh_token'] ?? null,
            'marketing_line_enabled' => true,
            'marketing_consent_at' => now(),
            'is_active' => true,
        ]);

        Auth::login($user, true);

        return redirect()->intended(route('customer.dashboard'))
            ->with('success', 'สร้างบัญชีและเข้าสู่ระบบด้วย LINE สำเร็จ');
    }

    /**
     * Extract email from LINE ID token.
     */
    private function getEmailFromIdToken(string $idToken): ?string
    {
        try {
            $parts = explode('.', $idToken);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);

            return $payload['email'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Unlink LINE account from user.
     */
    public function unlink(Request $request)
    {
        $user = Auth::user();

        // Check if user has password set (can login without LINE)
        if (Str::startsWith($user->email, 'line_') && empty($user->password_set_at)) {
            return redirect()->route('profile.edit')
                ->with('error', 'กรุณาตั้งรหัสผ่านและอีเมลก่อนยกเลิกการเชื่อมต่อ LINE');
        }

        $user->update([
            'line_uid' => null,
            'line_display_name' => null,
            'line_picture_url' => null,
            'line_access_token' => null,
            'line_refresh_token' => null,
        ]);

        return redirect()->route('profile.edit')
            ->with('success', 'ยกเลิกการเชื่อมต่อบัญชี LINE สำเร็จ');
    }
}
