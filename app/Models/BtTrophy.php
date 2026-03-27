<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BtTrophy extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon',
        'badge_text',
        'difficulty',
        'requirement_type',
        'requirement_value',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'requirement_value' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function userTrophies(): HasMany
    {
        return $this->hasMany(BtUserTrophy::class, 'trophy_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeEasy(Builder $query): Builder
    {
        return $query->where('difficulty', 'easy');
    }

    public function scopeMedium(Builder $query): Builder
    {
        return $query->where('difficulty', 'medium');
    }

    public function scopeHard(Builder $query): Builder
    {
        return $query->where('difficulty', 'hard');
    }
}
