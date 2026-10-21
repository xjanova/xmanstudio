<?php

namespace App\Services;

use App\Exceptions\PayoutReversalRefused;
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
 *   - ทำได้ครั้งเดียว: ตรวจสถานะใต้ row lock และเลขรายการ GXV{id งาน} / GXVR{id งาน} (ส่วนผู้แนะนำ
 *     ของงานนั้น) / GXAV{id ค่าแนะนำ} เป็น unique — กดซ้ำหรือสองแท็บพร้อมกันไม่หักสองรอบ
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

    /** ส่วนแบ่งผู้แนะนำของงานหนึ่งงานที่ถูกหักคืนจากกระเป๋าผู้แนะนำ — GXVR{id งาน} */
    public const REFERRAL_SHARE_PREFIX = 'GXVR';

    public function __construct(private readonly WalletLedger $ledger) {}

    /**
     * เรียกคืนรายได้ของเครื่องหนึ่งงาน — และส่วนแบ่งผู้แนะนำของงานเดียวกันถ้า $withReferral
     *
     * ส่วนแบ่งผู้แนะนำอยู่ในค่าแนะนำก้อนรวมของรอบโอน (หลายงานต่อหนึ่งรายการ) จึงเอาออกเฉพาะส่วนของงานนี้:
     *   - ค่าแนะนำจ่ายแล้ว: หักกระเป๋าผู้แนะนำเท่าส่วนของงานนี้ (GXVR{id งาน}) ภายใต้กฎยอดที่ใช้ได้
     *     ข้อเดียวกัน — ผู้แนะนำถอนไปแล้ว = ปฏิเสธทั้งหมด (แอดมินเลือกเรียกคืนเฉพาะส่วนเจ้าของได้)
     *   - ค่าแนะนำยังไม่จ่าย (กระเป๋าผู้แนะนำถูกปิด): ตัดยอดของรายการลง เหลือศูนย์ = ปฏิเสธรายการนั้น
     *     — ไม่งั้นการกวาดรอบหน้าจ่ายเต็มจำนวนทั้งที่งานถูกเรียกคืนไปแล้ว
     *   - ค่าแนะนำถูกเรียกคืนทั้งก้อนหรือถูกปฏิเสธไปแล้ว: ไม่มีอะไรต้องทำ
     *
     * ลำดับล็อกเดียวกับตอนจ่าย: กระเป๋าเจ้าของ → แถวงาน → กระเป๋าผู้แนะนำ → แถวค่าแนะนำ
     *
     * @return array{ok:bool, message:string, satang:int, transaction_id:?int}
     */
    public function reverseEarning(int $earningId, User $admin, string $reason, bool $withReferral = false): array
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
            return DB::transaction(function () use ($earningId, $admin, $reason, $withReferral) {
                $row = GpuJobEarning::whereKey($earningId)->first();
                $wallet = Wallet::where('user_id', $row->user_id)->lockForUpdate()->first();
                $row = GpuJobEarning::whereKey($earningId)->lockForUpdate()->first();

                if ($refused = $this->earningNotReversible($row)) {
                    return $refused;
                }

                $satang = max(0, (int) $row->amount_satang);
                $transaction = null;

                // ส่วนผู้แนะนำ: ล็อกและตรวจให้ครบก่อนเขียนอะไรเลย — ปฏิเสธได้โดยไม่ต้องย้อนเงินที่หักไปแล้ว
                $referral = $this->referralShareOf($row);
                if ($withReferral && $referral !== null && ($refused = $this->referralNotTakeable($referral))) {
                    return $refused;
                }

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

                // reviewed_by/reviewed_at ไม่ถูกแตะ — เป็นร่องรอยว่าใครอนุมัติงานที่เคยติดรอตรวจ
                // ผู้เรียกคืนอยู่ใน reversed_by/reversed_at ของมันเอง
                $updated = GpuJobEarning::whereKey($row->id)
                    ->where('status', GpuJobEarning::STATUS_PAID)
                    ->whereNull('reversed_at')
                    ->update([
                        'status' => GpuJobEarning::STATUS_VOID,
                        'void_reason' => $reason,
                        'reversed_at' => now(),
                        'reversed_by' => $admin->id,
                        'reversal_wallet_transaction_id' => $transaction?->id,
                    ]);

                if ($updated !== 1) {
                    // แถวถูกล็อกอยู่ — ถ้ามาถึงตรงนี้คือมีอะไรผิดปกติ ย้อนทั้งหมดรวมเงินที่เพิ่งหัก
                    throw new RuntimeException("GPUxMINE reversal: earning {$row->id} changed under its lock");
                }

                $referralNote = $referral === null ? ''
                    : ($withReferral
                        ? $this->takeReferralShare($row, $referral, $admin, $reason)
                        : ' · ส่วนแบ่งผู้แนะนำของงานนี้ ' . Satang::format($referral['satang']) . ' (ค่าแนะนำ #' . $referral['commission']->id . ') ไม่ได้เรียกคืน');

                Log::warning('[GPUxMINE] paid earning reversed by admin', [
                    'earning_id' => $row->id,
                    'satang' => $satang,
                    'wallet_transaction_id' => $transaction?->id,
                    'referral' => $referral === null ? null : [
                        'affiliate_commission_id' => $referral['commission']->id,
                        'satang' => $referral['satang'],
                        'taken' => $withReferral,
                    ],
                    'by' => $admin->id,
                ]);

                return [
                    'ok' => true,
                    'message' => 'เรียกคืนรายได้ ' . $row->job_id . ' ' . Satang::format($satang) . ' จากกระเป๋าเจ้าของเครื่องแล้ว' . $referralNote,
                    'satang' => $satang,
                    'transaction_id' => $transaction?->id,
                ];
            }, GpuxMineEarningSettlementService::DEADLOCK_ATTEMPTS);
        } catch (UniqueConstraintViolationException) {
            return $this->refuse('รายการนี้ถูกเรียกคืนไปแล้ว');
        } catch (PayoutReversalRefused $e) {
            return $this->refuse($e->getMessage());
        }
    }

    /**
     * ส่วนแบ่งผู้แนะนำของงานนี้ที่ยังเอาออกได้ — ล็อกกระเป๋าผู้แนะนำแล้วแถวค่าแนะนำ (null = ไม่มีอะไรให้เอาออก)
     *
     * @return array{commission: AffiliateCommission, affiliate: ?Affiliate, wallet: ?Wallet, satang: int}|null
     */
    private function referralShareOf(GpuJobEarning $row): ?array
    {
        $share = (int) $row->referral_satang;
        if ($share <= 0 || $row->affiliate_commission_id === null || $row->referral_unpaid_to !== null || (int) $row->referral_reversed_satang > 0) {
            return null;
        }

        $peek = AffiliateCommission::find($row->affiliate_commission_id);
        if ($peek === null || $peek->source_type !== GpuxMineReferralPayoutService::SOURCE) {
            return null;
        }

        $affiliate = Affiliate::find($peek->affiliate_id);
        $wallet = $affiliate ? Wallet::where('user_id', $affiliate->user_id)->lockForUpdate()->first() : null;
        $commission = AffiliateCommission::whereKey($peek->id)->lockForUpdate()->first();

        if ($commission === null || ! in_array($commission->status, ['pending', 'paid'], true)) {
            // ถูกเรียกคืนทั้งก้อน หรือถูกปฏิเสธ (ไม่เคยจ่าย) ไปแล้ว — ส่วนของงานนี้ไม่อยู่กับผู้แนะนำ
            return null;
        }

        $left = $this->commissionLeftSatang($commission);

        return $left > 0 ? ['commission' => $commission, 'affiliate' => $affiliate, 'wallet' => $wallet, 'satang' => min($share, $left)] : null;
    }

    /** @param  array{commission: AffiliateCommission, affiliate: ?Affiliate, wallet: ?Wallet, satang: int}  $referral */
    private function referralNotTakeable(array $referral): ?array
    {
        if ($referral['commission']->status !== 'paid') {
            return null;
        }
        if ($referral['wallet'] === null) {
            return $this->refuse('ไม่พบกระเป๋าเงินของผู้แนะนำ — เอาเครื่องหมาย "เรียกคืนส่วนแบ่งผู้แนะนำ" ออก เพื่อเรียกคืนเฉพาะส่วนของเจ้าของเครื่อง');
        }

        $available = $this->ledger->balanceSatang($referral['wallet']->id);

        return $available < $referral['satang']
            ? $this->refuse('ผู้แนะนำถอนออกไปแล้ว — เรียกคืนส่วนแบ่งผู้แนะนำ ' . Satang::format($referral['satang'])
                . ' ไม่ได้ (กระเป๋าผู้แนะนำเหลือยอดที่ใช้ได้ ' . Satang::format(max(0, $available)) . ')'
                . ' · เอาเครื่องหมาย "เรียกคืนส่วนแบ่งผู้แนะนำ" ออก เพื่อเรียกคืนเฉพาะส่วนของเจ้าของเครื่อง')
            : null;
    }

    /**
     * เอาส่วนแบ่งผู้แนะนำของงานที่เพิ่งเรียกคืนออก — ทุกอย่างถูกล็อกและตรวจแล้วใน reverseEarning()
     *
     * @param  array{commission: AffiliateCommission, affiliate: ?Affiliate, wallet: ?Wallet, satang: int}  $referral
     */
    private function takeReferralShare(GpuJobEarning $row, array $referral, User $admin, string $reason): string
    {
        $commission = $referral['commission'];
        $satang = $referral['satang'];
        $amount = Satang::toBaht($satang);

        if ($commission->status === 'pending') {
            $left = Satang::fromBaht((string) $commission->commission_amount) - $satang;

            $updated = AffiliateCommission::whereKey($commission->id)->where('status', 'pending')->update(array_merge(
                ['commission_amount' => Satang::toBaht($left)],
                $left > 0 ? [] : ['status' => 'rejected', 'admin_note' => 'ทุกงานในรายการนี้ถูกเรียกคืนแล้ว — ' . $reason],
            ));
            if ($updated !== 1) {
                throw new RuntimeException("GPUxMINE reversal: commission {$commission->id} changed under its lock");
            }

            if ($referral['affiliate']) {
                Affiliate::whereKey($referral['affiliate']->id)->decrement('total_earned', $amount);
                Affiliate::whereKey($referral['affiliate']->id)->decrement('total_pending', $amount);
            }
            GpuJobEarning::whereKey($row->id)->update(['referral_reversed_satang' => $satang]);

            return ' · ตัดส่วนแบ่งผู้แนะนำ ' . Satang::format($satang) . ' ออกจากค่าแนะนำ #' . $commission->id . ' ที่ยังไม่จ่าย'
                . ($left > 0 ? ' (เหลือ ' . Satang::format($left) . ')' : ' — ไม่เหลือยอดแล้ว รายการถูกปฏิเสธ');
        }

        $transaction = $this->ledger->debit($referral['wallet'], $satang, [
            'transaction_id' => self::REFERRAL_SHARE_PREFIX . $row->id,
            'type' => WalletTransaction::TYPE_ADJUSTMENT,
            'reference_type' => 'affiliate_commissions',
            'reference_id' => $commission->id,
            'description' => 'เรียกคืนส่วนแบ่งค่าแนะนำ GPUxMINE ของงาน ' . $row->job_id . ' (ค่าแนะนำ #' . $commission->id . ') · ' . $reason,
            'admin_note' => $reason,
            'created_by' => $admin->id,
            'status' => WalletTransaction::STATUS_COMPLETED,
            'metadata' => [
                'source' => WalletTransaction::SOURCE_REVERSAL,
                'reverses' => 'gpu_job_earning_referral',
                'earning_id' => $row->id,
                'job_id' => $row->job_id,
                'affiliate_commission_id' => $commission->id,
                'reverses_wallet_transaction_id' => $commission->wallet_transaction_id,
                'amount_satang' => $satang,
                'reason' => $reason,
                'admin_id' => $admin->id,
            ],
        ]);

        if ($transaction === null) {
            // ตรวจยอดใต้ล็อกไปแล้ว — มาถึงนี่ได้แค่เมื่อมีคนหักกระเป๋าโดยไม่ผ่านล็อกของเรา ย้อนทั้งหมด
            throw new PayoutReversalRefused('ผู้แนะนำถอนออกไปแล้ว — เรียกคืนส่วนแบ่งผู้แนะนำ ' . Satang::format($satang) . ' ไม่ได้ ลองใหม่ หรือเรียกคืนเฉพาะส่วนของเจ้าของเครื่อง');
        }

        if ($referral['affiliate']) {
            Affiliate::whereKey($referral['affiliate']->id)->decrement('total_earned', $amount);
            Affiliate::whereKey($referral['affiliate']->id)->decrement('total_paid', $amount);
        }
        GpuJobEarning::whereKey($row->id)->update([
            'referral_reversed_satang' => $satang,
            'referral_reversal_wallet_transaction_id' => $transaction->id,
        ]);

        return ' · และส่วนแบ่งผู้แนะนำ ' . Satang::format($satang) . ' จากกระเป๋าผู้แนะนำ (ค่าแนะนำ #' . $commission->id . ')';
    }

    /**
     * ยอดของค่าแนะนำที่ยังอยู่กับผู้แนะนำ
     *
     * ส่วนของงานที่ถูกเอาออกตอนค่าแนะนำยังไม่จ่าย ถูกตัดจาก commission_amount ไปแล้ว (ไม่มีรายการในกระเป๋า)
     * ส่วนที่ถูกหักจากกระเป๋าหลังจ่ายแล้วมี referral_reversal_wallet_transaction_id — หักเฉพาะอย่างหลัง
     * ไม่งั้นส่วนที่ตัดไปแล้วถูกลบซ้ำเมื่อค่าแนะนำรายการนั้นถูกจ่ายทีหลัง
     */
    private function commissionLeftSatang(AffiliateCommission $commission): int
    {
        $total = Satang::fromBaht((string) $commission->commission_amount);

        if ($commission->status !== 'paid') {
            return $total;
        }

        return $total - (int) GpuJobEarning::where('affiliate_commission_id', $commission->id)
            ->whereNotNull('referral_reversal_wallet_transaction_id')
            ->sum('referral_reversed_satang');
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

                // ส่วนของงานที่ถูกเรียกคืนแยกไปก่อนแล้ว (GXVR) ไม่ถูกหักซ้ำ
                $satang = max(0, $this->commissionLeftSatang($commission));
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
