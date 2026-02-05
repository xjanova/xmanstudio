<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewOrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The order that was created.
     */
    public Order $order;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('sms-checker.broadcast'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.created';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'new_order',
            'order' => [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'customer_name' => $this->order->customer_name,
                'customer_email' => $this->order->customer_email,
                'total' => (float) $this->order->total,
                'unique_amount' => $this->order->uniquePaymentAmount
                    ? (float) $this->order->uniquePaymentAmount->unique_amount
                    : null,
                'status' => $this->order->sms_verification_status ?? 'pending',
                'expires_at' => $this->order->uniquePaymentAmount?->expires_at?->toIso8601String(),
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
