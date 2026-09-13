<?php

namespace App\Observers;

use App\Jobs\DeliverAiCreditsJob;
use App\Models\Order;
use App\Services\AiCreditDelivery;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * When an AI-credit order is paid by any route, queue the delivery of its credits to AIXMAN.
 *
 * Judged on the order's current state, not on which attributes the last save changed: after a
 * commit, every queued callback sees only the LAST save's changes, and a payment approved in a
 * transaction that then saves the order again would otherwise be missed. Queuing more than once
 * is harmless — AiCreditDelivery delivers at most once.
 */
class AiCreditOrderObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Order $order): void
    {
        $this->queueIfOwed($order);
    }

    public function updated(Order $order): void
    {
        $this->queueIfOwed($order);
    }

    private function queueIfOwed(Order $order): void
    {
        try {
            if (AiCreditDelivery::isOwed($order)) {
                DeliverAiCreditsJob::dispatch((int) $order->id);
            }
        } catch (Throwable $e) {
            // A queue hiccup must not turn an approved payment into an error page; the success
            // page and the Stripe webhook still deliver, and the order shows as undelivered.
            Log::error('AI credit delivery could not be queued', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }
}
