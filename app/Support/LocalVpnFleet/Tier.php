<?php

namespace App\Support\LocalVpnFleet;

use App\Models\LicenseKey;

/**
 * Which relay-fleet tier a LocalVPN license is on: free, trial or pro.
 *
 * Kept to one method on purpose so it can later delegate to the shared
 * LocalVpnLicenses helper without touching any caller.
 */
final class Tier
{
    public const FREE = 'free';

    public const TRIAL = 'trial';

    public const PRO = 'pro';

    /** Lowest to highest — budget cut-offs drop tiers in this order. */
    public const ALL = [self::FREE, self::TRIAL, self::PRO];

    private const PAID_TYPES = ['lifetime', 'yearly', 'monthly', 'weekly', 'daily'];

    private const TRIAL_TYPES = ['demo', 'trial'];

    public static function forLicense(?LicenseKey $license): string
    {
        if ($license === null
            || $license->status !== LicenseKey::STATUS_ACTIVE
            || $license->isExpired()) {
            return self::FREE;
        }

        if (in_array($license->license_type, self::PAID_TYPES, true)) {
            return self::PRO;
        }

        return in_array($license->license_type, self::TRIAL_TYPES, true) ? self::TRIAL : self::FREE;
    }
}
