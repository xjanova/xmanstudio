<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\TpixPayment;
use App\Models\WalletTopup;
use App\Services\TpixPay\TpixCheckout;
use App\Services\TpixPay\TpixMath;
use App\Services\TpixPay\TpixPaymentService;
use App\Services\TpixPay\TpixPaySettings;
use App\Services\TpixPay\TpixPriceService;
use App\Services\TpixPay\TpixQuoteException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

/**
 * The TPIX pay page: the exact amount, the address, a QR (EIP-681), "pay with MetaMask", a box for
 * the transaction hash, and a status poll. Only the quote's owner sees or submits it.
 *
 * Nothing posted here is trusted beyond "look at this transaction": the amount, the address and the
 * outcome come from the quote and the chain (TpixPaymentService).
 */
class TpixPayController extends Controller
{
    public function show(Request $request, TpixPayment $tpixPayment): View
    {
        $this->authorizeView($request, $tpixPayment);

        $payment = $tpixPayment->load('payable');

        $qrSvg = null;
        try {
            $qrSvg = (string) QrCode::format('svg')->size(220)->margin(1)->errorCorrection('M')->generate($payment->paymentUri());
        } catch (Throwable $e) {
            Log::warning('TPIX QR not rendered', ['error' => $e->getMessage()]);
        }

        return view('tpix-pay.show', [
            'payment' => $payment,
            'qrSvg' => $qrSvg,
            'valueHex' => TpixMath::weiToHex($payment->amount_wei),
            'rateFormatted' => TpixPriceService::formatRate((string) $payment->rate_thb),
            'requoteUrl' => $this->requoteUrl($payment),
            'returnUrl' => $this->returnUrl($payment),
            'requiredConfirmations' => app(TpixPaySettings::class)->confirmations(),
        ]);
    }

    /** The pay page asks every few seconds; at most one chain/explorer look per quote per 8 seconds. */
    public function status(Request $request, TpixPayment $tpixPayment): JsonResponse
    {
        $this->authorizeOwner($request, $tpixPayment);

        if (! $tpixPayment->isSettled() && Cache::add('tpix_pay:poll:' . $tpixPayment->id, 1, 8)) {
            try {
                app(TpixPaymentService::class)->detectFor($tpixPayment);
            } catch (Throwable $e) {
                Log::info('TPIX status poll could not check the chain', ['payment' => $tpixPayment->reference, 'error' => $e->getMessage()]);
            }
        }

        return response()->json($this->statusPayload($tpixPayment->fresh()));
    }

    public function submit(Request $request, TpixPayment $tpixPayment): JsonResponse|RedirectResponse
    {
        $this->authorizeOwner($request, $tpixPayment);

        $validated = $request->validate([
            'tx_hash' => ['required', 'string', 'max:80'],
        ], [
            'tx_hash.required' => 'กรุณาวางเลขธุรกรรม (tx hash)',
        ]);

        $outcome = app(TpixPaymentService::class)->submitTx($tpixPayment, $validated['tx_hash']);

        if ($request->expectsJson()) {
            return response()->json($outcome + $this->statusPayload($tpixPayment->fresh()));
        }

        $ok = in_array($outcome['result'], ['paid', 'credited_wallet', 'settled', 'confirming', 'late', 'unknown'], true);

        return redirect()->route('tpix-pay.show', $tpixPayment)->with($ok ? 'success' : 'error', $outcome['message']);
    }

    /** A new quote for an order still waiting for TPIX (the last one expired, or the price moved). */
    public function quoteOrder(Request $request, Order $order): RedirectResponse
    {
        if ((int) $order->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ($order->payment_method !== TpixCheckout::METHOD || ! TpixPaymentService::orderIsPayable($order)) {
            return redirect()->route('orders.show', $order)->with('error', 'คำสั่งซื้อนี้ไม่ได้รอชำระด้วย TPIX');
        }

        if ($open = TpixCheckout::openQuoteFor($order)) {
            return redirect()->route('tpix-pay.show', $open);
        }

        $previous = TpixCheckout::latestQuoteFor($order);

        return TpixCheckout::redirectForOrder($order, $previous?->meta['return_url'] ?? null);
    }

    /** A new quote for a TPIX wallet top-up still pending. */
    public function quoteTopup(Request $request, WalletTopup $topup): RedirectResponse
    {
        if ((int) $topup->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ($topup->payment_method !== TpixCheckout::METHOD || $topup->status !== WalletTopup::STATUS_PENDING) {
            return redirect()->route('user.wallet.topup-status', $topup)->with('error', 'รายการนี้ไม่ได้รอชำระด้วย TPIX');
        }

        if ($open = TpixCheckout::openQuoteFor($topup)) {
            return redirect()->route('tpix-pay.show', $open);
        }

        try {
            $payment = TpixCheckout::quoteForTopup($topup);
        } catch (TpixQuoteException $e) {
            return redirect()->route('user.wallet.topup-status', $topup)->with('error', $e->getMessage());
        }

        return redirect()->route('tpix-pay.show', $payment);
    }

    /** @return array<string, mixed> */
    private function statusPayload(TpixPayment $payment): array
    {
        $settled = $payment->isSettled();

        return [
            'status' => $payment->status,
            'label' => $payment->statusLabel(),
            'settled' => $settled,
            'payable' => $payment->isPayable(),
            'confirmations' => (int) $payment->confirmations,
            'required_confirmations' => app(TpixPaySettings::class)->confirmations(),
            'tx_hash' => $payment->tx_hash,
            'explorer_url' => $payment->explorerTxUrl(),
            'expires_at' => $payment->expires_at?->toIso8601String(),
            'seconds_left' => $payment->expires_at ? max(0, now()->diffInSeconds($payment->expires_at, false)) : 0,
            'redirect' => $settled ? $this->returnUrl($payment) : null,
        ];
    }

    private function returnUrl(TpixPayment $payment): string
    {
        $url = $payment->meta['return_url'] ?? null;

        // Written by this app when the quote was made; still refuse anything that leaves the site
        if (is_string($url) && str_starts_with($url, url('/'))) {
            return $url;
        }

        return match (true) {
            $payment->payable instanceof Order => route('orders.show', $payment->payable),
            $payment->payable instanceof WalletTopup => route('user.wallet.topup-status', $payment->payable),
            default => route('user.wallet.index'),
        };
    }

    private function requoteUrl(TpixPayment $payment): ?string
    {
        $payable = $payment->payable;

        if ($payable instanceof Order && $payable->payment_method === TpixCheckout::METHOD && TpixPaymentService::orderIsPayable($payable)) {
            return route('orders.tpix-quote', $payable);
        }

        if ($payable instanceof WalletTopup && $payable->payment_method === TpixCheckout::METHOD && $payable->status === WalletTopup::STATUS_PENDING) {
            return route('user.wallet.tpix-quote', $payable);
        }

        return null;
    }

    private function authorizeOwner(Request $request, TpixPayment $payment): void
    {
        if ((int) $payment->user_id !== (int) $request->user()->id) {
            abort(403);
        }
    }

    private function authorizeView(Request $request, TpixPayment $payment): void
    {
        $user = $request->user();
        if ((int) $payment->user_id !== (int) $user->id && ! $user->isAdmin()) {
            abort(403);
        }
    }
}
