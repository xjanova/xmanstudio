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
 * - spot(): the pool price, cached for display_cache_seconds and read at most once per request, so
 *   a page with fifty price tags asks the chain at most once a minute. Snapshotted once a minute.
 * - quote rate: the LOWER of the live THB price and the last half hour's average (when there are
 *   enough snapshots). The pool is thin; pumping it for a minute must not buy a cheap quote.
 * - forDisplay(): the price tag. Never throws, returns null whenever there is no trustworthy price —
 *   the tag then simply is not shown.
 *
 * Bound as a scoped singleton (AppServiceProvider) so the per-request memo is per request.
 */
class TpixPriceService
{
    public const SPOT_CACHE_KEY = 'tpix_pay:spot';

    public const FX_CACHE_KEY = 'tpix_pay:fx';

    private const GET_RESERVES = '0x0902f1ac';

    private const TOKEN0 = '0x0dfe1681';

    private bool $loaded = false;

    /** @var array<string, mixed>|null */
    private ?array $memo = null;

    public function __construct(
        private TpixRpcClient $rpc,
        private TpixPaySettings $settings,
    ) {}

    /**
     * @return array{price_usd:string, usd_thb:string, price_thb:string, quote_rate_thb:string,
     *               reserve_tpix:string, reserve_usdt:string, source:string, at:int}|null
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
        }

        try {
            $spot = $this->readSpot();
        } catch (Throwable $e) {
            Log::warning('TPIX price unavailable', ['error' => $e->getMessage()]);
            $spot = null;
        }

        // Kept a little longer than the scheduler's one-minute refresh (tpix:pay-scan reads it fresh),
        // so a page view practically never waits on the chain itself. A failure is remembered
        // briefly too, so a dead node is not asked once per page view.
        Cache::put(self::SPOT_CACHE_KEY, ['spot' => $spot], $spot !== null
            ? max(5, (int) config('tpix_pay.display_cache_seconds', 60)) + 30
            : 15);

        $this->loaded = true;

        return $this->memo = $spot;
    }

    /** THB per 1 TPIX a new quote uses, or null when there is no trustworthy price. */
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
     * The price tag: how much $thb is in TPIX right now, or the product's fixed TPIX price.
     * null = show nothing (feature off, no price, nothing to price). Never throws.
     *
     * @return array{tpix:string, tpix_formatted:string, fixed:bool, rate_thb:?string,
     *               rate_formatted:?string, price_usd:?string, updated_at:?Carbon}|null
     */
    public function forDisplay(string|int|float|null $thb, string|int|float|null $fixedTpix = null): ?array
    {
        try {
            if (! $this->settings->showsPrices()) {
                return null;
            }

            $spot = $this->spot();
            $rate = $spot['quote_rate_thb'] ?? null;

            if ($fixedTpix !== null && $fixedTpix !== '' && TpixMath::dec($fixedTpix)->isPositive()) {
                $tpix = (string) TpixMath::dec($fixedTpix)->toScale(4, RoundingMode::Ceiling);
                $fixed = true;
            } elseif ($rate !== null && $thb !== null && $thb !== '' && TpixMath::dec($thb)->isPositive()) {
                $tpix = $this->thbToTpix($thb, $rate);
                $fixed = false;
            } else {
                return null;
            }

            return [
                'tpix' => $tpix,
                'tpix_formatted' => TpixMath::formatTpix($tpix),
                'fixed' => $fixed,
                'rate_thb' => $rate,
                'rate_formatted' => $rate !== null ? self::formatRate($rate) : null,
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
     * VAT and a coupon move it in the same proportion as they move the THB total.
     */
    public function fixedTotalForOrder(Order $order): ?string
    {
        $order->loadMissing('items.product');

        return $this->fixedTotalForLines($order->items, $order->subtotal ?? 0, $order->total ?? 0);
    }

    /**
     * The same for any lines with ->product, ->price and ->quantity (order items, cart items), so the
     * checkout shows the amount the quote will ask for.
     *
     * @param  iterable<object>  $lines
     */
    public function fixedTotalForLines(iterable $lines, string|int|float $subtotal, string|int|float $total): ?string
    {
        if (! TpixMath::dec(TpixMath::thb($subtotal))->isPositive()) {
            return null;
        }

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
            $sum = $sum->plus(TpixMath::dec($product->price_tpix)->multipliedBy((int) $line->quantity));
            $any = true;
        }

        if (! $any) {
            return null;
        }

        return (string) $sum->multipliedBy(TpixMath::thb($total))
            ->dividedBy(TpixMath::thb($subtotal), 4, RoundingMode::Ceiling);
    }

    /**
     * The TPIX price for an API payload (catalog, pricing, packs): a 4-decimal string, or null when the
     * pages would show none. Additive fields — apps that do not know them ignore them.
     */
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
        if (! $this->settings->showsPrices() || ($spot = $this->spot()) === null) {
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

    /**
     * @return array<string, mixed>|null
     *
     * @throws TpixRpcException
     */
    private function readSpot(): ?array
    {
        $pair = config('tpix_pay.pair_address');
        $reserves = substr($this->rpc->ethCall($pair, self::GET_RESERVES), 2);
        $token0 = '0x' . strtolower(substr($this->rpc->ethCall($pair, self::TOKEN0), -40));

        if (strlen($reserves) < 128) {
            throw new TpixRpcException('getReserves returned too little data');
        }

        $reserve0 = self::word($reserves, 0);
        $reserve1 = self::word($reserves, 1);

        [$tpixRaw, $usdtRaw] = match ($token0) {
            strtolower(config('tpix_pay.wtpix_address')) => [$reserve0, $reserve1],
            strtolower(config('tpix_pay.usdt_address')) => [$reserve1, $reserve0],
            default => throw new TpixRpcException('the pair is not WTPIX/USDT (token0 ' . $token0 . ')'),
        };

        $reserveTpix = BigDecimal::ofUnscaledValue($tpixRaw, 18);
        $reserveUsdt = BigDecimal::ofUnscaledValue($usdtRaw, (int) config('tpix_pay.usdt_decimals', 6));

        if (! $reserveTpix->isPositive() || $reserveUsdt->isLessThan((string) config('tpix_pay.min_pool_usd', 5))) {
            Log::warning('TPIX pool too thin to price', ['reserve_usdt' => (string) $reserveUsdt]);

            return null;
        }

        $usdThb = $this->usdThb();
        if ($usdThb === null) {
            return null;
        }

        $priceUsd = $reserveUsdt->dividedBy($reserveTpix, 12, RoundingMode::HalfUp);
        $priceThb = $priceUsd->multipliedBy($usdThb)->toScale(12, RoundingMode::HalfUp);

        $this->snapshot($priceUsd, $usdThb, $priceThb, $reserveTpix, $reserveUsdt);

        return [
            'price_usd' => (string) $priceUsd,
            'usd_thb' => $usdThb,
            'price_thb' => (string) $priceThb,
            'quote_rate_thb' => $this->quoteRateFrom($priceThb),
            'reserve_tpix' => (string) $reserveTpix,
            'reserve_usdt' => (string) $reserveUsdt,
            'source' => 'dex',
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
     * USD→THB: a live feed, refreshed hourly. When every feed is down, the last rate fetched within
     * fx_max_age_hours (cache, then the snapshots); after that, none — never a typed-in number.
     */
    private function usdThb(): ?string
    {
        $cached = Cache::get(self::FX_CACHE_KEY);
        $age = is_array($cached) ? now()->timestamp - (int) ($cached['at'] ?? 0) : PHP_INT_MAX;

        if (is_array($cached) && $age < (int) config('tpix_pay.fx_refresh_minutes', 60) * 60) {
            return $cached['rate'];
        }

        foreach ((array) config('tpix_pay.fx_urls', []) as $url) {
            try {
                $response = Http::withHeaders(['User-Agent' => config('tpix_pay.user_agent')])
                    ->acceptJson()
                    ->timeout(6)
                    ->get($url);
                $thb = $response->successful() ? ($response->json('rates.THB')) : null;
            } catch (Throwable) {
                $thb = null;
            }

            if (is_numeric($thb) && $thb > 0) {
                $rate = number_format((float) $thb, 6, '.', '');
                Cache::forever(self::FX_CACHE_KEY, ['rate' => $rate, 'at' => now()->timestamp]);

                return $rate;
            }
        }

        $maxAge = (int) config('tpix_pay.fx_max_age_hours', 24) * 3600;
        if (is_array($cached) && $age < $maxAge) {
            return $cached['rate'];
        }

        $last = TpixPriceSnapshot::where('created_at', '>=', now()->subSeconds($maxAge))->latest('created_at')->value('usd_thb');

        return $last !== null ? (string) TpixMath::dec($last)->toScale(6, RoundingMode::HalfUp) : null;
    }

    private function snapshot(BigDecimal $priceUsd, string $usdThb, BigDecimal $priceThb, BigDecimal $reserveTpix, BigDecimal $reserveUsdt): void
    {
        try {
            if (TpixPriceSnapshot::where('created_at', '>=', now()->subSeconds(55))->exists()) {
                return;
            }

            TpixPriceSnapshot::create([
                'price_usd' => (string) $priceUsd,
                'usd_thb' => $usdThb,
                'price_thb' => (string) $priceThb,
                'reserve_tpix' => (string) $reserveTpix,
                'reserve_usdt' => (string) $reserveUsdt,
                'source' => 'dex',
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('TPIX price snapshot not written', ['error' => $e->getMessage()]);
        }
    }

    private function quoteRateFrom(BigDecimal $spotThb): string
    {
        $values = TpixPriceSnapshot::where('created_at', '>=', now()->subMinutes((int) config('tpix_pay.average_window_minutes', 30)))
            ->pluck('price_thb');

        if ($values->count() < (int) config('tpix_pay.average_min_snapshots', 3)) {
            return (string) $spotThb;
        }

        $sum = BigDecimal::zero();
        foreach ($values as $value) {
            $sum = $sum->plus(TpixMath::dec($value));
        }
        $average = $sum->dividedBy($values->count(), 12, RoundingMode::HalfUp);

        return (string) ($average->isLessThan($spotThb) ? $average : $spotThb);
    }
}
