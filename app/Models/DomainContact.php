<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The person a domain is registered to.
 *
 * Always the customer, never us. See the migration for why that is a rule
 * rather than a preference.
 */
class DomainContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'remote_whois_id',
        'label',
        'first_name',
        'last_name',
        'organization',
        'email',
        'phone_country_code',
        'phone',
        'address1',
        'address2',
        'city',
        'state',
        'zip',
        'country',
        'extra_fields',
        'is_default',
        'hidden_from_picker',
        'synced_at',
    ];

    protected $casts = [
        'extra_fields' => 'array',
        'is_default' => 'boolean',
        'hidden_from_picker' => 'boolean',
        'synced_at' => 'datetime',
    ];

    /**
     * Personal data that must never reach a log line, an alert card or an
     * error page. Consulted by the admin alert redactor.
     *
     * @var array<int,string>
     */
    protected $hidden = [
        'phone',
        'address1',
        'address2',
        'zip',
        'extra_fields',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function registrations()
    {
        return $this->hasMany(DomainRegistration::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * One-line summary for a picker, with the street address left out — the
     * customer recognises their own contact from the name and city, and the
     * rest does not belong in a dropdown someone might screen-share.
     */
    public function summary(): string
    {
        $parts = array_filter([
            $this->label,
            $this->fullName(),
            $this->organization,
            $this->city,
            strtoupper($this->country),
        ]);

        return implode(' · ', $parts);
    }

    /**
     * Has this contact been mirrored upstream and not edited since?
     */
    public function isSynced(): bool
    {
        return $this->remote_whois_id
            && $this->synced_at
            && $this->synced_at->gte($this->updated_at);
    }

    public function phoneE164(): string
    {
        $cc = ltrim($this->phone_country_code, '+');
        $number = ltrim(preg_replace('/\D/', '', (string) $this->phone), '0');

        return '+' . $cc . $number;
    }
}
