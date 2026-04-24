<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Order;
use App\Services\AffiliateCommissionService;
use App\Services\AixmanService;
use App\Services\ThaiPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            'page'    => '',
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
            'customer_name'  => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'payment_method' => 'required|in:promptpay,bank_transfer',
        ]);

        $price = (int) $package['price_thb'];
        $metadata = [
            'source'         => 'xdreamer',
            'package_slug'   => $package['slug'],
            'package_name'   => $package['name'],
            'credits'        => (int) ($package['credits'] ?? 0),
            'bonus_credits'  => (int) ($package['bonus_credits'] ?? 0),
            'ref'            => session('ai_credit_ref'),
        ];

        $order = Order::create([
            'user_id'         => auth()->id(),
            'order_number'    => $this->generateOrderNumber(),
            'customer_name'   => $validated['customer_name'],
            'customer_email'  => $validated['customer_email'],
            'customer_phone'  => $validated['customer_phone'],
            'subtotal'        => $price,
            'discount'        => 0,
            'total'           => $price,
            'status'          => 'pending',
            'payment_method'  => $validated['payment_method'],
            'notes'           => "AI Credits · {$package['name']} · {$metadata['credits']} credits"
                . ($metadata['bonus_credits'] ? " +{$metadata['bonus_credits']} bonus" : ''),
            'metadata'        => json_encode($metadata),
        ]);

        $order->items()->create([
            'product_id'   => null, // virtual product — no row in products table
            'product_name' => 'AI Credits · '.$package['name'],
            'quantity'     => 1,
            'price'        => $price,
            'subtotal'     => $price,
        ]);

        // Affiliate commission (standard pipeline, per CLAUDE.md)
        $affiliateService = app(AffiliateCommissionService::class);
        $affiliate = $affiliateService->resolveAffiliate(auth()->id());
        if ($affiliate) {
            $order->update([
                'affiliate_id'  => $affiliate->id,
                'referral_code' => $affiliate->referral_code,
            ]);
            $affiliateService->recordCommission(
                $affiliate, $price, $order->id, auth()->id(),
                'ai_credits', $order->id, "AI Credits {$package['name']}"
            );
        }

        return redirect()->route('xdreamer.checkout.payment', $order->id);
    }

    /**
     * Show payment page (PromptPay QR or bank transfer info).
     */
    public function payment(Order $order)
    {
        $this->authorizeOrder($order);

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
            'order'        => $order,
            'paymentInfo'  => $paymentInfo,
            'bankAccounts' => $bankAccounts,
            'page'         => '',
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
            'notes'        => 'nullable|string|max:500',
        ]);

        $slipPath = app(\App\Services\ImageService::class)
            ->storeAsWebp($request->file('payment_slip'), 'payment-slips/ai-credits');

        $metadata = $order->metadata ?: [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }
        $metadata['payment_slip'] = $slipPath;
        $metadata['payment_submitted_at'] = now()->toISOString();
        $metadata['payment_notes'] = $validated['notes'] ?? null;

        $order->update([
            'status'   => 'processing',
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

        $metadata = is_array($order->metadata)
            ? $order->metadata
            : (json_decode($order->metadata ?? '{}', true) ?: []);

        // If order is paid and we haven't notified AIXMAN yet, do so now.
        $alreadyNotified = ! empty($metadata['aixman_notified_at']);
        if ($order->payment_status === 'paid' && ! $alreadyNotified && $order->user_id) {
            $ok = $this->aixman->notifyCreditPurchase(
                (int) $order->user_id,
                (string) ($metadata['package_slug'] ?? ''),
                (int) $order->id,
                (int) ($metadata['credits'] ?? 0),
                (int) ($metadata['bonus_credits'] ?? 0),
            );
            if ($ok) {
                $metadata['aixman_notified_at'] = now()->toISOString();
                $order->update(['metadata' => json_encode($metadata)]);
            }
        }

        return view('xdreamer.success', [
            'order'    => $order,
            'metadata' => $metadata,
            'page'     => '',
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

    private function generateOrderNumber(): string
    {
        return 'AIC-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
    }
}
