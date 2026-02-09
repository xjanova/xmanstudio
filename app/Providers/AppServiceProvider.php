<?php

namespace App\Providers;

use App\Events\NewOrderCreated;
use App\Events\PaymentMatched;
use App\Listeners\SendNewOrderFcmNotification;
use App\Listeners\SendPaymentMatchedNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->registerSmsCheckerEvents();
        $this->registerBladeDirectives();
    }

    /**
     * Register custom Blade directives for authorization.
     */
    protected function registerBladeDirectives(): void
    {
        // @can('permission-name') ... @endcan
        // @permission('users.view') ... @endpermission
        Blade::if('permission', function (string $permission) {
            return auth()->check() && auth()->user()->hasPermission($permission);
        });

        // @role('admin') ... @endrole
        Blade::if('role', function (string $roles) {
            if (! auth()->check()) {
                return false;
            }

            $roleArray = array_map('trim', explode(',', $roles));

            return auth()->user()->hasRole($roleArray) || auth()->user()->isSuperAdmin();
        });

        // @anypermission(['users.view', 'users.edit']) ... @endanypermission
        Blade::if('anypermission', function (array $permissions) {
            return auth()->check() && auth()->user()->hasAnyPermission($permissions);
        });
    }

    /**
     * Register SMS Checker event listeners.
     */
    protected function registerSmsCheckerEvents(): void
    {
        Event::listen(
            PaymentMatched::class,
            SendPaymentMatchedNotification::class
        );

        // ส่ง FCM push ไปยัง SmsChecker app เมื่อมีบิลใหม่
        // แอพจะโหลดบิลทันทีโดยไม่ต้องรอ periodic sync
        Event::listen(
            NewOrderCreated::class,
            SendNewOrderFcmNotification::class
        );
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Default API rate limiter (required for throttleApi middleware)
        RateLimiter::for('api', function ($request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'error' => 'Too many API requests. Please wait before trying again.',
                        'code' => 'RATE_LIMIT_EXCEEDED',
                    ], 429);
                });
        });

        RateLimiter::for('ai-operations', function ($request) {
            return Limit::perMinute(10)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'error' => 'Too many AI requests. Please wait before trying again.',
                        'code' => 'RATE_LIMIT_EXCEEDED',
                    ], 429);
                });
        });

        RateLimiter::for('youtube-operations', function ($request) {
            return Limit::perMinute(20)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'error' => 'Too many YouTube API requests. Please wait before trying again.',
                        'code' => 'RATE_LIMIT_EXCEEDED',
                    ], 429);
                });
        });

        RateLimiter::for('comment-moderation', function ($request) {
            return Limit::perMinute(30)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'error' => 'Too many moderation requests. Please wait before trying again.',
                        'code' => 'RATE_LIMIT_EXCEEDED',
                    ], 429);
                });
        });
    }
}
