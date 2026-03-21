<?php

namespace App\Http\Controllers;

use App\Mail\PaymentConfirmedMail;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\RentalPayment;
use App\Models\WalletTopup;
use App\Services\LicenseService;
use App\Services\LineNotifyService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function __construct(protected StripeService $stripeService) {}

    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = $this->stripeService->constructWebhookEvent($payload, $sigHeader);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response('Invalid signature', 400);
        } catch (\Exception $e) {
            Log::error('Stripe webhook error', ['error' => $e->getMessage()]);

            return response('Webhook error', 400);
        }

        Log::info('Stripe webhook received', [
            'type' => $event->type,
            'id' => $event->id,
        ]);

        match ($event->type) {
            'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($event->data->object),
            default => Log::info("Unhandled Stripe event: {$event->type}"),
        };

        return response('OK', 200);
    }

    protected function handlePaymentIntentSucceeded($paymentIntent): void
    {
        $metadata = $paymentIntent->metadata;
        $type = $metadata->type ?? null;

        match ($type) {
            'order' => $this->confirmOrderPayment($paymentIntent),
            'wallet_topup' => $this->confirmTopupPayment($paymentIntent),
            'rental' => $this->confirmRentalPayment($paymentIntent),
            default => Log::warning('Unknown Stripe payment type', [
                'payment_intent' => $paymentIntent->id,
                'metadata' => (array) $metadata,
            ]),
        };
    }

    protected function confirmOrderPayment($paymentIntent): void
    {
        $order = Order::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (! $order) {
            Log::error('Order not found for Stripe PI', ['pi' => $paymentIntent->id]);

            return;
        }

        if ($order->payment_status === 'paid') {
            return; // Idempotent
        }

        $order->update([
            'payment_status' => 'paid',
            'status' => 'processing',
            'paid_at' => now(),
            'stripe_payment_method_id' => $paymentIntent->payment_method,
            'stripe_metadata' => [
                'amount_received' => $paymentIntent->amount_received,
                'payment_method_types' => $paymentIntent->payment_method_types,
                'latest_charge' => $paymentIntent->latest_charge,
            ],
        ]);

        // Generate licenses
        $this->generateLicensesForOrder($order);

        // Notify admin via LINE
        try {
            $lineNotify = new LineNotifyService;
            $message = "💳 Stripe ชำระเงินสำเร็จ!\n"
                . "━━━━━━━━━━━━━━━\n"
                . "🔢 เลขที่: {$order->order_number}\n"
                . "👤 ลูกค้า: {$order->customer_name}\n"
                . '💰 ยอดชำระ: ฿' . number_format($order->total, 2) . "\n"
                . '⏰ ' . now()->format('d/m/Y H:i');
            $lineNotify->send($message);
        } catch (\Exception $e) {
            Log::error('LINE notification failed', ['error' => $e->getMessage()]);
        }
    }

    protected function confirmTopupPayment($paymentIntent): void
    {
        $topup = WalletTopup::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (! $topup) {
            Log::error('Topup not found for Stripe PI', ['pi' => $paymentIntent->id]);

            return;
        }

        if ($topup->status === WalletTopup::STATUS_APPROVED) {
            return; // Idempotent
        }

        $topup->approve(0); // 0 = system-approved
    }

    protected function confirmRentalPayment($paymentIntent): void
    {
        $payment = RentalPayment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (! $payment) {
            Log::error('Rental payment not found for Stripe PI', ['pi' => $paymentIntent->id]);

            return;
        }

        if ($payment->status === RentalPayment::STATUS_COMPLETED) {
            return; // Idempotent
        }

        $payment->update([
            'gateway_response' => [
                'payment_intent_id' => $paymentIntent->id,
                'amount_received' => $paymentIntent->amount_received,
                'payment_method' => $paymentIntent->payment_method,
            ],
        ]);
        $payment->markAsCompleted();
    }

    protected function handlePaymentIntentFailed($paymentIntent): void
    {
        $errorMessage = $paymentIntent->last_payment_error?->message ?? 'Unknown';

        Log::warning('Stripe payment failed', [
            'payment_intent' => $paymentIntent->id,
            'last_error' => $errorMessage,
        ]);

        $metadata = $paymentIntent->metadata;
        $type = $metadata->type ?? null;

        // Update payment status to failed based on type
        if ($type === 'order') {
            $order = Order::where('stripe_payment_intent_id', $paymentIntent->id)->first();
            if ($order && $order->payment_status === 'pending') {
                $order->update([
                    'payment_status' => 'failed',
                    'notes' => ($order->notes ? $order->notes . "\n" : '') . "Stripe payment failed: {$errorMessage}",
                ]);
            }
        } elseif ($type === 'wallet_topup') {
            $topup = WalletTopup::where('stripe_payment_intent_id', $paymentIntent->id)->first();
            if ($topup && $topup->status === WalletTopup::STATUS_PENDING) {
                $topup->update([
                    'status' => WalletTopup::STATUS_REJECTED,
                    'reject_reason' => "Stripe: {$errorMessage}",
                ]);
            }
        } elseif ($type === 'rental') {
            $payment = RentalPayment::where('stripe_payment_intent_id', $paymentIntent->id)->first();
            if ($payment && $payment->status === RentalPayment::STATUS_PENDING) {
                $payment->update([
                    'status' => RentalPayment::STATUS_FAILED,
                    'gateway_response' => [
                        'error' => $errorMessage,
                        'payment_intent_id' => $paymentIntent->id,
                    ],
                ]);
            }
        }
    }

    /**
     * Generate license keys for order (same logic as OrderController)
     */
    protected function generateLicensesForOrder(Order $order): void
    {
        $order->load('items.product');
        $licenseService = app(LicenseService::class);
        $generated = false;

        foreach ($order->items as $item) {
            if (! $item->product || ! $item->product->requires_license) {
                continue;
            }

            $existingCount = LicenseKey::where('order_id', $order->id)
                ->where('product_id', $item->product_id)
                ->count();

            if ($existingCount >= $item->quantity) {
                continue;
            }

            $licenseType = 'yearly';
            if ($item->custom_requirements) {
                $requirements = json_decode($item->custom_requirements, true);
                if (! empty($requirements['license_type'])) {
                    $licenseType = $requirements['license_type'];
                }
            }

            $expiresAt = match ($licenseType) {
                'daily' => now()->addDay(),
                'weekly' => now()->addDays(7),
                'monthly' => now()->addDays(30),
                'yearly' => now()->addYear(),
                'lifetime' => null,
                default => now()->addYear(),
            };

            $toGenerate = $item->quantity - $existingCount;
            $licenses = $licenseService->generateLicenses(
                $licenseType,
                $toGenerate,
                1,
                $item->product_id
            );

            foreach ($licenses as $license) {
                LicenseKey::where('id', $license['id'])->update([
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'expires_at' => $expiresAt,
                ]);
            }

            $generated = true;
        }

        if ($generated && $order->customer_email) {
            try {
                Mail::to($order->customer_email)
                    ->send(new PaymentConfirmedMail($order->fresh(['items.product', 'user'])));
            } catch (\Exception $e) {
                Log::error('Failed to send Stripe payment confirmed email', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
