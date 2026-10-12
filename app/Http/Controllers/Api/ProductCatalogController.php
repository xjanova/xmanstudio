<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
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

        return response()
            ->json(['success' => true, 'products' => $products])
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
            'image_url' => $image !== null && str_starts_with($image, '/') ? url($image) : $image,
            'real_image' => $product->hasRealImage(),
            'url' => route('products.show', $product->slug),
            'version' => $status === 'off_sale' ? null : $product->latestVersion()?->version,
            'download_url' => $status === 'on_sale' && $route !== null && Route::has($route) ? route($route) : null,
        ];
    }
}
