<?php

namespace App\Support;

use App\Models\Order;
use App\Support\Quotation\VatMode;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Carbon\CarbonInterface;

/**
 * The receipt (ใบเสร็จรับเงิน) of a paid shop order, as a PDF.
 *
 * One builder for every way a customer gets it — the order page, the member area, the admin
 * copy and the payment-confirmed e-mail — so the four can never disagree.
 *
 * Everything printed is read off the order row: nothing is recalculated, so a receipt already
 * in somebody's inbox prints the same figures when it is downloaded again next year. The number
 * is derived from the order number rather than stored, which keeps it stable without a column.
 */
class OrderReceipt
{
    public const TIMEZONE = 'Asia/Bangkok';

    /**
     * A receipt says money was received. An order still waiting on its slip gets none.
     */
    public static function available(Order $order): bool
    {
        return $order->payment_status === 'paid';
    }

    public static function number(Order $order): string
    {
        return 'RC-' . ($order->order_number ?: str_pad((string) $order->id, 6, '0', STR_PAD_LEFT));
    }

    public static function filename(Order $order): string
    {
        // The order number is ours (XM20260928-AB12), but a stray character from an older flow
        // would end up in a Content-Disposition header, so it is cut down to what a filename needs.
        return preg_replace('/[^A-Za-z0-9_-]/', '', self::number($order)) . '.pdf';
    }

    public static function pdf(Order $order): DomPdfWrapper
    {
        return ThaiPdf::view('receipt.pdf', [
            'doc' => self::data($order),
            'companyInfo' => Letterhead::info(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function data(Order $order): array
    {
        $order->loadMissing('items.product', 'user');

        $subtotal = (float) $order->subtotal;
        $tax = (float) $order->tax;
        $discount = (float) $order->discount;
        $total = (float) $order->total;
        $paidAmount = (float) ($order->payment_display_amount ?? $total);

        $paidAt = self::paidAt($order);

        return [
            'number' => self::number($order),
            'order_number' => $order->order_number,
            'issued' => $paidAt->format('d/m/Y'),
            'paid_at' => $paidAt->format('d/m/Y H:i'),
            'ordered' => $order->created_at?->copy()->timezone(self::TIMEZONE)->format('d/m/Y'),
            'payment_method' => self::paymentMethodLabel($order->payment_method),
            'payment_method_en' => self::paymentMethodLabel($order->payment_method, 'en'),
            'items' => $order->items->map(fn ($item) => [
                'name' => $item->product_name ?: ($item->product->name ?? '-'),
                'sku' => $item->product->sku ?? null,
                'quantity' => (int) $item->quantity,
                'price' => (float) $item->price,
                'amount' => (float) $item->subtotal,
            ])->values()->all(),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'vat_label' => self::vatLabel($subtotal, $tax),
            'vat_label_en' => self::vatLabel($subtotal, $tax, 'en'),
            'discount' => $discount,
            'coupon_code' => $order->coupon_code,
            'total' => $total,
            'total_words' => VatMode::bahtText($total),
            'total_words_en' => AmountInWords::baht($total),
            // The SMS-matched checkout asks for a few extra satang so the bank message can be
            // told apart from every other transfer of the same price. The receipt is for the
            // order's price; the odd satang are shown on their own line so nobody reconciling
            // the bank statement thinks the two disagree.
            'paid_amount' => abs($paidAmount - $total) >= 0.005 ? $paidAmount : null,
            'customer' => [
                'name' => $order->customer_name ?: ($order->user->name ?? '-'),
                'email' => $order->customer_email ?: ($order->user->email ?? ''),
                'phone' => $order->customer_phone ?: '',
            ],
            'order_url' => $order->user_id ? route('customer.orders.show', $order) : null,
        ];
    }

    /**
     * When the money arrived. Older orders were marked paid without stamping paid_at, so the
     * SMS match and finally the last update stand in — the same fallback the e-mail uses.
     */
    protected static function paidAt(Order $order): CarbonInterface
    {
        $at = $order->paid_at ?? $order->sms_verified_at ?? $order->updated_at ?? $order->created_at ?? now();

        return $at->copy()->timezone(self::TIMEZONE);
    }

    /**
     * The payment method as printed — Thai by default, 'en' for the English half.
     */
    public static function paymentMethodLabel(?string $method, string $lang = 'th'): string
    {
        [$th, $en] = match ($method) {
            'promptpay' => ['พร้อมเพย์', 'PromptPay'],
            'bank_transfer' => ['โอนเงินผ่านธนาคาร', 'Bank transfer'],
            'credit_card', 'card' => ['บัตรเครดิต/เดบิต', 'Credit/debit card'],
            'stripe' => ['บัตรเครดิต/เดบิต (Stripe)', 'Credit/debit card (Stripe)'],
            'wallet' => ['XMAN Wallet', 'XMAN Wallet'],
            null, '' => ['-', '-'],
            default => [$method, $method],
        };

        return $lang === 'en' ? $en : $th;
    }

    /**
     * The shop adds VAT on top of the item prices (config app.vat_rate). The rate is printed
     * only when the row's tax really is that rate of the subtotal, so an order taken under a
     * different rate, or by a flow that charged none, is not labelled with a number it never used.
     */
    protected static function vatLabel(float $subtotal, float $tax, string $lang = 'th'): string
    {
        $rate = (float) config('app.vat_rate', 0.07);
        $label = $lang === 'en' ? 'VAT' : 'ภาษีมูลค่าเพิ่ม';

        if ($subtotal > 0 && abs(round($subtotal * $rate, 2) - $tax) < 0.02) {
            return $label . ' ' . rtrim(rtrim(number_format($rate * 100, 2), '0'), '.') . '%';
        }

        return $label;
    }
}
