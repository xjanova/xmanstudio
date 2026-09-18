<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * คำขอยืนยันตัวตนหนึ่งใบ — หนึ่งแถวต่อหนึ่งผู้ใช้
 *
 * ถูกปฏิเสธแล้วส่งใหม่ได้: แถวเดิมถูกเขียนทับและเหตุผลที่เคยถูกปฏิเสธถูกผลักลง
 * `history` ผู้ตรวจคนถัดไปจึงเห็นว่าคนนี้เคยพลาดอะไรมาแล้วบ้าง
 */
class KycVerification extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /** สถานะที่ผู้ใช้ยังไม่เคยส่งอะไรเข้ามา — เก็บบน users เท่านั้น ไม่มีแถวที่นี่ */
    public const USER_STATUS_NOT_SUBMITTED = 'not_submitted';

    protected $fillable = [
        'user_id',
        'status',
        'id_card_front_path',
        'id_card_back_path',
        'selfie_path',
        'bank_book_path',
        'id_card_number_hash',
        'id_card_number_last4',
        'full_name_th',
        'full_name_en',
        'birth_date',
        'bank_code',
        'bank_account_number',
        'bank_account_name',
        'extracted_data',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'submitted_at',
        'attempts',
        'history',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'extracted_data' => 'array',
        'history' => 'array',
        'reviewed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'attempts' => 'integer',
    ];

    /**
     * ไม่ให้หลุดออก API/JSON โดยไม่ตั้งใจ — path ของเอกสารและแฮชเลขบัตร
     * ไม่ใช่ของที่ client ต้องเห็น
     */
    protected $hidden = [
        'id_card_front_path',
        'id_card_back_path',
        'selfie_path',
        'bank_book_path',
        'id_card_number_hash',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /** ส่งใหม่ได้เมื่อยังไม่เคยส่ง หรือเคยถูกปฏิเสธ — ระหว่างรอตรวจห้ามส่งซ้ำ */
    public function canResubmit(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'รอตรวจสอบ',
            self::STATUS_APPROVED => 'ยืนยันตัวตนแล้ว',
            self::STATUS_REJECTED => 'ไม่ผ่าน — ส่งใหม่ได้',
            default => 'ยังไม่ได้ส่ง',
        };
    }

    /**
     * แฮชเลขบัตรแบบที่เทียบกันได้ข้ามแถว
     *
     * ใช้ HMAC กับ APP_KEY ไม่ใช่ sha256 เปล่า ๆ — เลขบัตรประชาชนมีแค่ 13 หลัก
     * ถ้าใช้แฮชธรรมดา คนที่ได้ฐานข้อมูลไปไล่สร้างตารางทุกเลขที่เป็นไปได้จบใน
     * ไม่กี่ชั่วโมง การผูกกับคีย์ของแอปทำให้ทำแบบนั้นไม่ได้ถ้าไม่มีคีย์ด้วย
     */
    public static function hashIdCardNumber(string $idCardNumber): string
    {
        $digits = preg_replace('/\D/', '', $idCardNumber) ?? '';

        return hash_hmac('sha256', $digits, config('app.key'));
    }

    /**
     * ตรวจเลขบัตรประชาชนไทยด้วยหลักตรวจสอบของตัวมันเอง
     *
     * หลักที่ 13 คำนวณจาก 12 หลักแรก เลขที่พิมพ์ผิดหรือมั่วขึ้นมาลอย ๆ จึงตกที่นี่
     * ก่อนจะไปถึงผู้ตรวจ — ไม่ได้พิสูจน์ว่าบัตรมีจริง แต่คัดขยะออกได้ฟรี
     */
    public static function isValidThaiIdCardNumber(string $idCardNumber): bool
    {
        $digits = preg_replace('/\D/', '', $idCardNumber) ?? '';

        if (strlen($digits) !== 13) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += ((int) $digits[$i]) * (13 - $i);
        }

        return ((11 - ($sum % 11)) % 10) === (int) $digits[12];
    }
}
