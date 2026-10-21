<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One instalment of an accepted job.
 *
 * Created as a set when a quotation is accepted: the first one issued, the rest
 * scheduled. Amounts are copied from the quotation at acceptance and never
 * recalculated — a document the customer already has must not change because a
 * setting moved.
 */
class ProjectInvoice extends Model
{
    use HasFactory;

    /**
     * The statuses the application knows.
     *
     * Kept here rather than in a database enum: a value the enum has not heard
     * of is a hard error on MySQL and a silent success on SQLite, which is how
     * a status bug reaches production green.
     */
    public const SCHEDULED = 'scheduled';

    public const ISSUED = 'issued';

    public const PAID = 'paid';

    public const VOID = 'void';

    /** @var list<string> */
    public const STATUSES = [self::SCHEDULED, self::ISSUED, self::PAID, self::VOID];

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        self::SCHEDULED => 'ยังไม่เรียกเก็บ',
        self::ISSUED => 'รอชำระ',
        self::PAID => 'ชำระแล้ว',
        self::VOID => 'ยกเลิก',
    ];

    protected $fillable = [
        'invoice_number',
        'project_order_id',
        'quotation_id',
        'installment_no',
        'total_installments',
        'title',
        'percent',
        'amount',
        'status',
        'due_date',
        'issued_at',
        'paid_at',
        'paid_note',
        'notes',
        'public_token',
    ];

    /**
     * The link is the key to the document — it must not ride along in an API
     * response the way the quotation's token nearly did.
     */
    protected $hidden = ['public_token'];

    protected $casts = [
        'installment_no' => 'integer',
        'total_installments' => 'integer',
        'percent' => 'decimal:2',
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber();
            }
            if (empty($invoice->public_token)) {
                $invoice->public_token = static::newPublicToken();
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        do {
            $number = 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));
        } while (static::where('invoice_number', $number)->exists());

        return $number;
    }

    public static function newPublicToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));
        } while (static::where('public_token', $token)->exists());

        return $token;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectOrder::class, 'project_order_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function scopeOutstanding($query)
    {
        return $query->whereIn('status', [self::SCHEDULED, self::ISSUED]);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function isPaid(): bool
    {
        return $this->status === self::PAID;
    }

    /**
     * Past due and still unpaid. A scheduled instalment is not overdue — it has
     * not been asked for yet.
     */
    public function isOverdue(): bool
    {
        return $this->status === self::ISSUED
            && $this->due_date !== null
            && $this->due_date->endOfDay()->isPast();
    }

    public function publicUrl(): ?string
    {
        return $this->public_token ? route('invoice.show', $this->public_token) : null;
    }

    /**
     * Hand the invoice to the customer. Idempotent: re-issuing keeps the
     * original issue date, so a second click does not push the due date out.
     */
    public function issue(?int $dueDays = null): void
    {
        if (in_array($this->status, [self::PAID, self::VOID], true)) {
            return;
        }

        $this->update([
            'status' => self::ISSUED,
            'issued_at' => $this->issued_at ?? now(),
            'due_date' => $this->due_date ?? now()->addDays($dueDays ?? 7)->toDateString(),
        ]);
    }

    public function markAsPaid(?string $note = null): void
    {
        if ($this->status === self::PAID) {
            return;
        }

        $this->update([
            'status' => self::PAID,
            'paid_at' => now(),
            'paid_note' => $note ?: $this->paid_note,
            // Paying an instalment nobody had issued still counts as paid, but
            // the document needs an issue date or it prints a blank line.
            'issued_at' => $this->issued_at ?? now(),
        ]);
    }
}
