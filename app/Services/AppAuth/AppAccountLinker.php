<?php

namespace App\Services\AppAuth;

use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

/**
 * Links a desktop app (CluadeX, BrainX) on one machine to a signed-in account, and picks the
 * licence the app should use there — the LinkResult of app-auth/login and an approved
 * app-auth/device/poll (installer contract 1.5).
 *
 *   1. A key the account owns for this product (key.user_id or its order's user_id — the same
 *      ownership as the customer portal), active, not expired, not demo/free. Never activated
 *      here: the app calls the existing /activate (force_rebind only after the user agrees), so
 *      every activation rule stays in one place.
 *   2. Otherwise this machine's own demo/free key that nobody owns yet — claimed for the account
 *      with a conditional update, so of two accounts at once exactly one wins and a key another
 *      account already holds is never handed over.
 *   3. Otherwise, for products on the free allowlist, the machine's FREE key (created if missing)
 *      claimed the same way. Others: no licence — the account is still linked.
 */
class AppAccountLinker
{
    /**
     * Products whose app may take a FREE key without buying (app-auth/free-license and rule 3).
     * BrainX is not here on purpose: its quick path is the existing 7-day trial (/demo).
     */
    public const FREE_LICENSE_PRODUCTS = [
        'cluadex-ai-coding-assistant',
    ];

    public static function freeLicenseAllowed(Product $product): bool
    {
        return in_array($product->licenseSlug(), self::FREE_LICENSE_PRODUCTS, true);
    }

    /**
     * The LinkResult `data` for this account on this machine.
     *
     * @return array{account: array{name: string, email_masked: string}, account_linked: bool, license: ?array, purchase_url: string}
     */
    public function link(User $user, Product $product, string $machineId): array
    {
        [$license, $source] = $this->licenseFor($user, $product, $machineId);

        return [
            'account' => [
                'name' => (string) $user->name,
                'email_masked' => self::maskEmail((string) $user->email),
            ],
            'account_linked' => true,
            'license' => $license ? self::describe($license, $source, $machineId) : null,
            'purchase_url' => route('products.show', $product->slug),
        ];
    }

    /**
     * @return array{0: ?LicenseKey, 1: ?string} the key and its source (purchased|trial|free)
     */
    public function licenseFor(User $user, Product $product, string $machineId): array
    {
        $owned = $this->ownedKey($user, $product, $machineId);
        if ($owned) {
            return [$owned, 'purchased'];
        }

        $machineKey = $this->claimMachineKey($user, $product, $machineId);
        if ($machineKey) {
            return [$machineKey, self::sourceOf($machineKey)];
        }

        if (self::freeLicenseAllowed($product)) {
            $free = LicenseKey::freeFor($product, $machineId);

            if ($this->claim($free, $user)) {
                return [$free->refresh(), 'free'];
            }
        }

        return [null, null];
    }

    /**
     * Rule 1. Of several paid keys: one already on this machine, then one not bound anywhere, then
     * one on another machine — so a second key the customer owns is never moved off the PC it runs
     * on. Within each group, lifetime first, then the latest end date (a key whose term has not
     * started yet counts as the latest).
     */
    private function ownedKey(User $user, Product $product, string $machineId): ?LicenseKey
    {
        $userId = (int) $user->id;

        return LicenseKey::where('product_id', $product->id)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereIn('order_id', Order::where('user_id', $userId)->select('id'));
            })
            ->active()
            ->whereNotIn('license_type', [LicenseKey::TYPE_DEMO, LicenseKey::TYPE_FREE])
            ->get()
            ->sortBy(fn (LicenseKey $key) => [
                match (true) {
                    $key->machine_id === $machineId => 0,
                    blank($key->machine_id) => 1,
                    default => 2,
                },
                $key->license_type === LicenseKey::TYPE_LIFETIME ? 0 : 1,
                $key->expires_at ? -$key->expires_at->getTimestamp() : PHP_INT_MIN,
                -$key->id,
            ])
            ->first();
    }

    /**
     * Rule 2. The machine's running trial first (it unlocks more), then its FREE key. A key this
     * account already claimed is simply returned; one held by another account is skipped.
     */
    private function claimMachineKey(User $user, Product $product, string $machineId): ?LicenseKey
    {
        $userId = (int) $user->id;

        $candidates = LicenseKey::where('product_id', $product->id)
            ->where('machine_id', $machineId)
            ->whereIn('license_type', [LicenseKey::TYPE_DEMO, LicenseKey::TYPE_FREE])
            ->active()
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')->orWhere('user_id', $userId);
            })
            ->get()
            ->sortBy(fn (LicenseKey $key) => [
                $key->license_type === LicenseKey::TYPE_DEMO ? 0 : 1,
                -$key->id,
            ]);

        foreach ($candidates as $key) {
            if ($this->claim($key, $user)) {
                return $key->refresh();
            }
        }

        return null;
    }

    /**
     * Put an unowned key on the account. True when the key is (now) this account's — false when
     * another account holds it, including one that won a claim racing this one.
     */
    private function claim(LicenseKey $key, User $user): bool
    {
        $userId = (int) $user->id;

        if ($key->user_id !== null) {
            return (int) $key->user_id === $userId;
        }

        // Conditional update (as MacDockController::claimFreeLicense): of two accounts at once, one wins
        $claimed = LicenseKey::where('id', $key->id)
            ->whereNull('user_id')
            ->update(['user_id' => $userId]);

        if ($claimed === 1) {
            Log::info('app-auth: machine key claimed', ['license_id' => $key->id, 'user_id' => $userId]);

            return true;
        }

        return (int) LicenseKey::whereKey($key->id)->value('user_id') === $userId;
    }

    private static function sourceOf(LicenseKey $key): string
    {
        return match ($key->license_type) {
            LicenseKey::TYPE_DEMO => 'trial',
            LicenseKey::TYPE_FREE => 'free',
            default => 'purchased',
        };
    }

    /**
     * The licence object the apps read.
     */
    public static function describe(LicenseKey $key, ?string $source = null, ?string $machineId = null): array
    {
        $described = [
            'license_key' => $key->license_key,
            'license_type' => $key->license_type,
            'expires_at' => self::iso($key->expires_at),
            'source' => $source ?? self::sourceOf($key),
        ];

        if ($machineId !== null) {
            $described['bound_to_other_device'] = filled($key->machine_id) && $key->machine_id !== $machineId;
        }

        return $described;
    }

    /** ISO 8601 in UTC with a Z, as `server_time` — null stays null. */
    public static function iso(?CarbonInterface $at): ?string
    {
        return $at?->copy()->utc()->format('Y-m-d\TH:i:s\Z');
    }

    /** "xjanova@gmail.com" → "xj***@gmail.com": enough for the owner to recognise, not to reuse. */
    public static function maskEmail(string $email): string
    {
        $at = strrpos($email, '@');
        if ($at === false) {
            return mb_substr($email, 0, 2) . '***';
        }

        $local = substr($email, 0, $at);
        $keep = mb_strlen($local) <= 2 ? 1 : 2;

        return mb_substr($local, 0, $keep) . '***' . substr($email, $at);
    }
}
