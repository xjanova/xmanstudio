<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * งานหนึ่งชิ้นที่เครื่องของคนแชร์ทำเสร็จ และเงินที่เขาควรได้จากมัน
 *
 * หนึ่งแถวคือหนึ่งงาน ไม่ใช่ยอดรวมรายวัน — เจ้าของเครื่องต้องชี้ได้ว่า
 * เงินก้อนนี้มาจากงานไหน เมื่อไร ใช้เวลาเท่าไร ไม่ใช่เห็นแค่ตัวเลขรวม
 * ที่ตรวจสอบย้อนกลับไม่ได้
 */
class GpuJobEarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'gpu_node_id',
        'user_id',
        'worker_id',
        'job_id',
        'kind',
        'model_key',
        'lane',
        'seconds',
        'amount_satang',
        'status',
        'wallet_transaction_id',
        'completed_at',
    ];

    protected $casts = [
        'seconds' => 'integer',
        'amount_satang' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(GpuNode::class, 'gpu_node_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
            'paid' => 'เข้ากระเป๋าแล้ว',
            'pending' => 'รอเข้ากระเป๋า',
            'void' => 'ยกเลิก',
            default => $this->status,
        };
    }

    /**
     * บันทึกงานที่เสร็จแล้ว โดยไม่ยอมให้งานเดิมถูกบันทึกซ้ำ
     *
     * ตัวที่ยิงผลงานมาอาจ retry ได้ทุกเมื่อ และการนับงานเดิมสองครั้งคือการ
     * จ่ายเงินสองครั้ง `job_id` เป็น unique อยู่แล้วในฐานข้อมูล ตรงนี้แค่
     * ทำให้การเรียกซ้ำกลายเป็นเรื่องปกติแทนที่จะเป็น exception
     */
    public static function record(array $attributes): self
    {
        return static::firstOrCreate(
            ['job_id' => $attributes['job_id']],
            $attributes
        );
    }
}
