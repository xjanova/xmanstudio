<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BtFile extends Model
{
    protected $fillable = [
        'category_id',
        'uploader_machine_id',
        'uploader_display_name',
        'file_hash',
        'file_name',
        'file_size',
        'description',
        'thumbnail_url',
        'chunk_size',
        'total_chunks',
        'download_count',
        'is_active',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'chunk_size' => 'integer',
        'total_chunks' => 'integer',
        'download_count' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(BtCategory::class, 'category_id');
    }

    public function seeders(): HasMany
    {
        return $this->hasMany(BtFileSeeder::class, 'bt_file_id');
    }

    public function onlineSeeders(): HasMany
    {
        return $this->hasMany(BtFileSeeder::class, 'bt_file_id')
            ->where('is_online', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
