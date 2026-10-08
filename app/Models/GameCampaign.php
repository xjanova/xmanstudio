<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameCampaign extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'goal_satang', 'tiers', 'active'];

    protected $casts = ['tiers' => 'array', 'active' => 'boolean', 'goal_satang' => 'integer'];

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
}
