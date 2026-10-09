<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** An in-game item a game can unlock from a redeem code. Never deleted, only deactivated. */
class GameItem extends Model
{
    public const KINDS = ['cosmetic' => 'ของแต่ง', 'title' => 'ฉายา', 'badge' => 'ตรา / ป้าย', 'pass' => 'บัตรผ่าน'];

    protected $fillable = ['game_campaign_id', 'key', 'name', 'description', 'kind', 'image_url', 'max_devices', 'active'];

    protected $casts = ['active' => 'boolean', 'max_devices' => 'integer'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(GameCampaign::class, 'game_campaign_id');
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(GameEntitlement::class);
    }
}
