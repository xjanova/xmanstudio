<?php

namespace App\Observers;

use App\Models\AutoTradeXDevice;
use App\Models\Order;
use App\Models\ProductDevice;
use App\Models\Quotation;
use App\Models\RentalPayment;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\User;
use App\Models\WalletTopup;
use App\Support\AdminAlerts;
use App\Support\Alerts\BusinessAlerts;
use App\Support\Alerts\SecurityAlerts;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Feeds model changes to the admin alerts. Registered on each model it knows (see
 * AdminAlertServiceProvider) rather than called from the controllers, because the same event
 * happens in many places: an order is created by six different checkouts, paid by the SMS matcher,
 * Stripe, a wallet, the admin page and the Telegram bot. Watching the row catches all of them,
 * including the ones written after this.
 *
 * After-commit: a checkout that rolls back never announces an order that does not exist, and the
 * order's items (inserted after the order row, in the same transaction) exist by the time we look.
 */
class AdminAlertObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Model $model): void
    {
        $this->safely(fn () => match (true) {
            $model instanceof Order => BusinessAlerts::orderCreated($model),
            $model instanceof WalletTopup => BusinessAlerts::topupCreated($model),
            $model instanceof RentalPayment => BusinessAlerts::rentalPaymentCreated($model),
            $model instanceof Quotation => BusinessAlerts::quotationCreated($model),
            $model instanceof SupportTicket => BusinessAlerts::ticketCreated($model),
            $model instanceof TicketReply => BusinessAlerts::ticketReplied($model),
            $model instanceof User => AdminAlerts::noteSignup((int) $model->id, (string) $model->name, $model->line_uid ? 'LINE' : 'เว็บไซต์'),
            default => null,
        });
    }

    public function updated(Model $model): void
    {
        $this->safely(function () use ($model) {
            $changes = $model->getChanges();

            match (true) {
                $model instanceof Order => BusinessAlerts::orderUpdated($model, $changes),
                $model instanceof WalletTopup => BusinessAlerts::topupUpdated($model, $changes),
                $model instanceof RentalPayment => BusinessAlerts::rentalPaymentUpdated($model, $changes),
                $model instanceof ProductDevice, $model instanceof AutoTradeXDevice => $this->device($model, $changes),
                default => null,
            };
        });
    }

    /**
     * An after-commit observer that throws turns a committed checkout or signup into a 500 page —
     * the row is saved, the customer sees an error. Nothing about an alert is worth that.
     */
    private function safely(callable $fn): void
    {
        try {
            $fn();
        } catch (Throwable $e) {
            try {
                Log::warning('admin-alert: observer failed', ['error' => $e->getMessage(), 'at' => $e->getFile() . ':' . $e->getLine()]);
            } catch (Throwable) {
            }
        }
    }

    /** A licensed app's device newly flagged suspicious, or blocked. */
    private function device(Model $device, array $changes): void
    {
        $blocked = ($changes['status'] ?? null) === 'blocked';
        $flagged = array_key_exists('is_suspicious', $changes) && $device->is_suspicious;
        if (! $blocked && ! $flagged) {
            return;
        }
        $product = $device instanceof AutoTradeXDevice ? 'AutoTradeX' : ($device->product?->name ?? 'โปรแกรม');

        SecurityAlerts::licenseAbuse(
            (string) $product,
            $device->machine_id,
            (string) ($device->abuse_reason ?: 'ระบบตรวจพบพฤติกรรมผิดปกติ'),
            $device->last_ip,
            $blocked,
        );
    }
}
