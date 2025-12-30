<?php

namespace App\Http\Middleware;

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
            abort(403, 'ไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        return $next($request);
    }
}
