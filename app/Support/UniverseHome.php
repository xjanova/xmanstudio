<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Foundation\ViteException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;

/**
 * XMAN Universe — the full-3D home page, and who gets it.
 *
 * The universe is a layer over the site theme, not a theme of its own. A browser that can
 * run it gets home-universe.blade.php; everyone else gets the theme's own home page exactly
 * as before (HomeController picks it). "Can run it" is decided twice:
 *
 *   1. here, on the server: the admin switch (/admin/theme), an explicit ?view=classic, the
 *      visitor's own earlier choice (cookie), crawlers, which index the theme's page
 *      instead (same content, same links, no WebGL needed to read it), and whether the
 *      current front-end build has the page at all (see assetsBuilt());
 *   2. in the browser, by partials/universe/detect.blade.php before anything paints: WebGL2
 *      on real graphics hardware, no reduced-motion preference, enough memory. A device that
 *      fails goes to ?view=classic and the cookie remembers it for a week, so its next visit
 *      is served the classic page straight away instead of bouncing through a redirect.
 *
 * The cookie is written by JavaScript, so it is excluded from Laravel's cookie encryption in
 * bootstrap/app.php — an encrypted-cookie middleware would otherwise throw its value away.
 */
class UniverseHome
{
    public const SETTING = 'home_universe';

    public const COOKIE = 'xu_mode';

    /** The visitor asked for the 3D home (the "3D Universe" pill on the classic page). */
    public const MODE_UNIVERSE = 'universe';

    /** The visitor asked for the classic home (the "Classic view" link inside the universe). */
    public const MODE_CLASSIC = 'classic';

    /** The browser check sent this device to the classic home; expires after a week. */
    public const MODE_LITE = 'lite';

    /** The page's own Vite entry points (vite.config.js). */
    private const ENTRIES = ['resources/css/universe.css', 'resources/js/universe/main.js'];

    /**
     * Crawlers and link-preview fetchers. Listed by name rather than matched on a bare "bot":
     * phone models such as "CUBOT" sit in ordinary Android user agents.
     */
    private const CRAWLERS = '/googlebot|google-inspectiontool|storebot-google|adsbot-google|mediapartners-google'
        . '|bingbot|bingpreview|yandex|baiduspider|duckduckbot|slurp|applebot|petalbot|bytespider|sogou'
        . '|semrushbot|ahrefsbot|mj12bot|dotbot|gptbot|claudebot|ccbot|perplexitybot|amazonbot'
        . '|facebookexternalhit|facebookcatalog|meta-externalagent|twitterbot|linkedinbot|pinterestbot|redditbot'
        . '|slackbot|discordbot|telegrambot|whatsapp|skypeuripreview|embedly|line-poker|kakaotalk-scrap'
        . '|chrome-lighthouse|headlesschrome|crawler|spider/i';

    public static function enabled(): bool
    {
        return (bool) Setting::getValue(self::SETTING, true);
    }

    public static function setEnabled(bool $enabled): void
    {
        Setting::setValue(self::SETTING, $enabled ? '1' : '0', 'boolean', 'appearance', 'XMAN Universe — full-3D home page');
    }

    /**
     * Should this request get the 3D home page rather than the theme's own?
     */
    public static function shouldServe(Request $request): bool
    {
        if (! self::enabled()) {
            return false;
        }

        if ($request->query('view') === 'classic') {
            return false;
        }

        if (in_array($request->cookie(self::COOKIE), [self::MODE_CLASSIC, self::MODE_LITE], true)) {
            return false;
        }

        return ! self::isCrawler((string) $request->userAgent()) && self::assetsBuilt();
    }

    /**
     * Is the universe in the current front-end build?
     *
     * A deploy puts the new views live (git reset) minutes before `npm run build` writes the
     * manifest that knows these entries, and a build that fails leaves the old manifest for
     * good. @vite throws on an entry it cannot find, so without this check the home page
     * would answer 500 through every deploy that ships a change to it.
     */
    public static function assetsBuilt(): bool
    {
        try {
            foreach (self::ENTRIES as $entry) {
                Vite::asset($entry);
            }

            return true;
        } catch (ViteException) {
            return false;
        }
    }

    public static function isCrawler(string $userAgent): bool
    {
        return $userAgent !== '' && preg_match(self::CRAWLERS, $userAgent) === 1;
    }

    /**
     * The theme's home page, whatever the cookie says. Also where a browser that cannot run the
     * universe is sent.
     */
    public static function classicUrl(): string
    {
        return rtrim(url('/'), '/') . '/?view=classic';
    }
}
