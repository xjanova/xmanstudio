<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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
     * Get setting value by key
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting.{$key}", 3600, function () use ($key) {
            return static::where('key', $key)->first();
        });

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    /**
     * Set setting value
     */
    public static function setValue(
        string $key,
        mixed $value,
        string $type = 'string',
        ?string $group = null,
        ?string $description = null,
        bool $isPublic = false
    ): void {
        $data = [
            'value' => $type === 'json' ? json_encode($value) : (string) $value,
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
