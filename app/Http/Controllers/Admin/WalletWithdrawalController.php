<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\WithdrawalRefused;
use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use App\Models\WalletWithdrawal;
use App\Services\WalletWithdrawalService;
use App\Support\Satang;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * คิวคำขอถอนเงินของแอดมิน (R2-4)
 *
 * ยอดถูกพักไว้ตั้งแต่ผู้ใช้ขอ — แอดมินโอนเงินจริงนอกระบบ (แอปธนาคาร) แล้วกลับมากดอนุมัติพร้อม
 * เลขอ้างอิง/สลิป หรือปฏิเสธพร้อมเหตุผล (ยอดคืนกระเป๋า) ทั้งสองอย่างถามยืนยันก่อนที่หน้าเว็บ
 * และกันกดซ้ำที่ฝั่งเซิร์ฟเวอร์ (WalletWithdrawalService)
 */
class WalletWithdrawalController extends Controller
{
    private const PER_PAGE = 30;

    /** ตัวกรองสถานะ → คำบนแท็บ */
    public const FILTERS = [
        WalletWithdrawal::STATUS_PENDING => 'รออนุมัติ',
        WalletWithdrawal::STATUS_APPROVED => 'โอนแล้ว',
        WalletWithdrawal::STATUS_REJECTED => 'ไม่อนุมัติ',
        WalletWithdrawal::STATUS_CANCELLED => 'ผู้ใช้ยกเลิก',
        'all' => 'ทั้งหมด',
    ];

    public function __construct(private readonly WalletWithdrawalService $withdrawals) {}

    public function index(Request $request): View
    {
        [$status, $search] = $this->filters($request);

        return view('admin.wallets.withdrawals.index', [
            'withdrawals' => $this->filtered($status, $search)
                ->with(['user:id,name,email', 'reviewer:id,name'])
                // คิวรออนุมัติ: รอนานสุดขึ้นก่อน — ที่อื่นใหม่สุดก่อน
                ->when(
                    $status === WalletWithdrawal::STATUS_PENDING,
                    fn (Builder $q) => $q->orderBy('id'),
                    fn (Builder $q) => $q->orderByDesc('id'),
                )
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
            'status' => $status,
            'search' => $search,
            'counts' => WalletWithdrawal::query()
                ->selectRaw('status, COUNT(*) as n, COALESCE(SUM(amount), 0) as total')
                ->groupBy('status')
                ->get()
                ->keyBy('status'),
        ]);
    }

    public function show(int $id): View
    {
        $withdrawal = WalletWithdrawal::with(['user', 'reviewer', 'holdTransaction', 'returnTransaction'])->findOrFail($id);

        return view('admin.wallets.withdrawals.show', [
            'withdrawal' => $withdrawal,
            // ชื่อที่ยืนยันตัวตนไว้ — ให้เทียบกับชื่อบัญชีปลายทางก่อนโอน
            'kyc' => $withdrawal->user_id ? KycVerification::where('user_id', $withdrawal->user_id)->first() : null,
            'others' => $withdrawal->user_id
                ? WalletWithdrawal::where('user_id', $withdrawal->user_id)->whereKeyNot($withdrawal->id)->latest('id')->limit(10)->get()
                : collect(),
        ]);
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'payout_reference' => ['nullable', 'string', 'max:100', 'required_without:slip'],
            'slip' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:' . WalletWithdrawalService::MAX_SLIP_KB, 'required_without:payout_reference'],
        ], [
            'payout_reference.required_without' => 'กรอกเลขอ้างอิงการโอน หรือแนบสลิปอย่างน้อยหนึ่งอย่าง',
            'slip.required_without' => 'กรอกเลขอ้างอิงการโอน หรือแนบสลิปอย่างน้อยหนึ่งอย่าง',
            'payout_reference.max' => 'เลขอ้างอิงยาวเกิน 100 ตัวอักษร',
            'slip.image' => 'สลิปต้องเป็นไฟล์รูปภาพ',
            'slip.mimes' => 'สลิปต้องเป็น JPG, PNG หรือ WEBP',
            'slip.max' => 'สลิปใหญ่เกิน 5 MB',
        ]);

        $withdrawal = WalletWithdrawal::findOrFail($id);

        try {
            $withdrawal = $this->withdrawals->approve($withdrawal, $request->user(), $validated['payout_reference'] ?? null, $request->file('slip'));
        } catch (WithdrawalRefused $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'บันทึกการโอน ' . $withdrawal->withdrawal_id . ' ' . Satang::format($withdrawal->amountSatang()) . ' แล้ว — ผู้ใช้เห็นว่า "โอนเข้าบัญชีแล้ว"');
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:' . WalletWithdrawalService::MIN_REJECT_REASON, 'max:255'],
        ], [
            'reason.required' => 'กรุณาระบุเหตุผล — ผู้ใช้จะเห็นข้อความนี้',
            'reason.min' => 'เหตุผลสั้นเกินไป (อย่างน้อย ' . WalletWithdrawalService::MIN_REJECT_REASON . ' ตัวอักษร)',
            'reason.max' => 'เหตุผลยาวเกิน 255 ตัวอักษร',
        ]);

        $withdrawal = WalletWithdrawal::findOrFail($id);

        try {
            $withdrawal = $this->withdrawals->reject($withdrawal, $request->user(), $validated['reason']);
        } catch (WithdrawalRefused $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'ไม่อนุมัติ ' . $withdrawal->withdrawal_id . ' — คืน ' . Satang::format($withdrawal->amountSatang()) . ' เข้ากระเป๋าผู้ใช้แล้ว');
    }

    public function slip(int $id): StreamedResponse
    {
        $withdrawal = WalletWithdrawal::findOrFail($id);
        abort_if(! $withdrawal->slip_path || ! Storage::disk('local')->exists($withdrawal->slip_path), 404);

        return Storage::disk('local')->response($withdrawal->slip_path, null, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Type' => 'image/jpeg',
        ]);
    }

    /**
     * ส่งออก CSV ตามตัวกรองเดียวกับหน้ารายการ — ปลายทางปิดเลขกลาง (ไฟล์นี้ถูกส่งต่อได้ง่าย
     * เลขเต็มดูที่หน้ารายละเอียดของคำขอ) และข้อความที่ผู้ใช้กรอกเองถูกกันไม่ให้ Excel ตีความเป็นสูตร
     */
    public function export(Request $request): StreamedResponse
    {
        [$status, $search] = $this->filters($request);
        $query = $this->filtered($status, $search)->with(['user:id,name,email', 'reviewer:id,name'])->orderBy('id');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['เลขที่', 'ขอเมื่อ', 'ผู้ใช้', 'อีเมล', 'ยอด (บาท)', 'สถานะ', 'ช่องทาง', 'ปลายทาง', 'ชื่อบัญชี', 'ที่มาของบัญชี', 'อ้างอิงการโอน', 'เหตุผลที่ไม่อนุมัติ', 'ผู้ดำเนินการ', 'ดำเนินการเมื่อ']);

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $w) {
                    fputcsv($out, array_map([$this, 'csvCell'], [
                        $w->withdrawal_id,
                        $w->created_at?->copy()->timezone('Asia/Bangkok')->format('Y-m-d H:i'),
                        $w->user?->name,
                        $w->user?->email,
                        Satang::toBaht($w->amountSatang()),
                        $w->statusLabel(),
                        $w->methodLabel(),
                        $w->maskedDestination(),
                        $w->account_name,
                        $w->account_source === WalletWithdrawal::SOURCE_KYC ? 'KYC' : 'กรอกเอง',
                        $w->payout_reference,
                        $w->reject_reason,
                        $w->reviewer?->name,
                        ($w->reviewed_at ?? $w->cancelled_at)?->copy()->timezone('Asia/Bangkok')->format('Y-m-d H:i'),
                    ]));
                }
            });

            fclose($out);
        }, 'withdrawals-' . now()->format('Y-m-d-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** ค่าที่ขึ้นต้นด้วย = + - @ (หรือ tab/CR) ถูกนำหน้าด้วย ' — กัน CSV/formula injection */
    public function csvCell(mixed $value): string
    {
        $text = (string) ($value ?? '');

        return $text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'" . $text : $text;
    }

    /** @return array{0:string, 1:string} */
    private function filters(Request $request): array
    {
        $status = $request->query('status');
        $status = is_string($status) && array_key_exists($status, self::FILTERS) ? $status : WalletWithdrawal::STATUS_PENDING;
        $search = $request->query('q');
        $search = is_string($search) ? mb_substr(trim($search), 0, 100) : '';

        return [$status, $search];
    }

    private function filtered(string $status, string $search): Builder
    {
        return WalletWithdrawal::query()
            ->when($status !== 'all', fn (Builder $q) => $q->where('status', $status))
            ->when($search !== '', function (Builder $q) use ($search) {
                $like = '%' . addcslashes($search, '%_\\') . '%';
                $q->where(function (Builder $q) use ($like) {
                    $q->where('withdrawal_id', 'like', $like)
                        ->orWhere('account_name', 'like', $like)
                        ->orWhere('payout_reference', 'like', $like)
                        ->orWhereHas('user', fn (Builder $u) => $u->where('email', 'like', $like)->orWhere('name', 'like', $like));
                });
            });
    }
}
