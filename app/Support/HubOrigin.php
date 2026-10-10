<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Which XGamesHub origin a cross-origin JSON answer is for.
 *
 * The hub moved from xgameshub.xman4289.com to xmangameshub.online on
 * 2026-10-10, and both serve the same files while players move across. A
 * browser accepts exactly one Access-Control-Allow-Origin value, so the
 * answer names the caller's origin when it is one of ours, and the current
 * hub otherwise (with Vary: Origin, so a cache never hands one origin's
 * answer to the other).
 */
class HubOrigin
{
    public static function current(): string
    {
        return rtrim((string) config('game-support.hub_origin'), '/');
    }

    /**
     * @return array<int,string>
     */
    public static function allowed(): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($o) => rtrim(trim((string) $o), '/'),
            array_merge([self::current()], (array) config('game-support.legacy_hub_origins', [])),
        ))));
    }

    public static function forRequest(?Request $request = null): string
    {
        $origin = rtrim((string) ($request ?? request())->headers->get('Origin', ''), '/');

        return in_array($origin, self::allowed(), true) ? $origin : self::current();
    }
}
