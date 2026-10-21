<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One movement of money for a VPS: the first order, or a renewal.
 */
class VpsPayment extends Model
{
    public const KIND_ORDER = 'order';

    public const KIND_RENEW = 'renew';

    /** Debited from the wallet; the supplier has not confirmed yet. */
    public const STATUS_PENDING = 'pending';

    /** The supplier took the order — the money is spent upstream too. */
    public const STATUS_PAID = 'paid';

    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'vps_instance_id',
        'user_id',
        'kind',
        'status',
        'amount_thb',
        'cost_cents',
        'cost_currency',
        'months',
        'item_id',
        'previous_expires_at',
        'wallet_transaction_id',
        'refund_transaction_id',
        'idempotency_key',
        'remote_order_id',
        'last_error',
    ];

    protected $casts = [
        'amount_thb' => 'decimal:2',
        'cost_cents' => 'integer',
        'months' => 'integer',
        'previous_expires_at' => 'datetime',
    ];

    protected $hidden = ['last_error'];

    public function instance()
    {
        return $this->belongsTo(VpsInstance::class, 'vps_instance_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isRefunded(): bool
    {
        return $this->refund_transaction_id !== null;
    }
}
