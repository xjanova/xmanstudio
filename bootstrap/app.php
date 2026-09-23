<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AffiliateTracking;
use App\Http\Middleware\AiCrawlDetector;
use App\Http\Middleware\BlockAbusiveIps;
use App\Http\Middleware\EnsureKycVerified;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\ThemeMiddleware;
use App\Http\Middleware\VerifySmsCheckerDevice;
use App\Http\Middleware\VerifyTurnstile;
use App\Http\Middleware\WatchScheduler;
use App\Support\Alerts\ErrorAlert;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'theme' => ThemeMiddleware::class,
            'smschecker.device' => VerifySmsCheckerDevice::class,
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'turnstile' => VerifyTurnstile::class,
            'affiliate' => AffiliateTracking::class,
            'kyc.verified' => EnsureKycVerified::class,
        ]);

        // Exclude Stripe webhook from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);

        // Apply theme + affiliate tracking + AI crawl detection middleware to web routes
        $middleware->web(append: [
            AiCrawlDetector::class,
            ThemeMiddleware::class,
            AffiliateTracking::class,
            // Dead man's switch for the cron — checked after the response is sent.
            WatchScheduler::class,
        ]);

        // Trusted proxies are set in AppServiceProvider::boot(), NOT here.
        //
        // This closure runs while the HTTP kernel is being resolved, which is
        // before the configuration files are loaded — calling config() here
        // throws "Class 'config' does not exist" and takes every web request
        // with it. (Artisan hides the bug: the console kernel resolves later,
        // so the same line works fine from the command line.)

        // Refuse a blocked address before the router does any work. Sits ahead
        // of the throttle on purpose: the point of a block is that the request
        // stops being cheap for us and expensive for them.
        $middleware->prepend(BlockAbusiveIps::class);

        // Configure rate limiting for specific operations
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A failed validation flashes the submitted form into the session so it can be refilled —
        // including a pasted bot token, which would then sit in plaintext in the session store.
        // Same for a VPS root password: a customer who mistyped the hostname must not leave the
        // password to their server in Redis. (Laravel's own password fields merge into this list.)
        $exceptions->dontFlash([
            'telegram_bot_token',
            'hostinger_api_token',
            'root_password',
            'root_password_confirmation',
            'recovery_password',
            'recovery_password_confirmation',
            'panel_password',
            'panel_password_confirmation',
            // Meant to be a public key — but what gets pasted into that box by
            // mistake is sometimes the private one, and it must not sit in the session.
            'public_key',
        ]);

        // Tell the owner in Telegram when something throws (a 500, a dying command) — throttled
        // hard and sent after the response. Returns nothing, so normal logging still happens.
        $exceptions->report(function (Throwable $e): void {
            ErrorAlert::report($e);
        });

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

        // An API caller gets JSON for a 401 or a 422 whether or not it sent Accept —
        // not a redirect to the login page.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        // In production, don't expose internal errors
        $exceptions->render(function (Throwable $e, Request $request) {
            // These are answers, not failures: "who are you?" (401) and "these
            // fields are wrong" (422). Caught here they all left as a bare 500, so
            // an app could not tell a wrong password from a crashed server, and
            // every one was logged as an error with its trace.
            if ($e instanceof AuthenticationException || $e instanceof ValidationException) {
                return null;
            }

            if (! config('app.debug') && ($request->is('api/*') || $request->wantsJson())) {
                Log::error('API Error', [
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
