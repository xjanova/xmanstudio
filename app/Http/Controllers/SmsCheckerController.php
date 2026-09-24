<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesReleaseDownloads;
use App\Mail\PaymentConfirmedMail;
use App\Models\BankAccount;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\Wallet;
use App\Services\AffiliateCommissionService;
use App\Services\GithubReleaseService;
use App\Services\ImageService;
use App\Services\LicenseService;
use App\Services\ThaiPaymentService;
use App\Support\LicensePlans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * SmsChecker Web Controller
 *
 * Handles web pages for SmsChecker product (pricing, checkout, payment).
 * Supports wallet payment with instant license generation + HWID binding.
 */
class SmsCheckerController extends Controller
{
    use ServesReleaseDownloads;

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
                'ตรวจจับ SMS อัตโนมัติ',
                'เชื่อมต่อหลายเซิร์ฟเวอร์',
                'ซัพพอร์ตมาตรฐาน',
            ],
        ],
        'yearly' => [
            'name' => 'Yearly',
            'name_th' => 'รายปี',
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

    /** PLANS with each price from config/licenses.php — only the terms the store sells. */
    private static function pricedPlans(): array
    {
        return LicensePlans::priced('smschecker', self::PLANS);
    }

    public function detail(GithubReleaseService $github)
    {
        $this->abortUnlessOnSale();

        $product = Product::where('slug', 'smschecker')->first();

        $version = null;
        if ($product) {
            // แหล่งความจริงเดียวกับ API เช็คอัพเดท + ตามทัน GitHub เอง (read-through)
            // เดิม orderByDesc('version') เป็น string sort ทำให้ "2.0.99" ชนะ "2.0.176" ได้
            $version = $github->latestVersionFresh($product);
        }

        return view('smschecker.detail', [
            'pricing' => self::pricedPlans(),
            'product' => $product,
            'version' => $version,
        ]);
    }

    public function pricing(Request $request)
    {
        $this->abortUnlessOnSale();

        $machineId = $request->query('machine_id') ?? session('smschecker_machine_id');

        if ($machineId) {
            session(['smschecker_machine_id' => $machineId]);
        }

        return view('smschecker.pricing', [
            'machineId' => $machineId,
            'pricing' => self::pricedPlans(),
        ]);
    }

    public function checkout(Request $request, string $plan)
    {
        $this->abortUnlessOnSale();

        if (! array_key_exists($plan, self::pricedPlans())) {
            abort(404, 'Plan not found');
        }

        $product = Product::where('slug', 'smschecker')->firstOrFail();
        $machineId = $request->query('machine_id') ?? session('smschecker_machine_id');
        $planInfo = self::pricedPlans()[$plan];

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
        $this->abortUnlessOnSale();

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

        $machineId = $validated['machine_id'] ?? session('smschecker_machine_id');
        $product = Product::where('slug', 'smschecker')->firstOrFail();
        $planInfo = self::pricedPlans()[$plan];
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

            // The license is out, so the order is complete (as LicenseService does for every other
            // checkout). Left at processing, every "has this customer bought it?" check said no.
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
        $planInfo = self::pricedPlans()[$plan] ?? self::pricedPlans()['monthly'];

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

        $slipPath = app(ImageService::class)->storeAsWebp(
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
        $planInfo = self::pricedPlans()[$plan] ?? self::pricedPlans()['monthly'];
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
        $this->abortUnlessOnSale();

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

    public function downloadPage(GithubReleaseService $github)
    {
        $this->abortUnlessOnSale();

        $product = Product::where('slug', 'smschecker')->first();

        $version = null;
        if ($product) {
            // แหล่งความจริงเดียวกับ API เช็คอัพเดท + ตามทัน GitHub เอง (read-through)
            $version = $github->latestVersionFresh($product);
        }

        return view('smschecker.download', [
            'product' => $product,
            'version' => $version,
        ]);
    }

    /**
     * APK ของ SMS Checker — ไฟล์ส่งจาก xman4289.com เอง ลูกค้าไม่เห็น GitHub (ReleaseDownloadStreamer)
     * และจองที่ในช่องส่งไฟล์ร่วมของทั้งเว็บ (config/downloads.php) เหมือนแอปอื่น
     *
     * GET /smschecker/download/apk — ตัวเช็คอัปเดตในแอปส่งลูกค้ามาที่นี่ จึงเปิดอยู่แม้ปิดขาย
     * (ไม่เรียก abortUnlessOnSale — ลูกค้าที่ถือ license อยู่ต้องอัปเดตได้)
     */
    public function downloadApk(Request $request)
    {
        $product = Product::where('slug', 'smschecker')->first();
        $backTo = $this->apkBackTo($request, $product);

        if (! $product) {
            return $this->downloadUnavailable($request, $backTo, 404, 'Product not found', 'ยังไม่มีไฟล์สำหรับดาวน์โหลด กรุณาลองใหม่ภายหลัง');
        }

        // ต้องตรงกับเวอร์ชันที่ API เช็คอัพเดทโฆษณาไว้เป๊ะ (latestVersionFresh ตัวเดียวกัน) ไม่งั้นจะบอกลูกค้าว่ามี 2.0.176
        // แต่เสิร์ฟไฟล์เก่ากว่า → ติดตั้งไม่ได้เพราะ Android กัน downgrade
        return $this->serveApk($request, $product, $this->latestRelease($product), $backTo, 'SmsChecker');
    }

    /**
     * ส่งไฟล์ไม่ได้ พาเบราว์เซอร์ไปหน้าที่เปิดได้จริงพร้อมข้อความ — ระหว่างปิดขายหน้า SMS Checker ทุกหน้าตอบ 404
     * คนที่ล็อกอินอยู่มาจากศูนย์ดาวน์โหลด (ทางเดียวที่ยังมีปุ่มนี้) จึงกลับไปที่นั่น · คนอื่นไปหน้าแรก
     */
    private function apkBackTo(Request $request, ?Product $product): string
    {
        return match (true) {
            (bool) $product?->is_active => route('smschecker.download'),
            $request->user() !== null => route('customer.downloads'),
            default => route('home'),
        };
    }

    protected function generateOrderNumber(): string
    {
        $prefix = 'SC' . date('Ymd');
        $random = strtoupper(Str::random(4));

        return $prefix . '-' . $random;
    }

    /**
     * Sales are closed until the owner says otherwise (2026-09-23: the prices on
     * the pages and in the code disagreed). Every page that shows or sells the
     * product answers 404 while the smschecker product is switched off in
     * แอดมิน → สินค้า; switching it back on reopens them, no deploy needed.
     *
     * Deliberately NOT gated: the APK download (the app's own update check points
     * there, and 8 customers hold active licences), the licence API, and the
     * payment pages of orders already placed.
     */
    private function abortUnlessOnSale(): void
    {
        abort_unless(Product::where('slug', 'smschecker')->where('is_active', true)->exists(), 404);
    }
}
