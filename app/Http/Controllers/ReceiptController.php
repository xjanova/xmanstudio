<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\OrderReceipt;
use Illuminate\Support\Facades\Auth;

/**
 * The receipt of a paid shop order, as a PDF download.
 *
 * The customer reaches it from the order page and the member area's order history, the admin
 * from the order screen. The same document is attached to the payment-confirmed e-mail — see
 * App\Support\OrderReceipt, which all of them print through.
 */
class ReceiptController extends Controller
{
    public function download(Order $order)
    {
        // Somebody else's order is a 404, not a 403: the id in the address is a counter, and a
        // different answer for "exists but not yours" would tell a scan which orders exist.
        abort_unless($order->user_id !== null && $order->user_id === Auth::id(), 404);

        return $this->pdf($order);
    }

    public function adminDownload(Order $order)
    {
        return $this->pdf($order);
    }

    protected function pdf(Order $order)
    {
        if (! OrderReceipt::available($order)) {
            return redirect()
                ->back()
                ->with('error', 'ออกใบเสร็จได้หลังยืนยันการชำระเงินแล้วเท่านั้น');
        }

        return OrderReceipt::pdf($order)->download(OrderReceipt::filename($order));
    }
}
