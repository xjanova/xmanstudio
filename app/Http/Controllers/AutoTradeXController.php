<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * AutoTradeX Web Controller
 *
 * Handles web pages for AutoTradeX product (pricing, checkout, etc.)
 */
class AutoTradeXController extends Controller
{
    /**
     * License pricing information
     */
    private const PRICING = [
        'monthly' => [
            'name' => 'Monthly',
            'name_th' => 'รายเดือน',
            'price' => 990,
            'duration_days' => 30,
            'license_type' => 'monthly',
        ],
        'yearly' => [
            'name' => 'Yearly',
            'name_th' => 'รายปี',
            'price' => 7900,
            'duration_days' => 365,
            'license_type' => 'yearly',
        ],
        'lifetime' => [
            'name' => 'Lifetime',
            'name_th' => 'ตลอดชีพ',
            'price' => 19900,
            'duration_days' => null, // Never expires
            'license_type' => 'lifetime',
        ],
    ];

    /**
     * Show pricing page
     *
     * GET /autotradex/pricing
     */
    public function pricing()
    {
        return view('autotradex.pricing');
    }

    /**
     * Show checkout page for specific plan
     *
     * GET /autotradex/checkout/{plan}
     */
    public function checkout(string $plan)
    {
        if (! isset(self::PRICING[$plan])) {
            abort(404, 'Plan not found');
        }

        $planInfo = self::PRICING[$plan];
        $product = Product::where('slug', 'autotradex')->first();

        if (! $product) {
            abort(404, 'Product not found');
        }

        return view('autotradex.checkout', [
            'plan' => $plan,
            'planInfo' => $planInfo,
            'product' => $product,
        ]);
    }

    /**
     * Process checkout
     *
     * POST /autotradex/checkout/{plan}
     */
    public function processCheckout(Request $request, string $plan)
    {
        if (! isset(self::PRICING[$plan])) {
            abort(404, 'Plan not found');
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'payment_method' => 'required|in:promptpay,bank_transfer',
        ]);

        $planInfo = self::PRICING[$plan];
        $product = Product::where('slug', 'autotradex')->firstOrFail();

        // Create order
        $order = Order::create([
            'user_id' => auth()->id(),
            'order_number' => $this->generateOrderNumber(),
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'subtotal' => $planInfo['price'],
            'total' => $planInfo['price'],
            'status' => 'pending',
            'payment_method' => $validated['payment_method'],
            'notes' => "AutoTradeX {$planInfo['name']} License | Plan: {$plan} | Type: {$planInfo['license_type']}",
        ]);

        // Create order item
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $planInfo['price'],
        ]);

        // Redirect to payment page
        return redirect()->route('autotradex.payment', [
            'order' => $order->id,
        ]);
    }

    /**
     * Show payment page
     *
     * GET /autotradex/payment/{order}
     */
    public function payment(Order $order)
    {
        // Verify order belongs to current user or is guest
        if ($order->user_id && auth()->id() !== $order->user_id) {
            abort(403);
        }

        $metadata = json_decode($order->metadata ?? '{}', true);
        $plan = $metadata['plan'] ?? 'monthly';
        $planInfo = self::PRICING[$plan] ?? self::PRICING['monthly'];

        return view('autotradex.payment', [
            'order' => $order,
            'planInfo' => $planInfo,
        ]);
    }

    /**
     * Redirect from app to purchase page
     *
     * GET /autotradex/buy
     * GET /autotradex/buy?plan=yearly
     * GET /autotradex/buy?machine_id=xxx
     */
    public function buyRedirect(Request $request)
    {
        $plan = $request->query('plan', 'yearly'); // Default to yearly (best value)
        $machineId = $request->query('machine_id');

        // Store machine_id in session for later use
        if ($machineId) {
            session(['autotradex_machine_id' => $machineId]);
        }

        // If specific plan requested, go to checkout
        if (in_array($plan, ['monthly', 'yearly', 'lifetime'])) {
            return redirect()->route('autotradex.checkout', $plan);
        }

        // Otherwise show pricing page
        return redirect()->route('autotradex.pricing');
    }

    /**
     * Confirm payment with slip upload
     *
     * POST /autotradex/payment/{order}/confirm
     */
    public function confirmPayment(Request $request, Order $order)
    {
        // Verify order belongs to current user or is guest
        if ($order->user_id && auth()->id() !== $order->user_id) {
            abort(403);
        }

        // Verify order is still pending
        if ($order->status !== 'pending') {
            return back()->with('error', 'คำสั่งซื้อนี้ได้รับการดำเนินการแล้ว');
        }

        $validated = $request->validate([
            'payment_slip' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'notes' => 'nullable|string|max:500',
        ]);

        // Store the payment slip
        $slipPath = $request->file('payment_slip')->store('payment-slips/autotradex', 'public');

        // Update order with payment info
        $metadata = json_decode($order->metadata ?? '{}', true);
        $metadata['payment_slip'] = $slipPath;
        $metadata['payment_submitted_at'] = now()->toISOString();
        $metadata['payment_notes'] = $validated['notes'] ?? null;

        $order->update([
            'status' => 'processing',
            'metadata' => json_encode($metadata),
        ]);

        return redirect()->route('autotradex.payment-success', $order->id);
    }

    /**
     * Payment success page
     *
     * GET /autotradex/payment/{order}/success
     */
    public function paymentSuccess(Order $order)
    {
        // Verify order belongs to current user or is guest
        if ($order->user_id && auth()->id() !== $order->user_id) {
            abort(403);
        }

        $metadata = json_decode($order->metadata ?? '{}', true);
        $plan = $metadata['plan'] ?? 'monthly';
        $planInfo = self::PRICING[$plan] ?? self::PRICING['monthly'];

        return view('autotradex.payment-success', [
            'order' => $order,
            'planInfo' => $planInfo,
        ]);
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

    /**
     * Show Reset Device page for Lifetime license holders
     *
     * GET /autotradex/reset-device
     */
    public function resetDevicePage()
    {
        return view('products.autotradex-reset-device');
    }
}
