<?php

namespace App\Http\Controllers\User;

use App\Exceptions\WithdrawalRefused;
use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use App\Models\Wallet;
use App\Models\WalletWithdrawal;
use App\Services\WalletWithdrawalService;
use App\Support\Satang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ถอนเงินสดออกจากกระเป๋า XMAN ฝั่งผู้ใช้ (R2-4)
 *
 * หน้าถอนเปิดได้ทุกคนที่ล็อกอิน — คนที่ยังไม่ยืนยันตัวตนเห็นว่าต้องทำอะไรก่อน และคนที่มีคำขอค้าง
 * อยู่ยังยกเลิกได้เสมอแม้ KYC จะถูกเปลี่ยนสถานะทีหลัง ส่วนการส่งคำขอผ่าน kyc.verified ที่ route
 * กฎเงินทั้งหมดอยู่ใน WalletWithdrawalService — ที่นี่ตรวจรูปแบบที่กรอกแล้วบอกผลเป็นภาษาไทย
 */
class WalletWithdrawalController extends Controller
{
    public function __construct(private readonly WalletWithdrawalService $withdrawals) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $wallet = Wallet::getOrCreateForUser($user->id);

        return view('user.wallet.withdraw', [
            'wallet' => $wallet,
            'availableSatang' => Satang::fromBaht((string) $wallet->balance),
            'open' => $this->withdrawals->openFor($user->id),
            'history' => WalletWithdrawal::where('user_id', $user->id)
                ->latest('id')
                ->paginate(10)
                ->withQueryString(),
            'kycStatus' => $user->kyc_status ?? KycVerification::USER_STATUS_NOT_SUBMITTED,
            'kycAccount' => $this->withdrawals->kycAccount($user),
            'minSatang' => $this->withdrawals->minSatang(),
            'enabled' => $this->withdrawals->enabled(),
            'banks' => KycVerification::BANKS,
            // หนึ่งฟอร์มหนึ่งกุญแจ — กดส่งซ้ำหรือรีเฟรชหลังส่ง ได้คำขอเดิม ไม่ใช่คำขอที่สอง
            'requestKey' => old('request_key') ?: (string) Str::uuid(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $needsAccount = $this->withdrawals->kycAccount($user) === null;

        $rules = [
            'amount' => ['required', 'string', 'max:20'],
            'request_key' => ['required', 'string', 'regex:/^[A-Za-z0-9-]{16,64}$/'],
        ];
        if ($needsAccount) {
            $rules += [
                'method' => ['required', Rule::in([WalletWithdrawal::METHOD_BANK, WalletWithdrawal::METHOD_PROMPTPAY])],
                'bank_code' => ['required_if:method,bank', 'nullable', Rule::in(array_keys(KycVerification::BANKS))],
                'account_number' => ['required_if:method,bank', 'nullable', 'string', 'max:30'],
                'account_name' => ['required', 'string', 'min:3', 'max:150'],
                'promptpay_id' => ['required_if:method,promptpay', 'nullable', 'string', 'max:20'],
            ];
        }

        $validated = $request->validate($rules, [
            'amount.required' => 'กรุณากรอกยอดที่ต้องการถอน',
            'request_key.*' => 'ฟอร์มหมดอายุ กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง',
            'method.*' => 'กรุณาเลือกช่องทางรับเงิน',
            'bank_code.required_if' => 'กรุณาเลือกธนาคาร',
            'bank_code.in' => 'ธนาคารที่เลือกไม่ถูกต้อง',
            'account_number.required_if' => 'กรุณากรอกเลขบัญชี',
            'account_name.required' => 'กรุณากรอกชื่อบัญชี (ต้องตรงกับชื่อที่ยืนยันตัวตน)',
            'account_name.min' => 'ชื่อบัญชีสั้นเกินไป',
            'account_name.max' => 'ชื่อบัญชียาวเกินไป',
            'promptpay_id.required_if' => 'กรุณากรอกเบอร์หรือเลขบัตรที่ผูกพร้อมเพย์',
        ]);

        $satang = Satang::parseInput($validated['amount']);
        if ($satang === null || $satang <= 0) {
            throw ValidationException::withMessages(['amount' => 'กรอกยอดเงินเป็นตัวเลข เช่น 500 หรือ 500.50']);
        }

        $account = $needsAccount ? $this->formAccount($validated) : null;

        try {
            [$withdrawal, $created] = $this->withdrawals->request($user, $satang, $validated['request_key'], $account, $request->ip());
        } catch (WithdrawalRefused $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('user.wallet.withdraw')->with('success', $created
            ? 'ส่งคำขอถอน ' . $withdrawal->withdrawal_id . ' ' . Satang::format($withdrawal->amountSatang())
                . ' แล้ว — ยอดนี้ถูกพักไว้ในกระเป๋า แอดมินจะโอนเข้า ' . $withdrawal->maskedDestination() . ' แล้วแจ้งผลที่หน้านี้'
            : 'คำขอนี้ส่งไปแล้ว (' . $withdrawal->withdrawal_id . ') — ไม่ได้สร้างคำขอซ้ำ');
    }

    public function cancel(Request $request, int $id): RedirectResponse
    {
        $withdrawal = $this->ownRequest($request, $id);

        try {
            $withdrawal = $this->withdrawals->cancel($request->user(), $withdrawal);
        } catch (WithdrawalRefused $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('user.wallet.withdraw')->with('success', 'ยกเลิกคำขอ ' . $withdrawal->withdrawal_id
            . ' แล้ว — ' . Satang::format($withdrawal->amountSatang()) . ' คืนเข้ากระเป๋าเรียบร้อย');
    }

    /** สลิปการโอนที่แอดมินแนบ — เจ้าของคำขอดูได้ ไฟล์อยู่บนดิสก์ส่วนตัว */
    public function slip(Request $request, int $id): StreamedResponse
    {
        $withdrawal = $this->ownRequest($request, $id);
        abort_if(! $withdrawal->slip_path || ! Storage::disk('local')->exists($withdrawal->slip_path), 404);

        return Storage::disk('local')->response($withdrawal->slip_path, null, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Type' => 'image/jpeg',
        ]);
    }

    /** คำขอของคนอื่นเป็น 404 ไม่ใช่ 403 — 403 ยืนยันให้คนเดา id ว่ามีคำขอนั้นอยู่จริง */
    private function ownRequest(Request $request, int $id): WalletWithdrawal
    {
        return WalletWithdrawal::whereKey($id)->where('user_id', $request->user()->id)->firstOrFail();
    }

    /**
     * บัญชีที่กรอกเอง (เฉพาะเมื่อ KYC ไม่มีบัญชี) → รูปแบบที่ตรวจแล้ว
     *
     * @param  array<string, mixed>  $validated
     * @return array{method:string, bank_code:?string, account_number:?string, account_name:string, promptpay_id:?string}
     */
    private function formAccount(array $validated): array
    {
        $name = trim(preg_replace('/\s+/u', ' ', (string) $validated['account_name']) ?? '');

        if ($validated['method'] === WalletWithdrawal::METHOD_PROMPTPAY) {
            $id = preg_replace('/\D/', '', (string) ($validated['promptpay_id'] ?? '')) ?? '';
            // เบอร์มือถือ 10 หลักขึ้นต้น 0 หรือเลขบัตรประชาชน 13 หลัก
            if (! preg_match('/^(0\d{9}|\d{13})$/', $id)) {
                throw ValidationException::withMessages(['promptpay_id' => 'พร้อมเพย์ต้องเป็นเบอร์มือถือ 10 หลัก หรือเลขบัตรประชาชน 13 หลัก']);
            }

            return ['method' => WalletWithdrawal::METHOD_PROMPTPAY, 'bank_code' => null, 'account_number' => null, 'account_name' => $name, 'promptpay_id' => $id];
        }

        $number = preg_replace('/\D/', '', (string) ($validated['account_number'] ?? '')) ?? '';
        if (! preg_match('/^\d{10,15}$/', $number)) {
            throw ValidationException::withMessages(['account_number' => 'เลขบัญชีต้องเป็นตัวเลข 10–15 หลัก']);
        }

        return ['method' => WalletWithdrawal::METHOD_BANK, 'bank_code' => (string) $validated['bank_code'], 'account_number' => $number, 'account_name' => $name, 'promptpay_id' => null];
    }
}
