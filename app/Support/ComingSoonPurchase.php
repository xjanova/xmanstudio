<?php

namespace App\Support;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A product still Coming Soon cannot be bought from any path (owner, 2026-10-12: "สินค้าไหน coming soon
 * ต้องยังดาวโหลดไม่ได้ ซื้อไม่ได้"). The downloads side is DownloadUnavailableException::comingSoon().
 *
 * Every page that starts a checkout and every POST that creates an order asks here first, so a
 * hand-made request is refused even where the button is already hidden. Orders paid before the
 * product turned Coming Soon, and the licences they issued, are not touched.
 *
 * Once coming_soon_until has passed Product::isComingSoon() turns false and sales open by themselves.
 */
class ComingSoonPurchase
{
    public const MESSAGE = 'สินค้านี้ยังไม่เปิดขาย (Coming Soon) / This product is not on sale yet';

    /** /products/{slug} only redirects on for these, and a flash does not survive the second hop. */
    private const OWN_PAGES = [
        'tping' => 'tping.detail',
        'smschecker' => 'smschecker.detail',
        'chanthra-studio' => 'chanthra-studio.detail',
    ];

    /**
     * The answer to send instead of selling, or null when the product is on sale.
     *
     * @param  ?string  $backTo  where a browser goes back to — the product's own page when null
     */
    public static function refusal(?Product $product, ?string $backTo = null): ?Response
    {
        if (! $product?->isComingSoon()) {
            return null;
        }

        return self::respond(self::MESSAGE, $backTo ?? self::pageOf($product));
    }

    /**
     * Checkout of the cart: a product can turn Coming Soon, or be switched off in the admin, after
     * it went in — CartController::add refuses both. The customer is sent back to the cart with the
     * names, to take them out themselves — nothing is removed for them.
     */
    public static function refusalForCart(Cart $cart): ?Response
    {
        $names = $cart->items
            ->filter(fn ($item) => $item->product && ! $item->product->isAvailable())
            ->map(fn ($item) => $item->product->name)
            ->unique()
            ->implode(', ');

        if ($names === '') {
            return null;
        }

        return self::respond(
            "สินค้า {$names} ไม่ได้เปิดขายอยู่ (Coming Soon หรือปิดการขาย) กรุณานำออกจากตะกร้าก่อนชำระเงิน"
                . " / {$names} is not on sale, please remove it from your cart before checking out",
            route('cart.index'),
        );
    }

    /** The page that shows the product's Coming Soon label. */
    public static function pageOf(Product $product): string
    {
        // /products/{slug} is a 404 for a product switched off in the admin
        if (! $product->is_active) {
            return route('products.index');
        }

        $route = self::OWN_PAGES[$product->slug] ?? null;

        return $route ? route($route) : route('products.show', $product->slug);
    }

    /** An app or a script gets JSON it can read; a browser goes back to a page, with the message. */
    private static function respond(string $message, string $backTo): Response
    {
        /** @var Request $request */
        $request = request();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'error' => 'Coming soon: this product is not on sale yet',
                'error_code' => 'COMING_SOON',
                'coming_soon' => true,
                'message' => $message,
            ], 403);
        }

        return redirect()->to($backTo)->with('error', $message);
    }
}
