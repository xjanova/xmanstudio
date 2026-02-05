<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     * Check if the authenticated user has the required permission.
     *
     * @param  string  $permission  The permission to check (e.g., 'users.view', 'roles.edit')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Super admin bypasses all permission checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has the required permission
        if (! $user->hasPermission($permission)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'คุณไม่มีสิทธิ์ดำเนินการนี้',
                    'required_permission' => $permission,
                ], 403);
            }

            abort(403, 'คุณไม่มีสิทธิ์ดำเนินการนี้');
        }

        return $next($request);
    }
}
