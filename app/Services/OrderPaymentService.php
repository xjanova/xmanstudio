<?php

namespace App\Services;

use App\Models\LicenseKey;
use App\Models\LicenseRenewal;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Approving or rejecting an order's payment by hand — from the admin order pages, or from the
 * buttons under an order card in the admin Telegram chat. One implementation, so a payment approved
 * from a phone issues exactly the licenses (and sends exactly the e-mail) the admin page would.
 *
 * approve/reject/setStatus moved out of Admin\OrderController::updatePaymentStatus unchanged,
 * including its order of steps (licenses are generated BEFORE the order row is updated);
 * confirmSmsOrder/rejectSmsOrder out of Admin\SmsPaymentController the same way.
 */
class OrderPaymentService
{
    public function __construct(private LicenseService $licenses) {}

    public function approve(Order $order, ?string $note = null): void
    {
        DB::transaction(function () use ($order, $note) {
            // Serialise approvals of one order (the admin page, the Telegram bot, a double-click):
            // two at once would each generate the "missing" licenses and each send the e-mail.
            $this->lock($order);

            // Generate license keys for products that require them
            $this->generateAndBindLicenses($order);

            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                // Completed once its licenses are out: issuing them just now completed it, and an
                // order approved again (after a mistaken rejection, say) still holds its first keys.
                // Writing processing over that sent the buyer's /download/{slug} page to "buy first".
                'status' => $order->status === 'completed' || $this->licensesIssued($order) ? 'completed' : 'processing',
            ] + $this->noteField($order, $note));
        });
    }

    public function reject(Order $order, ?string $note = null): void
    {
        $order->update(['payment_status' => 'rejected', 'status' => 'cancelled'] + $this->noteField($order, $note));
    }

    /**
     * Confirm an SMS-payment order (one with a unique transfer amount) by hand — what the SMS
     * Payment admin page does, moved here so the Telegram bot does the same: the SMS record is
     * marked confirmed and the SmsChecker phone app is told, not just the order.
     */
    public function confirmSmsOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $this->lock($order);
            $order->update([
                'sms_verification_status' => 'confirmed',
                'sms_verified_at' => now(),
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            if ($order->smsNotification) {
                $order->smsNotification->update(['status' => 'confirmed']);
            }

            // Auto-generate license keys for products that require them
            $this->licenses->generateLicensesForOrder($order);
        });

        // Send FCM push to Android app so it updates immediately
        try {
            app(FcmNotificationService::class)->notifyOrderApproved($order);
        } catch (\Exception $e) {
            Log::error('FCM: Failed to send order_approved push', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function rejectSmsOrder(Order $order, string $reason): void
    {
        $order->update([
            'sms_verification_status' => 'rejected',
            'payment_status' => 'failed',
            'notes' => ($order->notes ? $order->notes . "\n" : '') . '[SMS Rejected] ' . $reason,
        ]);

        if ($order->smsNotification) {
            $order->smsNotification->update(['status' => 'rejected']);
        }

        if ($order->uniquePaymentAmount) {
            $order->uniquePaymentAmount->cancel();
        }

        // Send FCM push to Android app so it updates immediately
        try {
            app(FcmNotificationService::class)->notifyOrderRejected($order);
        } catch (\Exception $e) {
            Log::error('FCM: Failed to send order_rejected push', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** Take the order's row lock for the rest of the current transaction, and read it fresh. */
    private function lock(Order $order): void
    {
        $fresh = Order::whereKey($order->getKey())->lockForUpdate()->first();
        if ($fresh) {
            $order->setRawAttributes($fresh->getAttributes(), true);
        }
    }

    /** Any other payment_status the admin page can set (back to pending / verifying). */
    public function setStatus(Order $order, string $status, ?string $note = null): void
    {
        $order->update(['payment_status' => $status] + $this->noteField($order, $note));
    }

    /** @return array{notes?:string} */
    private function noteField(Order $order, ?string $note): array
    {
        if (! $note) {
            return [];
        }

        return ['notes' => ($order->notes ? $order->notes . "\n" : '')
            . '[Admin] ' . $note . ' — ' . now()->format('d/m/Y H:i')];
    }

    /** Whether the order has delivered licenses: keys of its own, or time added to a key the buyer held. */
    private function licensesIssued(Order $order): bool
    {
        return LicenseKey::where('order_id', $order->id)->exists()
            || LicenseRenewal::where('order_id', $order->id)->exists();
    }

    /**
     * Generate license keys for order items and bind machine_id if available.
     */
    private function generateAndBindLicenses(Order $order): void
    {
        // Generate licenses via shared service (handles all product types)
        $this->licenses->generateLicensesForOrder($order);

        // Bind machine_id from order metadata (for LocalVPN and similar products)
        $metadata = $order->metadata ?? [];
        $machineId = $metadata['machine_id'] ?? null;

        if ($machineId) {
            $licenses = LicenseKey::where('order_id', $order->id)
                ->whereNull('machine_id')
                ->get();

            foreach ($licenses as $license) {
                $license->activateOnMachine($machineId, $machineId);
            }
        }
    }
}
