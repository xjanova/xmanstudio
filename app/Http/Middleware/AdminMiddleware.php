<?php

namespace App\Http\Middleware;

use App\Support\Alerts\SecurityAlerts;
use App\Support\Auth\TwoFactor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * Check if the authenticated user has admin role.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        if (! auth()->user()->isAdmin()) {
            SecurityAlerts::forbidden(auth()->user(), $request->path(), $request->ip());
            abort(403, 'ไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        // The second step: a code from the admin's authenticator app, once per
        // session, whichever way the session was signed in.
        if ($refusal = TwoFactor::gate($request, auth()->user())) {
            return $refusal;
        }

        $response = $next($request);

        // Never framed by another origin. SameSite=Lax keeps the session cookie out
        // of a frame on a stranger's site, but not out of one on a sibling
        // *.xman4289.com site — and a click on "อนุมัติ" in a disguised frame is
        // a real approval.
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Never kept. Customer records, wallets and settings should not sit in the
        // browser's disk cache or come back with the Back button after logout.
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
