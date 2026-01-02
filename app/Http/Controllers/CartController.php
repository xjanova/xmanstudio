<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
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
        $request->validate([
            'quantity' => 'integer|min:1|max:99',
        ]);

        $cart = $this->getOrCreateCart();
        $quantity = $request->input('quantity', 1);

        // Check if product already in cart
        $cartItem = $cart->items()->where('product_id', $product->id)->first();

        if ($cartItem) {
            $cartItem->update([
                'quantity' => $cartItem->quantity + $quantity,
            ]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->price,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "เพิ่ม '{$product->name}' ลงตะกร้าแล้ว",
                'cart_count' => $cart->items()->sum('quantity'),
            ]);
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
