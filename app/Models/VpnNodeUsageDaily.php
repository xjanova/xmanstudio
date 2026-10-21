<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One fleet node's counters for one day (Asia/Bangkok). Written by NodeSync. */
class VpnNodeUsageDaily extends Model
{
    protected $table = 'vpn_node_usage_daily';

    protected $fillable = [
        'node_id',
        'date',
        'nic_rx',
        'nic_tx',
        'relay_bytes',
        'exit_bytes',
        'peak_clients',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'nic_rx' => 'integer',
        'nic_tx' => 'integer',
        'relay_bytes' => 'integer',
        'exit_bytes' => 'integer',
        'peak_clients' => 'integer',
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(VpnNode::class, 'node_id');
    }
}
