<?php

namespace App\Support;

use App\Models\LicenseActivity;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\ProductDevice;
use Illuminate\Support\Collection;

/**
 * Which keys a machine may run under, and the devices that share one key.
 *
 * A key has a single machine_id column. A product that sells one key for several devices
 * (config/licenses.php → devices_per_key, LocalVPN: a PC + a phone) keeps its first device
 * there, as always, and every further device as a "seat": its product_devices row is
 * status=licensed with license_id = the key. machines() is the union of the two.
 *
 * Only a shared key (the product sells several devices per key AND the key has
 * max_activations > 1) ever looks at seats. A key of any other product — or a trial or free
 * key — is bound to license_keys.machine_id alone, exactly as before this class existed, so a
 * stale product_devices row can never put another product's key on a second machine.
 */
class LicenseSeats
{
    /** Tiers, best first: a paid term beats a trial, a trial beats the free key. */
    public const TIER_PAID = 0;

    public const TIER_TRIAL = 1;

    public const TIER_FREE = 2;

    /** Order inside a tier: the longest term first. */
    private const TYPE_RANK = [
        LicenseKey::TYPE_LIFETIME => 0,
        LicenseKey::TYPE_YEARLY => 1,
        LicenseKey::TYPE_MONTHLY => 2,
        LicenseKey::TYPE_WEEKLY => 3,
        LicenseKey::TYPE_DAILY => 4,
        LicenseKey::TYPE_PRODUCT => 5,
        LicenseKey::TYPE_DEMO => 6,
        LicenseKey::TYPE_FREE => 7,
    ];

    /** How many devices one key of this product may run on. */
    public static function perKey(Product $product): int
    {
        return LicensePlans::devicesPerKey($product->slug);
    }

    /** True when this key is shared between devices (see the class comment). */
    public static function isShared(LicenseKey $license, Product $product): bool
    {
        return self::perKey($product) > 1 && (int) $license->max_activations > 1;
    }

    public static function tier(LicenseKey $license): int
    {
        return match ($license->license_type) {
            LicenseKey::TYPE_FREE => self::TIER_FREE,
            LicenseKey::TYPE_DEMO => self::TIER_TRIAL,
            default => self::TIER_PAID,
        };
    }

    /**
     * Every usable key of this product on this machine, best first: active, not expired, and
     * bound to the machine directly or (shared keys only) through a seat.
     *
     * @return Collection<int, LicenseKey>
     */
    public static function keysOn(Product $product, string $machineId): Collection
    {
        $seatKeyIds = ProductDevice::query()
            ->select('license_id')
            ->where('product_id', $product->id)
            ->where('machine_id', $machineId)
            ->where('status', ProductDevice::STATUS_LICENSED)
            ->whereNotNull('license_id');

        $keys = LicenseKey::query()
            ->where('product_id', $product->id)
            ->where('status', LicenseKey::STATUS_ACTIVE)
            ->where(function ($query) use ($machineId, $seatKeyIds, $product) {
                $query->where('machine_id', $machineId);

                if (self::perKey($product) > 1) {
                    $query->orWhere(fn ($seat) => $seat->whereIn('id', $seatKeyIds)->where('max_activations', '>', 1));
                }
            })
            ->get();

        return self::sortBest($keys->reject(fn (LicenseKey $key) => $key->isExpired()));
    }

    /** The best usable key on this machine, or null. */
    public static function best(Product $product, string $machineId): ?LicenseKey
    {
        return self::keysOn($product, $machineId)->first();
    }

    /**
     * @param  Collection<int, LicenseKey>  $keys
     * @return Collection<int, LicenseKey>
     */
    public static function sortBest(Collection $keys): Collection
    {
        return $keys
            ->sortBy([
                fn (LicenseKey $a, LicenseKey $b) => (self::TYPE_RANK[$a->license_type] ?? 5) <=> (self::TYPE_RANK[$b->license_type] ?? 5),
                // the newest key first among equals (a renewal bought later outlives the first one)
                fn (LicenseKey $a, LicenseKey $b) => $b->id <=> $a->id,
            ])
            ->values();
    }

    /**
     * The machines holding this key: license_keys.machine_id first, then its seats.
     *
     * @return Collection<int, string>
     */
    public static function machines(LicenseKey $license, Product $product): Collection
    {
        $machines = collect([$license->machine_id]);

        if (self::isShared($license, $product)) {
            $machines = $machines->merge(
                ProductDevice::where('product_id', $product->id)
                    ->where('license_id', $license->id)
                    ->where('status', ProductDevice::STATUS_LICENSED)
                    ->orderBy('id')
                    ->pluck('machine_id')
            );
        }

        return $machines->filter()->unique()->values();
    }

    /** Whether this machine holds the key (its own machine, or a seat of a shared key). */
    public static function holds(LicenseKey $license, Product $product, string $machineId): bool
    {
        if ($license->machine_id === $machineId) {
            return true;
        }

        return self::isShared($license, $product)
            && ProductDevice::where('product_id', $product->id)
                ->where('machine_id', $machineId)
                ->where('license_id', $license->id)
                ->where('status', ProductDevice::STATUS_LICENSED)
                ->exists();
    }

    /**
     * Take a machine off a shared key: the key's own machine is cleared, a seat's device goes
     * back to pending with no key. Its product_devices row loses license_id too, so the
     * drm_id/android_id fallbacks of check-machine cannot hand the key back to it.
     */
    public static function release(LicenseKey $license, Product $product, string $machineId): void
    {
        if ($license->machine_id === $machineId) {
            $license->update(['machine_id' => null, 'machine_fingerprint' => null]);
        }

        ProductDevice::where('product_id', $product->id)
            ->where('machine_id', $machineId)
            ->where('license_id', $license->id)
            ->update(['status' => ProductDevice::STATUS_PENDING, 'license_id' => null]);

        self::recount($license, $product);
    }

    /** Free every seat of a shared key (the admin's "reset machine"). No-op for any other key. */
    public static function releaseAll(LicenseKey $license, Product $product): void
    {
        if (! self::isShared($license, $product)) {
            return;
        }

        ProductDevice::where('product_id', $product->id)
            ->where('license_id', $license->id)
            ->where('status', ProductDevice::STATUS_LICENSED)
            ->update(['status' => ProductDevice::STATUS_PENDING, 'license_id' => null]);

        self::recount($license, $product);
    }

    /**
     * The machine to free when a full shared key is moved to a new device with force_rebind:
     * the one seen least recently (a replaced phone, a PC that was reinstalled). A machine with
     * no device row was never seen by the API at all and goes first.
     */
    public static function stalest(LicenseKey $license, Product $product): ?string
    {
        $machines = self::machines($license, $product);

        // raw "Y-m-d H:i:s" strings (toBase skips the datetime cast) sort in time order
        $lastSeen = ProductDevice::where('product_id', $product->id)
            ->whereIn('machine_id', $machines->all())
            ->toBase()
            ->pluck('last_seen_at', 'machine_id');

        return $machines
            ->sortBy(fn (string $machine) => (string) ($lastSeen[$machine] ?? ''))
            ->first();
    }

    /** Keep `activations` equal to the number of machines on the key (the admin page shows it). */
    public static function recount(LicenseKey $license, Product $product): void
    {
        $license->refresh();
        $count = self::machines($license, $product)->count();

        if ((int) $license->activations !== $count) {
            $license->update(['activations' => $count]);
        }
    }

    public static function logRelease(LicenseKey $license, string $machineId, string $byMachine, string $why): void
    {
        LicenseActivity::log(
            $license,
            LicenseActivity::ACTION_MACHINE_RESET,
            LicenseActivity::ACTOR_API,
            null,
            $byMachine,
            $why,
            ['released_machine_id' => $machineId]
        );
    }
}
