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
use App\Services\LicenseService;
use App\Services\ThaiPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * SmsChecker Web Controller
 *
 * Handles web pages for SmsChecker product (pricing, checkout, payment).
 * Supports wallet payment with instant license generation + HWID binding.
 */
class SmsCheckerController extends Controller
{
    private const WALLET_DISCOUNT_PERCENT = 10;

    private const PRICING = [
        'monthly' => [
            'name' => 'Monthly',
            'name_th' => 'รายเดือน',
            'price' => 499,
            'duration_days' => 30,
            'license_type' => 'monthly',
            'features' => [
                'ตรวจจับ SMS อัตโนมัติ',
                'เชื่อมต่อหลายเซิร์ฟเวอร์',
                'ซัพพอร์ตมาตรฐาน',
            ],
        ],
        'yearly' => [
            'name' => 'Yearly',
            'name_th' => 'รายปี',
            'price' => 4990,
            'duration_days' => 365,
            'license_type' => 'yearly',
            'features' => [
                'ตรวจจับ SMS อัตโนมัติ',
                'เชื่อมต่อหลายเซิร์ฟเวอร์',
                'ซัพพอร์ตพรีเมียม',
                'อัพเดทก่อนใคร',
            ],
        ],
        'lifetime' => [
            'name' => 'Lifetime',
            'name_th' => 'ตลอดชีพ',
            'price' => 29000,
            'duration_days' => null,
            'license_type' => 'lifetime',
            'features' => [
                'ตรวจจับ SMS อัตโนมัติ',
                'เชื่อมต่อหลายเซิร์ฟเวอร์',
                'ซัพพอร์ตพรีเมียม',
                'อัพเดทตลอดชีพ',
                'ใช้ได้หลายเครื่อง',
            ],
        ],
    ];

    public function detail()
    {
        $product = Product::where('slug', 'smschecker')->first();

        $version = null;
        if ($product) {
            $version = ProductVersion::where('product_id', $product->id)
                ->where('is_active', true)
                ->orderByDesc('version')
                ->first();
        }

        return view('smschecker.detail', [
            'pricing' => self::PRICING,
            'product' => $product,
            'version' => $version,
        ]);
    }

    public function pricing(Request $request)
    {
        $machineId = $request->query('machine_id') ?? session('smschecker_machine_id');

        if ($machineId) {
            session(['smschecker_machine_id' => $machineId]);
        }

        return view('smschecker.pricing', [
            'machineId' => $machineId,
            'pricing' => self::PRICING,
        ]);
    }

    public function checkout(Request $request, string $plan)
    {
        if (! isset(self::PRICING[$plan])) {
            abort(404, 'Plan not found');
        }

        $product = Product::where('slug', 'smschecker')->firstOrFail();
        $machineId = $request->query('machine_id') ?? session('smschecker_machine_id');
        $planInfo = self::PRICING[$plan];

        $wallet = auth()->check() ? Wallet::getOrCreateForUser(auth()->id()) : null;
        $walletDiscount = (int) floor($planInfo['price'] * self::WALLET_DISCOUNT_PERCENT / 100);
        $walletPrice = $planInfo['price'] - $walletDiscount;

        return view('smschecker.checkout', [
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

        $machineId = $validated['machine_id'] ?? session('smschecker_machine_id');
        $product = Product::where('slug', 'smschecker')->firstOrFail();
        $planInfo = self::PRICING[$plan];
        $isWallet = $validated['payment_method'] === 'wallet';

        $subtotal = $planInfo['price'];
        $discount = 0;
        $finalPrice = $subtotal;

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

        $affiliateService = app(AffiliateCommissionService::class);
        $affiliate = $affiliateService->resolveAffiliate(auth()->id());

        $metadata = [
            'plan' => $plan,
            'license_type' => $planInfo['license_type'],
            'machine_id' => $machineId,
            'wallet_discount_percent' => $isWallet ? self::WALLET_DISCOUNT_PERCENT : 0,
            'affiliate_code' => $affiliate?->referral_code,
        ];

        $notes = "SmsChecker {$planInfo['name']} License | Plan: {$plan} | Type: {$planInfo['license_type']}";

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

        if ($isWallet) {
            $transaction = $wallet->pay(
                $finalPrice,
                "ชำระ SmsChecker {$planInfo['name_th']} License (ลด {$discount}฿)",
                'App\Models\Order',
                $order->id
            );

            if ($transaction) {
                $order->update(['wallet_transaction_id' => $transaction->id]);
            } else {
                $order->update(['status' => 'cancelled', 'payment_status' => 'failed']);

                return redirect()->back()->with('error', 'ชำระเงินไม่สำเร็จ กรุณาลองใหม่');
            }

            $this->generateLicenseForOrder($order, $product, $planInfo, $machineId);

            if ($affiliate) {
                $affiliateService->recordCommission(
                    $affiliate, $order->total, $order->id, $order->user_id,
                    'smschecker', $order->id, "SmsChecker {$planInfo['name']} License"
                );
            }

            return redirect()->route('smschecker.payment-success', $order->id);
        }

        if ($affiliate) {
            $affiliateService->recordCommission(
                $affiliate, $order->total, $order->id, $order->user_id,
                'smschecker', $order->id, "SmsChecker {$planInfo['name']} License"
            );
        }

        return redirect()->route('smschecker.payment', ['order' => $order->id]);
    }

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
                Log::error('SmsCheckerController: License generation returned empty', ['order_id' => $order->id]);

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
                Log::info('SmsCheckerController: License activated on HWID', [
                    'order_id' => $order->id,
                    'license_key' => $license->license_key,
                    'machine_id' => $machineId,
                ]);
            }

            $metadata = json_decode($order->metadata ?? '{}', true);
            $metadata['license_key'] = $license->license_key;
            $metadata['license_id'] = $license->id;
            $metadata['hwid_bound'] = ! empty($machineId);
            $order->update(['metadata' => json_encode($metadata)]);

            // Send payment confirmed email with license keys
            if ($order->customer_email && PaymentSetting::get('mail_enabled', true)) {
                try {
                    Mail::to($order->customer_email)
                        ->send(new PaymentConfirmedMail($order->fresh(['items.product', 'user'])));
                } catch (\Exception $mailError) {
                    Log::error('SmsChecker: Failed to send payment email', [
                        'order_id' => $order->id,
                        'error' => $mailError->getMessage(),
                    ]);
                }
            }

            return $license;
        } catch (\Exception $e) {
            Log::error('SmsCheckerController: License generation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

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
            $paymentInfo = $paymentService->generatePromptPayQR($order->total, (string) $order->id);
        } elseif ($order->payment_method === 'bank_transfer') {
            $paymentInfo = $paymentService->getBankTransferInfo();
            $bankAccounts = BankAccount::active()->ordered()->get();
        }

        return view('smschecker.payment', [
            'order' => $order,
            'planInfo' => $planInfo,
            'paymentInfo' => $paymentInfo,
            'bankAccounts' => $bankAccounts,
        ]);
    }

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

        $slipPath = app(\App\Services\ImageService::class)->storeAsWebp(
            $request->file('payment_slip'), 'payment-slips/smschecker',
        );

        $metadata = json_decode($order->metadata ?? '{}', true);
        $metadata['payment_slip'] = $slipPath;
        $metadata['payment_submitted_at'] = now()->toISOString();
        $metadata['payment_notes'] = $validated['notes'] ?? null;

        $order->update([
            'status' => 'processing',
            'metadata' => json_encode($metadata),
        ]);

        return redirect()->route('smschecker.payment-success', $order->id);
    }

    public function paymentSuccess(Order $order)
    {
        if ($order->user_id && auth()->id() !== $order->user_id) {
            abort(403);
        }

        $metadata = json_decode($order->metadata ?? '{}', true);
        $plan = $metadata['plan'] ?? 'monthly';
        $planInfo = self::PRICING[$plan] ?? self::PRICING['monthly'];
        $licenses = LicenseKey::where('order_id', $order->id)->get();

        return view('smschecker.payment-success', [
            'order' => $order,
            'planInfo' => $planInfo,
            'licenses' => $licenses,
            'metadata' => $metadata,
        ]);
    }

    public function buyRedirect(Request $request)
    {
        $plan = $request->query('plan');
        $machineId = $request->query('machine_id');

        if ($machineId) {
            session(['smschecker_machine_id' => $machineId]);
        }

        if ($plan && in_array($plan, ['monthly', 'yearly', 'lifetime'])) {
            $url = route('smschecker.checkout', $plan);
            if ($machineId) {
                $url .= '?machine_id=' . $machineId;
            }

            return redirect($url);
        }

        $url = route('smschecker.pricing');
        if ($machineId) {
            $url .= '?machine_id=' . $machineId;
        }

        return redirect($url);
    }

    public function downloadPage()
    {
        $product = Product::where('slug', 'smschecker')->first();

        $version = null;
        if ($product) {
            $version = ProductVersion::where('product_id', $product->id)
                ->where('is_active', true)
                ->orderByDesc('version')
                ->first();
        }

        return view('smschecker.download', [
            'product' => $product,
            'version' => $version,
        ]);
    }

    public function downloadApk()
    {
        $product = Product::where('slug', 'smschecker')->firstOrFail();
        $version = ProductVersion::where('product_id', $product->id)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();

        if (! $version || ! $version->github_release_url) {
            return redirect()->route('smschecker.download')
                ->with('error', 'ยังไม่มีไฟล์สำหรับดาวน์โหลด กรุณาลองใหม่ภายหลัง');
        }

        $githubSetting = $product->githubSetting;

        if (! $githubSetting) {
            return redirect()->route('smschecker.download')
                ->with('error', 'ระบบดาวน์โหลดยังไม่พร้อม');
        }

        return $this->proxyGithubDownload($githubSetting, $version);
    }

    protected function proxyGithubDownload($githubSetting, ProductVersion $productVersion): StreamedResponse
    {
        $token = $githubSetting->github_token_decrypted;
        $assetUrl = $productVersion->github_release_url;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/octet-stream',
            'User-Agent' => 'XMAN-SmsChecker-Download-Proxy',
        ])->withOptions([
            'allow_redirects' => false,
        ])->get($assetUrl);

        if ($response->status() === 302) {
            $downloadUrl = $response->header('Location');
        } else {
            $downloadUrl = $assetUrl;
        }

        $filename = $productVersion->download_filename ?? 'SmsChecker-v' . $productVersion->version . '.apk';
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
                    'User-Agent: XMAN-SmsChecker-Download-Proxy',
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
        $prefix = 'SC' . date('Ymd');
        $random = strtoupper(Str::random(4));

        return $prefix . '-' . $random;
    }
}
