<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Services\LicenseService;
use App\Support\CloudStorage;
use App\Support\ComingSoonPurchase;
use App\Support\DgxSparkCampaign;
use App\Support\LicensePlans;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Display the cart
     */
    public function index()
    {
        $cart = $this->getOrCreateCart();
        $cart->load('items.product');

        return view('cart.index', compact('cart'));
    }

    /**
     * Add product to cart
     */
    public function add(Request $request, Product $product)
    {
        // A product switched off in the admin is off sale — its page is already a
        // 404, and a hand-made POST must not buy it either.
        abort_unless($product->is_active, 404);

        // Coming Soon is not on sale yet: its page shows the label instead of a buy button, and a
        // hand-made POST must not buy it either (owner, 2026-10-12).
        // ...except renewing a key the customer already holds, where the admin allows it meanwhile
        // (the product's renew_while_coming_soon switch)
        $renewsOwnKey = $request->filled('license_type') && $request->filled('renew_license')
            && $product->acceptsRenewals()
            && app(LicenseService::class)->renewalTargetFor(auth()->id(), $product, $request->integer('renew_license'))?->id === $request->integer('renew_license');

        if (! $renewsOwnKey && ($refused = ComingSoonPurchase::refusal($product))) {
            return $refused;
        }

        // The DGX Spark bundle is sold only through its own page: the 20-set cap, VAT included in
        // its price, bank transfer only, no coupon or commission. Even if someone switches the
        // product on in the admin, the cart must not become a way around all of that.
        if (DgxSparkCampaign::isCampaignProduct($product)) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'สั่งชุดนี้ได้ที่หน้าแคมเปญเท่านั้น'], 422)
                : redirect()->route('campaign.dgx-spark');
        }

        // A cloud space pack (BrainX Cloud — พื้นที่เพิ่ม) is bought for one of the customer's own keys
        if ($forProduct = CloudStorage::productForAddon($product->slug)) {
            return $this->addStorageAddon($request, $product, $forProduct);
        }

        $request->validate([
            'quantity' => 'integer|min:1|max:99',
            'license_type' => 'nullable|in:monthly,yearly,lifetime',
            'price' => 'nullable|numeric|min:0',
            'renew_license' => 'nullable|integer',
        ]);

        $cart = $this->getOrCreateCart();
        $quantity = $request->input('quantity', 1);
        $price = $product->price;

        // Build custom requirements with license_type if provided
        $customRequirements = null;
        if ($request->filled('license_type') && $product->requires_license) {
            $licenseType = $request->license_type;

            // "ต่ออายุ" on one of the customer's own keys names that key, so the payment extends
            // it even when they hold more than one. Anyone else's key, or one that can no longer
            // be renewed, is ignored here — and checked again when the order is paid.
            $renewKeyId = null;
            if ($request->filled('renew_license')) {
                $keyId = $request->integer('renew_license');
                $target = app(LicenseService::class)->renewalTargetFor(auth()->id(), $product, $keyId);

                if ($target && $target->id === $keyId) {
                    $renewKeyId = $keyId;
                }
            }

            // A product with a checkout of its own (Tping, AutoTradeX …) is not sold here — that
            // checkout binds the new key to the app's machine. Renewing a key the customer already
            // holds binds nothing new, so the cart takes it.
            $termPrice = LicensePlans::soldInCart($product->slug) || $renewKeyId !== null
                ? LicensePlans::price($product->slug, $licenseType)
                : null;

            // Only a term this product is sold by in the cart, at the price in
            // config/licenses.php — the same number its page shows. The form's own
            // `price` field is ignored: it used to be charged as sent.
            if ($termPrice === null) {
                $message = 'แพ็กเกจนี้ไม่มีจำหน่ายสำหรับสินค้านี้';

                return $request->wantsJson()
                    ? response()->json(['success' => false, 'message' => $message], 422)
                    : redirect()->back()->with('error', $message);
            }

            $price = $termPrice;
            $requirements = ['license_type' => $licenseType];

            if ($renewKeyId !== null) {
                $requirements['renew_license_id'] = $renewKeyId;
            }

            $customRequirements = json_encode($requirements);
        }

        // Check if product already in cart (replace if different license_type)
        $cartItem = $cart->items()->where('product_id', $product->id)->first();

        if ($cartItem) {
            $update = ['quantity' => $cartItem->quantity + $quantity];
            if ($customRequirements) {
                $update['custom_requirements'] = $customRequirements;
                $update['price'] = $price;
            }
            $cartItem->update($update);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $price,
                'custom_requirements' => $customRequirements,
            ]);
        }

        return $this->added($request, $product, $cart);
    }

    /**
     * Add a cloud space pack (config/licenses.php → cloud_storage.*.addons) for one key the
     * customer holds. The pack's price comes from the config, never the form. The key must be
     * theirs, of the product the pack is for, and not revoked; the space already on it, plus
     * what the cart already holds for it, must leave room under max_extra_gb. The payment
     * checks the key again before adding anything (LicenseService::deliverStorageAddon()).
     *
     * One cart line per key and pack size, so a +4 and a +12 for the same key are two lines.
     */
    protected function addStorageAddon(Request $request, Product $addon, string $forProduct)
    {
        if (! auth()->check()) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อนซื้อพื้นที่เพิ่ม'], 401)
                : redirect()->guest(route('login'))->with('error', 'กรุณาเข้าสู่ระบบก่อนซื้อพื้นที่เพิ่ม');
        }

        $request->validate([
            'quantity' => 'integer|min:1|max:99',
            'storage_gb' => 'required|integer',
            'license' => 'required|integer',
        ]);

        $refuse = fn (string $message) => $request->wantsJson()
            ? response()->json(['success' => false, 'message' => $message], 422)
            : redirect()->back()->with('error', $message);

        $packGb = $request->integer('storage_gb');
        $price = CloudStorage::addonPrice($forProduct, $packGb);
        if ($price === null) {
            return $refuse('ไม่มีแพ็กพื้นที่ขนาดนี้');
        }

        $license = LicenseKey::with('product')
            ->whereKey($request->integer('license'))
            ->where('user_id', auth()->id())
            ->whereIn('status', [LicenseKey::STATUS_ACTIVE, LicenseKey::STATUS_EXPIRED])
            ->whereHas('product', fn ($query) => $query->whereIn('slug', Product::slugsFor($forProduct)))
            ->first();

        if (! $license) {
            return $refuse('เพิ่มพื้นที่ได้เฉพาะคีย์ของคุณเองที่ยังไม่ถูกยกเลิก');
        }

        $cart = $this->getOrCreateCart();
        $quantity = $request->integer('quantity', 1);
        $room = CloudStorage::roomLeftGb($license) - $this->storageInCart($cart, $addon->id, $license->id);

        if ($packGb * $quantity > $room) {
            return $refuse('คีย์นี้เพิ่มพื้นที่ได้อีก ' . max(0, $room) . ' GB');
        }

        $requirements = json_encode(['storage_gb' => $packGb, 'license_id' => $license->id]);
        $line = $cart->items()
            ->where('product_id', $addon->id)
            ->where('custom_requirements', $requirements)
            ->first();

        if ($line) {
            $line->update(['quantity' => $line->quantity + $quantity, 'price' => $price]);
        } else {
            $cart->items()->create([
                'product_id' => $addon->id,
                'quantity' => $quantity,
                'price' => $price,
                'custom_requirements' => $requirements,
            ]);
        }

        return $this->added($request, $addon, $cart);
    }

    /** GB of space the cart already holds for one key, every pack size, optionally leaving one line out. */
    protected function storageInCart(Cart $cart, int $addonProductId, int $licenseId, ?int $exceptItemId = null): int
    {
        return (int) $cart->items()
            ->where('product_id', $addonProductId)
            ->when($exceptItemId, fn ($query) => $query->whereKeyNot($exceptItemId))
            ->get()
            ->filter(fn (CartItem $item) => (json_decode((string) $item->custom_requirements, true)['license_id'] ?? null) === $licenseId)
            ->sum(fn (CartItem $item) => (CloudStorage::packOf($item->custom_requirements) ?? 0) * $item->quantity);
    }

    protected function added(Request $request, Product $product, Cart $cart)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "เพิ่ม '{$product->name}' ลงตะกร้าแล้ว",
                'cart_count' => $cart->items()->sum('quantity'),
            ]);
        }

        // "ซื้อเลย" (e.g. the WinXTools page the app opens to buy a licence): go straight to the
        // cart to check out, instead of staying on the page with only a flash message.
        if ($request->boolean('buy_now')) {
            return redirect()
                ->route('cart.index')
                ->with('success', "เพิ่ม '{$product->name}' ลงตะกร้าแล้ว");
        }

        return redirect()
            ->back()
            ->with('success', "เพิ่ม '{$product->name}' ลงตะกร้าแล้ว");
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request, CartItem $item)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:99',
        ]);

        // Verify item belongs to user's cart
        $cart = $this->getOrCreateCart();
        if ($item->cart_id !== $cart->id) {
            abort(403);
        }

        // A cloud space line: more packs must still fit under the key's max_extra_gb
        if ($packGb = CloudStorage::packOf($item->custom_requirements)) {
            $licenseId = json_decode($item->custom_requirements, true)['license_id'] ?? null;
            $license = is_int($licenseId) ? LicenseKey::with('product')->find($licenseId) : null;
            $room = $license
                ? CloudStorage::roomLeftGb($license) - $this->storageInCart($cart, $item->product_id, $license->id, $item->id)
                : 0;

            if ($packGb * (int) $request->quantity > $room) {
                $message = 'คีย์นี้เพิ่มพื้นที่ได้อีก ' . max(0, $room) . ' GB';

                return $request->wantsJson()
                    ? response()->json(['success' => false, 'message' => $message], 422)
                    : redirect()->back()->with('error', $message);
            }
        }

        $item->update([
            'quantity' => $request->quantity,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'อัพเดทจำนวนแล้ว',
                'item_total' => $item->quantity * $item->price,
                'cart_total' => $cart->fresh()->total,
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'อัพเดทจำนวนแล้ว');
    }

    /**
     * Remove item from cart
     */
    public function remove(CartItem $item)
    {
        // Verify item belongs to user's cart
        $cart = $this->getOrCreateCart();
        if ($item->cart_id !== $cart->id) {
            abort(403);
        }

        $productName = $item->product->name;
        $item->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "ลบ '{$productName}' ออกจากตะกร้าแล้ว",
                'cart_count' => $cart->items()->sum('quantity'),
            ]);
        }

        return redirect()
            ->back()
            ->with('success', "ลบ '{$productName}' ออกจากตะกร้าแล้ว");
    }

    /**
     * Clear cart
     */
    public function clear()
    {
        $cart = $this->getOrCreateCart();
        $cart->items()->delete();

        return redirect()
            ->route('cart.index')
            ->with('success', 'ล้างตะกร้าแล้ว');
    }

    /**
     * Get or create cart for current session/user
     */
    protected function getOrCreateCart(): Cart
    {
        $userId = auth()->id();
        $sessionId = session()->getId();

        if ($userId) {
            $cart = Cart::where('user_id', $userId)->first();

            if (! $cart) {
                // Check if there's a session cart to migrate
                $sessionCart = Cart::where('session_id', $sessionId)->whereNull('user_id')->first();

                if ($sessionCart) {
                    $sessionCart->update(['user_id' => $userId]);

                    return $sessionCart;
                }

                $cart = Cart::create(['user_id' => $userId]);
            }
        } else {
            $cart = Cart::where('session_id', $sessionId)->first();

            if (! $cart) {
                $cart = Cart::create(['session_id' => $sessionId]);
            }
        }

        return $cart;
    }
}
