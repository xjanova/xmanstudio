<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use App\Support\Auth\LoginLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns away addresses on the block list, before anything else runs.
 *
 * Deliberately the first middleware in the stack: a blocked address should
 * cost one cache read, not a session load, a theme lookup and a route match.
 *
 * Fails open. A database or cache problem here would otherwise take the whole
 * site down for everyone — the exact opposite of what a security control is
 * for.
 */
class BlockAbusiveIps
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $block = BlockedIp::findActive($request->ip());
        } catch (\Throwable $e) {
            Log::warning('BlockAbusiveIps lookup failed', ['error' => $e->getMessage()]);

            return $next($request);
        }

        if (! $block) {
            return $next($request);
        }

        try {
            $block->recordHit();

            // One row per refused *login*, not per refused request. A blocked
            // bot retries the whole site; logging all of it would bury the
            // thing the log is for. The hit counter above covers the volume.
            if ($request->isMethod('post') && $request->is('login')) {
                LoginLog::blocked(is_string($request->input('email')) ? $request->input('email') : null);
            }
        } catch (\Throwable $e) {
            // Counting is a nicety; refusing is the job.
        }

        $retryAfter = $block->expires_at
            ? max(1, now()->diffInSeconds($block->expires_at, false))
            : null;

        $until = $block->expires_at
            ? 'ปลดบล็อกอัตโนมัติเวลา ' . $block->expires_at->timezone(config('app.timezone'))->format('H:i น. (d/m/Y)')
            : 'ถูกบล็อกถาวร';

        $message = 'ที่อยู่ IP ของคุณถูกระงับชั่วคราวเนื่องจากพยายามเข้าสู่ระบบผิดปกติ — ' . $until;

        $headers = $retryAfter ? ['Retry-After' => (int) $retryAfter] : [];

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => $message,
                'code' => 'IP_BLOCKED',
            ], 403, $headers);
        }

        return response()->view('errors.blocked', [
            'message' => $message,
            'ip' => $request->ip(),
            'until' => $block->expires_at,
        ], 403, $headers);
    }
}
