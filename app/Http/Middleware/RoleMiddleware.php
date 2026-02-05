<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Check if the authenticated user has the required role.
     *
     * @param  string  $roles  Comma-separated list of roles (e.g., 'admin,super_admin')
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Super admin bypasses all role checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Parse roles (comma-separated)
        $requiredRoles = array_map('trim', explode(',', $roles));

        // Check if user has any of the required roles
        if (! $user->hasRole($requiredRoles)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'คุณไม่มีบทบาทที่จำเป็นสำหรับการดำเนินการนี้',
                    'required_roles' => $requiredRoles,
                ], 403);
            }

            abort(403, 'คุณไม่มีบทบาทที่จำเป็นสำหรับการดำเนินการนี้');
        }

        return $next($request);
    }
}
