<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RentalPayment;
use App\Models\WalletTopup;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;

class StripeController extends Controller
{
    public function __construct(protected StripeService $stripeService) {}

    /**
     * Create PaymentIntent for order checkout
     */
    public function createOrderPaymentIntent(Order $order): JsonResponse
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['error' => 'Order already paid'], 400);
        }

        // Reuse existing PaymentIntent if available
        if ($order->stripe_payment_intent_id) {
            $intent = $this->stripeService->retrievePaymentIntent($order->stripe_payment_intent_id);

            if ($intent->status !== 'canceled') {
                return response()->json([
                    'clientSecret' => $intent->client_secret,
                    'publishableKey' => $this->stripeService->getPublishableKey(),
                ]);
            }
        }

        $intent = $this->stripeService->createPaymentIntentForOrder($order, auth()->user());

        return response()->json([
            'clientSecret' => $intent->client_secret,
            'publishableKey' => $this->stripeService->getPublishableKey(),
        ]);
    }

    /**
     * Create PaymentIntent for wallet topup
     */
    public function createTopupPaymentIntent(WalletTopup $topup): JsonResponse
    {
        if ($topup->user_id !== auth()->id()) {
            abort(403);
        }

        if ($topup->status === WalletTopup::STATUS_APPROVED) {
            return response()->json(['error' => 'Topup already approved'], 400);
        }

        if ($topup->stripe_payment_intent_id) {
            $intent = $this->stripeService->retrievePaymentIntent($topup->stripe_payment_intent_id);

            if ($intent->status !== 'canceled') {
                return response()->json([
                    'clientSecret' => $intent->client_secret,
                    'publishableKey' => $this->stripeService->getPublishableKey(),
                ]);
            }
        }

        $intent = $this->stripeService->createPaymentIntentForTopup($topup, auth()->user());

        return response()->json([
            'clientSecret' => $intent->client_secret,
            'publishableKey' => $this->stripeService->getPublishableKey(),
        ]);
    }

    /**
     * Create PaymentIntent for rental payment
     */
    public function createRentalPaymentIntent(RentalPayment $payment): JsonResponse
    {
        if ($payment->user_id !== auth()->id()) {
            abort(403);
        }

        if ($payment->status === RentalPayment::STATUS_COMPLETED) {
            return response()->json(['error' => 'Payment already completed'], 400);
        }

        if ($payment->stripe_payment_intent_id) {
            $intent = $this->stripeService->retrievePaymentIntent($payment->stripe_payment_intent_id);

            if ($intent->status !== 'canceled') {
                return response()->json([
                    'clientSecret' => $intent->client_secret,
                    'publishableKey' => $this->stripeService->getPublishableKey(),
                ]);
            }
        }

        $intent = $this->stripeService->createPaymentIntentForRental($payment, auth()->user());

        return response()->json([
            'clientSecret' => $intent->client_secret,
            'publishableKey' => $this->stripeService->getPublishableKey(),
        ]);
    }
}
