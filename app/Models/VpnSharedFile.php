<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VpnSharedFile extends Model
{
    protected $fillable = [
        'network_id',
        'owner_member_id',
        'file_hash',
        'file_name',
        'file_size',
        'chunk_size',
        'total_chunks',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'chunk_size' => 'integer',
        'total_chunks' => 'integer',
    ];

    public function network(): BelongsTo
    {
        return $this->belongsTo(VpnNetwork::class, 'network_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(VpnNetworkMember::class, 'owner_member_id');
    }

    public function seeders(): HasMany
    {
        return $this->hasMany(VpnFileSeeder::class, 'shared_file_id');
    }

    public function onlineSeeders(): HasMany
    {
        return $this->hasMany(VpnFileSeeder::class, 'shared_file_id')
            ->whereHas('member', fn ($q) => $q->where('is_online', true));
    }
}
