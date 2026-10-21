<?php

namespace App\Support;

use App\Models\Setting;

/**
 * The VPS shop's operator settings: whether it sells, and when a renewal is
 * announced and charged.
 *
 * The renewal schedule is shorter than the domain one on purpose. A domain
 * is paid a year at a time and warned a month ahead; a server is usually paid
 * monthly, and a 37-day warning on a 30-day rental would arrive before the
 * rental did.
 */
class VpsSettings
{
    public const DEFAULT_NOTICE_DAYS = 7;

    public const DEFAULT_CHARGE_DAYS = 3;

    public const DEFAULT_LEAD_DAYS = 1;

    public const MAX_DAYS = 60;

    public static function salesEnabled(): bool
    {
        return (bool) Setting::getValue('vps_sales_enabled', true);
    }

    /** Announce the coming charge this many days before expiry. */
    public static function noticeDays(): int
    {
        return self::days('vps_notice_days', self::DEFAULT_NOTICE_DAYS);
    }

    /** Take the money this many days before expiry. */
    public static function chargeDays(): int
    {
        return self::days('vps_charge_days', self::DEFAULT_CHARGE_DAYS);
    }

    /** The notice must have been out at least this long before the charge. */
    public static function leadDays(): int
    {
        $value = Setting::getValue('vps_notice_lead_days', null);

        return ($value === null || $value === '') ? self::DEFAULT_LEAD_DAYS : max(0, min(30, (int) $value));
    }

    public static function scheduleIsCoherent(): bool
    {
        return self::noticeDays() >= self::chargeDays() + self::leadDays();
    }

    protected static function days(string $key, int $default): int
    {
        $value = (int) Setting::getValue($key, 0);

        return $value > 0 ? min($value, self::MAX_DAYS) : $default;
    }
}
