<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameDonation extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['publish_name' => 'boolean', 'amount_satang' => 'integer', 'bank_snapshot' => 'array', 'reward_snapshot' => 'array', 'audit' => 'array', 'reviewed_at' => 'datetime'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(GameCampaign::class, 'game_campaign_id');
    }

    /** In-game items granted when this donation was approved. */
    public function entitlements(): HasMany
    {
        return $this->hasMany(GameEntitlement::class);
    }
}
