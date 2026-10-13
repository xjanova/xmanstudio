<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\TpixPayment;
use App\Models\TpixPriceSnapshot;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTopup;
use App\Services\TpixPay\TpixMath;
use App\Services\TpixPay\TpixPaymentService;
use App\Services\TpixPay\TpixPriceService;
use App\Services\TpixPay\TpixQuoteException;
use Brick\Math\BigInteger;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\FakesTpixChain;
use Tests\TestCase;

/**
 * Paying with TPIX (owner, 2026-10-13: "ชำระเหรียญได้จริง ตามราคาปัจจุบันจริงๆ").
 *
 * The rules pinned here are the money rules: an exact amount per quote, the chain decides (address,
 * chain, exact wei, success, confirmations, time window), one transfer pays one quote, a late
 * transfer waits for the admin, and money for an order that no longer wants it goes to the wallet.
 */
class TpixPaymentTest extends TestCase
{
    use FakesTpixChain;
    use RefreshDatabase;

    private Product $product;

    private int $orders = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->fakeTpixChain();
        $this->enableTpixPayments();

        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Some Desktop App',
            'slug' => 'some-desktop-app',
            'description' => 'x',
            'price' => 1000,
            'stock' => 0,
            'requires_license' => true,
            'is_active' => true,
        ]);
    }

    // ── price and quote ─────────────────────────────────────────────

    public function test_the_price_comes_from_the_dex_pool_times_the_live_fx_rate(): void
    {
        $spot = app(TpixPriceService::class)->spot();

        $this->assertSame('0.100000000000', $spot['price_usd']);
        $this->assertSame('32.500000', $spot['usd_thb']);
        $this->assertSame('3.250000000000', $spot['price_thb']);
        $this->assertSame(1, TpixPriceSnapshot::count());

        // Cloudflare refuses the RPC without a User-Agent
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'rpc.tpix.online')
            && str_contains($r->header('User-Agent')[0] ?? '', 'XmanStudio-TpixPay'));
    }

    public function test_reserves_are_swapped_when_the_pair_lists_usdt_first(): void
    {
        $this->token0 = config('tpix_pay.usdt_address');
        [$this->reserveTpixWei, $this->reserveUsdtUnits] = ['19990000', '199900000000000000000'];

        $this->assertSame('0.100000000000', app(TpixPriceService::class)->spot()['price_usd']);
    }

    public function test_a_quote_is_the_thb_amount_at_the_live_price_rounded_up_plus_a_unique_tag(): void
    {
        $order = $this->order();
        $payment = app(TpixPaymentService::class)->createQuote(TpixPayment::PURPOSE_ORDER, $order, $order->user, '1000.00');

        // ฿1000 / ฿3.25 = 307.692307… → 307.6924 rounded up, then + n × 0.00000001 (n = 1..9999)
        $amount = TpixMath::dec($payment->amount_tpix);
        $this->assertTrue($amount->isGreaterThan('307.6924'));
        $this->assertTrue($amount->isLessThan('307.6925'));
        $this->assertSame(TpixMath::toWei($payment->amount_tpix), $payment->amount_wei);
        $this->assertSame(strtolower($this->shopAddress) . ':' . $payment->amount_wei, $payment->open_key);
        $this->assertSame('3.250000000000', (string) TpixMath::dec($payment->rate_thb)->toScale(12));
        $this->assertSame(TpixPayment::STATUS_PENDING, $payment->status);
        $this->assertTrue($payment->expires_at->isFuture());
    }

    public function test_a_quote_uses_the_recent_average_when_it_is_lower_than_a_pumped_spot(): void
    {
        foreach ([10, 20, 25] as $minutesAgo) {
            TpixPriceSnapshot::create(['price_usd' => '0.08', 'usd_thb' => '31.25', 'price_thb' => '2.5', 'created_at' => now()->subMinutes($minutesAgo)]);
        }

        $order = $this->order();
        $payment = app(TpixPaymentService::class)->createQuote(TpixPayment::PURPOSE_ORDER, $order, $order->user, '1000.00');

        // average of 2.5, 2.5, 2.5 and the new 3.25 snapshot = 2.6875 < 3.25 spot
        $this->assertSame('2.687500000000', (string) TpixMath::dec($payment->rate_thb)->toScale(12));
        $this->assertTrue(TpixMath::dec($payment->amount_tpix)->isGreaterThan('372.0930'));
    }

    public function test_no_quote_and_no_price_tag_without_a_usd_thb_rate(): void
    {
        $this->fxDown = true;

        $this->assertNull(app(TpixPriceService::class)->forDisplay(1000));

        $order = $this->order();
        $this->expectException(TpixQuoteException::class);
        app(TpixPaymentService::class)->createQuote(TpixPayment::PURPOSE_ORDER, $order, $order->user, '1000.00');
    }

    public function test_a_rate_fetched_within_a_day_still_quotes_when_the_feeds_are_down_but_an_older_one_does_not(): void
    {
        $this->fxDown = true;
        TpixPriceSnapshot::create(['price_usd' => '0.1', 'usd_thb' => '33.000000', 'price_thb' => '3.3', 'created_at' => now()->subHours(23)]);

        $this->assertSame('33.000000', app(TpixPriceService::class)->spot(fresh: true)['usd_thb']);

        TpixPriceSnapshot::query()->update(['created_at' => now()->subHours(25)]);
        Cache::flush();

        $this->assertNull(app(TpixPriceService::class)->spot(fresh: true));
    }

    public function test_a_pool_too_thin_to_price_gives_no_quote(): void
    {
        $this->reserveUsdtUnits = '4000000'; // 4 USDT < min_pool_usd 5

        $this->assertNull(app(TpixPriceService::class)->spot());
        $this->assertNull(app(TpixPriceService::class)->forDisplay(1000));
    }

    public function test_no_quote_while_tpix_is_switched_off(): void
    {
        $this->enableTpixPayments(enabled: false);

        $order = $this->order();
        $this->expectException(TpixQuoteException::class);
        app(TpixPaymentService::class)->createQuote(TpixPayment::PURPOSE_ORDER, $order, $order->user, '1000.00');
    }

    public function test_two_open_quotes_can_never_ask_for_the_same_amount(): void
    {
        $order = $this->order();
        $payment = app(TpixPaymentService::class)->createQuote(TpixPayment::PURPOSE_ORDER, $order, $order->user, '1000.00');

        $this->expectException(UniqueConstraintViolationException::class);
        TpixPayment::create($payment->only(['user_id', 'purpose', 'amount_thb', 'rate_thb', 'amount_wei', 'amount_tpix', 'receive_address', 'status', 'open_key', 'expires_at'])
            + ['reference' => 'TPXDUPLICATE']);
    }

    public function test_an_expired_quote_keeps_its_amount_reserved_until_the_grace_ends(): void
    {
        $order = $this->order();
        $service = app(TpixPaymentService::class);
        $payment = $service->createQuote(TpixPayment::PURPOSE_ORDER, $order, $order->user, '1000.00');

        $payment->update(['expires_at' => now()->subMinute()]);
        $service->expireStale();
        $this->assertSame(TpixPayment::STATUS_EXPIRED, $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->open_key);

        $payment->update(['expires_at' => now()->subMinutes(config('tpix_pay.late_grace_minutes') + 1)]);
        $service->expireStale();
        $this->assertNull($payment->fresh()->open_key);
    }

    public function test_products_priced_in_tpix_are_quoted_at_that_price_with_vat_in_proportion(): void
    {
        $this->product->update(['price_tpix' => '100']);
        $order = $this->order(subtotal: 1000, total: 1070); // + 7% VAT

        $fixed = app(TpixPriceService::class)->fixedTotalForOrder($order);
        $this->assertSame('107.0000', $fixed);

        $payment = app(TpixPaymentService::class)->createQuote(TpixPayment::PURPOSE_ORDER, $order, $order->user, '1070.00', $fixed);
        $this->assertTrue(TpixMath::dec($payment->amount_tpix)->isGreaterThan('107'));
        $this->assertTrue(TpixMath::dec($payment->amount_tpix)->isLessThan('107.0001'));
        $this->assertTrue($payment->meta['fixed_price']);
    }

    public function test_a_line_sold_at_another_price_than_the_tpix_price_converts_at_market(): void
    {
        $this->product->update(['price_tpix' => '100']);
        $order = $this->order(itemPrice: 1990); // e.g. a lifetime term, not the row's price

        $this->assertNull(app(TpixPriceService::class)->fixedTotalForOrder($order));
    }

    // ── verifying a transfer ────────────────────────────────────────

    public function test_a_matching_confirmed_transfer_pays_the_order_through_the_normal_approval(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $hash = self::txHash('happy');
        $this->mineTransfer($hash, $payment->amount_wei, 990, now()->timestamp);

        $outcome = app(TpixPaymentService::class)->submitTx($payment, $hash);

        $this->assertSame('paid', $outcome['result']);
        $payment->refresh();
        $this->assertSame(TpixPayment::STATUS_PAID, $payment->status);
        $this->assertSame($hash, $payment->tx_hash);
        $this->assertSame(11, $payment->confirmations);
        $this->assertNull($payment->open_key);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('completed', $order->status);
        $this->assertSame(1, LicenseKey::where('order_id', $order->id)->count());
        $this->assertStringContainsString($hash, $order->notes);
    }

    public function test_a_transfer_to_another_address_is_refused(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $hash = self::txHash('elsewhere');
        $this->mineTransfer($hash, $payment->amount_wei, 990, now()->timestamp, ['to' => '0x000000000000000000000000000000000000dead']);

        $this->assertSame('wrong_to', app(TpixPaymentService::class)->submitTx($payment, $hash)['reason']);
        $this->assertUnpaid($order, $payment);
    }

    public function test_a_transfer_of_a_different_amount_is_refused_even_by_one_wei(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $hash = self::txHash('short');
        $this->mineTransfer($hash, (string) BigInteger::of($payment->amount_wei)->minus(1), 990, now()->timestamp);

        $this->assertSame('wrong_amount', app(TpixPaymentService::class)->submitTx($payment, $hash)['reason']);
        $this->assertUnpaid($order, $payment);
        $this->assertStringContainsString('wrong amount', $payment->fresh()->failure_reason);
    }

    public function test_a_transfer_signed_for_another_chain_is_refused(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $hash = self::txHash('chain');
        $this->mineTransfer($hash, $payment->amount_wei, 990, now()->timestamp, ['chainId' => '0x1']);

        $this->assertSame('wrong_chain', app(TpixPaymentService::class)->submitTx($payment, $hash)['reason']);

        // and the same read from an EIP-155 v: chain 1 → v = 37
        $hash2 = self::txHash('chain-v');
        $this->mineTransfer($hash2, $payment->amount_wei, 990, now()->timestamp, ['chainId' => null, 'v' => '0x25']);
        $this->assertSame('wrong_chain', app(TpixPaymentService::class)->submitTx($payment, $hash2)['reason']);
        $this->assertUnpaid($order, $payment);
    }

    public function test_a_failed_transaction_pays_nothing(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $hash = self::txHash('reverted');
        $this->mineTransfer($hash, $payment->amount_wei, 990, now()->timestamp, [], '0x0');

        $this->assertSame('reverted', app(TpixPaymentService::class)->submitTx($payment, $hash)['reason']);
        $this->assertUnpaid($order, $payment);
    }

    public function test_one_transfer_cannot_pay_two_quotes(): void
    {
        [, $first] = $this->quotedOrder();
        $hash = self::txHash('once');
        $this->mineTransfer($hash, $first->amount_wei, 990, now()->timestamp);
        app(TpixPaymentService::class)->submitTx($first, $hash);

        [$second, $secondPayment] = $this->quotedOrder();
        $outcome = app(TpixPaymentService::class)->submitTx($secondPayment, $hash);

        $this->assertSame('tx_used', $outcome['reason']);
        $this->assertUnpaid($second, $secondPayment);
    }

    public function test_too_few_confirmations_waits_then_the_scanner_settles(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $hash = self::txHash('young');
        $this->latestBlock = 991;
        $this->mineTransfer($hash, $payment->amount_wei, 990, now()->timestamp);

        $this->assertSame('confirming', app(TpixPaymentService::class)->submitTx($payment, $hash)['result']);
        $this->assertSame(TpixPayment::STATUS_CONFIRMING, $payment->fresh()->status);
        $this->assertSame(2, $payment->fresh()->confirmations);
        $this->assertSame('pending', $order->fresh()->payment_status);

        $this->latestBlock = 992;
        $this->artisan('tpix:pay-scan')->assertSuccessful();

        $this->assertSame(TpixPayment::STATUS_PAID, $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_a_transfer_after_the_quote_expired_is_held_for_the_admin_not_fulfilled(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $hash = self::txHash('late');
        $this->mineTransfer($hash, $payment->amount_wei, 990, $payment->expires_at->copy()->addMinutes(5)->timestamp);

        $this->assertSame('late', app(TpixPaymentService::class)->submitTx($payment, $hash)['result']);

        $payment->refresh();
        $this->assertSame(TpixPayment::STATUS_LATE, $payment->status);
        $this->assertSame($hash, $payment->tx_hash);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame(0, LicenseKey::where('order_id', $order->id)->count());

        // The admin accepts it
        $this->assertSame('paid', app(TpixPaymentService::class)->verify($payment, $hash, acceptLate: true)['result']);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_money_for_an_order_paid_another_way_goes_to_the_wallet(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $order->update(['payment_status' => 'paid', 'status' => 'completed', 'paid_at' => now()]);
        $hash = self::txHash('double');
        $this->mineTransfer($hash, $payment->amount_wei, 990, now()->timestamp);

        $this->assertSame('credited_wallet', app(TpixPaymentService::class)->submitTx($payment, $hash)['result']);

        $this->assertSame(TpixPayment::STATUS_CREDITED_WALLET, $payment->fresh()->status);
        $this->assertSame('1000.00', (string) Wallet::where('user_id', $order->user_id)->value('balance'));
    }

    public function test_money_for_a_cancelled_order_goes_to_the_wallet(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $order->update(['status' => 'cancelled', 'payment_status' => 'rejected']);
        $hash = self::txHash('cancelled');
        $this->mineTransfer($hash, $payment->amount_wei, 990, now()->timestamp);

        $this->assertSame('credited_wallet', app(TpixPaymentService::class)->submitTx($payment, $hash)['result']);
        $this->assertSame('1000.00', (string) Wallet::where('user_id', $order->user_id)->value('balance'));
        $this->assertSame('rejected', $order->fresh()->payment_status);
    }

    public function test_settling_twice_delivers_once(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $hash = self::txHash('twice');
        $this->mineTransfer($hash, $payment->amount_wei, 990, now()->timestamp);
        $service = app(TpixPaymentService::class);

        $this->assertSame('paid', $service->verify($payment, $hash)['result']);
        $this->assertSame('settled', $service->verify($payment, $hash)['result']);
        $this->artisan('tpix:pay-scan')->assertSuccessful();

        $this->assertSame(1, LicenseKey::where('order_id', $order->id)->count());
        $this->assertNull(Wallet::where('user_id', $order->user_id)->value('balance'));
    }

    public function test_the_chain_being_down_leaves_the_payment_pending(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $this->rpcDown = true;

        $outcome = app(TpixPaymentService::class)->submitTx($payment, self::txHash('later'));

        $this->assertSame('unknown', $outcome['result']);
        $this->assertSame(TpixPayment::STATUS_PENDING, $payment->fresh()->status);
        $this->assertContains(self::txHash('later'), $payment->fresh()->meta['candidate_txs']);

        // The node comes back, the transfer is there: the scanner picks the pasted hash up
        $this->rpcDown = false;
        $this->mineTransfer(self::txHash('later'), $payment->amount_wei, 990, now()->timestamp);
        $this->artisan('tpix:pay-scan')->assertSuccessful();

        $this->assertSame(TpixPayment::STATUS_PAID, $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_the_scanner_finds_a_transfer_nobody_pasted(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $hash = self::txHash('qr');
        $this->mineTransfer($hash, $payment->amount_wei, 990, now()->timestamp);
        $this->explorerItems = [[
            'hash' => $hash, 'value' => $payment->amount_wei, 'status' => 'ok', 'result' => 'success',
            'timestamp' => now()->toIso8601String(), 'to' => ['hash' => $this->shopAddress],
        ]];

        $this->artisan('tpix:pay-scan')->assertSuccessful();

        $this->assertSame(TpixPayment::STATUS_PAID, $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_a_wallet_top_up_paid_in_tpix_is_approved_with_its_bonus_rules(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::getOrCreateForUser($user->id);
        $topup = WalletTopup::create([
            'wallet_id' => $wallet->id, 'user_id' => $user->id, 'topup_id' => WalletTopup::generateTopupId(),
            'amount' => 500, 'bonus_amount' => 0, 'total_amount' => 500,
            'payment_method' => 'tpix', 'status' => WalletTopup::STATUS_PENDING,
        ]);
        $payment = app(TpixPaymentService::class)->createQuote(TpixPayment::PURPOSE_WALLET_TOPUP, $topup, $user, '500.00');
        $hash = self::txHash('topup');
        $this->mineTransfer($hash, $payment->amount_wei, 990, now()->timestamp);

        $this->assertSame('paid', app(TpixPaymentService::class)->submitTx($payment, $hash)['result']);

        $this->assertSame(WalletTopup::STATUS_APPROVED, $topup->fresh()->status);
        $this->assertSame('500.00', (string) $wallet->fresh()->balance);
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function quotedOrder(): array
    {
        $order = $this->order();
        $payment = app(TpixPaymentService::class)->createQuote(TpixPayment::PURPOSE_ORDER, $order, $order->user, (string) $order->total);

        return [$order, $payment];
    }

    private function order(float $subtotal = 1000, float $total = 1000, float $itemPrice = 1000): Order
    {
        $buyer = User::factory()->create();

        $order = Order::create([
            'order_number' => 'TPIX-' . (++$this->orders),
            'user_id' => $buyer->id,
            'customer_name' => $buyer->name,
            'customer_email' => $buyer->email,
            'subtotal' => $subtotal,
            'tax' => $total - $subtotal,
            'total' => $total,
            'payment_method' => 'tpix',
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'price' => $itemPrice,
            'quantity' => 1,
            'subtotal' => $itemPrice,
        ]);

        return $order;
    }

    private function assertUnpaid(Order $order, TpixPayment $payment): void
    {
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertNotContains($payment->fresh()->status, [TpixPayment::STATUS_PAID, TpixPayment::STATUS_CREDITED_WALLET]);
        $this->assertSame(0, LicenseKey::where('order_id', $order->id)->count());
    }
}
