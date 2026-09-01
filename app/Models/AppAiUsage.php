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
        'ip_address',
    ];

    protected $casts = [
        'ok' => 'boolean',
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

    /**
     * Calls this license has made since midnight.
     *
     * Counts failed calls too. A caller hammering a broken request still costs
     * us upstream attempts, and leaving failures uncounted turns "my key is
     * wrong" into an unlimited retry loop against our account.
     */
    public static function todayFor(string $licenseKey): int
    {
        return static::where('license_key', $licenseKey)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }
}
