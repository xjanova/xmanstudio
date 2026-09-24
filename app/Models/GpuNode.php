<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * เครื่องหนึ่งเครื่องที่เจ้าของนำมาแชร์ใน GPUxMINE
 *
 * ใครเป็นเจ้าของเครื่องไหน อยู่ที่นี่ที่เดียว relay เก็บแค่ worker id กับ
 * token hash และ aixman เก็บแค่ปลายทางที่จะส่งงาน — ไม่มีใครในสองที่นั้น
 * รู้ว่าเงินต้องเข้ากระเป๋าใบไหน
 */
class GpuNode extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * อายุรหัสจับคู่
     *
     * สิบนาทีพอสำหรับคนที่เปิดเว็บแล้วหันไปพิมพ์ในโปรแกรม และสั้นพอที่รหัส
     * ซึ่งหลุดไปอยู่ในประวัติแชทหรือภาพหน้าจอจะใช้ไม่ได้อีกแล้ว — รหัสนี้
     * สร้าง worker ใหม่ในนามบัญชีนี้ได้ จึงต้องถือว่ามันคือความลับ
     */
    public const PAIRING_TTL_MINUTES = 10;

    /**
     * การถอน worker ที่ aixman และ relay — ยังไม่ยืนยันทั้งสองฝั่ง
     *
     * แถวที่ soft delete แล้วแต่ยังค้างสถานะนี้ gpuxmine:sync-nodes จะลองถอน
     * ให้ใหม่ทุกรอบ จนทั้งสองฝั่งตอบว่าเอาออกแล้ว
     */
    public const RETIRE_PENDING = 'pending';

    public const RETIRE_DONE = 'done';

    protected $fillable = [
        'user_id',
        'referrer_user_id',
        'product_device_id',
        'pairing_code',
        'pairing_expires_at',
        'paired_at',
        'worker_id',
        'relay_token',
        'tunnel_token',
        'relay_url',
        'tunnel_endpoint',
        'label',
        'machine_id',
        'online',
        'agent_version',
        'gpu_name',
        'vram_total_mb',
        'score',
        'tier',
        'assessed',
        'accepting',
        'busy',
        'can_run',
        'lanes',
        'provisional',
        'free_share_pct',
        'last_seen_at',
        'dispatch_synced_at',
        'dispatch_status',
        'dispatch_note',
        'dispatch_fingerprint',
        'dispatch_worker_status',
        'dispatch_last_error',
        'suspended_at',
        'suspended_reason',
        'suspended_by',
        'retire_status',
    ];

    protected $casts = [
        'pairing_expires_at' => 'datetime',
        'paired_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'dispatch_synced_at' => 'datetime',
        'suspended_at' => 'datetime',
        'online' => 'boolean',
        'assessed' => 'boolean',
        // null = เครื่องไม่ได้บอก (ไคลเอนต์รุ่นเก่า หรือออฟไลน์อยู่) ไม่ใช่ "ไม่รับ"
        'accepting' => 'boolean',
        'busy' => 'boolean',
        'can_run' => 'array',
        // เร็วพอให้คนนั่งรอไหม — คนละคำถามกับ can_run ที่ตอบแค่ว่าทำได้ไหม
        'lanes' => 'array',
        'provisional' => 'array',
        // กุญแจเปิดอุโมงค์ไปเครื่องในบ้านคน — ห้ามอยู่ในฐานข้อมูลแบบอ่านได้
        'relay_token' => 'encrypted',
        // กุญแจฝั่ง aixman (/w/) ที่ relay รุ่นใหม่ออกแยกจากกุญแจของเครื่อง
        'tunnel_token' => 'encrypted',
    ];

    protected $hidden = ['relay_token', 'tunnel_token'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** ผู้แนะนำของเจ้าของเครื่อง — จับไว้ครั้งเดียวตอนจับคู่ (D8) */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    /**
     * กุญแจที่ aixman ต้องใช้เปิดอุโมงค์ /w/ ของเครื่องนี้
     *
     * relay รุ่นใหม่ออกกุญแจแยกให้ aixman — เครื่องถือ relay_token ไว้ต่อ
     * /agent อย่างเดียว ส่วนเครื่องที่ลงทะเบียนกับ relay รุ่นเก่ามีกุญแจใบเดียว
     * ใช้ทั้งสองทาง
     */
    public function dispatchToken(): ?string
    {
        return $this->tunnel_token ?: $this->relay_token;
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /** งานที่เครื่องนี้ทำเสร็จและเงินที่ได้จากมัน */
    public function earnings(): HasMany
    {
        return $this->hasMany(GpuJobEarning::class, 'gpu_node_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(ProductDevice::class, 'product_device_id');
    }

    /**
     * รหัสจับคู่ที่อ่านออกเสียงแล้วไม่กำกวม
     *
     * ตัดตัวที่คนอ่านผิดบ่อยออกหมด (0/O, 1/I/L) เพราะคนต้องอ่านจากจอนึง
     * ไปพิมพ์ในอีกจอนึง ตัวอักษรที่สลับกันได้คือสายซัพพอร์ตที่ไม่ควรมี
     */
    public static function newPairingCode(): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $code = substr($code, 0, 4) . '-' . substr($code, 4);
        } while (self::where('pairing_code', $code)->exists());

        return $code;
    }

    public function pairingIsUsable(): bool
    {
        return $this->paired_at === null
            && $this->pairing_code !== null
            && $this->pairing_expires_at !== null
            && $this->pairing_expires_at->isFuture();
    }

    /** ชื่อที่เอาไว้โชว์ ก่อนเครื่องจะรายงานอะไรมาเลยก็ต้องมีชื่อ */
    public function displayName(): string
    {
        return $this->label
            ?: $this->gpu_name
            ?: ($this->worker_id ? 'เครื่อง ' . Str::after($this->worker_id, 'gxm-') : 'เครื่องใหม่');
    }

    /**
     * สถานะที่เจ้าของเข้าใจได้ในคำเดียว
     *
     * เรียงตามลำดับที่เจ้าของต้องแก้: ยังไม่จับคู่ → ยังไม่เปิด → ยังไม่ประเมิน
     * → ประเมินแล้วแต่ยังไม่มีงานที่รับได้ → พร้อมรับงาน
     */
    public function statusLabel(): string
    {
        if ($this->paired_at === null) {
            return 'รอจับคู่';
        }
        if (! $this->online) {
            return 'ออฟไลน์';
        }
        if (! $this->assessed) {
            return 'กำลังประเมินเครื่อง';
        }
        if ($this->dispatch_status === 'eligible') {
            return 'พร้อมรับงาน';
        }

        return 'ออนไลน์ • ยังไม่ได้รับงาน';
    }

    public function statusTone(): string
    {
        return match ($this->statusLabel()) {
            'พร้อมรับงาน' => 'text-green-700 bg-green-50 border-green-200',
            'ออฟไลน์' => 'text-gray-600 bg-gray-50 border-gray-200',
            'รอจับคู่' => 'text-blue-700 bg-blue-50 border-blue-200',
            default => 'text-amber-700 bg-amber-50 border-amber-200',
        };
    }

    public function scopePaired($query)
    {
        return $query->whereNotNull('paired_at');
    }

    public function scopeOnline($query)
    {
        return $query->where('online', true);
    }
}
