<?php

namespace App\Events;

use App\Models\Order;
use App\Models\SmsPaymentNotification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentMatched implements ShouldBroadcast
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

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            // Broadcast to general SMS Checker channel
            new Channel('sms-checker.broadcast'),
        ];

        // If device is known, also send to device-specific channel
        if ($this->notification->device_id) {
            $channels[] = new PrivateChannel('sms-checker.device.' . $this->notification->device_id);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'payment.matched';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $banks = config('smschecker.banks', []);

        return [
            'type' => 'payment_matched',
            'order' => [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'customer_name' => $this->order->customer_name,
                'total' => (float) $this->order->total,
                'unique_amount' => $this->order->uniquePaymentAmount
                    ? (float) $this->order->uniquePaymentAmount->unique_amount
                    : null,
                'status' => $this->order->sms_verification_status ?? 'matched',
            ],
            'notification' => [
                'id' => $this->notification->id,
                'bank' => $this->notification->bank,
                'bank_name' => $banks[$this->notification->bank] ?? $this->notification->bank,
                'amount' => (float) $this->notification->amount,
                'status' => $this->notification->status,
                'sms_timestamp' => $this->notification->sms_timestamp,
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
