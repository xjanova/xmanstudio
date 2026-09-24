<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesReleaseDownloads;
use App\Mail\PaymentConfirmedMail;
use App\Models\BankAccount;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\Wallet;
use App\Services\AffiliateCommissionService;
use App\Services\ImageService;
use App\Services\LicenseService;
use App\Services\ThaiPaymentService;
use App\Support\LicensePlans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Tping Web Controller
 *
 * Handles web pages for Tping product (pricing, checkout, payment).
 * Supports wallet payment with instant license generation + HWID binding.
 */
class TpingController extends Controller
{
    use ServesReleaseDownloads;

    /**
     * Wallet payment discount percentage.
     * When paying with wallet, users get this % off.
     */
    private const WALLET_DISCOUNT_PERCENT = 10;

    /**
     * The plans this page offers: names, durations, features. What each one
     * costs is in config/licenses.php; pricedPlans() puts the two together.
     */
    private const PLANS = [
        'monthly' => [
            'name' => 'Monthly',
            'name_th' => 'รายเดือน',
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

    /** PLANS with each price from config/licenses.php — only the terms the store sells. */
    private static function pricedPlans(): array
    {
        return LicensePlans::priced('tping', self::PLANS);
    }

    /**
     * Show Tping detail / landing page
     *
     * GET /tping
     */
    public function detail()
    {
        $product = Product::where('slug', 'tping')->first();

        $version = null;
        if ($product) {
            $version = ProductVersion::where('product_id', $product->id)
                ->where('is_active', true)
                ->orderByDesc('version')
                ->first();
        }

        return view('tping.detail', [
            'pricing' => self::pricedPlans(),
            'product' => $product,
            'version' => $version,
        ]);
    }

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
            'pricing' => self::pricedPlans(),
        ]);
    }

    /**
     * Show checkout page for specific plan
     *
     * GET /tping/checkout/{plan}
     */
    public function checkout(Request $request, string $plan)
    {
        if (! array_key_exists($plan, self::pricedPlans())) {
            abort(404, 'Plan not found');
        }

        $product = Product::where('slug', 'tping')->first();

        if (! $product) {
            abort(404, 'Product not found');
        }

        $machineId = $request->query('machine_id') ?? session('tping_machine_id');
        $planInfo = self::pricedPlans()[$plan];

        // Load wallet for authenticated user
        $wallet = auth()->check() ? Wallet::getOrCreateForUser(auth()->id()) : null;
        $walletDiscount = (int) floor($planInfo['price'] * self::WALLET_DISCOUNT_PERCENT / 100);
        $walletPrice = $planInfo['price'] - $walletDiscount;

        return view('tping.checkout', [
            'plan' => $plan,
            'planInfo' => $planInfo,
            'product' => $product,
            'machineId' => $machineId,
            'wallet' => $wallet,
            'walletDiscount' => $walletDiscount,
            'walletDiscountPercent' => self::WALLET_DISCOUNT_PERCENT,
            'walletPrice' => $walletPrice,
        ]);
    }

    /**
     * Process checkout
     *
     * POST /tping/checkout/{plan}
     */
    public function processCheckout(Request $request, string $plan)
    {
        if (! array_key_exists($plan, self::pricedPlans())) {
            abort(404, 'Plan not found');
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'payment_method' => 'required|in:promptpay,bank_transfer,wallet',
            'machine_id' => 'nullable|string|max:64',
        ]);

        $machineId = $validated['machine_id'] ?? session('tping_machine_id');
        $product = Product::where('slug', 'tping')->firstOrFail();
        $planInfo = self::pricedPlans()[$plan];
        $isWallet = $validated['payment_method'] === 'wallet';

        // === Calculate price & discount ===
        $subtotal = $planInfo['price'];
        $discount = 0;
        $finalPrice = $subtotal;

        if ($isWallet) {
            $discount = (int) floor($subtotal * self::WALLET_DISCOUNT_PERCENT / 100);
            $finalPrice = $subtotal - $discount;

            // Check wallet balance
            if (! auth()->check()) {
                return redirect()->back()->with('error', 'กรุณาเข้าสู่ระบบก่อนใช้ Wallet');
            }

            $wallet = Wallet::getOrCreateForUser(auth()->id());

            if (! $wallet->hasSufficientBalance($finalPrice)) {
                return redirect()->back()->with(
                    'error',
                    'ยอดเงินใน Wallet ไม่เพียงพอ (คงเหลือ: ฿' . number_format($wallet->balance, 2) .
                    ' / ต้องการ: ฿' . number_format($finalPrice) . ')'
                );
            }
        }

        // === Affiliate tracking ===
        $affiliateService = app(AffiliateCommissionService::class);
        $affiliate = $affiliateService->resolveAffiliate(auth()->id());

        // === Create order ===
        $metadata = [
            'plan' => $plan,
            'license_type' => $planInfo['license_type'],
            'machine_id' => $machineId,
            'wallet_discount_percent' => $isWallet ? self::WALLET_DISCOUNT_PERCENT : 0,
            'affiliate_code' => $affiliate?->referral_code,
        ];

        $notes = "Tping {$planInfo['name']} License | Plan: {$plan} | Type: {$planInfo['license_type']}";

        $order = Order::create([
            'user_id' => auth()->id(),
            'order_number' => $this->generateOrderNumber(),
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $finalPrice,
            'status' => $isWallet ? 'processing' : 'pending',
            'payment_method' => $validated['payment_method'],
            'payment_status' => $isWallet ? 'paid' : 'pending',
            'paid_at' => $isWallet ? now() : null,
            'notes' => $notes,
            'metadata' => json_encode($metadata),
            'affiliate_id' => $affiliate?->id,
            'referral_code' => $affiliate?->referral_code,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'price' => $subtotal,
            'subtotal' => $finalPrice,
            'custom_requirements' => json_encode([
                'license_type' => $planInfo['license_type'],
                'duration_days' => $planInfo['duration_days'],
            ]),
        ]);

        // === Wallet payment: instant processing ===
        if ($isWallet) {
            // Deduct from wallet
            $transaction = $wallet->pay(
                $finalPrice,
                "ชำระ Tping {$planInfo['name_th']} License (ลด {$discount}฿)",
                'App\Models\Order',
                $order->id
            );

            if ($transaction) {
                $order->update(['wallet_transaction_id' => $transaction->id]);
            } else {
                // Payment failed — rollback order
                $order->update(['status' => 'cancelled', 'payment_status' => 'failed']);

                return redirect()->back()->with('error', 'ชำระเงินไม่สำเร็จ กรุณาลองใหม่');
            }

            // Generate license key
            $this->generateLicenseForOrder($order, $product, $planInfo, $machineId);

            // Record affiliate commission (wallet = instant, so commission pending for admin review)
            if ($affiliate) {
                $affiliateService->recordCommission(
                    $affiliate, $order->total, $order->id, $order->user_id,
                    'tping', $order->id, "Tping {$planInfo['name']} License"
                );
            }

            // Redirect to success (skip payment page — already paid)
            return redirect()->route('tping.payment-success', $order->id);
        }

        // === PromptPay / Bank Transfer: redirect to payment page ===
        // Record affiliate commission (will be pending until order is paid)
        if ($affiliate) {
            $affiliateService->recordCommission(
                $affiliate, $order->total, $order->id, $order->user_id,
                'tping', $order->id, "Tping {$planInfo['name']} License"
            );
        }

        return redirect()->route('tping.payment', [
            'order' => $order->id,
        ]);
    }

    /**
     * Generate license key for an order and optionally bind to HWID.
     */
    protected function generateLicenseForOrder(
        Order $order,
        Product $product,
        array $planInfo,
        ?string $machineId
    ): ?LicenseKey {
        try {
            $licenseService = app(LicenseService::class);
            $licenses = $licenseService->generateLicenses(
                $planInfo['license_type'],
                1,
                $planInfo['license_type'] === 'lifetime' ? 3 : 1,
                $product->id
            );

            if (empty($licenses)) {
                Log::error('TpingController: License generation returned empty', [
                    'order_id' => $order->id,
                ]);

                return null;
            }

            $licenseData = $licenses[0];
            $license = LicenseKey::find($licenseData['id']);

            if (! $license) {
                return null;
            }

            // Set expiry
            $expiresAt = match ($planInfo['license_type']) {
                'daily' => now()->addDay(),
                'weekly' => now()->addDays(7),
                'monthly' => now()->addDays(30),
                'yearly' => now()->addYear(),
                'lifetime' => null,
                default => now()->addYear(),
            };

            // Link to order & user
            $license->update([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'expires_at' => $expiresAt,
            ]);

            // HWID binding: auto-activate on the purchasing device
            if ($machineId) {
                $license->activateOnMachine($machineId, $machineId);

                Log::info('TpingController: License activated on HWID', [
                    'order_id' => $order->id,
                    'license_key' => $license->license_key,
                    'machine_id' => $machineId,
                ]);
            }

            // Store license key in order metadata for easy display, and complete the order: its
            // license is out (as LicenseService does for every other checkout). Left at processing,
            // every "has this customer bought it?" check told the buyer to buy it first.
            $metadata = json_decode($order->metadata ?? '{}', true);
            $metadata['license_key'] = $license->license_key;
            $metadata['license_id'] = $license->id;
            $metadata['hwid_bound'] = ! empty($machineId);
            $order->update(['metadata' => json_encode($metadata), 'status' => 'completed']);

            // Send payment confirmed email with license keys
            if ($order->customer_email && PaymentSetting::get('mail_enabled', true)) {
                try {
                    Mail::to($order->customer_email)
                        ->send(new PaymentConfirmedMail($order->fresh(['items.product', 'user'])));
                } catch (\Exception $mailError) {
                    Log::error('Tping: Failed to send payment email', [
                        'order_id' => $order->id,
                        'error' => $mailError->getMessage(),
                    ]);
                }
            }

            return $license;
        } catch (\Exception $e) {
            Log::error('TpingController: License generation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
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
        $planInfo = self::pricedPlans()[$plan] ?? self::pricedPlans()['monthly'];

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

        $slipPath = app(ImageService::class)->storeAsWebp(
            $request->file('payment_slip'), 'payment-slips/tping',
        );

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
        $planInfo = self::pricedPlans()[$plan] ?? self::pricedPlans()['monthly'];

        // Load license keys for this order (available for wallet payments)
        $licenses = LicenseKey::where('order_id', $order->id)->get();

        return view('tping.payment-success', [
            'order' => $order,
            'planInfo' => $planInfo,
            'licenses' => $licenses,
            'metadata' => $metadata,
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

    // ================================================================
    // APK Download — streamed from this site (no GitHub URL exposed)
    // ================================================================

    /**
     * Public download page for Tping APK.
     *
     * GET /tping/download
     */
    public function downloadPage()
    {
        $product = Product::where('slug', 'tping')->first();

        $version = null;
        if ($product) {
            $version = ProductVersion::where('product_id', $product->id)
                ->where('is_active', true)
                ->orderByDesc('version')
                ->first();
        }

        return view('tping.download', [
            'product' => $product,
            'version' => $version,
        ]);
    }

    /**
     * Installation guide with phone mockups.
     * Supports real screenshots uploaded by admin.
     *
     * GET /tping/install-guide
     */
    public function installGuide()
    {
        // Load admin-uploaded screenshots (keyed by step number)
        $screenshots = [];
        $guideDir = 'guide-screenshots/tping';

        for ($i = 1; $i <= 6; $i++) {
            foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                $path = "{$guideDir}/step-{$i}.{$ext}";
                if (Storage::disk('public')->exists($path)) {
                    $screenshots[$i] = $path;
                    break;
                }
            }
        }

        return view('tping.install-guide', [
            'screenshots' => $screenshots,
        ]);
    }

    /**
     * APK ของ Tping — ไฟล์ส่งจาก xman4289.com เอง ลูกค้าไม่เห็น GitHub (ReleaseDownloadStreamer)
     * และจองที่ในช่องส่งไฟล์ร่วมของทั้งเว็บ (config/downloads.php) เหมือนแอปอื่น
     *
     * GET /tping/download/apk — ตัวอัปเดตในแอป (update/check) ก็ชี้มาที่นี่
     * เวอร์ชันที่ส่งยังเป็นกฎเดิมของ Tping: ตัวที่ active
     */
    public function downloadApk(Request $request)
    {
        $product = Product::where('slug', 'tping')->first();

        if (! $product) {
            return $this->downloadUnavailable($request, route('tping.download'), 404, 'Product not found', 'ยังไม่มีไฟล์สำหรับดาวน์โหลด กรุณาลองใหม่ภายหลัง');
        }

        $version = ProductVersion::where('product_id', $product->id)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();

        return $this->serveApk($request, $product, $version, route('tping.download'), 'Tping');
    }

    protected function generateOrderNumber(): string
    {
        $prefix = 'TP' . date('Ymd');
        $random = strtoupper(Str::random(4));

        return $prefix . '-' . $random;
    }
}
