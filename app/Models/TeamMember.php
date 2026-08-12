<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    /**
     * Portrait to render on the team page.
     *
     * An uploaded `image` wins. Otherwise falls back to the in-repo portrait at
     * public_html/artwork/team/<slug-of-name>.webp — storage/ is not in version
     * control, so a seeded member would otherwise render the blank silhouette
     * on a fresh deploy. Returns null when neither exists.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }

        $file = 'artwork/team/' . Str::slug($this->name) . '.webp';

        return is_file(public_path($file)) ? asset($file) : null;
    }

    public function getSkillsArrayAttribute(): array
    {
        return $this->skills ? array_map('trim', explode(',', $this->skills)) : [];
    }
}
