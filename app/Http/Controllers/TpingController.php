<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Order;
use App\Models\Product;
use App\Services\ThaiPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Tping Web Controller
 *
 * Handles web pages for Tping product (pricing, checkout, payment).
 */
class TpingController extends Controller
{
    /**
     * License pricing information
     */
    private const PRICING = [
        'monthly' => [
            'name' => 'Monthly',
            'name_th' => 'รายเดือน',
            'price' => 399,
            'duration_days' => 30,
            'license_type' => 'monthly',
            'features' => [
                'ใช้งานทุกฟีเจอร์',
                'Cloud Sync',
                'ซัพพอร์ตมาตรฐาน',
            ],
        ],
        'yearly' => [
            'name' => 'Yearly',
            'name_th' => 'รายปี',
            'price' => 2500,
            'duration_days' => 365,
            'license_type' => 'yearly',
            'features' => [
                'ใช้งานทุกฟีเจอร์',
                'Cloud Sync',
                'ซัพพอร์ตพรีเมียม',
                'อัพเดทก่อนใคร',
            ],
        ],
        'lifetime' => [
            'name' => 'Lifetime',
            'name_th' => 'ตลอดชีพ',
            'price' => 5000,
            'duration_days' => null,
            'license_type' => 'lifetime',
            'features' => [
                'ใช้งานทุกฟีเจอร์',
                'Cloud Sync',
                'ซัพพอร์ตพรีเมียม',
                'อัพเดทตลอดชีพ',
                'ใช้ได้หลายเครื่อง',
            ],
        ],
    ];

    /**
     * Show pricing page
     *
     * GET /tping/pricing
     */
    public function pricing(Request $request)
    {
        $machineId = $request->query('machine_id') ?? session('tping_machine_id');

        if ($machineId) {
            session(['tping_machine_id' => $machineId]);
        }

        return view('tping.pricing', [
            'machineId' => $machineId,
            'pricing' => self::PRICING,
        ]);
    }

    /**
     * Show checkout page for specific plan
     *
     * GET /tping/checkout/{plan}
     */
    public function checkout(Request $request, string $plan)
    {
        if (! isset(self::PRICING[$plan])) {
            abort(404, 'Plan not found');
        }

        $product = Product::where('slug', 'tping')->first();

        if (! $product) {
            abort(404, 'Product not found');
        }

        $machineId = $request->query('machine_id') ?? session('tping_machine_id');
        $planInfo = self::PRICING[$plan];

        return view('tping.checkout', [
            'plan' => $plan,
            'planInfo' => $planInfo,
            'product' => $product,
            'machineId' => $machineId,
        ]);
    }

    /**
     * Process checkout
     *
     * POST /tping/checkout/{plan}
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
            'machine_id' => 'nullable|string|max:64',
        ]);

        $machineId = $validated['machine_id'] ?? session('tping_machine_id');
        $product = Product::where('slug', 'tping')->firstOrFail();
        $planInfo = self::PRICING[$plan];
        $finalPrice = $planInfo['price'];

        $metadata = [
            'plan' => $plan,
            'license_type' => $planInfo['license_type'],
            'machine_id' => $machineId,
        ];

        $notes = "Tping {$planInfo['name']} License | Plan: {$plan} | Type: {$planInfo['license_type']}";

        $order = Order::create([
            'user_id' => auth()->id(),
            'order_number' => $this->generateOrderNumber(),
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'subtotal' => $finalPrice,
            'discount' => 0,
            'total' => $finalPrice,
            'status' => 'pending',
            'payment_method' => $validated['payment_method'],
            'notes' => $notes,
            'metadata' => json_encode($metadata),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'price' => $finalPrice,
            'subtotal' => $finalPrice,
        ]);

        return redirect()->route('tping.payment', [
            'order' => $order->id,
        ]);
    }

    /**
     * Show payment page
     *
     * GET /tping/payment/{order}
     */
    public function payment(Order $order)
    {
        if ($order->user_id && auth()->id() !== $order->user_id) {
            abort(403);
        }

        $metadata = json_decode($order->metadata ?? '{}', true);
        $plan = $metadata['plan'] ?? 'monthly';
        $planInfo = self::PRICING[$plan] ?? self::PRICING['monthly'];

        $paymentService = app(ThaiPaymentService::class);
        $paymentInfo = null;
        $bankAccounts = null;

        if ($order->payment_method === 'promptpay') {
            $paymentInfo = $paymentService->generatePromptPayQR(
                $order->total,
                (string) $order->id
            );
        } elseif ($order->payment_method === 'bank_transfer') {
            $paymentInfo = $paymentService->getBankTransferInfo();
            $bankAccounts = BankAccount::active()->ordered()->get();
        }

        return view('tping.payment', [
            'order' => $order,
            'planInfo' => $planInfo,
            'paymentInfo' => $paymentInfo,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    /**
     * Confirm payment with slip upload
     *
     * POST /tping/payment/{order}/confirm
     */
    public function confirmPayment(Request $request, Order $order)
    {
        if ($order->user_id && auth()->id() !== $order->user_id) {
            abort(403);
        }

        if ($order->status !== 'pending') {
            return back()->with('error', 'คำสั่งซื้อนี้ได้รับการดำเนินการแล้ว');
        }

        $validated = $request->validate([
            'payment_slip' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'notes' => 'nullable|string|max:500',
        ]);

        $slipPath = $request->file('payment_slip')->store('payment-slips/tping', 'public');

        $metadata = json_decode($order->metadata ?? '{}', true);
        $metadata['payment_slip'] = $slipPath;
        $metadata['payment_submitted_at'] = now()->toISOString();
        $metadata['payment_notes'] = $validated['notes'] ?? null;

        $order->update([
            'status' => 'processing',
            'metadata' => json_encode($metadata),
        ]);

        return redirect()->route('tping.payment-success', $order->id);
    }

    /**
     * Payment success page
     *
     * GET /tping/payment/{order}/success
     */
    public function paymentSuccess(Order $order)
    {
        if ($order->user_id && auth()->id() !== $order->user_id) {
            abort(403);
        }

        $metadata = json_decode($order->metadata ?? '{}', true);
        $plan = $metadata['plan'] ?? 'monthly';
        $planInfo = self::PRICING[$plan] ?? self::PRICING['monthly'];

        return view('tping.payment-success', [
            'order' => $order,
            'planInfo' => $planInfo,
        ]);
    }

    /**
     * Redirect from app to purchase page
     *
     * GET /tping/buy
     * GET /tping/buy?plan=yearly
     * GET /tping/buy?machine_id=xxx
     */
    public function buyRedirect(Request $request)
    {
        $plan = $request->query('plan');
        $machineId = $request->query('machine_id');

        if ($machineId) {
            session(['tping_machine_id' => $machineId]);
        }

        $queryParams = $machineId ? ['machine_id' => $machineId] : [];

        if ($plan && in_array($plan, ['monthly', 'yearly', 'lifetime'])) {
            $url = route('tping.checkout', $plan);
            if ($machineId) {
                $url .= '?machine_id=' . $machineId;
            }

            return redirect($url);
        }

        $url = route('tping.pricing');
        if ($machineId) {
            $url .= '?machine_id=' . $machineId;
        }

        return redirect($url);
    }

    protected function generateOrderNumber(): string
    {
        $prefix = 'TP' . date('Ymd');
        $random = strtoupper(Str::random(4));

        return $prefix . '-' . $random;
    }
}
