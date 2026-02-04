<?php

namespace App\Events;

use App\Models\SmsPaymentNotification;
use App\Models\WalletTopup;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletTopupMatched
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The wallet topup that was matched.
     */
    public WalletTopup $topup;

    /**
     * The SMS notification that matched the topup.
     */
    public SmsPaymentNotification $notification;

    /**
     * Create a new event instance.
     */
    public function __construct(WalletTopup $topup, SmsPaymentNotification $notification)
    {
        $this->topup = $topup;
        $this->notification = $notification;
    }
}
