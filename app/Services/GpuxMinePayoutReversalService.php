<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\GpuJobEarning;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\Satang;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * แอดมินเรียกคืนเงิน GPUxMINE ที่จ่ายเข้ากระเป๋าไปแล้ว (R2-3) — รายได้ของเครื่องหนึ่งงาน
 * หรือค่าแนะนำหนึ่งรายการ ด้วยกลไกเดียวกัน
 *
 * เจ้าของตัดสินไว้ว่า "จ่ายอัตโนมัติเลย แต่โรลแบ็คคืนได้ ถ้ายังไม่ได้ถอนเป็นเงินสด":
 *
 *   - หักกระเป๋าใบเดิมเท่ากับยอดเดิม ต่อเมื่อยอดที่ใช้ได้ตอนนี้ยังพอ — เงินที่พักไว้กับคำขอถอน
 *     ถูกหักออกจาก balance ไปแล้วตั้งแต่ตอนขอ จึงไม่นับเป็นยอดที่ใช้ได้อยู่แล้ว ยอดไม่พอคือ
 *     เงินออกไปแล้ว (ถอนเป็นเงินสด ใช้จ่าย หรือรอถอนอยู่) → ปฏิเสธ ไม่หักจนติดลบ
 *   - เป็นรายการในกระเป๋าหนึ่งแถว ประเภท adjustment (ค่าที่มีอยู่แล้วใน enum — ไม่มีใครต้องรู้จัก
 *     ประเภทใหม่) metadata.source = reversal ผูกกลับมาที่รายการเดิม พร้อมแอดมินและเหตุผล
 *   - ทำได้ครั้งเดียว: ตรวจสถานะใต้ row lock และเลขรายการ GXV{id งาน} / GXAV{id ค่าแนะนำ}
 *     เป็น unique — กดซ้ำหรือสองแท็บพร้อมกันไม่หักสองรอบ
 *   - งานที่ถูกเรียกคืนเป็น void + reversed_at (โปรแกรมของเครื่องเห็นเป็น void พร้อมเหตุผล)
 *     ค่าแนะนำเป็น 'reversed'
 *
 * ลำดับล็อกเหมือนทุกทางที่แตะเงิน: กระเป๋าก่อน แล้วแถวของรายการ
 */
class GpuxMinePayoutReversalService
{
    /** เหตุผลสั้นสุด — เจ้าของเงินได้อ่าน และแอดมินคนถัดไปต้องเข้าใจว่าทำไม */
    public const MIN_REASON = 10;

    public const MAX_REASON = 255;

    public const EARNING_PREFIX = 'GXV';

    public const COMMISSION_PREFIX = 'GXAV';

    public function __construct(private readonly WalletLedger $ledger) {}

    /**
     * @return array{ok:bool, message:string, satang:int, transaction_id:?int}
     */
    public function reverseEarning(int $earningId, User $admin, string $reason): array
    {
        $reason = trim($reason);
        if ($refused = $this->badReason($reason)) {
            return $refused;
        }

        $earning = GpuJobEarning::find($earningId);
        if ($earning === null) {
            return $this->refuse('ไม่พบรายการนี้');
        }
        if ($refused = $this->earningNotReversible($earning)) {
            return $refused;
        }
        if ($earning->user_id === null) {
            return $this->refuse('บัญชีเจ้าของเครื่องถูกลบไปแล้ว — ไม่มีกระเป๋าให้เรียกคืน');
        }

        try {
            return DB::transaction(function () use ($earningId, $admin, $reason) {
                $row = GpuJobEarning::whereKey($earningId)->first();
                $wallet = Wallet::where('user_id', $row->user_id)->lockForUpdate()->first();
                $row = GpuJobEarning::whereKey($earningId)->lockForUpdate()->first();

                if ($refused = $this->earningNotReversible($row)) {
                    return $refused;
                }

                $satang = max(0, (int) $row->amount_satang);
                $transaction = null;

                if ($satang > 0) {
                    if ($wallet === null) {
                        return $this->refuse('ไม่พบกระเป๋าเงินของเจ้าของเครื่อง');
                    }
                    if ($short = $this->shortOf($wallet, $satang)) {
                        return $short;
                    }

                    $transaction = $this->ledger->debit($wallet, $satang, [
                        'transaction_id' => self::EARNING_PREFIX . $row->id,
                        'type' => WalletTransaction::TYPE_ADJUSTMENT,
                        'reference_type' => GpuxMineEarningSettlementService::REFERENCE_TYPE,
                        'reference_id' => $row->id,
                        'description' => 'เรียกคืนรายได้ GPUxMINE ' . $row->job_id . ' · ' . $reason,
                        'admin_note' => $reason,
                        'created_by' => $admin->id,
                        'status' => WalletTransaction::STATUS_COMPLETED,
                        'metadata' => [
                            'source' => WalletTransaction::SOURCE_REVERSAL,
                            'reverses' => 'gpu_job_earning',
                            'earning_id' => $row->id,
                            'job_id' => $row->job_id,
                            'reverses_wallet_transaction_id' => $row->wallet_transaction_id,
                            'amount_satang' => $satang,
                            'reason' => $reason,
                            'admin_id' => $admin->id,
                        ],
                    ]);

                    if ($transaction === null) {
                        return $this->withdrawnAlready($satang, $this->ledger->balanceSatang($wallet->id));
                    }
                }

                $updated = GpuJobEarning::whereKey($row->id)
                    ->where('status', GpuJobEarning::STATUS_PAID)
                    ->whereNull('reversed_at')
                    ->update([
                        'status' => GpuJobEarning::STATUS_VOID,
                        'void_reason' => $reason,
                        'reversed_at' => now(),
                        'reversed_by' => $admin->id,
                        'reversal_wallet_transaction_id' => $transaction?->id,
                        'reviewed_by' => $admin->id,
                        'reviewed_at' => now(),
                    ]);

                if ($updated !== 1) {
                    // แถวถูกล็อกอยู่ — ถ้ามาถึงตรงนี้คือมีอะไรผิดปกติ ย้อนทั้งหมดรวมเงินที่เพิ่งหัก
                    throw new RuntimeException("GPUxMINE reversal: earning {$row->id} changed under its lock");
                }

                Log::warning('[GPUxMINE] paid earning reversed by admin', [
                    'earning_id' => $row->id,
                    'satang' => $satang,
                    'wallet_transaction_id' => $transaction?->id,
                    'by' => $admin->id,
                ]);

                return [
                    'ok' => true,
                    'message' => 'เรียกคืนรายได้ ' . $row->job_id . ' ' . Satang::format($satang) . ' จากกระเป๋าเจ้าของเครื่องแล้ว',
                    'satang' => $satang,
                    'transaction_id' => $transaction?->id,
                ];
            }, GpuxMineEarningSettlementService::DEADLOCK_ATTEMPTS);
        } catch (UniqueConstraintViolationException) {
            return $this->refuse('รายการนี้ถูกเรียกคืนไปแล้ว');
        }
    }

    /**
     * @return array{ok:bool, message:string, satang:int, transaction_id:?int}
     */
    public function reverseCommission(int $commissionId, User $admin, string $reason): array
    {
        $reason = trim($reason);
        if ($refused = $this->badReason($reason)) {
            return $refused;
        }

        $commission = AffiliateCommission::find($commissionId);
        if ($commission === null) {
            return $this->refuse('ไม่พบค่าแนะนำรายการนี้');
        }
        if ($refused = $this->commissionNotReversible($commission)) {
            return $refused;
        }

        $affiliate = Affiliate::find($commission->affiliate_id);
        if ($affiliate === null) {
            return $this->refuse('ไม่พบบัญชี affiliate ของผู้แนะนำแล้ว — ไม่มีกระเป๋าให้เรียกคืน');
        }

        try {
            return DB::transaction(function () use ($commissionId, $affiliate, $admin, $reason) {
                $wallet = Wallet::where('user_id', $affiliate->user_id)->lockForUpdate()->first();
                $commission = AffiliateCommission::whereKey($commissionId)->lockForUpdate()->first();

                if ($refused = $this->commissionNotReversible($commission)) {
                    return $refused;
                }

                $satang = Satang::fromBaht((string) $commission->commission_amount);
                $transaction = null;

                if ($satang > 0) {
                    if ($wallet === null) {
                        return $this->refuse('ไม่พบกระเป๋าเงินของผู้แนะนำ');
                    }
                    if ($short = $this->shortOf($wallet, $satang)) {
                        return $short;
                    }

                    $transaction = $this->ledger->debit($wallet, $satang, [
                        'transaction_id' => self::COMMISSION_PREFIX . $commission->id,
                        'type' => WalletTransaction::TYPE_ADJUSTMENT,
                        'reference_type' => 'affiliate_commissions',
                        'reference_id' => $commission->id,
                        'description' => 'เรียกคืนค่าแนะนำ GPUxMINE #' . $commission->id . ' · ' . $reason,
                        'admin_note' => $reason,
                        'created_by' => $admin->id,
                        'status' => WalletTransaction::STATUS_COMPLETED,
                        'metadata' => [
                            'source' => WalletTransaction::SOURCE_REVERSAL,
                            'reverses' => 'affiliate_commission',
                            'affiliate_commission_id' => $commission->id,
                            'reverses_wallet_transaction_id' => $commission->wallet_transaction_id,
                            'amount_satang' => $satang,
                            'reason' => $reason,
                            'admin_id' => $admin->id,
                        ],
                    ]);

                    if ($transaction === null) {
                        return $this->withdrawnAlready($satang, $this->ledger->balanceSatang($wallet->id));
                    }
                }

                $updated = AffiliateCommission::whereKey($commission->id)
                    ->where('status', 'paid')
                    ->update([
                        'status' => AffiliateCommission::STATUS_REVERSED,
                        'reversed_at' => now(),
                        'reversed_by' => $admin->id,
                        'reversal_reason' => $reason,
                        'reversal_wallet_transaction_id' => $transaction?->id,
                    ]);

                if ($updated !== 1) {
                    throw new RuntimeException("GPUxMINE reversal: commission {$commission->id} changed under its lock");
                }

                // ยอดสะสมของ affiliate ถอยตามที่เคยขยับตอนจ่าย (total_earned ตอนสร้าง total_paid ตอนจ่าย)
                $amount = Satang::toBaht($satang);
                Affiliate::whereKey($affiliate->id)->decrement('total_earned', $amount);
                Affiliate::whereKey($affiliate->id)->decrement('total_paid', $amount);

                Log::warning('[GPUxMINE] paid referral commission reversed by admin', [
                    'affiliate_commission_id' => $commission->id,
                    'satang' => $satang,
                    'wallet_transaction_id' => $transaction?->id,
                    'by' => $admin->id,
                ]);

                return [
                    'ok' => true,
                    'message' => 'เรียกคืนค่าแนะนำ #' . $commission->id . ' ' . Satang::format($satang) . ' จากกระเป๋าผู้แนะนำแล้ว',
                    'satang' => $satang,
                    'transaction_id' => $transaction?->id,
                ];
            }, GpuxMineEarningSettlementService::DEADLOCK_ATTEMPTS);
        } catch (UniqueConstraintViolationException) {
            return $this->refuse('ค่าแนะนำรายการนี้ถูกเรียกคืนไปแล้ว');
        }
    }

    /** @return array{ok:bool, message:string, satang:int, transaction_id:?int}|null */
    private function earningNotReversible(?GpuJobEarning $earning): ?array
    {
        return match (true) {
            $earning === null => $this->refuse('ไม่พบรายการนี้'),
            $earning->reversed_at !== null => $this->refuse('รายการนี้ถูกเรียกคืนไปแล้ว'),
            $earning->status !== GpuJobEarning::STATUS_PAID => $this->refuse(
                'เรียกคืนได้เฉพาะรายได้ที่เข้ากระเป๋าแล้ว — งานนี้ ' . $earning->statusLabel()
                . (in_array($earning->status, GpuJobEarning::UNPAID_STATUSES, true) ? ' ใช้ปุ่มยกเลิกแทน' : '')
            ),
            default => null,
        };
    }

    /** @return array{ok:bool, message:string, satang:int, transaction_id:?int}|null */
    private function commissionNotReversible(?AffiliateCommission $commission): ?array
    {
        return match (true) {
            $commission === null => $this->refuse('ไม่พบค่าแนะนำรายการนี้'),
            $commission->source_type !== GpuxMineReferralPayoutService::SOURCE => $this->refuse('เรียกคืนได้เฉพาะค่าแนะนำจาก GPUxMINE'),
            $commission->status === AffiliateCommission::STATUS_REVERSED => $this->refuse('ค่าแนะนำรายการนี้ถูกเรียกคืนไปแล้ว'),
            $commission->status !== 'paid' => $this->refuse('ค่าแนะนำรายการนี้ยังไม่ได้จ่าย (' . $commission->status_label . ') — ใช้ปฏิเสธแทน'),
            default => null,
        };
    }

    /** ยอดที่ใช้ได้ไม่พอ = เงินออกไปแล้ว — บอกตัวเลขจริงให้แอดมินเห็น */
    private function shortOf(Wallet $wallet, int $satang): ?array
    {
        $available = $this->ledger->balanceSatang($wallet->id);

        return $available < $satang ? $this->withdrawnAlready($satang, $available) : null;
    }

    private function withdrawnAlready(int $satang, int $available): array
    {
        return $this->refuse('ถอนออกไปแล้ว — เรียกคืนไม่ได้: กระเป๋าเหลือยอดที่ใช้ได้ ' . Satang::format(max(0, $available))
            . ' ไม่พอเรียกคืน ' . Satang::format($satang)
            . ' (เงินถูกถอนเป็นเงินสด ถูกใช้ไปแล้ว หรือพักไว้กับคำขอถอนที่รออยู่)');
    }

    private function badReason(string $reason): ?array
    {
        $length = mb_strlen($reason);

        return match (true) {
            $length < self::MIN_REASON => $this->refuse('กรุณาระบุเหตุผลอย่างน้อย ' . self::MIN_REASON . ' ตัวอักษร — เจ้าของเงินจะเห็นข้อความนี้'),
            $length > self::MAX_REASON => $this->refuse('เหตุผลยาวเกิน ' . self::MAX_REASON . ' ตัวอักษร'),
            default => null,
        };
    }

    /** @return array{ok:bool, message:string, satang:int, transaction_id:?int} */
    private function refuse(string $message): array
    {
        return ['ok' => false, 'message' => $message, 'satang' => 0, 'transaction_id' => null];
    }
}
