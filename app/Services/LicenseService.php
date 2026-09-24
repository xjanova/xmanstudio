<?php

namespace App\Services;

use App\Mail\PaymentConfirmedMail;
use App\Models\LicenseActivity;
use App\Models\LicenseKey;
use App\Models\LicenseRenewal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentSetting;
use App\Models\Product;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LicenseService
{
    /** License types that run for a fixed term — the ones a renewal can add to a key. */
    private const TERM_TYPES = [
        LicenseKey::TYPE_DAILY,
        LicenseKey::TYPE_WEEKLY,
        LicenseKey::TYPE_MONTHLY,
        LicenseKey::TYPE_YEARLY,
    ];

    /**
     * Generate license keys
     */
    public function generateLicenses(
        string $type,
        int $quantity = 1,
        int $maxActivations = 1,
        ?int $productId = null
    ): array {
        $licenses = [];

        for ($i = 0; $i < $quantity; $i++) {
            $key = LicenseKey::generateKey();

            $license = LicenseKey::create([
                'product_id' => $productId,
                'license_key' => $key,
                'license_type' => $type,
                'status' => LicenseKey::STATUS_ACTIVE,
                'max_activations' => $maxActivations,
                'activations' => 0,
                'metadata' => [
                    'generated_at' => now()->toISOString(),
                ],
            ]);

            $licenses[] = [
                'license_key' => $key,
                'type' => $type,
                'id' => $license->id,
            ];
        }

        return $licenses;
    }

    /**
     * Generate license keys for all items in an order that require them,
     * then send payment-confirmed email with license keys.
     *
     * A renewable product (config/licenses.php — BrainX Cloud) extends the key
     * the buyer already holds instead: see deliverRenewable().
     *
     * Shared method used by: OrderController, StripeWebhookController,
     * OrderPaymentService (admin order + SMS pages, Telegram bot),
     * Api\V1\SmsPaymentController, SmsPaymentNotification model.
     */
    public function generateLicensesForOrder(Order $order): void
    {
        // One delivery of an order at a time. A payment is often confirmed more than once, and
        // sometimes at the same moment (Stripe's webhook and the checkout page's own poll, an SMS
        // match and an admin click). The second delivery waits on the order row, then finds the
        // first one's keys and renewals and does nothing: no second key, no second month added to
        // a renewed key, no second e-mail.
        $generated = DB::transaction(function () use ($order) {
            Order::whereKey($order->getKey())->lockForUpdate()->first();

            $order->load('items.product');
            $generated = false;

            foreach ($order->items as $item) {
                if (! $item->product || ! $item->product->requires_license) {
                    continue;
                }

                // Yearly unless the product is bought outright (an avatar pack) or sold by
                // the month (BrainX Cloud) - see Product::defaultLicenseType(). An explicit
                // license_type on the order item still wins over this.
                $licenseType = $item->product->defaultLicenseType();
                $requirements = $this->requirementsOf($item);
                if (! empty($requirements['license_type'])) {
                    $licenseType = $requirements['license_type'];
                }

                if ($item->product->isRenewable() && in_array($licenseType, self::TERM_TYPES, true)) {
                    $generated = $this->deliverRenewable($order, $item, $licenseType) || $generated;

                    continue;
                }

                $existingCount = LicenseKey::where('order_id', $order->id)
                    ->where('product_id', $item->product_id)
                    ->count();

                if ($existingCount >= $item->quantity) {
                    continue;
                }

                $expiresAt = match ($licenseType) {
                    'daily' => now()->addDay(),
                    'weekly' => now()->addDays(7),
                    'monthly' => now()->addDays(30),
                    'yearly' => now()->addYear(),
                    'lifetime' => null,
                    default => now()->addYear(),
                };

                $toGenerate = $item->quantity - $existingCount;
                $licenses = $this->generateLicenses($licenseType, $toGenerate, 1, $item->product_id);

                foreach ($licenses as $license) {
                    LicenseKey::where('id', $license['id'])->update([
                        'order_id' => $order->id,
                        'user_id' => $order->user_id,
                        'expires_at' => $expiresAt,
                    ]);
                }

                $generated = true;
            }

            // Mark order as completed when licenses are generated
            if ($generated) {
                $order->update(['status' => 'completed']);
            }

            return $generated;
        });

        if ($generated && $order->customer_email && PaymentSetting::get('mail_enabled', true)) {
            try {
                Mail::to($order->customer_email)
                    ->send(new PaymentConfirmedMail($order->fresh(['items.product', 'user'])));
            } catch (\Exception $e) {
                Log::error('Failed to send payment confirmed email', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * The key a purchase of a renewable product would extend for this customer, or null when
     * it would issue a new one: no customer (a guest order), a product that is not renewable,
     * or no key of it that can still be renewed (revoked keys stay revoked).
     *
     * $preferredLicenseId is the key the customer pressed "renew" on; it wins when it is theirs
     * and renewable, otherwise the key with the latest expiry is the one extended.
     */
    public function renewalTargetFor(?int $userId, Product $product, mixed $preferredLicenseId = null): ?LicenseKey
    {
        return $this->findRenewalTarget($userId, $product, $preferredLicenseId, false);
    }

    /**
     * Deliver one order item of a renewable product: extend the key the buyer already holds
     * — the same key, so a BrainX Cloud account (a hash of the key) keeps its notes — or, with
     * nothing to extend, issue ONE key carrying the whole term. The item's quantity is the
     * number of terms bought either way (2 × monthly = 60 days), never a number of keys.
     *
     * The cart keeps one line per product, so an order holds at most one item of a product.
     *
     * @return bool whether this call delivered anything (false: an earlier delivery of the
     *              same order already did — a payment callback that arrived twice)
     */
    private function deliverRenewable(Order $order, OrderItem $item, string $licenseType): bool
    {
        // Already delivered: this order issued a key of the product, or this item extended one.
        // A key the admin has since deleted still counts — that is not a reason to issue another.
        $issuedBefore = LicenseKey::withTrashed()
            ->where('order_id', $order->id)
            ->where('product_id', $item->product_id)
            ->exists();

        if ($issuedBefore || LicenseRenewal::where('order_item_id', $item->id)->exists()) {
            return false;
        }

        $units = max(1, (int) $item->quantity);
        $target = $this->findRenewalTarget(
            $order->user_id,
            $item->product,
            $this->requirementsOf($item)['renew_license_id'] ?? null,
            true
        );

        if ($target) {
            return $this->extendLicense($target, $order, $item, $licenseType, $units);
        }

        // Nothing to extend: a first purchase, a guest order, or every earlier key revoked
        $license = $this->generateLicenses($licenseType, 1, 1, $item->product_id)[0];

        LicenseKey::where('id', $license['id'])->update([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'expires_at' => $this->addTerm(now(), $licenseType, $units),
        ]);

        return true;
    }

    /**
     * Add $units terms to a key: from its expiry while it is still running, from now once it
     * has lapsed — a customer who comes back late pays for time they get, not time they missed.
     */
    private function extendLicense(LicenseKey $license, Order $order, OrderItem $item, string $licenseType, int $units): bool
    {
        $previousExpiry = $license->expires_at;
        $from = $previousExpiry !== null && $previousExpiry->isFuture() ? $previousExpiry : now();
        $expiresAt = $this->addTerm($from, $licenseType, $units);

        try {
            DB::transaction(function () use ($license, $order, $item, $licenseType, $units, $previousExpiry, $from, $expiresAt) {
                // First, so a second delivery of this item stops on the unique order_item_id
                // before the key moves — even one that read the renewals table too early
                LicenseRenewal::create([
                    'license_key_id' => $license->id,
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'user_id' => $order->user_id,
                    'license_type' => $licenseType,
                    'units' => $units,
                    'days_added' => (int) round($from->diffInDays($expiresAt)),
                    'previous_expires_at' => $previousExpiry,
                    'expires_at' => $expiresAt,
                ]);

                $license->update([
                    'expires_at' => $expiresAt,
                    'status' => LicenseKey::STATUS_ACTIVE,
                ]);

                LicenseActivity::log(
                    $license,
                    LicenseActivity::ACTION_EXTENDED,
                    LicenseActivity::ACTOR_SYSTEM,
                    $order->user_id,
                    null,
                    "ต่ออายุจากคำสั่งซื้อ #{$order->order_number}",
                    [
                        'order_id' => $order->id,
                        'license_type' => $licenseType,
                        'units' => $units,
                        'previous_expiry' => $previousExpiry?->toISOString(),
                        'new_expiry' => $expiresAt->toISOString(),
                    ]
                );
            });
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }

    private function findRenewalTarget(?int $userId, Product $product, mixed $preferredLicenseId, bool $lock): ?LicenseKey
    {
        if (! $userId || ! $product->isRenewable()) {
            return null;
        }

        $keys = fn () => LicenseKey::query()
            ->where('user_id', $userId)
            ->where('product_id', $product->id)
            ->whereIn('status', [LicenseKey::STATUS_ACTIVE, LicenseKey::STATUS_EXPIRED])
            ->whereIn('license_type', self::TERM_TYPES)
            ->when($lock, fn ($query) => $query->lockForUpdate());

        if (is_numeric($preferredLicenseId)) {
            $preferred = $keys()->whereKey((int) $preferredLicenseId)->first();

            if ($preferred) {
                return $preferred;
            }
        }

        return $keys()->orderByDesc('expires_at')->orderByDesc('id')->first();
    }

    private function addTerm(CarbonInterface $from, string $licenseType, int $units): CarbonInterface
    {
        return match ($licenseType) {
            LicenseKey::TYPE_DAILY => $from->copy()->addDays($units),
            LicenseKey::TYPE_WEEKLY => $from->copy()->addDays(7 * $units),
            LicenseKey::TYPE_MONTHLY => $from->copy()->addDays(30 * $units),
            LicenseKey::TYPE_YEARLY => $from->copy()->addYears($units),
        };
    }

    /** @return array<string, mixed> what the cart recorded on the line (license_type, renew_license_id) */
    private function requirementsOf(OrderItem $item): array
    {
        $decoded = $item->custom_requirements ? json_decode($item->custom_requirements, true) : null;

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Activate license
     */
    public function activate(
        string $licenseKey,
        string $machineId,
        string $machineFingerprint,
        ?string $appVersion = null
    ): array {
        $licenseKey = strtoupper(trim($licenseKey));

        $license = LicenseKey::byKey($licenseKey)->first();

        if (! $license) {
            return [
                'success' => false,
                'error' => 'License key ไม่ถูกต้อง',
                'code' => 'INVALID_KEY',
            ];
        }

        if ($license->status === LicenseKey::STATUS_REVOKED) {
            return [
                'success' => false,
                'error' => 'License นี้ถูกยกเลิกแล้ว',
                'code' => 'REVOKED',
            ];
        }

        // Check if already activated on another machine
        if ($license->machine_id && $license->machine_id !== $machineId) {
            if ($license->activations >= $license->max_activations) {
                return [
                    'success' => false,
                    'error' => 'License นี้ถูกใช้งานครบจำนวนเครื่องแล้ว',
                    'code' => 'MACHINE_LIMIT',
                ];
            }
        }

        // Activate on machine
        if (! $license->activateOnMachine($machineId, $machineFingerprint)) {
            return [
                'success' => false,
                'error' => 'ไม่สามารถเปิดใช้งาน License ได้',
                'code' => 'ACTIVATION_FAILED',
            ];
        }

        // Update metadata
        $metadata = $license->metadata ?? [];
        $metadata['last_app_version'] = $appVersion;
        $metadata['last_activation_ip'] = request()->ip();
        $license->update(['metadata' => $metadata]);

        // Log activation
        LicenseActivity::log(
            $license,
            LicenseActivity::ACTION_ACTIVATED,
            LicenseActivity::ACTOR_API,
            null,
            $machineId,
            'เปิดใช้งานผ่าน API',
            ['app_version' => $appVersion]
        );

        return [
            'success' => true,
            'data' => [
                'license_key' => $license->license_key,
                'type' => $license->license_type,
                'expires_at' => $license->expires_at?->toISOString(),
                'days_remaining' => $license->daysRemaining(),
                'machine_id' => $license->machine_id,
            ],
            'message' => 'เปิดใช้งาน License สำเร็จ',
        ];
    }

    /**
     * Validate license
     */
    public function validate(string $licenseKey, string $machineId): array
    {
        $licenseKey = strtoupper(trim($licenseKey));

        $license = LicenseKey::byKey($licenseKey)
            ->byMachine($machineId)
            ->first();

        if (! $license) {
            return [
                'success' => false,
                'is_valid' => false,
                'error' => 'License ไม่ถูกต้องหรือไม่ตรงกับเครื่อง',
                'code' => 'INVALID',
            ];
        }

        $isValid = $license->isValid();

        // Update last validated
        $license->update(['last_validated_at' => now()]);

        // Log validation
        LicenseActivity::log(
            $license,
            LicenseActivity::ACTION_VALIDATED,
            LicenseActivity::ACTOR_API,
            null,
            $machineId,
            $isValid ? 'ตรวจสอบสำเร็จ' : 'ตรวจสอบล้มเหลว',
            ['is_valid' => $isValid]
        );

        return [
            'success' => true,
            'is_valid' => $isValid,
            'data' => [
                'license_key' => $license->license_key,
                'type' => $license->license_type,
                'status' => $license->status,
                'expires_at' => $license->expires_at?->toISOString(),
                'days_remaining' => $license->daysRemaining(),
                'is_expired' => $license->isExpired(),
            ],
        ];
    }

    /**
     * Start demo license
     */
    public function startDemo(string $machineId, string $machineFingerprint, string $productSlug = 'skidrow-killer'): array
    {
        // Find product by slug
        $product = Product::where('slug', $productSlug)->first();

        // Check if already has demo for this machine
        $existingDemo = LicenseKey::where('license_type', LicenseKey::TYPE_DEMO)
            ->byMachine($machineId)
            ->first();

        if ($existingDemo) {
            if ($existingDemo->isExpired()) {
                return [
                    'success' => false,
                    'error' => 'Demo หมดอายุแล้ว ไม่สามารถใช้ Demo ซ้ำได้',
                    'code' => 'DEMO_EXPIRED',
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'type' => 'demo',
                    'expires_at' => $existingDemo->expires_at->toISOString(),
                    'days_remaining' => $existingDemo->daysRemaining(),
                    'already_started' => true,
                ],
                'message' => 'คุณกำลังใช้งาน Demo อยู่แล้ว',
            ];
        }

        // Create new demo license
        $demoKey = LicenseKey::generateDemoKey();

        $license = LicenseKey::create([
            'product_id' => $product?->id,
            'license_key' => $demoKey,
            'machine_id' => $machineId,
            'machine_fingerprint' => Hash::make($machineFingerprint),
            'license_type' => LicenseKey::TYPE_DEMO,
            'status' => LicenseKey::STATUS_ACTIVE,
            'activated_at' => now(),
            'expires_at' => now()->addDays(3),
            'max_activations' => 1,
            'activations' => 1,
            'metadata' => [
                'demo_started_at' => now()->toISOString(),
                'ip' => request()->ip(),
                'product_slug' => $productSlug,
            ],
        ]);

        // Log demo creation and activation
        LicenseActivity::log(
            $license,
            LicenseActivity::ACTION_CREATED,
            LicenseActivity::ACTOR_API,
            null,
            $machineId,
            'สร้าง Demo License',
            ['product_slug' => $productSlug, 'demo_days' => 3]
        );

        LicenseActivity::log(
            $license,
            LicenseActivity::ACTION_ACTIVATED,
            LicenseActivity::ACTOR_API,
            null,
            $machineId,
            'เปิดใช้งาน Demo',
            ['product_slug' => $productSlug]
        );

        return [
            'success' => true,
            'data' => [
                'type' => 'demo',
                'expires_at' => $license->expires_at->toISOString(),
                'days_remaining' => 3,
                'already_started' => false,
            ],
            'message' => 'เริ่มใช้งาน Demo 3 วันสำเร็จ',
        ];
    }

    /**
     * Check demo status
     */
    public function checkDemo(string $machineId): array
    {
        $demo = LicenseKey::where('license_type', LicenseKey::TYPE_DEMO)
            ->byMachine($machineId)
            ->first();

        if (! $demo) {
            return [
                'has_used_demo' => false,
                'can_start_demo' => true,
            ];
        }

        return [
            'has_used_demo' => true,
            'can_start_demo' => false,
            'is_active' => $demo->isValid(),
            'expires_at' => $demo->expires_at?->toISOString(),
            'days_remaining' => $demo->daysRemaining(),
        ];
    }

    /**
     * Deactivate license from machine
     */
    public function deactivate(string $licenseKey, string $machineId): array
    {
        $licenseKey = strtoupper(trim($licenseKey));

        $license = LicenseKey::byKey($licenseKey)
            ->byMachine($machineId)
            ->first();

        if (! $license) {
            return [
                'success' => false,
                'error' => 'License ไม่ถูกต้องหรือไม่ตรงกับเครื่อง',
                'code' => 'INVALID',
            ];
        }

        $previousMachineId = $license->machine_id;

        // Clear machine info
        $license->update([
            'machine_id' => null,
            'machine_fingerprint' => null,
            'activations' => max(0, $license->activations - 1),
        ]);

        // Log deactivation
        LicenseActivity::log(
            $license,
            LicenseActivity::ACTION_DEACTIVATED,
            LicenseActivity::ACTOR_API,
            null,
            $previousMachineId,
            'ยกเลิกการเปิดใช้งานผ่าน API',
            ['previous_machine_id' => $previousMachineId]
        );

        return [
            'success' => true,
            'message' => 'ยกเลิกการเปิดใช้งาน License สำเร็จ',
            'data' => [
                'license_key' => $license->license_key,
                'can_reactivate' => $license->activations < $license->max_activations,
            ],
        ];
    }

    /**
     * Get license status
     */
    public function getStatus(string $licenseKey): array
    {
        $licenseKey = strtoupper(trim($licenseKey));

        $license = LicenseKey::byKey($licenseKey)->first();

        if (! $license) {
            return [
                'success' => false,
                'error' => 'License key ไม่ถูกต้อง',
                'code' => 'NOT_FOUND',
            ];
        }

        return [
            'success' => true,
            'data' => [
                'license_key' => $license->license_key,
                'type' => $license->license_type,
                'status' => $license->status,
                'is_valid' => $license->isValid(),
                'is_expired' => $license->isExpired(),
                'is_activated' => ! empty($license->machine_id),
                'activated_at' => $license->activated_at?->toISOString(),
                'expires_at' => $license->expires_at?->toISOString(),
                'days_remaining' => $license->daysRemaining(),
                'activations' => $license->activations,
                'max_activations' => $license->max_activations,
            ],
        ];
    }

    /**
     * Revoke license
     */
    public function revoke(string $licenseKey, ?string $reason = null): array
    {
        $licenseKey = strtoupper(trim($licenseKey));

        $license = LicenseKey::byKey($licenseKey)->first();

        if (! $license) {
            return [
                'success' => false,
                'error' => 'License key ไม่ถูกต้อง',
                'code' => 'NOT_FOUND',
            ];
        }

        $metadata = $license->metadata ?? [];
        $metadata['revoked_at'] = now()->toISOString();
        $metadata['revoked_reason'] = $reason;

        $license->update([
            'status' => LicenseKey::STATUS_REVOKED,
            'metadata' => $metadata,
        ]);

        // Log revocation
        LicenseActivity::log(
            $license,
            LicenseActivity::ACTION_REVOKED,
            LicenseActivity::ACTOR_ADMIN,
            auth()->id(),
            $license->machine_id,
            $reason ?? 'ยกเลิก License',
            ['reason' => $reason]
        );

        return [
            'success' => true,
            'message' => 'ยกเลิก License สำเร็จ',
        ];
    }
}
