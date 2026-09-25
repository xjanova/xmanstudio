<?php

namespace App\Services;

use App\Exceptions\WithdrawalRefused;
use App\Models\KycVerification;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WalletWithdrawal;
use App\Support\Alerts\BusinessAlerts;
use App\Support\Satang;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * ถอนเงินสดออกจากกระเป๋า XMAN (R2-4): ผู้ใช้ขอ → พักยอดทันที → แอดมินโอนแล้วอนุมัติ หรือปฏิเสธ
 *
 * เจ้าของระบบตัดสินไว้: "การถอนเงินสดอยู่ที่ xmanstudio คือการกดถอนออกจากวอลเลต แล้วรอแอดมินอนุมัติ"
 *
 * กฎที่บังคับที่นี่ (ไม่ใช่แค่ที่ฟอร์ม):
 *   - ต้องยืนยันตัวตนแล้ว และโอนเข้าบัญชีที่ยืนยันชื่อไว้กับ KYC เท่านั้น (กรอกเองได้เฉพาะเมื่อ
 *     KYC ไม่มีบัญชี) — ขโมย session ได้ก็เปลี่ยนปลายทางเป็นบัญชีตัวเองไม่ได้
 *   - หนึ่งคำขอที่เปิดอยู่ต่อคน (unique ที่ open_user_id), ยอดขั้นต่ำ, ไม่เกินยอดคงเหลือ
 *   - กดส่งซ้ำ/รีเฟรช = คำขอเดิม (unique ที่ user_id + idempotency_key)
 *   - พักยอดด้วยการหัก wallets.balance ทันที (รายการ withdrawal ที่ยัง pending) — เงินที่รอถอน
 *     จึงใช้จ่ายไม่ได้ทั้งในเว็บนี้และที่ aixman และไม่นับเป็นยอดที่ใช้ได้ตอนแอดมินเรียกคืนรายได้
 *   - เปลี่ยนสถานะด้วย UPDATE แบบมีเงื่อนไข (status = pending) ที่ชนะได้คนเดียว: แอดมินสองคนกด
 *     อนุมัติพร้อมกัน หรือผู้ใช้ยกเลิกขณะแอดมินอนุมัติ — มีผลแค่ครั้งแรก ยอดคืนครั้งเดียว
 *
 * ลำดับล็อกเหมือนทุกทางที่แตะเงิน: กระเป๋าก่อน แล้วแถวของรายการ
 */
class WalletWithdrawalService
{
    public const DEFAULT_MIN_BAHT = '100';

    public const HOLD_PREFIX = 'WDH';

    public const RETURN_PREFIX = 'WDR';

    public const MIN_REJECT_REASON = 10;

    /** สลิปใหญ่สุด (KB) — ใหญ่พอสำหรับภาพหน้าจอจากแอปธนาคาร */
    public const MAX_SLIP_KB = 5120;

    /** พิกเซลสูงสุดของสลิป (กว้าง × สูง) — กันไฟล์เล็กที่แตกออกมาเป็นภาพมหึมา */
    public const MAX_SLIP_PIXELS = 40_000_000;

    public function __construct(private readonly WalletLedger $ledger) {}

    /** แอดมินปิดการถอนชั่วคราวได้ในหน้าตั้งค่า Wallet (ค่าเริ่มต้น: เปิด) */
    public function enabled(): bool
    {
        return filter_var(Setting::getValue('wallet_withdraw_enabled', true), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * ยอดถอนขั้นต่ำเป็นสตางค์ — ตั้งในหลังบ้าน (wallet_withdraw_min_amount) ก่อน แล้วจึง
     * WALLET_WITHDRAW_MIN_BAHT ค่าเริ่มต้น ฿100 ค่าที่อ่านไม่ออกถือเป็นค่าเริ่มต้น ไม่ใช่ศูนย์
     */
    public function minSatang(): int
    {
        $setting = Setting::getValue('wallet_withdraw_min_amount', null);
        $baht = $setting !== null && $setting !== '' ? $setting : config('services.wallet.withdraw_min_baht', self::DEFAULT_MIN_BAHT);

        $satang = Satang::parseInput(is_numeric($baht) ? (string) $baht : '');

        return max(1, $satang ?? Satang::parseInput(self::DEFAULT_MIN_BAHT));
    }

    /** บัญชีปลายทางจาก KYC ที่อนุมัติแล้ว (null = KYC ไม่มีบัญชี ต้องกรอกเอง) */
    public function kycAccount(User $user): ?array
    {
        return KycVerification::where('user_id', $user->id)->first()?->payoutAccount();
    }

    public function openFor(int $userId): ?WalletWithdrawal
    {
        return WalletWithdrawal::where('open_user_id', $userId)->first();
    }

    /** ยอดที่ถูกพักกับคำขอถอนที่ยังรอ (สตางค์) */
    public function pendingSatang(int $userId): int
    {
        $sum = WalletWithdrawal::where('user_id', $userId)
            ->where('status', WalletWithdrawal::STATUS_PENDING)
            ->sum('amount');

        return Satang::fromBaht((string) $sum);
    }

    /**
     * ขอถอนเงิน — คืน [คำขอ, สร้างใหม่ตอนนี้หรือไม่] (false = คำขอเดิมของ key เดียวกัน)
     *
     * $formAccount ใช้เฉพาะเมื่อ KYC ไม่มีบัญชี (ผู้เรียกตรวจรูปแบบมาแล้ว) — มีบัญชี KYC เมื่อไรถูกเมิน
     *
     * @param  array{method:string, bank_code?:?string, account_number?:?string, account_name?:?string, promptpay_id?:?string}|null  $formAccount
     * @return array{0: WalletWithdrawal, 1: bool}
     *
     * @throws WithdrawalRefused
     */
    public function request(User $user, int $satang, string $key, ?array $formAccount = null, ?string $ip = null): array
    {
        if ($existing = WalletWithdrawal::where('user_id', $user->id)->where('idempotency_key', $key)->first()) {
            return [$existing, false];
        }

        if (! $this->enabled()) {
            throw new WithdrawalRefused('ระบบถอนเงินปิดปรับปรุงชั่วคราว กรุณาลองใหม่ภายหลัง');
        }
        if (($user->kyc_status ?? null) !== KycVerification::STATUS_APPROVED) {
            throw new WithdrawalRefused('ต้องยืนยันตัวตน (KYC) ให้ผ่านก่อนจึงจะถอนเงินได้');
        }
        if ($satang < $this->minSatang()) {
            throw new WithdrawalRefused('ถอนขั้นต่ำครั้งละ ' . Satang::format($this->minSatang()));
        }

        $account = $this->destination($user, $formAccount);

        $wallet = Wallet::where('user_id', $user->id)->first();
        if ($wallet === null) {
            throw new WithdrawalRefused('ยอดเงินในกระเป๋าไม่พอ');
        }

        try {
            [$withdrawal, $created] = DB::transaction(function () use ($user, $wallet, $satang, $key, $account, $ip) {
                $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->first();

                // ใต้ล็อกกระเป๋าแล้ว: คำขอเดียวกันที่ชนะไปก่อนหน้าเสี้ยววินาทีเห็นได้แน่นอน
                if ($same = WalletWithdrawal::where('user_id', $user->id)->where('idempotency_key', $key)->first()) {
                    return [$same, false];
                }
                if ($wallet === null || ! $wallet->is_active) {
                    throw new WithdrawalRefused('กระเป๋าเงินนี้ถูกระงับการใช้งาน กรุณาติดต่อทีมงาน');
                }
                if ($open = WalletWithdrawal::where('open_user_id', $user->id)->lockForUpdate()->first()) {
                    throw new WithdrawalRefused($this->alreadyOpen($open));
                }

                $available = $this->ledger->balanceSatang($wallet->id);
                if ($available < $satang) {
                    throw new WithdrawalRefused('ยอดเงินที่ถอนได้ไม่พอ — คงเหลือ ' . Satang::format($available));
                }

                $withdrawal = new WalletWithdrawal(array_merge($account, [
                    'withdrawal_id' => 'WD' . now()->format('ymd') . strtoupper(Str::random(8)),
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'amount' => Satang::toBaht($satang),
                    'idempotency_key' => $key,
                    'request_ip' => $ip,
                ]));
                $withdrawal->forceFill(['status' => WalletWithdrawal::STATUS_PENDING, 'open_user_id' => $user->id])->save();

                $hold = $this->ledger->debit($wallet, $satang, [
                    'transaction_id' => self::HOLD_PREFIX . $withdrawal->id,
                    'type' => WalletTransaction::TYPE_WITHDRAWAL,
                    'reference_type' => WalletWithdrawal::REFERENCE_TYPE,
                    'reference_id' => $withdrawal->id,
                    'payment_method' => $withdrawal->method,
                    'description' => 'ถอนเงิน ' . $withdrawal->withdrawal_id . ' → ' . $withdrawal->maskedDestination(),
                    'status' => WalletTransaction::STATUS_PENDING,
                    'metadata' => [
                        'source' => 'withdrawal',
                        'withdrawal_id' => $withdrawal->id,
                        'amount_satang' => $satang,
                    ],
                ]);

                if ($hold === null) {
                    // ยอดถูกใช้ไประหว่างอ่านกับหัก (aixman ตัดเงินจากกระเป๋าใบเดียวกันโดยไม่ผ่านล็อกของเรา)
                    throw new WithdrawalRefused('ยอดเงินที่ถอนได้ไม่พอ — คงเหลือ ' . Satang::format($this->ledger->balanceSatang($wallet->id)));
                }

                $withdrawal->forceFill(['hold_transaction_id' => $hold->id])->save();

                return [$withdrawal, true];
            }, GpuxMineEarningSettlementService::DEADLOCK_ATTEMPTS);
        } catch (UniqueConstraintViolationException) {
            // สองคำขอหลุดมาพร้อมกัน (หรือ SQLite ที่ไม่มี row lock): key เดียวกัน = คำขอเดิม
            if ($same = WalletWithdrawal::where('user_id', $user->id)->where('idempotency_key', $key)->first()) {
                return [$same, false];
            }
            $open = $this->openFor($user->id);

            throw new WithdrawalRefused($open ? $this->alreadyOpen($open) : 'ส่งคำขอไม่สำเร็จ กรุณาลองใหม่');
        }

        if ($created) {
            Log::info('[wallet] withdrawal requested', [
                'withdrawal_id' => $withdrawal->id,
                'user_id' => $user->id,
                'satang' => $satang,
                'source' => $withdrawal->account_source,
            ]);
            BusinessAlerts::withdrawalRequested($withdrawal);
        }

        return [$withdrawal, $created];
    }

    /**
     * ผู้ใช้ยกเลิกคำขอของตัวเองระหว่างรอ — ยอดที่พักคืนเข้ากระเป๋าทันที
     *
     * @throws WithdrawalRefused
     */
    public function cancel(User $user, WalletWithdrawal $withdrawal): WalletWithdrawal
    {
        if ((int) $withdrawal->user_id !== (int) $user->id) {
            throw new WithdrawalRefused('ไม่พบคำขอนี้');
        }

        return $this->close($withdrawal, WalletWithdrawal::STATUS_CANCELLED, [
            'cancelled_at' => now(),
        ], WalletTransaction::STATUS_CANCELLED, 'คืนยอดที่พักไว้ — ยกเลิกคำขอถอน ' . $withdrawal->withdrawal_id);
    }

    /**
     * แอดมินปฏิเสธ — ต้องมีเหตุผล (ผู้ใช้เห็น) ยอดที่พักคืนเข้ากระเป๋า
     *
     * @throws WithdrawalRefused
     */
    public function reject(WalletWithdrawal $withdrawal, User $admin, string $reason): WalletWithdrawal
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < self::MIN_REJECT_REASON || mb_strlen($reason) > 255) {
            throw new WithdrawalRefused('กรุณาระบุเหตุผล ' . self::MIN_REJECT_REASON . '–255 ตัวอักษร — ผู้ใช้จะเห็นข้อความนี้');
        }

        return $this->close($withdrawal, WalletWithdrawal::STATUS_REJECTED, [
            'reject_reason' => $reason,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ], WalletTransaction::STATUS_FAILED, 'คืนยอดที่พักไว้ — ไม่อนุมัติคำขอถอน ' . $withdrawal->withdrawal_id . ': ' . $reason);
    }

    /**
     * แอดมินโอนเงินให้แล้ว — บันทึกเลขอ้างอิงการโอน และ/หรือสลิป (เก็บบนดิสก์ส่วนตัว)
     *
     * @throws WithdrawalRefused
     */
    public function approve(WalletWithdrawal $withdrawal, User $admin, ?string $reference, ?UploadedFile $slip): WalletWithdrawal
    {
        $reference = $reference !== null ? trim($reference) : null;
        $reference = $reference === '' ? null : $reference;

        if ($reference === null && $slip === null) {
            throw new WithdrawalRefused('กรุณากรอกเลขอ้างอิงการโอน หรือแนบสลิปอย่างน้อยหนึ่งอย่าง');
        }
        if ($withdrawal->status !== WalletWithdrawal::STATUS_PENDING) {
            throw new WithdrawalRefused($this->alreadyDecided($withdrawal));
        }

        $slipPath = $slip ? $this->storeSlip($slip, (int) ($withdrawal->user_id ?? 0)) : null;

        $won = DB::transaction(function () use ($withdrawal, $admin, $reference, $slipPath) {
            $claimed = WalletWithdrawal::whereKey($withdrawal->id)
                ->where('status', WalletWithdrawal::STATUS_PENDING)
                ->update([
                    'status' => WalletWithdrawal::STATUS_APPROVED,
                    'open_user_id' => null,
                    'payout_reference' => $reference,
                    'slip_path' => $slipPath,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                ]);

            if ($claimed !== 1) {
                return false;
            }

            if ($withdrawal->hold_transaction_id) {
                WalletTransaction::whereKey($withdrawal->hold_transaction_id)
                    ->where('status', WalletTransaction::STATUS_PENDING)
                    ->update([
                        'status' => WalletTransaction::STATUS_COMPLETED,
                        'payment_reference' => $reference !== null ? mb_substr($reference, 0, 100) : null,
                        'admin_note' => 'โอนแล้วโดยผู้ดูแล',
                    ]);
            }

            return true;
        });

        if (! $won) {
            if ($slipPath !== null) {
                Storage::disk('local')->delete($slipPath);
            }

            throw new WithdrawalRefused($this->alreadyDecided($withdrawal->fresh() ?? $withdrawal));
        }

        $withdrawal->refresh();
        Log::info('[wallet] withdrawal approved', ['withdrawal_id' => $withdrawal->id, 'by' => $admin->id]);
        BusinessAlerts::withdrawalUpdated($withdrawal);

        return $withdrawal;
    }

    /**
     * ปิดคำขอแบบคืนยอด (ยกเลิก/ปฏิเสธ) — claim ได้ครั้งเดียว แล้วคืนยอดเข้ากระเป๋าในทรานแซกชันเดียวกัน
     *
     * @param  array<string, mixed>  $columns
     *
     * @throws WithdrawalRefused
     */
    private function close(WalletWithdrawal $withdrawal, string $to, array $columns, string $holdStatus, string $description): WalletWithdrawal
    {
        if ($withdrawal->status !== WalletWithdrawal::STATUS_PENDING) {
            throw new WithdrawalRefused($this->alreadyDecided($withdrawal));
        }

        $won = DB::transaction(function () use ($withdrawal, $to, $columns, $holdStatus, $description) {
            $wallet = $withdrawal->wallet_id ? Wallet::whereKey($withdrawal->wallet_id)->lockForUpdate()->first() : null;

            $claimed = WalletWithdrawal::whereKey($withdrawal->id)
                ->where('status', WalletWithdrawal::STATUS_PENDING)
                ->update(array_merge($columns, ['status' => $to, 'open_user_id' => null]));

            if ($claimed !== 1) {
                return false;
            }

            $satang = $withdrawal->amountSatang();

            if ($wallet !== null && $satang > 0) {
                // คืนแม้กระเป๋าถูกปิด — เป็นเงินของเขาที่เราพักไว้เอง ไม่ใช่เงินใหม่
                $returned = $this->ledger->credit($wallet, $satang, [
                    'transaction_id' => self::RETURN_PREFIX . $withdrawal->id,
                    'type' => WalletTransaction::TYPE_REFUND,
                    'reference_type' => WalletWithdrawal::REFERENCE_TYPE,
                    'reference_id' => $withdrawal->id,
                    'description' => $description,
                    'status' => WalletTransaction::STATUS_COMPLETED,
                    'metadata' => [
                        'source' => WalletTransaction::SOURCE_WITHDRAWAL_RETURN,
                        'withdrawal_id' => $withdrawal->id,
                        'amount_satang' => $satang,
                    ],
                ], onlyActive: false);

                WalletWithdrawal::whereKey($withdrawal->id)->update(['return_transaction_id' => $returned->id]);
            } elseif ($satang > 0) {
                // บัญชี (และกระเป๋า) ถูกลบไปแล้วระหว่างรอ — ไม่มีที่ให้คืน บันทึกไว้ให้คนตามได้
                Log::warning('[wallet] withdrawal closed without a wallet to return the hold to', [
                    'withdrawal_id' => $withdrawal->id,
                    'satang' => $satang,
                    'to' => $to,
                ]);
            }

            if ($withdrawal->hold_transaction_id) {
                WalletTransaction::whereKey($withdrawal->hold_transaction_id)
                    ->where('status', WalletTransaction::STATUS_PENDING)
                    ->update(['status' => $holdStatus]);
            }

            return true;
        }, GpuxMineEarningSettlementService::DEADLOCK_ATTEMPTS);

        if (! $won) {
            throw new WithdrawalRefused($this->alreadyDecided($withdrawal->fresh() ?? $withdrawal));
        }

        $withdrawal->refresh();
        Log::info('[wallet] withdrawal closed', ['withdrawal_id' => $withdrawal->id, 'status' => $to]);
        BusinessAlerts::withdrawalUpdated($withdrawal);

        return $withdrawal;
    }

    /**
     * บัญชีปลายทาง: KYC ที่อนุมัติแล้วก่อนเสมอ — ฟอร์มถูกใช้เฉพาะเมื่อ KYC ไม่มีบัญชี
     *
     * @return array<string, ?string>
     *
     * @throws WithdrawalRefused
     */
    private function destination(User $user, ?array $formAccount): array
    {
        if ($kyc = $this->kycAccount($user)) {
            return [
                'method' => WalletWithdrawal::METHOD_BANK,
                'account_source' => WalletWithdrawal::SOURCE_KYC,
                'bank_code' => $kyc['bank_code'],
                'bank_name' => $kyc['bank_name'],
                'account_number' => $kyc['account_number'],
                'account_name' => $kyc['account_name'],
                'promptpay_id' => null,
            ];
        }

        if ($formAccount === null) {
            throw new WithdrawalRefused('กรุณากรอกบัญชีรับเงิน — ข้อมูลยืนยันตัวตนของคุณยังไม่มีบัญชีธนาคาร');
        }

        if (($formAccount['method'] ?? null) === WalletWithdrawal::METHOD_PROMPTPAY) {
            return [
                'method' => WalletWithdrawal::METHOD_PROMPTPAY,
                'account_source' => WalletWithdrawal::SOURCE_FORM,
                'bank_code' => null,
                'bank_name' => null,
                'account_number' => null,
                'account_name' => $formAccount['account_name'] ?? null,
                'promptpay_id' => $formAccount['promptpay_id'] ?? null,
            ];
        }

        $code = (string) ($formAccount['bank_code'] ?? '');

        return [
            'method' => WalletWithdrawal::METHOD_BANK,
            'account_source' => WalletWithdrawal::SOURCE_FORM,
            'bank_code' => $code,
            'bank_name' => KycVerification::BANKS[$code] ?? $code,
            'account_number' => $formAccount['account_number'] ?? null,
            'account_name' => $formAccount['account_name'] ?? null,
            'promptpay_id' => null,
        ];
    }

    /**
     * สลิปการโอน → เข้ารหัสใหม่ด้วย GD → ดิสก์ส่วนตัว (แบบเดียวกับเอกสาร KYC)
     *
     * เข้ารหัสใหม่เพื่อตัด EXIF และทำลายไฟล์ลูกผสม — สิ่งที่ลงดิสก์คือพิกเซลที่ GD วาดใหม่
     *
     * @throws WithdrawalRefused
     */
    private function storeSlip(UploadedFile $file, int $userId): string
    {
        $raw = @file_get_contents($file->getRealPath());
        $size = $raw === false ? false : @getimagesizefromstring($raw);

        // ถามขนาดก่อนให้ GD แตกไฟล์: PNG 5 MB ที่ประกาศตัวเอง 20000×20000 พิกเซล ใช้หน่วยความจำ
        // เป็นกิกะไบต์ตอนถอดรหัส — ภาพหน้าจอสลิปจริงไม่เคยใหญ่ใกล้เพดานนี้
        if ($size === false || $size[0] < 1 || $size[1] < 1 || $size[0] * $size[1] > self::MAX_SLIP_PIXELS) {
            throw new WithdrawalRefused('ไฟล์สลิปไม่ใช่รูปภาพที่อ่านได้ หรือใหญ่เกินไป');
        }

        $image = @imagecreatefromstring($raw);
        if ($image === false) {
            throw new WithdrawalRefused('ไฟล์สลิปไม่ใช่รูปภาพที่อ่านได้');
        }

        try {
            ob_start();
            imagejpeg($image, null, 88);
            $encoded = ob_get_clean();
        } finally {
            imagedestroy($image);
        }

        if ($encoded === false || $encoded === '') {
            throw new RuntimeException('Re-encoding a withdrawal slip produced nothing');
        }

        $path = sprintf('withdrawals/%d/%s.jpg', $userId, Str::uuid());
        Storage::disk('local')->put($path, $encoded);

        return $path;
    }

    private function alreadyOpen(WalletWithdrawal $open): string
    {
        return 'มีคำขอถอน ' . $open->withdrawal_id . ' (' . Satang::format($open->amountSatang()) . ') รอแอดมินอยู่แล้ว — ยกเลิกคำขอเดิมก่อน หรือรอให้ดำเนินการเสร็จ';
    }

    private function alreadyDecided(WalletWithdrawal $withdrawal): string
    {
        return 'คำขอ ' . $withdrawal->withdrawal_id . ' ' . $withdrawal->statusLabel() . ' ไปแล้ว — โหลดหน้าใหม่แล้วดูอีกครั้ง';
    }
}
