<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A browser that may skip the second step until expires_at
 * (App\Support\Auth\TwoFactor::passedOnTrustedDevice).
 */
class TwoFactorTrustedDevice extends Model
{
    protected $fillable = [
        'user_id',
        'token_hash',
        'secret_fingerprint',
        'label',
        'ip',
        'last_used_at',
        'expires_at',
    ];

    protected $hidden = [
        'token_hash',
        'secret_fingerprint',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
