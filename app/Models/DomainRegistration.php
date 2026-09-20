<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A domain purchase: one row per register or renew, from the moment the
 * customer's money moves until the domain is live or the money is back.
 */
class DomainRegistration extends Model
{
    use HasFactory;

    /** Money taken, nothing sent upstream yet. */
    public const STATUS_PENDING = 'pending';

    /** Upstream accepted but has not finished — being polled. */
    public const STATUS_REGISTERING = 'registering';

    public const STATUS_ACTIVE = 'active';

    /** Upstream refused or timed out; money has been or is being returned. */
    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_EXPIRED = 'expired';

    /** Customer moved the domain to another registrar. */
    public const STATUS_TRANSFERRED_OUT = 'transferred_out';

    /**
     * The whitelist lives here rather than in the column, so adding a status
     * costs no migration and cannot 500 on MySQL while passing on SQLite.
     *
     * @var array<int,string>
     */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_REGISTERING,
        self::STATUS_ACTIVE,
        self::STATUS_FAILED,
        self::STATUS_REFUNDED,
        self::STATUS_EXPIRED,
        self::STATUS_TRANSFERRED_OUT,
    ];

    public const KIND_REGISTER = 'register';

    public const KIND_RENEW = 'renew';

    protected $fillable = [
        'user_id',
        'domain',
        'tld',
        'status',
        'kind',
        'renewal_of',
        'domain_contact_id',
        'remote_order_id',
        'remote_subscription_id',
        'price_thb',
        'cost_usd_cents',
        'cost_currency',
        'fx_rate',
        'years',
        'wallet_transaction_id',
        'refund_transaction_id',
        'idempotency_key',
        'privacy_protection',
        'auto_renew',
        'nameservers',
        'registered_at',
        'expires_at',
        'renewal_notice_sent_at',
        'expiry_reminders_sent',
        'last_polled_at',
        'poll_attempts',
        'last_error',
    ];

    protected $casts = [
        'price_thb' => 'decimal:2',
        'cost_usd_cents' => 'integer',
        'fx_rate' => 'decimal:4',
        'years' => 'integer',
        'privacy_protection' => 'boolean',
        'auto_renew' => 'boolean',
        'nameservers' => 'array',
        'registered_at' => 'datetime',
        'expires_at' => 'datetime',
        'renewal_notice_sent_at' => 'datetime',
        'expiry_reminders_sent' => 'array',
        'last_polled_at' => 'datetime',
        'poll_attempts' => 'integer',
    ];

    /**
     * last_error can quote the upstream registrar word for word, which would
     * give away who we buy from. It never goes out with the model.
     *
     * @var array<int,string>
     */
    protected $hidden = ['last_error'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contact()
    {
        return $this->belongsTo(DomainContact::class, 'domain_contact_id');
    }

    public function walletTransaction()
    {
        return $this->belongsTo(WalletTransaction::class, 'wallet_transaction_id');
    }

    public function tldRecord()
    {
        return $this->belongsTo(DomainTld::class, 'tld', 'tld');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /** Orders the reconciliation job should chase. */
    public function scopeUnsettled($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_REGISTERING]);
    }

    /**
     * The domains themselves — one row per name.
     *
     * A renewal is a payment row in this same table, so any list of "the
     * customer's domains" has to exclude them or the same name appears again
     * every year it is kept.
     */
    public function scopeRegistrations($query)
    {
        return $query->where('kind', self::KIND_REGISTER);
    }

    /**
     * Domains whose owner should be told a charge is coming.
     *
     * Only one notice per period: renewal_notice_sent_at is cleared when the
     * renewal goes through, which is what arms it again for next year.
     */
    public function scopeDueForRenewalNotice($query, int $withinDays = 37)
    {
        return $query->registrations()
            ->active()
            ->where('auto_renew', true)
            ->whereNull('renewal_notice_sent_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($withinDays));
    }

    /**
     * Domains to charge for.
     *
     * The notice has to have gone out, and to have been out long enough to be
     * read — the page promises a warning before the money moves, and a warning
     * that arrives in the same minute as the receipt is not one.
     */
    public function scopeDueForRenewalCharge($query, int $withinDays = 30, int $noticeDays = 3)
    {
        return $query->registrations()
            ->active()
            ->where('auto_renew', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($withinDays))
            ->whereNotNull('renewal_notice_sent_at')
            ->where('renewal_notice_sent_at', '<=', now()->subDays($noticeDays));
    }

    /**
     * Domains running out that nothing will renew for them.
     *
     * The mirror of dueForRenewalNotice: that one is for auto-renew, this one
     * is for everybody else. Until this existed, a customer who left auto-renew
     * off heard nothing at all — the domain just stopped working one morning.
     *
     * Deliberately not filtered on which milestone is due. The milestones are
     * configuration and the "already sent" record is a JSON list, so choosing
     * between them is done in PHP, per domain, by dueReminderMilestone().
     */
    public function scopeDueForExpiryReminder($query, int $withinDays = 60)
    {
        return $query->registrations()
            ->active()
            ->where('auto_renew', false)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($withinDays));
    }

    /**
     * The milestone this domain has reached and not yet been told about.
     *
     * Walks the configured days largest-first and takes the first one the
     * domain is at or past, so a domain picked up late (added when it already
     * had 8 days left, with milestones 60/30/14/7) still gets the 14-day
     * reminder rather than silently skipping to 7.
     *
     * Only the milestone that fires is recorded, not the ones jumped over:
     * the customer should hear again at 7 and at 1.
     *
     * @param  list<int>  $milestones  descending
     */
    public function dueReminderMilestone(array $milestones): ?int
    {
        $daysLeft = $this->daysUntilExpiry();

        // Null means no expiry date on record, which is not the same as
        // "expires today" — there is nothing to count down to.
        if ($daysLeft === null || $daysLeft < 0) {
            return null;
        }

        $sent = array_map('intval', (array) ($this->expiry_reminders_sent ?? []));

        foreach ($milestones as $milestone) {
            if ($daysLeft <= $milestone && ! in_array($milestone, $sent, true)) {
                return $milestone;
            }
        }

        return null;
    }

    /** Remember that this milestone has gone out for the current period. */
    public function markReminderSent(int $milestone): void
    {
        $sent = array_map('intval', (array) ($this->expiry_reminders_sent ?? []));
        $sent[] = $milestone;

        $this->update(['expiry_reminders_sent' => array_values(array_unique($sent))]);
    }

    /**
     * Every renewal ever paid for this domain, newest first.
     */
    public function renewals()
    {
        return $this->hasMany(self::class, 'renewal_of')->orderByDesc('created_at');
    }

    public function renewalOf()
    {
        return $this->belongsTo(self::class, 'renewal_of');
    }

    public function isRenewal(): bool
    {
        return $this->kind === self::KIND_RENEW;
    }

    /**
     * Can this be renewed right now?
     *
     * A domain we never finished registering has no subscription upstream to
     * renew, and renewing one that is already paid for a year ahead is how a
     * customer ends up buying three years by pressing a button twice.
     */
    public function canRenew(): bool
    {
        return $this->kind === self::KIND_REGISTER
            && $this->status === self::STATUS_ACTIVE
            && $this->remote_subscription_id !== null
            && ! $this->renewals()->whereIn('status', [self::STATUS_PENDING, self::STATUS_REGISTERING])->exists();
    }

    public function isSettled(): bool
    {
        return ! in_array($this->status, [self::STATUS_PENDING, self::STATUS_REGISTERING], true);
    }

    public function isUsable(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Has the money already been given back? Guards a second refund when a
     * failure is retried.
     */
    public function isRefunded(): bool
    {
        return $this->refund_transaction_id !== null;
    }

    public function daysUntilExpiry(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expires_at->startOfDay(), false);
    }

    public function isExpiringSoon(int $withinDays = 30): bool
    {
        $days = $this->daysUntilExpiry();

        return $days !== null && $days >= 0 && $days <= $withinDays;
    }

    /**
     * Customer-facing status label. Deliberately says nothing about who the
     * registrar is or which step upstream is stuck on.
     *
     * `classes` is a complete, literal Tailwind class string rather than a
     * colour name the view interpolates. Tailwind only emits classes it can
     * see as whole strings when it scans the source, so "bg-{$tone}-100"
     * renders as an unstyled badge in production — and only in production,
     * which is the worst way to find out.
     *
     * Both light and dark are spelled out because the premium portal layout
     * forces light backgrounds dark with !important without remapping text
     * colour; a badge that only sets the light pair disappears there.
     *
     * @return array{label_th: string, label_en: string, classes: string}
     */
    public function statusBadge(): array
    {
        return match ($this->status) {
            self::STATUS_PENDING,
            self::STATUS_REGISTERING => [
                'label_th' => 'กำลังจดทะเบียน',
                'label_en' => 'Registering',
                'classes' => 'bg-amber-100 dark:bg-amber-500/20 text-amber-800 dark:text-amber-300',
            ],
            self::STATUS_ACTIVE => [
                'label_th' => 'ใช้งานอยู่',
                'label_en' => 'Active',
                'classes' => 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-800 dark:text-emerald-300',
            ],
            self::STATUS_FAILED => [
                'label_th' => 'ไม่สำเร็จ',
                'label_en' => 'Failed',
                'classes' => 'bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300',
            ],
            self::STATUS_REFUNDED => [
                'label_th' => 'คืนเงินแล้ว',
                'label_en' => 'Refunded',
                'classes' => 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300',
            ],
            self::STATUS_EXPIRED => [
                'label_th' => 'หมดอายุ',
                'label_en' => 'Expired',
                'classes' => 'bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300',
            ],
            self::STATUS_TRANSFERRED_OUT => [
                'label_th' => 'ย้ายออกแล้ว',
                'label_en' => 'Transferred out',
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
     * The key that makes a double-tap lose at the unique index instead of at
     * a balance check that has already gone stale.
     *
     * Scoped to the day so that a genuine repurchase of a domain that lapsed
     * a year later is still allowed through.
     */
    public static function makeIdempotencyKey(int $userId, string $domain, string $kind): string
    {
        return substr(hash('sha256', implode('|', [
            $userId,
            strtolower($domain),
            $kind,
            now()->format('Y-m-d'),
        ])), 0, 64);
    }
}
