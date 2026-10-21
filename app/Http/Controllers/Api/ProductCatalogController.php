<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\TpixPay\TpixPriceService;
use App\Support\LicensePlans;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * The shop's products as product.xman4289.com reads them (owner, 2026-10-12: "เว็บนี้ต้องเชื่อมต่อ
 * ด้วยกันทั้งหมด"): whether each is on sale, its picture and its latest version, so the hub shows
 * what this site shows.
 *
 * `real_image` says the picture is the real one set in the admin. When it is false, `image_url` is
 * only the default art, and the hub keeps its own (which is where that default came from).
 * `download_url` is the public download of an app on sale — never one of a Coming Soon product.
 *
 * TPIX (added 2026-10-13, additive): `price_tpix` / `plans_tpix` are the same prices in TPIX at the
 * live TPIX DEX price (or the admin's fixed TPIX price for the product's own price), and the top-level
 * `tpix` block says at what rate. All null/absent while TPIX prices are switched off or unavailable.
 * Worked out after the cache, so a cached list never carries a stale conversion.
 */
class ProductCatalogController extends Controller
{
    public const CACHE_KEY = 'api.v1.catalog';

    public function index(): JsonResponse
    {
        $products = Cache::remember(self::CACHE_KEY, 120, fn () => Product::onWebsite()
            ->orderBy('id')
            ->get()
            ->map(fn (Product $product) => $this->entry($product))
            ->values()
            ->all());

        $tpix = app(TpixPriceService::class);
        $products = array_map(function (array $entry) use ($tpix) {
            $fixed = $entry['price_tpix_fixed'] ?? null;
            unset($entry['price_tpix_fixed']);
            $price = $tpix->apiPrice($entry['price'] ?? null, $fixed);

            return $entry + [
                'price_tpix' => $price,
                'price_tpix_is_fixed' => $fixed !== null && $price !== null,
                'plans_tpix' => (object) array_filter(array_map(
                    fn ($thb) => $tpix->apiPrice($thb),
                    (array) ($entry['plans'] ?? []),
                ), fn ($v) => $v !== null),
            ];
        }, $products);

        return response()
            // plans: the shop's one price table (config/licenses.php) — the hub fills every price it
            // shows from it, including BrainX Cloud's, which has no product row of its own.
            ->json(['success' => true, 'plans' => (object) array_map(fn ($terms) => (object) $terms, config('licenses.plans', [])), 'tpix' => $tpix->apiRate(), 'products' => $products])
            ->header('Cache-Control', 'public, max-age=300');
    }

    /** @return array<string, mixed> */
    private function entry(Product $product): array
    {
        $status = match (true) {
            ! $product->is_active => 'off_sale',
            $product->isComingSoon() => 'coming_soon',
            default => 'on_sale',
        };

        $image = $product->artwork_url;
        $route = config('downloads.app_routes', [])[$product->slug] ?? null;

        return [
            'slug' => $product->slug,
            'name' => $product->name,
            'status' => $status,
            'price' => (float) $product->price,
            // The admin's fixed TPIX price; turned into price_tpix after the cache (see index())
            'price_tpix_fixed' => $product->price_tpix !== null && (float) $product->price_tpix > 0 ? (string) $product->price_tpix : null,
            // The license terms it sells and their prices; {} when it sells none (an empty array would be []).
            'plans' => (object) LicensePlans::for($product->slug),
            'image_url' => $image !== null && str_starts_with($image, '/') ? url($image) : $image,
            'real_image' => $product->hasRealImage(),
            'url' => route('products.show', $product->slug),
            'version' => $status === 'off_sale' ? null : $product->latestVersion()?->version,
            'download_url' => $status === 'on_sale' && $route !== null && Route::has($route) ? route($route) : null,
        ];
    }
}
