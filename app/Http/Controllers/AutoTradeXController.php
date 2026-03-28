<?php

namespace App\Http\Controllers;

use App\Models\AutoTradeXDevice;
use App\Models\BankAccount;
use App\Models\Order;
use App\Models\Product;
use App\Services\AffiliateCommissionService;
use App\Services\ThaiPaymentService;
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
     * Early bird discount percentage
     */
    private const EARLY_BIRD_DISCOUNT_PERCENT = 20;

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
    public function pricing(Request $request)
    {
        // Get machine_id from query string or session
        $machineId = $request->query('machine_id') ?? session('autotradex_machine_id');

        // Store machine_id in session for checkout flow
        if ($machineId) {
            session(['autotradex_machine_id' => $machineId]);
        }

        // Check for early bird discount eligibility
        $earlyBirdInfo = $this->checkEarlyBirdEligibility($machineId);

        // Calculate discounted prices if eligible
        $pricing = $this->getPricingWithDiscount($earlyBirdInfo);

        return view('autotradex.pricing', [
            'machineId' => $machineId,
            'earlyBird' => $earlyBirdInfo,
            'pricing' => $pricing,
        ]);
    }

    /**
     * Show checkout page for specific plan
     *
     * GET /autotradex/checkout/{plan}
     */
    public function checkout(Request $request, string $plan)
    {
        if (! isset(self::PRICING[$plan])) {
            abort(404, 'Plan not found');
        }

        $product = Product::where('slug', 'autotradex')->first();

        if (! $product) {
            abort(404, 'Product not found');
        }

        // Get machine_id from query string or session
        $machineId = $request->query('machine_id') ?? session('autotradex_machine_id');

        // Check Early Bird eligibility
        $earlyBirdInfo = $this->checkEarlyBirdEligibility($machineId);

        // Get pricing with discount
        $pricing = $this->getPricingWithDiscount($earlyBirdInfo);
        $planInfo = $pricing[$plan];

        return view('autotradex.checkout', [
            'plan' => $plan,
            'planInfo' => $planInfo,
            'product' => $product,
            'machineId' => $machineId,
            'earlyBird' => $earlyBirdInfo,
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
            'machine_id' => 'nullable|string|max:64',
        ]);

        $machineId = $validated['machine_id'] ?? session('autotradex_machine_id');
        $product = Product::where('slug', 'autotradex')->firstOrFail();

        // Check Early Bird eligibility and get discounted pricing
        $earlyBirdInfo = $this->checkEarlyBirdEligibility($machineId);
        $pricing = $this->getPricingWithDiscount($earlyBirdInfo);
        $planInfo = $pricing[$plan];

        // Use discounted price if eligible
        $finalPrice = $planInfo['discounted_price'];
        $originalPrice = $planInfo['original_price'];
        $discountAmount = $planInfo['discount_amount'];

        // Prepare metadata with machine_id, plan info, and discount info
        $metadata = [
            'plan' => $plan,
            'license_type' => $planInfo['license_type'],
            'machine_id' => $machineId,
            'early_bird_applied' => $earlyBirdInfo['eligible'],
            'original_price' => $originalPrice,
            'discount_amount' => $discountAmount,
            'discount_percent' => $earlyBirdInfo['discount_percent'],
        ];

        // Build notes with discount info
        $notes = "AutoTradeX {$planInfo['name']} License | Plan: {$plan} | Type: {$planInfo['license_type']}";
        if ($earlyBirdInfo['eligible']) {
            $notes .= " | Early Bird: -{$earlyBirdInfo['discount_percent']}% (฿{$discountAmount})";
        }

        // Create order
        $order = Order::create([
            'user_id' => auth()->id(),
            'order_number' => $this->generateOrderNumber(),
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'subtotal' => $originalPrice,
            'discount' => $discountAmount,
            'total' => $finalPrice,
            'status' => 'pending',
            'payment_method' => $validated['payment_method'],
            'notes' => $notes,
            'metadata' => json_encode($metadata),
        ]);

        // Create order item
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'price' => $finalPrice,
            'subtotal' => $finalPrice,
        ]);

        // Affiliate commission tracking
        $affiliateService = app(AffiliateCommissionService::class);
        $affiliate = $affiliateService->resolveAffiliate(auth()->id());
        if ($affiliate) {
            $order->update([
                'affiliate_id' => $affiliate->id,
                'referral_code' => $affiliate->referral_code,
            ]);
            $affiliateService->recordCommission(
                $affiliate, $finalPrice, $order->id, auth()->id(),
                'autotradex', $order->id, "AutoTradeX {$planInfo['name']} License"
            );
        }

        // Mark Early Bird as used if applicable
        if ($earlyBirdInfo['eligible'] && $machineId) {
            $this->markEarlyBirdUsed($machineId);
        }

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

        return view('autotradex.payment', [
            'order' => $order,
            'planInfo' => $planInfo,
            'paymentInfo' => $paymentInfo,
            'bankAccounts' => $bankAccounts,
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
        $plan = $request->query('plan');
        $machineId = $request->query('machine_id');

        // Store machine_id in session for later use
        if ($machineId) {
            session(['autotradex_machine_id' => $machineId]);
        }

        // Build query params
        $queryParams = $machineId ? ['machine_id' => $machineId] : [];

        // If specific plan requested, go to checkout with machine_id
        if ($plan && in_array($plan, ['monthly', 'yearly', 'lifetime'])) {
            $url = route('autotradex.checkout', $plan);
            if ($machineId) {
                $url .= '?machine_id=' . $machineId;
            }

            return redirect($url);
        }

        // Otherwise show pricing page with machine_id (to show Early Bird info)
        $url = route('autotradex.pricing');
        if ($machineId) {
            $url .= '?machine_id=' . $machineId;
        }

        return redirect($url);
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
        $slipPath = app(\App\Services\ImageService::class)->storeAsWebp(
            $request->file('payment_slip'), 'payment-slips/autotradex',
        );

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
        $prefix = 'XM' . date('Ymd');
        $random = strtoupper(Str::random(4));

        return $prefix . '-' . $random;
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

    /**
     * Check if a device is eligible for Early Bird discount
     *
     * Eligibility requirements:
     * 1. Must have a valid machine_id
     * 2. Device must be registered (pending, trial, or demo status)
     * 3. Device must NOT have used early bird discount before
     * 4. Device must NOT already be licensed
     *
     * Early Bird is available for:
     * - New devices (pending status) - 30 days from first seen
     * - Devices in trial - until trial expires
     * - Devices in demo mode - 7 days grace period after trial expires
     *
     * @return array{eligible: bool, discount_percent: int, days_remaining: int, message: string}
     */
    protected function checkEarlyBirdEligibility(?string $machineId): array
    {
        // Default: not eligible
        $result = [
            'eligible' => false,
            'discount_percent' => 0,
            'days_remaining' => 0,
            'message' => '',
        ];

        // No machine_id = no discount
        if (empty($machineId)) {
            $result['message'] = 'กรุณาเปิดจากแอป AutoTrade-X เพื่อรับส่วนลด';

            return $result;
        }

        // Find device record (try both full and short machine_id)
        $device = AutoTradeXDevice::where('machine_id', $machineId)
            ->orWhere('machine_id', 'LIKE', $machineId . '%')
            ->first();

        if (! $device) {
            // Device not registered yet - still eligible if they register
            // Store machine_id in session for later
            session(['autotradex_machine_id' => $machineId]);
            $result['message'] = 'กรุณาเปิดแอป AutoTrade-X เพื่อลงทะเบียนอุปกรณ์';

            return $result;
        }

        // Check if early bird discount was already used
        if ($device->early_bird_used) {
            $result['message'] = 'คุณได้ใช้สิทธิ์ Early Bird ไปแล้ว';

            return $result;
        }

        // Check if already licensed - no discount for existing customers
        if ($device->status === AutoTradeXDevice::STATUS_LICENSED) {
            $result['message'] = 'คุณมี License อยู่แล้ว';

            return $result;
        }

        // Check if device is blocked
        if ($device->status === AutoTradeXDevice::STATUS_BLOCKED) {
            $result['message'] = 'อุปกรณ์นี้ถูกระงับ';

            return $result;
        }

        // Calculate eligibility period based on device status
        $daysRemaining = 0;
        $eligibleUntil = null;

        if ($device->status === AutoTradeXDevice::STATUS_TRIAL && $device->trial_expires_at) {
            // In active trial - eligible until trial expires
            $eligibleUntil = $device->trial_expires_at;
        } elseif ($device->status === AutoTradeXDevice::STATUS_DEMO ||
                  $device->status === AutoTradeXDevice::STATUS_EXPIRED) {
            // Trial expired but in demo/grace period - give 7 more days from expiry
            if ($device->trial_expires_at) {
                $eligibleUntil = $device->trial_expires_at->copy()->addDays(7);
            } else {
                // No trial info - use first_seen + 37 days (30 trial + 7 grace)
                $eligibleUntil = $device->first_seen_at ?
                    $device->first_seen_at->copy()->addDays(37) :
                    now()->addDays(7);
            }
        } elseif ($device->status === AutoTradeXDevice::STATUS_PENDING) {
            // New device, not started trial yet - eligible for 30 days from registration
            $eligibleUntil = $device->first_seen_at ?
                $device->first_seen_at->copy()->addDays(30) :
                now()->addDays(30);
        } else {
            // Unknown status - give 7 days from now
            $eligibleUntil = now()->addDays(7);
        }

        $daysRemaining = (int) now()->diffInDays($eligibleUntil, false);

        // Check if still within eligibility period
        if ($daysRemaining <= 0) {
            $result['message'] = 'หมดเวลารับส่วนลด Early Bird แล้ว';

            return $result;
        }

        // All checks passed - eligible for Early Bird
        $result['eligible'] = true;
        $result['discount_percent'] = self::EARLY_BIRD_DISCOUNT_PERCENT;
        $result['days_remaining'] = $daysRemaining;

        if ($device->status === AutoTradeXDevice::STATUS_PENDING) {
            $result['message'] = "🎉 ส่วนลดพิเศษสำหรับลูกค้าใหม่! เหลือเวลาอีก {$daysRemaining} วัน";
        } elseif ($daysRemaining <= 3) {
            $result['message'] = "⏰ รีบซื้อเลย! เหลือเวลาอีกแค่ {$daysRemaining} วัน!";
        } else {
            $result['message'] = '🔥 ซื้อตอนนี้ลด ' . self::EARLY_BIRD_DISCOUNT_PERCENT . "%! เหลือเวลาอีก {$daysRemaining} วัน";
        }

        return $result;
    }

    /**
     * Get pricing with discount applied if eligible
     *
     * @param  array  $earlyBirdInfo  Result from checkEarlyBirdEligibility()
     * @return array Pricing with original_price and discounted_price
     */
    protected function getPricingWithDiscount(array $earlyBirdInfo): array
    {
        $pricing = [];

        foreach (self::PRICING as $key => $plan) {
            $pricing[$key] = $plan;
            $pricing[$key]['original_price'] = $plan['price'];

            if ($earlyBirdInfo['eligible']) {
                $discountAmount = (int) ($plan['price'] * $earlyBirdInfo['discount_percent'] / 100);
                $pricing[$key]['discounted_price'] = $plan['price'] - $discountAmount;
                $pricing[$key]['discount_amount'] = $discountAmount;
            } else {
                $pricing[$key]['discounted_price'] = $plan['price'];
                $pricing[$key]['discount_amount'] = 0;
            }
        }

        return $pricing;
    }

    /**
     * Mark Early Bird discount as used for a device
     */
    protected function markEarlyBirdUsed(string $machineId): bool
    {
        $device = AutoTradeXDevice::where('machine_id', $machineId)->first();

        if (! $device) {
            return false;
        }

        $device->update([
            'early_bird_used' => true,
            'early_bird_used_at' => now(),
        ]);

        return true;
    }
}
