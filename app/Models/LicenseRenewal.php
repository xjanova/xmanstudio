<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A paid order item that extended a key the customer already held, instead of
 * issuing a new one (a renewable product — config/licenses.php).
 *
 * order_item_id is unique: one order item extends a key once, however many
 * times its payment is confirmed. See LicenseService::generateLicensesForOrder().
 */
class LicenseRenewal extends Model
{
    protected $fillable = [
        'license_key_id',
        'order_id',
        'order_item_id',
        'user_id',
        'license_type',
        'units',
        'days_added',
        'previous_expires_at',
        'expires_at',
    ];

    protected $casts = [
        'units' => 'integer',
        'days_added' => 'integer',
        'previous_expires_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function licenseKey(): BelongsTo
    {
        return $this->belongsTo(LicenseKey::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
