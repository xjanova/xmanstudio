<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyAixmanSignature;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\Order;
use App\Models\TpixPayment;
use App\Models\TpixPriceSnapshot;
use App\Models\User;
use App\Services\AiCreditDelivery;
use App\Services\AiCreditOrders;
use App\Services\TpixPay\TpixMath;
use App\Services\TpixPay\TpixPaySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\FakesTpixChain;
use Tests\TestCase;

/**
 * ai.xman4289.com sells AI credit packages for TPIX on its own page through the signed
 * /api/aixman/tpix API, and an AI credit package paid in TPIX is cheaper (20% by default) on every
 * path — that API and the normal AI credit checkout alike.
 *
 * Fake pool: ฿3.25 per TPIX (FakesTpixChain); pool limit 5 × 19.99 USDT × 32.5 = ฿3,248.37 a quote.
 */
class AixmanTpixApiTest extends TestCase
{
    use FakesTpixChain;
    use RefreshDatabase;

    private const SECRET = 'test-secret';

    private const AIXMAN = 'https://ai.example.test';

    private const WEBHOOK = self::AIXMAN . '/api/webhooks/xman-credit';

    private const ORDERS = '/api/aixman/tpix/orders';

    private const STATUS = '/api/aixman/tpix/payments/status';

    private const TX = '/api/aixman/tpix/payments/tx';

    private User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Cache::flush();
        $this->withoutVite();

        config([
            'services.aixman.api_base' => self::AIXMAN,
            'services.aixman.webhook_url' => self::WEBHOOK,
            'services.aixman.webhook_secret' => self::SECRET,
        ]);

        // Registered before the chain fake, so these two URLs are answered here and not by its catch-all
        Http::fake([
            self::AIXMAN . '/api/packages' => Http::response(['packages' => [
                ['slug' => 'starter', 'name' => 'ผู้เริ่มฝัน', 'price_thb' => 0, 'credits' => 50, 'bonus_credits' => 0],
                ['slug' => 'creator', 'name' => 'ครีเอเตอร์', 'price_thb' => 299, 'credits' => 3000, 'bonus_credits' => 0],
                ['slug' => 'weaver', 'name' => 'นักทอ', 'price_thb' => 490, 'credits' => 5000, 'bonus_credits' => 500],
                ['slug' => 'big', 'name' => 'ใหญ่', 'price_thb' => 4000, 'credits' => 50000, 'bonus_credits' => 0],
                ['slug' => 'whale', 'name' => 'วาฬ', 'price_thb' => 5000, 'credits' => 70000, 'bonus_credits' => 0],
            ]]),
            self::WEBHOOK => Http::response(['success' => true]),
        ]);
        $this->fakeTpixChain();
        $this->enableTpixPayments();
        $this->seedTwap();

        // Without an admin every web page redirects to the setup wizard
        User::factory()->create(['role' => 'admin']);
        $this->buyer = User::factory()->create(['name' => 'ลูกค้า ทดสอบ', 'email' => 'buyer@example.test']);
    }

    // ── the signature ────────────────────────────────────────────────

    /**
     * The contract's worked example, pinned so aixman's signer (computed independently with openssl)
     * and this one can never drift apart.
     */
    public function test_the_contract_signature_example(): void
    {
        $body = '{"user_id":7,"package_slug":"creator"}';

        $this->assertSame('775697600a35aab8f1cf4b1393196eddc04635b8039bf1df53d6421e8bd5919e', VerifyAixmanSignature::signingKey('test-secret'));
        $this->assertSame('4b69afe29d2c67e5de0efad8215b083d61be961750e411b271961cc2fcba9fe7', hash('sha256', $body));
        $this->assertSame(
            '75647e6716da121cbbb1dc5779a8574fb85844d2ca9863de39d4e40649e86420',
            VerifyAixmanSignature::sign('test-secret', 1761710000, 'POST', '/api/aixman/tpix/orders', $body),
        );

        // And the middleware takes exactly those headers for exactly that body at that time: the call
        // gets past the signature (to "no such user" — there is no user 7 here)
        Carbon::setTestNow(Carbon::createFromTimestamp(1761710000));
        $this->call('POST', self::ORDERS, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_AIXMAN_TIMESTAMP' => '1761710000',
            'HTTP_X_AIXMAN_SIGNATURE' => '75647e6716da121cbbb1dc5779a8574fb85844d2ca9863de39d4e40649e86420',
        ], $body)
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'ไม่พบบัญชีผู้ใช้นี้ หรือบัญชีถูกระงับการใช้งาน');
    }

    public function test_a_missing_wrong_or_stale_signature_is_refused(): void
    {
        $body = json_encode(['user_id' => $this->buyer->id, 'package_slug' => 'weaver']);
        $headers = fn (string $ts, string $sig) => [
            'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_AIXMAN_TIMESTAMP' => $ts, 'HTTP_X_AIXMAN_SIGNATURE' => $sig,
        ];
        $now = now()->getTimestamp();

        // No headers at all
        $this->call('POST', self::ORDERS, [], [], [], ['CONTENT_TYPE' => 'application/json'], $body)
            ->assertStatus(401)->assertJsonPath('success', false)->assertJsonPath('message', 'ลายเซ็นคำขอไม่ถูกต้องหรือหมดอายุ');

        // Signed with another secret
        $this->call('POST', self::ORDERS, [], [], [], $headers((string) $now, VerifyAixmanSignature::sign('other-secret', $now, 'POST', self::ORDERS, $body)), $body)
            ->assertStatus(401);

        // Right secret, but for another path, another body, another method
        $this->call('POST', self::ORDERS, [], [], [], $headers((string) $now, VerifyAixmanSignature::sign(self::SECRET, $now, 'POST', self::STATUS, $body)), $body)
            ->assertStatus(401);
        $this->call('POST', self::ORDERS, [], [], [], $headers((string) $now, VerifyAixmanSignature::sign(self::SECRET, $now, 'POST', self::ORDERS, $body . ' ')), $body)
            ->assertStatus(401);
        $this->call('POST', self::ORDERS, [], [], [], $headers((string) $now, VerifyAixmanSignature::sign(self::SECRET, $now, 'GET', self::ORDERS, $body)), $body)
            ->assertStatus(401);

        // Correctly signed but more than 300 s old, or from the future
        foreach ([$now - 301, $now + 301] as $ts) {
            $this->call('POST', self::ORDERS, [], [], [], $headers((string) $ts, VerifyAixmanSignature::sign(self::SECRET, $ts, 'POST', self::ORDERS, $body)), $body)
                ->assertStatus(401);
        }

        // Within the window (and an upper-case hex signature is the same signature)
        $this->call('POST', self::ORDERS, [], [], [], $headers((string) ($now - 299), strtoupper(VerifyAixmanSignature::sign(self::SECRET, $now - 299, 'POST', self::ORDERS, $body))), $body)
            ->assertOk();

        $this->assertSame(1, Order::count());
    }

    public function test_without_the_shared_secret_the_api_is_closed(): void
    {
        config(['services.aixman.webhook_secret' => null]);

        $this->signed(self::ORDERS, ['user_id' => $this->buyer->id, 'package_slug' => 'weaver'], secret: 'anything')
            ->assertStatus(503)
            ->assertJsonPath('success', false);

        $this->assertSame(0, Order::count());
    }

    public function test_an_address_that_keeps_failing_the_signature_is_turned_away(): void
    {
        $body = json_encode(['user_id' => $this->buyer->id, 'package_slug' => 'weaver']);
        $bad = ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => '203.0.113.9', 'HTTP_X_AIXMAN_TIMESTAMP' => (string) now()->getTimestamp(), 'HTTP_X_AIXMAN_SIGNATURE' => str_repeat('a', 64)];

        foreach (range(1, VerifyAixmanSignature::REFUSED_PER_MINUTE) as $i) {
            $this->call('POST', self::ORDERS, [], [], [], $bad, $body)->assertStatus(401);
        }
        $this->call('POST', self::ORDERS, [], [], [], $bad, $body)
            ->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertHeader('Retry-After');

        // aixman's own address is not affected
        $this->signed(self::ORDERS, ['user_id' => $this->buyer->id, 'package_slug' => 'weaver'])->assertOk();
    }

    public function test_unsigned_calls_do_not_use_up_a_customers_rate_limit(): void
    {
        $body = json_encode(['user_id' => $this->buyer->id, 'package_slug' => 'weaver']);
        foreach (range(1, 12) as $i) {
            $this->call('POST', self::ORDERS, [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_AIXMAN_TIMESTAMP' => (string) now()->getTimestamp(), 'HTTP_X_AIXMAN_SIGNATURE' => str_repeat('0', 64)], $body)
                ->assertStatus(401);
        }

        // Ten a minute per customer — all ten still there, and they reuse the one order
        foreach (range(1, 10) as $i) {
            $this->signed(self::ORDERS, ['user_id' => $this->buyer->id, 'package_slug' => 'weaver'])->assertOk();
        }
        $this->signed(self::ORDERS, ['user_id' => $this->buyer->id, 'package_slug' => 'weaver'])
            ->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'ส่งคำขอถี่เกินไป กรุณารอสักครู่แล้วลองใหม่')
            ->assertHeader('Retry-After');

        // Per customer, not per address: everyone else's calls come from the same aixman server
        $other = User::factory()->create();
        $this->signed(self::ORDERS, ['user_id' => $other->id, 'package_slug' => 'weaver'])->assertOk();

        $this->assertSame(2, Order::count());
    }

    // ── orders ───────────────────────────────────────────────────────

    public function test_an_order_is_made_at_the_tpix_discount_with_a_quote_for_the_discounted_total(): void
    {
        $response = $this->signed(self::ORDERS, ['user_id' => $this->buyer->id, 'package_slug' => 'weaver'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.package', ['slug' => 'weaver', 'name' => 'นักทอ', 'credits' => 5000, 'bonus_credits' => 500])
            ->assertJsonPath('data.price_thb', '490.00')
            ->assertJsonPath('data.discount_thb', '98.00')
            ->assertJsonPath('data.total_thb', '392.00')
            ->assertJsonPath('data.discount_percent', 20)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.chain_id', 4289)
            ->assertJsonPath('data.receive_address', strtolower($this->shopAddress));

        $order = Order::sole();
        $payment = TpixPayment::sole();
        $data = $response->json('data');

        $this->assertSame($payment->reference, $data['reference']);
        $this->assertSame($order->order_number, $data['order_number']);
        $this->assertStringStartsWith('AIC-', $order->order_number);

        // The order: subtotal = price, discount, total = what is paid; the line keeps the package price
        $this->assertSame($this->buyer->id, (int) $order->user_id);
        $this->assertSame('tpix', $order->payment_method);
        $this->assertSame('pending', $order->status);
        $this->assertSame('490.00', TpixMath::thb($order->subtotal));
        $this->assertSame('98.00', TpixMath::thb($order->discount));
        $this->assertSame('392.00', TpixMath::thb($order->total));
        $this->assertSame('ลูกค้า ทดสอบ', $order->customer_name);
        $this->assertSame('buyer@example.test', $order->customer_email);
        $this->assertSame('490.00', TpixMath::thb($order->items()->sole()->price));
        $metadata = AiCreditDelivery::metadata($order);
        $this->assertSame(['xdreamer', 'weaver', 5000, 500, 'aixman-tpix', 20], [
            $metadata['source'], $metadata['package_slug'], $metadata['credits'], $metadata['bonus_credits'], $metadata['ref'], $metadata['tpix_discount_percent'],
        ]);

        // The quote is for the discounted total: ฿392 / ฿3.25 = 120.6154 TPIX (rounded up) + its tag
        $this->assertSame('392.00', TpixMath::thb($payment->amount_thb));
        $this->assertTrue($payment->payable->is($order));
        $this->assertStringStartsWith('120.6154', $data['amount_tpix']);
        $this->assertSame((string) $payment->amount_wei, $data['amount_wei']);
        $this->assertSame($data['amount_wei'], TpixMath::toWei($data['amount_tpix']), 'amount_tpix is exactly amount_wei');
        $this->assertStringStartsWith('ethereum:' . strtolower($this->shopAddress) . '@4289', $data['payment_uri']);
        $this->assertSame($payment->paymentUri(), $data['payment_uri']);
        $this->assertSame(route('tpix-pay.show', $payment), $data['pay_url']);
        $this->assertIsInt($data['seconds_left']);
        $this->assertGreaterThan(1700, $data['seconds_left']);
        $this->assertNotEmpty($data['expires_at']);
        $this->assertNotEmpty($data['rate_thb']);
        $this->assertSame(route('xdreamer.checkout.success', $order->id), $payment->meta['return_url']);
    }

    public function test_a_pending_order_for_the_same_package_is_reused_not_doubled(): void
    {
        $first = $this->signed(self::ORDERS, ['user_id' => $this->buyer->id, 'package_slug' => 'weaver'])->assertOk()->json('data');
        $again = $this->signed(self::ORDERS, ['user_id' => $this->buyer->id, 'package_slug' => 'weaver'])->assertOk()->json('data');

        // Same order and the same open quote
        $this->assertSame($first['order_number'], $again['order_number']);
        $this->assertSame($first['reference'], $again['reference']);
        $this->assertSame(1, Order::count());
        $this->assertSame(1, TpixPayment::count());

        // Its quote expired: the same order, a fresh quote
        $this->travel(31)->minutes();
        $this->seedTwap();
        $requoted = $this->signed(self::ORDERS, ['user_id' => $this->buyer->id, 'package_slug' => 'weaver'])->assertOk()->json('data');
        $this->assertSame($first['order_number'], $requoted['order_number']);
        $this->assertNotSame($first['reference'], $requoted['reference']);
        $this->assertSame(1, Order::count());

        // Another package is another order
        $this->signed(self::ORDERS, ['user_id' => $this->buyer->id, 'package_slug' => 'creator'])->assertOk();
        $this->assertSame(2, Order::count());

        // The discount changed since: the old order is not priced as the package is now → a new one
        app(TpixPaySettings::class)->save([
            'enabled' => true, 'address' => $this->shopAddress, 'confirmations' => 3, 'ttl' => 30, 'ai_credit_discount_percent' => 10,
        ]);
        $repriced = $this->signed(self::ORDERS, ['user_id' => $this->buyer->id, 'package_slug' => 'weaver'])->assertOk()->json('data');
        $this->assertNotSame($first['order_number'], $repriced['order_number']);
        $this->assertSame('441.00', $repriced['total_thb']);
        $this->assertSame(10, $repriced['discount_percent']);

        // Older than a day: a new order
        $this->travel(25)->hours();
        $this->seedTwap();
        $later = $this->signed(self::ORDERS, ['user_id' => $this->buyer->id, 'package_slug' => 'weaver'])->assertOk()->json('data');
        $this->assertNotSame($repriced['order_number'], $later['order_number']);
        $this->assertSame(4, Order::count());
    }

    public function test_a_paid_order_is_not_reused(): void
    {
        $first = $this->signed(self::ORDERS, ['user_id' => $this->buyer->id, 'package_slug' => 'weaver'])->assertOk()->json('data');
        Order::sole()->update(['payment_status' => 'paid']);

        $next = $this->signed(self::ORDERS, ['user_id' => $this->buyer->id, 'package_slug' => 'weaver'])->assertOk()->json('data');

        $this->assertNotSame($first['order_number'], $next['order_number']);
    }

    public function test_a_second_call_while_the_first_holds_the_users_lock_makes_nothing(): void
    {
        Sleep::fake(syncWithCarbon: true);
        $held = Cache::lock('aixman-tpix:order:' . $this->buyer->id, 30);
        $this->assertTrue($held->get());

        $this->signed(self::ORDERS, ['user_id' => $this->buyer->id, 'package_slug' => 'weaver'])
            ->assertStatus(429)
            ->assertJsonPath('success', false);

        $this->assertSame(0, Order::count());
        $this->assertSame(0, TpixPayment::count());
    }

    public function test_payments_switched_off_are_refused_in_thai_and_nothing_is_written(): void
    {
        $this->enableTpixPayments(enabled: false);

        $this->signed(self::ORDERS, ['user_id' => $this->buyer->id, 'package_slug' => 'weaver'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'ขณะนี้ยังชำระด้วยเหรียญ TPIX ไม่ได้ กรุณาเลือกช่องทางอื่น');

        $this->assertSame(0, Order::count());
        $this->assertSame(0, TpixPayment::count());
    }

    public function test_no_quote_rate_yet_is_refused(): void
    {
        TpixPriceSnapshot::query()->delete();
        Cache::flush();
        $this->app->forgetScopedInstances();

        $this->signed(self::ORDERS, ['user_id' => $this->buyer->id, 'package_slug' => 'weaver'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame(0, Order::count());
    }

    public function test_unknown_users_and_packages_are_refused(): void
    {
        $inactive = User::factory()->create(['is_active' => false]);
        $cases = [
            [['user_id' => 999999, 'package_slug' => 'weaver'], 'ไม่พบบัญชีผู้ใช้นี้ หรือบัญชีถูกระงับการใช้งาน'],
            [['user_id' => $inactive->id, 'package_slug' => 'weaver'], 'ไม่พบบัญชีผู้ใช้นี้ หรือบัญชีถูกระงับการใช้งาน'],
            [['user_id' => $this->buyer->id, 'package_slug' => 'nope'], 'ไม่พบแพ็กเกจนี้ หรือเป็นแพ็กเกจที่ไม่ต้องชำระเงิน'],
            [['user_id' => $this->buyer->id, 'package_slug' => 'starter'], 'ไม่พบแพ็กเกจนี้ หรือเป็นแพ็กเกจที่ไม่ต้องชำระเงิน'],
            [['user_id' => $this->buyer->id], 'คำขอไม่ครบถ้วน (ต้องมี user_id และ package_slug)'],
            [['user_id' => 'abc', 'package_slug' => 'weaver'], 'คำขอไม่ครบถ้วน (ต้องมี user_id และ package_slug)'],
        ];

        foreach ($cases as [$body, $message]) {
            $this->signed(self::ORDERS, $body)->assertStatus(422)->assertJsonPath('success', false)->assertJsonPath('message', $message);
        }

        $this->assertSame(0, Order::count());
    }

    public function test_the_pool_limit_applies_to_the_discounted_total(): void
    {
        // ฿5,000 − 20% = ฿4,000: more than the ฿3,248.37 the pool can carry
        $this->signed(self::ORDERS, ['user_id' => $this->buyer->id, 'package_slug' => 'whale'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'ยอดนี้เกินสภาพคล่องของตลาด TPIX ตอนนี้ — ชำระด้วย TPIX ได้สูงสุด 3,248.37 บาทต่อรายการ');
        $this->assertSame(0, Order::count());

        // ฿4,000 − 20% = ฿3,200: fits only because of the discount
        $this->signed(self::ORDERS, ['user_id' => $this->buyer->id, 'package_slug' => 'big'])
            ->assertOk()
            ->assertJsonPath('data.total_thb', '3200.00');
    }

    public function test_only_the_query_string_free_signed_body_is_read(): void
    {
        // A user_id slipped into the query string (not covered by the signature) is ignored
        $other = User::factory()->create();
        $this->signed(self::ORDERS . '?user_id=' . $other->id, ['package_slug' => 'weaver'], path: self::ORDERS)
            ->assertStatus(422);

        $this->assertSame(0, Order::count());
    }

    // ── status ───────────────────────────────────────────────────────

    public function test_status_is_for_the_owner_only(): void
    {
        $reference = $this->order()['reference'];
        $stranger = User::factory()->create();

        $this->signed(self::STATUS, ['user_id' => $stranger->id, 'reference' => $reference])
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'ไม่พบรายการชำระนี้');
        $this->signed(self::STATUS, ['user_id' => $this->buyer->id, 'reference' => 'TPXDOESNOTEXIST'])->assertNotFound();
        $this->signed(self::STATUS, ['user_id' => $this->buyer->id, 'reference' => '../../etc'])->assertNotFound();

        $this->signed(self::STATUS, ['user_id' => $this->buyer->id, 'reference' => $reference])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reference', $reference)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.label', 'รอชำระ')
            ->assertJsonPath('data.settled', false)
            ->assertJsonPath('data.payable', true)
            ->assertJsonPath('data.confirmations', 0)
            ->assertJsonPath('data.required_confirmations', 3)
            ->assertJsonPath('data.tx_hash', null)
            ->assertJsonPath('data.explorer_url', null)
            ->assertJsonPath('data.order_number', Order::sole()->order_number)
            ->assertJsonPath('data.order_status', 'pending')
            ->assertJsonPath('data.credits_delivered', false);
    }

    public function test_a_quote_that_is_not_for_an_ai_credit_order_is_not_found(): void
    {
        $reference = $this->order()['reference'];
        $order = Order::sole();
        $order->forceFill(['metadata' => json_encode(['source' => 'something-else'])])->saveQuietly();

        $this->signed(self::STATUS, ['user_id' => $this->buyer->id, 'reference' => $reference])->assertNotFound();
        $this->signed(self::TX, ['user_id' => $this->buyer->id, 'reference' => $reference, 'tx_hash' => self::txHash('x')])->assertNotFound();
    }

    public function test_paid_in_tpix_the_status_call_delivers_the_credits_once(): void
    {
        Queue::fake(); // the observer's queued delivery does not run: the status call must do it
        $quote = $this->order();
        $payment = TpixPayment::sole();
        $hash = self::txHash('aixman-paid');
        $this->mineTransfer($hash, $payment->amount_wei, 990, now()->timestamp);

        $this->signed(self::TX, ['user_id' => $this->buyer->id, 'reference' => $quote['reference'], 'tx_hash' => $hash])
            ->assertOk()
            ->assertJsonPath('data.result', 'paid')
            ->assertJsonPath('data.settled', true);
        $this->assertCount(0, $this->webhookCalls());

        $this->signed(self::STATUS, ['user_id' => $this->buyer->id, 'reference' => $quote['reference']])
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.settled', true)
            ->assertJsonPath('data.tx_hash', $hash)
            ->assertJsonPath('data.explorer_url', 'https://explorer.tpix.online/tx/' . $hash)
            ->assertJsonPath('data.credits_delivered', true);

        $calls = $this->webhookCalls();
        $this->assertCount(1, $calls);
        $this->assertSame([
            'userId' => $this->buyer->id,
            'packageId' => 'weaver',
            'orderId' => (string) Order::sole()->id,
            'credits' => 5000,
            'bonusCredits' => 500,
        ], $calls[0]->data());
        $this->assertSame('paid', Order::sole()->payment_status);

        // Polling on does not credit again
        $this->signed(self::STATUS, ['user_id' => $this->buyer->id, 'reference' => $quote['reference']])
            ->assertOk()->assertJsonPath('data.credits_delivered', true);
        $this->assertCount(1, $this->webhookCalls());
    }

    // ── tx ───────────────────────────────────────────────────────────

    public function test_tx_hands_the_hash_to_the_payment_service_for_the_owner_only(): void
    {
        $quote = $this->order();
        $payment = TpixPayment::sole();

        $this->signed(self::TX, ['user_id' => User::factory()->create()->id, 'reference' => $quote['reference'], 'tx_hash' => self::txHash('x')])
            ->assertNotFound();
        $this->signed(self::TX, ['user_id' => $this->buyer->id, 'reference' => $quote['reference']])
            ->assertStatus(422)
            ->assertJsonPath('message', 'กรุณาวางเลขธุรกรรม (tx hash)');

        // Answered by TpixPaymentService::submitTx, with the status fields alongside
        $this->signed(self::TX, ['user_id' => $this->buyer->id, 'reference' => $quote['reference'], 'tx_hash' => 'not-a-hash'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.result', 'rejected')
            ->assertJsonPath('data.reason', 'bad_hash')
            ->assertJsonPath('data.message', 'เลขธุรกรรมไม่ถูกต้อง (ต้องขึ้นต้นด้วย 0x ตามด้วยอักษร 64 ตัว)')
            ->assertJsonPath('data.reference', $quote['reference'])
            ->assertJsonPath('data.status', 'pending');

        $wrong = self::txHash('wrong-amount');
        $this->mineTransfer($wrong, '1000000000000000000', 990, now()->timestamp);
        $this->signed(self::TX, ['user_id' => $this->buyer->id, 'reference' => $quote['reference'], 'tx_hash' => $wrong])
            ->assertOk()
            ->assertJsonPath('data.result', 'rejected')
            ->assertJsonPath('data.reason', 'wrong_amount');

        // Not mined yet: remembered for the scanner, not bound
        $pending = self::txHash('mempool');
        $this->mineTransfer($pending, $payment->amount_wei, null, now()->timestamp);
        $this->signed(self::TX, ['user_id' => $this->buyer->id, 'reference' => $quote['reference'], 'tx_hash' => $pending])
            ->assertOk()
            ->assertJsonPath('data.result', 'unknown')
            ->assertJsonPath('data.reason', 'not_mined');
        $this->assertSame([$pending], $payment->fresh()->meta['submitted_hashes']);

        $hash = self::txHash('right');
        $this->mineTransfer($hash, $payment->amount_wei, 990, now()->timestamp);
        $this->signed(self::TX, ['user_id' => $this->buyer->id, 'reference' => $quote['reference'], 'tx_hash' => $hash])
            ->assertOk()
            ->assertJsonPath('data.result', 'paid')
            ->assertJsonPath('data.message', 'ชำระเงินสำเร็จ ขอบคุณครับ')
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.order_number', Order::sole()->order_number);
        $this->assertSame('paid', Order::sole()->payment_status);
    }

    // ── the discount on the normal checkout ──────────────────────────

    public function test_the_ai_credit_checkout_gives_tpix_the_discount_and_commission_is_on_what_is_paid(): void
    {
        $affiliate = $this->affiliate();

        $this->actingAs($this->buyer)->withSession(['affiliate_ref' => $affiliate->referral_code])
            ->post(route('xdreamer.checkout.process', 'weaver'), $this->checkoutForm('tpix'))
            ->assertRedirect(route('tpix-pay.show', TpixPayment::sole()));

        $order = Order::sole();
        $this->assertSame(['490.00', '98.00', '392.00'], [TpixMath::thb($order->subtotal), TpixMath::thb($order->discount), TpixMath::thb($order->total)]);
        $this->assertSame('392.00', TpixMath::thb(TpixPayment::sole()->amount_thb));
        $this->assertSame(20, AiCreditDelivery::metadata($order)['tpix_discount_percent']);
        $this->assertSame($affiliate->id, (int) $order->affiliate_id);

        // 10% of the ฿392 actually paid, not of the ฿490 list price
        $commission = AffiliateCommission::sole();
        $this->assertSame('392.00', TpixMath::thb($commission->order_amount));
        $this->assertSame('39.00', TpixMath::thb($commission->commission_amount));
        $this->assertSame($order->id, (int) $commission->order_id);
    }

    public function test_promptpay_on_the_ai_credit_checkout_pays_the_full_price(): void
    {
        $affiliate = $this->affiliate();

        $this->actingAs($this->buyer)->withSession(['affiliate_ref' => $affiliate->referral_code])
            ->post(route('xdreamer.checkout.process', 'weaver'), $this->checkoutForm('promptpay'))
            ->assertRedirect(route('xdreamer.checkout.payment', Order::sole()->id));

        $order = Order::sole();
        $this->assertSame(['490.00', '0.00', '490.00'], [TpixMath::thb($order->subtotal), TpixMath::thb($order->discount), TpixMath::thb($order->total)]);
        $this->assertArrayNotHasKey('tpix_discount_percent', AiCreditDelivery::metadata($order));
        $this->assertSame('490.00', TpixMath::thb($order->items()->sole()->price));
        $this->assertSame('49.00', TpixMath::thb(AffiliateCommission::sole()->commission_amount));
        $this->assertSame(0, TpixPayment::count());
    }

    public function test_the_checkout_page_shows_the_tpix_discount_and_the_discounted_total(): void
    {
        $this->actingAs($this->buyer)->get(route('xdreamer.checkout.show', 'weaver'))
            ->assertOk()
            ->assertSee('value="tpix"', false)
            ->assertSee('ลด 20%')
            ->assertSee('ชำระเพียง ฿392')
            ->assertSee('data-tpix="392"', false);

        // No discount set: the option says nothing about one
        app(TpixPaySettings::class)->save(['enabled' => true, 'address' => $this->shopAddress, 'confirmations' => 3, 'ttl' => 30, 'ai_credit_discount_percent' => 0]);
        $this->actingAs($this->buyer)->get(route('xdreamer.checkout.show', 'weaver'))
            ->assertOk()
            ->assertSee('value="tpix"', false)
            ->assertDontSee('ลด 20%')
            ->assertDontSee('ชำระเพียง');
    }

    public function test_the_discount_is_exact_decimal_maths(): void
    {
        app(TpixPaySettings::class)->save(['enabled' => true, 'address' => $this->shopAddress, 'confirmations' => 3, 'ttl' => 30, 'ai_credit_discount_percent' => 15]);
        $orders = app(AiCreditOrders::class);

        $this->assertSame(
            ['price_thb' => '299.00', 'discount_percent' => 15, 'discount_thb' => '44.85', 'total_thb' => '254.15'],
            $orders->pricing(['price_thb' => 299], 'tpix'),
        );
        $this->assertSame(
            ['price_thb' => '299.00', 'discount_percent' => 0, 'discount_thb' => '0.00', 'total_thb' => '299.00'],
            $orders->pricing(['price_thb' => 299], 'promptpay'),
        );
    }

    // ── settings and the public price API ────────────────────────────

    public function test_the_admin_sets_the_discount_between_0_and_50(): void
    {
        $admin = User::where('role', 'admin')->first();
        $form = ['tpix_enabled' => '1', 'tpix_show_prices' => '1', 'tpix_receive_address' => $this->shopAddress, 'tpix_confirmations' => 3, 'tpix_quote_ttl_minutes' => 30, 'tpix_max_pool_ratio' => 5];

        $this->assertSame(20, app(TpixPaySettings::class)->aiCreditDiscountPercent(), 'config default');
        $this->actingAs($admin)->get(route('admin.tpix-payments.index'))
            ->assertOk()
            ->assertSee('ส่วนลดแพ็กเกจ AI Credits เมื่อชำระด้วย TPIX (%)')
            ->assertSee('name="tpix_ai_credit_discount_percent"', false);

        $this->actingAs($admin)->put(route('admin.tpix-payments.settings.update'), $form + ['tpix_ai_credit_discount_percent' => 35])
            ->assertSessionHasNoErrors();
        $this->assertSame(35, app(TpixPaySettings::class)->aiCreditDiscountPercent());

        foreach ([51, -1, 'abc', 12.5] as $bad) {
            $this->actingAs($admin)->put(route('admin.tpix-payments.settings.update'), $form + ['tpix_ai_credit_discount_percent' => $bad])
                ->assertSessionHasErrors('tpix_ai_credit_discount_percent');
        }
        $this->assertSame(35, app(TpixPaySettings::class)->aiCreditDiscountPercent());

        // A form without the field (an older admin page open in another tab) keeps it
        $this->actingAs($admin)->put(route('admin.tpix-payments.settings.update'), $form)->assertSessionHasNoErrors();
        $this->assertSame(35, app(TpixPaySettings::class)->aiCreditDiscountPercent());

        $this->actingAs($admin)->put(route('admin.tpix-payments.settings.update'), $form + ['tpix_ai_credit_discount_percent' => 0])
            ->assertSessionHasNoErrors();
        $this->assertSame(0, app(TpixPaySettings::class)->aiCreditDiscountPercent());
    }

    public function test_the_price_api_says_the_discount_in_both_answers(): void
    {
        $this->getJson('/api/v1/tpix/price')
            ->assertOk()
            ->assertJsonPath('data.ai_credit_discount_percent', 20);

        app(TpixPaySettings::class)->save(['enabled' => false, 'address' => '', 'confirmations' => 3, 'ttl' => 30, 'ai_credit_discount_percent' => 35]);
        $this->getJson('/api/v1/tpix/price')
            ->assertOk()
            ->assertJsonPath('data.payments_enabled', false)
            ->assertJsonPath('data.ai_credit_discount_percent', 35);

        // No price at all: the 503 shape still carries it
        $this->fxDown = true;
        Cache::flush();
        TpixPriceSnapshot::query()->delete();
        $this->app->forgetScopedInstances();
        $this->getJson('/api/v1/tpix/price')
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.ai_credit_discount_percent', 35);
    }

    // ── helpers ──────────────────────────────────────────────────────

    /**
     * POST a body to the API signed as aixman signs it. $path is what is signed (defaults to $uri).
     *
     * @param  array<string, mixed>  $body
     */
    private function signed(string $uri, array $body, ?string $secret = null, ?string $path = null): TestResponse
    {
        $raw = json_encode($body);
        $ts = now()->getTimestamp();

        return $this->call('POST', $uri, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_AIXMAN_TIMESTAMP' => (string) $ts,
            'HTTP_X_AIXMAN_SIGNATURE' => VerifyAixmanSignature::sign($secret ?? self::SECRET, $ts, 'POST', $path ?? $uri, $raw),
        ], $raw);
    }

    /** @return array<string, mixed> the orders call's data */
    private function order(string $slug = 'weaver'): array
    {
        return $this->signed(self::ORDERS, ['user_id' => $this->buyer->id, 'package_slug' => $slug])->assertOk()->json('data');
    }

    /** @return list<ClientRequest> */
    private function webhookCalls(): array
    {
        return Http::recorded()->map(fn ($pair) => $pair[0])
            ->filter(fn (ClientRequest $r) => $r->url() === self::WEBHOOK)->values()->all();
    }

    private function affiliate(): Affiliate
    {
        return Affiliate::create([
            'user_id' => User::factory()->create()->id,
            'referral_code' => strtoupper(Str::random(8)),
            'commission_rate' => 10,
            'status' => 'active',
        ]);
    }

    /** @return array<string, string> */
    private function checkoutForm(string $method): array
    {
        return [
            'customer_name' => 'สมชาย ใจดี',
            'customer_email' => 'buyer@example.test',
            'customer_phone' => '0812345678',
            'payment_method' => $method,
        ];
    }
}
