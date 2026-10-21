<?php

namespace App\Services\LocalVpnFleet;

use App\Models\LicenseKey;
use App\Models\Product;
use App\Support\LocalVpnFleet\Tier;

/**
 * Which LocalVPN license each device is on, for many devices in one query.
 *
 * A device's tier is its best active, unexpired LocalVPN license (pro over
 * trial over free). The relay and exit usage a node reports resolves to
 * machine_ids, so this is the one place that turns those into licenses.
 */
final class MachineLicenses
{
    private ?int $productId = null;

    private bool $productLoaded = false;

    /**
     * @param  list<string>  $machineIds
     * @return array<string, LicenseKey> machine_id => best license (devices without one are absent)
     */
    public function resolve(array $machineIds): array
    {
        // Array keys turn numeric machine ids into ints; they are still ids.
        $machineIds = array_values(array_unique(array_filter(
            array_map(fn ($id) => is_int($id) ? (string) $id : $id, $machineIds),
            fn ($id) => is_string($id) && $id !== '',
        )));
        $productId = $this->productId();
        if ($machineIds === [] || $productId === null) {
            return [];
        }

        $best = [];
        foreach (array_chunk($machineIds, 500) as $chunk) {
            $licenses = LicenseKey::query()
                ->where('product_id', $productId)
                ->whereIn('machine_id', $chunk)
                ->where('status', LicenseKey::STATUS_ACTIVE)
                ->get(['id', 'product_id', 'user_id', 'license_key', 'status', 'license_type', 'expires_at', 'machine_id']);

            foreach ($licenses as $license) {
                if ($license->isExpired()) {
                    continue;
                }
                $current = $best[$license->machine_id] ?? null;
                if ($current === null || self::rank($license) > self::rank($current)) {
                    $best[$license->machine_id] = $license;
                }
            }
        }

        return $best;
    }

    /**
     * The tier a device gets: the better of the license the request
     * authenticated with and the device's best license.
     */
    public function tierFor(string $machineId, ?LicenseKey $authenticated = null): string
    {
        $best = $this->resolve([$machineId])[$machineId] ?? null;

        return Tier::ALL[max(self::tierRank(Tier::forLicense($authenticated)), self::tierRank(Tier::forLicense($best)))];
    }

    private static function rank(LicenseKey $license): int
    {
        return self::tierRank(Tier::forLicense($license));
    }

    private static function tierRank(string $tier): int
    {
        return (int) array_search($tier, Tier::ALL, true);
    }

    private function productId(): ?int
    {
        if (! $this->productLoaded) {
            $this->productId = Product::where('slug', 'localvpn')->value('id');
            $this->productLoaded = true;
        }

        return $this->productId;
    }
}
