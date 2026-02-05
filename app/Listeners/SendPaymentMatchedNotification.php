<?php

namespace App\Listeners;

use App\Events\PaymentMatched;
use App\Services\FcmNotificationService;
use App\Services\LineNotifyService;
use App\Services\SmsPaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendPaymentMatchedNotification implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private LineNotifyService $lineNotifyService,
        private SmsPaymentService $smsPaymentService,
        private FcmNotificationService $fcmService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(PaymentMatched $event): void
    {
        $order = $event->order;
        $notification = $event->notification;

        Log::info('PaymentMatched event received', [
            'order_id' => $order->id,
            'notification_id' => $notification->id,
            'amount' => $notification->amount,
        ]);

        // Send LINE notification if enabled
        if (config('smschecker.notifications.line_on_match', true)) {
            try {
                $this->smsPaymentService->notifyPaymentMatched($order, $notification);
                Log::info('LINE notification sent for payment match', [
                    'order_id' => $order->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send LINE notification', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Send FCM push notification if enabled
        if (config('smschecker.notifications.fcm_on_match', true)) {
            try {
                $this->fcmService->notifyPaymentMatched($order, $notification);
                Log::info('FCM notification sent for payment match', [
                    'order_id' => $order->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send FCM notification', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Email notification if enabled
        if (config('smschecker.notifications.email_on_match', false)) {
            // TODO: Implement email notification
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(PaymentMatched $event, \Throwable $exception): void
    {
        Log::error('PaymentMatched listener failed', [
            'order_id' => $event->order->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
