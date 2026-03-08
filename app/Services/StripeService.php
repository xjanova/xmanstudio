<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\RentalPayment;
use App\Models\User;
use App\Models\WalletTopup;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\StripeClient;

class StripeService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $secretKey = PaymentSetting::get('stripe_secret_key')
            ?: config('stripe.secret_key');

        $this->stripe = new StripeClient($secretKey);
    }

    /**
     * Get publishable key for frontend
     */
    public function getPublishableKey(): string
    {
        return PaymentSetting::get('stripe_public_key')
            ?: config('stripe.public_key');
    }

    /**
     * Get or create a Stripe Customer for a user
     */
    public function getOrCreateCustomer(User $user): Customer
    {
        if ($user->stripe_customer_id) {
            try {
                return $this->stripe->customers->retrieve($user->stripe_customer_id);
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // Customer was deleted in Stripe, create new one
            }
        }

        $customer = $this->stripe->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => ['user_id' => $user->id],
        ]);

        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer;
    }

    /**
     * Create PaymentIntent for an order
     */
    public function createPaymentIntentForOrder(Order $order, User $user): PaymentIntent
    {
        $customer = $this->getOrCreateCustomer($user);

        $intent = $this->stripe->paymentIntents->create([
            'amount' => $this->toStripeAmount($order->total),
            'currency' => config('stripe.currency', 'thb'),
            'customer' => $customer->id,
            'metadata' => [
                'type' => 'order',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
            'automatic_payment_methods' => ['enabled' => true],
            'description' => "Order #{$order->order_number}",
        ]);

        $order->update([
            'stripe_payment_intent_id' => $intent->id,
            'stripe_customer_id' => $customer->id,
        ]);

        return $intent;
    }

    /**
     * Create PaymentIntent for wallet topup
     */
    public function createPaymentIntentForTopup(WalletTopup $topup, User $user): PaymentIntent
    {
        $customer = $this->getOrCreateCustomer($user);

        $intent = $this->stripe->paymentIntents->create([
            'amount' => $this->toStripeAmount($topup->amount),
            'currency' => config('stripe.currency', 'thb'),
            'customer' => $customer->id,
            'metadata' => [
                'type' => 'wallet_topup',
                'topup_id' => $topup->id,
                'topup_number' => $topup->topup_id,
            ],
            'automatic_payment_methods' => ['enabled' => true],
            'description' => "Wallet Topup #{$topup->topup_id}",
        ]);

        $topup->update([
            'stripe_payment_intent_id' => $intent->id,
            'stripe_customer_id' => $customer->id,
        ]);

        return $intent;
    }

    /**
     * Create PaymentIntent for rental payment
     */
    public function createPaymentIntentForRental(RentalPayment $payment, User $user): PaymentIntent
    {
        $customer = $this->getOrCreateCustomer($user);

        $intent = $this->stripe->paymentIntents->create([
            'amount' => $this->toStripeAmount($payment->amount),
            'currency' => config('stripe.currency', 'thb'),
            'customer' => $customer->id,
            'metadata' => [
                'type' => 'rental',
                'payment_id' => $payment->id,
                'payment_reference' => $payment->payment_reference,
            ],
            'automatic_payment_methods' => ['enabled' => true],
            'description' => "Rental Payment #{$payment->payment_reference}",
        ]);

        $payment->update([
            'stripe_payment_intent_id' => $intent->id,
            'stripe_customer_id' => $customer->id,
            'gateway' => 'stripe',
            'gateway_reference' => $intent->id,
        ]);

        return $intent;
    }

    /**
     * Process refund
     */
    public function refund(string $paymentIntentId, ?int $amountInSatang = null): Refund
    {
        $params = ['payment_intent' => $paymentIntentId];
        if ($amountInSatang !== null) {
            $params['amount'] = $amountInSatang;
        }

        return $this->stripe->refunds->create($params);
    }

    /**
     * Retrieve PaymentIntent
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->stripe->paymentIntents->retrieve($paymentIntentId);
    }

    /**
     * Construct webhook event from payload
     */
    public function constructWebhookEvent(string $payload, string $sigHeader): \Stripe\Event
    {
        $webhookSecret = PaymentSetting::get('stripe_webhook_secret')
            ?: config('stripe.webhook_secret');

        return \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
    }

    /**
     * Convert THB to satang (Stripe smallest unit)
     */
    public function toStripeAmount(float $amount): int
    {
        return (int) round($amount * config('stripe.currency_multiplier', 100));
    }

    /**
     * Convert satang back to THB
     */
    public function fromStripeAmount(int $amount): float
    {
        return $amount / config('stripe.currency_multiplier', 100);
    }

    /**
     * Check if Stripe is enabled
     */
    public static function isEnabled(): bool
    {
        return (bool) PaymentSetting::get('stripe_enabled', false);
    }
}
