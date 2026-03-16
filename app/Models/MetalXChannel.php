<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetalXChannel extends Model
{
    protected $fillable = [
        'name',
        'youtube_channel_id',
        'google_email',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'channel_thumbnail_url',
        'subscriber_count',
        'video_count',
        'is_active',
        'is_default',
        'scopes',
        'last_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'scopes' => 'array',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'subscriber_count' => 'integer',
        'video_count' => 'integer',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    // Encrypt tokens on set
    public function setAccessTokenAttribute($value): void
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getAccessTokenAttribute($value): ?string
    {
        try {
            return $value ? Crypt::decryptString($value) : null;
        } catch (\Exception $e) {
            return $value;
        }
    }

    public function setRefreshTokenAttribute($value): void
    {
        $this->attributes['refresh_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getRefreshTokenAttribute($value): ?string
    {
        try {
            return $value ? Crypt::decryptString($value) : null;
        } catch (\Exception $e) {
            return $value;
        }
    }

    // Relationships
    public function videos(): HasMany
    {
        return $this->hasMany(MetalXVideo::class, 'metal_x_channel_id');
    }

    public function videoProjects(): HasMany
    {
        return $this->hasMany(MetalXVideoProject::class, 'channel_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Methods
    public static function getDefault(): ?self
    {
        return static::active()->default()->first();
    }

    public function isTokenExpired(): bool
    {
        if (! $this->token_expires_at) {
            return true;
        }

        return now()->addMinutes(5)->isAfter($this->token_expires_at);
    }

    public function getValidAccessToken(): ?string
    {
        $token = $this->access_token;

        if (empty($token)) {
            return null;
        }

        if ($this->isTokenExpired()) {
            return $this->refreshAccessToken();
        }

        return $token;
    }

    public function refreshAccessToken(): ?string
    {
        $refreshToken = $this->refresh_token;

        if (empty($refreshToken)) {
            Log::warning("[Metal-X Channel] No refresh token for channel {$this->youtube_channel_id}");

            return null;
        }

        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => config('services.youtube.client_id') ?: Setting::getValue('youtube_client_id'),
                'client_secret' => config('services.youtube.client_secret') ?: Setting::getValue('youtube_client_secret'),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $this->update([
                    'access_token' => $data['access_token'],
                    'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
                ]);

                Log::info("[Metal-X Channel] Token refreshed for {$this->name}");

                return $data['access_token'];
            }

            Log::error("[Metal-X Channel] Token refresh failed for {$this->name}", [
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("[Metal-X Channel] Token refresh error: {$e->getMessage()}");

            return null;
        }
    }

    public function hasUploadScope(): bool
    {
        $scopes = $this->scopes ?? [];

        return in_array('https://www.googleapis.com/auth/youtube.upload', $scopes);
    }

    public function getFormattedSubscriberCountAttribute(): string
    {
        return number_format($this->subscriber_count);
    }

    public function getYoutubeUrlAttribute(): string
    {
        return "https://www.youtube.com/channel/{$this->youtube_channel_id}";
    }
}
