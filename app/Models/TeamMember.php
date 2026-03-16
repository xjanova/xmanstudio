<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    protected $fillable = [
        'name',
        'name_th',
        'position',
        'position_th',
        'bio',
        'bio_th',
        'image',
        'department',
        'skills',
        'facebook_url',
        'linkedin_url',
        'github_url',
        'website_url',
        'is_leader',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_leader' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function scopeLeaders($query)
    {
        return $query->where('is_leader', true);
    }

    public function scopeMembers($query)
    {
        return $query->where('is_leader', false);
    }

    public function getSkillsArrayAttribute(): array
    {
        return $this->skills ? array_map('trim', explode(',', $this->skills)) : [];
    }
}
