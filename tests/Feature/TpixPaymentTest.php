<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentSetting;
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
        $this->seedTwap();

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
        $this->assertSame('3.250000000000', $spot['quote_rate_thb']);
        $this->assertSame(TpixPriceService::SOURCE_LIVE, TpixPriceSnapshot::latest('created_at')->value('source'));

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

    public function test_a_quote_uses_the_usd_twap_when_it_is_lower_than_a_pumped_spot(): void
    {
        TpixPriceSnapshot::query()->delete();
        $this->seedTwap('0.08');

        $order = $this->order();
        $payment = app(TpixPaymentService::class)->createQuote(TpixPayment::PURPOSE_ORDER, $order, $order->user, '1000.00');

        // TWAP of the USD price: (0.08 × 3 + the new 0.10) / 4 = 0.085 < 0.10 spot → × 32.5 = ฿2.7625
        $this->assertSame('2.762500000000', (string) TpixMath::dec($payment->rate_thb)->toScale(12));
        $this->assertTrue(TpixMath::dec($payment->amount_tpix)->isGreaterThan('361.9910'));
        $this->assertTrue(TpixMath::dec($payment->amount_tpix)->isLessThan('361.9911'));
    }

    public function test_no_quote_until_the_twap_exists(): void
    {
        TpixPriceSnapshot::query()->delete();
        $order = $this->order();

        $this->assertNull(app(TpixPriceService::class)->quoteRateThb());
        $this->assertNull(app(TpixPriceService::class)->forDisplay(1000));

        $this->expectExceptionMessage('ราคาเฉลี่ย');
        app(TpixPaymentService::class)->createQuote(TpixPayment::PURPOSE_ORDER, $order, $order->user, '1000.00');
    }

    public function test_an_amount_beyond_the_pool_is_refused_with_the_limit(): void
    {
        // 19.99 USDT × 5 × 32.5 = ฿3,248.37 per quote at most
        $order = $this->order(subtotal: 5000, total: 5000, itemPrice: 5000);

        $this->expectExceptionMessage('ยอดนี้เกินสภาพคล่องของตลาด TPIX ตอนนี้ — ชำระด้วย TPIX ได้สูงสุด 3,248.37 บาทต่อรายการ');
        app(TpixPaymentService::class)->createQuote(TpixPayment::PURPOSE_ORDER, $order, $order->user, '5000.00');
    }

    public function test_a_broken_token0_answer_gives_no_price(): void
    {
        $this->token0Raw = '0x';
        $this->assertNull(app(TpixPriceService::class)->spot(fresh: true));

        $this->token0Raw = '0x' . str_repeat('0', 64);
        $this->assertNull(app(TpixPriceService::class)->spot(fresh: true));
    }

    public function test_tests_never_reach_the_network_unless_they_fake_it(): void
    {
        config(['tpix_pay.allow_network_in_tests' => false]);
        Http::fake();

        $this->assertNull(app(TpixPriceService::class)->spot(fresh: true));
        Http::assertNothingSent();
    }

    public function test_pages_only_read_the_cache_and_one_request_refreshes_it(): void
    {
        Cache::flush();
        Cache::add('tpix_pay:spot:refreshing', 1, 15); // another request is reading the chain right now
        Http::fake();

        $this->assertNull(app(TpixPriceService::class)->forDisplay(1000));
        Http::assertNothingSent();
    }

    public function test_no_quote_and_no_price_tag_without_a_usd_thb_rate(): void
    {
        $this->fxDown = true;
        // Rows whose rate was itself a fallback never stand in for a live rate
        TpixPriceSnapshot::query()->update(['source' => TpixPriceService::SOURCE_FX_STALE]);

        $this->assertNull(app(TpixPriceService::class)->forDisplay(1000));

        $order = $this->order();
        $this->expectException(TpixQuoteException::class);
        app(TpixPaymentService::class)->createQuote(TpixPayment::PURPOSE_ORDER, $order, $order->user, '1000.00');
    }

    public function test_a_rate_fetched_within_a_day_still_quotes_when_the_feeds_are_down_but_an_older_one_does_not(): void
    {
        $this->fxDown = true;
        TpixPriceSnapshot::query()->update(['source' => TpixPriceService::SOURCE_FX_STALE]);
        $live = TpixPriceSnapshot::create(['price_usd' => '0.1', 'usd_thb' => '33.000000', 'price_thb' => '3.3', 'source' => TpixPriceService::SOURCE_LIVE, 'created_at' => now()->subHours(23)]);

        $spot = app(TpixPriceService::class)->spot(fresh: true);
        $this->assertSame('33.000000', $spot['usd_thb']);
        $this->assertFalse($spot['fx_live']);
        // This minute's read is recorded, marked as priced with a fallback rate
        $this->assertSame(TpixPriceService::SOURCE_FX_STALE, TpixPriceSnapshot::latest('created_at')->value('source'));

        $live->update(['created_at' => now()->subHours(25)]);
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
        $this->product->update(['price_tpix' => '400']); // above its market value (฿1000 ≈ 307.69 TPIX)
        $order = $this->order(subtotal: 1000, total: 1070); // + 7% VAT

        $fixed = app(TpixPriceService::class)->fixedTotalForOrder($order);
        $this->assertSame('428.0000', $fixed);

        $payment = app(TpixPaymentService::class)->createQuote(TpixPayment::PURPOSE_ORDER, $order, $order->user, '1070.00', $fixed);
        $this->assertTrue(TpixMath::dec($payment->amount_tpix)->isGreaterThan('428'));
        $this->assertTrue(TpixMath::dec($payment->amount_tpix)->isLessThan('428.0001'));
        $this->assertTrue($payment->meta['fixed_price']);
    }

    public function test_a_fixed_price_below_the_market_is_never_what_anyone_pays(): void
    {
        // Coins bought cheap at a stale fixed price, then the order cancelled → the full THB would
        // have come back to the wallet. The market value is the floor, line by line.
        $this->product->update(['price_tpix' => '100']);
        $order = $this->order(subtotal: 1000, total: 1070);

        $this->assertSame('329.2308', app(TpixPriceService::class)->fixedTotalForOrder($order)); // 1070 / 3.25

        $payment = app(TpixPaymentService::class)->createQuote(TpixPayment::PURPOSE_ORDER, $order, $order->user, '1070.00', '107');
        $this->assertTrue(TpixMath::dec($payment->amount_tpix)->isGreaterThan('329.2307'));
        $this->assertArrayNotHasKey('fixed_price', $payment->meta);

        // The price tag shows the market price, not the lower fixed one
        $tag = app(TpixPriceService::class)->forDisplay(1000, '100');
        $this->assertSame('307.6924', $tag['tpix']);
        $this->assertFalse($tag['fixed']);
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
        $this->assertContains(self::txHash('later'), $payment->fresh()->meta['submitted_hashes']);

        // The node comes back, the transfer is there: the scanner picks the pasted hash up
        $this->rpcDown = false;
        $this->mineTransfer(self::txHash('later'), $payment->amount_wei, 990, now()->timestamp);
        $this->artisan('tpix:pay-scan')->assertSuccessful();

        $this->assertSame(TpixPayment::STATUS_PAID, $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_the_scanner_finds_a_transfer_nobody_pasted_by_walking_the_blocks(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $hash = self::txHash('qr');

        // Zero-value spam to the shop costs nothing on a gas-0 chain — it must not bury the payment
        foreach (range(1, 60) as $i) {
            $this->mineTransfer(self::txHash("spam-{$i}"), '0', 950 + intdiv($i, 4), now()->timestamp);
        }
        $this->mineTransfer($hash, $payment->amount_wei, 990, now()->timestamp);

        $this->artisan('tpix:pay-scan')->assertSuccessful();

        $this->assertSame(TpixPayment::STATUS_PAID, $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(1000, TpixPaymentService::scanCursor());
        // Blocks were fetched in JSON-RPC batches of 40
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'rpc.tpix.online') && array_is_list($r->data()) && count($r->data()) === 40);
    }

    public function test_with_no_open_quote_the_scanner_only_follows_the_head(): void
    {
        $this->artisan('tpix:pay-scan')->assertSuccessful();

        $this->assertSame(1000, TpixPaymentService::scanCursor());
        Http::assertNotSent(fn (Request $r) => array_is_list($r->data()) && $r->data() !== []);
    }

    public function test_a_block_the_node_leaves_out_stops_the_walk_just_before_it(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $this->mineTransfer(self::txHash('after-gap'), $payment->amount_wei, 990, now()->timestamp);
        $this->missingBlocks = [500];

        $this->artisan('tpix:pay-scan')->assertSuccessful();
        $this->assertSame(499, TpixPaymentService::scanCursor());
        $this->assertSame(TpixPayment::STATUS_PENDING, $payment->fresh()->status);

        // The node has it again: the next run resumes at block 500 and finds the payment
        $this->missingBlocks = [];
        $this->artisan('tpix:pay-scan')->assertSuccessful();
        $this->assertSame(TpixPayment::STATUS_PAID, $payment->fresh()->status);
    }

    public function test_one_run_walks_at_most_900_blocks(): void
    {
        $this->quotedOrder();
        $this->latestBlock = 5000;
        PaymentSetting::set(TpixPaymentService::CURSOR_KEY, 1000, ['group' => 'tpix_scan', 'type' => 'integer', 'label' => 'x']);

        $this->artisan('tpix:pay-scan')->assertSuccessful();

        $this->assertSame(1900, TpixPaymentService::scanCursor());
    }

    public function test_one_bad_payment_cannot_stop_the_others_in_a_scan(): void
    {
        [$badOrder, $bad] = $this->quotedOrder();
        [$order, $good] = $this->quotedOrder();
        // Something about this row makes delivery throw
        $bad->forceFill(['payable_type' => 'App\\Models\\NoSuchModel'])->save();
        $this->mineTransfer(self::txHash('bad'), $bad->amount_wei, 990, now()->timestamp);
        $this->mineTransfer(self::txHash('good'), $good->amount_wei, 990, now()->timestamp);

        $this->artisan('tpix:pay-scan')->assertSuccessful();

        $this->assertSame(TpixPayment::STATUS_PAID, $good->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertNotContains($bad->fresh()->status, [TpixPayment::STATUS_PAID, TpixPayment::STATUS_CREDITED_WALLET]);
    }

    public function test_a_transaction_still_in_the_mempool_is_not_bound_and_a_replacement_can_pay(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $stuck = self::txHash('stuck');
        $this->mineTransfer($stuck, $payment->amount_wei, null, now()->timestamp);

        $outcome = app(TpixPaymentService::class)->submitTx($payment, $stuck);

        $this->assertSame('unknown', $outcome['result']);
        $this->assertSame('not_mined', $outcome['reason']);
        $payment->refresh();
        $this->assertNull($payment->tx_hash);
        $this->assertSame(TpixPayment::STATUS_PENDING, $payment->status);
        $this->assertContains($stuck, $payment->meta['submitted_hashes']);

        // The wallet sped it up with a new hash: that one pays, the stuck one never locked the quote
        $replacement = self::txHash('replacement');
        $this->mineTransfer($replacement, $payment->amount_wei, 990, now()->timestamp);

        $this->assertSame('paid', app(TpixPaymentService::class)->submitTx($payment, $replacement)['result']);
        $this->assertSame($replacement, $payment->fresh()->tx_hash);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_only_five_pasted_hashes_are_remembered(): void
    {
        [, $payment] = $this->quotedOrder();

        foreach (range(1, 7) as $i) {
            $this->mineTransfer(self::txHash("pending-{$i}"), $payment->amount_wei, null, now()->timestamp);
            app(TpixPaymentService::class)->submitTx($payment, self::txHash("pending-{$i}"));
        }

        $this->assertCount(5, $payment->fresh()->meta['submitted_hashes']);
    }

    public function test_a_wallet_credit_is_never_more_than_the_coins_are_worth_now(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $order->update(['status' => 'cancelled', 'payment_status' => 'rejected']);

        // The price halves before the coins arrive: the quote rate takes the lower live price,
        // 0.05 × 32.5 = ฿1.625, so the 307.69… TPIX are worth ฿500.00 now — not the quote's ฿1000
        $this->reserveUsdtUnits = '9995000';
        Cache::flush();
        $this->app->forgetScopedInstances();

        $hash = self::txHash('cheap-coins');
        $this->mineTransfer($hash, $payment->amount_wei, 990, now()->timestamp);
        $this->assertSame('credited_wallet', app(TpixPaymentService::class)->submitTx($payment, $hash)['result']);

        $this->assertSame('500.00', (string) Wallet::where('user_id', $order->user_id)->value('balance'));
        $this->assertSame('500.00', TpixMath::thb($payment->fresh()->credited_thb));
    }

    public function test_without_a_price_to_value_the_coins_the_credit_waits_for_the_admin(): void
    {
        [$order, $payment] = $this->quotedOrder();
        $order->update(['status' => 'cancelled', 'payment_status' => 'rejected']);
        $this->fxDown = true;
        TpixPriceSnapshot::query()->delete();
        Cache::flush();
        $this->app->forgetScopedInstances();

        $hash = self::txHash('no-price');
        $this->mineTransfer($hash, $payment->amount_wei, 990, now()->timestamp);
        $this->assertSame('held', app(TpixPaymentService::class)->submitTx($payment, $hash)['result']);
        $this->assertSame(TpixPayment::STATUS_HELD, $payment->fresh()->status);
        $this->assertNull(Wallet::where('user_id', $order->user_id)->value('balance'));

        // Prices are back: the admin credits it (the coins' value now, capped at the quote's THB)
        $this->fxDown = false;
        $this->seedTwap();
        Cache::flush();
        $this->app->forgetScopedInstances();

        $this->assertSame('credited_wallet', app(TpixPaymentService::class)->approveHeld($payment)['result']);
        $this->assertSame('1000.00', (string) Wallet::where('user_id', $order->user_id)->value('balance'));
    }

    public function test_over_the_daily_auto_credit_cap_the_coins_wait_for_the_admin(): void
    {
        config(['tpix_pay.auto_credit_daily_cap_thb' => 500]);
        [$order, $payment] = $this->quotedOrder();
        $order->update(['status' => 'cancelled', 'payment_status' => 'rejected']);

        $hash = self::txHash('over-cap');
        $this->mineTransfer($hash, $payment->amount_wei, 990, now()->timestamp);

        $this->assertSame('held', app(TpixPaymentService::class)->submitTx($payment, $hash)['result']);
        $this->assertNull(Wallet::where('user_id', $order->user_id)->value('balance'));
        $this->assertStringContainsString('daily', $payment->fresh()->failure_reason);
    }

    public function test_a_second_quote_paid_on_one_top_up_is_credited_at_most_its_value(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::getOrCreateForUser($user->id);
        $topup = WalletTopup::create([
            'wallet_id' => $wallet->id, 'user_id' => $user->id, 'topup_id' => WalletTopup::generateTopupId(),
            'amount' => 500, 'bonus_amount' => 0, 'total_amount' => 500,
            'payment_method' => 'tpix', 'status' => WalletTopup::STATUS_PENDING,
        ]);
        $service = app(TpixPaymentService::class);
        $first = $service->createQuote(TpixPayment::PURPOSE_WALLET_TOPUP, $topup, $user, '500.00');
        $second = $service->createQuote(TpixPayment::PURPOSE_WALLET_TOPUP, $topup, $user, '500.00');

        $this->mineTransfer(self::txHash('first'), $first->amount_wei, 990, now()->timestamp);
        $this->mineTransfer(self::txHash('second'), $second->amount_wei, 991, now()->timestamp);

        $this->assertSame('paid', $service->submitTx($first, self::txHash('first'))['result']);
        $this->assertSame('credited_wallet', $service->submitTx($second, self::txHash('second'))['result']);

        // ฿500 from the top-up + the second quote's coins at today's rate (≥ ฿500.00, capped at ฿500)
        $this->assertSame('1000.00', (string) $wallet->fresh()->balance);
        $this->assertSame('500.00', TpixMath::thb($second->fresh()->credited_thb));
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
