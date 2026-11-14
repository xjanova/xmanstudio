<?php

namespace App\Services\Rentals;

use App\Models\RentalPackage;
use App\Models\RentalPayment;
use App\Models\UserRental;
use App\Models\UserRentalAddon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Everything that happens to a rental after money moves or time runs out, in one place — so the
 * Stripe webhook, the admin's "confirm", the Telegram bot and the payment page's self-heal all do
 * the same thing, and the MLM side hears about it exactly once.
 *
 * A payment says what it pays for in metadata.kind:
 *   initial (or absent — every payment made before this existed) → start the rental + its add-ons
 *   renewal → one more cycle for the rental and every add-on in force, at full price
 *   addon   → the add-ons bought part-way through the cycle
 */
class RentalLifecycle
{
    public const KIND_INITIAL = 'initial';

    public const KIND_RENEWAL = 'renewal';

    public const KIND_ADDON = 'addon';

    public function __construct(private MlmWebhookNotifier $webhooks) {}

    /**
     * A payment has cleared: do what it paid for — once. A second call (the Stripe webhook racing
     * the payment page, an admin double-click) finds metadata.settled_at and does nothing; without
     * that, a renewal would add a second cycle.
     */
    public function settle(RentalPayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $locked = RentalPayment::whereKey($payment->getKey())->lockForUpdate()->first();
            if (! $locked) {
                return;
            }

            $metadata = is_array($locked->metadata) ? $locked->metadata : [];
            if (! empty($metadata['settled_at'])) {
                return;
            }

            // The rental row is locked too: rentals:expire re-reads it under the same lock, so a
            // renewal landing in the same second cannot be overwritten with "expired".
            $rental = UserRental::whereKey($locked->user_rental_id)->lockForUpdate()->first();
            if ($rental) {
                match ($metadata['kind'] ?? self::KIND_INITIAL) {
                    self::KIND_RENEWAL => $this->applyRenewal($rental, $locked, $metadata),
                    self::KIND_ADDON => $this->applyAddonPurchase($rental, $locked),
                    default => $this->applyFirstPayment($rental, $locked),
                };
            }

            $metadata['settled_at'] = now()->toIso8601String();
            $locked->forceFill(['metadata' => $metadata])->save();

            $payment->forceFill(['metadata' => $metadata]);
            $payment->syncOriginalAttribute('metadata');
        });
    }

    /** A rental that cost nothing (a 100% promo) started at checkout without a payment. */
    public function startedFree(UserRental $rental): void
    {
        $this->activateAddons($rental, $rental->addons()->where('status', UserRentalAddon::STATUS_PENDING)->get());
        $this->webhooks->send(MlmWebhookNotifier::RENTAL_ACTIVATED, $rental);
    }

    /** The rental was cancelled (by its owner, or its first payment was refused). */
    public function cancelled(UserRental $rental): void
    {
        $rental->addons()
            ->whereIn('status', [UserRentalAddon::STATUS_PENDING, UserRentalAddon::STATUS_ACTIVE])
            ->update(['status' => UserRentalAddon::STATUS_CANCELLED, 'cancelled_at' => now()]);

        $this->webhooks->send(MlmWebhookNotifier::RENTAL_CANCELLED, $rental);
    }

    /**
     * The rental ran out. Its add-ons stay attached: renewing an expired rental brings them back
     * with it, and the MLM side reads "rental expired" as everything stopping.
     */
    public function expired(UserRental $rental): void
    {
        $this->webhooks->send(MlmWebhookNotifier::RENTAL_EXPIRED, $rental);
    }

    /** An admin moved the expiry (extend). Add-ons follow it; the MLM side sees a renewal. */
    public function extended(UserRental $rental): void
    {
        $this->syncAddonExpiry($rental);
        $this->webhooks->send(MlmWebhookNotifier::RENTAL_RENEWED, $rental);
    }

    /**
     * A payment that will not clear (refused by an admin, cancelled): the add-ons it was buying are
     * off. Whether the rental itself goes too is the caller's call — only a first payment's does.
     */
    public function paymentFailed(RentalPayment $payment): void
    {
        UserRentalAddon::where('rental_payment_id', $payment->id)
            ->where('status', UserRentalAddon::STATUS_PENDING)
            ->update(['status' => UserRentalAddon::STATUS_CANCELLED, 'cancelled_at' => now()]);
    }

    public static function kindOf(RentalPayment $payment): string
    {
        $metadata = is_array($payment->metadata) ? $payment->metadata : [];

        return (string) ($metadata['kind'] ?? self::KIND_INITIAL);
    }

    private function applyFirstPayment(UserRental $rental, RentalPayment $payment): void
    {
        // false when the rental is no longer pending — cancelled before the money came, or already
        // started. Same as before add-ons existed.
        if (! $rental->activate()) {
            return;
        }

        $this->activateAddons($rental, $rental->addons()
            ->where('rental_payment_id', $payment->id)
            ->where('status', UserRentalAddon::STATUS_PENDING)
            ->get());

        $this->webhooks->send(MlmWebhookNotifier::RENTAL_ACTIVATED, $rental);
    }

    private function applyRenewal(UserRental $rental, RentalPayment $payment, array $metadata): void
    {
        $package = isset($metadata['package_id'])
            ? RentalPackage::withTrashed()->find((int) $metadata['package_id'])
            : null;

        if (! $rental->renew($package ?? $rental->rentalPackage)) {
            // Paid for, but the rental was cancelled or suspended meanwhile: an admin has to decide.
            Log::warning('Rental renewal paid for a rental that cannot renew', [
                'rental_id' => $rental->id,
                'status' => $rental->status,
                'payment_id' => $payment->id,
            ]);

            return;
        }

        $this->syncAddonExpiry($rental);
        $this->webhooks->send(MlmWebhookNotifier::RENTAL_RENEWED, $rental);
    }

    private function applyAddonPurchase(UserRental $rental, RentalPayment $payment): void
    {
        $rows = $rental->addons()
            ->where('rental_payment_id', $payment->id)
            ->where('status', UserRentalAddon::STATUS_PENDING)
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $this->activateAddons($rental, $rows);
        $this->webhooks->send(MlmWebhookNotifier::ADDON_ACTIVATED, $rental);
    }

    private function activateAddons(UserRental $rental, $rows): void
    {
        foreach ($rows as $row) {
            $row->update([
                'status' => UserRentalAddon::STATUS_ACTIVE,
                'starts_at' => now(),
                'expires_at' => $rental->expires_at,
            ]);
        }
    }

    private function syncAddonExpiry(UserRental $rental): void
    {
        $rental->addons()->where('status', UserRentalAddon::STATUS_ACTIVE)
            ->update(['expires_at' => $rental->expires_at]);
    }
}
