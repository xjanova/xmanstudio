<?php

namespace App\Support;

use App\Models\LicenseKey;
use App\Models\Product;

/**
 * How much cloud space a key of a cloud product carries: the plan's base
 * (config/licenses.php → cloud_storage), plus the packs the customer bought
 * for that key (license_storage_addons), plus whatever the admin added to it
 * (license_keys.extra_storage_gb).
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
        return $slug !== null && isset(config('licenses.cloud_storage')[Product::canonicalSlug($slug)]);
    }

    /** The space every key of the product starts with, in GB (0 when it offers none). */
    public static function baseGb(?string $slug): int
    {
        return self::offers($slug) ? max(0, (int) (config('licenses.cloud_storage')[Product::canonicalSlug($slug)]['base_gb'] ?? 0)) : 0;
    }

    /** The most one key may carry on top of its base — bought and admin-added together, in GB. */
    public static function maxExtraGb(?string $slug): int
    {
        return self::offers($slug) ? max(0, (int) (config('licenses.cloud_storage')[Product::canonicalSlug($slug)]['max_extra_gb'] ?? 0)) : 0;
    }

    /**
     * The packs a customer can buy for one key of the product: GB => price (THB),
     * smallest first. Empty for a product that sells none.
     *
     * @return array<int, int>
     */
    public static function addons(?string $slug): array
    {
        if (! self::offers($slug)) {
            return [];
        }

        $packs = [];
        foreach ((array) (config('licenses.cloud_storage')[Product::canonicalSlug($slug)]['addons'] ?? []) as $gb => $price) {
            if ((int) $gb > 0 && (int) $price > 0) {
                $packs[(int) $gb] = (int) $price;
            }
        }
        ksort($packs);

        return $packs;
    }

    /** What one pack of $gb costs, or null when the product does not sell that pack. */
    public static function addonPrice(?string $slug, mixed $gb): ?int
    {
        return is_numeric($gb) && (int) $gb == $gb ? (self::addons($slug)[(int) $gb] ?? null) : null;
    }

    /** The slug of the cart product that sells space for this product, or null. */
    public static function addonProductSlug(?string $slug): ?string
    {
        return self::offers($slug) ? (config('licenses.cloud_storage')[Product::canonicalSlug($slug)]['addon_product'] ?? null) : null;
    }

    /** The product whose keys an add-on product gives space to (brainx-cloud-storage → brainx), or null. */
    public static function productForAddon(?string $addonSlug): ?string
    {
        foreach ((array) config('licenses.cloud_storage') as $slug => $settings) {
            if ($addonSlug !== null && ($settings['addon_product'] ?? null) === $addonSlug) {
                return $slug;
            }
        }

        return null;
    }

    /** Every add-on product slug — kept out of the store listings. @return list<string> */
    public static function addonProductSlugs(): array
    {
        return array_values(array_filter(array_map(
            fn ($settings) => $settings['addon_product'] ?? null,
            (array) config('licenses.cloud_storage')
        )));
    }

    /** The pack size an order or cart line of an add-on bought, from its custom_requirements, or null. */
    public static function packOf(?string $customRequirements): ?int
    {
        $requirements = $customRequirements ? json_decode($customRequirements, true) : null;
        $gb = is_array($requirements) ? ($requirements['storage_gb'] ?? null) : null;

        return is_int($gb) && $gb > 0 ? $gb : null;
    }

    /** The space the customer bought for this key, in GB. */
    public static function purchasedGb(LicenseKey $license): int
    {
        return (int) ($license->relationLoaded('storageAddons')
            ? $license->storageAddons->sum('gb_added')
            : $license->storageAddons()->sum('gb_added'));
    }

    /** How many more GB this key can still take before it reaches max_extra_gb. */
    public static function roomLeftGb(LicenseKey $license): int
    {
        $slug = $license->product?->slug;

        return max(0, self::maxExtraGb($slug) - self::purchasedGb($license) - max(0, (int) $license->extra_storage_gb));
    }

    /** Base + bought + admin-added, in GB; null for a product without cloud space. */
    public static function totalGb(LicenseKey $license): ?int
    {
        $slug = $license->product?->slug;
        if (! self::offers($slug)) {
            return null;
        }

        return self::baseGb($slug) + self::purchasedGb($license) + max(0, (int) $license->extra_storage_gb);
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
