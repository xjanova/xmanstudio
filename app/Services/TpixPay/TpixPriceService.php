<?php

namespace App\Services\TpixPay;

use App\Models\Order;
use App\Models\TpixPriceSnapshot;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The real, current price of TPIX — read from the chain's own DEX pool (WTPIX/USDT), times the
 * live USD→THB rate. No price is ever typed in.
 *
 * The pool is thin (about $20 deep: a few USDT doubles the price and can be held for minutes), so:
 * - a quote uses the LOWER of the live pool price and its 30-minute average (TWAP, on the USD price),
 *   and there is no quote at all until that average exists (≥ 3 one-minute snapshots);
 * - one quote may be worth at most max_pool_ratio × the pool's USDT side.
 *
 * Reading:
 * - spot(): the cached price. Pages never wait on the chain — the scanner refreshes the cache every
 *   minute; when it is empty, ONE request refreshes it (a cache lock) and the others show no tag.
 * - spotForQuote(): the cached price when it is at most 30 s old, otherwise one fresh read.
 * - forDisplay(): the price tag. Never throws; null whenever there is no trustworthy price.
 *
 * Bound as a scoped singleton (AppServiceProvider) so the per-request memo is per request.
 */
class TpixPriceService
{
    public const SPOT_CACHE_KEY = 'tpix_pay:spot';

    public const FX_CACHE_KEY = 'tpix_pay:fx';

    private const REFRESH_LOCK = 'tpix_pay:spot:refreshing';

    private const GET_RESERVES = '0x0902f1ac';

    private const TOKEN0 = '0x0dfe1681';

    /** Snapshot sources: FX read live / FX was a fallback / no FX at all (USD price only, for the TWAP) */
    public const SOURCE_LIVE = 'dex';

    public const SOURCE_FX_STALE = 'dex_fx_stale';

    public const SOURCE_NO_FX = 'dex_no_fx';

    private bool $loaded = false;

    /** @var array<string, mixed>|null */
    private ?array $memo = null;

    public function __construct(
        private TpixRpcClient $rpc,
        private TpixPaySettings $settings,
    ) {}

    /**
     * @return array{price_usd:string, twap_usd:?string, usd_thb:string, fx_live:bool, price_thb:string,
     *               quote_rate_thb:?string, max_quote_thb:string, reserve_tpix:string, reserve_usdt:string,
     *               source:string, at:int}|null
     */
    public function spot(bool $fresh = false): ?array
    {
        if (! $fresh && $this->loaded) {
            return $this->memo;
        }

        if (! $fresh) {
            $cached = Cache::get(self::SPOT_CACHE_KEY);
            if (is_array($cached) && array_key_exists('spot', $cached)) {
                $this->loaded = true;

                return $this->memo = $cached['spot'];
            }

            // Nothing cached: one request reads the chain, every other one shows no price meanwhile
            if (! Cache::add(self::REFRESH_LOCK, 1, 15)) {
                $this->loaded = true;

                return $this->memo = null;
            }
        }

        try {
            $spot = $this->readSpot();
        } catch (Throwable $e) {
            Log::warning('TPIX price unavailable', ['error' => $e->getMessage()]);
            $spot = null;
        }

        // A failure is remembered briefly too, so a dead node is not asked once per page view
        Cache::put(self::SPOT_CACHE_KEY, ['spot' => $spot], $spot !== null
            ? max(70, (int) config('tpix_pay.display_cache_seconds', 90))
            : 15);

        if (! $fresh) {
            Cache::forget(self::REFRESH_LOCK);
        }

        $this->loaded = true;

        return $this->memo = $spot;
    }

    /** The price a new quote uses: the cached read when it is fresh enough, else one read now. */
    public function spotForQuote(): ?array
    {
        $spot = $this->spot();
        $maxAge = (int) config('tpix_pay.quote_spot_max_age_seconds', 30);

        if ($spot !== null && $spot['at'] >= now()->timestamp - $maxAge) {
            return $spot;
        }

        return $this->spot(fresh: true);
    }

    /** THB per 1 TPIX a new quote would use right now, or null (no price, or no TWAP yet). */
    public function quoteRateThb(): ?string
    {
        return $this->spot()['quote_rate_thb'] ?? null;
    }

    /** How many TPIX a THB amount costs at $rateThb: rounded UP to 4 decimals, so the shop is never short. */
    public function thbToTpix(string|int|float $amountThb, string $rateThb): string
    {
        $rate = TpixMath::dec($rateThb);
        if ($rate->isLessThanOrEqualTo(0)) {
            throw new \InvalidArgumentException('rate must be positive');
        }

        return (string) TpixMath::dec(TpixMath::thb($amountThb))->dividedBy($rate, 4, RoundingMode::Ceiling);
    }

    /**
     * The price tag: how much $thb is in TPIX right now. A product's fixed TPIX price is shown only
     * when it is not below that (a fixed price under the market value is never what anyone pays).
     * null = show nothing (feature off, no price, nothing to price). Never throws.
     *
     * @return array{tpix:string, tpix_formatted:string, fixed:bool, rate_thb:string,
     *               rate_formatted:string, price_usd:?string, updated_at:?Carbon}|null
     */
    public function forDisplay(string|int|float|null $thb, string|int|float|null $fixedTpix = null): ?array
    {
        try {
            if (! $this->settings->showsPrices() || $thb === null || $thb === '' || ! TpixMath::dec($thb)->isPositive()) {
                return null;
            }

            $spot = $this->spot();
            $rate = $spot['quote_rate_thb'] ?? null;
            if ($rate === null) {
                return null;
            }

            $tpix = $this->thbToTpix($thb, $rate);
            $fixed = false;
            if ($fixedTpix !== null && $fixedTpix !== '' && TpixMath::dec($fixedTpix)->isPositive()) {
                $fixedValue = TpixMath::dec($fixedTpix)->toScale(4, RoundingMode::Ceiling);
                if ($fixedValue->isGreaterThanOrEqualTo($tpix)) {
                    $tpix = (string) $fixedValue;
                    $fixed = true;
                }
            }

            return [
                'tpix' => $tpix,
                'tpix_formatted' => TpixMath::formatTpix($tpix),
                'fixed' => $fixed,
                'rate_thb' => $rate,
                'rate_formatted' => self::formatRate($rate),
                'price_usd' => $spot['price_usd'] ?? null,
                'updated_at' => isset($spot['at']) ? Carbon::createFromTimestamp($spot['at']) : null,
            ];
        } catch (Throwable $e) {
            Log::debug('TPIX price tag skipped', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * The order's total in TPIX when every line is a product the admin priced in TPIX, at the price
     * that TPIX price was set against (a licence term priced differently converts at market).
     */
    public function fixedTotalForOrder(Order $order, ?string $rateThb = null): ?string
    {
        $order->loadMissing('items.product');

        return $this->fixedTotalForLines($order->items, $order->subtotal ?? 0, $order->total ?? 0, $rateThb);
    }

    /**
     * The same for any lines with ->product, ->price and ->quantity (order items, cart items).
     *
     * Each line is the HIGHER of its fixed TPIX price and its THB value at the quote rate — VAT and a
     * coupon move both in the same proportion as they move the THB total. A fixed price below the
     * market would let coins bought cheap pay for an order whose cancellation then credits the full
     * THB to the wallet. No rate → no fixed total (it could not be checked against the market).
     *
     * @param  iterable<object>  $lines
     */
    public function fixedTotalForLines(iterable $lines, string|int|float $subtotal, string|int|float $total, ?string $rateThb = null): ?string
    {
        $rateThb ??= $this->quoteRateThb();
        if ($rateThb === null || ! TpixMath::dec(TpixMath::thb($subtotal))->isPositive()) {
            return null;
        }

        $rate = TpixMath::dec($rateThb);
        $ratio = TpixMath::dec(TpixMath::thb($total))->dividedBy(TpixMath::thb($subtotal), 18, RoundingMode::HalfUp);
        $sum = BigDecimal::zero();
        $any = false;

        foreach ($lines as $line) {
            $product = $line->product;
            if (! $product || $product->price_tpix === null || ! TpixMath::dec($product->price_tpix)->isPositive()) {
                return null;
            }
            if (! TpixMath::dec(TpixMath::thb($line->price))->isEqualTo(TpixMath::dec(TpixMath::thb($product->price)))) {
                return null;
            }

            $quantity = (int) $line->quantity;
            $fixedLine = TpixMath::dec($product->price_tpix)->multipliedBy($quantity)->multipliedBy($ratio);
            $marketLine = TpixMath::dec(TpixMath::thb($line->price))->multipliedBy($quantity)->multipliedBy($ratio)
                ->dividedBy($rate, 18, RoundingMode::Ceiling);
            $sum = $sum->plus($fixedLine->isGreaterThan($marketLine) ? $fixedLine : $marketLine);
            $any = true;
        }

        return $any ? (string) $sum->toScale(4, RoundingMode::Ceiling) : null;
    }

    /** The TPIX price for an API payload: a 4-decimal string, or null when the pages would show none. */
    public function apiPrice(string|int|float|null $thb, string|int|float|null $fixedTpix = null): ?string
    {
        return $this->forDisplay($thb, $fixedTpix)['tpix'] ?? null;
    }

    /**
     * The rate block an API payload carries next to its TPIX prices, or null while none are shown.
     *
     * @return array{symbol:string, chain_id:int, price_thb:string, quote_rate_thb:string, price_usd:string, updated_at:string}|null
     */
    public function apiRate(): ?array
    {
        $spot = $this->settings->showsPrices() ? $this->spot() : null;
        if ($spot === null || ($spot['quote_rate_thb'] ?? null) === null) {
            return null;
        }

        return [
            'symbol' => (string) config('tpix_pay.symbol'),
            'chain_id' => (int) config('tpix_pay.chain_id'),
            'price_thb' => $spot['price_thb'],
            'quote_rate_thb' => $spot['quote_rate_thb'],
            'price_usd' => $spot['price_usd'],
            'updated_at' => Carbon::createFromTimestamp($spot['at'])->toIso8601String(),
        ];
    }

    public static function formatRate(string $rateThb): string
    {
        $rate = TpixMath::dec($rateThb);

        return TpixMath::format($rate, $rate->compareTo(10) >= 0 ? 2 : 4);
    }

    /** Tests never reach the real chain or FX feeds unless they fake them and opt in. */
    private function networkAllowed(): bool
    {
        return ! app()->runningUnitTests() || (bool) config('tpix_pay.allow_network_in_tests');
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws TpixRpcException
     */
    private function readSpot(): ?array
    {
        if (! $this->networkAllowed()) {
            return null;
        }

        $pair = config('tpix_pay.pair_address');

        // token0 must be one of the pair's two tokens, or the reserves would be read upside down
        // (a bare "0x" answer would flip them and the price would explode)
        $token0Raw = $this->rpc->ethCall($pair, self::TOKEN0);
        $token0 = strlen($token0Raw) >= 42 ? '0x' . strtolower(substr($token0Raw, -40)) : '';
        $reserves = substr($this->rpc->ethCall($pair, self::GET_RESERVES), 2);

        if (strlen($reserves) < 128) {
            throw new TpixRpcException('getReserves returned too little data');
        }

        $reserve0 = self::word($reserves, 0);
        $reserve1 = self::word($reserves, 1);

        [$tpixRaw, $usdtRaw] = match ($token0) {
            strtolower(config('tpix_pay.wtpix_address')) => [$reserve0, $reserve1],
            strtolower(config('tpix_pay.usdt_address')) => [$reserve1, $reserve0],
            default => throw new TpixRpcException('the pair is not WTPIX/USDT (token0 ' . ($token0 ?: 'empty') . ')'),
        };

        $reserveTpix = BigDecimal::ofUnscaledValue($tpixRaw, 18);
        $reserveUsdt = BigDecimal::ofUnscaledValue($usdtRaw, (int) config('tpix_pay.usdt_decimals', 6));

        if (! $reserveTpix->isPositive() || $reserveUsdt->isLessThan((string) config('tpix_pay.min_pool_usd', 5))) {
            Log::warning('TPIX pool too thin to price', ['reserve_usdt' => (string) $reserveUsdt]);

            return null;
        }

        $priceUsd = $reserveUsdt->dividedBy($reserveTpix, 12, RoundingMode::HalfUp);
        $fx = $this->usdThb();

        // Every read is snapshotted (at most once a minute), FX or not: the TWAP is on the USD price
        $this->snapshot($priceUsd, $fx, $reserveTpix, $reserveUsdt);

        if ($fx === null) {
            return null;
        }

        $usdThb = TpixMath::dec($fx['rate']);
        $twapUsd = $this->twapUsd();
        $quoteUsd = $twapUsd === null ? null : ($twapUsd->isLessThan($priceUsd) ? $twapUsd : $priceUsd);

        return [
            'price_usd' => (string) $priceUsd,
            'twap_usd' => $twapUsd !== null ? (string) $twapUsd : null,
            'usd_thb' => $fx['rate'],
            'fx_live' => $fx['live'],
            'price_thb' => (string) $priceUsd->multipliedBy($usdThb)->toScale(12, RoundingMode::HalfUp),
            'quote_rate_thb' => $quoteUsd !== null ? (string) $quoteUsd->multipliedBy($usdThb)->toScale(12, RoundingMode::HalfUp) : null,
            'max_quote_thb' => (string) $reserveUsdt->multipliedBy((string) $this->settings->maxPoolRatio())
                ->multipliedBy($usdThb)->toScale(2, RoundingMode::Down),
            'reserve_tpix' => (string) $reserveTpix,
            'reserve_usdt' => (string) $reserveUsdt,
            'source' => $fx['live'] ? self::SOURCE_LIVE : self::SOURCE_FX_STALE,
            'at' => now()->timestamp,
        ];
    }

    /** One 32-byte word of ABI-encoded return data as an integer. */
    private static function word(string $hex, int $index): BigInteger
    {
        $digits = ltrim(substr($hex, $index * 64, 64), '0');

        return $digits === '' ? BigInteger::zero() : BigInteger::fromBase($digits, 16);
    }

    /**
     * USD→THB: a live feed, refreshed hourly (`live`). When every feed is down, the last rate fetched
     * live within fx_max_age_hours — the cache, then a snapshot whose rate was live — marked not live.
     * After that, none: never a typed-in number.
     *
     * @return array{rate:string, live:bool}|null
     */
    private function usdThb(): ?array
    {
        $cached = Cache::get(self::FX_CACHE_KEY);
        $age = is_array($cached) ? now()->timestamp - (int) ($cached['at'] ?? 0) : PHP_INT_MAX;

        if (is_array($cached) && $age < (int) config('tpix_pay.fx_refresh_minutes', 60) * 60) {
            return ['rate' => $cached['rate'], 'live' => true];
        }

        foreach ((array) config('tpix_pay.fx_urls', []) as $url) {
            try {
                $response = Http::withHeaders(['User-Agent' => config('tpix_pay.user_agent')])
                    ->acceptJson()
                    ->timeout(6)
                    ->get($url);
                $thb = $response->successful() ? $response->json('rates.THB') : null;
            } catch (Throwable) {
                $thb = null;
            }

            if (is_numeric($thb) && $thb > 0) {
                $rate = number_format((float) $thb, 6, '.', '');
                Cache::forever(self::FX_CACHE_KEY, ['rate' => $rate, 'at' => now()->timestamp]);

                return ['rate' => $rate, 'live' => true];
            }
        }

        $maxAge = (int) config('tpix_pay.fx_max_age_hours', 24) * 3600;
        if (is_array($cached) && $age < $maxAge) {
            return ['rate' => $cached['rate'], 'live' => false];
        }

        // Only a row read with a live rate may stand in: a stale row would keep a dead rate alive forever
        $last = TpixPriceSnapshot::where('source', self::SOURCE_LIVE)
            ->whereNotNull('usd_thb')
            ->where('created_at', '>=', now()->subSeconds($maxAge))
            ->latest('created_at')
            ->value('usd_thb');

        return $last !== null ? ['rate' => (string) TpixMath::dec($last)->toScale(6, RoundingMode::HalfUp), 'live' => false] : null;
    }

    /** @param  array{rate:string, live:bool}|null  $fx */
    private function snapshot(BigDecimal $priceUsd, ?array $fx, BigDecimal $reserveTpix, BigDecimal $reserveUsdt): void
    {
        try {
            if (TpixPriceSnapshot::where('created_at', '>=', now()->subSeconds(55))->exists()) {
                return;
            }

            TpixPriceSnapshot::create([
                'price_usd' => (string) $priceUsd,
                'usd_thb' => $fx['rate'] ?? null,
                'price_thb' => $fx !== null ? (string) $priceUsd->multipliedBy($fx['rate'])->toScale(12, RoundingMode::HalfUp) : null,
                'reserve_tpix' => (string) $reserveTpix,
                'reserve_usdt' => (string) $reserveUsdt,
                'source' => $fx === null ? self::SOURCE_NO_FX : ($fx['live'] ? self::SOURCE_LIVE : self::SOURCE_FX_STALE),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('TPIX price snapshot not written', ['error' => $e->getMessage()]);
        }
    }

    /** The pool's average USD price over the window, or null with fewer than the minimum snapshots. */
    private function twapUsd(): ?BigDecimal
    {
        $values = TpixPriceSnapshot::where('created_at', '>=', now()->subMinutes((int) config('tpix_pay.average_window_minutes', 30)))
            ->pluck('price_usd');

        if ($values->count() < (int) config('tpix_pay.average_min_snapshots', 3)) {
            return null;
        }

        $sum = BigDecimal::zero();
        foreach ($values as $value) {
            $sum = $sum->plus(TpixMath::dec($value));
        }

        return $sum->dividedBy($values->count(), 12, RoundingMode::HalfUp);
    }
}
