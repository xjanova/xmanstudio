<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TpixPayment;
use App\Services\TpixPay\TpixReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin: "รายงาน TPIX" — totals, charts and the top payers of the TPIX payments, and a CSV of every
 * quote in a date range. Read-only: the decisions stay on the TPIX payments page.
 */
class TpixReportController extends Controller
{
    /** Longest range one CSV may cover, in days. */
    public const MAX_EXPORT_DAYS = 366;

    public function index(TpixReportService $reports): View
    {
        $today = CarbonImmutable::now(TpixReportService::TIMEZONE);

        return view('admin.tpix-payments.reports', [
            'report' => $reports->report(),
            'exportFrom' => $today->subDays(29)->format('Y-m-d'),
            'exportTo' => $today->format('Y-m-d'),
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ], [
            'from.required' => 'กรุณาเลือกวันที่เริ่มต้น',
            'to.required' => 'กรุณาเลือกวันที่สิ้นสุด',
            'from.date_format' => 'วันที่เริ่มต้นไม่ถูกต้อง',
            'to.date_format' => 'วันที่สิ้นสุดไม่ถูกต้อง',
            'to.after_or_equal' => 'วันที่สิ้นสุดต้องไม่ก่อนวันที่เริ่มต้น',
        ]);

        $tz = TpixReportService::TIMEZONE;
        $from = CarbonImmutable::createFromFormat('Y-m-d', $validated['from'], $tz)->startOfDay();
        $to = CarbonImmutable::createFromFormat('Y-m-d', $validated['to'], $tz)->endOfDay();

        if ($from->diffInDays($to) > self::MAX_EXPORT_DAYS) {
            throw ValidationException::withMessages(['to' => 'ส่งออกได้ครั้งละไม่เกิน ' . self::MAX_EXPORT_DAYS . ' วัน']);
        }

        $filename = 'tpix-payments_' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv';
        $fromUtc = $from->utc();
        $toUtc = $to->utc();

        return response()->streamDownload(function () use ($fromUtc, $toUtc, $tz) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM: Excel opens the Thai text as Thai

            fputcsv($out, [
                'เลขอ้างอิง', 'วันที่สร้าง', 'วันที่ชำระ', 'รหัสผู้ใช้', 'ชื่อผู้ใช้', 'อีเมล', 'ประเภท', 'สถานะ',
                'ยอด (บาท)', 'จำนวน TPIX', 'อัตรา (บาท/TPIX)', 'ราคา (USD/TPIX)', 'USD/THB', 'เข้ากระเป๋าเงิน (บาท)',
                'กระเป๋าผู้โอน', 'เลขธุรกรรม', 'บล็อก', 'หมายเหตุ',
            ]);

            TpixPayment::query()
                ->with('user:id,name,email')
                ->whereBetween('created_at', [$fromUtc, $toUtc])
                ->orderBy('id')
                ->chunkById(500, function ($payments) use ($out, $tz) {
                    foreach ($payments as $p) {
                        fputcsv($out, array_map([self::class, 'cell'], [
                            $p->reference,
                            $p->created_at?->setTimezone($tz)->format('Y-m-d H:i:s'),
                            $p->paid_at?->setTimezone($tz)->format('Y-m-d H:i:s'),
                            $p->user_id,
                            $p->user?->name,
                            $p->user?->email,
                            match ($p->purpose) {
                                TpixPayment::PURPOSE_ORDER => 'คำสั่งซื้อ',
                                TpixPayment::PURPOSE_WALLET_TOPUP => 'เติมเงิน Wallet',
                                default => $p->purpose,
                            },
                            $p->statusLabel(),
                            number_format((float) $p->amount_thb, 2, '.', ''),
                            $p->tpixDisplay(),
                            (string) $p->rate_thb,
                            (string) $p->price_usd,
                            (string) $p->usd_thb,
                            $p->credited_thb !== null ? number_format((float) $p->credited_thb, 2, '.', '') : '',
                            $p->from_address,
                            $p->tx_hash,
                            $p->block_number,
                            $p->failure_reason,
                        ]));
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * One CSV cell, safe to open in a spreadsheet: text a user typed (a name, a note) that starts with
     * = + - @ or a control character would run as a formula, so it gets a leading apostrophe.
     * Plain numbers (incl. negative ones) are left alone.
     */
    public static function cell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        if ($value !== '' && ! is_numeric($value) && preg_match('/^[=+\-@\t\r]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }
}
