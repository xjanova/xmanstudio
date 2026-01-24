<?php

namespace App\Services;

use App\Models\LicenseActivity;
use App\Models\LicenseKey;
use Illuminate\Support\Facades\Hash;

class LicenseService
{
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
        $product = \App\Models\Product::where('slug', $productSlug)->first();

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
