<?php

namespace App\Providers;

use App\Models\AutoTradeXDevice;
use App\Models\Order;
use App\Models\ProductDevice;
use App\Models\Quotation;
use App\Models\RentalPayment;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\User;
use App\Models\WalletTopup;
use App\Observers\AdminAlertObserver;
use App\Support\Alerts\SecurityAlerts;
use App\Support\Alerts\SystemAlerts;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Console\Events\ScheduledBackgroundTaskFinished;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the admin Telegram alerts into the app: the model observer for business events, the auth
 * events for break-in signals, and the scheduler events for tasks that stopped working.
 *
 * Everything registered here is inert until a bot token and chat are saved on /admin/alerts, and
 * every handler swallows its own failures.
 */
class AdminAlertServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach ([
            Order::class, WalletTopup::class, RentalPayment::class, Quotation::class,
            SupportTicket::class, TicketReply::class, User::class,
            ProductDevice::class, AutoTradeXDevice::class,
        ] as $model) {
            $model::observe(AdminAlertObserver::class);
        }

        Event::listen(Failed::class, function (Failed $event) {
            SecurityAlerts::failedLogin(
                is_string($event->credentials['email'] ?? null) ? $event->credentials['email'] : null,
                request()->ip(),
                $event->user instanceof User ? $event->user : null,
            );
        });
        Event::listen(Lockout::class, function (Lockout $event) {
            SecurityAlerts::lockout((string) $event->request->input('email'), $event->request->ip());
        });
        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User) {
                SecurityAlerts::adminLogin($event->user, request()->ip(), request()->userAgent());
            }
        });

        Event::listen(ScheduledTaskFailed::class, function (ScheduledTaskFailed $event) {
            SystemAlerts::scheduledTaskFailed((string) ($event->task->description ?: $event->task->command), null, $event->exception);
        });
        // A command task that exits non-zero throws nothing in the scheduler; its exit code is the
        // only trace. Foreground tasks report it here, runInBackground ones via schedule:finish.
        Event::listen([ScheduledTaskFinished::class, ScheduledBackgroundTaskFinished::class], function ($event) {
            $code = $event->task->exitCode;
            if ($code !== null && $code !== 0) {
                SystemAlerts::scheduledTaskFailed((string) ($event->task->description ?: $event->task->command), (int) $code);
            }
        });
    }
}
