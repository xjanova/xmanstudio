<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Turns an upstream cost into the price we show.
 *
 * Every domain price in the application comes through here — the search
 * results, the order summary, the renewal notice and the admin preview. One
 * function means the four can never quote different numbers, and changing the
 * margin or the exchange rate is a settings edit rather than a deploy.
 *
 * The rate is a setting rather than a live feed on purpose. A price that moves
 * between the search page and the confirm button is a price the customer will
 * argue about, and an outage at a currency API is not a reason for the shop to
 * stop selling. The operator sets a rate with a buffer baked in and reviews it
 * when it drifts.
 */
class DomainPricing
{
    /** Fallback rate, used only when the operator has not set one. */
    public const DEFAULT_FX_RATE = 36.5;

    public const DEFAULT_MARGIN = 45.0;

    public const DEFAULT_ROUNDING = 10;

    /**
     * The selling price in THB for something that costs us $cents upstream.
     *
     * Rounds up, never down: rounding a margin away is how a catalogue ends up
     * with a line that loses money on every sale.
     */
    public static function sell(int $costUsdCents, ?float $marginPercent = null): float
    {
        if ($costUsdCents <= 0) {
            return 0.0;
        }

        $margin = $marginPercent ?? static::defaultMargin();

        $baseThb = ($costUsdCents / 100) * static::fxRate();
        $withMargin = $baseThb * (1 + ($margin / 100));

        return static::round($withMargin);
    }

    /**
     * What the sale actually cost us in THB, for margin reporting.
     */
    public static function costThb(int $costUsdCents, ?float $fxRate = null): float
    {
        return round(($costUsdCents / 100) * ($fxRate ?? static::fxRate()), 2);
    }

    /**
     * Round a price up to the configured step, so the catalogue reads as
     * prices rather than as conversions (590, not 587.42).
     */
    public static function round(float $amount): float
    {
        $step = static::rounding();

        if ($step <= 1) {
            return ceil($amount);
        }

        return ceil($amount / $step) * $step;
    }

    public static function fxRate(): float
    {
        $rate = (float) Setting::getValue('domain_usd_thb_rate', 0);

        // A zero or negative rate would silently price the whole catalogue at
        // nothing, so an unset or corrupted value falls back rather than sells.
        return $rate > 0 ? $rate : static::DEFAULT_FX_RATE;
    }

    public static function defaultMargin(): float
    {
        $margin = Setting::getValue('domain_margin_percent', null);

        if ($margin === null || $margin === '') {
            return static::DEFAULT_MARGIN;
        }

        $margin = (float) $margin;

        // Negative margin is always a mistake — an operator typing "-45" into
        // the box should not be able to sell the catalogue below cost.
        return $margin >= 0 ? $margin : static::DEFAULT_MARGIN;
    }

    public static function rounding(): int
    {
        $step = (int) Setting::getValue('domain_price_rounding', 0);

        return $step > 0 ? $step : static::DEFAULT_ROUNDING;
    }

    /**
     * Format for display. Domain prices are always whole baht after rounding,
     * so the decimals would be noise on every row.
     */
    public static function format(float $amount): string
    {
        return number_format($amount, 0) . ' ฿';
    }
}
