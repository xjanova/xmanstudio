<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\RentalPayment;
use App\Support\PaymentSlips;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The only way to a customer's payment slip. The files live outside the web root
 * (App\Support\PaymentSlips); this hands one to the customer who sent it or to an admin — the
 * "view-payment-slip" gate — for the order page, the admin order page and the admin rental list.
 */
class PaymentSlipController extends Controller
{
    /** GET /payment-slips/orders/{order} — the slip on a cart order or a product checkout. */
    public function order(Request $request, Order $order): Response
    {
        return $this->serve($request, $order, PaymentSlips::forOrder($order), 'slip-' . $order->order_number);
    }

    /** GET /payment-slips/rentals/{payment} — the slip on a rental payment. */
    public function rental(Request $request, RentalPayment $payment): Response
    {
        return $this->serve($request, $payment, $payment->transfer_slip_url, 'slip-' . ($payment->payment_reference ?: 'rental-' . $payment->id));
    }

    private function serve(Request $request, Order|RentalPayment $owner, ?string $stored, string $name): Response
    {
        // One answer for "not signed in", "not yours" and "no slip", so walking the ids tells a
        // stranger nothing about which orders exist or carry a slip.
        abort_unless($request->user()?->can('view-payment-slip', $owner), 404);

        return PaymentSlips::response($stored, $name) ?? abort(404);
    }
}
