<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LicenseActivity;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\ProductDevice;
use Illuminate\Http\Request;

/**
 * Generic Product License Controller
 *
 * API Controller ที่รองรับระบบ License สำหรับทุกผลิตภัณฑ์
 * ใช้ product slug เป็น route parameter เพื่อกำหนดผลิตภัณฑ์
 */
class ProductLicenseController extends Controller
{
    /**
     * Default pricing for products (can be overridden per product)
     */
    private const DEFAULT_PRICING = [
        'monthly' => [
            'original' => 299,
            'currency' => 'THB',
        ],
        'yearly' => [
            'original' => 1990,
            'currency' => 'THB',
        ],
        'lifetime' => [
            'original' => 4990,
            'currency' => 'THB',
        ],
    ];

    /**
     * Get product by slug or fail
     */
    private function getProduct(string $productSlug): ?Product
    {
        return Product::where('slug', $productSlug)
            ->where('requires_license', true)
            ->first();
    }

    /**
     * Register or update device
     */
    public function registerDevice(Request $request, string $productSlug)
    {
        $product = $this->getProduct($productSlug);
        if (! $product) {
            return response()->json([
                'success' => false,
                'error_code' => 'PRODUCT_NOT_FOUND',
                'message' => 'ไม่พบผลิตภัณฑ์',
            ], 404);
        }

        $validated = $request->validate([
            'machine_id' => 'required|string|min:32|max:64',
            'machine_name' => 'nullable|string|max:255',
            'os_version' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:50',
            'hardware_hash' => 'nullable|string|max:64',
        ]);

        $device = ProductDevice::updateOrCreate(
            [
                'product_id' => $product->id,
                'machine_id' => $validated['machine_id'],
            ],
            [
                'machine_name' => $validated['machine_name'] ?? null,
                'os_version' => $validated['os_version'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
                'hardware_hash' => $validated['hardware_hash'] ?? null,
                'last_ip' => $request->ip(),
                'first_ip' => $request->ip(),
                'last_seen_at' => now(),
                'first_seen_at' => now(),
            ]
        );

        // Check abuse if trying to start trial
        $abuseCheck = $device->checkTrialAbuse();

        return response()->json([
            'success' => true,
            'data' => [
                'device_status' => $device->status,
                'is_suspicious' => $device->is_suspicious,
                'can_start_trial' => $device->canStartTrial(),
                'trial_info' => $device->status === ProductDevice::STATUS_TRIAL ? [
                    'expires_at' => $device->trial_expires_at?->toISOString(),
                    'days_remaining' => $device->trialDaysRemaining(),
                    'is_expired' => $device->isTrialExpired(),
                ] : null,
                'early_bird' => $device->isEligibleForEarlyBird() ? [
                    'eligible' => true,
                    'discount_percent' => 20,
                    'days_remaining' => $device->trialDaysRemaining(),
                ] : ['eligible' => false],
                'abuse_warning' => $abuseCheck['is_abuse'] ? $abuseCheck['reasons'] : null,
            ],
        ]);
    }

    /**
     * Start demo/trial
     */
    public function startDemo(Request $request, string $productSlug)
    {
        $product = $this->getProduct($productSlug);
        if (! $product) {
            return response()->json([
                'success' => false,
                'error_code' => 'PRODUCT_NOT_FOUND',
                'message' => 'ไม่พบผลิตภัณฑ์',
            ], 404);
        }

        $validated = $request->validate([
            'machine_id' => 'required|string|min:32|max:64',
            'hardware_hash' => 'nullable|string|max:64',
        ]);

        // Get or create device
        $device = ProductDevice::firstOrCreate(
            [
                'product_id' => $product->id,
                'machine_id' => $validated['machine_id'],
            ],
            [
                'hardware_hash' => $validated['hardware_hash'] ?? null,
                'first_ip' => $request->ip(),
                'last_ip' => $request->ip(),
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]
        );

        // Update last seen
        $device->update([
            'last_ip' => $request->ip(),
            'last_seen_at' => now(),
        ]);

        // Check for abuse
        $abuseCheck = $device->checkTrialAbuse();
        if ($abuseCheck['is_abuse']) {
            $device->markSuspicious(implode('; ', $abuseCheck['reasons']));

            // Block if too many abuse indicators
            if (count($abuseCheck['reasons']) >= 2) {
                $device->block('Multiple abuse indicators detected');

                return response()->json([
                    'success' => false,
                    'error_code' => 'TRIAL_ABUSE_DETECTED',
                    'message' => 'ตรวจพบการใช้งาน Trial ไม่เหมาะสม',
                    'reasons' => $abuseCheck['reasons'],
                ], 403);
            }
        }

        // Check if can start trial
        if (! $device->canStartTrial()) {
            $reason = match (true) {
                $device->status === ProductDevice::STATUS_BLOCKED => 'DEVICE_BLOCKED',
                $device->status === ProductDevice::STATUS_LICENSED => 'ALREADY_LICENSED',
                $device->status === ProductDevice::STATUS_TRIAL && ! $device->isTrialExpired() => 'TRIAL_ACTIVE',
                $device->trial_attempts >= 3 => 'TOO_MANY_ATTEMPTS',
                default => 'TRIAL_NOT_AVAILABLE',
            };

            return response()->json([
                'success' => false,
                'error_code' => $reason,
                'message' => 'ไม่สามารถเริ่ม Trial ได้',
                'trial_info' => $device->status === ProductDevice::STATUS_TRIAL ? [
                    'expires_at' => $device->trial_expires_at?->toISOString(),
                    'days_remaining' => $device->trialDaysRemaining(),
                ] : null,
            ], 403);
        }

        // Start trial
        $trialDays = 7;
        if (! $device->startTrial($trialDays)) {
            return response()->json([
                'success' => false,
                'error_code' => 'TRIAL_START_FAILED',
                'message' => 'ไม่สามารถเริ่ม Trial ได้',
            ], 500);
        }

        // Create demo license
        $licenseKey = LicenseKey::generateDemoKey();
        $license = LicenseKey::create([
            'product_id' => $product->id,
            'license_key' => $licenseKey,
            'license_type' => LicenseKey::TYPE_DEMO,
            'status' => LicenseKey::STATUS_ACTIVE,
            'machine_id' => $validated['machine_id'],
            'activated_at' => now(),
            'expires_at' => now()->addDays($trialDays),
            'max_activations' => 1,
            'activations' => 1,
            'metadata' => [
                'product_slug' => $productSlug,
                'started_at' => now()->toISOString(),
                'ip' => $request->ip(),
            ],
        ]);

        // Link license to device
        $device->update(['license_id' => $license->id]);

        // Log trial creation
        LicenseActivity::log(
            $license,
            LicenseActivity::ACTION_CREATED,
            LicenseActivity::ACTOR_API,
            null,
            $validated['machine_id'],
            'เริ่มใช้งาน Trial',
            ['product_slug' => $productSlug, 'trial_days' => $trialDays]
        );

        LicenseActivity::log(
            $license,
            LicenseActivity::ACTION_ACTIVATED,
            LicenseActivity::ACTOR_API,
            null,
            $validated['machine_id'],
            'เปิดใช้งาน Trial',
            ['product_slug' => $productSlug]
        );

        return response()->json([
            'success' => true,
            'message' => "เริ่มใช้งาน Trial {$trialDays} วันสำเร็จ",
            'data' => [
                'license_key' => $licenseKey,
                'expires_at' => $license->expires_at->toISOString(),
                'days_remaining' => $trialDays,
                'features' => $this->getTrialFeatures($productSlug),
                'early_bird' => [
                    'eligible' => true,
                    'discount_percent' => 20,
                    'message' => "🎉 ซื้อตอนนี้ลด 20%! เหลือเวลาอีก {$trialDays} วัน",
                ],
            ],
        ]);
    }

    /**
     * Check demo status
     */
    public function checkDemo(Request $request, string $productSlug)
    {
        $product = $this->getProduct($productSlug);
        if (! $product) {
            return response()->json([
                'success' => false,
                'error_code' => 'PRODUCT_NOT_FOUND',
                'message' => 'ไม่พบผลิตภัณฑ์',
            ], 404);
        }

        $validated = $request->validate([
            'machine_id' => 'required|string|min:32|max:64',
        ]);

        $device = ProductDevice::where('product_id', $product->id)
            ->where('machine_id', $validated['machine_id'])
            ->first();

        if (! $device) {
            return response()->json([
                'success' => true,
                'data' => [
                    'has_used_demo' => false,
                    'can_start_demo' => true,
                ],
            ]);
        }

        $isTrialActive = $device->status === ProductDevice::STATUS_TRIAL && ! $device->isTrialExpired();

        return response()->json([
            'success' => true,
            'data' => [
                'has_used_demo' => $device->trial_attempts > 0,
                'can_start_demo' => $device->canStartTrial(),
                'is_trial_active' => $isTrialActive,
                'trial_info' => $device->trial_expires_at ? [
                    'expires_at' => $device->trial_expires_at->toISOString(),
                    'days_remaining' => $device->trialDaysRemaining(),
                    'is_expired' => $device->isTrialExpired(),
                ] : null,
                'early_bird' => $device->isEligibleForEarlyBird() ? [
                    'eligible' => true,
                    'discount_percent' => 20,
                    'days_remaining' => $device->trialDaysRemaining(),
                ] : ['eligible' => false],
            ],
        ]);
    }

    /**
     * Activate license
     */
    public function activate(Request $request, string $productSlug)
    {
        $product = $this->getProduct($productSlug);
        if (! $product) {
            return response()->json([
                'success' => false,
                'error_code' => 'PRODUCT_NOT_FOUND',
                'message' => 'ไม่พบผลิตภัณฑ์',
            ], 404);
        }

        $validated = $request->validate([
            'license_key' => 'required|string',
            'machine_id' => 'required|string|min:32|max:64',
            'machine_fingerprint' => 'required|string',
            'app_version' => 'nullable|string|max:50',
        ]);

        $licenseKey = strtoupper(trim($validated['license_key']));

        $license = LicenseKey::where('license_key', $licenseKey)
            ->where('product_id', $product->id)
            ->first();

        if (! $license) {
            return response()->json([
                'success' => false,
                'error_code' => 'INVALID_LICENSE',
                'message' => 'License key ไม่ถูกต้อง',
            ], 404);
        }

        if ($license->status === LicenseKey::STATUS_REVOKED) {
            return response()->json([
                'success' => false,
                'error_code' => 'LICENSE_REVOKED',
                'message' => 'License ถูกยกเลิกแล้ว',
            ], 403);
        }

        if ($license->isExpired()) {
            return response()->json([
                'success' => false,
                'error_code' => 'LICENSE_EXPIRED',
                'message' => 'License หมดอายุแล้ว',
            ], 403);
        }

        // Check if already activated on different machine
        if ($license->machine_id && $license->machine_id !== $validated['machine_id']) {
            if ($license->activations >= $license->max_activations) {
                return response()->json([
                    'success' => false,
                    'error_code' => 'MAX_ACTIVATIONS',
                    'message' => 'License ถูกใช้งานครบจำนวนเครื่องแล้ว',
                ], 403);
            }
        }

        // Activate
        if (! $license->activateOnMachine($validated['machine_id'], $validated['machine_fingerprint'])) {
            return response()->json([
                'success' => false,
                'error_code' => 'ACTIVATION_FAILED',
                'message' => 'ไม่สามารถเปิดใช้งาน License ได้',
            ], 500);
        }

        // Update device status
        $device = ProductDevice::updateOrCreate(
            [
                'product_id' => $product->id,
                'machine_id' => $validated['machine_id'],
            ],
            [
                'status' => ProductDevice::STATUS_LICENSED,
                'license_id' => $license->id,
                'last_ip' => $request->ip(),
                'last_seen_at' => now(),
                'app_version' => $validated['app_version'] ?? null,
            ]
        );

        // Log activation
        LicenseActivity::log(
            $license,
            LicenseActivity::ACTION_ACTIVATED,
            LicenseActivity::ACTOR_API,
            null,
            $validated['machine_id'],
            'เปิดใช้งาน License ผ่าน API',
            ['product_slug' => $productSlug, 'app_version' => $validated['app_version'] ?? null]
        );

        return response()->json([
            'success' => true,
            'message' => 'เปิดใช้งาน License สำเร็จ',
            'data' => [
                'license_key' => $license->license_key,
                'license_type' => $license->license_type,
                'expires_at' => $license->expires_at?->toISOString(),
                'days_remaining' => $license->daysRemaining(),
                'features' => $this->getFeaturesByType($productSlug, $license->license_type),
            ],
        ]);
    }

    /**
     * Validate license
     */
    public function validate(Request $request, string $productSlug)
    {
        $product = $this->getProduct($productSlug);
        if (! $product) {
            return response()->json([
                'success' => false,
                'error_code' => 'PRODUCT_NOT_FOUND',
                'message' => 'ไม่พบผลิตภัณฑ์',
            ], 404);
        }

        $validated = $request->validate([
            'license_key' => 'required|string',
            'machine_id' => 'required|string|min:32|max:64',
        ]);

        $licenseKey = strtoupper(trim($validated['license_key']));

        $license = LicenseKey::where('license_key', $licenseKey)
            ->where('product_id', $product->id)
            ->where('machine_id', $validated['machine_id'])
            ->first();

        if (! $license) {
            return response()->json([
                'success' => false,
                'is_valid' => false,
                'error_code' => 'INVALID_LICENSE',
                'message' => 'License ไม่ถูกต้องหรือไม่ตรงกับเครื่อง',
            ], 404);
        }

        // Update last validated
        $license->update(['last_validated_at' => now()]);

        // Update device last seen
        ProductDevice::where('product_id', $product->id)
            ->where('machine_id', $validated['machine_id'])
            ->update([
                'last_seen_at' => now(),
                'last_ip' => $request->ip(),
            ]);

        $isValid = $license->isValid();

        // Log validation
        LicenseActivity::log(
            $license,
            LicenseActivity::ACTION_VALIDATED,
            LicenseActivity::ACTOR_API,
            null,
            $validated['machine_id'],
            $isValid ? 'ตรวจสอบ License สำเร็จ' : 'ตรวจสอบ License ล้มเหลว',
            ['is_valid' => $isValid, 'product_slug' => $productSlug]
        );

        return response()->json([
            'success' => true,
            'is_valid' => $isValid,
            'data' => [
                'license_key' => $license->license_key,
                'license_type' => $license->license_type,
                'status' => $license->status,
                'expires_at' => $license->expires_at?->toISOString(),
                'days_remaining' => $license->daysRemaining(),
                'is_expired' => $license->isExpired(),
                'features' => $this->getFeaturesByType($productSlug, $license->license_type),
            ],
        ]);
    }

    /**
     * Deactivate license
     */
    public function deactivate(Request $request, string $productSlug)
    {
        $product = $this->getProduct($productSlug);
        if (! $product) {
            return response()->json([
                'success' => false,
                'error_code' => 'PRODUCT_NOT_FOUND',
                'message' => 'ไม่พบผลิตภัณฑ์',
            ], 404);
        }

        $validated = $request->validate([
            'license_key' => 'required|string',
            'machine_id' => 'required|string|min:32|max:64',
        ]);

        $licenseKey = strtoupper(trim($validated['license_key']));

        $license = LicenseKey::where('license_key', $licenseKey)
            ->where('product_id', $product->id)
            ->where('machine_id', $validated['machine_id'])
            ->first();

        if (! $license) {
            return response()->json([
                'success' => false,
                'error_code' => 'INVALID_LICENSE',
                'message' => 'License ไม่ถูกต้องหรือไม่ตรงกับเครื่อง',
            ], 404);
        }

        $previousMachineId = $license->machine_id;

        // Clear machine binding
        $license->update([
            'machine_id' => null,
            'machine_fingerprint' => null,
            'activations' => max(0, $license->activations - 1),
        ]);

        // Update device status
        ProductDevice::where('product_id', $product->id)
            ->where('machine_id', $validated['machine_id'])
            ->update([
                'status' => ProductDevice::STATUS_PENDING,
                'license_id' => null,
            ]);

        // Log deactivation
        LicenseActivity::log(
            $license,
            LicenseActivity::ACTION_DEACTIVATED,
            LicenseActivity::ACTOR_API,
            null,
            $previousMachineId,
            'ยกเลิกการเปิดใช้งาน License ผ่าน API',
            ['product_slug' => $productSlug, 'previous_machine_id' => $previousMachineId]
        );

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกการเปิดใช้งาน License สำเร็จ',
            'data' => [
                'license_key' => $license->license_key,
                'can_reactivate' => $license->activations < $license->max_activations,
            ],
        ]);
    }

    /**
     * Get license status
     */
    public function status(string $productSlug, string $licenseKey)
    {
        $product = $this->getProduct($productSlug);
        if (! $product) {
            return response()->json([
                'success' => false,
                'error_code' => 'PRODUCT_NOT_FOUND',
                'message' => 'ไม่พบผลิตภัณฑ์',
            ], 404);
        }

        $licenseKey = strtoupper(trim($licenseKey));

        $license = LicenseKey::where('license_key', $licenseKey)
            ->where('product_id', $product->id)
            ->first();

        if (! $license) {
            return response()->json([
                'success' => false,
                'error_code' => 'INVALID_LICENSE',
                'message' => 'ไม่พบ License key',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'license_key' => $license->license_key,
                'license_type' => $license->license_type,
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
        ]);
    }

    /**
     * Get pricing
     */
    public function pricing(string $productSlug)
    {
        $product = $this->getProduct($productSlug);
        if (! $product) {
            return response()->json([
                'success' => false,
                'error_code' => 'PRODUCT_NOT_FOUND',
                'message' => 'ไม่พบผลิตภัณฑ์',
            ], 404);
        }

        $pricing = $this->getPricingForProduct($productSlug);

        return response()->json([
            'success' => true,
            'data' => [
                'product' => [
                    'name' => $product->name,
                    'slug' => $product->slug,
                ],
                'plans' => [
                    'monthly' => [
                        'price' => $pricing['monthly']['original'],
                        'currency' => $pricing['monthly']['currency'],
                        'duration_days' => 30,
                        'features' => $this->getFeaturesByType($productSlug, 'monthly'),
                    ],
                    'yearly' => [
                        'price' => $pricing['yearly']['original'],
                        'currency' => $pricing['yearly']['currency'],
                        'duration_days' => 365,
                        'features' => $this->getFeaturesByType($productSlug, 'yearly'),
                    ],
                    'lifetime' => [
                        'price' => $pricing['lifetime']['original'],
                        'currency' => $pricing['lifetime']['currency'],
                        'duration_days' => null,
                        'features' => $this->getFeaturesByType($productSlug, 'lifetime'),
                    ],
                ],
            ],
        ]);
    }

    /**
     * Get features based on license type
     */
    private function getFeaturesByType(string $productSlug, string $type): array
    {
        // Default features - can be customized per product
        $baseFeatures = [
            'demo' => ['basic_features'],
            'monthly' => ['basic_features', 'standard_support'],
            'yearly' => ['basic_features', 'standard_support', 'priority_updates'],
            'lifetime' => ['all_features', 'priority_support', 'lifetime_updates'],
        ];

        return $baseFeatures[$type] ?? $baseFeatures['demo'];
    }

    /**
     * Get trial features
     */
    private function getTrialFeatures(string $productSlug): array
    {
        return ['basic_features', 'trial_mode'];
    }

    /**
     * Get pricing for product
     */
    private function getPricingForProduct(string $productSlug): array
    {
        // Can be customized per product from database or config
        return self::DEFAULT_PRICING;
    }
}
