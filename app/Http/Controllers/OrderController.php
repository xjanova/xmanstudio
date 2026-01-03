<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ThaiPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(
        protected ThaiPaymentService $paymentService
    ) {}

    /**
     * Display checkout page
     */
    public function checkout()
    {
        $cart = $this->getCart();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'ตะกร้าว่างเปล่า');
        }

        $cart->load('items.product');
        $paymentMethods = $this->paymentService->getSupportedMethods();

        return view('orders.checkout', compact('cart', 'paymentMethods'));
    }

    /**
     * Create order from cart
     */
    public function store(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:promptpay,bank_transfer,credit_card',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
        ]);

        $cart = $this->getCart();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'ตะกร้าว่างเปล่า');
        }

        $cart->load('items.product');

        try {
            DB::beginTransaction();

            // Calculate totals
            $subtotal = $cart->items->sum(fn ($item) => $item->price * $item->quantity);

            // Create order
            $order = Order::create([
                'user_id' => auth()->id(),
                'order_number' => $this->generateOrderNumber(),
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'subtotal' => $subtotal,
                'discount' => 0,
                'total' => $subtotal,
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'status' => 'pending',
                'notes' => $request->notes,
            ]);

            // Create order items
            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'product_name' => $cartItem->product->name,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                    'total' => $cartItem->price * $cartItem->quantity,
                ]);
            }

            // Clear cart
            $cart->items()->delete();

            DB::commit();

            return redirect()
                ->route('orders.show', $order)
                ->with('success', 'สร้างคำสั่งซื้อเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();

            // Log the actual error for debugging
            \Illuminate\Support\Facades\Log::error('Order creation failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return generic error message to user (don't expose internal details)
            return redirect()
                ->back()
                ->with('error', 'เกิดข้อผิดพลาดในการสร้างคำสั่งซื้อ กรุณาลองใหม่อีกครั้ง');
        }
    }

    /**
     * Display order details
     */
    public function show(Order $order)
    {
        // Verify ownership
        if (auth()->id() && $order->user_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            abort(403);
        }

        $order->load('items.product');

        // Get payment info based on method
        $paymentInfo = null;
        if ($order->payment_status === 'pending') {
            if ($order->payment_method === 'promptpay') {
                $paymentInfo = $this->paymentService->generatePromptPayQR(
                    $order->total,
                    $order->order_number
                );
            } elseif ($order->payment_method === 'bank_transfer') {
                $paymentInfo = $this->paymentService->getBankTransferInfo();
            }
        }

        return view('orders.show', compact('order', 'paymentInfo'));
    }

    /**
     * Confirm payment (upload slip)
     */
    public function confirmPayment(Request $request, Order $order)
    {
        if ($order->payment_status !== 'pending') {
            return redirect()
                ->back()
                ->with('error', 'คำสั่งซื้อนี้ไม่อยู่ในสถานะรอชำระเงิน');
        }

        $request->validate([
            'payment_slip' => 'required|image|max:5120',
        ]);

        // Store payment slip
        $path = $request->file('payment_slip')->store('payment-slips', 'public');

        $order->update([
            'payment_slip' => $path,
            'payment_status' => 'verifying',
        ]);

        return redirect()
            ->back()
            ->with('success', 'อัพโหลดสลิปเรียบร้อยแล้ว รอการตรวจสอบ');
    }

    /**
     * Download license keys (after payment confirmed)
     */
    public function download(Order $order)
    {
        // Verify ownership and payment status
        if (auth()->id() && $order->user_id !== auth()->id()) {
            abort(403);
        }

        if ($order->payment_status !== 'paid') {
            return redirect()
                ->back()
                ->with('error', 'คำสั่งซื้อยังไม่ได้รับการชำระเงิน');
        }

        // Get license keys for this order
        $licenses = LicenseKey::where('order_id', $order->id)->get();

        if ($licenses->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', 'ไม่พบ License Keys สำหรับคำสั่งซื้อนี้');
        }

        // Generate download content
        $content = "Order: {$order->order_number}\n";
        $content .= "Date: {$order->created_at->format('d/m/Y H:i')}\n\n";
        $content .= "License Keys:\n";
        $content .= str_repeat('-', 50)."\n";

        foreach ($licenses as $license) {
            $content .= "{$license->license_key} ({$license->license_type})\n";
        }

        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', "attachment; filename=\"licenses-{$order->order_number}.txt\"");
    }

    /**
     * Get cart for current session/user
     */
    protected function getCart(): ?Cart
    {
        $userId = auth()->id();
        $sessionId = session()->getId();

        if ($userId) {
            return Cart::where('user_id', $userId)->first();
        }

        return Cart::where('session_id', $sessionId)->first();
    }

    /**
     * Generate unique order number
     */
    protected function generateOrderNumber(): string
    {
        $prefix = 'XM'.date('Ymd');
        $random = strtoupper(Str::random(4));

        return $prefix.'-'.$random;
    }
}
