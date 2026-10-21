<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\PaymentIntent;
use Stripe\Util\ApiVersion;
use Stripe\Util\CaseInsensitiveArray;
use Tests\TestCase;

/**
 * What our code sends to and reads from Stripe, held against the SDK we ship.
 *
 * stripe/stripe-php pins the API version, and each major SDK release moves it to the next major
 * API release (v22: 2026-08-26.dahlia → 2026-09-30.endive), which may drop request parameters —
 * endive removed `payment_method_types` from PaymentIntent create. These tests run the real SDK
 * against a fake HTTP client, so a bump that changes what goes over the wire, or how a webhook
 * is verified and parsed, fails here instead of at a customer's checkout.
 */
class StripeSdkContractTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_contract_test_secret';

    /** @var ClientInterface&object{requests: array<int,array>, responses: array<int,array>} */
    private ClientInterface $stripeHttp;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(); // LINE / Telegram notifications stay inside the test
        config([
            'stripe.secret_key' => 'sk_test_contract',
            'stripe.webhook_secret' => self::WEBHOOK_SECRET,
            'stripe.currency' => 'thb',
        ]);

        $this->stripeHttp = new class implements ClientInterface
        {
            public array $requests = [];

            public array $responses = [];

            public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
            {
                $this->requests[] = compact('method', 'absUrl', 'headers', 'params');
                $body = array_shift($this->responses) ?? throw new \LogicException("Unexpected Stripe call: {$method} {$absUrl}");

                return [json_encode($body), 200, new CaseInsensitiveArray(['request-id' => 'req_contract'])];
            }
        };
        ApiRequestor::setHttpClient($this->stripeHttp);
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);
        parent::tearDown();
    }

    private function stripeOrder(User $customer, array $overrides = []): Order
    {
        return Order::create($overrides + [
            'user_id' => $customer->id,
            'order_number' => 'STR-' . strtoupper(Str::random(8)),
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '0812345678',
            'subtotal' => 299,
            'total' => 299,
            'payment_method' => 'stripe',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);
    }

    private function header(array $rawHeaders, string $name): ?string
    {
        foreach ($rawHeaders as $line) {
            [$key, $value] = array_map('trim', explode(':', $line, 2));
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    private function postWebhook(array $event, ?string $secret = self::WEBHOOK_SECRET)
    {
        $payload = json_encode($event);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return $this->call('POST', '/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ], $payload);
    }

    private function paymentIntentEvent(string $type, array $intent): array
    {
        return [
            'id' => 'evt_' . Str::random(12),
            'object' => 'event',
            // The webhook endpoint's own version, set in the Stripe dashboard — older than the
            // SDK's pin. The SDK must not refuse it.
            'api_version' => '2026-08-26.dahlia',
            'type' => $type,
            'data' => ['object' => $intent + ['object' => 'payment_intent']],
        ];
    }

    public function test_an_order_payment_intent_lets_stripe_choose_the_payment_methods(): void
    {
        $customer = User::factory()->create();
        $order = $this->stripeOrder($customer);
        $this->stripeHttp->responses = [
            ['id' => 'cus_contract', 'object' => 'customer'],
            ['id' => 'pi_contract', 'object' => 'payment_intent', 'client_secret' => 'pi_contract_secret_x', 'status' => 'requires_payment_method'],
        ];

        $stripe = app(StripeService::class);
        $intent = $stripe->createPaymentIntentForOrder($order, $customer);

        [$customerCall, $intentCall] = $this->stripeHttp->requests;
        $this->assertSame(['post', 'https://api.stripe.com/v1/customers'], [$customerCall['method'], $customerCall['absUrl']]);
        $this->assertSame($customer->email, $customerCall['params']['email']);

        $this->assertSame(['post', 'https://api.stripe.com/v1/payment_intents'], [$intentCall['method'], $intentCall['absUrl']]);
        $params = $intentCall['params'];
        $this->assertSame(['enabled' => 'true'], $params['automatic_payment_methods']);
        $this->assertArrayNotHasKey('payment_method_types', $params, 'Removed from PaymentIntent create in API version endive');
        $this->assertSame($stripe->toStripeAmount($stripe->calculateStripeFee(299)['total']), $params['amount']);
        $this->assertSame('thb', $params['currency']);
        $this->assertSame('cus_contract', $params['customer']);
        $this->assertSame('order', $params['metadata']['type']);
        $this->assertSame($order->id, $params['metadata']['order_id']);

        // Each request goes out on the SDK's pinned version. When the next major SDK moves it past
        // endive, re-read that release's breaking changes against StripeService before updating this.
        $this->assertSame(ApiVersion::CURRENT, $this->header($intentCall['headers'], 'Stripe-Version'));
        $this->assertSame('endive', ApiVersion::CURRENT_MAJOR);

        $this->assertSame('pi_contract_secret_x', $intent->client_secret);
        $order->refresh();
        $this->assertSame('pi_contract', $order->stripe_payment_intent_id);
        $this->assertSame('cus_contract', $order->stripe_customer_id);
    }

    public function test_the_fields_the_self_heal_and_refund_paths_rely_on_still_parse(): void
    {
        $this->stripeHttp->responses = [
            [
                'id' => 'pi_heal', 'object' => 'payment_intent', 'status' => 'succeeded',
                'payment_method' => 'pm_heal', 'amount_received' => 29900,
                'payment_method_types' => ['card', 'promptpay'], 'latest_charge' => 'ch_heal',
            ],
            ['id' => 're_heal', 'object' => 'refund', 'status' => 'succeeded', 'amount' => 1000],
        ];
        $stripe = app(StripeService::class);

        $intent = $stripe->retrievePaymentIntent('pi_heal');
        $refund = $stripe->refund('pi_heal', 1000);

        $this->assertSame(['get', 'https://api.stripe.com/v1/payment_intents/pi_heal'], [$this->stripeHttp->requests[0]['method'], $this->stripeHttp->requests[0]['absUrl']]);
        $this->assertSame(PaymentIntent::STATUS_SUCCEEDED, $intent->status);
        $this->assertSame('pm_heal', $intent->payment_method);
        $this->assertSame(29900, $intent->amount_received);
        $this->assertSame(['card', 'promptpay'], $intent->payment_method_types);
        $this->assertSame('ch_heal', $intent->latest_charge);

        $this->assertSame(['post', 'https://api.stripe.com/v1/refunds'], [$this->stripeHttp->requests[1]['method'], $this->stripeHttp->requests[1]['absUrl']]);
        $this->assertSame(['payment_intent' => 'pi_heal', 'amount' => 1000], $this->stripeHttp->requests[1]['params']);
        $this->assertSame('re_heal', $refund->id);
    }

    public function test_a_signed_payment_succeeded_webhook_marks_the_order_paid_once(): void
    {
        $order = $this->stripeOrder(User::factory()->create(), ['stripe_payment_intent_id' => 'pi_paid']);
        $event = $this->paymentIntentEvent('payment_intent.succeeded', [
            'id' => 'pi_paid', 'status' => 'succeeded', 'payment_method' => 'pm_paid',
            'amount_received' => 29900, 'payment_method_types' => ['card'], 'latest_charge' => 'ch_paid',
            'metadata' => ['type' => 'order', 'order_id' => (string) $order->id],
        ]);

        $this->postWebhook($event)->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertSame('pm_paid', $order->stripe_payment_method_id);
        // assertEquals, not assertSame: a MySQL JSON column hands the keys back in its own order.
        $this->assertEquals(['amount_received' => 29900, 'payment_method_types' => ['card'], 'latest_charge' => 'ch_paid'], $order->stripe_metadata);
        $paidAt = $order->paid_at;

        // Stripe retries deliveries; a second one changes nothing.
        $this->travel(5)->minutes();
        $this->postWebhook($event)->assertOk();
        $this->assertEquals($paidAt, $order->fresh()->paid_at);

        $this->assertSame([], $this->stripeHttp->requests, 'A webhook is verified locally — no call back to Stripe');
    }

    public function test_a_failed_payment_webhook_marks_the_order_failed_with_stripes_reason(): void
    {
        $order = $this->stripeOrder(User::factory()->create(), ['stripe_payment_intent_id' => 'pi_declined']);

        $this->postWebhook($this->paymentIntentEvent('payment_intent.payment_failed', [
            'id' => 'pi_declined', 'status' => 'requires_payment_method',
            'last_payment_error' => ['type' => 'card_error', 'code' => 'card_declined', 'message' => 'Your card was declined.'],
            'metadata' => ['type' => 'order', 'order_id' => (string) $order->id],
        ]))->assertOk();

        $order->refresh();
        $this->assertSame('failed', $order->payment_status);
        $this->assertStringContainsString('Your card was declined.', (string) $order->notes);
    }

    public function test_a_webhook_signed_with_another_secret_is_refused(): void
    {
        $order = $this->stripeOrder(User::factory()->create(), ['stripe_payment_intent_id' => 'pi_forged']);

        $this->postWebhook($this->paymentIntentEvent('payment_intent.succeeded', [
            'id' => 'pi_forged', 'status' => 'succeeded',
            'metadata' => ['type' => 'order', 'order_id' => (string) $order->id],
        ]), 'whsec_someone_else')->assertStatus(400);

        $this->assertSame('pending', $order->fresh()->payment_status);
    }
}
