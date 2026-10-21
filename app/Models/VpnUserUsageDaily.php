<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One device's relay or exit bytes for one day (Asia/Bangkok), across all nodes. */
class VpnUserUsageDaily extends Model
{
    public const KIND_RELAY = 'relay';

    public const KIND_EXIT = 'exit';

    protected $table = 'vpn_user_usage_daily';

    protected $fillable = [
        'date',
        'kind',
        'machine_id',
        'license_key_id',
        'bytes',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'bytes' => 'integer',
    ];

    public function licenseKey(): BelongsTo
    {
        return $this->belongsTo(LicenseKey::class);
    }
}
