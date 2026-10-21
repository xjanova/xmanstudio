<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class GameCampaign extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'goal_satang', 'tiers', 'active', 'hero_rank', 'hero_hidden'];

    protected $casts = ['tiers' => 'array', 'active' => 'boolean', 'goal_satang' => 'integer', 'hero_rank' => 'integer', 'hero_hidden' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function donations(): HasMany
    {
        return $this->hasMany(GameDonation::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(GameComment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GameItem::class);
    }

    /** Player reviews live in the shared `reviews` table. */
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }
}
