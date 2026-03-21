<?php

namespace App\Http\Controllers;

use App\Events\NewOrderCreated;
use App\Mail\OrderConfirmationMail;
use App\Mail\PaymentConfirmedMail;
use App\Models\BankAccount;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentSetting;
use App\Models\Wallet;
use App\Services\AffiliateCommissionService;
use App\Services\LicenseService;
use App\Services\LineNotifyService;
use App\Services\SmsPaymentService;
use App\Services\StripeService;
use App\Services\ThaiPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(
        protected ThaiPaymentService $paymentService,
        protected SmsPaymentService $smsPaymentService
    ) {}

    /**
     * Display user's orders
     */
    public function index()
    {
        $orders = Order::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    /**
     * Display checkout page
     */
    public function checkout(Request $request)
    {
        $cart = $this->getCart();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'ตะกร้าว่างเปล่า');
        }

        $cart->load('items.product');
        $paymentMethods = $this->paymentService->getSupportedMethods();

        // Handle coupon removal via query parameter
        if ($request->has('remove_coupon')) {
            session()->forget('applied_coupon');

            return redirect()->route('orders.checkout')->with('success', 'ลบคูปองเรียบร้อยแล้ว');
        }

        // Handle coupon application via query parameter
        if ($request->has('coupon') && ! empty($request->coupon)) {
            $couponCode = strtoupper(trim($request->coupon));
            $coupon = Coupon::where('code', $couponCode)->first();

            if ($coupon) {
                $subtotal = $cart->items->sum(fn ($item) => $item->price * $item->quantity);
                $productIds = $cart->items->pluck('product_id')->toArray();
                $canUse = $coupon->canBeUsedBy(auth()->user(), $subtotal, $productIds);

                if ($canUse['valid']) {
                    session(['applied_coupon' => $couponCode]);

                    return redirect()->route('orders.checkout')->with('success', 'ใช้คูปองเรียบร้อยแล้ว');
                } else {
                    return redirect()->route('orders.checkout')->with('error', $canUse['message'] ?? 'คูปองไม่สามารถใช้งานได้');
                }
            } else {
                return redirect()->route('orders.checkout')->with('error', 'ไม่พบรหัสคูปองนี้');
            }
        }

        // Get wallet balance if user is authenticated
        $wallet = null;
        if (auth()->check()) {
            $wallet = Wallet::getOrCreateForUser(auth()->id());
        }

        // Check for applied coupon
        $appliedCoupon = null;
        $couponDiscount = 0;
        if (session()->has('applied_coupon')) {
            $coupon = Coupon::where('code', session('applied_coupon'))->first();
            if ($coupon) {
                $subtotal = $cart->items->sum(fn ($item) => $item->price * $item->quantity);
                $productIds = $cart->items->pluck('product_id')->toArray();
                $canUse = $coupon->canBeUsedBy(auth()->user(), $subtotal, $productIds);

                if ($canUse['valid']) {
                    $appliedCoupon = $coupon;
                    $couponDiscount = $coupon->calculateDiscount($subtotal);
                } else {
                    // Invalid coupon, remove from session
                    session()->forget('applied_coupon');
                }
            }
        }

        return view('orders.checkout', compact('cart', 'paymentMethods', 'wallet', 'appliedCoupon', 'couponDiscount'));
    }

    /**
     * Create order from cart
     */
    public function store(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:promptpay,bank_transfer,credit_card,wallet,stripe',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
            'coupon_code' => 'nullable|string',
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

            // Calculate totals with VAT
            $subtotal = $cart->items->sum(fn ($item) => $item->price * $item->quantity);
            $vatRate = config('app.vat_rate', 0.07); // 7% VAT
            $tax = round($subtotal * $vatRate, 2);

            // Apply coupon discount
            $discount = 0;
            $couponId = null;
            $couponCode = $request->coupon_code ?? session('applied_coupon');

            if ($couponCode) {
                $coupon = Coupon::where('code', strtoupper(trim($couponCode)))->first();
                if ($coupon) {
                    $productIds = $cart->items->pluck('product_id')->toArray();
                    $canUse = $coupon->canBeUsedBy(auth()->user(), $subtotal, $productIds);

                    if ($canUse['valid']) {
                        $discount = $coupon->calculateDiscount($subtotal);
                        $couponId = $coupon->id;
                    }
                }
            }

            $total = max(0, $subtotal + $tax - $discount);

            // Handle wallet payment
            $paymentStatus = 'pending';
            $orderStatus = 'pending';

            if ($request->payment_method === 'wallet') {
                if (! auth()->check()) {
                    return redirect()
                        ->back()
                        ->with('error', 'กรุณาเข้าสู่ระบบก่อนใช้ Wallet');
                }

                $wallet = Wallet::getOrCreateForUser(auth()->id());

                if ($wallet->balance < $total) {
                    return redirect()
                        ->back()
                        ->with('error', 'ยอดเงินใน Wallet ไม่เพียงพอ (ยอดคงเหลือ: ฿' . number_format($wallet->balance, 2) . ')');
                }
            }

            // Create order
            $order = Order::create([
                'user_id' => auth()->id(),
                'order_number' => $this->generateOrderNumber(),
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'coupon_id' => $couponId,
                'coupon_code' => $couponId ? $coupon->code : null,
                'total' => $total,
                'payment_method' => $request->payment_method,
                'payment_status' => $paymentStatus,
                'status' => $orderStatus,
                'notes' => $request->notes,
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
                    $affiliate, $total, $order->id, auth()->id(),
                    'order', $order->id, "Order #{$order->order_number}"
                );
            }

            // Generate unique payment amount for bank transfer & promptpay (SMS verification)
            if (in_array($request->payment_method, ['bank_transfer', 'promptpay'])) {
                $uniqueAmount = $this->smsPaymentService->generateUniqueAmount(
                    $total,
                    $order->id,
                    'order',
                    (int) config('smschecker.unique_amount_expiry', 30)
                );

                if ($uniqueAmount) {
                    $order->update([
                        'unique_payment_amount_id' => $uniqueAmount->id,
                        'payment_display_amount' => $uniqueAmount->unique_amount,
                        'sms_verification_status' => 'pending',
                    ]);
                }
            }

            // Create order items
            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'product_name' => $cartItem->product->name,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                    'total' => $cartItem->price * $cartItem->quantity,
                    'custom_requirements' => $cartItem->custom_requirements,
                ]);
            }

            // Process wallet payment
            if ($request->payment_method === 'wallet') {
                $wallet = Wallet::getOrCreateForUser(auth()->id());
                $transaction = $wallet->pay(
                    $total,
                    "ชำระคำสั่งซื้อ #{$order->order_number}",
                    'App\Models\Order',
                    $order->id
                );

                // Mark order as paid + link wallet transaction
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                    'paid_at' => now(),
                    'wallet_transaction_id' => $transaction?->id,
                ]);
            }

            // Auto-generate license keys for wallet payments (instant confirmation)
            if ($request->payment_method === 'wallet') {
                $this->generateLicensesForOrder($order);
            }

            // Record coupon usage
            if ($couponId && $coupon) {
                $coupon->recordUsage(auth()->user(), $order, $discount, $subtotal);
            }

            // Clear cart and applied coupon
            $cart->items()->delete();
            session()->forget('applied_coupon');

            // Handle Stripe payment - create PaymentIntent
            $stripeClientSecret = null;
            if ($request->payment_method === 'stripe') {
                $stripeService = app(StripeService::class);
                $intent = $stripeService->createPaymentIntentForOrder($order, auth()->user());
                $stripeClientSecret = $intent->client_secret;
            }

            DB::commit();

            // Send order confirmation email
            try {
                Mail::to($request->customer_email)
                    ->send(new OrderConfirmationMail($order->load('items.product', 'user')));
            } catch (\Exception $e) {
                // Log email error but don't fail the order
                Log::error('Failed to send order confirmation email', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Notify admin via LINE and broadcast event
            try {
                $lineNotify = new LineNotifyService;
                $itemNames = $order->items->pluck('product_name')->implode(', ');
                $message = "🛒 คำสั่งซื้อใหม่!\n"
                    . "━━━━━━━━━━━━━━━\n"
                    . "🔢 เลขที่: {$order->order_number}\n"
                    . "👤 ลูกค้า: {$order->customer_name}\n"
                    . "📧 อีเมล: {$order->customer_email}\n"
                    . '📱 โทร: ' . ($order->customer_phone ?: '-') . "\n"
                    . "━━━━━━━━━━━━━━━\n"
                    . "📝 รายการ: {$itemNames}\n"
                    . "💳 ชำระผ่าน: {$request->payment_method}\n"
                    . '💰 ยอดชำระ: ฿' . number_format($order->total, 2) . "\n"
                    . "━━━━━━━━━━━━━━━\n"
                    . '⏰ ' . now()->format('d/m/Y H:i');
                $lineNotify->send($message);
            } catch (\Exception $e) {
                Log::error('Failed to send admin notification', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Dispatch event for SMS checker broadcast
            if ($order->usesSmsPayment()) {
                event(new NewOrderCreated($order->load('uniquePaymentAmount')));
            }

            // Redirect based on payment method
            if ($stripeClientSecret) {
                return redirect()
                    ->route('orders.show', $order)
                    ->with('stripe_client_secret', $stripeClientSecret)
                    ->with('success', 'สร้างคำสั่งซื้อเรียบร้อยแล้ว กรุณาชำระเงินผ่าน Stripe');
            }

            return redirect()
                ->route('orders.show', $order)
                ->with('success', 'สร้างคำสั่งซื้อเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();

            // Log the actual error for debugging
            Log::error('Order creation failed', [
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

        $order->load(['items.product', 'uniquePaymentAmount']);

        // Auto-cancel order if unique amount has expired and order still pending
        if ($order->payment_status === 'pending' &&
            $order->usesSmsPayment() &&
            $order->uniquePaymentAmount &&
            $order->uniquePaymentAmount->isExpired()) {

            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'expired',
                'notes' => ($order->notes ? $order->notes . "\n" : '') .
                    'หมดเวลาชำระเงิน - ระบบยกเลิกอัตโนมัติ ' . now()->format('d/m/Y H:i'),
            ]);

            if ($order->uniquePaymentAmount->status === 'reserved') {
                $order->uniquePaymentAmount->update(['status' => 'expired']);
            }

            $order->refresh();
        }

        // Get payment info based on method
        $paymentInfo = null;
        $bankAccounts = null;
        $promptpayNumber = null;
        $promptpayName = null;

        if ($order->payment_status === 'pending') {
            if ($order->payment_method === 'promptpay') {
                $paymentInfo = $this->paymentService->generatePromptPayQR(
                    $order->display_amount,
                    $order->order_number
                );
            } elseif ($order->payment_method === 'bank_transfer') {
                $paymentInfo = $this->paymentService->getBankTransferInfo();

                // Get dynamic bank accounts
                $bankAccounts = BankAccount::active()->ordered()->get();
                $promptpayNumber = PaymentSetting::get('promptpay_number')
                    ?? config('payment.promptpay.number');
                $promptpayName = PaymentSetting::get('promptpay_name', '');

                // Add SMS payment info if using unique amount
                if ($order->usesSmsPayment()) {
                    $paymentInfo['sms_payment'] = [
                        'enabled' => true,
                        'unique_amount' => $order->display_amount,
                        'unique_amount_formatted' => '฿' . number_format($order->display_amount, 2),
                        'expires_at' => $order->uniquePaymentAmount?->expires_at,
                        'status' => $order->sms_verification_status,
                    ];
                }
            }
        }

        // Self-heal: Stripe payment succeeded but webhook hasn't arrived yet
        if ($order->payment_status === 'pending'
            && $order->payment_method === 'stripe'
            && $order->stripe_payment_intent_id
        ) {
            try {
                $stripeService = app(StripeService::class);
                $intent = $stripeService->retrievePaymentIntent($order->stripe_payment_intent_id);
                if ($intent->status === 'succeeded') {
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => 'processing',
                        'paid_at' => now(),
                        'stripe_payment_method_id' => $intent->payment_method,
                        'stripe_metadata' => [
                            'amount_received' => $intent->amount_received,
                            'payment_method_types' => $intent->payment_method_types,
                            'latest_charge' => $intent->latest_charge,
                        ],
                    ]);
                    $this->generateLicensesForOrder($order);
                    $order->refresh();
                }
            } catch (\Exception $e) {
                // Ignore — webhook or next poll will handle it
            }
        }

        // Stripe payment data
        $stripeClientSecret = session('stripe_client_secret');
        $stripePublishableKey = null;
        $stripeFeeInfo = null;

        if ($order->usesStripe() && $order->payment_status === 'pending') {
            $stripeService = app(StripeService::class);
            $stripePublishableKey = $stripeService->getPublishableKey();
            $stripeFeeInfo = $stripeService->calculateStripeFee($order->total);

            // If no client secret in session, create new PaymentIntent
            if (! $stripeClientSecret && $order->stripe_payment_intent_id) {
                try {
                    $intent = $stripeService->retrievePaymentIntent($order->stripe_payment_intent_id);
                    if ($intent->status !== 'canceled' && $intent->status !== 'succeeded') {
                        $stripeClientSecret = $intent->client_secret;
                    }
                } catch (\Exception $e) {
                    // PaymentIntent not found, create new one
                }
            }

            if (! $stripeClientSecret) {
                try {
                    $intent = $stripeService->createPaymentIntentForOrder($order, auth()->user());
                    $stripeClientSecret = $intent->client_secret;
                } catch (\Exception $e) {
                    Log::error('Failed to create Stripe PaymentIntent', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Also calculate fee for display even when payment is done
        if ($order->payment_method === 'stripe' && ! $stripeFeeInfo) {
            $stripeService = $stripeService ?? app(StripeService::class);
            $stripeFeeInfo = $stripeService->calculateStripeFee($order->total);
        }

        return view('orders.show', compact(
            'order', 'paymentInfo', 'bankAccounts', 'promptpayNumber', 'promptpayName',
            'stripeClientSecret', 'stripePublishableKey', 'stripeFeeInfo'
        ));
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

        // Block slip upload if unique amount has expired
        if ($order->usesSmsPayment() &&
            $order->uniquePaymentAmount &&
            $order->uniquePaymentAmount->isExpired()) {

            // Auto-cancel the order
            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'expired',
                'notes' => ($order->notes ? $order->notes . "\n" : '') .
                    'หมดเวลาชำระเงิน - ระบบยกเลิกอัตโนมัติ ' . now()->format('d/m/Y H:i'),
            ]);

            if ($order->uniquePaymentAmount->status === 'reserved') {
                $order->uniquePaymentAmount->update(['status' => 'expired']);
            }

            return redirect()
                ->back()
                ->with('error', 'บิลนี้หมดอายุแล้ว กรุณาสร้างคำสั่งซื้อใหม่');
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
        $content .= str_repeat('-', 50) . "\n";

        foreach ($licenses as $license) {
            $content .= "{$license->license_key} ({$license->license_type})\n";
        }

        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', "attachment; filename=\"licenses-{$order->order_number}.txt\"");
    }

    /**
     * Auto-generate license keys for order items that require them.
     */
    protected function generateLicensesForOrder(Order $order): void
    {
        app(LicenseService::class)->generateLicensesForOrder($order);
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
     * AJAX: Check payment status for polling (used by checkout page).
     *
     * GET /orders/{order}/payment-status
     */
    public function checkPaymentStatus(Order $order): JsonResponse
    {
        // Verify ownership
        if (auth()->id() && $order->user_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            abort(403);
        }

        $order->refresh();
        $order->load('uniquePaymentAmount');

        // Self-heal: Stripe payment succeeded but webhook hasn't arrived yet
        if ($order->payment_status === 'pending'
            && $order->payment_method === 'stripe'
            && $order->stripe_payment_intent_id
        ) {
            try {
                $stripeService = app(StripeService::class);
                $intent = $stripeService->retrievePaymentIntent($order->stripe_payment_intent_id);
                if ($intent->status === 'succeeded') {
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => 'processing',
                        'paid_at' => now(),
                        'stripe_payment_method_id' => $intent->payment_method,
                        'stripe_metadata' => [
                            'amount_received' => $intent->amount_received,
                            'payment_method_types' => $intent->payment_method_types,
                            'latest_charge' => $intent->latest_charge,
                        ],
                    ]);
                    $this->generateLicensesForOrder($order);
                    $order->refresh();
                }
            } catch (\Exception $e) {
                // Ignore — will try again on next poll
            }
        }

        // Auto-cancel if amount expired but order still pending
        if ($order->payment_status === 'pending' &&
            $order->usesSmsPayment() &&
            $order->uniquePaymentAmount &&
            $order->uniquePaymentAmount->isExpired()) {

            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'expired',
                'notes' => ($order->notes ? $order->notes . "\n" : '') .
                    'หมดเวลาชำระเงิน - ระบบยกเลิกอัตโนมัติ ' . now()->format('d/m/Y H:i'),
            ]);

            if ($order->uniquePaymentAmount->status === 'reserved') {
                $order->uniquePaymentAmount->update(['status' => 'expired']);
            }

            $order->refresh();
        }

        return response()->json([
            'payment_status' => $order->payment_status,
            'status' => $order->status,
            'sms_verification_status' => $order->sms_verification_status,
            'redirect' => in_array($order->payment_status, ['paid', 'confirmed'])
                ? route('orders.show', $order)
                : null,
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
}
