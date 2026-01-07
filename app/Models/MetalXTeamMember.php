<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetalXTeamMember extends Model
{
    protected $fillable = [
        'name',
        'name_th',
        'role',
        'role_th',
        'bio',
        'bio_th',
        'image',
        'youtube_url',
        'facebook_url',
        'instagram_url',
        'twitter_url',
        'tiktok_url',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope a query to only include active team members.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order team members by their order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
