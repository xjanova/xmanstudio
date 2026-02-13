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
    ];

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

    public function markAsViewed(): void
    {
        if (! $this->viewed_at) {
            $this->update([
                'status' => 'viewed',
                'viewed_at' => now(),
            ]);
        }
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
