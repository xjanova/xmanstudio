<?php

namespace App\Listeners;

use App\Events\NewOrderCreated;
use App\Services\FcmNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * ส่ง FCM push notification ไปยัง SmsChecker Android app เมื่อมีบิลใหม่
 *
 * เพื่อให้แอพโหลดบิลทันทีโดยไม่ต้องรอ periodic sync
 * ลดการใช้เน็ตเพราะแอพไม่ต้อง poll ทุก 5 วินาที
 */
class SendNewOrderFcmNotification implements ShouldQueue
{
    public function __construct(
        private FcmNotificationService $fcmService
    ) {}

    public function handle(NewOrderCreated $event): void
    {
        $order = $event->order;

        if (! config('smschecker.notifications.fcm_on_new_order', true)) {
            return;
        }

        try {
            $this->fcmService->notifyNewOrder($order);
            Log::info('FCM: Sent new order notification', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        } catch (\Exception $e) {
            Log::error('FCM: Failed to send new order notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(NewOrderCreated $event, \Throwable $exception): void
    {
        Log::error('SendNewOrderFcmNotification failed', [
            'order_id' => $event->order->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
