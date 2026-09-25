<?php

namespace App\Providers;

use App\Events\NewOrderCreated;
use App\Events\PaymentMatched;
use App\Listeners\SendNewOrderFcmNotification;
use App\Listeners\SendPaymentMatchedNotification;
use App\Models\Order;
use App\Models\PaymentSetting;
use App\Observers\AiCreditOrderObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
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
        // Bilingual (TH + EN) global helpers: bi(), bi_th(), bi_en().
        require_once app_path('helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureTrustedProxies();
        $this->configureRateLimiting();
        $this->registerSmsCheckerEvents();
        $this->registerBladeDirectives();
        $this->configureMailFromDatabase();

        // A paid AI-credit order gets its credits on AIXMAN whichever way it was paid — admin
        // approval, the SMS matcher, the Telegram bot — not only via the success page or Stripe.
        Order::observe(AiCreditOrderObserver::class);
    }

    /**
     * Which proxies may tell us the visitor's real IP.
     *
     * Set here rather than in bootstrap/app.php because that closure runs
     * before the config files load, so config() throws there — silently only
     * on the web, since Artisan resolves its kernel late enough to work.
     *
     * The list is Cloudflare plus the local web server. It used to be '*',
     * which trusts the client's own X-Forwarded-For: with that in place, five
     * wrong passwords cost an attacker one header change to reset, and every
     * IP in the login log was whatever they felt like typing.
     */
    private function configureTrustedProxies(): void
    {
        $proxies = config('security.trusted_proxies', []);

        if (! empty($proxies)) {
            TrustProxies::at($proxies);
        }
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
    /**
     * Override mail config with database settings (Resend API key, from address).
     */
    protected function configureMailFromDatabase(): void
    {
        try {
            $apiKey = PaymentSetting::get('resend_api_key');
            if ($apiKey) {
                config(['services.resend.key' => $apiKey]);
            }

            $fromAddress = PaymentSetting::get('mail_from_address');
            if ($fromAddress) {
                config(['mail.from.address' => $fromAddress]);
            }

            $fromName = PaymentSetting::get('mail_from_name');
            if ($fromName) {
                config(['mail.from.name' => $fromName]);
            }
        } catch (\Exception $e) {
            // Database may not be available during migrations
        }
    }

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

        // /api/v1/product/gpuxmine/status — โปรแกรม GPUxMINE ถามทุกสามนาที (เร็วสุดทุกสิบห้าวินาที
        // เมื่อเจ้าของกดรีเฟรช/START/จับคู่) route นี้ไม่มีผู้ใช้ที่ล็อกอิน throttle:30,1 เดิมจึงนับ
        // ต่อ IP: ร้านหรือเจ้าของที่มีสิบเครื่องหลังเราเตอร์เดียว หรือ CGNAT ของ ISP ไทย ชน 429 พร้อมกัน
        // แล้วโปรแกรมถอยไปถึงสามสิบนาที ตอนนี้นับต่อเครื่อง (worker_id คู่กับ IP — คนอื่นที่รู้
        // worker_id จึงเผาโควตาของเครื่องจริงจากที่อื่นไม่ได้) และมีเพดานรวมต่อ IP กันการไล่ยิง
        RateLimiter::for('gpuxmine-status', function (Request $request) {
            $worker = $request->input('worker_id');
            $worker = is_string($worker) ? mb_substr($worker, 0, 64) : '';
            $tooMany = fn (Request $request, array $headers) => response()->json([
                'success' => false,
                'message' => 'ถามสถานะเครื่องถี่เกินไป — รอสักครู่แล้วลองใหม่',
                'code' => 'RATE_LIMIT_EXCEEDED',
            ], 429, $headers);

            return [
                Limit::perMinute(10)->by('gxm-status:' . $worker . '|' . $request->ip())->response($tooMany),
                Limit::perMinute(600)->by('gxm-status-ip:' . $request->ip())->response($tooMany),
            ];
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
