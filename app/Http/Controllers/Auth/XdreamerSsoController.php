<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * "Sign in with XMAN ID" for the X-DREAMER mobile app.
 *
 * The mirror image of the existing device-login bridge. That one turns an API
 * token into a web session; this one turns a web session into a one-time code
 * the app can trade for its own tokens. Same Cache-backed, five-minute,
 * single-use shape.
 *
 * The whole point is what the `auth` middleware does for free on [authorize]:
 *
 *   - already signed in here  → straight back to the app, no prompt
 *   - not signed in           → Laravel redirects to /login, then returns here
 *   - no account              → the login page's own register link
 *
 * Three behaviours, no new screens.
 *
 * The code is bound to a PKCE challenge because it comes back over a custom URL
 * scheme, which any installed app can claim. Intercepting the code buys nothing
 * without the verifier, which never leaves the device that started the flow.
 */
class XdreamerSsoController extends Controller
{
    /** Long enough that guessing is hopeless, short enough to be a URL. */
    private const CODE_TTL_SECONDS = 300;

    private const CACHE_PREFIX = 'xdreamer_sso:';

    /**
     * GET /auth/xdreamer/authorize
     *
     * Behind `auth`. By the time the body runs, we have a user.
     */
    public function authorize(Request $request)
    {
        $validated = $request->validate([
            'redirect_uri' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            // PKCE S256: base64url(sha256(verifier)), so 43 chars unpadded.
            'code_challenge' => ['required', 'string', 'min:43', 'max:128'],
        ]);

        $redirectUri = $validated['redirect_uri'];

        // An open redirector here would let anyone turn an XMAN session into a
        // code delivered to a URL of their choosing.
        if (! in_array($redirectUri, $this->allowedRedirectUris(), true)) {
            abort(400, 'redirect_uri is not registered for this client');
        }

        $code = bin2hex(random_bytes(32));

        Cache::put(
            self::CACHE_PREFIX . $code,
            [
                'user_id' => $request->user()->id,
                'code_challenge' => $validated['code_challenge'],
                'redirect_uri' => $redirectUri,
            ],
            now()->addSeconds(self::CODE_TTL_SECONDS)
        );

        $separator = str_contains($redirectUri, '?') ? '&' : '?';
        $target = $redirectUri . $separator . http_build_query([
            'code' => $code,
            'state' => $validated['state'],
        ]);

        // `away`, not `to` — the target is a custom scheme, not a route.
        return redirect()->away($target);
    }

    /**
     * POST /api/v1/auth/sso/exchange
     *
     * Server to server. The app never calls this; aixman does, holding the
     * shared secret, and then issues its own tokens for the user we name here.
     */
    public function exchange(Request $request): JsonResponse
    {
        $secret = (string) config('services.aixman.sso_secret');

        if ($secret === '') {
            return response()->json(['success' => false, 'message' => 'SSO is not configured'], 503);
        }

        // hash_equals, not ===, so a wrong secret does not leak its length or
        // its matching prefix through response timing.
        if (! hash_equals($secret, (string) $request->header('X-Sso-Secret'))) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:128'],
            'code_verifier' => ['required', 'string', 'min:43', 'max:128'],
        ]);

        // Pull, not get: a code is spent the moment it is looked at, whether or
        // not the verifier turns out to match. A wrong verifier does not get a
        // second attempt at the same code.
        $entry = Cache::pull(self::CACHE_PREFIX . $validated['code']);

        if (! is_array($entry)) {
            return response()->json(['success' => false, 'message' => 'Code is invalid or expired'], 400);
        }

        $expected = rtrim(strtr(base64_encode(hash('sha256', $validated['code_verifier'], true)), '+/', '-_'), '=');

        if (! hash_equals($entry['code_challenge'], $expected)) {
            return response()->json(['success' => false, 'message' => 'Code verifier does not match'], 400);
        }

        $user = User::find($entry['user_id']);

        if (! $user || ! $user->is_active) {
            return response()->json(['success' => false, 'message' => 'Account is unavailable'], 403);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'role' => $user->role,
            ],
        ]);
    }

    /** @return array<int, string> */
    private function allowedRedirectUris(): array
    {
        $configured = config('services.aixman.sso_redirect_uris');

        return is_array($configured) ? array_values(array_filter($configured)) : [];
    }
}
