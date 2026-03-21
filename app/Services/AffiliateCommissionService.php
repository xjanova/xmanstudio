<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AffiliateCommissionService
{
    /**
     * Resolve the active affiliate from session/cookie.
     * Returns null if no valid referral, self-referral, or suspended.
     */
    public function resolveAffiliate(?int $currentUserId = null): ?Affiliate
    {
        $ref = session('affiliate_ref') ?? request()->cookie('affiliate_ref');
        if (! $ref) {
            return null;
        }

        $affiliate = Affiliate::where('referral_code', $ref)
            ->where('status', 'active')
            ->first();

        if (! $affiliate) {
            return null;
        }

        // Prevent self-referral
        if ($currentUserId && $currentUserId === $affiliate->user_id) {
            return null;
        }

        return $affiliate;
    }

    /**
     * Record affiliate commission for any purchase type.
     *
     * @param  Affiliate|null  $affiliate  The affiliate that referred this purchase
     * @param  float  $paymentAmount  Actual amount paid (after discounts/fees)
     * @param  int|null  $orderId  Order ID (for order-based purchases, null for rentals)
     * @param  int|null  $referredUserId  The purchasing user
     * @param  string  $sourceType  'order', 'rental_payment', 'tping', 'autotradex'
     * @param  int|null  $sourceId  ID of the source record
     * @param  string  $sourceDescription  Human-readable description
     */
    public function recordCommission(
        ?Affiliate $affiliate,
        float $paymentAmount,
        ?int $orderId,
        ?int $referredUserId,
        string $sourceType = 'order',
        ?int $sourceId = null,
        string $sourceDescription = ''
    ): ?AffiliateCommission {
        if (! $affiliate || ! $affiliate->isActive()) {
            return null;
        }

        $commissionAmount = $affiliate->calculateCommission($paymentAmount);
        if ($commissionAmount <= 0) {
            return null;
        }

        $commission = DB::transaction(function () use ($affiliate, $orderId, $referredUserId, $paymentAmount, $commissionAmount, $sourceType, $sourceId, $sourceDescription) {
            $commission = AffiliateCommission::create([
                'affiliate_id' => $affiliate->id,
                'order_id' => $orderId,
                'referred_user_id' => $referredUserId,
                'order_amount' => $paymentAmount,
                'commission_rate' => $affiliate->commission_rate,
                'commission_amount' => $commissionAmount,
                'status' => 'pending',
                'source_type' => $sourceType,
                'source_id' => $sourceId ?? $orderId,
                'source_description' => $sourceDescription,
            ]);

            // Update affiliate counters atomically
            $affiliate->increment('total_referrals');
            $affiliate->increment('total_conversions');
            $affiliate->increment('total_earned', $commissionAmount);
            $affiliate->increment('total_pending', $commissionAmount);

            return $commission;
        });

        // Clear referral session (prevent double-counting)
        session()->forget('affiliate_ref');

        Log::info('Affiliate commission recorded', [
            'affiliate_id' => $affiliate->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId ?? $orderId,
            'payment_amount' => $paymentAmount,
            'commission_amount' => $commissionAmount,
        ]);

        return $commission;
    }

    /**
     * Clear referral tracking data from session.
     */
    public function clearReferral(): void
    {
        session()->forget('affiliate_ref');
    }
}
