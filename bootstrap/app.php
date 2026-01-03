<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);

        // Trust proxies for load balancers
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle API exceptions - return JSON responses
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Resource not found',
                    'code' => 'NOT_FOUND',
                ], 404);
            }
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage() ?: 'An error occurred',
                    'code' => 'HTTP_ERROR',
                ], $e->getStatusCode());
            }
        });

        // In production, don't expose internal errors
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! config('app.debug') && ($request->is('api/*') || $request->wantsJson())) {
                \Illuminate\Support\Facades\Log::error('API Error', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Internal server error',
                    'code' => 'INTERNAL_ERROR',
                ], 500);
            }
        });
    })->create();

// Set public path to public_html for DirectAdmin hosting compatibility
$app->usePublicPath($app->basePath('public_html'));

return $app;
