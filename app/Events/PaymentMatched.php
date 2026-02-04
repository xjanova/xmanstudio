<?php

namespace App\Events;

use App\Models\Order;
use App\Models\SmsPaymentNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentMatched
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The order that was matched.
     */
    public Order $order;

    /**
     * The SMS notification that matched the order.
     */
    public SmsPaymentNotification $notification;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, SmsPaymentNotification $notification)
    {
        $this->order = $order;
        $this->notification = $notification;
    }
}
