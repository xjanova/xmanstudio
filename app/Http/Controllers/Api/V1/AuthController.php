<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LicenseKey;
use App\Models\User;
use App\Support\Alerts\SecurityAlerts;
use App\Support\Auth\LoginLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login and return API token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'required|string|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            // The same books as the sign-in form. Without this the API was a quiet
            // second door: no Turnstile, no failure counter, no IP block and no
            // card in the admin chat — for a password that works on the website too.
            SecurityAlerts::failedLogin($request->email, $request->ip(), $user);
            LoginLog::failed($request->email, $user, 'api');

            throw ValidationException::withMessages([
                'email' => ['อีเมลหรือรหัสผ่านไม่ถูกต้อง'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'บัญชีถูกระงับ',
            ], 403);
        }

        LoginLog::success($user, 'api');
        SecurityAlerts::adminLogin($user, $request->ip(), $request->userAgent());

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Register a new user and return API token.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'device_name' => 'required|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    /**
     * Device auth — auto-authenticate using license key + machine ID.
     * Uses the license holder's real account (from order) for cloud sync.
     * Falls back to creating a device-linked user if no real user is found.
     * Used for automatic cloud sync when license is active.
     */
    public function deviceAuth(Request $request): JsonResponse
    {
        $request->validate([
            'license_key' => 'required|string',
            'machine_id' => 'required|string|min:32|max:128',
        ]);

        // Verify license exists and is active
        $license = LicenseKey::where('license_key', $request->license_key)
            ->whereNotNull('activated_at')
            ->with('order')
            ->first();

        if (! $license) {
            return response()->json([
                'success' => false,
                'message' => 'ไลเซนส์ไม่ถูกต้องหรือยังไม่ได้เปิดใช้งาน',
            ], 401);
        }

        // Strict machine_id check — re-bind must go through /activate endpoint
        // which has proper max_activations control (prevents license sharing abuse)
        if ($license->machine_id && $license->machine_id !== $request->machine_id) {
            return response()->json([
                'success' => false,
                'message' => 'เครื่องนี้ไม่ตรงกับไลเซนส์ กรุณาเปิดแอปใหม่เพื่อ re-activate',
            ], 403);
        }

        // Check if license is expired
        if ($license->expires_at && $license->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'ไลเซนส์หมดอายุแล้ว',
            ], 403);
        }

        // Use real license holder's account (from order) for cloud data ownership
        $user = null;

        // 1. Check if license has an order with a real user
        if ($license->order && $license->order->user_id) {
            $user = User::find($license->order->user_id);
        }

        // 2. Check if license has a direct user_id that's a real user (not a device user)
        if (! $user && $license->user_id) {
            $existingUser = User::find($license->user_id);
            if ($existingUser && ! str_ends_with($existingUser->email, '@tping.device')) {
                $user = $existingUser;
            }
        }

        // 3. Fallback: create device user only if no real user found
        if (! $user) {
            $deviceEmail = 'device-' . substr(hash('sha256', $request->machine_id), 0, 16) . '@tping.device';
            $deviceName = 'Device ' . substr($request->machine_id, 0, 8) . '...';

            $user = User::firstOrCreate(
                ['email' => $deviceEmail],
                [
                    'name' => $deviceName,
                    'password' => Hash::make(Str::random(32)),
                    'is_active' => true,
                ]
            );
        }

        // Link user to license if not already linked
        if (! $license->user_id || $license->user_id !== $user->id) {
            $license->update(['user_id' => $user->id]);
        }

        // Revoke old tokens for this device and create new one
        $user->tokens()->where('name', 'device-sync')->delete();
        $token = $user->createToken('device-sync')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Generate a one-time web login token for device-auth users.
     * The app calls this, then opens the returned URL in the browser.
     * Token expires in 5 minutes and can only be used once.
     */
    public function webLoginToken(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'ยังไม่ได้เข้าสู่ระบบ'], 401);
        }

        // This link signs a browser in with no password and no Turnstile. Fine for a
        // customer opening their dashboard from the app; never how an admin session
        // may start. A device token is minted from a licence key plus a machine id
        // (deviceAuth above), so an admin who owns a licence would otherwise be one
        // leaked key away from handing out the admin panel.
        if ($user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'บัญชีผู้ดูแลระบบต้องเข้าสู่ระบบผ่านหน้าเว็บด้วยรหัสผ่านเท่านั้น',
            ], 403);
        }

        if (! $user->is_active) {
            return response()->json(['success' => false, 'message' => 'บัญชีถูกระงับ'], 403);
        }

        // Generate a one-time token (stored in cache for 5 minutes)
        $token = bin2hex(random_bytes(32));
        Cache::put(
            "web_login_token:{$token}",
            $user->id,
            now()->addMinutes(5)
        );

        $url = url("/auth/device-login/{$token}");

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $url,
                'expires_in' => 300,
            ],
        ]);
    }

    /**
     * Logout (revoke current token).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'ออกจากระบบแล้ว',
        ]);
    }

    /**
     * Get current authenticated user.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
