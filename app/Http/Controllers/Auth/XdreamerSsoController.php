<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Alerts\Alert;
use App\Support\Alerts\SecurityAlerts;
use App\Support\Alerts\SystemAlerts;
use App\Support\Auth\TwoFactor;
use App\Support\Auth\XmanIdClients;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * "Sign in with XMAN ID" — first for the X-DREAMER mobile app, now for every app in the
 * XmanIdClients registry (aixman, mlm.xman4289.com, …).
 *
 * A request that names no client is aixman's, exactly as before: no client_id on authorize, no
 * X-Sso-Client header on exchange. Any other app sends both, and a code only ever redeems for the
 * client it was minted for.
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
     * GET /auth/sso/authorize       (any client)
     * GET /auth/xdreamer/authorize  (the original URL — still answers, same action)
     *
     * Behind `auth`. By the time the body runs, we have a user.
     */
    public function authorize(Request $request)
    {
        // An admin identity handed to another site passes the same second step
        // as the admin panel; the challenge brings the browser back here after.
        if ($refusal = TwoFactor::gate($request, $request->user())) {
            return $refusal;
        }

        $validated = $request->validate([
            // Absent = aixman, which never sent one.
            'client_id' => ['nullable', 'string', 'max:32'],
            'redirect_uri' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            // PKCE S256: base64url(sha256(verifier)), so 43 chars unpadded.
            'code_challenge' => ['required', 'string', 'min:43', 'max:128'],
        ]);

        $client = XmanIdClients::find($validated['client_id'] ?? null);
        if ($client === null) {
            abort(400, 'client_id is not registered');
        }

        $redirectUri = $validated['redirect_uri'];

        // An open redirector here would let anyone turn an XMAN session into a
        // code delivered to a URL of their choosing. Each client has its own list:
        // aixman's callback is not a valid destination for an mlm code.
        if (! in_array($redirectUri, $client['redirect_uris'], true)) {
            abort(400, 'redirect_uri is not registered for this client');
        }

        $code = bin2hex(random_bytes(32));

        Cache::put(
            self::CACHE_PREFIX . $code,
            [
                'user_id' => $request->user()->id,
                'client_id' => $client['id'],
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
     * Server to server. The app never calls this; the client's own server does,
     * holding its shared secret, and then issues its own tokens for the user we
     * name here. Headers: X-Sso-Client (absent = aixman) and X-Sso-Secret.
     */
    public function exchange(Request $request): JsonResponse
    {
        $client = XmanIdClients::find((string) $request->header('X-Sso-Client', ''));

        if ($client === null) {
            SecurityAlerts::forged('XMAN ID SSO', 'ขอแลกโค้ดในนามแอปที่ไม่ได้ลงทะเบียน', $request->ip(), [], Alert::WARNING);

            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $secret = $client['secret'];

        if ($secret === '') {
            // Fails closed — and, until this alert, silently: every SSO login of this client dies right here.
            SystemAlerts::ssoNotConfigured($client['id']);

            return response()->json(['success' => false, 'message' => 'SSO is not configured'], 503);
        }

        // hash_equals, not ===, so a wrong secret does not leak its length or
        // its matching prefix through response timing.
        if (! hash_equals($secret, (string) $request->header('X-Sso-Secret'))) {
            // Someone guessing, or the client's copy of the secret drifted from ours: logins fail either way.
            SecurityAlerts::forged('XMAN ID SSO', 'ขอแลกโค้ดด้วย secret ที่ไม่ถูกต้อง (แอป ' . $client['id'] . ')', $request->ip(), [], Alert::WARNING);

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

        // A code minted before clients existed carries no client_id; those were all aixman's.
        // The code is already spent above, so a client fishing with another's code burns it.
        if (($entry['client_id'] ?? XmanIdClients::DEFAULT) !== $client['id']) {
            return response()->json(['success' => false, 'message' => 'Code was not issued to this client'], 400);
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
}
