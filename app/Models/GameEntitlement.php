<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One member's right to one item, with the redeem code the game checks. */
class GameEntitlement extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['code', 'code_hash'];

    protected $casts = [
        'code' => 'encrypted',
        'redeem_count' => 'integer',
        'first_redeemed_at' => 'datetime',
        'last_redeemed_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(GameItem::class, 'game_item_id');
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(GameDonation::class, 'game_donation_id');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(GameItemRedemption::class);
    }
}
