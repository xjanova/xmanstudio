<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Completes the paid orders left at "processing" after their licenses went out. Every "has this
     * customer bought it?" check (/download/{slug}, the CluadeX and Chanthra Studio pages, the
     * product page, the support form, the admin revenue figures) looks for a completed order, so
     * their buyers were told to buy what they already own.
     *
     * Two causes, both fixed alongside this: the Tping, SmsChecker and LocalVPN wallet checkouts
     * never completed their orders, and an admin approval wrote "processing" over the "completed"
     * that issuing the licenses had just set. Production on 2026-09-24: orders 9 and 10 (Tping,
     * wallet) and 12 (CluadeX, a wallet order placed just before the checkout completed orders).
     *
     * Only paid orders with proof of delivery are touched: a live key of their own, or time they
     * added to a key the buyer already held. A paid order with nothing issued is still waiting for
     * someone to deliver it and stays processing. Running this again changes nothing.
     */
    public function up(): void
    {
        $ids = DB::table('orders')
            ->where('status', 'processing')
            ->where('payment_status', 'paid')
            ->where(function (Builder $delivered) {
                $delivered
                    ->whereExists(fn (Builder $keys) => $keys->select(DB::raw(1))
                        ->from('license_keys')
                        ->whereColumn('license_keys.order_id', 'orders.id')
                        ->whereNull('license_keys.deleted_at'))
                    ->orWhereExists(fn (Builder $renewals) => $renewals->select(DB::raw(1))
                        ->from('license_renewals')
                        ->whereColumn('license_renewals.order_id', 'orders.id'));
            })
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('orders')->whereIn('id', $ids)->update(['status' => 'completed', 'updated_at' => now()]);

        Log::info('Completed paid orders left at processing with their licenses out', ['order_ids' => $ids->all()]);
    }

    public function down(): void
    {
        // Not undone: putting them back to processing would lock their buyers out again
    }
};
