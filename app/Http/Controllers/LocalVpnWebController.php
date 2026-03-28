<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * LocalVPN Web Controller
 *
 * Handles web pages for LocalVPN product (pricing, checkout, download).
 * Follows the same pattern as TpingController.
 */
class LocalVpnWebController extends Controller
{
    private const WALLET_DISCOUNT_PERCENT = 10;

    private const PRICING = [
        'monthly' => [
            'name' => 'Monthly',
            'name_th' => 'รายเดือน',
            'price' => 399,
            'duration_days' => 30,
            'license_type' => 'monthly',
            'features' => [
                'สร้างวง LAN เสมือนไม่จำกัด',
                'สมาชิกในวงสูงสุด 50 คน',
                'เข้ารหัส WireGuard',
                'VPN มุดประเทศทุกประเทศ',
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
                'สร้างวง LAN เสมือนไม่จำกัด',
                'สมาชิกในวงสูงสุด 50 คน',
                'เข้ารหัส WireGuard',
                'VPN มุดประเทศทุกประเทศ',
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
                'สร้างวง LAN เสมือนไม่จำกัด',
                'สมาชิกในวงสูงสุด 50 คน',
                'เข้ารหัส WireGuard',
                'VPN มุดประเทศทุกประเทศ',
                'ซัพพอร์ตพรีเมียม',
                'อัพเดทตลอดชีพ',
                'ใช้ได้หลายเครื่อง',
            ],
        ],
    ];

    /**
     * Show LocalVPN detail / landing page
     */
    public function detail()
    {
        $product = Product::where('slug', 'localvpn')->first();
        $version = null;

        if ($product) {
            $version = ProductVersion::where('product_id', $product->id)
                ->where('is_active', true)
                ->orderByDesc('version')
                ->first();
        }

        return view('localvpn.detail', [
            'pricing' => self::PRICING,
            'product' => $product,
            'version' => $version,
        ]);
    }

    /**
     * Show pricing page
     */
    public function pricing(Request $request)
    {
        $machineId = $request->query('machine_id') ?? session('localvpn_machine_id');

        if ($machineId) {
            session(['localvpn_machine_id' => $machineId]);
        }

        return view('localvpn.pricing', [
            'pricing' => self::PRICING,
            'machineId' => $machineId,
            'walletDiscount' => self::WALLET_DISCOUNT_PERCENT,
        ]);
    }

    /**
     * Redirect to pricing or checkout based on plan
     */
    public function buyRedirect(Request $request)
    {
        $plan = $request->query('plan');
        $machineId = $request->query('machine_id');

        if ($machineId) {
            session(['localvpn_machine_id' => $machineId]);
        }

        if ($plan && in_array($plan, ['monthly', 'yearly', 'lifetime'])) {
            $url = route('localvpn.checkout', $plan);
            if ($machineId) {
                $url .= '?machine_id=' . $machineId;
            }

            return redirect($url);
        }

        $url = route('localvpn.pricing');
        if ($machineId) {
            $url .= '?machine_id=' . $machineId;
        }

        return redirect($url);
    }

    /**
     * Show download page
     */
    public function downloadPage()
    {
        $product = Product::where('slug', 'localvpn')->first();
        $version = null;

        if ($product) {
            $version = ProductVersion::where('product_id', $product->id)
                ->where('is_active', true)
                ->orderByDesc('version')
                ->first();
        }

        return view('localvpn.download', [
            'product' => $product,
            'version' => $version,
        ]);
    }

    /**
     * Download APK (proxy from GitHub release)
     */
    public function downloadApk()
    {
        $product = Product::where('slug', 'localvpn')->firstOrFail();
        $version = ProductVersion::where('product_id', $product->id)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();

        if (! $version || ! $version->github_release_url) {
            return redirect()->route('localvpn.download')
                ->with('error', 'ยังไม่มีไฟล์สำหรับดาวน์โหลด กรุณาลองใหม่ภายหลัง');
        }

        $githubSetting = $product->githubSetting;

        if (! $githubSetting) {
            return redirect()->route('localvpn.download')
                ->with('error', 'ระบบดาวน์โหลดยังไม่พร้อม');
        }

        return $this->proxyGithubDownload($githubSetting, $version);
    }

    /**
     * Proxy download from GitHub
     */
    protected function proxyGithubDownload($githubSetting, ProductVersion $productVersion): StreamedResponse
    {
        $token = $githubSetting->github_token_decrypted;
        $assetUrl = $productVersion->github_release_url;
        $filename = $productVersion->download_filename ?? 'LocalVPN-v' . $productVersion->version . '.apk';
        $fileSize = $productVersion->file_size;

        // For public repos without token: use browser_download_url (direct download)
        // which doesn't require authentication. The github_release_url is an API URL
        // that needs a token for private repos.
        if (empty($token)) {
            // Convert API asset URL to browser download URL, or use direct redirect
            $browserUrl = $productVersion->browser_download_url
                ?? "https://github.com/{$githubSetting->github_owner}/{$githubSetting->github_repo}/releases/download/v{$productVersion->version}/{$filename}";

            return new StreamedResponse(function () use ($browserUrl) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $browserUrl);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'User-Agent: XMAN-LocalVPN-Download-Proxy',
                ]);
                curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) {
                    echo $data;
                    flush();

                    return strlen($data);
                });
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

        // Private repo: use API URL with token to get redirect URL
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/octet-stream',
            'User-Agent' => 'XMAN-LocalVPN-Download-Proxy',
        ])->withOptions([
            'allow_redirects' => false,
        ])->get($assetUrl);

        if ($response->status() === 302) {
            $downloadUrl = $response->header('Location');
        } else {
            $downloadUrl = $assetUrl;
        }

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
                    'User-Agent: XMAN-LocalVPN-Download-Proxy',
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

    /**
     * Installation guide page.
     */
    public function installGuide()
    {
        $screenshots = [];
        $guideDir = 'guide-screenshots/localvpn';

        for ($i = 1; $i <= 6; $i++) {
            foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                $path = "{$guideDir}/step-{$i}.{$ext}";
                if (Storage::disk('public')->exists($path)) {
                    $screenshots[$i] = $path;
                    break;
                }
            }
        }

        return view('localvpn.install-guide', [
            'screenshots' => $screenshots,
        ]);
    }

    /**
     * Checkout page
     */
    public function checkout(Request $request, string $plan)
    {
        if (! isset(self::PRICING[$plan])) {
            return redirect()->route('localvpn.pricing');
        }

        $product = Product::where('slug', 'localvpn')->first();

        if (! $product) {
            abort(404, 'Product not found');
        }

        $planInfo = self::PRICING[$plan];
        $machineId = $request->query('machine_id') ?? session('localvpn_machine_id');

        if ($machineId) {
            session(['localvpn_machine_id' => $machineId]);
        }

        $wallet = auth()->check() ? Wallet::getOrCreateForUser(auth()->id()) : null;
        $walletDiscount = (int) floor($planInfo['price'] * self::WALLET_DISCOUNT_PERCENT / 100);
        $walletPrice = $planInfo['price'] - $walletDiscount;

        return view('localvpn.checkout', [
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
     * POST /localvpn/checkout/{plan}
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
            'machine_id' => 'nullable|string|max:255',
        ]);

        $machineId = $validated['machine_id'] ?? session('localvpn_machine_id');
        $product = Product::where('slug', 'localvpn')->firstOrFail();
        $planInfo = self::PRICING[$plan];
        $isWallet = $validated['payment_method'] === 'wallet';

        // === Calculate price & discount ===
        $subtotal = $planInfo['price'];
        $discount = 0;
        $finalPrice = $subtotal;
        $wallet = null;

        if ($isWallet) {
            $discount = (int) floor($subtotal * self::WALLET_DISCOUNT_PERCENT / 100);
            $finalPrice = $subtotal - $discount;

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

        // === Create order inside transaction ===
        try {
            $result = DB::transaction(function () use (
                $validated, $plan, $planInfo, $subtotal, $discount, $finalPrice,
                $isWallet, $wallet, $machineId, $product, $affiliate, $affiliateService,
            ) {
                $metadata = [
                    'plan' => $plan,
                    'license_type' => $planInfo['license_type'],
                    'machine_id' => $machineId,
                    'wallet_discount_percent' => $isWallet ? self::WALLET_DISCOUNT_PERCENT : 0,
                    'affiliate_code' => $affiliate?->referral_code,
                ];

                $notes = "LocalVPN {$planInfo['name']} License | Plan: {$plan} | Type: {$planInfo['license_type']}";

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
                    'metadata' => $metadata,
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
                    // Re-check balance with lock to prevent race condition
                    $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

                    if (! $wallet || ! $wallet->hasSufficientBalance($finalPrice)) {
                        throw new \RuntimeException('INSUFFICIENT_BALANCE');
                    }

                    $transaction = $wallet->pay(
                        $finalPrice,
                        "ชำระ LocalVPN {$planInfo['name_th']} License (ลด {$discount}฿)",
                        'App\Models\Order',
                        $order->id
                    );

                    if (! $transaction) {
                        throw new \RuntimeException('PAYMENT_FAILED');
                    }

                    $order->update(['wallet_transaction_id' => $transaction->id]);

                    // Idempotency: check if license already exists for this order
                    if (! LicenseKey::where('order_id', $order->id)->exists()) {
                        $license = $this->generateLicenseForOrder($order, $product, $planInfo, $machineId);
                        if (! $license) {
                            throw new \RuntimeException('LICENSE_GENERATION_FAILED');
                        }
                    }
                }

                if ($affiliate) {
                    $affiliateService->recordCommission(
                        $affiliate, $order->total, $order->id, $order->user_id,
                        'localvpn', $order->id, "LocalVPN {$planInfo['name']} License"
                    );
                }

                return $order;
            });

            if ($isWallet) {
                return redirect()->route('localvpn.payment-success', $result->id);
            }

            return redirect()->route('localvpn.payment', ['order' => $result->id]);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'INSUFFICIENT_BALANCE') {
                return redirect()->back()->with('error', 'ยอดเงินใน Wallet ไม่เพียงพอ กรุณาลองใหม่');
            }
            if ($e->getMessage() === 'LICENSE_GENERATION_FAILED') {
                return redirect()->back()->with('error', 'สร้าง License ไม่สำเร็จ เงินจะคืนอัตโนมัติ กรุณาลองใหม่');
            }

            return redirect()->back()->with('error', 'ชำระเงินไม่สำเร็จ กรุณาลองใหม่');
        }
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
                Log::error('LocalVpnWebController: License generation returned empty', [
                    'order_id' => $order->id,
                ]);

                return null;
            }

            $licenseData = $licenses[0];
            $license = LicenseKey::find($licenseData['id']);

            if (! $license) {
                return null;
            }

            $expiresAt = match ($planInfo['license_type']) {
                'daily' => now()->addDay(),
                'weekly' => now()->addDays(7),
                'monthly' => now()->addDays(30),
                'yearly' => now()->addYear(),
                'lifetime' => null,
                default => now()->addYear(),
            };

            $license->update([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'expires_at' => $expiresAt,
            ]);

            if ($machineId) {
                $license->activateOnMachine($machineId, $machineId);

                Log::info('LocalVpnWebController: License activated on HWID', [
                    'order_id' => $order->id,
                    'license_key' => $license->license_key,
                    'machine_id' => $machineId,
                ]);
            }

            $metadata = $order->metadata ?? [];
            $metadata['license_key'] = $license->license_key;
            $metadata['license_id'] = $license->id;
            $metadata['hwid_bound'] = ! empty($machineId);
            $order->update(['metadata' => $metadata]);

            if ($order->customer_email && PaymentSetting::get('mail_enabled', true)) {
                try {
                    Mail::to($order->customer_email)
                        ->send(new PaymentConfirmedMail($order->fresh(['items.product', 'user'])));
                } catch (\Exception $mailError) {
                    Log::error('LocalVPN: Failed to send payment email', [
                        'order_id' => $order->id,
                        'error' => $mailError->getMessage(),
                    ]);
                }
            }

            return $license;
        } catch (\Exception $e) {
            Log::error('LocalVpnWebController: License generation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Show payment page
     *
     * GET /localvpn/payment/{order}
     */
    public function payment(Order $order)
    {
        if (auth()->id() !== $order->user_id) {
            abort(403);
        }

        $metadata = $order->metadata ?? [];
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

        return view('localvpn.payment', [
            'order' => $order,
            'planInfo' => $planInfo,
            'paymentInfo' => $paymentInfo,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    /**
     * Confirm payment with slip upload
     *
     * POST /localvpn/payment/{order}/confirm
     */
    public function confirmPayment(Request $request, Order $order)
    {
        if (auth()->id() !== $order->user_id) {
            abort(403);
        }

        if ($order->status !== 'pending') {
            return back()->with('error', 'คำสั่งซื้อนี้ได้รับการดำเนินการแล้ว');
        }

        if ($order->payment_method === 'wallet') {
            return back()->with('error', 'คำสั่งซื้อนี้ชำระผ่าน Wallet แล้ว');
        }

        $validated = $request->validate([
            'payment_slip' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'notes' => 'nullable|string|max:500',
        ]);

        $slipPath = app(ImageService::class)->storeAsWebp(
            $request->file('payment_slip'),
            'payment-slips/localvpn',
        );

        $metadata = $order->metadata ?? [];
        $metadata['payment_slip'] = $slipPath;
        $metadata['payment_submitted_at'] = now()->toISOString();
        $metadata['payment_notes'] = $validated['notes'] ?? null;

        $order->update([
            'status' => 'processing',
            'metadata' => $metadata,
        ]);

        return redirect()->route('localvpn.payment-success', $order->id);
    }

    /**
     * Payment success page
     *
     * GET /localvpn/payment/{order}/success
     */
    public function paymentSuccess(Order $order)
    {
        if (auth()->id() !== $order->user_id) {
            abort(403);
        }

        $metadata = $order->metadata ?? [];
        $plan = $metadata['plan'] ?? 'monthly';
        $planInfo = self::PRICING[$plan] ?? self::PRICING['monthly'];

        $licenses = LicenseKey::where('order_id', $order->id)->get();

        return view('localvpn.payment-success', [
            'order' => $order,
            'planInfo' => $planInfo,
            'licenses' => $licenses,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Generate unique order number with LV prefix.
     */
    protected function generateOrderNumber(): string
    {
        $prefix = 'LV' . date('Ymd');
        $random = strtoupper(Str::random(4));

        return $prefix . '-' . $random;
    }
}
