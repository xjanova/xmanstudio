<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiprayChant extends Model
{
    protected $table = 'aipray_chants';

    protected $fillable = [
        'chant_id', 'title_th', 'title_en', 'category', 'lines',
        'audio_url', 'is_community', 'author', 'sort_order',
        'is_active', 'updated_at_token',
    ];

    protected $casts = [
        'lines' => 'array',
        'is_community' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCommunity($query)
    {
        return $query->where('is_community', true)->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::saving(function (AiprayChant $chant) {
            $chant->updated_at_token = now()->timestamp . '_' . $chant->chant_id;
        });
    }
}
