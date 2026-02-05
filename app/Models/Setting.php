<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * List of setting keys that contain sensitive data and should be encrypted
     */
    protected static array $encryptedKeys = [
        'ai_openai_key',
        'ai_claude_key',
        'line_notify_token',
        'youtube_api_key',
        'youtube_client_secret',
        'youtube_refresh_token',
        'smtp_password',
        'database_password',
        'stripe_secret_key',
        'paypal_secret',
    ];

    /**
     * Get setting value by key
     * Automatically decrypts sensitive data
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting.{$key}", 3600, function () use ($key) {
            return static::where('key', $key)->first();
        });

        if (! $setting) {
            return $default;
        }

        $value = $setting->value;

        // Decrypt if this is an encrypted key
        if (static::isEncryptedKey($key) && ! empty($value)) {
            try {
                $value = Crypt::decryptString($value);
            } catch (\Exception $e) {
                // If decryption fails, the value might not be encrypted yet
                // This handles backward compatibility with existing unencrypted values
                \Log::warning("Failed to decrypt setting '{$key}': " . $e->getMessage());
                // Return the value as-is for backward compatibility
                // Admin should re-save the setting to encrypt it
            }
        }

        return match ($setting->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Set setting value
     * Automatically encrypts sensitive data
     */
    public static function setValue(
        string $key,
        mixed $value,
        string $type = 'string',
        ?string $group = null,
        ?string $description = null,
        bool $isPublic = false
    ): void {
        $storedValue = $type === 'json' ? json_encode($value) : (string) $value;

        // Encrypt if this is a sensitive key
        if (static::isEncryptedKey($key) && ! empty($storedValue)) {
            $storedValue = Crypt::encryptString($storedValue);
        }

        $data = [
            'value' => $storedValue,
            'type' => $type,
        ];

        if ($group) {
            $data['group'] = $group;
        }

        if ($description !== null) {
            $data['description'] = $description;
        }

        $data['is_public'] = $isPublic;

        static::updateOrCreate(['key' => $key], $data);

        Cache::forget("setting.{$key}");
    }

    /**
     * Check if a setting key should be encrypted
     */
    protected static function isEncryptedKey(string $key): bool
    {
        return in_array($key, static::$encryptedKeys);
    }

    /**
     * Alias for getValue (for backward compatibility)
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::getValue($key, $default);
    }

    /**
     * Alias for setValue (for backward compatibility)
     */
    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        static::setValue($key, $value, $type);
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group): array
    {
        return static::where('group', $group)
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Get Line Notify token
     */
    public static function getLineNotifyToken(): ?string
    {
        return static::getValue('line_notify_token');
    }

    /**
     * Set Line Notify token
     */
    public static function setLineNotifyToken(string $token): void
    {
        static::setValue('line_notify_token', $token, 'string', 'notification');
    }

    /**
     * Check if notifications are enabled
     */
    public static function isNotificationEnabled(): bool
    {
        return static::getValue('notification_enabled', true);
    }
}
