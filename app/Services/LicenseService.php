<?php

namespace App\Services;

use App\Models\LicenseKey;
use App\Models\Product;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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

        if (!$license) {
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
        if (!$license->activateOnMachine($machineId, $machineFingerprint)) {
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

        if (!$license) {
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
    public function startDemo(string $machineId, string $machineFingerprint): array
    {
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
            ],
        ]);

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

        if (!$demo) {
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
}
