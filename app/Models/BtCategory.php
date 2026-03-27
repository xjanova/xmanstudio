<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BtCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'is_adult',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_adult' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function files(): HasMany
    {
        return $this->hasMany(BtFile::class, 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAdult(Builder $query): Builder
    {
        return $query->where('is_adult', true);
    }

    public function scopeNonAdult(Builder $query): Builder
    {
        return $query->where('is_adult', false);
    }
}
