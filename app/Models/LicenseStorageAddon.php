<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A paid order item that added cloud space to one of the buyer's keys — a pack
 * from config/licenses.php → cloud_storage.*.addons. The space stays with the
 * key for as long as the key lives; App\Support\CloudStorage adds it up.
 *
 * order_item_id is unique: one order item adds its space once, however many
 * times its payment is confirmed. See LicenseService::deliverStorageAddon().
 */
class LicenseStorageAddon extends Model
{
    protected $fillable = [
        'license_key_id',
        'order_id',
        'order_item_id',
        'user_id',
        'pack_gb',
        'units',
        'gb_added',
    ];

    protected $casts = [
        'pack_gb' => 'integer',
        'units' => 'integer',
        'gb_added' => 'integer',
    ];

    public function licenseKey(): BelongsTo
    {
        return $this->belongsTo(LicenseKey::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
