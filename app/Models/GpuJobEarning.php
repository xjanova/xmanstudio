<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * งานหนึ่งชิ้นที่เครื่องของคนแชร์ทำเสร็จ และเงินที่เขาควรได้จากมัน
 *
 * หนึ่งแถวคือหนึ่งงาน ไม่ใช่ยอดรวมรายวัน — เจ้าของเครื่องต้องชี้ได้ว่า
 * เงินก้อนนี้มาจากงานไหน เมื่อไร ใช้เวลาเท่าไร ไม่ใช่เห็นแค่ตัวเลขรวม
 * ที่ตรวจสอบย้อนกลับไม่ได้
 *
 * aixman เป็นคนเขียนแถว (ตรงเข้าฐานข้อมูลที่ใช้ร่วมกัน idempotent ด้วย job_id)
 * ส่วนการพักเงิน ปล่อยเงิน และโอนเข้ากระเป๋าเป็นของ gpuxmine:settle-earnings
 *
 *   pending ──(พ้นระยะพัก และเครื่องไม่ถูกระงับ)──▶ cleared ──(โอนเข้ากระเป๋า)──▶ paid
 *   (ระยะพักนับจากงานเสร็จหรือแถวถูกบันทึก แล้วแต่อันไหนช้ากว่า — ดู holdEndsAt())
 *   review  = ผลงานดูผิดปกติ รอแอดมินตัดสิน ไม่ถูกปล่อยเอง
 *   void    = ยกเลิก ไม่จ่าย
 *
 * amount_satang คือสิ่งที่เจ้าของเครื่องได้จริง (หลังหักส่วนแบ่งผู้แนะนำแล้ว) — ถ้าถึงวันโอน
 * ผู้แนะนำไม่ active แล้วและส่วนแบ่งถูกคืน ยอดนี้รวมส่วนที่คืนแล้ว (referral_unpaid_*)
 */
class GpuJobEarning extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_REVIEW = 'review';

    public const STATUS_CLEARED = 'cleared';

    public const STATUS_PAID = 'paid';

    public const STATUS_VOID = 'void';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_REVIEW,
        self::STATUS_CLEARED,
        self::STATUS_PAID,
        self::STATUS_VOID,
    ];

    /** เงินที่ยังไม่ถึงกระเป๋า แต่ยังเป็นของเจ้าของเครื่องอยู่ (ไม่รวม void) */
    public const UNPAID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_REVIEW,
        self::STATUS_CLEARED,
    ];

    /** รูปแบบ job_id ที่ aixman เขียน: 'aix-gpu-job-' + ai_gpu_jobs.id */
    public const JOB_ID_PREFIX = 'aix-gpu-job-';

    /**
     * ส่วนแบ่งผู้แนะนำที่ถึงวันโอนแล้วไม่มีใครรับได้ (referral_unpaid_to) — ดู
     * GpuxMineEarningSettlementService::unpaidReferralPolicy()
     *
     * owner: คืนเข้า amount_satang ของงานนี้แล้ว (referral_satang กลายเป็น 0)
     * platform: แพลตฟอร์มเก็บไว้ (referral_satang คงเดิม — ยังถูกหักจากเจ้าของ)
     */
    public const REFERRAL_UNPAID_TO_OWNER = 'owner';

    public const REFERRAL_UNPAID_TO_PLATFORM = 'platform';

    protected $fillable = [
        'gpu_node_id',
        'user_id',
        'worker_id',
        'job_id',
        'ai_gpu_job_id',
        'generation_id',
        'prompt_id',
        'kind',
        'model_key',
        'lane',
        'seconds',
        'amount_satang',
        'credits_charged',
        'thb_per_credit',
        'revenue_satang',
        'platform_fee_rate',
        'platform_fee_satang',
        'referral_satang',
        'referral_user_id',
        'referral_unpaid_satang',
        'referral_unpaid_to',
        'donated_value_satang',
        'free_share',
        'pro',
        'status',
        'review_reason',
        'wallet_transaction_id',
        'affiliate_commission_id',
        'completed_at',
        'cleared_at',
        'paid_at',
        'void_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'ai_gpu_job_id' => 'integer',
        'generation_id' => 'integer',
        'seconds' => 'integer',
        'amount_satang' => 'integer',
        'credits_charged' => 'integer',
        // เรตแปลงเครดิตเป็นบาทตอนคิดเงิน — เก็บเป็นทศนิยมตายตัว ไม่ใช่ float
        'thb_per_credit' => 'decimal:6',
        'revenue_satang' => 'integer',
        'platform_fee_rate' => 'decimal:4',
        'platform_fee_satang' => 'integer',
        'referral_satang' => 'integer',
        'referral_user_id' => 'integer',
        'referral_unpaid_satang' => 'integer',
        'donated_value_satang' => 'integer',
        'free_share' => 'boolean',
        'pro' => 'boolean',
        'wallet_transaction_id' => 'integer',
        'affiliate_commission_id' => 'integer',
        'completed_at' => 'datetime',
        'cleared_at' => 'datetime',
        'paid_at' => 'datetime',
        'reviewed_by' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(GpuNode::class, 'gpu_node_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** ผู้แนะนำของเจ้าของเครื่อง ณ ตอนที่ aixman คิดเงินงานนี้ */
    public function referralUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referral_user_id');
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    public function affiliateCommission(): BelongsTo
    {
        return $this->belongsTo(AffiliateCommission::class);
    }

    /** แอดมินที่อนุมัติหรือยกเลิกงานนี้ล่าสุด */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * ยกเลิกได้เฉพาะเงินที่ยังไม่ถึงกระเป๋า — แถวที่จ่ายแล้วมีรายการในกระเป๋า
     * ผูกอยู่ การยกเลิกตรงนี้จะทำให้ประวัติกับยอดเงินไม่ตรงกัน ต้องปรับกระเป๋า
     * แยกต่างหากแทน
     */
    public function canBeVoided(): bool
    {
        return in_array($this->status, self::UNPAID_STATUSES, true);
    }

    /** อนุมัติได้เฉพาะงานที่ติดรอตรวจ และยอดไม่ติดลบ (ยอดติดลบต้องยกเลิกเท่านั้น) */
    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_REVIEW && $this->amount_satang >= 0;
    }

    /**
     * เวลาที่งานนี้พ้นระยะพัก — นับจากเวลาที่ช้ากว่าระหว่างงานเสร็จกับแถวถูกบันทึก
     *
     * แถวที่ aixman เขียนย้อนหลัง (catch-up sweep) ต้องได้ระยะพักเต็มนับจากวันที่เงิน
     * ปรากฏในระบบ ไม่งั้นมันพ้นระยะพักตั้งแต่เกิด — ตรงกับ
     * GpuxMineEarningSettlementService::heldSince()
     */
    public function holdEndsAt(int $holdHours): ?Carbon
    {
        $from = match (true) {
            $this->completed_at === null => $this->created_at,
            $this->created_at === null => $this->completed_at,
            default => $this->created_at->gt($this->completed_at) ? $this->created_at : $this->completed_at,
        };

        return $from?->copy()->addHours(max(0, $holdHours));
    }

    /** ยอดเป็นบาท สำหรับแสดงผลเท่านั้น — การคำนวณทุกอย่างอยู่บนสตางค์ */
    public function amountBaht(): float
    {
        return $this->amount_satang / 100;
    }

    /** ชื่องานอย่างที่เจ้าของเครื่องเรียก ไม่ใช่คีย์ภายในระบบ */
    public function kindLabel(): string
    {
        return match ($this->kind) {
            'image' => 'สร้างภาพ',
            'video' => 'สร้างวิดีโอ',
            'audio' => 'สร้างเพลง',
            'upscale' => 'ขยายภาพ',
            'embed' => 'ประมวลผลข้อความ',
            default => $this->kind,
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'เข้ากระเป๋าแล้ว',
            self::STATUS_CLEARED => 'รอโอนเข้ากระเป๋า',
            self::STATUS_PENDING => 'อยู่ในระยะพัก',
            self::STATUS_REVIEW => 'รอตรวจสอบ',
            self::STATUS_VOID => 'ยกเลิก',
            default => $this->status,
        };
    }

    /**
     * บันทึกงานที่เสร็จแล้ว โดยไม่ยอมให้งานเดิมถูกบันทึกซ้ำ
     *
     * ตัวที่ยิงผลงานมาอาจ retry ได้ทุกเมื่อ และการนับงานเดิมสองครั้งคือการ
     * จ่ายเงินสองครั้ง `job_id` เป็น unique อยู่แล้วในฐานข้อมูล ตรงนี้แค่
     * ทำให้การเรียกซ้ำกลายเป็นเรื่องปกติแทนที่จะเป็น exception
     *
     * createOrFirst ไม่ใช่ firstOrCreate: สองตัวเขียนที่มาพร้อมกันเห็น "ยังไม่มี"
     * ทั้งคู่ firstOrCreate จะให้ตัวที่ช้ากว่าชน unique แล้วโยน exception ส่วน
     * createOrFirst ลองเขียนก่อน ชนเมื่อไรก็อ่านแถวของอีกตัวกลับมา แถวที่มีอยู่แล้ว
     * ไม่ถูกเขียนทับ — เงินที่เดินหน้าไปแล้ว (cleared/paid) ต้องไม่ถอยกลับเป็น pending
     */
    public static function record(array $attributes): self
    {
        return static::createOrFirst(
            ['job_id' => $attributes['job_id']],
            $attributes
        );
    }
}
