<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiprayDonation extends Model
{
    protected $table = 'aipray_donations';

    protected $fillable = [
        'product_id', 'wallet_topup_id', 'donor_name', 'donor_email',
        'amount', 'message', 'is_anonymous', 'payment_method',
        'payment_reference', 'status', 'display_on_page',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_anonymous' => 'boolean',
        'display_on_page' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function walletTopup(): BelongsTo
    {
        return $this->belongsTo(WalletTopup::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePublicDisplay($query)
    {
        return $query->where('display_on_page', true)
            ->where('status', 'completed');
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->is_anonymous) return 'ผู้ไม่ประสงค์ออกนาม';
        return $this->donor_name ?? 'ผู้ไม่ประสงค์ออกนาม';
    }
}
