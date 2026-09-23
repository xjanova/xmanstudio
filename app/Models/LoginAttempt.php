<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per sign-in attempt. Written by App\Support\Auth\LoginLog.
 *
 * There is no updated_at: an attempt is a thing that happened, not a thing
 * that changes.
 */
class LoginAttempt extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public const OUTCOME_SUCCESS = 'success';

    public const OUTCOME_FAILED = 'failed';

    /** Refused by the login throttle (five wrong passwords for one IP+email). */
    public const OUTCOME_LOCKOUT = 'lockout';

    /** Refused by the block list before the form was even reached. */
    public const OUTCOME_BLOCKED = 'blocked';

    /** The Turnstile token was missing or rejected. */
    public const OUTCOME_TURNSTILE = 'turnstile';

    public const OUTCOMES = [
        self::OUTCOME_SUCCESS => 'สำเร็จ',
        self::OUTCOME_FAILED => 'รหัสผ่านผิด',
        self::OUTCOME_LOCKOUT => 'ถูกล็อกชั่วคราว',
        self::OUTCOME_BLOCKED => 'ถูกบล็อก',
        self::OUTCOME_TURNSTILE => 'ไม่ผ่าน Turnstile',
    ];

    public const METHODS = [
        'password' => 'อีเมล + รหัสผ่าน',
        'line' => 'LINE',
        'google' => 'Google',
        'telegram' => 'Telegram',
        'api' => 'แอป (API)',
        'device' => 'ลิงก์จากแอป',
        'sso' => 'XMAN ID',
    ];

    protected $fillable = [
        'email',
        'user_id',
        'ip',
        'country',
        'user_agent',
        'outcome',
        'method',
        'is_admin_target',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_admin_target' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Anything that is not a completed sign-in. */
    public function scopeUnsuccessful(Builder $query): Builder
    {
        return $query->where('outcome', '!=', self::OUTCOME_SUCCESS);
    }

    public function scopeSince(Builder $query, \DateTimeInterface $since): Builder
    {
        return $query->where('created_at', '>=', $since);
    }

    public function outcomeLabel(): string
    {
        return self::OUTCOMES[$this->outcome] ?? $this->outcome;
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }

    /**
     * The email with its middle removed.
     *
     * The dashboard lists addresses that were typed by whoever was at the
     * keyboard — often a real customer's, sometimes a leaked list. Showing the
     * whole thing to every staff member with admin access is more than the
     * screen needs to do its job.
     */
    public function maskedEmail(): string
    {
        $email = (string) $this->email;

        if ($email === '') {
            return '—';
        }

        if (! str_contains($email, '@')) {
            return mb_substr($email, 0, 2) . '***';
        }

        [$local, $domain] = explode('@', $email, 2);

        $keep = mb_strlen($local) <= 2 ? 1 : 2;

        return mb_substr($local, 0, $keep) . str_repeat('*', max(1, mb_strlen($local) - $keep)) . '@' . $domain;
    }
}
