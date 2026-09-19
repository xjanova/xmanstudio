<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use HasFactory, SoftDeletes;

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
        'declined_at',
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
        'vat_rate' => 'decimal:2',
        'amount_before_vat' => 'decimal:2',
        'withholding_pct' => 'decimal:2',
        'withholding_amount' => 'decimal:2',
        'version' => 'integer',
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

    public function markAsDeclined(?string $reason = null): void
    {
        $this->update([
            'status' => 'declined',
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
}
