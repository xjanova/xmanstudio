<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpnTrafficLog extends Model
{
    public $timestamps = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'network_id',
        'member_id',
        'action',
        'bytes',
        'ip_address',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'bytes' => 'integer',
        'created_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    public function network(): BelongsTo
    {
        return $this->belongsTo(VpnNetwork::class, 'network_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(VpnNetworkMember::class, 'member_id');
    }
}
