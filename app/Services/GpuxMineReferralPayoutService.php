<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\Satang;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ค่าแนะนำ GPUxMINE เข้ากระเป๋า XMAN ของผู้แนะนำเอง ไม่ต้องรอแอดมินอนุมัติ (R2-3)
 *
 * รอบแรก (D8) ค่าแนะนำเป็น AffiliateCommission ที่ pending รอแอดมินกดจ่ายเหมือนทุกช่องทาง
 * เจ้าของตัดสินใหม่: จ่ายอัตโนมัติ แต่เรียกคืนได้ถ้ายังไม่ถอนเป็นเงินสด (ดู
 * GpuxMinePayoutReversalService) รายการ AffiliateCommission ยังอยู่ครบสำหรับรายงาน เพียงแต่
 * กลายเป็น paid พร้อมรายการในกระเป๋าที่ผูกไว้ทันที
 *
 * กันจ่ายซ้ำแบบเดียวกับรายได้ของเครื่อง:
 *   - ล็อกกระเป๋าของผู้แนะนำก่อนแถวค่าแนะนำเสมอ (ลำดับเดียวกับการจ่าย การถอน และการเรียกคืน)
 *   - claim ด้วย UPDATE แบบมีเงื่อนไข (pending → paid) ที่ชนะได้คนเดียว
 *   - เลขรายการในกระเป๋า GXA{id ค่าแนะนำ} คำนวณซ้ำได้และเป็น unique
 *   - บวกเงินแบบ atomic ไม่ผ่าน addBonus() — ไม่บวก total_deposited (ค่าแนะนำไม่ใช่เงินที่เติม)
 *
 * จ่ายไม่ได้ตอนนี้ (ผู้แนะนำถูกระงับ บัญชีหรือกระเป๋าถูกปิด): รายการค้างเป็น pending ไม่หาย และ
 * payPending() ในรอบ settle-earnings ถัด ๆ ไปลองให้เองเมื่อเปิดคืน — แอดมินยังปฏิเสธได้ทางเดิม
 */
class GpuxMineReferralPayoutService
{
    public const SOURCE = 'gpuxmine';

    public const TRANSACTION_PREFIX = 'GXA';

    /** ค่าแนะนำที่ค้างต่อรอบ — ที่เหลือรอรอบถัดไป */
    public const SWEEP_LIMIT = 500;

    public function __construct(private readonly WalletLedger $ledger) {}

    /**
     * จ่ายค่าแนะนำ GPUxMINE ที่ pending หนึ่งรายการเข้ากระเป๋าผู้แนะนำ
     *
     * เรียกได้ทั้งในทรานแซกชันของการจ่ายรายได้ (ค่าแนะนำที่เพิ่งสร้าง) และแยกเดี่ยว ๆ
     * คืน false (ไม่ throw) เมื่อจ่ายไม่ได้ตอนนี้ หรือมีคนจ่าย/ปฏิเสธไปแล้ว — ความผิดพลาดของ
     * ฐานข้อมูลเท่านั้นที่ throw และย้อนทั้งทรานแซกชันของผู้เรียก
     */
    public function pay(int $commissionId): bool
    {
        $peek = AffiliateCommission::find($commissionId);
        if ($peek === null || $peek->status !== 'pending' || $peek->source_type !== self::SOURCE) {
            return false;
        }

        $affiliate = Affiliate::find($peek->affiliate_id);
        $user = $affiliate ? User::find($affiliate->user_id) : null;
        if ($this->referrerBlocker($affiliate, $user) !== null) {
            return false;
        }

        $walletId = $this->walletFor((int) $user->id)->id;

        return DB::transaction(function () use ($commissionId, $walletId, $affiliate) {
            $wallet = Wallet::whereKey($walletId)->lockForUpdate()->first();
            if ($wallet === null || ! $wallet->is_active) {
                return false;
            }

            $commission = AffiliateCommission::whereKey($commissionId)->lockForUpdate()->first();
            if ($commission === null || $commission->status !== 'pending' || (int) $commission->affiliate_id !== (int) $affiliate->id) {
                return false;
            }

            $claimed = AffiliateCommission::whereKey($commissionId)
                ->where('status', 'pending')
                ->update(['status' => 'paid', 'paid_at' => now()]);

            if ($claimed !== 1) {
                return false;
            }

            $satang = Satang::fromBaht((string) $commission->commission_amount);
            $transaction = $satang > 0 ? $this->ledger->credit($wallet, $satang, [
                'transaction_id' => self::TRANSACTION_PREFIX . $commission->id,
                'type' => WalletTransaction::TYPE_EARNING,
                'reference_type' => 'affiliate_commissions',
                'reference_id' => $commission->id,
                'description' => 'ค่าแนะนำ GPUxMINE · ' . ($commission->source_description ?: 'ค่าแนะนำ #' . $commission->id),
                'status' => WalletTransaction::STATUS_COMPLETED,
                'metadata' => [
                    'source' => WalletTransaction::SOURCE_GPUXMINE_REFERRAL,
                    'affiliate_commission_id' => $commission->id,
                    'affiliate_id' => $affiliate->id,
                    'referred_user_id' => $commission->referred_user_id,
                    'amount_satang' => $satang,
                ],
            ]) : null;

            AffiliateCommission::whereKey($commissionId)->update(['wallet_transaction_id' => $transaction?->id]);

            $amount = Satang::toBaht($satang);
            Affiliate::whereKey($affiliate->id)->increment('total_paid', $amount);
            Affiliate::whereKey($affiliate->id)->decrement('total_pending', $amount);

            return true;
        }, GpuxMineEarningSettlementService::DEADLOCK_ATTEMPTS);
    }

    /**
     * ทำไมค่าแนะนำ pending รายการนี้จ่ายเข้ากระเป๋าไม่ได้ตอนนี้ — null = จ่ายได้ (หรือไม่ใช่ pending แล้ว)
     *
     * หน้าค่าแนะนำของแอดมินบอกสาเหตุนี้ตรง ๆ แทน "อาจจ่ายแล้วหรือถูกปฏิเสธ"
     */
    public function blockedReason(int $commissionId): ?string
    {
        $commission = AffiliateCommission::find($commissionId);
        if ($commission === null || $commission->status !== 'pending' || $commission->source_type !== self::SOURCE) {
            return null;
        }

        $affiliate = Affiliate::find($commission->affiliate_id);
        $user = $affiliate ? User::find($affiliate->user_id) : null;

        if ($reason = $this->referrerBlocker($affiliate, $user)) {
            return $reason;
        }

        $wallet = Wallet::where('user_id', $user->id)->first();

        return $wallet !== null && ! $wallet->is_active
            ? 'กระเป๋าเงินของผู้แนะนำถูกระงับ — ระบบจะจ่ายเองเมื่อเปิดคืน หรือกดปฏิเสธถ้าไม่ต้องการจ่าย'
            : null;
    }

    /**
     * ค่าแนะนำ GPUxMINE ที่ยังค้าง pending — ของรอบแรกที่รอแอดมิน และของผู้แนะนำที่กระเป๋าเพิ่งเปิดคืน
     *
     * คัดเฉพาะรายการที่จ่ายได้จริงตั้งแต่ใน query (affiliate active, บัญชีไม่ถูกปิด, กระเป๋าไม่ถูกระงับ
     * หรือยังไม่มี) — รายการของผู้แนะนำที่จ่ายไม่ได้ไปเรื่อย ๆ ค้างเป็น pending ที่ id ต่ำสุด ถ้าไม่คัดออก
     * มันจะกินโควตา SWEEP_LIMIT ทุกรอบ และรายการของคนอื่นที่ id สูงกว่าไม่ถูกจ่ายอีกเลย
     * ที่ถูกคัดออกนับเป็น blocked ให้รายงานของคำสั่งเห็น
     *
     * รายการหนึ่งพังไม่ลากรายการอื่น รอบหน้าลองใหม่
     *
     * @return array{paid:int, waiting:int, failed:int, blocked:int}
     */
    public function payPending(): array
    {
        $result = ['paid' => 0, 'waiting' => 0, 'failed' => 0, 'blocked' => 0];

        $ids = $this->payableNow()
            ->orderBy('affiliate_commissions.id')
            ->limit(self::SWEEP_LIMIT)
            ->pluck('affiliate_commissions.id');

        $result['blocked'] = AffiliateCommission::where('source_type', self::SOURCE)->where('status', 'pending')->count()
            - $this->payableNow()->count();

        foreach ($ids as $id) {
            try {
                $this->pay((int) $id) ? $result['paid']++ : $result['waiting']++;
            } catch (Throwable $e) {
                $result['failed']++;
                Log::error('[GPUxMINE] paying a referral commission into the wallet failed', [
                    'affiliate_commission_id' => (int) $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }

    /**
     * ค่าแนะนำ GPUxMINE pending ที่ผู้แนะนำรับเงินได้ตอนนี้ — เงื่อนไขเดียวกับ referrerBlocker() และการ
     * ตรวจกระเป๋าใน pay() แต่อยู่ใน SQL (users.is_active ที่เป็น NULL ถือว่าเปิด แบบเดียวกับ pay())
     */
    private function payableNow(): Builder
    {
        return AffiliateCommission::query()
            ->join('affiliates', 'affiliates.id', '=', 'affiliate_commissions.affiliate_id')
            ->join('users', 'users.id', '=', 'affiliates.user_id')
            ->leftJoin('wallets', 'wallets.user_id', '=', 'users.id')
            ->where('affiliate_commissions.source_type', self::SOURCE)
            ->where('affiliate_commissions.status', 'pending')
            ->where('affiliates.status', 'active')
            ->where(fn (Builder $q) => $q->where('users.is_active', true)->orWhereNull('users.is_active'))
            ->where(fn (Builder $q) => $q->whereNull('wallets.id')->orWhere('wallets.is_active', true));
    }

    /** ผู้แนะนำคนนี้รับเงินไม่ได้เพราะอะไร (ไม่รวมกระเป๋า — ตรวจใต้ล็อกใน pay()) — null = รับได้ */
    private function referrerBlocker(?Affiliate $affiliate, ?User $user): ?string
    {
        return match (true) {
            $affiliate === null => 'ไม่พบบัญชี affiliate ของผู้แนะนำแล้ว — กดปฏิเสธรายการนี้แทน',
            ! $affiliate->isActive() => 'บัญชี affiliate ของผู้แนะนำไม่ active (' . $affiliate->status . ') — ระบบจะจ่ายเองเมื่อเปิดคืน หรือกดปฏิเสธถ้าไม่ต้องการจ่าย',
            $user === null => 'บัญชีผู้แนะนำถูกลบแล้ว — กดปฏิเสธรายการนี้แทน',
            $user->is_active === false => 'บัญชีผู้แนะนำถูกปิดการใช้งาน — ระบบจะจ่ายเองเมื่อเปิดคืน หรือกดปฏิเสธถ้าไม่ต้องการจ่าย',
            default => null,
        };
    }

    /** กระเป๋าของผู้แนะนำ — สร้างให้ถ้ายังไม่มี โดยไม่ชนกับใครที่สร้างพร้อมกัน */
    private function walletFor(int $userId): Wallet
    {
        return Wallet::where('user_id', $userId)->first()
            ?? Wallet::createOrFirst(['user_id' => $userId], [
                'balance' => 0,
                'total_deposited' => 0,
                'total_spent' => 0,
                'total_refunded' => 0,
                'is_active' => true,
            ]);
    }
}
