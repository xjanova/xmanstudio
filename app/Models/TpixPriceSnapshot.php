<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The TPIX price read from the DEX pool, at most once a minute (TpixPriceService::spot()).
 * Decimal columns stay strings (no float cast): they feed exact money math.
 */
class TpixPriceSnapshot extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'price_usd',
        'usd_thb',
        'price_thb',
        'reserve_tpix',
        'reserve_usdt',
        'source',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
