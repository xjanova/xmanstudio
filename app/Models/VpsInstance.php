<?php

namespace App\Models;

use App\Support\VpsPricing;
use Illuminate\Database\Eloquent\Model;

/**
 * A server a customer rents from us, from the moment their money moves until
 * the rental ends.
 */
class VpsInstance extends Model
{
    /** Money taken, the supplier not called yet (or the call died on the way). */
    public const STATUS_PENDING = 'pending';

    /** Bought upstream, being built — or waiting for upstream's payment to clear. */
    public const STATUS_PROVISIONING = 'provisioning';

    public const STATUS_ACTIVE = 'active';

    /** Past its paid-for period. Upstream suspends it, and then deletes it. */
    public const STATUS_EXPIRED = 'expired';

    /** Bought upstream but could not be built — waiting for a person. */
    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    /** @var array<int,string> */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROVISIONING,
        self::STATUS_ACTIVE,
        self::STATUS_EXPIRED,
        self::STATUS_FAILED,
        self::STATUS_REFUNDED,
    ];

    protected $fillable = [
        'user_id',
        'vps_plan_id',
        'plan_name',
        'specs',
        'period',
        'months',
        'status',
        'hostname',
        'template_id',
        'template_name',
        'data_center_id',
        'data_center_name',
        'remote_vm_id',
        'remote_order_id',
        'remote_subscription_id',
        'remote_firewall_id',
        'state',
        'ipv4',
        'ipv6',
        'auto_renew',
        'expires_at',
        'activated_at',
        'renewal_notice_sent_at',
        'reminders_sent',
        'needs_password_reset',
        'last_polled_at',
        'poll_attempts',
        'setup_attempts',
        'last_error',
    ];

    protected $casts = [
        'specs' => 'array',
        'months' => 'integer',
        'template_id' => 'integer',
        'data_center_id' => 'integer',
        'remote_vm_id' => 'integer',
        'remote_firewall_id' => 'integer',
        'auto_renew' => 'boolean',
        'expires_at' => 'datetime',
        'activated_at' => 'datetime',
        'renewal_notice_sent_at' => 'datetime',
        'reminders_sent' => 'array',
        'needs_password_reset' => 'boolean',
        'last_polled_at' => 'datetime',
        'poll_attempts' => 'integer',
        'setup_attempts' => 'integer',
    ];

    /** Can quote the supplier word for word. Never leaves the server. */
    protected $hidden = ['last_error'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(VpsPlan::class, 'vps_plan_id');
    }

    public function payments()
    {
        return $this->hasMany(VpsPayment::class)->orderByDesc('created_at');
    }

    public function orderPayment()
    {
        return $this->hasOne(VpsPayment::class)->where('kind', VpsPayment::KIND_ORDER);
    }

    /** Orders the reconciliation job still has to finish. */
    public function scopeUnsettled($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PROVISIONING]);
    }

    /** What the customer's list shows: everything except orders that never happened. */
    public function scopeVisible($query)
    {
        return $query->where('status', '!=', self::STATUS_REFUNDED);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Can the customer press power, password and snapshot buttons?
     *
     * Only on a live rental with a machine behind it. An expired one is
     * suspended upstream, and the buttons would fail with an error that
     * names the supplier.
     */
    public function isManageable(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->remote_vm_id !== null;
    }

    /**
     * Can another period be bought now?
     *
     * An expired rental is still renewable — that is the point of telling the
     * customer before the supplier deletes it. A renewal already in flight is
     * not repeated: pressing the button twice must not buy two months.
     */
    public function canRenew(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_EXPIRED], true)
            && $this->remote_subscription_id !== null
            && ! $this->payments()
                ->where('kind', VpsPayment::KIND_RENEW)
                ->where('status', VpsPayment::STATUS_PENDING)
                ->exists();
    }

    public function isSettled(): bool
    {
        return ! in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROVISIONING], true);
    }

    public function daysUntilExpiry(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expires_at->copy()->startOfDay(), false);
    }

    public function isExpiringSoon(int $withinDays = 7): bool
    {
        $days = $this->daysUntilExpiry();

        return $days !== null && $days >= 0 && $days <= $withinDays;
    }

    public function periodLabel(string $lang = 'th'): string
    {
        return VpsPricing::periodLabel((string) $this->period, $lang);
    }

    public function spec(string $key): int
    {
        return (int) (($this->specs ?? [])[$key] ?? 0);
    }

    /**
     * Customer-facing label for where the rental stands. Complete Tailwind
     * class strings, for the reason on DomainRegistration::statusBadge().
     *
     * @return array{label_th:string,label_en:string,classes:string}
     */
    public function statusBadge(): array
    {
        return match ($this->status) {
            self::STATUS_PENDING,
            self::STATUS_PROVISIONING => [
                'label_th' => 'กำลังติดตั้ง',
                'label_en' => 'Setting up',
                'classes' => 'bg-amber-100 dark:bg-amber-500/20 text-amber-800 dark:text-amber-300',
            ],
            self::STATUS_ACTIVE => [
                'label_th' => 'ใช้งานอยู่',
                'label_en' => 'Active',
                'classes' => 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-800 dark:text-emerald-300',
            ],
            self::STATUS_EXPIRED => [
                'label_th' => 'หมดอายุ',
                'label_en' => 'Expired',
                'classes' => 'bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300',
            ],
            self::STATUS_FAILED => [
                'label_th' => 'รอทีมงานตรวจสอบ',
                'label_en' => 'Needs attention',
                'classes' => 'bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300',
            ],
            self::STATUS_REFUNDED => [
                'label_th' => 'คืนเงินแล้ว',
                'label_en' => 'Refunded',
                'classes' => 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300',
            ],
            default => [
                'label_th' => 'ไม่ทราบสถานะ',
                'label_en' => 'Unknown',
                'classes' => 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300',
            ],
        };
    }

    /**
     * The machine's power state, in words.
     *
     * @return array{th:string,en:string,dot:string}
     */
    public function stateLabel(): array
    {
        return match ($this->state) {
            'running' => ['th' => 'ทำงานอยู่', 'en' => 'Running', 'dot' => 'bg-emerald-400'],
            'stopped' => ['th' => 'ปิดอยู่', 'en' => 'Stopped', 'dot' => 'bg-slate-400'],
            'starting', 'unsuspending' => ['th' => 'กำลังเปิด', 'en' => 'Starting', 'dot' => 'bg-amber-400'],
            'stopping', 'suspending' => ['th' => 'กำลังปิด', 'en' => 'Stopping', 'dot' => 'bg-amber-400'],
            'creating', 'initial' => ['th' => 'กำลังสร้าง', 'en' => 'Creating', 'dot' => 'bg-amber-400'],
            'recreating' => ['th' => 'กำลังติดตั้ง OS ใหม่', 'en' => 'Reinstalling', 'dot' => 'bg-amber-400'],
            'restoring' => ['th' => 'กำลังกู้คืน', 'en' => 'Restoring', 'dot' => 'bg-amber-400'],
            'recovery', 'stopping_recovery' => ['th' => 'โหมดกู้ระบบ', 'en' => 'Recovery mode', 'dot' => 'bg-violet-400'],
            'suspended' => ['th' => 'ถูกระงับ', 'en' => 'Suspended', 'dot' => 'bg-red-400'],
            'error' => ['th' => 'มีปัญหา', 'en' => 'Error', 'dot' => 'bg-red-400'],
            default => ['th' => 'ไม่ทราบ', 'en' => 'Unknown', 'dot' => 'bg-slate-400'],
        };
    }

    /** True while upstream is in the middle of something and more buttons would only queue up. */
    public function isBusy(): bool
    {
        return in_array($this->state, ['starting', 'stopping', 'creating', 'initial', 'recreating', 'restoring', 'suspending', 'unsuspending', 'stopping_recovery'], true);
    }
}
