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
        'auto_sync',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_sync' => 'boolean',
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
        // 2026-08-31: เดิม `if (! empty($value))` = เขียนค่าว่างทับไม่ได้เลย
        // ⇒ token ที่ถูก revoke ค้างอยู่ในฐานข้อมูลตลอดกาล ลบทางหน้า admin ไม่ได้
        //   (cluadex เจอมาแล้ว GitHub ตอบ 401 ทั้งที่ repo เป็น public)
        //
        // การกันไม่ให้ฟอร์มลบ token โดยไม่ตั้งใจ เป็นหน้าที่ของ controller ที่เช็ค
        // ค่า '********' (= "ไม่แตะของเดิม") ไม่ใช่หน้าที่ของ mutator ที่จะเมินคำสั่งเงียบ ๆ
        //
        // เก็บเป็นสตริงว่าง ไม่ใช่ null เพราะคอลัมน์เป็น NOT NULL
        $this->attributes['github_token'] = empty($value) ? '' : Crypt::encryptString($value);
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
