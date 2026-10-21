<?php

namespace App\Models;

use App\Mail\AffiliateCommissionMail;
use App\Services\GpuxMineReferralPayoutService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AffiliateCommission extends Model
{
    /**
     * จ่ายแล้วถูกแอดมินเรียกคืนจากกระเป๋า (R2-3 — ค่าแนะนำ GPUxMINE เท่านั้น ดู
     * GpuxMinePayoutReversalService) ต่างจาก 'rejected' ซึ่งแปลว่าไม่เคยจ่าย
     */
    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'affiliate_id',
        'order_id',
        'referred_user_id',
        'order_amount',
        'commission_rate',
        'commission_amount',
        'status',
        'wallet_transaction_id',
        'paid_at',
        'admin_note',
        'source_type',
        'source_id',
        'source_description',
    ];

    protected $casts = [
        'order_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    // ── Relationships ──

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    /** งาน GPUxMINE ที่ส่วนแบ่งผู้แนะนำรวมอยู่ในค่าแนะนำรายการนี้ */
    public function gpuEarnings(): HasMany
    {
        return $this->hasMany(GpuJobEarning::class, 'affiliate_commission_id');
    }

    /** แอดมินที่เรียกคืนค่าแนะนำนี้ (ถ้าถูกเรียกคืน) */
    public function reversedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    /** เรียกคืนได้: ค่าแนะนำ GPUxMINE ที่จ่ายแล้วและยังไม่ถูกเรียกคืน (ยอดพอหรือไม่ ตัดสินตอนกด) */
    public function canBeReversed(): bool
    {
        return $this->source_type === 'gpuxmine' && $this->status === 'paid' && $this->reversed_at === null;
    }

    // ── Scopes ──

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    // ── Helpers ──

    /**
     * Approve this commission (auto-pay to wallet).
     *
     * The row is claimed with a conditional UPDATE (pending → paid) inside the same
     * transaction as the wallet credit, so only one caller can ever pay it. Checking
     * $this->status alone was not enough: bulk approve loads every pending row up front,
     * and an admin who re-submitted after a proxy timeout had a second request paying the
     * same rows from its own stale copies. The e-mail goes out only after the commit.
     */
    public function approveAndPay(?int $adminId = null): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        // ค่าแนะนำ GPUxMINE เข้ากระเป๋าทางเดียวกับที่ระบบจ่ายเอง (R2-3): เลขรายการ GXA{id}
        // ไม่บวก total_deposited และไม่จ่ายเข้ากระเป๋าที่ถูกปิด — ไม่ผ่าน addBonus() ข้างล่าง
        if ($this->source_type === 'gpuxmine') {
            $paid = app(GpuxMineReferralPayoutService::class)->pay((int) $this->id);
            $this->refresh();

            if ($paid) {
                $this->sendNotificationEmail('paid');
            }

            return $paid;
        }

        $paid = DB::transaction(function () use ($adminId) {
            $claimed = static::whereKey($this->id)
                ->where('status', 'pending')
                ->update(['status' => 'paid', 'paid_at' => now()]);

            if ($claimed !== 1) {
                return false;
            }

            $affiliate = $this->affiliate;
            $wallet = Wallet::getOrCreateForUser($affiliate->user_id);

            // Build description based on source
            $description = $this->source_description
                ?? ($this->order ? "Order #{$this->order->order_number}" : "Commission #{$this->id}");

            // Add commission to wallet as bonus
            $transaction = $wallet->addBonus(
                $this->commission_amount,
                "ค่าแนะนำ Affiliate จาก {$description}",
                $adminId,
                [
                    'affiliate_commission_id' => $this->id,
                    'affiliate_id' => $affiliate->id,
                    'order_id' => $this->order_id,
                    'source_type' => $this->source_type,
                    'source_id' => $this->source_id,
                ]
            );

            static::whereKey($this->id)->update(['wallet_transaction_id' => $transaction->id]);

            // Update affiliate totals
            $affiliate->increment('total_paid', $this->commission_amount);
            $affiliate->decrement('total_pending', $this->commission_amount);

            return true;
        });

        // Either way the row has moved on — keep this copy in step with it
        $this->refresh();

        if (! $paid) {
            return false;
        }

        // Send email notification
        $this->sendNotificationEmail('paid');

        return true;
    }

    /**
     * Reject this commission.
     *
     * Same one-winner claim as approveAndPay(): a row another request has just paid or
     * rejected is left alone, and total_pending moves once.
     */
    public function reject(?string $reason = null): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $rejected = DB::transaction(function () use ($reason) {
            $claimed = static::whereKey($this->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected', 'admin_note' => $reason]);

            if ($claimed !== 1) {
                return false;
            }

            // The amount as stored now, not this copy's: a GPUxMINE commission still waiting can be cut
            // by a reversed job's share (GpuxMinePayoutReversalService) after the admin's page loaded
            // it, and total_pending has already moved by that cut. The row is ours under the claim.
            $amount = (string) static::whereKey($this->id)->value('commission_amount');
            $this->affiliate->decrement('total_pending', $amount);

            return true;
        });

        $this->refresh();

        if (! $rejected) {
            return false;
        }

        // Send email notification
        $this->sendNotificationEmail('rejected');

        return true;
    }

    /**
     * Send email notification to affiliate user.
     */
    protected function sendNotificationEmail(string $action): void
    {
        if (! PaymentSetting::get('mail_enabled', true)) {
            return;
        }

        $email = $this->affiliate->user->email ?? null;
        if (! $email) {
            return;
        }

        try {
            Mail::to($email)->send(new AffiliateCommissionMail(
                $this->load('affiliate.user.wallet'),
                $action
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send affiliate commission email', [
                'commission_id' => $this->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Source type label in Thai.
     */
    public function getSourceLabelAttribute(): string
    {
        return match ($this->source_type) {
            'tping' => 'Tping',
            'order' => 'สินค้า',
            'rental_payment' => 'Rental',
            'autotradex' => 'AutoTradeX',
            'gpuxmine' => 'GPUxMINE',
            default => $this->source_type ?? 'ไม่ระบุ',
        };
    }

    /**
     * Status label in Thai.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'รอตรวจสอบ',
            'approved' => 'อนุมัติแล้ว',
            'paid' => 'จ่ายแล้ว',
            'rejected' => 'ปฏิเสธ',
            self::STATUS_REVERSED => 'เรียกคืนแล้ว',
            default => $this->status,
        };
    }

    /**
     * Status color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'approved' => 'blue',
            'paid' => 'green',
            'rejected' => 'red',
            self::STATUS_REVERSED => 'gray',
            default => 'gray',
        };
    }
}
