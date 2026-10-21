<?php

namespace App\Models;

use App\Support\Quotation\Pricing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The statuses the `status` column accepts.
     *
     * This mirrors the database enum exactly and exists because it was missed
     * once already: markAsDeclined() wrote 'declined', which SQLite stored
     * happily and MySQL — with STRICT_TRANS_TABLES, as production runs — would
     * have rejected outright, so a customer pressing "ไม่รับข้อเสนอ" on the live
     * page would have got a 500 while every local test passed. Code that sets a
     * status validates against this list; a genuinely new status needs a
     * migration to widen the enum first.
     *
     * @var list<string>
     */
    public const STATUSES = ['draft', 'sent', 'viewed', 'accepted', 'rejected', 'expired', 'paid'];

    protected $fillable = [
        'quote_number',
        'user_id',
        'customer_name',
        'customer_company',
        'customer_email',
        'customer_phone',
        'customer_address',
        'service_type',
        'service_name',
        'service_options',
        'additional_options',
        'option_details',
        'project_description',
        'timeline',
        'subtotal',
        'discount',
        'discount_percent',
        'rush_fee',
        'vat',
        'grand_total',
        'status',
        'action_type',
        'payment_method',
        'payment_status',
        'valid_until',
        'sent_at',
        'viewed_at',
        'accepted_at',
        'paid_at',
        'admin_notes',
        'customer_notes',
        'vat_mode',
        'vat_rate',
        'amount_before_vat',
        'withholding_pct',
        'withholding_amount',
        'public_token',
        'outcome',
        'version',
        'revision_of',
        'revision_note',
        'declined_at',
        'superseded_at',
        'follow_up_sent_at',
    ];

    /**
     * The token is the key to the document. It must not travel in API
     * responses or land in a log line by accident — the link is e-mailed, and
     * nothing else should hand it out.
     */
    protected $hidden = ['public_token'];

    protected $casts = [
        'service_options' => 'array',
        'additional_options' => 'array',
        'option_details' => 'array',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'rush_fee' => 'decimal:2',
        'vat' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'accepted_at' => 'datetime',
        'paid_at' => 'datetime',
        'declined_at' => 'datetime',
        'superseded_at' => 'datetime',
        'follow_up_sent_at' => 'datetime',
        'vat_rate' => 'decimal:2',
        'amount_before_vat' => 'decimal:2',
        'withholding_pct' => 'decimal:2',
        'withholding_amount' => 'decimal:2',
        'version' => 'integer',
        'revision_of' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): HasOne
    {
        return $this->hasOne(ProjectOrder::class);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'sent', 'viewed']);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOrders($query)
    {
        return $query->where('action_type', 'order');
    }

    public function scopeQuotations($query)
    {
        return $query->where('action_type', 'quotation');
    }

    public function isExpired(): bool
    {
        return $this->valid_until->isPast();
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * A link that cannot be walked.
     *
     * quote_number is QT-<date>-<4 chars from a 32-symbol alphabet>, which is
     * about a million guesses for a given day — a script's afternoon. The
     * document behind it carries prices and the customer's contact details,
     * so the public link gets its own secret instead.
     */
    public static function newPublicToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));
        } while (static::where('public_token', $token)->exists());

        return $token;
    }

    /**
     * The address we put in the e-mail. Null while the quotation has no token
     * — the 13 issued before this existed — so callers can fall back rather
     * than link to a page that will 404.
     */
    public function publicUrl(): ?string
    {
        return $this->public_token ? route('quote.show', $this->public_token) : null;
    }

    public function markAsViewed(): void
    {
        // Only the first open, and never a downgrade: a customer re-reading a
        // quotation they already accepted must not push it back to "viewed".
        if (! $this->viewed_at && in_array($this->status, ['draft', 'sent'], true)) {
            $this->update([
                'status' => 'viewed',
                'viewed_at' => now(),
            ]);
        }
    }

    /**
     * The customer said no.
     *
     * Stored as 'rejected' — the value the column has always accepted and the
     * admin list already labels "ปฏิเสธ". declined_at records when, so the
     * answer is distinguishable from a quotation an admin rejected by hand.
     */
    public function markAsDeclined(?string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'declined_at' => now(),
            'customer_notes' => $reason ?: $this->customer_notes,
        ]);
    }

    /**
     * Still open to an answer from the customer.
     */
    public function awaitingResponse(): bool
    {
        return in_array($this->status, ['draft', 'sent', 'viewed'], true)
            && $this->superseded_at === null
            && ! $this->isExpired();
    }

    public function markAsAccepted(): void
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    /**
     * The instalments, once the job is accepted and a project exists.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(ProjectInvoice::class)->orderBy('installment_no');
    }

    /**
     * Every version of this document, this one included, oldest first.
     *
     * Grouped by quote_number rather than by revision_of so the chain reads the
     * same whichever version you start from.
     */
    public function versions()
    {
        return static::withTrashed()
            ->where('quote_number', $this->quote_number)
            ->orderBy('version');
    }

    /**
     * What to print and what to show in a list: the number, plus the revision
     * when there has been one. Version 1 prints as a plain number — nobody
     * wants "Rev.1" on a first quotation.
     */
    public function displayNumber(): string
    {
        return ((int) $this->version) > 1
            ? $this->quote_number . ' Rev.' . (int) $this->version
            : (string) $this->quote_number;
    }

    public function isSuperseded(): bool
    {
        return $this->superseded_at !== null;
    }

    /**
     * Re-quote the same job.
     *
     * The customer asked to renegotiate: they get a new document, but the job
     * keeps its number so the admin list, the customer's inbox and accounting
     * all still see one thread. The old version is marked superseded — it stays
     * readable, because the customer has the PDF, but can no longer be
     * accepted, so two versions can never both be live.
     *
     * The copy starts as a draft with no answer on it: carrying accepted_at or
     * viewed_at across would make a brand new document look answered.
     */
    public function createRevision(?string $note = null): self
    {
        $nextVersion = (int) static::withTrashed()
            ->where('quote_number', $this->quote_number)
            ->max('version') + 1;

        $copy = $this->replicate([
            'public_token',
            'status',
            'sent_at',
            'follow_up_sent_at',
            'viewed_at',
            'accepted_at',
            'paid_at',
            'declined_at',
            'superseded_at',
        ]);

        $copy->fill([
            'version' => $nextVersion,
            'revision_of' => $this->revision_of ?: $this->id,
            'revision_note' => $note,
            'status' => 'draft',
            'public_token' => static::newPublicToken(),
            'valid_until' => now()->addDays(Pricing::validDays()),
        ]);
        $copy->save();

        $this->update(['superseded_at' => now()]);

        return $copy;
    }

    /**
     * Sent, still silent, and about to run out of time.
     *
     * The window is bounded on both sides: a quotation that expired while the
     * scheduler was down must not get a "3 days left" e-mail a week late.
     */
    public function scopeNeedsFollowUp($query, int $daysBefore = 3)
    {
        return $query->whereIn('status', ['sent', 'viewed'])
            ->whereNull('follow_up_sent_at')
            ->whereNull('superseded_at')
            ->whereNotNull('sent_at')
            ->whereDate('valid_until', '>=', now()->toDateString())
            ->whereDate('valid_until', '<=', now()->addDays($daysBefore)->toDateString());
    }

    /**
     * Sent and unanswered — what the admin needs to chase, newest silence last.
     */
    public function scopeAwaitingAnswer($query)
    {
        return $query->whereIn('status', ['sent', 'viewed'])
            ->whereNull('superseded_at')
            ->whereNotNull('sent_at');
    }

    /**
     * Days since the customer was sent this, or null if it never went out.
     */
    public function daysSinceSent(): ?int
    {
        return $this->sent_at ? (int) $this->sent_at->startOfDay()->diffInDays(now()->startOfDay()) : null;
    }

    /**
     * Days left before it expires. Negative once it has.
     */
    public function daysLeft(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->valid_until->startOfDay(), false);
    }
}
