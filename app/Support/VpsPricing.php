<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Turns what a VPS costs us into what we charge for it.
 *
 * The same rules as DomainPricing — cost × (1 + margin), rounded UP to a
 * step, never stored — with the VPS shop's own margin and step, because a
 * domain and a server are not sold at the same markup. Currency conversion is
 * DomainPricing's: our supplier account bills in baht, and a cost that is
 * already baht must not be multiplied by an exchange rate.
 */
class VpsPricing
{
    public const DEFAULT_MARGIN = 30.0;

    public const DEFAULT_ROUNDING = 10;

    /**
     * The billing periods the catalogue offers, in the order a customer reads
     * them. Anything else the supplier adds is ignored until it is listed here.
     *
     * @var array<string,array{months:int,th:string,en:string,unit_th:string,unit_en:string}>
     */
    public const PERIODS = [
        '1m' => ['months' => 1, 'th' => 'รายเดือน', 'en' => 'Monthly', 'unit_th' => 'เดือน', 'unit_en' => 'mo'],
        '1y' => ['months' => 12, 'th' => 'รายปี', 'en' => 'Yearly', 'unit_th' => 'ปี', 'unit_en' => 'yr'],
        '2y' => ['months' => 24, 'th' => 'ราย 2 ปี', 'en' => 'Every 2 years', 'unit_th' => '2 ปี', 'unit_en' => '2 yrs'],
    ];

    public static function sell(int $costCents, ?float $marginPercent = null, string $currency = DomainPricing::HOME_CURRENCY): float
    {
        if ($costCents <= 0) {
            return 0.0;
        }

        $margin = $marginPercent ?? static::defaultMargin();
        $withMargin = DomainPricing::costThb($costCents, null, $currency) * (1 + ($margin / 100));

        $step = static::rounding();

        return $step <= 1 ? ceil($withMargin) : ceil($withMargin / $step) * $step;
    }

    public static function defaultMargin(): float
    {
        $margin = Setting::getValue('vps_margin_percent', null);

        if ($margin === null || $margin === '') {
            return static::DEFAULT_MARGIN;
        }

        $margin = (float) $margin;

        // A negative margin is a typo, never a strategy: it would rent every
        // server below what it costs us.
        return $margin >= 0 ? $margin : static::DEFAULT_MARGIN;
    }

    public static function rounding(): int
    {
        $step = (int) Setting::getValue('vps_price_rounding', 0);

        return $step > 0 ? $step : static::DEFAULT_ROUNDING;
    }

    public static function format(float $amount): string
    {
        return DomainPricing::format($amount);
    }

    /**
     * The period key for a catalogue price row: (1, "month") → "1m".
     */
    public static function periodKey(int $period, string $unit): ?string
    {
        $unit = strtolower(trim($unit));

        $key = match (true) {
            str_starts_with($unit, 'month') => $period . 'm',
            str_starts_with($unit, 'year') => $period . 'y',
            default => null,
        };

        return $key !== null && isset(self::PERIODS[$key]) ? $key : null;
    }

    public static function months(string $period): int
    {
        return self::PERIODS[$period]['months'] ?? 1;
    }

    /** "รายเดือน / Monthly" pieces for a period key. */
    public static function periodLabel(string $period, string $lang = 'th'): string
    {
        return self::PERIODS[$period][$lang] ?? $period;
    }

    public static function unitLabel(string $period, string $lang = 'th'): string
    {
        return self::PERIODS[$period]['unit_' . $lang] ?? $period;
    }
}
