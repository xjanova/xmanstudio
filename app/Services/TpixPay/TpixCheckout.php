<?php

namespace App\Services\TpixPay;

use App\Models\Order;
use App\Models\TpixPayment;
use App\Models\WalletTopup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

/**
 * The glue every checkout uses for "เหรียญ TPIX": whether to offer it, and turning a just-created
 * order or top-up into a quote and the pay page.
 *
 * A checkout only has to (1) accept `tpix` in its payment_method rule when available() is true and
 * (2) hand the order it created to redirectForOrder(). Delivery afterwards goes through the same
 * OrderPaymentService::approve() an admin approval uses, so licences, AI credits and e-mails follow.
 */
class TpixCheckout
{
    public const METHOD = 'tpix';

    /** Offer TPIX: switched on, an address to receive at, and a price right now. */
    public static function available(): bool
    {
        return app(TpixPaySettings::class)->acceptsPayments()
            && app(TpixPriceService::class)->spot() !== null;
    }

    /**
     * A quote for the order's total. $returnUrl is where the pay page sends the customer once paid
     * (the product's own success page); always a URL this app generated, never one from the request.
     *
     * @throws TpixQuoteException
     */
    public static function quoteForOrder(Order $order, ?string $returnUrl = null): TpixPayment
    {
        if (! $order->user) {
            throw new TpixQuoteException('กรุณาเข้าสู่ระบบก่อนชำระด้วย TPIX');
        }

        return app(TpixPaymentService::class)->createQuote(
            TpixPayment::PURPOSE_ORDER,
            $order,
            $order->user,
            (string) $order->total,
            app(TpixPriceService::class)->fixedTotalForOrder($order),
            ['return_url' => $returnUrl ?? route('orders.show', $order)],
        );
    }

    /** Quote and go to the pay page; if no quote can be made, the order page explains and offers a retry. */
    public static function redirectForOrder(Order $order, ?string $returnUrl = null): RedirectResponse
    {
        try {
            $payment = self::quoteForOrder($order, $returnUrl);
        } catch (TpixQuoteException $e) {
            return redirect()->route('orders.show', $order)->with('error', $e->getMessage());
        }

        return redirect()->route('tpix-pay.show', $payment)
            ->with('success', "สร้างคำสั่งซื้อ #{$order->order_number} แล้ว — โอนเหรียญ TPIX ตามยอดด้านล่างภายในเวลาที่กำหนด");
    }

    /** @throws TpixQuoteException */
    public static function quoteForTopup(WalletTopup $topup): TpixPayment
    {
        return app(TpixPaymentService::class)->createQuote(
            TpixPayment::PURPOSE_WALLET_TOPUP,
            $topup,
            $topup->user,
            (string) $topup->amount,
            null,
            ['return_url' => route('user.wallet.topup-status', $topup)],
        );
    }

    /** The newest quote for this order/top-up that the customer can still pay. */
    public static function openQuoteFor(Model $payable): ?TpixPayment
    {
        return TpixPayment::where('payable_type', $payable->getMorphClass())
            ->where('payable_id', $payable->getKey())
            ->where('status', TpixPayment::STATUS_PENDING)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    /** The newest quote of any status (the order page shows what happened to it). */
    public static function latestQuoteFor(Model $payable): ?TpixPayment
    {
        return TpixPayment::where('payable_type', $payable->getMorphClass())
            ->where('payable_id', $payable->getKey())
            ->latest('id')
            ->first();
    }
}
