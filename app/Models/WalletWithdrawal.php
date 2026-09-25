<?php

namespace App\Models;

use App\Support\Satang;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * คำขอถอนเงินสดออกจากกระเป๋า XMAN หนึ่งใบ (R2-4)
 *
 *   pending ──(แอดมินกด "เริ่มโอน")──▶ processing ──(โอนในแอปธนาคารแล้ว + เลขอ้างอิง/สลิป)──▶ approved
 *      │                                  └──(แอดมินปฏิเสธ + เหตุผล: คืนยอดที่พัก)──────────▶ rejected
 *      │──(แอดมินปฏิเสธ + เหตุผล: คืนยอดที่พักเข้ากระเป๋า)──────────────────────────────────▶ rejected
 *      └──(ผู้ใช้ยกเลิกเอง: คืนยอดที่พักเข้ากระเป๋า)──────────────────────────────────────▶ cancelled
 *
 * processing มีไว้กันจ่ายสองทาง: แอดมินโอนเงินจริงก่อนแล้วค่อยกลับมาบันทึก ถ้าผู้ใช้ยังยกเลิกได้
 * ในช่วงนั้น ยอดที่พักจะคืนเข้ากระเป๋าทั้งที่เงินสดออกไปแล้ว ผู้ใช้ยกเลิกได้เฉพาะ pending
 * และบันทึกว่าโอนแล้วได้เฉพาะ processing — เลขบัญชีเต็มขึ้นให้แอดมินเห็นหลังกดเริ่มโอนเท่านั้น
 *
 * ยอดถูกพักตั้งแต่ตอนขอ: wallets.balance ถูกหักด้วยรายการ withdrawal (pending) ทันที
 * เงินที่รอถอนจึงใช้จ่ายไม่ได้ — ทั้งในเว็บนี้และที่ aixman ซึ่งตัดเงินจาก balance ตรง ๆ
 * การเปลี่ยนสถานะทั้งหมดอยู่ใน WalletWithdrawalService (UPDATE แบบมีเงื่อนไข ชนะได้คนเดียว)
 */
class WalletWithdrawal extends Model
{
    public const STATUS_PENDING = 'pending';

    /** แอดมินรับเรื่องและกำลังโอน — ยอดยังพักอยู่ ผู้ใช้ยกเลิกไม่ได้แล้ว */
    public const STATUS_PROCESSING = 'processing';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    /** ยังเปิดอยู่ = ยอดยังถูกพัก และ open_user_id ยังกันคำขอใหม่ของคนเดียวกัน */
    public const OPEN_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
    ];

    public const METHOD_BANK = 'bank';

    public const METHOD_PROMPTPAY = 'promptpay';

    /** บัญชีปลายทางมาจากไหน — KYC ที่ยืนยันชื่อแล้ว หรือผู้ใช้กรอกเอง (KYC ไม่มีบัญชี) */
    public const SOURCE_KYC = 'kyc';

    public const SOURCE_FORM = 'form';

    /** อ้างอิงของรายการในกระเป๋า — reference_type ของรายการพักยอดและคืนยอด */
    public const REFERENCE_TYPE = 'wallet_withdrawals';

    /**
     * ไม่มี status / open_user_id / *_transaction_id / reviewed_* ในนี้โดยตั้งใจ —
     * สถานะเปลี่ยนได้ทางเดียวคือ UPDATE แบบมีเงื่อนไขใน WalletWithdrawalService
     */
    protected $fillable = [
        'withdrawal_id',
        'user_id',
        'wallet_id',
        'amount',
        'idempotency_key',
        'method',
        'account_source',
        'bank_code',
        'bank_name',
        'account_number',
        'account_name',
        'promptpay_id',
        'request_ip',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'account_number' => 'encrypted',
        'promptpay_id' => 'encrypted',
        'reviewed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'processing_at' => 'datetime',
        'open_user_id' => 'integer',
        'hold_transaction_id' => 'integer',
        'return_transaction_id' => 'integer',
        'reviewed_by' => 'integer',
        'processing_by' => 'integer',
    ];

    /** เลขบัญชี/พร้อมเพย์เต็ม ๆ ห้ามหลุดออก JSON โดยไม่ตั้งใจ */
    protected $hidden = [
        'account_number',
        'promptpay_id',
        'slip_path',
        'idempotency_key',
        'request_ip',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** แอดมินที่กด "เริ่มโอน" */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processing_by');
    }

    public function holdTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'hold_transaction_id');
    }

    public function returnTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'return_transaction_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /** ยอดยังถูกพักอยู่ (รอแอดมิน หรือแอดมินกำลังโอน) */
    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'รอแอดมินอนุมัติ',
            self::STATUS_PROCESSING => 'แอดมินกำลังโอน',
            self::STATUS_APPROVED => 'โอนเข้าบัญชีแล้ว',
            self::STATUS_REJECTED => 'ไม่อนุมัติ · คืนเข้ากระเป๋าแล้ว',
            self::STATUS_CANCELLED => 'ยกเลิกแล้ว · คืนเข้ากระเป๋าแล้ว',
            default => (string) $this->status,
        };
    }

    public function methodLabel(): string
    {
        return $this->method === self::METHOD_PROMPTPAY ? 'พร้อมเพย์' : 'บัญชีธนาคาร';
    }

    /** ปลายทางแบบปิดเลขกลาง — ใช้ทุกที่ที่ไม่ใช่หน้าที่แอดมินต้องโอนเงินจริง (แจ้งเตือน, ประวัติ, log) */
    public function maskedDestination(): string
    {
        $number = $this->method === self::METHOD_PROMPTPAY ? $this->promptpay_id : $this->account_number;
        $digits = preg_replace('/\D/', '', (string) $number) ?? '';
        $masked = strlen($digits) > 4 ? str_repeat('x', 3) . '-' . substr($digits, -4) : ($digits !== '' ? $digits : '—');

        return $this->method === self::METHOD_PROMPTPAY
            ? 'พร้อมเพย์ ' . $masked
            : trim(($this->bank_name ?: $this->bank_code ?: 'ธนาคาร') . ' ' . $masked);
    }

    /** ยอดเป็นสตางค์ — การคำนวณทุกอย่างอยู่บนสตางค์ ไม่ผ่าน float */
    public function amountSatang(): int
    {
        return Satang::fromBaht($this->amount === null ? null : (string) $this->amount);
    }
}
