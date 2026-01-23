<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class YouTubeOAuthController extends Controller
{
    /**
     * Redirect to YouTube authorization.
     */
    public function redirect(Request $request)
    {
        $clientId = config('services.youtube.client_id');

        if (empty($clientId)) {
            return redirect()->route('admin.settings.integrations')
                ->with('error', 'ยังไม่ได้ตั้งค่า YouTube OAuth Client ID');
        }

        // Generate state for CSRF protection
        $state = Str::random(40);
        $request->session()->put('youtube_oauth_state', $state);

        $redirectUri = route('youtube.callback');

        // YouTube OAuth scopes
        // https://www.googleapis.com/auth/youtube - Full YouTube account access
        // https://www.googleapis.com/auth/youtube.force-ssl - Manage YouTube account (SSL)
        $scope = 'https://www.googleapis.com/auth/youtube.force-ssl';

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scope,
            'state' => $state,
            'access_type' => 'offline', // Request refresh token
            'prompt' => 'consent', // Force consent to get refresh token every time
        ]);

        return redirect($authUrl);
    }

    /**
     * Handle YouTube OAuth callback.
     */
    public function callback(Request $request)
    {
        // Verify state for CSRF protection
        $state = $request->session()->pull('youtube_oauth_state');
        if (empty($state) || $state !== $request->input('state')) {
            return redirect()->route('admin.settings.integrations')
                ->with('error', 'Invalid state parameter - CSRF validation failed');
        }

        // Check for errors from Google
        if ($request->has('error')) {
            Log::error('YouTube OAuth error', [
                'error' => $request->input('error'),
                'description' => $request->input('error_description'),
            ]);

            return redirect()->route('admin.settings.integrations')
                ->with('error', 'YouTube OAuth ล้มเหลว: '.$request->input('error_description'));
        }

        // Exchange authorization code for access token
        $code = $request->input('code');
        $tokenData = $this->getAccessToken($code);

        if (! $tokenData) {
            return redirect()->route('admin.settings.integrations')
                ->with('error', 'ไม่สามารถรับ access token จาก YouTube ได้');
        }

        // Get channel information
        $channelInfo = $this->getChannelInfo($tokenData['access_token']);

        // Store tokens in settings
        try {
            Setting::set('metalx_youtube_access_token', $tokenData['access_token']);

            if (isset($tokenData['refresh_token'])) {
                Setting::set('youtube_refresh_token', $tokenData['refresh_token']);
            }

            // Store token expiration time
            if (isset($tokenData['expires_in'])) {
                $expiresAt = now()->addSeconds($tokenData['expires_in']);
                Setting::set('youtube_token_expires_at', $expiresAt->toDateTimeString());
            }

            // Store channel info if available
            if ($channelInfo) {
                Setting::set('metalx_youtube_channel_id', $channelInfo['id']);
                Setting::set('metalx_channel_name', $channelInfo['snippet']['title'] ?? 'Unknown Channel');

                Log::info('YouTube OAuth successful', [
                    'channel_id' => $channelInfo['id'],
                    'channel_name' => $channelInfo['snippet']['title'] ?? 'Unknown',
                ]);
            }

            return redirect()->route('admin.settings.integrations')
                ->with('success', 'เชื่อมต่อ YouTube Channel สำเร็จ! คุณสามารถใช้งาน YouTube API ได้แล้ว');

        } catch (\Exception $e) {
            Log::error('Failed to store YouTube tokens', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('admin.settings.integrations')
                ->with('error', 'เกิดข้อผิดพลาดในการบันทึก tokens: '.$e->getMessage());
        }
    }

    /**
     * Exchange authorization code for access token.
     */
    private function getAccessToken(string $code): ?array
    {
        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => route('youtube.callback'),
                'client_id' => config('services.youtube.client_id'),
                'client_secret' => config('services.youtube.client_secret'),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('YouTube token exchange error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('YouTube token exchange exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Refresh the access token using refresh token.
     */
    public static function refreshAccessToken(): ?string
    {
        $refreshToken = Setting::get('youtube_refresh_token');

        if (empty($refreshToken)) {
            Log::warning('Cannot refresh YouTube token: No refresh token available');
            return null;
        }

        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => config('services.youtube.client_id'),
                'client_secret' => config('services.youtube.client_secret'),
            ]);

            if ($response->successful()) {
                $tokenData = $response->json();

                // Update access token
                Setting::set('metalx_youtube_access_token', $tokenData['access_token']);

                // Update expiration time
                if (isset($tokenData['expires_in'])) {
                    $expiresAt = now()->addSeconds($tokenData['expires_in']);
                    Setting::set('youtube_token_expires_at', $expiresAt->toDateTimeString());
                }

                Log::info('YouTube access token refreshed successfully');

                return $tokenData['access_token'];
            }

            Log::error('YouTube token refresh error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('YouTube token refresh exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get authenticated channel information.
     */
    private function getChannelInfo(string $accessToken): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
            ])->get('https://www.googleapis.com/youtube/v3/channels', [
                'part' => 'snippet,contentDetails,statistics',
                'mine' => 'true',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['items'][0])) {
                    return $data['items'][0];
                }
            }

            Log::warning('Could not fetch YouTube channel info', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('YouTube channel info exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Disconnect YouTube OAuth.
     */
    public function disconnect(Request $request)
    {
        try {
            // Revoke the token with Google
            $accessToken = Setting::get('metalx_youtube_access_token');

            if ($accessToken) {
                Http::post('https://oauth2.googleapis.com/revoke', [
                    'token' => $accessToken,
                ]);
            }

            // Clear all YouTube OAuth related settings
            Setting::set('metalx_youtube_access_token', null);
            Setting::set('youtube_refresh_token', null);
            Setting::set('youtube_token_expires_at', null);

            return redirect()->route('admin.settings.integrations')
                ->with('success', 'ยกเลิกการเชื่อมต่อ YouTube สำเร็จ');

        } catch (\Exception $e) {
            Log::error('YouTube disconnect error', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('admin.settings.integrations')
                ->with('error', 'เกิดข้อผิดพลาดในการยกเลิกการเชื่อมต่อ: '.$e->getMessage());
        }
    }

    /**
     * Check if access token is expired or about to expire.
     */
    public static function isTokenExpired(): bool
    {
        $expiresAt = Setting::get('youtube_token_expires_at');

        if (empty($expiresAt)) {
            return true;
        }

        // Consider token expired if it expires in less than 5 minutes
        return now()->addMinutes(5)->isAfter($expiresAt);
    }

    /**
     * Get valid access token (refresh if needed).
     */
    public static function getValidAccessToken(): ?string
    {
        $accessToken = Setting::get('metalx_youtube_access_token');

        if (empty($accessToken)) {
            return null;
        }

        // Check if token is expired
        if (self::isTokenExpired()) {
            Log::info('YouTube access token expired, refreshing...');
            return self::refreshAccessToken();
        }

        return $accessToken;
    }
}
