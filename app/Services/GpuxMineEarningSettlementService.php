<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\GpuJobEarning;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * พารายได้ของเจ้าของเครื่องจาก gpu_job_earnings ไปถึงกระเป๋า XMAN (D2)
 *
 * aixman เขียนแถวไว้เป็น pending ทุกงาน ตรงนี้ทำสองขั้น:
 *
 *  1. ปล่อยเงิน (clearMatured): แถว pending ที่พักมาครบระยะพักแล้ว (นับจากงานเสร็จ
 *     หรือแถวถูกบันทึก แล้วแต่อันไหนช้ากว่า) และเครื่องไม่ได้ถูกแอดมินระงับ → cleared
 *     ระยะพักคือช่วงที่ยังจับเครื่องที่ส่งผลงานปลอมทันก่อนเงินออก แถว review ไม่ถูกแตะ
 *
 *  2. จ่าย (payUser): ต่อเจ้าของหนึ่งคน ในทรานแซกชันเดียว ล็อกกระเป๋าแล้วล็อกแถว
 *     cleared ของเขา บวกเงินแบบ atomic เขียน wallet_transactions หนึ่งแถวต่อชุด
 *     แล้วเปลี่ยนแถวเป็น paid พร้อมเลขรายการ — ขั้นไหนพลาด ย้อนทั้งชุด
 *     ส่วนแบ่งผู้แนะนำ (referral_satang) กลายเป็น AffiliateCommission หนึ่งรายการต่อ
 *     ผู้แนะนำต่อชุด ในทรานแซกชันเดียวกัน รอแอดมินอนุมัติแบบเดียวกับค่าแนะนำจากทุกช่องทาง
 *     ส่วนแบ่งที่ไม่มีผู้แนะนำคนไหนรับได้แล้ว ไม่หายเงียบ: คืนเข้ายอดของเจ้าของเครื่องใน
 *     รายการเดียวกัน หรือแพลตฟอร์มเก็บไว้ตามที่ตั้ง — ดู unpaidReferralPolicy()
 *
 * กันจ่ายซ้ำหลายชั้น เพราะนี่คือเงินออกจริง: withoutOverlapping ที่ตัวตั้งเวลา,
 * row lock บนกระเป๋าและแถวรายได้, UPDATE แบบมีเงื่อนไข (status = cleared) ที่ต้อง
 * โดนครบทุกแถว และเลขรายการที่คำนวณได้ซ้ำ (GXM{user}-{id สูงสุดของชุด}) ซึ่งเป็น
 * unique — ตัวรันสองตัวที่หลุดมาพร้อมกันจะชนกันที่ฐานข้อมูล ไม่ใช่จ่ายสองรอบ
 *
 * เงินทั้งหมดคิดเป็นสตางค์จำนวนเต็ม แปลงเป็นทศนิยมสองตำแหน่งตอนเขียนลงคอลัมน์บาท
 * ด้วย BigDecimal เท่านั้น ไม่ผ่าน float
 */
class GpuxMineEarningSettlementService
{
    /** แถวต่อหนึ่งทรานแซกชัน — ล็อกกระเป๋าของใครไว้นานเกินไปไม่ได้ */
    public const BATCH_SIZE = 500;

    /** ชุดสูงสุดต่อเจ้าของต่อรอบ ที่เหลือรอรอบถัดไป */
    public const MAX_BATCHES_PER_USER = 20;

    /**
     * ทรานแซกชันที่ชนกับตัวรันอื่นจน deadlock ลองใหม่ได้กี่ครั้ง — Laravel ลองซ้ำเฉพาะ
     * ความผิดพลาดจากการแย่งล็อก ไม่ลองซ้ำเมื่อชนเลขรายการ (unique) ซึ่งแปลว่าจ่ายไปแล้ว
     */
    public const DEADLOCK_ATTEMPTS = 3;

    public const COMMISSION_SOURCE = 'gpuxmine';

    public const REFERENCE_TYPE = 'gpu_job_earnings';

    public function holdHours(): int
    {
        return max(0, (int) config('services.gpuxmine.earning_hold_hours', 24));
    }

    /**
     * ส่วนแบ่งผู้แนะนำที่ถึงวันโอนแล้วไม่มีใครรับได้ ไปที่ไหน (ต่อจาก D8)
     *
     * aixman หักส่วนแบ่งออกจากเงินของเจ้าของเครื่องตอนงานเสร็จ เมื่อผู้แนะนำเป็น affiliate ที่
     * active ตอนนั้น ถ้าถึงวันโอนผู้แนะนำถูกระงับ บัญชีถูกลบ หรือกลายเป็นเจ้าของเครื่องเอง
     * ส่วนนั้นเคยไม่ถูกจ่ายให้ใครและไม่คืนใคร — หายเข้าแพลตฟอร์มโดยมีแค่บรรทัดใน log
     *
     * 'owner' (ค่าเริ่มต้น): คืนเข้ายอดของเจ้าของเครื่อง เงินก้อนนั้นหักจากเขาตั้งแต่แรก
     * 'platform': แพลตฟอร์มเก็บไว้ ต้องตั้ง GPUXMINE_UNPAID_REFERRAL=platform เอง
     * ทั้งสองแบบบันทึกลงแถว (referral_unpaid_*) และเห็นในหน้าแอดมิน ค่าอื่นถือเป็น 'owner'
     */
    public function unpaidReferralPolicy(): string
    {
        return config('services.gpuxmine.unpaid_referral') === GpuJobEarning::REFERRAL_UNPAID_TO_PLATFORM
            ? GpuJobEarning::REFERRAL_UNPAID_TO_PLATFORM
            : GpuJobEarning::REFERRAL_UNPAID_TO_OWNER;
    }

    /**
     * pending ที่พ้นระยะพักแล้ว → cleared
     *
     * ยอดติดลบไม่ใช่สิ่งที่ aixman ควรเขียนมาได้ — ถ้าเจอ ส่งให้แอดมินดู ไม่หักเงิน
     * ใครเงียบ ๆ แถวที่ไม่มีเจ้าของ (บัญชีถูกลบ) ค้างไว้ตามเดิม
     *
     * ทำเป็นชุดเรียงตาม id ชุดละทรานแซกชันสั้น ๆ ไม่ใช่ UPDATE ก้อนเดียวทั้งตาราง:
     * UPDATE ก้อนใหญ่สองก้อนที่รันทับกัน (รันด้วยมือระหว่างที่ตัวตั้งเวลาทำงาน) เคย
     * deadlock กันจริงบน MySQL ชุดที่ล็อกตามลำดับ id ไม่วนรอกัน และถ้าชนก็ลองใหม่เอง
     *
     * @return array{cleared:int, review:int}
     */
    public function clearMatured(?CarbonInterface $now = null): array
    {
        $now ??= now();
        $cutoff = $now->copy()->subHours($this->holdHours());
        $counts = ['cleared' => 0, 'review' => 0];

        $this->notFrozen(
            $this->heldSince(
                GpuJobEarning::query()
                    ->where('status', GpuJobEarning::STATUS_PENDING)
                    ->whereNotNull('user_id'),
                $cutoff
            )
        )
            ->select(['id', 'amount_satang'])
            ->chunkById(self::BATCH_SIZE, function (Collection $rows) use ($now, &$counts) {
                [$negative, $payable] = $rows->partition(fn (GpuJobEarning $row) => $row->amount_satang < 0);

                $counts['review'] += $this->movePending($negative->modelKeys(), [
                    'status' => GpuJobEarning::STATUS_REVIEW,
                    'review_reason' => 'ยอดเงินติดลบ — ต้องตรวจก่อนจ่าย',
                ]);
                $counts['cleared'] += $this->movePending($payable->modelKeys(), [
                    'status' => GpuJobEarning::STATUS_CLEARED,
                    'cleared_at' => $now,
                ]);
            });

        return $counts;
    }

    /**
     * แถวที่พักมาครบถึง $cutoff แล้ว — นับจากเวลาที่ช้ากว่าระหว่างงานเสร็จกับแถวถูกบันทึก
     *
     * เคยนับจากงานเสร็จอย่างเดียว แต่ aixman เขียนย้อนหลังได้ถึงเจ็ดวัน (catch-up sweep
     * หลัง DB ล่ม, แพ็กเกจเครดิตถูกปิด, ยังไม่รู้เจ้าของ ฯลฯ) แถวที่เข้ามาช้าจึงพ้นระยะพัก
     * ตั้งแต่วินาทีที่เกิด และถูกโอนในรอบชั่วโมงถัดไป — ช่วงที่แอดมินจะจับผลงานปลอมได้ทัน
     * เหลือศูนย์ ระยะพักต้องเริ่มเมื่อเงินปรากฏในระบบให้คนเห็น ไม่ใช่ก่อนนั้น
     *
     * aixman ควรใส่ completed_at เสมอ — ถ้าไม่มี นับจากเวลาที่แถวเกิด ดีกว่าค้าง pending
     * ไปตลอด GpuJobEarning::holdEndsAt() คิดแบบเดียวกัน
     */
    public function heldSince(Builder $query, CarbonInterface $cutoff): Builder
    {
        return $query
            ->where(fn (Builder $q) => $q->whereNull('completed_at')->orWhere('completed_at', '<=', $cutoff))
            ->where(fn (Builder $q) => $q->whereNull('created_at')->orWhere('created_at', '<=', $cutoff))
            ->where(fn (Builder $q) => $q->whereNotNull('completed_at')->orWhereNotNull('created_at'));
    }

    /**
     * ย้ายแถวที่ยังเป็น pending อยู่จริงในชุดนี้ — เงื่อนไขเดิมถูกตรวจซ้ำตอนเขียน
     * แถวที่ตัวรันอีกตัวย้ายไปก่อนแล้ว หรือเครื่องที่เพิ่งถูกระงับระหว่างอ่านกับเขียน
     * ไม่ถูกแตะ
     *
     * @param  array<int, int>  $ids
     * @param  array<string, mixed>  $values
     */
    private function movePending(array $ids, array $values): int
    {
        if ($ids === []) {
            return 0;
        }

        return DB::transaction(
            fn () => $this->notFrozen(
                GpuJobEarning::whereKey($ids)->where('status', GpuJobEarning::STATUS_PENDING)
            )->update($values),
            self::DEADLOCK_ATTEMPTS
        );
    }

    /** @return array<int, int> เจ้าของที่มีเงิน cleared รอโอน */
    public function usersToPay(): array
    {
        return GpuJobEarning::query()
            ->where('status', GpuJobEarning::STATUS_CLEARED)
            ->whereNotNull('user_id')
            ->distinct()
            ->orderBy('user_id')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * โอนเงิน cleared ทั้งหมดของเจ้าของหนึ่งคนเข้ากระเป๋า
     *
     * บัญชีหรือกระเป๋าที่ถูกปิดใช้งาน: ไม่โอน แถวค้างเป็น cleared จนกว่าจะเปิดคืน
     *
     * @return array{skipped:bool, rows:int, satang:int, transactions:int, commissions:int}
     */
    public function payUser(int $userId): array
    {
        $result = ['skipped' => false, 'rows' => 0, 'satang' => 0, 'transactions' => 0, 'commissions' => 0];

        $user = User::find($userId);
        if ($user === null || $user->is_active === false) {
            return ['skipped' => true] + $result;
        }

        $wallet = $this->walletFor($userId);
        if (! $wallet->is_active) {
            return ['skipped' => true] + $result;
        }

        for ($i = 0; $i < self::MAX_BATCHES_PER_USER; $i++) {
            $batch = DB::transaction(fn () => $this->payBatch($userId, $wallet->id), self::DEADLOCK_ATTEMPTS);

            if ($batch === null) {
                // กระเป๋าถูกปิดระหว่างทาง
                return ['skipped' => true] + $result;
            }

            foreach (['rows', 'satang', 'transactions', 'commissions'] as $key) {
                $result[$key] += $batch[$key];
            }

            if ($batch['rows'] < self::BATCH_SIZE) {
                break;
            }
        }

        return $result;
    }

    /**
     * หนึ่งชุด ในทรานแซกชันของผู้เรียก — ล็อกกระเป๋าก่อนแถวรายได้เสมอ
     *
     * @return array{rows:int, satang:int, transactions:int, commissions:int}|null
     */
    private function payBatch(int $userId, int $walletId): ?array
    {
        $wallet = Wallet::whereKey($walletId)->lockForUpdate()->first();
        if ($wallet === null || ! $wallet->is_active) {
            return null;
        }

        /** @var Collection<int, GpuJobEarning> $rows */
        $rows = $this->payable()
            ->where('user_id', $userId)
            ->orderBy('id')
            ->limit(self::BATCH_SIZE)
            ->lockForUpdate()
            ->get();

        if ($rows->isEmpty()) {
            return ['rows' => 0, 'satang' => 0, 'transactions' => 0, 'commissions' => 0];
        }

        $ids = $rows->pluck('id')->map(fn ($id) => (int) $id)->all();

        // ตัดสินส่วนแบ่งผู้แนะนำก่อนคิดยอด: ส่วนที่ไม่มีใครรับได้แล้วและต้องคืนเจ้าของ
        // ต้องเข้ากระเป๋าในรายการเดียวกับงานของมัน ไม่ใช่หายไประหว่างทาง
        [$owed, $unpaid] = $this->splitReferrals($rows);
        $returned = $this->settleUnpaidReferrals($unpaid);
        $satang = (int) $rows->sum('amount_satang') + $returned['satang'];

        // งานแชร์ฟรีได้ 0 — ปิดแถวเป็น paid ได้เลย ไม่ต้องมีรายการ 0 บาทในกระเป๋า
        $transaction = $satang > 0 ? $this->credit($wallet, $satang, $ids, $returned) : null;

        $now = now();
        $paid = GpuJobEarning::whereKey($ids)
            ->where('status', GpuJobEarning::STATUS_CLEARED)
            ->update([
                'status' => GpuJobEarning::STATUS_PAID,
                'paid_at' => $now,
                'wallet_transaction_id' => $transaction?->id,
            ]);

        if ($paid !== count($ids)) {
            // มีแถวในชุดนี้ถูกคนอื่นเปลี่ยนไปแล้ว ทั้งที่ล็อกไว้ — ย้อนทั้งชุด
            // รวมเงินที่เพิ่งบวกเข้ากระเป๋า ดีกว่าจ่ายเกิน
            throw new RuntimeException("GPUxMINE payout for user {$userId}: expected " . count($ids) . " cleared rows, updated {$paid}");
        }

        $commissions = $this->recordReferrals($owed);

        return [
            'rows' => count($ids),
            'satang' => $satang,
            'transactions' => $transaction ? 1 : 0,
            'commissions' => $commissions,
        ];
    }

    /**
     * บวกเงินเข้ากระเป๋าหนึ่งรายการ — ไม่ผ่าน Wallet::deposit()/addBonus()
     *
     * สองตัวนั้นบวก total_deposited ด้วย และอ่าน balance_before โดยไม่ล็อก รายได้
     * ไม่ใช่เงินที่เจ้าของเติม ตรงนี้บวกแบบ atomic (balance = balance + x) โดยมีเงื่อนไข
     * ว่ากระเป๋ายังเปิดใช้งาน แล้วอ่านยอดหลังบวกในทรานแซกชันเดียวกัน — แบบเดียวกับ
     * ที่ aixman ตัดเงินจากกระเป๋าใบนี้อยู่ (wallet.ts)
     *
     * ส่วนแบ่งผู้แนะนำที่คืนให้เจ้าของ (ถ้ามี) รวมอยู่ใน $satang แล้ว — metadata บอกว่าเท่าไร
     * จากงานไหน ให้คนที่ดูรายการในกระเป๋าตามได้ว่าทำไมยอดสูงกว่าผลรวมที่ aixman เขียน
     *
     * @param  array<int, int>  $ids
     * @param  array{satang:int, earning_ids:array<int, int>}  $returned
     */
    private function credit(Wallet $wallet, int $satang, array $ids, array $returned): WalletTransaction
    {
        $amount = self::baht($satang);

        $updated = Wallet::whereKey($wallet->id)
            ->where('is_active', true)
            ->increment('balance', $amount);

        if ($updated !== 1) {
            throw new RuntimeException("GPUxMINE payout: wallet {$wallet->id} refused the credit");
        }

        $after = self::satang((string) Wallet::whereKey($wallet->id)->value('balance'));
        $lastId = max($ids);

        return WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            // คำนวณซ้ำได้จากชุดเดิม และเป็น unique — ชุดเดียวกันเข้ากระเป๋าสองรอบไม่ได้
            'transaction_id' => sprintf('GXM%d-%d', $wallet->user_id, $lastId),
            'type' => WalletTransaction::TYPE_EARNING,
            'amount' => $amount,
            'balance_before' => self::baht($after - $satang),
            'balance_after' => self::baht($after),
            'reference_type' => self::REFERENCE_TYPE,
            'reference_id' => $lastId,
            'description' => 'รายได้จากการแชร์การ์ดจอ GPUxMINE · ' . count($ids) . ' งาน'
                . ($returned['satang'] > 0 ? ' · รวมส่วนแบ่งผู้แนะนำที่คืนให้ ฿' . self::baht($returned['satang']) : ''),
            'status' => WalletTransaction::STATUS_COMPLETED,
            'metadata' => [
                'source' => 'gpuxmine',
                'jobs' => count($ids),
                'amount_satang' => $satang,
                'earning_ids' => $ids,
                'returned_referral_satang' => $returned['satang'],
                'returned_referral_earning_ids' => $returned['earning_ids'],
            ],
        ]);
    }

    /**
     * แยกส่วนแบ่งผู้แนะนำของชุดนี้เป็น "จ่ายได้" กับ "ไม่มีใครรับได้แล้ว"
     *
     * จ่ายได้ = ผู้แนะนำมีบัญชี affiliate ที่ active และไม่ใช่เจ้าของเครื่องเอง ที่เหลือ (ผู้แนะนำถูก
     * ระงับ ไม่มีบัญชี affiliate บัญชีถูกลบจน referral_user_id กลายเป็น null หรือเป็นเจ้าของเอง)
     * ไม่มีใครรับได้ งานที่มีค่าแนะนำอยู่แล้ว (affiliate_commission_id) หรือถูกตัดสินไปแล้ว
     * (referral_unpaid_to) ไม่ถูกนับอีก ค่าแนะนำรายงานเดิมที่บันทึกไว้ก่อนเปลี่ยนเป็นแบบรวม
     * (source_id = id ของงานนั้น) ถูกผูกกลับตรงนี้ ไม่ถูกสร้างซ้ำ และไม่ถูกคืนใคร
     *
     * @param  Collection<int, GpuJobEarning>  $rows  แถวของเจ้าของหนึ่งคนที่กำลังจ่าย (ล็อกอยู่)
     * @return array{0: array<int, array{affiliate: Affiliate, rows: Collection<int, GpuJobEarning>}>, 1: Collection<int, GpuJobEarning>}
     */
    private function splitReferrals(Collection $rows): array
    {
        $candidates = $rows->filter(fn (GpuJobEarning $row) => $row->referral_satang > 0
            && $row->affiliate_commission_id === null
            && $row->referral_unpaid_to === null);

        if ($candidates->isEmpty()) {
            return [[], new Collection];
        }

        $existing = AffiliateCommission::where('source_type', self::COMMISSION_SOURCE)
            ->whereIn('source_id', $candidates->modelKeys())
            ->pluck('id', 'source_id');

        foreach ($existing as $sourceId => $commissionId) {
            GpuJobEarning::whereKey((int) $sourceId)->update(['affiliate_commission_id' => (int) $commissionId]);
        }

        $owed = [];
        $unpaid = new Collection;
        $candidates = $candidates->reject(fn (GpuJobEarning $row) => $existing->has($row->id));

        foreach ($candidates->groupBy(fn (GpuJobEarning $row) => (int) $row->referral_user_id) as $referrerId => $group) {
            $ownerId = (int) $group->first()->user_id;
            $affiliate = $referrerId > 0 && $referrerId !== $ownerId
                ? Affiliate::where('user_id', $referrerId)->first()
                : null;

            if ($affiliate !== null && $affiliate->isActive()) {
                $owed[] = ['affiliate' => $affiliate, 'rows' => $group];
            } else {
                $unpaid = $unpaid->merge($group);
            }
        }

        return [$owed, $unpaid];
    }

    /**
     * ส่วนแบ่งผู้แนะนำที่ไม่มีใครรับได้แล้ว → ตามนโยบาย (unpaidReferralPolicy) แล้วบันทึกลงแถว
     *
     * 'owner': ย้ายเข้า amount_satang ของงานนั้น และ referral_satang เป็น 0 — ยอดของงานบอกสิ่งที่
     *   เจ้าของได้จริง (หน้าเจ้าของ, /status ของโปรแกรม และยอดรวมทุกที่ตรงกับกระเป๋าเอง) และ
     *   amount + referral ยังเท่ากับส่วนของเครื่องที่ aixman คิดไว้ (aixman ใช้ผลรวมนี้คิดคะแนน
     *   ความร่วมมือ) ผู้เรียกบวกยอดที่คืนเข้ารายการในกระเป๋าของชุดเดียวกัน
     * 'platform': แถวคงยอดเดิม (ส่วนแบ่งยังถูกหักจากเจ้าของ) แค่ประทับว่าไม่มีใครรับ แพลตฟอร์มเก็บ
     *
     * UPDATE มีเงื่อนไขเดียวกับที่เลือกแถวมา และต้องโดนครบทุกแถว — ไม่ครบคือมีคนแตะแถวที่ล็อก
     * อยู่ ย้อนทั้งชุด
     *
     * @param  Collection<int, GpuJobEarning>  $unpaid
     * @return array{satang:int, earning_ids:array<int, int>} ยอดที่คืนเข้ากระเป๋าเจ้าของ
     */
    private function settleUnpaidReferrals(Collection $unpaid): array
    {
        if ($unpaid->isEmpty()) {
            return ['satang' => 0, 'earning_ids' => []];
        }

        $policy = $this->unpaidReferralPolicy();
        $ids = $unpaid->modelKeys();
        $satang = (int) $unpaid->sum('referral_satang');

        $marked = GpuJobEarning::whereKey($ids)
            ->where('status', GpuJobEarning::STATUS_CLEARED)
            ->where('referral_satang', '>', 0)
            ->whereNull('referral_unpaid_to')
            ->whereNull('affiliate_commission_id')
            ->update(array_merge(
                [
                    'referral_unpaid_satang' => DB::raw('referral_satang'),
                    'referral_unpaid_to' => $policy,
                ],
                // อ่าน referral_satang เดิมทั้งสองช่อง — MySQL ประเมิน SET จากซ้ายไปขวา
                // จึงศูนย์มันในคำสั่งถัดไป ไม่ใช่คำสั่งนี้
                $policy === GpuJobEarning::REFERRAL_UNPAID_TO_OWNER
                    ? ['amount_satang' => DB::raw('amount_satang + referral_satang')]
                    : [],
            ));

        if ($marked !== count($ids)) {
            throw new RuntimeException('GPUxMINE payout: expected ' . count($ids) . " unpaid referral rows, updated {$marked}");
        }

        Log::info('[GPUxMINE] referral share had no active referrer at payout — ' . ($policy === GpuJobEarning::REFERRAL_UNPAID_TO_OWNER ? 'returned to the owner' : 'kept by the platform'), [
            'earning_ids' => $ids,
            'referral_user_ids' => $unpaid->pluck('referral_user_id')->unique()->values()->all(),
            'satang' => $satang,
        ]);

        if ($policy !== GpuJobEarning::REFERRAL_UNPAID_TO_OWNER) {
            return ['satang' => 0, 'earning_ids' => []];
        }

        GpuJobEarning::whereKey($ids)
            ->where('referral_unpaid_to', GpuJobEarning::REFERRAL_UNPAID_TO_OWNER)
            ->update(['referral_satang' => 0]);

        return ['satang' => $satang, 'earning_ids' => array_map('intval', $ids)];
    }

    /**
     * ส่วนแบ่งผู้แนะนำของชุดที่เพิ่งจ่าย → AffiliateCommission หนึ่งรายการต่อผู้แนะนำ (pending
     * รอแอดมินอนุมัติเหมือนค่าแนะนำจากทุกช่องทาง)
     *
     * เคยเป็นหนึ่งรายการต่อหนึ่งงาน: เครื่องสามเครื่องที่ทำงานวันละสี่ร้อยชิ้น ทำให้ค่าแนะนำ
     * pending ขึ้นเป็นพันต่อสัปดาห์ และทางอนุมัติรวดเดียวของหน้า affiliate ส่งเมลทีละรายการ
     * จนคำขอยาวเกินเวลาของ proxy — แอดมินกดซ้ำแล้วจ่ายซ้ำได้ ตอนนี้ชุดหนึ่ง (เจ้าของหนึ่งคน
     * ต่อรอบโอน) ได้หนึ่งรายการต่อผู้แนะนำ source_id คือ id สูงสุดของงานในรายการนั้น และทุก
     * งานในชุดชี้กลับมาที่รายการเดียวกันด้วย affiliate_commission_id
     *
     * กันซ้ำ: แถวถูกล็อกอยู่ในทรานแซกชันของการจ่าย และแถวที่มี affiliate_commission_id แล้ว
     * ไม่ถูกนับอีก (splitReferrals() คัดมาให้แล้ว รวมการผูกค่าแนะนำรายงานเดิมกลับ)
     *
     * ผู้แนะนำที่ถูกระงับ ไม่มีบัญชี affiliate แล้ว หรือเป็นเจ้าของเครื่องเอง ไม่มาถึงตรงนี้ —
     * ส่วนของเขาถูกตัดสินใน settleUnpaidReferrals() ไม่หายเงียบอีก
     *
     * ยอดสะสมของ affiliate ขยับแบบเดียวกับ AffiliateCommissionService (total_earned,
     * total_pending) แต่ total_referrals/total_conversions นับเจ้าของเครื่องหนึ่งคน
     * ครั้งเดียว ไม่ใช่ทุกรายการ — ไม่งั้นตัวเลข "ชวนได้กี่คน" กลายเป็นจำนวนรอบโอน
     *
     * @param  array<int, array{affiliate: Affiliate, rows: Collection<int, GpuJobEarning>}>  $owed
     * @return int จำนวนค่าแนะนำที่สร้างรอบนี้
     */
    private function recordReferrals(array $owed): int
    {
        $created = 0;

        foreach ($owed as ['affiliate' => $affiliate, 'rows' => $group]) {
            $ownerId = (int) $group->first()->user_id;
            $shareSatang = (int) $group->sum('referral_satang');
            $revenueSatang = (int) $group->sum(fn (GpuJobEarning $row) => max(0, (int) $row->revenue_satang));
            $amount = self::baht($shareSatang);
            $sorted = $group->sortBy('id')->values();

            $firstFromThisOwner = ! AffiliateCommission::where('affiliate_id', $affiliate->id)
                ->where('source_type', self::COMMISSION_SOURCE)
                ->where('referred_user_id', $ownerId)
                ->exists();

            $commission = AffiliateCommission::create([
                'affiliate_id' => $affiliate->id,
                'order_id' => null,
                'referred_user_id' => $ownerId,
                'order_amount' => self::baht($revenueSatang),
                'commission_rate' => self::ratePercent($shareSatang, $revenueSatang),
                'commission_amount' => $amount,
                'status' => 'pending',
                'source_type' => self::COMMISSION_SOURCE,
                'source_id' => (int) $sorted->last()->id,
                'source_description' => $sorted->count() === 1
                    ? 'GPUxMINE ' . $sorted->first()->job_id
                    : sprintf('GPUxMINE %d งาน (%s – %s)', $sorted->count(), $sorted->first()->job_id, $sorted->last()->job_id),
            ]);

            $totals = ['total_earned' => $amount, 'total_pending' => $amount];
            if ($firstFromThisOwner) {
                $totals += ['total_referrals' => 1, 'total_conversions' => 1];
            }
            Affiliate::whereKey($affiliate->id)->toBase()->incrementEach($totals, ['updated_at' => now()]);

            GpuJobEarning::whereKey($group->modelKeys())->update(['affiliate_commission_id' => $commission->id]);
            $created++;
        }

        return $created;
    }

    /**
     * แถว cleared ที่โอนได้ตอนนี้ — ไม่รวมเครื่องที่ถูกระงับหลังเงินพ้นระยะพักแล้ว
     * และไม่รวมยอดติดลบที่ถูกแก้เป็น cleared ด้วยมือ (ผลรวมติดลบจะกลายเป็นการ "จ่าย"
     * โดยไม่มีรายการในกระเป๋า)
     */
    private function payable(): Builder
    {
        return $this->notFrozen(
            GpuJobEarning::query()
                ->where('status', GpuJobEarning::STATUS_CLEARED)
                ->where('amount_satang', '>=', 0)
        );
    }

    /**
     * ตัดแถวของเครื่องที่แอดมินระงับไว้ (รวมเครื่องที่ถอนไปแล้ว — soft delete ไม่ปลดการระงับ)
     *
     * จับทั้ง gpu_node_id และ worker_id: aixman อาจเขียนแถวโดยไม่มี gpu_node_id
     * gpuxmine:doctor ใช้ตัวเดียวกันนับเงินที่ค้าง — เงินของเครื่องที่ถูกระงับค้างโดยตั้งใจ
     */
    public function notFrozen(Builder $query): Builder
    {
        return $query->whereNotExists(function (QueryBuilder $q) {
            $q->selectRaw('1')
                ->from('gpu_nodes')
                ->whereNotNull('gpu_nodes.suspended_at')
                ->where(function (QueryBuilder $q) {
                    $q->whereColumn('gpu_nodes.id', 'gpu_job_earnings.gpu_node_id')
                        ->orWhereColumn('gpu_nodes.worker_id', 'gpu_job_earnings.worker_id');
                });
        });
    }

    /**
     * รายการไหนในชุดนี้ที่ถูกพักไว้เพราะเครื่องถูกระงับ — ตัวโอนจะข้ามจนกว่าจะยกเลิกระงับ
     *
     * หน้าแอดมินใช้บอกตรง ๆ ตอนอนุมัติ: เคยบอกว่า "เข้ากระเป๋ารอบถัดไป" ทั้งที่เงินค้างอยู่
     * คำถามเดียวต่อหน้า ไม่ใช่หนึ่งคำถามต่อแถว
     *
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    public function frozenAmong(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $free = $this->notFrozen(GpuJobEarning::query()->whereKey($ids))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_diff(array_map('intval', $ids), $free));
    }

    /** กระเป๋าของเจ้าของ — สร้างให้ถ้ายังไม่มี โดยไม่ชนกับใครที่สร้างพร้อมกัน */
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

    /** สตางค์ → บาททศนิยมสองตำแหน่งแบบสตริง ("1234.05") */
    public static function baht(int $satang): string
    {
        return (string) BigDecimal::ofUnscaledValue($satang, 2);
    }

    /** บาทจากคอลัมน์ decimal → สตางค์ ปัดครึ่งขึ้นที่ทศนิยมตำแหน่งที่สอง */
    public static function satang(?string $baht): int
    {
        if ($baht === null || $baht === '') {
            return 0;
        }

        return BigDecimal::of($baht)->toScale(2, RoundingMode::HALF_UP)->getUnscaledValue()->toInt();
    }

    /** อัตราส่วนแบ่งเป็นเปอร์เซ็นต์ของรายรับของงาน (commission_rate เก็บ 0.00–999.99) */
    private static function ratePercent(int $share, int $revenue): string
    {
        if ($revenue <= 0) {
            return '0.00';
        }

        $rate = BigDecimal::of($share)->multipliedBy(100)->dividedBy($revenue, 2, RoundingMode::HALF_UP);

        return (string) ($rate->isGreaterThan('999.99') ? BigDecimal::of('999.99') : $rate);
    }
}
