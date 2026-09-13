<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\AiCreditDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

/**
 * Deliver a paid AI-credit order's credits to AIXMAN, off the request that approved it — the admin
 * pressing "approve", the SMS phone app, the Telegram bot — none of which should wait on
 * ai.xman4289.com, or lose the credits if it is briefly down.
 *
 * Retries with backoff while AIXMAN refuses; each failure already alerts the admin on Telegram
 * (AixmanService), and a job that gives up lands in failed_jobs, which the watchdog reports.
 * Safe to run any number of times: AiCreditDelivery sends at most once per order.
 */
class DeliverAiCreditsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;

    /** @var array<int,int> seconds between attempts: 1 min, 5 min, 15 min, 1 h, 3 h */
    public array $backoff = [60, 300, 900, 3600, 10800];

    public function __construct(public int $orderId) {}

    public function handle(AiCreditDelivery $delivery): void
    {
        $order = Order::find($this->orderId);
        if (! $order || ! AiCreditDelivery::isOwed($order)) {
            return;     // gone, not an AI-credit order, not paid, or already delivered
        }

        if (! $delivery->deliver($order)) {
            // Refused, unreachable, or another caller holds the lock right now: try again later.
            throw new RuntimeException("AI credits for order #{$this->orderId} not delivered yet");
        }
    }
}
