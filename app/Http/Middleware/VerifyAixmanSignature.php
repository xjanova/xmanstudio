<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-to-server calls from aixman (ai.xman4289.com) to /api/aixman/tpix/*: signed with the shared
 * webhook secret (services.aixman.webhook_secret here = XMAN_WEBHOOK_SECRET on aixman).
 *
 *   X-Aixman-Timestamp: unix seconds, at most WINDOW_SECONDS from now
 *   X-Aixman-Signature: lowercase hex HMAC_SHA256(signingKey, "{ts}\n{METHOD}\n{path}\n{sha256_hex(raw body)}")
 *   signingKey        = hex HMAC_SHA256(secret, "aixman-tpix-api-v1") — the hex string itself is the key
 *   path              = the request path without the query, e.g. /api/aixman/tpix/orders
 *
 * No secret configured → 503 (fails closed, as the SSO exchange does). Missing, malformed, stale or
 * wrong → 401; an address that keeps sending those is answered 429 for the rest of the minute
 * (REFUSED_PER_MINUTE) — the routes have no per-address throttle otherwise, since every real call
 * comes from aixman's one server. The controller reads its input from the signed raw body only,
 * never the query string, which the signature does not cover.
 */
class VerifyAixmanSignature
{
    public const WINDOW_SECONDS = 300;

    public const KEY_CONTEXT = 'aixman-tpix-api-v1';

    /** Refused (unsigned, stale, wrong) calls an address may make in a minute before it is turned away unread. */
    public const REFUSED_PER_MINUTE = 30;

    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.aixman.webhook_secret');
        if ($secret === '') {
            Log::warning('aixman TPIX API called but AIXMAN_WEBHOOK_SECRET is not set — answering 503');

            return response()->json([
                'success' => false,
                'message' => 'ยังไม่ได้ตั้งค่าการเชื่อมต่อกับ ai.xman4289.com',
            ], 503);
        }

        $refusedKey = 'aixman-tpix-refused:' . $request->ip();
        if (RateLimiter::tooManyAttempts($refusedKey, self::REFUSED_PER_MINUTE)) {
            return response()->json([
                'success' => false,
                'message' => 'ลายเซ็นคำขอผิดหลายครั้งเกินไป กรุณารอสักครู่แล้วลองใหม่',
            ], 429, ['Retry-After' => (string) max(1, RateLimiter::availableIn($refusedKey))]);
        }

        $timestamp = (string) $request->header('X-Aixman-Timestamp', '');
        $signature = strtolower(trim((string) $request->header('X-Aixman-Signature', '')));

        if (! preg_match('/^\d{1,12}$/', $timestamp)
            || abs(now()->getTimestamp() - (int) $timestamp) > self::WINDOW_SECONDS
            || ! preg_match('/^[0-9a-f]{64}$/', $signature)) {
            return $this->refuse($request, 'missing or stale');
        }

        $expected = self::sign($secret, $timestamp, $request->getRealMethod(), self::pathOf($request), $request->getContent());

        if (! hash_equals($expected, $signature)) {
            return $this->refuse($request, 'bad signature');
        }

        return $next($request);
    }

    /** The key both sides sign with: the hex HMAC of the context string, used as the (string) key. */
    public static function signingKey(string $secret): string
    {
        return hash_hmac('sha256', self::KEY_CONTEXT, $secret);
    }

    public static function sign(string $secret, string|int $timestamp, string $method, string $path, string $rawBody): string
    {
        $message = $timestamp . "\n" . strtoupper($method) . "\n" . $path . "\n" . hash('sha256', $rawBody);

        return hash_hmac('sha256', $message, self::signingKey($secret));
    }

    /** The path as the client asked for it, without the query (and with the base path, if the app has one). */
    public static function pathOf(Request $request): string
    {
        return $request->getBaseUrl() . $request->getPathInfo();
    }

    private function refuse(Request $request, string $why): Response
    {
        RateLimiter::hit('aixman-tpix-refused:' . $request->ip(), 60);
        Log::notice('aixman TPIX API call refused', ['why' => $why, 'path' => $request->getPathInfo(), 'ip' => $request->ip()]);

        return response()->json([
            'success' => false,
            'message' => 'ลายเซ็นคำขอไม่ถูกต้องหรือหมดอายุ',
        ], 401);
    }
}
