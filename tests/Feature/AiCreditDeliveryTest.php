<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\AiCreditDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * An AI-credit order's credits reach AIXMAN once, whichever way the order was paid.
 *
 * Only the customer's success page and the Stripe webhook used to deliver, so a bank-transfer
 * customer whose slip an admin approved got no credits until they reopened the success page.
 * Now admin approval (and the SMS matcher, and the Telegram bot) deliver too — and a delivery is
 * recorded, so reloads, retries and re-approvals never credit twice.
 */
class AiCreditDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK = 'https://ai.example.test/api/webhooks/xman-credit';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Sleep::fake();
        Http::preventStrayRequests();
        config([
            'services.aixman.webhook_url' => self::WEBHOOK,
            'services.aixman.webhook_secret' => 'shared-webhook-secret-for-tests',
        ]);
    }

    private function aiOrder(User $customer, array $overrides = []): Order
    {
        return Order::create($overrides + [
            'user_id' => $customer->id,
            'order_number' => 'AIC-260913-' . strtoupper(substr(md5((string) random_int(1, PHP_INT_MAX)), 0, 5)),
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '0812345678',
            'subtotal' => 299,
            'total' => 299,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'pending',
            'status' => 'processing',
            // What the checkout stores: JSON text inside the array-cast column.
            'metadata' => json_encode(['source' => 'xdreamer', 'package_slug' => 'starter', 'credits' => 100, 'bonus_credits' => 10]),
        ]);
    }

    /** @return array<int,Request> */
    private function webhookCalls(): array
    {
        return Http::recorded()->map(fn ($pair) => $pair[0])
            ->filter(fn (Request $r) => $r->url() === self::WEBHOOK)->values()->all();
    }

    private function approveAsAdmin(Order $order): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->patch(route('admin.orders.update-payment-status', $order), ['payment_status' => 'paid'])
            ->assertSessionHas('success');
    }

    public function test_an_admin_approving_the_slip_delivers_the_credits(): void
    {
        Http::fake([self::WEBHOOK => Http::response(['success' => true, 'balance' => 110])]);
        $customer = User::factory()->create();
        $order = $this->aiOrder($customer);

        $this->approveAsAdmin($order);

        $calls = $this->webhookCalls();
        $this->assertCount(1, $calls);
        $this->assertSame('shared-webhook-secret-for-tests', $calls[0]->header('x-webhook-secret')[0] ?? null);
        $this->assertSame([
            'userId' => $customer->id,
            'packageId' => 'starter',
            'orderId' => (string) $order->id,
            'credits' => 100,
            'bonusCredits' => 10,
        ], $calls[0]->data());
        $this->assertNotEmpty(AiCreditDelivery::metadata($order->fresh())['aixman_notified_at'] ?? null);
    }

    public function test_approving_again_and_reloading_the_success_page_do_not_credit_twice(): void
    {
        Http::fake([self::WEBHOOK => Http::response(['success' => true])]);
        $customer = User::factory()->create();
        $order = $this->aiOrder($customer);

        $this->approveAsAdmin($order);
        $this->approveAsAdmin($order->fresh());
        $this->actingAs($customer)->get(route('xdreamer.checkout.success', $order))->assertOk();
        $this->actingAs($customer)->get(route('xdreamer.checkout.success', $order))->assertOk();

        $this->assertCount(1, $this->webhookCalls());
    }

    public function test_nothing_is_sent_before_the_order_is_paid(): void
    {
        Http::fake([self::WEBHOOK => Http::response(['success' => true])]);
        $customer = User::factory()->create();
        $order = $this->aiOrder($customer);

        $this->actingAs($customer)->get(route('xdreamer.checkout.success', $order))->assertOk();

        $this->assertCount(0, $this->webhookCalls());
    }

    public function test_a_refused_delivery_is_not_recorded_and_goes_through_later(): void
    {
        // AixmanService tries twice per call: the approval's two attempts fail, the next succeeds.
        Http::fake([self::WEBHOOK => Http::sequence()->push([], 500)->push([], 500)->push(['success' => true])]);
        $customer = User::factory()->create();
        $order = $this->aiOrder($customer);

        $this->approveAsAdmin($order);
        $this->assertEmpty(AiCreditDelivery::metadata($order->fresh())['aixman_notified_at'] ?? null, 'a failed send is not marked delivered');
        $this->assertSame('paid', $order->fresh()->payment_status, 'and the approval itself still stands');

        $this->actingAs($customer)->get(route('xdreamer.checkout.success', $order))->assertOk();

        $this->assertNotEmpty(AiCreditDelivery::metadata($order->fresh())['aixman_notified_at'] ?? null);
        $this->assertCount(3, $this->webhookCalls());
    }

    public function test_a_delivery_in_progress_elsewhere_is_not_repeated(): void
    {
        Http::fake([self::WEBHOOK => Http::response(['success' => true])]);
        // Created already paid, without the observer queueing a delivery of its own.
        $order = Order::withoutEvents(fn () => $this->aiOrder(User::factory()->create(), ['payment_status' => 'paid', 'paid_at' => now()]));
        $held = Cache::lock('ai-credits:deliver:' . $order->id, 60);
        $this->assertTrue($held->get());

        $this->assertFalse(app(AiCreditDelivery::class)->deliver($order));
        $this->assertCount(0, $this->webhookCalls());

        $held->release();
        $this->assertTrue(app(AiCreditDelivery::class)->deliver($order));
        $this->assertCount(1, $this->webhookCalls());
    }

    /**
     * Both customer pages had "bonus@endif" glued together: Blade read it as text, the @if never
     * closed, and every buyer got a 500 right after ordering (2026-06-27 → 2026-09-13).
     */
    public function test_the_payment_and_success_pages_render_in_every_state(): void
    {
        Http::fake([self::WEBHOOK => Http::response(['success' => true])]);
        $customer = User::factory()->create();
        $order = $this->aiOrder($customer, ['status' => 'pending']);

        $this->actingAs($customer)->get(route('xdreamer.checkout.payment', $order))
            ->assertOk()->assertSee('100 credits', false)->assertSee('+ 10 bonus', false);
        $this->actingAs($customer)->get(route('xdreamer.checkout.success', $order))
            ->assertOk()->assertSee('Pending review', false);

        $this->approveAsAdmin($order);

        $this->actingAs($customer)->get(route('xdreamer.checkout.success', $order))
            ->assertOk()
            ->assertDontSee('Pending review', false)
            ->assertSee('have been added to your account', false);
    }

    public function test_orders_that_are_not_ai_credits_are_left_alone(): void
    {
        Http::fake([self::WEBHOOK => Http::response(['success' => true])]);
        $order = $this->aiOrder(User::factory()->create(), ['metadata' => json_encode(['plan' => 'pro'])]);

        $this->approveAsAdmin($order);

        $this->assertCount(0, $this->webhookCalls());
    }
}
