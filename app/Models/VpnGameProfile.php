<?php

namespace App\Models;

use App\Services\LocalVpnFleet\GameProfiles;
use App\Support\LocalVpnFleet\Ipv4Cidr;
use Illuminate\Database\Eloquent\Model;

/**
 * A game's server ranges for the ping-booster exit. `cidrs` is the admin's
 * text, one CIDR per line ("# comments" allowed); it is validated on save.
 */
class VpnGameProfile extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'region',
        'cidrs',
        'probe_host',
        'enabled',
        'sort',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        // The public list and every node's allow-list are cached.
        static::saved(fn () => GameProfiles::forget());
        static::deleted(fn () => GameProfiles::forget());
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /** @return list<string> */
    public function cidrList(): array
    {
        return Ipv4Cidr::parseList((string) $this->cidrs)[0];
    }
}
