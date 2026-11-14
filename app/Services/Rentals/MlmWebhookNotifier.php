<?php

namespace App\Services\Rentals;

use App\Jobs\SendMlmWebhook;
use App\Models\RentalPackage;
use App\Models\UserRental;
use App\Models\UserRentalAddon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Turns a change to an MLM rental into one signed delivery for mlm.xman4289.com.
 *
 * Rentals of every other product are ignored here, so callers can report every change without
 * checking the product themselves. The payload is a snapshot taken now; the job only signs and
 * sends it (and is dispatched after the surrounding transaction commits — a rolled-back payment
 * announces nothing).
 *
 * Body:
 *   {"id","event","occurred_at",
 *    "rental":{"id","uuid","package_code","billing_cycle","starts_at","expires_at",
 *              "addons":[{"code","quantity","expires_at"}]},
 *    "user":{"id","email","name"},
 *    "tenant":{"reservation"}}
 */
class MlmWebhookNotifier
{
    public const RENTAL_ACTIVATED = 'rental.activated';

    public const RENTAL_RENEWED = 'rental.renewed';

    public const RENTAL_EXPIRED = 'rental.expired';

    public const RENTAL_CANCELLED = 'rental.cancelled';

    public const ADDON_ACTIVATED = 'addon.activated';

    /** @return string|null the delivery id, or null when nothing was queued */
    public function send(string $event, UserRental $rental): ?string
    {
        $package = $rental->rentalPackage()->withTrashed()->first();
        if (! $package || $package->product_key !== self::productKey()) {
            return null;
        }

        if ((string) config('services.mlm.webhook_url') === '' || (string) config('services.mlm.webhook_secret') === '') {
            Log::warning('[MLM webhook] MLM_WEBHOOK_URL / MLM_WEBHOOK_SECRET not set — event not sent', [
                'event' => $event,
                'rental_id' => $rental->id,
            ]);

            return null;
        }

        $deliveryId = (string) Str::uuid();
        SendMlmWebhook::dispatch($deliveryId, $event, $this->payload($event, $deliveryId, $rental->fresh() ?? $rental, $package))
            ->afterCommit();

        return $deliveryId;
    }

    public function payload(string $event, string $deliveryId, UserRental $rental, RentalPackage $package): array
    {
        $user = $rental->user()->first();
        $metadata = is_array($rental->metadata) ? $rental->metadata : [];

        return [
            'id' => $deliveryId,
            'event' => $event,
            'occurred_at' => now()->toIso8601String(),
            'rental' => [
                'id' => (int) $rental->id,
                'uuid' => (string) $rental->uuid,
                'package_code' => $package->code,
                'billing_cycle' => $package->billingCycle(),
                'starts_at' => $rental->starts_at?->toIso8601String(),
                'expires_at' => $rental->expires_at?->toIso8601String(),
                'addons' => $this->addons($rental),
            ],
            'user' => [
                'id' => (int) $rental->user_id,
                'email' => $user?->email,
                'name' => $user?->name,
            ],
            'tenant' => [
                'reservation' => isset($metadata['mlm_reservation']) ? (string) $metadata['mlm_reservation'] : null,
            ],
        ];
    }

    /** Add-ons in force on the rental, one entry per code (units of the same code add up). */
    private function addons(UserRental $rental): array
    {
        return $rental->addons()->where('status', UserRentalAddon::STATUS_ACTIVE)->with('addon')->get()
            ->filter(fn (UserRentalAddon $row) => $row->addon !== null)
            ->groupBy(fn (UserRentalAddon $row) => $row->addon->code)
            ->map(function ($rows, $code) {
                $latest = $rows->sortByDesc(fn ($row) => $row->expires_at?->getTimestamp() ?? 0)->first();

                return [
                    'code' => (string) $code,
                    'quantity' => (int) $rows->sum('quantity'),
                    'expires_at' => $latest->expires_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    public static function productKey(): string
    {
        return (string) config('services.mlm.product_key', 'mlm');
    }
}
