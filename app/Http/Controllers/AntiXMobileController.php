<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesReleaseDownloads;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Anti X Mobile (Android) — the public APK download.
 *
 * The phone companion of Anti X is free for everyone, bought Anti X or not (owner, 2026-10-21): no session,
 * no key. The app takes its FREE licence by itself on first start (check-machine, FREEMIUM_PRODUCTS) and its
 * updater fetches the exact {version} that update/check announced with its sha256, from here.
 *
 * ⚠️ The download is tied to anti-x-mobile on purpose, never /{slug}/download — that would become a back
 *    door to download products that must be bought first.
 * ⚠️ Releases live in a private repository: the APK is streamed from xman4289.com with the product's
 *    token, never a redirect to GitHub (owner's rule 2026-09-24). The updater refuses redirects anyway.
 */
class AntiXMobileController extends Controller
{
    use ServesReleaseDownloads;

    private const PRODUCT_SLUG = 'anti-x-mobile';

    /**
     * GET /anti-x-mobile/download/{version?} — anyone.
     *
     * {version} = exactly the build update/check announced with its sha256 · none = the latest.
     */
    public function download(Request $request, ?string $version = null)
    {
        $product = Product::where('slug', self::PRODUCT_SLUG)
            ->where('is_active', true)
            ->first();

        // The mobile section lives on the Anti X page — that is where a browser goes back to
        $backTo = route('products.show', 'anti-x') . '#mobile';

        if (! $product) {
            abort_if($this->isBrowser($request), 404);

            return response()->json(['success' => false, 'error' => 'Product not found'], 404);
        }

        $release = $this->releaseFor($product, $version);

        return $this->serveRelease(
            $request,
            $product,
            $release,
            $backTo,
            // a version made by hand in the admin may not know its file name — same name as the release asset,
            // and .apk so the phone is offered the installer (the streamer picks the type from the extension)
            $release ? "AntiX-Mobile-{$release->version}.apk" : null,
        );
    }
}
