<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A device that redeemed an entitlement's code. Only hashes are stored. */
class GameItemRedemption extends Model
{
    protected $guarded = ['id'];

    public function entitlement(): BelongsTo
    {
        return $this->belongsTo(GameEntitlement::class, 'game_entitlement_id');
    }
}
