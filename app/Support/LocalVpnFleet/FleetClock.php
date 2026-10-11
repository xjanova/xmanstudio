<?php

namespace App\Support\LocalVpnFleet;

use Carbon\CarbonImmutable;

/**
 * The relay fleet's notion of "today" and "this month".
 *
 * Users are in Thailand and the admin reads Thai dates, so daily caps reset at
 * midnight Bangkok time and node billing periods start on billing_day in
 * Bangkok time. (Providers that bill by UTC month are off by seven hours at
 * the edges — irrelevant for a budget projection.)
 */
final class FleetClock
{
    public const TZ = 'Asia/Bangkok';

    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TZ);
    }

    public static function today(): string
    {
        return self::now()->toDateString();
    }
}
