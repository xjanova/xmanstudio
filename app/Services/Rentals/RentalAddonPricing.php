<?php

namespace App\Services\Rentals;

use App\Models\UserRental;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * What an add-on costs when it is bought part-way through the main package's cycle.
 *
 *     price = floor(cycle price × quantity × days left ÷ days in the cycle)      (satang, integer)
 *
 * Days are Thai calendar days (Asia/Bangkok), counted from the day the money arrives to the main
 * package's expiry day — the app clock is UTC, and a payment at 01:00 in Bangkok is still the
 * previous day in UTC. "Days in the cycle" is the cycle actually being paid for (period start →
 * expiry), so a yearly package paid 1 Jan 2027 has 365, one paid 1 Jan 2028 has 366.
 *
 * Worked example (the spec's): yearly package paid 1 Jan 2027, add-on ฿2,900/year bought 1 Jul 2027
 * → 184 days left → floor(290000 × 184 ÷ 365) = 146191 satang = ฿1,461.91. Paid 3 Jul instead
 * → 182 days → ฿1,446.02. A quote is therefore good for the day it was made only.
 */
final class RentalAddonPricing
{
    public const TIMEZONE = 'Asia/Bangkok';

    /** Whole days from $on's Thai date up to the rental's expiry date. 0 once the expiry day has come. */
    public static function daysLeft(UserRental $rental, ?CarbonInterface $on = null): int
    {
        if (! $rental->expires_at) {
            return 0;
        }

        return max(0, self::daysBetween($on ?? now(), $rental->expires_at));
    }

    /** Days in the cycle being paid for (period start → expiry), at least 1 when there is one. */
    public static function cycleDays(UserRental $rental): int
    {
        $start = $rental->period_starts_at ?? $rental->starts_at;
        if (! $start || ! $rental->expires_at) {
            return 0;
        }

        return max(1, self::daysBetween($start, $rental->expires_at));
    }

    public static function prorate(int $cyclePriceSatang, int $quantity, int $daysLeft, int $cycleDays): int
    {
        if ($cyclePriceSatang <= 0 || $quantity <= 0 || $daysLeft <= 0 || $cycleDays <= 0) {
            return 0;
        }

        // Never more than a full cycle, even if the rental was extended past one.
        $daysLeft = min($daysLeft, $cycleDays);

        // intdiv on non-negative integers is floor — no float anywhere.
        return intdiv($cyclePriceSatang * $quantity * $daysLeft, $cycleDays);
    }

    /** Today's date in Thailand ("2027-07-01") — what a quote is stamped with. */
    public static function today(?CarbonInterface $now = null): string
    {
        return CarbonImmutable::instance($now ?? now())->setTimezone(self::TIMEZONE)->toDateString();
    }

    private static function daysBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        $a = CarbonImmutable::instance($from)->setTimezone(self::TIMEZONE)->startOfDay();
        $b = CarbonImmutable::instance($to)->setTimezone(self::TIMEZONE)->startOfDay();

        // Thailand has no DST, so two local midnights are a whole number of days apart.
        return (int) round($a->diffInDays($b, false));
    }
}
