<?php

namespace App\Support;

use App\Models\LicenseKey;

/**
 * How much cloud space a key of a cloud product carries: the plan's base
 * (config/licenses.php → cloud_storage) plus whatever the admin added to that
 * one key (license_keys.extra_storage_gb).
 *
 * The product's server reads the total from the status/validate API as
 * cloud_storage_bytes and enforces it — BrainX Cloud refuses an upload that
 * would pass it. A product missing from the config has no cloud space, and
 * its API answers stay exactly as they were.
 */
class CloudStorage
{
    public const BYTES_PER_GB = 1024 * 1024 * 1024;

    /** Whether keys of this product carry cloud space at all. */
    public static function offers(?string $slug): bool
    {
        return $slug !== null && isset(config('licenses.cloud_storage')[$slug]);
    }

    /** The space every key of the product starts with, in GB (0 when it offers none). */
    public static function baseGb(?string $slug): int
    {
        return self::offers($slug) ? max(0, (int) (config('licenses.cloud_storage')[$slug]['base_gb'] ?? 0)) : 0;
    }

    /** The most the admin may add to one key, in GB. */
    public static function maxExtraGb(?string $slug): int
    {
        return self::offers($slug) ? max(0, (int) (config('licenses.cloud_storage')[$slug]['max_extra_gb'] ?? 0)) : 0;
    }

    /** Base + this key's extra, in GB; null for a product without cloud space. */
    public static function totalGb(LicenseKey $license): ?int
    {
        $slug = $license->product?->slug;
        if (! self::offers($slug)) {
            return null;
        }

        return self::baseGb($slug) + max(0, (int) $license->extra_storage_gb);
    }

    /**
     * The fields the product API adds for a key of a cloud product — the byte
     * count is an integer so the server never rounds a float — or nothing.
     *
     * @return array<string, int>
     */
    public static function apiFields(LicenseKey $license): array
    {
        $gb = self::totalGb($license);

        return $gb === null ? [] : [
            'cloud_storage_gb' => $gb,
            'cloud_storage_bytes' => $gb * self::BYTES_PER_GB,
        ];
    }
}
