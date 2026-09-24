<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BugReport;
use App\Models\LicenseActivity;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\ProductDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            'original' => 399,
            'currency' => 'THB',
        ],
        'yearly' => [
            'original' => 2500,
            'currency' => 'THB',
        ],
        'lifetime' => [
            'original' => 5000,
            'currency' => 'THB',
        ],
    ];

    /** อายุของแต่ละแผน (วัน) ที่ pricing() บอกแอป — null = ใช้ได้ตลอด */
    private const PLAN_DURATION_DAYS = [
        'monthly' => 30,
        'yearly' => 365,
        'lifetime' => null,
    ];

    /**
     * ระยะทดลองใช้ (วัน) รายผลิตภัณฑ์ — ตัวที่ไม่อยู่ในนี้ได้ 1 วัน (24 ชม.) เท่าเดิม
     */
    private const TRIAL_DAYS = [
        // แอปบอกลูกค้าไว้ว่าทดลอง Pro ได้ 48 ชั่วโมง
        'winx-tools' => 2,
    ];

    /**
     * ทดลองได้ครั้งเดียวต่อฮาร์ดแวร์ ไม่ใช่ต่อ machine_id
     *
     * machine_id ของ WinXTools มาจาก MachineGuid ของ Windows — ลง Windows ใหม่ได้ machine_id ใหม่
     * = เครื่องใหม่ = ทดลองใหม่ได้อีกรอบ แต่ hardware_hash (SMBIOS UUID + serial ของบอร์ด/เครื่อง)
     * ยังเท่าเดิม จึงใช้ตัวนี้ผูกสิทธิ์ทดลองแทน
     *
     * เฉพาะผลิตภัณฑ์ในรายการนี้ — แอป Android บางตัวส่ง hash ระดับรุ่นเครื่อง (ทุกเครื่องรุ่นเดียวกัน
     * ได้ค่าเดียวกัน) ถ้าใช้กฎนี้กับทุกตัวจะตัดสิทธิ์ลูกค้าจริงจำนวนมาก
     */
    private const HARDWARE_BOUND_TRIAL_PRODUCTS = [
        'winx-tools',
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
            'drm_id' => 'nullable|string|max:128',
            'android_id' => 'nullable|string|max:64',
            'force_rebind' => 'nullable|string',
        ]);

        $deviceData = [
            'machine_name' => $validated['machine_name'] ?? null,
            'os_version' => $validated['os_version'] ?? null,
            'app_version' => $validated['app_version'] ?? null,
            'hardware_hash' => $validated['hardware_hash'] ?? null,
            'last_ip' => $request->ip(),
            'first_ip' => $request->ip(),
            'last_seen_at' => now(),
            'first_seen_at' => now(),
        ];
        if (! empty($validated['drm_id'])) {
            $deviceData['drm_id'] = $validated['drm_id'];
        }
        if (! empty($validated['android_id'])) {
            $deviceData['android_id'] = $validated['android_id'];
        }

        $device = ProductDevice::updateOrCreate(
            [
                'product_id' => $product->id,
                'machine_id' => $validated['machine_id'],
            ],
            $deviceData
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
            'drm_id' => 'nullable|string|max:128',
        ]);

        // Get or create device
        $createData = [
            'hardware_hash' => $validated['hardware_hash'] ?? null,
            'first_ip' => $request->ip(),
            'last_ip' => $request->ip(),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ];
        if (! empty($validated['drm_id'])) {
            $createData['drm_id'] = $validated['drm_id'];
        }
        $device = ProductDevice::firstOrCreate(
            [
                'product_id' => $product->id,
                'machine_id' => $validated['machine_id'],
            ],
            $createData
        );

        // Update last seen + drm_id
        $updateData = [
            'last_ip' => $request->ip(),
            'last_seen_at' => now(),
        ];
        if (! empty($validated['drm_id']) && ! $device->drm_id) {
            $updateData['drm_id'] = $validated['drm_id'];
        }
        $device->update($updateData);

        // ฮาร์ดแวร์นี้เคยทดลองแล้วในชื่อเครื่องอื่น (เช่นลง Windows ใหม่) — ไม่ให้ทดลองซ้ำ
        // ตรวจก่อน abuse check และไม่ติดธง suspicious/blocked: ลูกค้าที่ลงเครื่องใหม่ไม่ได้โกง แค่ใช้สิทธิ์ไปแล้ว
        if ($this->trialUsedOnThisHardware($product, $device, $validated['hardware_hash'] ?? null)) {
            return response()->json([
                'success' => false,
                'server_time' => now()->toISOString(),
                'error_code' => 'TRIAL_USED_ON_THIS_HARDWARE',
                'message' => 'เครื่องนี้เคยใช้สิทธิ์ทดลองใช้ไปแล้ว',
            ], 403);
        }

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
                'server_time' => now()->toISOString(),
                'error_code' => $reason,
                'message' => 'ไม่สามารถเริ่ม Trial ได้',
                'trial_info' => $device->status === ProductDevice::STATUS_TRIAL ? [
                    'expires_at' => $device->trial_expires_at?->toISOString(),
                    'days_remaining' => $device->trialDaysRemaining(),
                    'hours_remaining' => $device->trialHoursRemaining(),
                    'seconds_remaining' => $device->trialSecondsRemaining(),
                ] : null,
            ], 403);
        }

        // Start trial — 24 ชม. เว้นแต่ผลิตภัณฑ์กำหนดไว้เองใน TRIAL_DAYS
        $trialDays = self::TRIAL_DAYS[$product->slug] ?? 1;
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
            'server_time' => now()->toISOString(),
            'message' => "เริ่มใช้งาน Trial {$trialDays} วันสำเร็จ",
            'data' => [
                'license_key' => $licenseKey,
                'expires_at' => $license->expires_at->toISOString(),
                'days_remaining' => $trialDays,
                'hours_remaining' => $trialDays * 24,
                'seconds_remaining' => $trialDays * 24 * 3600,
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
            'drm_id' => 'nullable|string|max:128',
        ]);

        $drmId = $validated['drm_id'] ?? null;

        // Primary: lookup by machine_id
        $device = ProductDevice::where('product_id', $product->id)
            ->where('machine_id', $validated['machine_id'])
            ->first();

        // Fallback: lookup by drm_id (handles HWID migration after reinstall/update)
        if (! $device && $drmId) {
            $device = ProductDevice::where('product_id', $product->id)
                ->where('drm_id', $drmId)
                ->first();

            // Migrate machine_id if found via drm_id
            if ($device) {
                $device->update(['machine_id' => $validated['machine_id']]);
            }
        }

        // ฮาร์ดแวร์นี้เคยทดลองแล้วในชื่อเครื่องอื่น → บอกแอปตั้งแต่ตรงนี้ว่าใช้สิทธิ์ไปแล้ว ไม่ต้องยิง /demo
        // (แอปไม่ต้องส่ง hardware_hash มาที่นี่ก็ได้ ใช้ค่าที่ register-device เก็บไว้กับเครื่อง)
        $usedOnThisHardware = $this->trialUsedOnThisHardware($product, $device, $request->input('hardware_hash'));

        if (! $device) {
            return response()->json([
                'success' => true,
                'data' => [
                    'has_used_demo' => $usedOnThisHardware,
                    'can_start_demo' => ! $usedOnThisHardware,
                ],
            ]);
        }

        $isTrialActive = $device->status === ProductDevice::STATUS_TRIAL && ! $device->isTrialExpired();

        return response()->json([
            'success' => true,
            'server_time' => now()->toISOString(),
            'data' => [
                'has_used_demo' => $device->trial_attempts > 0 || $usedOnThisHardware,
                'can_start_demo' => $device->canStartTrial() && ! $usedOnThisHardware,
                'is_trial_active' => $isTrialActive,
                'trial_info' => $device->trial_expires_at ? [
                    'expires_at' => $device->trial_expires_at->toISOString(),
                    'days_remaining' => $device->trialDaysRemaining(),
                    'hours_remaining' => $device->trialHoursRemaining(),
                    'seconds_remaining' => $device->trialSecondsRemaining(),
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
     * สิทธิ์ทดลองของฮาร์ดแวร์นี้ถูกใช้ไปแล้วบนเครื่องอื่น (machine_id อื่น) หรือยัง
     *
     * เฉพาะผลิตภัณฑ์ใน HARDWARE_BOUND_TRIAL_PRODUCTS — ตัวอื่นได้ false เสมอ (พฤติกรรมเดิม)
     * ใช้ hardware_hash ที่ส่งมากับคำขอก่อน ไม่มีค่อยใช้ค่าที่เครื่องนี้ลงทะเบียนไว้ ไม่มีทั้งคู่ = ไม่ตัดสิทธิ์
     * (แอปส่ง null เมื่อ firmware เป็นค่าขยะ ดีกว่าให้หลายเครื่องได้ hash เดียวกันแล้วโดนตัดสิทธิ์พร้อมกัน)
     */
    private function trialUsedOnThisHardware(Product $product, ?ProductDevice $device, mixed $hardwareHash): bool
    {
        if (! in_array($product->slug, self::HARDWARE_BOUND_TRIAL_PRODUCTS, true)) {
            return false;
        }

        if (! is_string($hardwareHash) || $hardwareHash === '' || strlen($hardwareHash) > 64) {
            $hardwareHash = $device?->hardware_hash;
        }

        if (empty($hardwareHash)) {
            return false;
        }

        return ProductDevice::where('product_id', $product->id)
            ->where('hardware_hash', $hardwareHash)
            ->when($device, fn ($query) => $query->where('id', '!=', $device->id))
            ->where(function ($query) {
                $query->whereNotNull('first_trial_at')
                    ->orWhere('trial_attempts', '>', 0);
            })
            ->exists();
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
            'drm_id' => 'nullable|string|max:128',
            'android_id' => 'nullable|string|max:64',
            'force_rebind' => 'nullable|string',
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
        $isRebind = false;
        $previousMachineId = null;
        if ($license->machine_id && $license->machine_id !== $validated['machine_id']) {
            $requestDrmId = $validated['drm_id'] ?? null;

            // Determine if this is the SAME physical device (HWID migration)
            // or a DIFFERENT device trying to steal the license
            $isSameDevice = false;

            // Check 1: drm_id matches — same physical device, HWID hash just changed
            if ($requestDrmId && $license->drm_id && $requestDrmId === $license->drm_id) {
                $isSameDevice = true;
            }

            // Check 2: drm_id matches via device record (fallback)
            if (! $isSameDevice && $requestDrmId) {
                $existingDevice = ProductDevice::where('product_id', $product->id)
                    ->where('drm_id', $requestDrmId)
                    ->where('license_id', $license->id)
                    ->first();
                if ($existingDevice) {
                    $isSameDevice = true;
                }
            }

            if ($isSameDevice) {
                // Same device, HWID migration — allow re-bind
                $isRebind = true;
                $previousMachineId = $license->machine_id;

                // Clear old machine binding first
                $license->update([
                    'machine_id' => null,
                    'machine_fingerprint' => null,
                    'activations' => max(0, $license->activations - 1),
                ]);

                // Update old device record to point to new machine_id
                ProductDevice::where('product_id', $product->id)
                    ->where('machine_id', $previousMachineId)
                    ->update([
                        'machine_id' => $validated['machine_id'],
                    ]);
            } else {
                // DIFFERENT device - check if force_rebind is requested
                $forceRebind = filter_var($validated['force_rebind'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if (! $forceRebind) {
                    return response()->json([
                        'success' => false,
                        'error_code' => 'ALREADY_ACTIVATED_OTHER_DEVICE',
                        'message' => 'License นี้ถูกใช้งานบนเครื่องอื่นแล้ว',
                    ], 403);
                }

                // Force rebind - deactivate old device, activate on new
                $isRebind = true;
                $previousMachineId = $license->machine_id;

                $license->update([
                    'machine_id' => null,
                    'machine_fingerprint' => null,
                    'activations' => max(0, $license->activations - 1),
                ]);

                ProductDevice::where('product_id', $product->id)
                    ->where('machine_id', $previousMachineId)
                    ->update(['status' => ProductDevice::STATUS_EXPIRED]);

                LicenseActivity::log(
                    $license,
                    LicenseActivity::ACTION_MACHINE_RESET,
                    LicenseActivity::ACTOR_API,
                    null,
                    $validated['machine_id'],
                    'Force rebind: ย้ายเครื่องโดยผู้ใช้',
                    ['previous_machine_id' => $previousMachineId]
                );
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

        // Store drm_id + android_id on license for future cross-HWID lookups
        $drmId = $validated['drm_id'] ?? null;
        $androidId = $validated['android_id'] ?? null;
        $licenseUpdate = [];
        if ($drmId) {
            $licenseUpdate['drm_id'] = $drmId;
        }
        if ($androidId) {
            $licenseUpdate['android_id'] = $androidId;
        }
        if ($licenseUpdate) {
            $license->update($licenseUpdate);
        }

        // Update device status
        $deviceData = [
            'status' => ProductDevice::STATUS_LICENSED,
            'license_id' => $license->id,
            'last_ip' => $request->ip(),
            'last_seen_at' => now(),
            'app_version' => $validated['app_version'] ?? null,
        ];
        if ($drmId) {
            $deviceData['drm_id'] = $drmId;
        }
        if ($androidId) {
            $deviceData['android_id'] = $androidId;
        }
        $device = ProductDevice::updateOrCreate(
            [
                'product_id' => $product->id,
                'machine_id' => $validated['machine_id'],
            ],
            $deviceData
        );

        // Log activation (include rebind info if applicable)
        $logMessage = $isRebind
            ? 'Re-bind License ไปเครื่องใหม่ (HWID migration)'
            : 'เปิดใช้งาน License ผ่าน API';

        LicenseActivity::log(
            $license,
            LicenseActivity::ACTION_ACTIVATED,
            LicenseActivity::ACTOR_API,
            null,
            $validated['machine_id'],
            $logMessage,
            array_filter([
                'product_slug' => $productSlug,
                'app_version' => $validated['app_version'] ?? null,
                'is_rebind' => $isRebind ?: null,
                'previous_machine_id' => $previousMachineId,
            ])
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
     * Check if a machine already has an active license.
     * Used by the app on first launch to auto-activate without entering a key.
     */
    public function checkMachine(Request $request, string $productSlug)
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
            'drm_id' => 'nullable|string|max:128',
            'android_id' => 'nullable|string|max:64',
            'force_rebind' => 'nullable|string',
        ]);

        $drmId = $validated['drm_id'] ?? null;
        $androidId = $validated['android_id'] ?? null;
        $migratedFrom = null; // 'drm_id' | 'android_id' — which fallback matched

        // ── Primary: lookup by machine_id ──────────────────────────────────
        $license = LicenseKey::where('product_id', $product->id)
            ->where('machine_id', $validated['machine_id'])
            ->where('status', LicenseKey::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        // ── Fallback 1: search by drm_id (MediaDrm/Widevine) ───────────────
        // Handles HWID migration (e.g. hardware hash formula change after update)
        if (! $license && $drmId) {
            // Check license_keys.drm_id
            $license = LicenseKey::where('product_id', $product->id)
                ->where('drm_id', $drmId)
                ->where('status', LicenseKey::STATUS_ACTIVE)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->first();

            // Check product_devices.drm_id
            if (! $license) {
                $device = ProductDevice::where('product_id', $product->id)
                    ->where('drm_id', $drmId)
                    ->whereNotNull('license_id')
                    ->first();
                if ($device) {
                    $license = LicenseKey::where('id', $device->license_id)
                        ->where('status', LicenseKey::STATUS_ACTIVE)
                        ->where(function ($q) {
                            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                        })
                        ->first();
                }
            }

            if ($license) {
                $migratedFrom = 'drm_id';
            }
        }

        // ── Fallback 2: search by android_id (ANDROID_ID) ──────────────────
        // Stable across reinstall within the same signing key (Android 8+)
        if (! $license && $androidId) {
            // Check license_keys.android_id
            $license = LicenseKey::where('product_id', $product->id)
                ->where('android_id', $androidId)
                ->where('status', LicenseKey::STATUS_ACTIVE)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->first();

            // Check product_devices.android_id
            if (! $license) {
                $device = ProductDevice::where('product_id', $product->id)
                    ->where('android_id', $androidId)
                    ->whereNotNull('license_id')
                    ->first();
                if ($device) {
                    $license = LicenseKey::where('id', $device->license_id)
                        ->where('status', LicenseKey::STATUS_ACTIVE)
                        ->where(function ($q) {
                            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                        })
                        ->first();
                }
            }

            if ($license) {
                $migratedFrom = 'android_id';
            }
        }

        // ── If found via fallback: migrate machine_id to current one ────────
        if ($license && $migratedFrom) {
            $previousMachineId = $license->machine_id;

            // Update license binding to new machine_id
            $licenseUpdate = [
                'machine_id' => $validated['machine_id'],
                'last_validated_at' => now(),
            ];
            if ($drmId) {
                $licenseUpdate['drm_id'] = $drmId;
            }
            if ($androidId) {
                $licenseUpdate['android_id'] = $androidId;
            }
            $license->update($licenseUpdate);

            // Update device record to new machine_id
            if ($previousMachineId) {
                $deviceUpdate = ['machine_id' => $validated['machine_id']];
                if ($drmId) {
                    $deviceUpdate['drm_id'] = $drmId;
                }
                if ($androidId) {
                    $deviceUpdate['android_id'] = $androidId;
                }
                ProductDevice::where('product_id', $product->id)
                    ->where('machine_id', $previousMachineId)
                    ->update($deviceUpdate);
            }
        }

        if (! $license) {
            // LocalVPN freemium: auto-create a free license for new devices.
            // Use firstOrCreate to prevent duplicates from concurrent requests.
            if ($productSlug === 'localvpn') {
                $license = LicenseKey::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'machine_id' => $validated['machine_id'],
                        'license_type' => 'free',
                        'status' => LicenseKey::STATUS_ACTIVE,
                    ],
                    [
                        'license_key' => 'FREE-' . strtoupper(Str::random(20)),
                        'machine_fingerprint' => $validated['machine_id'],
                        'activated_at' => now(),
                        'max_activations' => 1,
                        'activations' => 1,
                        'drm_id' => $drmId,
                        'android_id' => $androidId,
                    ]
                );

                return response()->json([
                    'success' => true,
                    'has_license' => true,
                    'data' => [
                        'license_key' => $license->license_key,
                        'license_type' => 'free',
                        'status' => $license->status,
                        'expires_at' => null,
                        'days_remaining' => null,
                        'features' => $this->getFeaturesByType($productSlug, 'free'),
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'has_license' => false,
                'message' => 'ไม่พบ License สำหรับเครื่องนี้',
            ]);
        }

        // Update last validated + store new IDs if not already present
        $updateData = ['last_validated_at' => now()];
        if ($drmId && ! $license->drm_id) {
            $updateData['drm_id'] = $drmId;
        }
        if ($androidId && ! $license->android_id) {
            $updateData['android_id'] = $androidId;
        }
        $license->update($updateData);

        // Log check
        LicenseActivity::log(
            $license,
            LicenseActivity::ACTION_VALIDATED,
            LicenseActivity::ACTOR_API,
            null,
            $validated['machine_id'],
            $migratedFrom
                ? "ตรวจสอบ License จาก HWID สำเร็จ (migrated via {$migratedFrom})"
                : 'ตรวจสอบ License จาก HWID สำเร็จ (auto-check)',
            array_filter([
                'product_slug' => $productSlug,
                'method' => 'check-machine',
                'migrated_from' => $migratedFrom,
            ])
        );

        return response()->json([
            'success' => true,
            'has_license' => true,
            'data' => [
                'license_key' => $license->license_key,
                'license_type' => $license->license_type,
                'status' => $license->status,
                'expires_at' => $license->expires_at?->toISOString(),
                'days_remaining' => $license->daysRemaining(),
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

        // เฉพาะแผนที่ผลิตภัณฑ์นี้ขายจริง ตามลำดับที่ประกาศไว้ (WinXTools มีแค่ lifetime)
        $plans = [];
        foreach ($this->getPricingForProduct($product->slug) as $type => $plan) {
            $plans[$type] = [
                'price' => $plan['original'],
                'currency' => $plan['currency'],
                'duration_days' => self::PLAN_DURATION_DAYS[$type],
                'features' => $this->getFeaturesByType($productSlug, $type),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'product' => [
                    'name' => $product->name,
                    'slug' => $product->slug,
                ],
                'plans' => $plans,
                // หน้าเว็บที่ซื้อได้ — null ถ้าสินค้าปิดขายอยู่ (หน้าสินค้าตอบ 404)
                'purchase_url' => $product->is_active ? route('products.show', $product->slug) : null,
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
            'demo' => ['basic_features', 'trial_mode'],
            'monthly' => ['all_features', 'standard_support', 'cloud_sync'],
            'yearly' => ['all_features', 'priority_support', 'cloud_sync', 'priority_updates'],
            'lifetime' => ['all_features', 'priority_support', 'cloud_sync', 'lifetime_updates', 'unlimited_devices'],
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
     *
     * คืนเฉพาะแผนที่ผลิตภัณฑ์นั้นขาย — pricing() ส่งให้แอปตามนี้ทุกแผน ไม่เติมแผนที่ไม่มีให้
     */
    private function getPricingForProduct(string $productSlug): array
    {
        // Per-product pricing overrides
        $productPricing = [
            // Pro ฿199 ต่อปี — ราคาเดียวกับหน้า products/winxtools และ
            // CartController::LICENSE_TERM_PRICES แก้ต้องแก้พร้อมกัน
            'winx-tools' => [
                'yearly' => ['original' => 199, 'currency' => 'THB'],
            ],
            // BrainX Cloud ฿399 ต่อเดือน — แผนเดียว ราคาเดียวกับ CartController::LICENSE_TERM_PRICES
            // แก้ต้องแก้พร้อมกัน · ซื้อซ้ำต่ออายุคีย์เดิม (config/licenses.php)
            'brainx' => [
                'monthly' => ['original' => 399, 'currency' => 'THB'],
            ],
            'smschecker' => [
                'monthly' => ['original' => 499, 'currency' => 'THB'],
                'yearly' => ['original' => 4990, 'currency' => 'THB'],
                'lifetime' => ['original' => 29000, 'currency' => 'THB'],
            ],
            'localvpn' => [
                'monthly' => ['original' => 399, 'currency' => 'THB'],
                'yearly' => ['original' => 2500, 'currency' => 'THB'],
                'lifetime' => ['original' => 5000, 'currency' => 'THB'],
            ],
        ];

        return $productPricing[$productSlug] ?? self::DEFAULT_PRICING;
    }

    /**
     * Store diagnostic reports from app
     * POST /api/v1/product/{productSlug}/diagnostics
     *
     * Receives batch diagnostic events (crashes, captcha results, errors)
     * and stores them as bug_reports for analysis.
     */
    public function storeDiagnostics(Request $request, string $productSlug)
    {
        $request->validate([
            'machine_id' => 'required|string|max:255',
            'machine_name' => 'nullable|string|max:255',
            'os_version' => 'nullable|string|max:50',
            'hardware_hash' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:20',
            'app_version_code' => 'nullable|integer',
            'events' => 'required|array|max:50',
            'events.*.category' => 'required|string|max:50',
            'events.*.message' => 'required|string|max:500',
            'events.*.details' => 'nullable|string|max:2000',
            'events.*.timestamp' => 'nullable|string|max:30',
            'events.*.version' => 'nullable|string|max:20',
        ]);

        $events = $request->input('events', []);
        $created = 0;

        foreach ($events as $event) {
            $category = $event['category'] ?? 'unknown';
            $isCrash = $category === 'crash';

            BugReport::create([
                'product_name' => $productSlug,
                'product_version' => $event['version'] ?? $request->input('app_version'),
                'report_type' => $isCrash ? 'crash' : 'diagnostic',
                'title' => mb_substr($event['message'] ?? 'Diagnostic event', 0, 255),
                'description' => $event['details'] ?? '',
                'device_id' => $request->input('machine_id'),
                'os_version' => $request->input('os_version'),
                'app_version' => $request->input('app_version'),
                'stack_trace' => $isCrash ? ($event['details'] ?? null) : null,
                'priority' => $isCrash ? 'high' : 'low',
                'severity' => $isCrash ? 'major' : 'minor',
                'metadata' => [
                    'category' => $category,
                    'machine_name' => $request->input('machine_name'),
                    'hardware_hash' => $request->input('hardware_hash'),
                    'app_version_code' => $request->input('app_version_code'),
                    'event_timestamp' => $event['timestamp'] ?? null,
                ],
            ]);
            $created++;
        }

        return response()->json([
            'success' => true,
            'message' => "Stored {$created} diagnostic events",
            'count' => $created,
        ]);
    }
}
