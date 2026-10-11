<?php

namespace App\Services\LocalVpnFleet;

use App\Models\VpnGameProfile;
use Illuminate\Support\Facades\Cache;

/**
 * Enabled game profiles, cached five minutes: the app's split-tunnel list
 * (GET /api/v1/localvpn/game-profiles) and every game node's firewall
 * allow-list (exit.allow_cidrs in the node sync answer). Saving or deleting a
 * profile clears both.
 */
final class GameProfiles
{
    private const PUBLIC_KEY = 'localvpn_fleet:game_profiles';

    private const CIDRS_KEY = 'localvpn_fleet:allow_cidrs';

    private const TTL_SECONDS = 300;

    /** @return list<array{slug: string, name: string, region: ?string, cidrs: list<string>, probe_host: ?string}> */
    public static function publicList(): array
    {
        return Cache::remember(self::PUBLIC_KEY, self::TTL_SECONDS, fn () => VpnGameProfile::enabled()
            ->orderBy('sort')
            ->orderBy('name')
            ->get()
            ->map(fn (VpnGameProfile $profile) => [
                'slug' => $profile->slug,
                'name' => $profile->name,
                'region' => $profile->region,
                'cidrs' => $profile->cidrList(),
                'probe_host' => $profile->probe_host,
            ])
            ->filter(fn (array $profile) => $profile['cidrs'] !== [])
            ->values()
            ->all());
    }

    /** Union of every enabled profile's CIDRs. @return list<string> */
    public static function allowCidrs(): array
    {
        return Cache::remember(self::CIDRS_KEY, self::TTL_SECONDS, function () {
            $all = [];
            foreach (self::publicList() as $profile) {
                foreach ($profile['cidrs'] as $cidr) {
                    $all[$cidr] = true;
                }
            }
            $cidrs = array_keys($all);
            sort($cidrs);

            return $cidrs;
        });
    }

    public static function forget(): void
    {
        Cache::forget(self::PUBLIC_KEY);
        Cache::forget(self::CIDRS_KEY);
    }
}
