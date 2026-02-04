<?php

namespace App\Providers;

use App\Events\PaymentMatched;
use App\Listeners\SendPaymentMatchedNotification;
use Illuminate\Cache\RateLimiting\Limit;
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
