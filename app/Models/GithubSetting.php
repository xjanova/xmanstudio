<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class GithubSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'github_owner',
        'github_repo',
        'github_token',
        'asset_pattern',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'github_token',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Encrypt the token before saving
     */
    public function setGithubTokenAttribute($value): void
    {
        if (! empty($value)) {
            $this->attributes['github_token'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt the token when accessing
     */
    public function getGithubTokenDecryptedAttribute(): ?string
    {
        if (empty($this->attributes['github_token'])) {
            return null;
        }

        try {
            return Crypt::decryptString($this->attributes['github_token']);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get full repository name (owner/repo)
     */
    public function getFullRepoNameAttribute(): string
    {
        return "{$this->github_owner}/{$this->github_repo}";
    }

    /**
     * Get GitHub API URL for releases
     */
    public function getReleasesApiUrlAttribute(): string
    {
        return "https://api.github.com/repos/{$this->full_repo_name}/releases";
    }

    /**
     * Get latest release API URL
     */
    public function getLatestReleaseApiUrlAttribute(): string
    {
        return "https://api.github.com/repos/{$this->full_repo_name}/releases/latest";
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
