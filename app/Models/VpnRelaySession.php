<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpnRelaySession extends Model
{
    protected $fillable = [
        'network_id',
        'source_member_id',
        'target_member_id',
        'bytes_relayed',
        'started_at',
        'ended_at',
        'is_active',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_active' => 'boolean',
        'bytes_relayed' => 'integer',
    ];

    // ==================== Relationships ====================

    public function network(): BelongsTo
    {
        return $this->belongsTo(VpnNetwork::class, 'network_id');
    }

    public function sourceMember(): BelongsTo
    {
        return $this->belongsTo(VpnNetworkMember::class, 'source_member_id');
    }

    public function targetMember(): BelongsTo
    {
        return $this->belongsTo(VpnNetworkMember::class, 'target_member_id');
    }
}
