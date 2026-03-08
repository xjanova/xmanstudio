<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\BankAccount;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\Wallet;
use App\Services\GithubReleaseService;
use App\Services\LicenseService;
use App\Services\ThaiPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tping Web Controller
 *
 * Handles web pages for Tping product (pricing, checkout, payment).
 * Supports wallet payment with instant license generation + HWID binding.
 */
class TpingController extends Controller
{
    /**
     * Wallet payment discount percentage.
     * When paying with wallet, users get this % off.
     */
    private const WALLET_DISCOUNT_PERCENT = 10;

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
        if (! isset(self::PRICING[$plan])) {
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
        $planInfo = self::PRICING[$plan];
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
        $affiliateRef = session('affiliate_ref') ?? $request->cookie('affiliate_ref');
        $affiliate = null;
        if ($affiliateRef) {
            $affiliate = Affiliate::where('referral_code', $affiliateRef)
                ->where('status', 'active')
                ->first();
            // Prevent self-referral
            if ($affiliate && auth()->id() === $affiliate->user_id) {
                $affiliate = null;
            }
        }

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
            $this->recordAffiliateCommission($order, $affiliate);

            // Redirect to success (skip payment page — already paid)
            return redirect()->route('tping.payment-success', $order->id);
        }

        // === PromptPay / Bank Transfer: redirect to payment page ===
        // Record affiliate commission (will be pending until order is paid)
        $this->recordAffiliateCommission($order, $affiliate);

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

            // Store license key in order metadata for easy display
            $metadata = json_decode($order->metadata ?? '{}', true);
            $metadata['license_key'] = $license->license_key;
            $metadata['license_id'] = $license->id;
            $metadata['hwid_bound'] = ! empty($machineId);
            $order->update(['metadata' => json_encode($metadata)]);

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

    /**
     * Record affiliate commission for an order.
     */
    protected function recordAffiliateCommission(Order $order, ?Affiliate $affiliate): void
    {
        if (! $affiliate) {
            return;
        }

        $commissionAmount = $affiliate->calculateCommission($order->total);

        if ($commissionAmount <= 0) {
            return;
        }

        AffiliateCommission::create([
            'affiliate_id' => $affiliate->id,
            'order_id' => $order->id,
            'referred_user_id' => $order->user_id,
            'order_amount' => $order->total,
            'commission_rate' => $affiliate->commission_rate,
            'commission_amount' => $commissionAmount,
            'status' => 'pending', // Admin must approve before wallet payout
        ]);

        // Update affiliate counters
        $affiliate->increment('total_referrals');
        $affiliate->increment('total_conversions');
        $affiliate->increment('total_earned', $commissionAmount);
        $affiliate->increment('total_pending', $commissionAmount);

        // Clear referral session/cookie
        session()->forget('affiliate_ref');

        Log::info('Affiliate commission recorded', [
            'affiliate_id' => $affiliate->id,
            'order_id' => $order->id,
            'amount' => $commissionAmount,
        ]);
    }

    // ================================================================
    // APK Download — proxied from GitHub (no GitHub URL exposed)
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
     * Stream APK download proxied through our server.
     * Users never see or reach GitHub.
     *
     * GET /tping/download/apk
     */
    public function downloadApk()
    {
        $product = Product::where('slug', 'tping')->firstOrFail();
        $version = ProductVersion::where('product_id', $product->id)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();

        if (! $version || ! $version->github_release_url) {
            return redirect()->route('tping.download')
                ->with('error', 'ยังไม่มีไฟล์สำหรับดาวน์โหลด กรุณาลองใหม่ภายหลัง');
        }

        $githubSetting = $product->githubSetting;

        if (! $githubSetting) {
            return redirect()->route('tping.download')
                ->with('error', 'ระบบดาวน์โหลดยังไม่พร้อม');
        }

        // Proxy the download
        return $this->proxyGithubDownload($githubSetting, $version);
    }

    /**
     * Proxy download from GitHub (same pattern as DownloadController).
     */
    protected function proxyGithubDownload($githubSetting, ProductVersion $productVersion): StreamedResponse
    {
        $token = $githubSetting->github_token_decrypted;
        $assetUrl = $productVersion->github_release_url;

        // Get the actual download URL (GitHub redirects to S3)
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/octet-stream',
            'User-Agent' => 'XMAN-Tping-Download-Proxy',
        ])->withOptions([
            'allow_redirects' => false,
        ])->get($assetUrl);

        if ($response->status() === 302) {
            $downloadUrl = $response->header('Location');
        } else {
            $downloadUrl = $assetUrl;
        }

        $filename = $productVersion->download_filename ?? 'Tping-v' . $productVersion->version . '.apk';
        $fileSize = $productVersion->file_size;

        return new StreamedResponse(function () use ($downloadUrl, $token) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $downloadUrl);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) {
                echo $data;
                flush();

                return strlen($data);
            });

            if (strpos($downloadUrl, 'github.com') !== false) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $token,
                    'Accept: application/octet-stream',
                    'User-Agent: XMAN-Tping-Download-Proxy',
                ]);
            }

            curl_exec($ch);
            curl_close($ch);
        }, 200, [
            'Content-Type' => 'application/vnd.android.package-archive',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => $fileSize,
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    protected function generateOrderNumber(): string
    {
        $prefix = 'TP' . date('Ymd');
        $random = strtoupper(Str::random(4));

        return $prefix . '-' . $random;
    }
}
