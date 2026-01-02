<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PaymentSetting extends Model
{
    protected $fillable = [
        'key',
        'group',
        'value',
        'type',
        'label',
        'description',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /**
     * Get setting value with proper type casting
     */
    public function getTypedValueAttribute()
    {
        $value = $this->is_encrypted && $this->value
            ? Crypt::decryptString($this->value)
            : $this->value;

        return match ($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            'integer' => (int) $value,
            'float' => (float) $value,
            default => $value,
        };
    }

    /**
     * Set value with encryption if needed
     */
    public function setValueAttribute($value)
    {
        if ($this->type === 'json' && is_array($value)) {
            $value = json_encode($value);
        }

        $this->attributes['value'] = $this->is_encrypted && $value
            ? Crypt::encryptString($value)
            : $value;
    }

    /**
     * Get setting by key
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->typed_value : $default;
    }

    /**
     * Set setting by key
     */
    public static function set(string $key, $value, array $attributes = []): self
    {
        $setting = static::firstOrNew(['key' => $key]);

        foreach ($attributes as $attr => $val) {
            $setting->{$attr} = $val;
        }

        $setting->value = $value;
        $setting->save();

        return $setting;
    }

    /**
     * Get all settings in a group
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->pluck('typed_value', 'key')
            ->toArray();
    }

    /**
     * Scope by group
     */
    public function scopeInGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}
