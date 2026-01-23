<?php

namespace App\Http\Middleware;

use App\Services\ThemeService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ThemeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get current theme
        $currentTheme = ThemeService::getCurrentTheme();

        // Share theme data to all views
        View::share('currentTheme', $currentTheme);
        View::share('themeService', new ThemeService());

        // Share layout paths
        View::share('adminLayout', ThemeService::getAdminLayout());
        View::share('customerLayout', ThemeService::getCustomerLayout());
        View::share('publicLayout', ThemeService::getPublicLayout());

        return $next($request);
    }
}
