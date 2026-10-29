<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Order;
use App\Services\AffiliateCommissionService;
use App\Services\AiCreditDelivery;
use App\Services\AiCreditOrders;
use App\Services\AixmanService;
use App\Services\ThaiPaymentService;
use App\Services\TpixPay\TpixCheckout;
use App\Support\PaymentSlips;
use Illuminate\Http\Request;

/**
 * AiCreditCheckoutController
 *
 * Handles purchase flow for AIXMAN AI credit packages.
 * Pattern mirrors AutoTradeXController for consistency.
 *
 * Flow:
 *   GET  /checkout/ai-credits/{slug}               — show checkout form
 *   POST /checkout/ai-credits/{slug}               — create Order + redirect to payment
 *   GET  /checkout/ai-credits/payment/{order}      — show PromptPay QR / bank transfer
 *   POST /checkout/ai-credits/payment/{order}/confirm — upload slip
 *   GET  /checkout/ai-credits/payment/{order}/success — success + notify AIXMAN
 */
class AiCreditCheckoutController extends Controller
{
    public function __construct(private AixmanService $aixman) {}

    /**
     * Show checkout form for a package.
     */
    public function checkout(Request $request, string $slug)
    {
        $package = $this->aixman->getPackage($slug);
        if (! $package || (int) $package['price_thb'] <= 0) {
            abort(404, 'Package not found');
        }

        // If the user came from AIXMAN with ?ref=ai, stash it for analytics
        if ($request->query('ref') === 'ai') {
            session(['ai_credit_ref' => 'aixman']);
        }

        return view('xdreamer.checkout', [
            'package' => $package,
            // What the TPIX option costs (the package price less the TPIX discount)
            'tpixPricing' => app(AiCreditOrders::class)->pricing($package, TpixCheckout::METHOD),
            'page' => '',
        ]);
    }

    /**
     * Create order + redirect to payment.
     */
    public function processCheckout(Request $request, string $slug)
    {
        $package = $this->aixman->getPackage($slug);
        if (! $package || (int) $package['price_thb'] <= 0) {
            abort(404, 'Package not found');
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'payment_method' => 'required|in:promptpay,bank_transfer' . (TpixCheckout::available() ? ',' . TpixCheckout::METHOD : ''),
        ]);

        // Paid in TPIX, the package is cheaper (TpixPaySettings::aiCreditDiscountPercent()); every
        // other method pays the package price. Priced and written in one place (AiCreditOrders).
        $orders = app(AiCreditOrders::class);
        $pricing = $orders->pricing($package, $validated['payment_method']);

        // TPIX: refused before anything is written when this amount cannot be quoted now (no price
        // average yet, or more than the TPIX market behind the price can carry)
        if ($refused = TpixCheckout::refuse($validated['payment_method'], $pricing['total_thb'])) {
            return $refused;
        }

        $order = $orders->create(
            $request->user(),
            $package,
            $validated['payment_method'],
            [
                'name' => $validated['customer_name'],
                'email' => $validated['customer_email'],
                'phone' => $validated['customer_phone'],
            ],
            session('ai_credit_ref'),
            app(AffiliateCommissionService::class)->resolveAffiliate(auth()->id()),
        );

        // TPIX: quote and pay on the TPIX pay page; once paid it comes back to the success page,
        // which (like every paid path) sends the credits to AIXMAN
        if ($validated['payment_method'] === TpixCheckout::METHOD) {
            return TpixCheckout::redirectForOrder($order, route('xdreamer.checkout.success', $order->id));
        }

        return redirect()->route('xdreamer.checkout.payment', $order->id);
    }

    /**
     * Show payment page (PromptPay QR or bank transfer info).
     */
    public function payment(Order $order)
    {
        $this->authorizeOrder($order);

        // A TPIX order is paid on the TPIX pay page; its order page links there (or makes a new quote)
        if ($order->payment_method === TpixCheckout::METHOD) {
            return redirect()->route('orders.show', $order);
        }

        if (! $this->isAiCreditOrder($order)) {
            abort(404);
        }

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

        return view('xdreamer.payment', [
            'order' => $order,
            'paymentInfo' => $paymentInfo,
            'bankAccounts' => $bankAccounts,
            'page' => '',
        ]);
    }

    /**
     * Confirm payment with slip upload.
     */
    public function confirmPayment(Request $request, Order $order)
    {
        $this->authorizeOrder($order);

        if (! $this->isAiCreditOrder($order) || $order->status !== 'pending') {
            return back()->with('error', 'คำสั่งซื้อนี้ได้รับการดำเนินการแล้ว');
        }

        $validated = $request->validate([
            'payment_slip' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'notes' => 'nullable|string|max:500',
        ]);

        // Kept off the web root, like every payment slip (App\Support\PaymentSlips)
        $slipPath = PaymentSlips::storeAsWebp($request->file('payment_slip'), 'ai-credits');
        if ($slipPath === null) {
            return back()->with('error', 'บันทึกสลิปไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        $metadata = $order->metadata ?: [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }
        $metadata['payment_slip'] = $slipPath;
        $metadata['payment_submitted_at'] = now()->toISOString();
        $metadata['payment_notes'] = $validated['notes'] ?? null;

        $order->update([
            'status' => 'processing',
            'metadata' => json_encode($metadata),
        ]);

        return redirect()->route('xdreamer.checkout.success', $order->id);
    }

    /**
     * Payment success page — also fires AIXMAN webhook if order is paid + not yet notified.
     */
    public function success(Order $order)
    {
        $this->authorizeOrder($order);

        if (! $this->isAiCreditOrder($order)) {
            abort(404);
        }

        // If the order is paid and its credits have not reached AIXMAN yet, send them now — the
        // customer is looking at this page. Once-only across every path (see AiCreditDelivery).
        if (AiCreditDelivery::isOwed($order)) {
            app(AiCreditDelivery::class)->deliver($order);
        }

        return view('xdreamer.success', [
            'order' => $order,
            'metadata' => AiCreditDelivery::metadata($order),
            'page' => '',
        ]);
    }

    // ─── helpers ────────────────────────────────────────────────────────

    private function authorizeOrder(Order $order): void
    {
        if ($order->user_id && auth()->id() !== $order->user_id) {
            abort(403);
        }
    }

    private function isAiCreditOrder(Order $order): bool
    {
        $metadata = is_array($order->metadata)
            ? $order->metadata
            : (json_decode($order->metadata ?? '{}', true) ?: []);

        return ($metadata['source'] ?? null) === 'xdreamer';
    }
}
