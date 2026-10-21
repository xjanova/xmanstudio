<?php

namespace App\Models;

use App\Services\TpixPay\TpixMath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One TPIX quote: "send exactly amount_tpix TPIX to receive_address before expires_at" for an order
 * or a wallet top-up, and what the chain said about it. Made and settled only by
 * App\Services\TpixPay\TpixPaymentService — nothing the browser sends is trusted, the chain is.
 *
 * Money columns are kept as strings (decimal casts would round-trip through float): amount_wei is
 * the exact integer the transfer's value is compared against.
 */
class TpixPayment extends Model
{
    public const STATUS_PENDING = 'pending';

    /** The transfer is on the chain and matches, waiting for enough confirmations. */
    public const STATUS_CONFIRMING = 'confirming';

    public const STATUS_PAID = 'paid';

    /** Paid, but what it was for had already been paid or cancelled: the THB went to the wallet. */
    public const STATUS_CREDITED_WALLET = 'credited_wallet';

    /** A matching transfer that arrived after the quote expired — parked for the admin, never auto-fulfilled. */
    public const STATUS_LATE = 'late';

    /**
     * Received and verified, but the wallet credit it would need is not automatic (no price to value
     * the coins right now, or over the customer's daily auto-credit cap) — waits for the admin.
     */
    public const STATUS_HELD = 'held';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_PENDING, self::STATUS_CONFIRMING, self::STATUS_PAID, self::STATUS_CREDITED_WALLET,
        self::STATUS_LATE, self::STATUS_HELD, self::STATUS_EXPIRED, self::STATUS_CANCELLED, self::STATUS_FAILED,
    ];

    public const PURPOSE_ORDER = 'order';

    public const PURPOSE_WALLET_TOPUP = 'wallet_topup';

    public const PURPOSES = [self::PURPOSE_ORDER, self::PURPOSE_WALLET_TOPUP];

    protected $fillable = [
        'reference',
        'user_id',
        'payable_type',
        'payable_id',
        'purpose',
        'amount_thb',
        'rate_thb',
        'price_usd',
        'usd_thb',
        'amount_wei',
        'amount_tpix',
        'receive_address',
        'status',
        'open_key',
        'tx_hash',
        'from_address',
        'block_number',
        'confirmations',
        'credited_thb',
        'submitted_at',
        'expires_at',
        'paid_at',
        'failure_reason',
        'meta',
    ];

    protected $casts = [
        'block_number' => 'integer',
        'confirmations' => 'integer',
        'submitted_at' => 'datetime',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Settled one way or the other: nothing more will happen to it on its own. */
    public function isSettled(): bool
    {
        return in_array($this->status, [self::STATUS_PAID, self::STATUS_CREDITED_WALLET], true);
    }

    /** Nothing on the chain changes it any more: delivered, credited, or received and waiting for the admin. */
    public function isFinal(): bool
    {
        return $this->isSettled() || $this->status === self::STATUS_HELD;
    }

    /** The customer may still send the amount (the scanner keeps looking past this, until the grace ends). */
    public function isPayable(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->expires_at?->isFuture();
    }

    /** The amount as the customer types it into a wallet: every significant digit, nothing more. */
    public function tpixDisplay(): string
    {
        // From the wei, the exact integer: a decimal column can come back from SQLite as a float
        $value = rtrim(rtrim(TpixMath::fromWei((string) $this->amount_wei), '0'), '.');

        return $value === '' ? '0' : $value;
    }

    /** EIP-681 payment link: wallets that scan it fill in the chain, the address and the exact amount. */
    public function paymentUri(): string
    {
        return 'ethereum:' . $this->receive_address . '@' . config('tpix_pay.chain_id') . '?value=' . $this->amount_wei;
    }

    public function explorerTxUrl(): ?string
    {
        return $this->tx_hash ? rtrim(config('tpix_pay.explorer_url'), '/') . '/tx/' . $this->tx_hash : null;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'รอชำระ',
            self::STATUS_CONFIRMING => 'รอยืนยันบนเชน',
            self::STATUS_PAID => 'ชำระแล้ว',
            self::STATUS_CREDITED_WALLET => 'เข้ากระเป๋าเงินแล้ว',
            self::STATUS_LATE => 'โอนหลังหมดเวลา (รอแอดมิน)',
            self::STATUS_HELD => 'ได้รับเหรียญแล้ว (รอแอดมินอนุมัติ)',
            self::STATUS_EXPIRED => 'หมดเวลา',
            self::STATUS_CANCELLED => 'ยกเลิก',
            self::STATUS_FAILED => 'ไม่สำเร็จ',
            default => $this->status,
        };
    }

    public function purposeLabel(): string
    {
        $payable = $this->payable;

        return match ($this->purpose) {
            self::PURPOSE_ORDER => 'คำสั่งซื้อ' . ($payable instanceof Order ? ' #' . $payable->order_number : ''),
            self::PURPOSE_WALLET_TOPUP => 'เติมเงิน Wallet' . ($payable instanceof WalletTopup ? ' #' . $payable->topup_id : ''),
            default => $this->purpose,
        };
    }
}
