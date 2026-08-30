<?php

namespace App\Http\Controllers;

use App\Models\AvatarPack;

/**
 * The buy page the GigGok app opens in a browser.
 *
 * The app knows packs by their pack id; the store knows them by product slug.
 * This is the one place those two meet, so the app can link to a pack without
 * knowing anything about products.
 *
 * Paying happens on the web rather than in the app because GigGok ships
 * through GitHub Releases, not Play - Play's billing rules apply to apps on
 * Play. See docs/pack-store.md in the app repo; if it ever goes on Play this
 * needs rethinking, not just moving a button.
 */
class PackShopController extends Controller
{
    /**
     * Send the buyer to the real product page.
     *
     * Deliberately a redirect rather than a second storefront: the product
     * page already has the cart, coupons, every payment method and the
     * license issuing that follows a completed order. A parallel page for
     * packs would be a second thing to keep correct, and would silently miss
     * every fix the real one gets.
     */
    public function show(string $pack)
    {
        $avatarPack = AvatarPack::active()
            ->with('product')
            ->where('pack_id', $pack)
            ->first();

        abort_unless($avatarPack && $avatarPack->product?->is_active, 404);

        return redirect()->route('products.show', $avatarPack->product->slug);
    }
}
