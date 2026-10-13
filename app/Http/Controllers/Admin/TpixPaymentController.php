<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TpixPayment;
use App\Models\TpixPriceSnapshot;
use App\Services\TpixPay\TpixPaymentService;
use App\Services\TpixPay\TpixPaySettings;
use App\Services\TpixPay\TpixPriceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin: TPIX payments — the switch and the receive address, every quote and what became of it,
 * and the decisions only a person makes: re-check a quote on the chain, accept a transfer that
 * arrived after its quote expired (`late`), cancel a quote nobody paid.
 */
class TpixPaymentController extends Controller
{
    public function index(Request $request, TpixPaySettings $settings, TpixPriceService $prices): View
    {
        $query = TpixPayment::with(['user:id,name,email', 'payable'])->latest('id');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($purpose = $request->query('purpose')) {
            $query->where('purpose', $purpose);
        }
        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('tx_hash', 'like', '%' . strtolower($search) . '%')
                    ->orWhere('from_address', 'like', '%' . strtolower($search) . '%')
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%"));
            });
        }

        return view('admin.tpix-payments.index', [
            'payments' => $query->paginate(25)->withQueryString(),
            'settings' => [
                'enabled' => $settings->enabled(),
                'address' => $settings->receiveAddress(),
                'confirmations' => $settings->confirmations(),
                'ttl' => $settings->quoteTtlMinutes(),
            ],
            'spot' => $prices->spot(),
            'lastSnapshot' => TpixPriceSnapshot::latest('created_at')->first(),
            'counts' => TpixPayment::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
        ]);
    }

    public function updateSettings(Request $request, TpixPaySettings $settings): RedirectResponse
    {
        $validated = $request->validate([
            'tpix_enabled' => ['boolean'],
            'tpix_receive_address' => ['nullable', 'required_if:tpix_enabled,1', 'regex:' . TpixPaySettings::ADDRESS_PATTERN],
            'tpix_confirmations' => ['required', 'integer', 'min:1', 'max:64'],
            'tpix_quote_ttl_minutes' => ['required', 'integer', 'min:5', 'max:240'],
        ], [
            'tpix_receive_address.required_if' => 'เปิดรับชำระด้วย TPIX ต้องใส่ที่อยู่กระเป๋ารับเหรียญ',
            'tpix_receive_address.regex' => 'ที่อยู่กระเป๋าต้องเป็น 0x ตามด้วยตัวอักษรฐานสิบหก 40 ตัว',
        ]);

        $settings->save([
            'enabled' => $request->boolean('tpix_enabled'),
            'address' => $validated['tpix_receive_address'] ?? '',
            'confirmations' => (int) $validated['tpix_confirmations'],
            'ttl' => (int) $validated['tpix_quote_ttl_minutes'],
        ]);

        return redirect()->route('admin.tpix-payments.index')->with('success', 'บันทึกการตั้งค่าการชำระด้วย TPIX แล้ว');
    }

    /** Look at the chain again: the quote's own transaction, or search the explorer for its amount. */
    public function recheck(TpixPayment $tpixPayment, TpixPaymentService $service): RedirectResponse
    {
        if ($tpixPayment->tx_hash) {
            $outcome = $service->verify($tpixPayment, $tpixPayment->tx_hash);
            $message = $outcome['message'];
        } else {
            $service->detectFor($tpixPayment);
            $message = 'ตรวจบนเชนแล้ว — สถานะ: ' . $tpixPayment->fresh()->statusLabel();
        }

        return back()->with('success', "#{$tpixPayment->reference}: {$message}");
    }

    /** The transfer came after the quote expired: the admin decides to honour it (deliver, or wallet credit). */
    public function acceptLate(TpixPayment $tpixPayment, TpixPaymentService $service): RedirectResponse
    {
        if ($tpixPayment->status !== TpixPayment::STATUS_LATE || ! $tpixPayment->tx_hash) {
            return back()->with('error', 'รับได้เฉพาะรายการที่โอนหลังหมดเวลา (late) และมีเลขธุรกรรมแล้ว');
        }

        $outcome = $service->verify($tpixPayment, $tpixPayment->tx_hash, acceptLate: true);
        $ok = in_array($outcome['result'], ['paid', 'credited_wallet', 'settled'], true);

        return back()->with($ok ? 'success' : 'error', "#{$tpixPayment->reference}: {$outcome['message']}");
    }

    public function cancel(TpixPayment $tpixPayment, TpixPaymentService $service): RedirectResponse
    {
        return $service->cancel($tpixPayment)
            ? back()->with('success', "ยกเลิกรายการ #{$tpixPayment->reference} แล้ว")
            : back()->with('error', 'ยกเลิกได้เฉพาะรายการที่ยังไม่มีการโอน (รอชำระ/หมดเวลา)');
    }
}
