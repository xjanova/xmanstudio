<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Product;
use App\Models\SeoSetting;
use App\Models\Service;
use App\Services\AiChat\SiteMap;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * /sitemap.xml, built from the live site on request (cached 30 minutes): every public page the
 * router has, every product on sale, every active service, and every published article.
 *
 * It used to be a file an admin button wrote once (public_html/sitemap.xml, last written
 * 2026-01-21, six addresses), so Google never heard of a product page or anything added
 * since. Nothing writes that file any more — if it exists the web server serves it before
 * Laravel and this page is never reached, so the SEO admin button deletes it.
 */
class SitemapController extends Controller
{
    public const CACHE_KEY = 'sitemap:xml:v1';

    /** Pages that are public but not worth an index entry (forms, sign-in, the cart). */
    private const SKIP_ROUTE = '/^(login|register|password\.|verification\.|logout|cart|checkout|two-factor|setup|og-image|sitemap|robots|articles\.feed)/';

    /** The same by path, for the sign-in, account and lookup pages that other products name their own way. */
    private const SKIP_PATH = '#/(login|register|signup|dashboard|reset-device|track|tracking)(/|$)#';

    public function index(): Response
    {
        if (! SeoSetting::getInstance()->sitemap_enabled) {
            abort(404);
        }

        $xml = Cache::remember(self::CACHE_KEY, now()->addMinutes(30), fn () => $this->build());

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=1800',
        ]);
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function build(): string
    {
        $urls = [];
        $add = function (string $loc, $lastmod = null, string $freq = 'weekly', string $priority = '0.6') use (&$urls) {
            $urls[$loc] = [
                'loc' => $loc,
                'lastmod' => $lastmod ? Carbon::parse($lastmod)->toAtomString() : null,
                'changefreq' => $freq,
                'priority' => $priority,
            ];
        };

        $add(url('/'), now()->startOfDay(), 'daily', '1.0');

        foreach (app(SiteMap::class)->crawlable() as $path => $name) {
            if ($path === '/' || $path === '' || preg_match(self::SKIP_ROUTE, $name) || preg_match(self::SKIP_PATH, '/' . ltrim($path, '/'))) {
                continue;
            }
            $priority = in_array($name, ['products.index', 'services.index', 'campaign.dgx-spark', 'articles.index'], true) ? '0.9' : '0.6';
            $add(url($path), null, in_array($name, ['articles.index', 'campaign.dgx-spark'], true) ? 'daily' : 'weekly', $priority);
        }

        Product::query()->onWebsite()->where('is_active', true)->get()
            ->each(fn (Product $p) => $add(route('products.show', $p->slug), $p->updated_at, 'weekly', '0.8'));

        Service::query()->where('is_active', true)->get()
            ->each(fn (Service $s) => $add(route('services.show', $s->slug), $s->updated_at, 'monthly', '0.7'));

        Article::published()->orderByDesc('published_at')->get(['slug', 'updated_at', 'published_at'])
            ->each(fn (Article $a) => $add(route('articles.show', $a->slug), $a->updated_at ?? $a->published_at, 'monthly', '0.7'));

        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $u) {
            $out .= "  <url>\n    <loc>" . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
            if ($u['lastmod']) {
                $out .= '    <lastmod>' . $u['lastmod'] . "</lastmod>\n";
            }
            $out .= '    <changefreq>' . $u['changefreq'] . "</changefreq>\n    <priority>" . $u['priority'] . "</priority>\n  </url>\n";
        }

        return $out . '</urlset>' . "\n";
    }
}
