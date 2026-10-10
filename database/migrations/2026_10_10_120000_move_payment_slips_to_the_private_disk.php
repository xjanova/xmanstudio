<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Moves the payment slips already on the public disk to the private one, in the deploy that stops
 * writing them there (`payment-slips:privatize`, which logs what it moved). Production on
 * 2026-10-10 had a single file in storage/app/public/payment-slips — an AutoTradeX slip no order
 * points at any more — and no cart or rental slips.
 *
 * Never fails the deploy: a slip that cannot be moved stays where it is (admins still see it through
 * the slip route), the problem goes to the log, and the command can be run again by hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        // `migrate --pretend` must not move real files
        if (DB::pretending()) {
            return;
        }

        try {
            Artisan::call('payment-slips:privatize');
        } catch (Throwable $e) {
            Log::error('Payment slips could not be moved off the public disk', ['error' => $e->getMessage()]);
        }
    }

    public function down(): void
    {
        // Not undone: moving the slips back would publish them again
    }
};
