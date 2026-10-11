<?php

namespace App\Support;

use App\Models\LicenseKey;
use App\Models\Product;

/**
 * The licence a LocalVPN API request runs under, and whether it gets Pro.
 *
 * One place for what LocalVpnRelayController, WireguardController, VpnProxyController,
 * LocalVpnFileController and GlobalTorrentController each used to work out on their own.
 *
 * Owner's rules (2026-10-11):
 *   — Pro = a paid key OR the 30-day trial while it runs (the trial is the full Pro app).
 *   — Everyone else is on the free tier, which still needs a FREE key for these APIs: a device
 *     without any usable key gets one here (a trial that ended falls back to it).
 *   — One paid key runs on two devices (a PC + a phone): the second device is a seat of the key
 *     (LicenseSeats), so it is recognised everywhere the first one is.
 */
class LocalVpnLicenses
{
    public const SLUG = 'localvpn';

    public static function product(): ?Product
    {
        return Product::where('slug', self::SLUG)->where('requires_license', true)->first();
    }

    /**
     * The key for a request from $machineId.
     *
     * The key the app sent, when this machine holds it and it still runs — unless the machine has
     * a better tier now (the app still sends its FREE key after a trial started or a key was bought:
     * the machine is Pro all the same). Otherwise ($fallback) the best usable key on the machine —
     * paid, then trial, then free — and when there is none ($createFree) its FREE key, created here.
     */
    public static function forDevice(?string $licenseKey, ?string $machineId, bool $fallback = true, bool $createFree = true): ?LicenseKey
    {
        if (! $machineId || ! ($product = self::product())) {
            return null;
        }

        $best = LicenseSeats::best($product, $machineId);

        if ($licenseKey) {
            $license = LicenseKey::where('license_key', $licenseKey)
                ->where('product_id', $product->id)
                ->where('status', LicenseKey::STATUS_ACTIVE)
                ->first();

            if ($license && ! $license->isExpired() && LicenseSeats::holds($license, $product, $machineId)
                && ($best === null || LicenseSeats::tier($best) >= LicenseSeats::tier($license))) {
                return $license;
            }
        }

        if (! $fallback) {
            return null;
        }

        return $best ?? ($createFree ? self::freeKey($product, $machineId) : null);
    }

    /**
     * For APIs that insist on the key the app sends (torrent): the key must belong to this
     * machine. When it is this machine's own key but no longer usable — the trial ran out, a
     * term ended — the machine carries on with its best key or the free one, instead of the
     * app being locked out until it next validates. A key that is not this machine's (or a
     * revoked one) still gets nothing.
     */
    public static function forDeviceWithKey(?string $licenseKey, ?string $machineId): ?LicenseKey
    {
        if (! $licenseKey || ! $machineId || ! ($product = self::product())) {
            return null;
        }

        $license = LicenseKey::where('license_key', $licenseKey)->where('product_id', $product->id)->first();

        if (! $license || $license->status === LicenseKey::STATUS_REVOKED || ! LicenseSeats::holds($license, $product, $machineId)) {
            return null;
        }

        return $license->isValid() ? $license : self::forDevice(null, $machineId);
    }

    /** The machine's FREE key — one per machine, created if missing (firstOrCreate, safe to repeat). */
    public static function freeKey(Product $product, string $machineId): LicenseKey
    {
        return LicenseKey::freeFor($product, $machineId);
    }

    /** Pro: a usable key that is not the free one — a paid term or a running trial. */
    public static function isPremium(?LicenseKey $license): bool
    {
        return $license !== null
            && $license->license_type !== LicenseKey::TYPE_FREE
            && $license->isValid();
    }

    /** Whether this machine runs Pro (any of its keys, seats of a shared key included). */
    public static function machineHasPremium(?string $machineId): bool
    {
        if (! $machineId || ! ($product = self::product())) {
            return false;
        }

        return self::isPremium(LicenseSeats::best($product, $machineId));
    }

    /** Whether this account owns a LocalVPN key that runs Pro (keys bought on the site carry user_id). */
    public static function userHasPremium(?int $userId): bool
    {
        if (! $userId || ! ($product = self::product())) {
            return false;
        }

        return LicenseKey::where('product_id', $product->id)
            ->where('user_id', $userId)
            ->where('status', LicenseKey::STATUS_ACTIVE)
            ->where('license_type', '!=', LicenseKey::TYPE_FREE)
            ->get()
            ->contains(fn (LicenseKey $key) => self::isPremium($key));
    }
}
