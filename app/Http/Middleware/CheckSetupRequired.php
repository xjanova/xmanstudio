<?php

namespace App\Http\Middleware;

use App\Http\Controllers\SetupController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSetupRequired
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if already on setup page
        if ($request->routeIs('setup.*')) {
            return $next($request);
        }

        // Redirect to setup if no admin exists
        if (SetupController::isSetupRequired()) {
            return redirect()->route('setup.index');
        }

        return $next($request);
    }
}
