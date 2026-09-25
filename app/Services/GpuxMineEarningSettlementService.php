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
 *  1. ปล่อยเงิน (clearMatured): แถว pending ที่งานเสร็จมาเกินระยะพักแล้ว และเครื่อง
 *     ไม่ได้ถูกแอดมินระงับ → cleared ระยะพักคือช่วงที่ยังจับเครื่องที่ส่งผลงานปลอม
 *     ทันก่อนเงินออก แถว review ไม่ถูกแตะ — รอแอดมิน
 *
 *  2. จ่าย (payUser): ต่อเจ้าของหนึ่งคน ในทรานแซกชันเดียว ล็อกกระเป๋าแล้วล็อกแถว
 *     cleared ของเขา บวกเงินแบบ atomic เขียน wallet_transactions หนึ่งแถวต่อชุด
 *     แล้วเปลี่ยนแถวเป็น paid พร้อมเลขรายการ — ขั้นไหนพลาด ย้อนทั้งชุด
 *     ส่วนแบ่งผู้แนะนำ (referral_satang) กลายเป็น AffiliateCommission ในทรานแซกชัน
 *     เดียวกัน รอแอดมินอนุมัติแบบเดียวกับค่าแนะนำจากทุกช่องทาง
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
            GpuJobEarning::query()
                ->where('status', GpuJobEarning::STATUS_PENDING)
                ->whereNotNull('user_id')
                ->where(function (Builder $q) use ($cutoff) {
                    $q->where('completed_at', '<=', $cutoff)
                        // aixman ควรใส่ completed_at เสมอ — ถ้าไม่มี นับจากเวลาที่แถวเกิด
                        // ดีกว่าปล่อยให้ค้างเป็น pending ไปตลอด
                        ->orWhere(fn (Builder $q) => $q->whereNull('completed_at')->where('created_at', '<=', $cutoff));
                })
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
        $satang = (int) $rows->sum('amount_satang');

        // งานแชร์ฟรีได้ 0 — ปิดแถวเป็น paid ได้เลย ไม่ต้องมีรายการ 0 บาทในกระเป๋า
        $transaction = $satang > 0 ? $this->credit($wallet, $satang, $ids) : null;

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

        $commissions = 0;
        foreach ($rows as $row) {
            if ($this->recordReferral($row)) {
                $commissions++;
            }
        }

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
     * @param  array<int, int>  $ids
     */
    private function credit(Wallet $wallet, int $satang, array $ids): WalletTransaction
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
            'description' => 'รายได้จากการแชร์การ์ดจอ GPUxMINE · ' . count($ids) . ' งาน',
            'status' => WalletTransaction::STATUS_COMPLETED,
            'metadata' => [
                'source' => 'gpuxmine',
                'jobs' => count($ids),
                'amount_satang' => $satang,
                'earning_ids' => $ids,
            ],
        ]);
    }

    /**
     * ส่วนแบ่งผู้แนะนำของงานหนึ่งชิ้น → AffiliateCommission (pending รอแอดมินอนุมัติ)
     *
     * หนึ่งงานได้ค่าแนะนำหนึ่งรายการเท่านั้น: เช็กทั้ง affiliate_commission_id บนแถว
     * (ซึ่งล็อกอยู่) และรายการเดิมที่ source_type/source_id ตรงกัน ผู้แนะนำที่ถูกระงับ
     * หรือไม่มีบัญชี affiliate แล้ว ไม่ได้ — ส่วนนั้นไม่ถูกจ่ายให้ใคร
     *
     * ยอดสะสมของ affiliate ขยับแบบเดียวกับ AffiliateCommissionService (total_earned,
     * total_pending) แต่ total_referrals/total_conversions นับเจ้าของเครื่องหนึ่งคน
     * ครั้งเดียว ไม่ใช่ทุกงาน — ไม่งั้นตัวเลข "ชวนได้กี่คน" กลายเป็นจำนวนภาพที่เรนเดอร์
     */
    private function recordReferral(GpuJobEarning $row): bool
    {
        if ($row->referral_satang <= 0 || $row->referral_user_id === null || $row->affiliate_commission_id !== null) {
            return false;
        }

        // ผู้แนะนำคือเจ้าของเครื่องเอง = ข้อมูลผิด ไม่จ่าย
        if ($row->referral_user_id === (int) $row->user_id) {
            return false;
        }

        $existing = AffiliateCommission::where('source_type', self::COMMISSION_SOURCE)
            ->where('source_id', $row->id)
            ->value('id');

        if ($existing !== null) {
            GpuJobEarning::whereKey($row->id)->update(['affiliate_commission_id' => $existing]);

            return false;
        }

        $affiliate = Affiliate::where('user_id', $row->referral_user_id)->first();
        if ($affiliate === null || ! $affiliate->isActive()) {
            Log::info('[GPUxMINE] referral share not paid: referrer has no active affiliate', [
                'earning_id' => $row->id,
                'referral_user_id' => $row->referral_user_id,
            ]);

            return false;
        }

        $amount = self::baht($row->referral_satang);

        $firstFromThisOwner = ! AffiliateCommission::where('affiliate_id', $affiliate->id)
            ->where('source_type', self::COMMISSION_SOURCE)
            ->where('referred_user_id', $row->user_id)
            ->exists();

        $commission = AffiliateCommission::create([
            'affiliate_id' => $affiliate->id,
            'order_id' => null,
            'referred_user_id' => $row->user_id,
            'order_amount' => self::baht(max(0, $row->revenue_satang)),
            'commission_rate' => self::ratePercent($row->referral_satang, $row->revenue_satang),
            'commission_amount' => $amount,
            'status' => 'pending',
            'source_type' => self::COMMISSION_SOURCE,
            'source_id' => $row->id,
            'source_description' => 'GPUxMINE ' . $row->job_id,
        ]);

        $totals = ['total_earned' => $amount, 'total_pending' => $amount];
        if ($firstFromThisOwner) {
            $totals += ['total_referrals' => 1, 'total_conversions' => 1];
        }
        Affiliate::whereKey($affiliate->id)->toBase()->incrementEach($totals, ['updated_at' => now()]);

        GpuJobEarning::whereKey($row->id)->update(['affiliate_commission_id' => $commission->id]);

        return true;
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
