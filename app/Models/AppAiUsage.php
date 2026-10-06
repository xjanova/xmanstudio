<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One AI call made by the GigGok app through our server.
 *
 * See the migration for why this is a table and not a cache counter.
 */
class AppAiUsage extends Model
{
    protected $fillable = [
        'user_id',
        'license_key_id',
        'license_key',
        'provider',
        'model',
        'message_count',
        'chars_in',
        'chars_out',
        'ok',
        'price',
        'wallet_transaction_id',
        'refunded',
        'ip_address',
    ];

    protected $casts = [
        'ok' => 'boolean',
        'price' => 'decimal:2',
        'refunded' => 'boolean',
        'message_count' => 'integer',
        'chars_in' => 'integer',
        'chars_out' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function licenseKey(): BelongsTo
    {
        return $this->belongsTo(LicenseKey::class, 'license_key_id');
    }

    /** The wallet payment that paid for this call (null when the model was free). */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'wallet_transaction_id');
    }
}
