<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AutoTradeXDevice;
use App\Models\LicenseKey;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * License Controller for AutoTradeX
 *
 * This controller handles all license-related API requests from the
 * AutoTradeX desktop application (Crypto Arbitrage Trading Bot).
 */
class AutoTradeXLicenseController extends Controller
{
    /**
     * Product slug for AutoTradeX
     */
    private const PRODUCT_SLUG = 'autotradex';

    /**
     * Features available for trial/demo
     */
    private const TRIAL_FEATURES = [
        'simulation_mode',
        'single_exchange',
        'basic_alerts',
        'trade_history',
    ];

    /**
     * Features for monthly subscription
     */
    private const MONTHLY_FEATURES = [
        'simulation_mode',
        'live_trading',
        'multi_exchange',
        'basic_alerts',
        'advanced_alerts',
        'trade_history',
        'pnl_tracking',
        'basic_arbitrage',
    ];

    /**
     * Features for yearly subscription
     */
    private const YEARLY_FEATURES = [
        'simulation_mode',
        'live_trading',
        'multi_exchange',
        'basic_alerts',
        'advanced_alerts',
        'trade_history',
        'pnl_tracking',
        'basic_arbitrage',
        'advanced_arbitrage',
        'auto_rebalance',
        'priority_support',
    ];

    /**
     * Full features for lifetime license
     */
    private const LIFETIME_FEATURES = [
        'simulation_mode',
        'live_trading',
        'multi_exchange',
        'all_exchanges',
        'basic_alerts',
        'advanced_alerts',
        'custom_alerts',
        'trade_history',
        'pnl_tracking',
        'advanced_charts',
        'basic_arbitrage',
        'advanced_arbitrage',
        'triangular_arbitrage',
        'auto_rebalance',
        'risk_management',
        'api_access',
        'priority_support',
        'lifetime_updates',
    ];

    /**
     * Supported exchanges
     */
    private const EXCHANGES = [
        'trial' => ['binance'],
        'monthly' => ['binance', 'kucoin', 'okx'],
        'yearly' => ['binance', 'kucoin', 'okx', 'bybit', 'gateio'],
        'lifetime' => ['binance', 'kucoin', 'okx', 'bybit', 'gateio', 'bitkub'],
    ];

    /**
     * Trial duration in days
     */
    private const TRIAL_DAYS = 7;

    /**
     * Early bird discount (20% off when buying during trial)
     */
    private const EARLY_BIRD_DISCOUNT_PERCENT = 20;

    /**
     * Pricing (THB)
     */
    private const PRICING = [
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
     * Get features based on license type
     */
    private function getFeaturesByType(string $type): array
    {
        return match ($type) {
            LicenseKey::TYPE_DEMO => self::TRIAL_FEATURES,
            LicenseKey::TYPE_MONTHLY => self::MONTHLY_FEATURES,
            LicenseKey::TYPE_YEARLY => self::YEARLY_FEATURES,
            LicenseKey::TYPE_LIFETIME, LicenseKey::TYPE_PRODUCT => self::LIFETIME_FEATURES,
            default => self::TRIAL_FEATURES,
        };
    }

    /**
     * Get supported exchanges based on license type
     */
    private function getExchangesByType(string $type): array
    {
        return match ($type) {
            LicenseKey::TYPE_DEMO => self::EXCHANGES['trial'],
            LicenseKey::TYPE_MONTHLY => self::EXCHANGES['monthly'],
            LicenseKey::TYPE_YEARLY => self::EXCHANGES['yearly'],
            LicenseKey::TYPE_LIFETIME, LicenseKey::TYPE_PRODUCT => self::EXCHANGES['lifetime'],
            default => self::EXCHANGES['trial'],
        };
    }

    /**
     * Register a device automatically when app starts
     * This is called immediately when the app connects to the server
     *
     * POST /api/v1/autotradex/register-device
     */
    public function registerDevice(Request $request)
    {
        $validated = $request->validate([
            'machine_id' => 'required|string|min:32|max:64',
            'machine_name' => 'nullable|string|max:255',
            'os_version' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:50',
            'hardware_hash' => 'nullable|string|max:64',
        ]);

        $ip = $request->ip();

        // Find or create device
        $device = AutoTradeXDevice::firstOrNew(['machine_id' => $validated['machine_id']]);

        $isNew = ! $device->exists;

        // Update device info
        $device->fill([
            'machine_name' => $validated['machine_name'] ?? $device->machine_name,
            'os_version' => $validated['os_version'] ?? $device->os_version,
            'app_version' => $validated['app_version'] ?? $device->app_version,
            'hardware_hash' => $validated['hardware_hash'] ?? $device->hardware_hash,
            'last_ip' => $ip,
            'last_seen_at' => now(),
        ]);

        if ($isNew) {
            $device->first_ip = $ip;
            $device->first_seen_at = now();
            $device->status = AutoTradeXDevice::STATUS_PENDING;
        }

        $device->save();

        // Check for abuse patterns
        $abuseCheck = $device->checkTrialAbuse();
        if ($abuseCheck['is_abuse']) {
            $device->markSuspicious(implode('; ', $abuseCheck['reasons']));
            Log::warning('AutoTradeX: Suspicious device detected', [
                'machine_id' => $validated['machine_id'],
                'reasons' => $abuseCheck['reasons'],
                'ip' => $ip,
            ]);
        }

        // Check if device has an active license
        $activeLicense = null;
        if ($device->license_id) {
            $activeLicense = $device->license;
            if ($activeLicense && $activeLicense->isValid()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Device registered with active license',
                    'data' => [
                        'device_status' => 'licensed',
                        'has_license' => true,
                        'license_type' => $activeLicense->license_type,
                        'expires_at' => $activeLicense->expires_at?->toISOString(),
                        'features' => $this->getFeaturesByType($activeLicense->license_type),
                        'exchanges' => $this->getExchangesByType($activeLicense->license_type),
                    ],
                ]);
            }
        }

        // Check trial status and early bird discount
        $trialInfo = null;
        $earlyBirdInfo = null;
        if ($device->status === AutoTradeXDevice::STATUS_TRIAL && ! $device->isTrialExpired()) {
            $trialInfo = [
                'is_active' => true,
                'days_remaining' => $device->trialDaysRemaining(),
                'expires_at' => $device->trial_expires_at->toISOString(),
            ];

            // Check for early bird discount
            $earlyBirdInfo = $this->checkEarlyBirdDiscount($device);
        }

        // Check if should be in demo mode (trial expired)
        $isDemoMode = $device->isDemoMode();
        $demoModeInfo = null;
        if ($isDemoMode) {
            // Auto-switch to demo mode status if trial expired
            if ($device->status !== AutoTradeXDevice::STATUS_DEMO &&
                $device->status !== AutoTradeXDevice::STATUS_LICENSED) {
                $device->switchToDemoMode();
            }

            $demoModeInfo = $device->getDemoModeConfig();
            $demoModeInfo['purchase_url'] = $this->getPurchaseUrlForDevice($device);
        }

        // Get pricing info with potential discount
        $pricingInfo = $this->getPricingForDevice($device);

        return response()->json([
            'success' => true,
            'message' => $isNew ? 'Device registered successfully' : 'Device updated',
            'data' => [
                'device_status' => $device->status,
                'is_new' => $isNew,
                'has_license' => false,
                'trial' => $trialInfo,
                'can_start_trial' => $device->canStartTrial(),
                'is_suspicious' => $device->is_suspicious,
                'purchase_url' => $this->getPurchaseUrlForDevice($device),
                // Demo mode info
                'is_demo_mode' => $isDemoMode,
                'demo_mode' => $demoModeInfo,
                // Early bird discount info
                'early_bird' => $earlyBirdInfo,
                'pricing' => $pricingInfo,
            ],
        ]);
    }

    /**
     * Get purchase URL for a device
     */
    private function getPurchaseUrlForDevice(AutoTradeXDevice $device): string
    {
        $baseUrl = config('app.url');
        $machineIdShort = substr($device->machine_id, 0, 16);

        // Check if eligible for early bird discount
        $discountInfo = $this->checkEarlyBirdDiscount($device);

        if ($discountInfo['eligible']) {
            return "{$baseUrl}/autotradex/buy?machine_id={$machineIdShort}&discount=earlybird&code={$discountInfo['code']}";
        }

        return "{$baseUrl}/autotradex/buy?machine_id={$machineIdShort}";
    }

    /**
     * Check if device is eligible for early bird discount
     * Discount is available only during active trial and only once per device
     */
    private function checkEarlyBirdDiscount(AutoTradeXDevice $device): array
    {
        // Not eligible if device has already used discount
        if ($device->early_bird_used ?? false) {
            return [
                'eligible' => false,
                'reason' => 'discount_already_used',
            ];
        }

        // Not eligible if device already has a license (not first purchase)
        if ($device->status === AutoTradeXDevice::STATUS_LICENSED) {
            return [
                'eligible' => false,
                'reason' => 'already_licensed',
            ];
        }

        // Eligible if in active trial (not expired)
        if ($device->status === AutoTradeXDevice::STATUS_TRIAL && ! $device->isTrialExpired()) {
            $daysRemaining = $device->trialDaysRemaining();
            $discountCode = $this->generateDiscountCode($device->machine_id);

            return [
                'eligible' => true,
                'discount_percent' => self::EARLY_BIRD_DISCOUNT_PERCENT,
                'days_remaining' => $daysRemaining,
                'code' => $discountCode,
                'message' => "🎉 ซื้อตอนนี้ลด {self::EARLY_BIRD_DISCOUNT_PERCENT}%! เหลือเวลาอีก {$daysRemaining} วัน",
                'expires_at' => $device->trial_expires_at?->toISOString(),
            ];
        }

        // Not eligible - trial expired or not started
        return [
            'eligible' => false,
            'reason' => $device->status === AutoTradeXDevice::STATUS_PENDING ? 'trial_not_started' : 'trial_expired',
        ];
    }

    /**
     * Generate unique discount code for a device
     */
    private function generateDiscountCode(string $machineId): string
    {
        $data = $machineId.config('app.key').date('Ymd');

        return 'EARLY'.strtoupper(substr(hash('sha256', $data), 0, 8));
    }

    /**
     * Validate discount code for a device
     */
    public function validateDiscountCode(string $code, string $machineId): bool
    {
        $expectedCode = $this->generateDiscountCode($machineId);

        return hash_equals($expectedCode, $code);
    }

    /**
     * Get pricing with optional early bird discount
     */
    private function getPricingForDevice(AutoTradeXDevice $device): array
    {
        $discountInfo = $this->checkEarlyBirdDiscount($device);
        $pricing = [];

        foreach (self::PRICING as $plan => $priceInfo) {
            $originalPrice = $priceInfo['original'];
            $finalPrice = $originalPrice;
            $discount = null;

            if ($discountInfo['eligible']) {
                $discountAmount = $originalPrice * (self::EARLY_BIRD_DISCOUNT_PERCENT / 100);
                $finalPrice = $originalPrice - $discountAmount;
                $discount = [
                    'percent' => self::EARLY_BIRD_DISCOUNT_PERCENT,
                    'amount' => $discountAmount,
                    'code' => $discountInfo['code'],
                ];
            }

            $pricing[$plan] = [
                'original_price' => $originalPrice,
                'final_price' => $finalPrice,
                'currency' => $priceInfo['currency'],
                'discount' => $discount,
                'features' => $this->getFeaturesByType($plan === 'monthly' ? LicenseKey::TYPE_MONTHLY :
                    ($plan === 'yearly' ? LicenseKey::TYPE_YEARLY : LicenseKey::TYPE_LIFETIME)),
                'exchanges' => $this->getExchangesByType($plan === 'monthly' ? LicenseKey::TYPE_MONTHLY :
                    ($plan === 'yearly' ? LicenseKey::TYPE_YEARLY : LicenseKey::TYPE_LIFETIME)),
            ];
        }

        return [
            'plans' => $pricing,
            'early_bird' => $discountInfo,
        ];
    }

    /**
     * Activate a license key on a machine
     *
     * POST /api/v1/autotradex/activate
     */
    public function activate(Request $request)
    {
        $validated = $request->validate([
            'license_key' => 'required|string',
            'machine_id' => 'required|string|size:32',
            'machine_name' => 'nullable|string|max:255',
            'os_version' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:50',
        ]);

        // Find the license
        $license = LicenseKey::where('license_key', strtoupper($validated['license_key']))
            ->whereHas('product', fn ($q) => $q->where('slug', self::PRODUCT_SLUG))
            ->first();

        if (! $license) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid license key',
                'error_code' => 'INVALID_LICENSE',
            ], 404);
        }

        // Check if license is revoked
        if ($license->status === LicenseKey::STATUS_REVOKED) {
            return response()->json([
                'success' => false,
                'message' => 'This license has been revoked',
                'error_code' => 'LICENSE_REVOKED',
            ], 403);
        }

        // Check if license is expired
        if ($license->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'This license has expired',
                'error_code' => 'LICENSE_EXPIRED',
                'expired_at' => $license->expires_at?->toISOString(),
            ], 403);
        }

        // Check max activations
        if ($license->activations >= $license->max_activations) {
            // Check if this machine is already activated
            if ($license->machine_id !== $validated['machine_id']) {
                return response()->json([
                    'success' => false,
                    'message' => "Maximum activations reached ({$license->max_activations} devices). Please deactivate another device first.",
                    'error_code' => 'MAX_ACTIVATIONS',
                    'max_activations' => $license->max_activations,
                    'current_activations' => $license->activations,
                ], 403);
            }
        }

        // Activate on this machine
        $license->activateOnMachine(
            $validated['machine_id'],
            $request->input('machine_fingerprint', $validated['machine_id'])
        );

        // Update metadata
        $metadata = json_decode($license->metadata ?? '{}', true);
        $metadata['last_activation'] = [
            'machine_name' => $validated['machine_name'] ?? 'Unknown',
            'os_version' => $validated['os_version'] ?? 'Unknown',
            'app_version' => $validated['app_version'] ?? 'Unknown',
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ];

        // Remove this machine_id from revoked list if exists
        // This allows re-activation after reset (e.g., same hardware after Windows reinstall)
        if (! empty($metadata['revoked_machine_ids'])) {
            $metadata['revoked_machine_ids'] = array_values(array_filter(
                $metadata['revoked_machine_ids'],
                fn ($revoked) => $revoked['machine_id'] !== $validated['machine_id']
            ));
        }

        $license->update(['metadata' => json_encode($metadata)]);

        // Unblock device if it was blocked
        $device = AutoTradeXDevice::where('machine_id', $validated['machine_id'])->first();
        if ($device && $device->status === AutoTradeXDevice::STATUS_BLOCKED) {
            $device->update([
                'status' => AutoTradeXDevice::STATUS_LICENSED,
                'license_id' => $license->id,
                'is_suspicious' => false,
                'abuse_reason' => null,
            ]);
        }

        // Get order info for customer details
        $order = $license->order;
        $features = $this->getFeaturesByType($license->license_type);
        $exchanges = $this->getExchangesByType($license->license_type);

        return response()->json([
            'success' => true,
            'message' => 'License activated successfully',
            'data' => [
                'email' => $order?->email ?? null,
                'customer_name' => $order?->customer_name ?? null,
                'product_name' => $license->product->name,
                'license_type' => $license->license_type,
                'expires_at' => $license->expires_at?->toISOString(),
                'days_remaining' => $license->daysRemaining(),
                'max_devices' => $license->max_activations,
                'current_devices' => $license->activations,
                'features' => $features,
                'exchanges' => $exchanges,
                'status' => $license->status,
            ],
        ]);
    }

    /**
     * Validate an existing license
     *
     * POST /api/v1/autotradex/validate
     */
    public function validate(Request $request)
    {
        $validated = $request->validate([
            'license_key' => 'required|string',
            'machine_id' => 'required|string|size:32',
        ]);

        $license = LicenseKey::where('license_key', strtoupper($validated['license_key']))
            ->whereHas('product', fn ($q) => $q->where('slug', self::PRODUCT_SLUG))
            ->first();

        if (! $license) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid license key',
                'error_code' => 'INVALID_LICENSE',
            ], 404);
        }

        // Check if this machine_id has been revoked (device was reset)
        // But only if this is NOT the currently activated machine
        // (allows re-validation after same hardware reinstall Windows)
        $metadata = json_decode($license->metadata ?? '{}', true);
        $revokedMachines = $metadata['revoked_machine_ids'] ?? [];

        // If license is currently activated on THIS machine, don't check revoked list
        $isCurrentlyActivated = $license->machine_id === $validated['machine_id'];

        if (! $isCurrentlyActivated) {
            foreach ($revokedMachines as $revoked) {
                if ($revoked['machine_id'] === $validated['machine_id']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This device has been deauthorized. The license was moved to a new device.',
                        'message_th' => 'อุปกรณ์นี้ถูกยกเลิกการใช้งานแล้ว License ถูกย้ายไปใช้งานบนเครื่องอื่น',
                        'error_code' => 'DEVICE_REVOKED',
                        'revoked_at' => $revoked['revoked_at'],
                    ], 403);
                }
            }
        }

        // Verify machine ID matches
        if ($license->machine_id && $license->machine_id !== $validated['machine_id']) {
            return response()->json([
                'success' => false,
                'message' => 'License is activated on a different device',
                'error_code' => 'DEVICE_MISMATCH',
            ], 403);
        }

        // Check if valid
        if (! $license->isValid()) {
            return response()->json([
                'success' => false,
                'message' => $license->isExpired() ? 'License has expired' : 'License is not valid',
                'error_code' => $license->isExpired() ? 'LICENSE_EXPIRED' : 'LICENSE_INVALID',
            ], 403);
        }

        // Update last validated timestamp
        $license->update(['last_validated_at' => now()]);

        $features = $this->getFeaturesByType($license->license_type);
        $exchanges = $this->getExchangesByType($license->license_type);

        return response()->json([
            'success' => true,
            'message' => 'License is valid',
            'data' => [
                'license_type' => $license->license_type,
                'expires_at' => $license->expires_at?->toISOString(),
                'days_remaining' => $license->daysRemaining(),
                'features' => $features,
                'exchanges' => $exchanges,
                'status' => $license->status,
            ],
        ]);
    }

    /**
     * Deactivate a license from a machine
     *
     * POST /api/v1/autotradex/deactivate
     */
    public function deactivate(Request $request)
    {
        $validated = $request->validate([
            'license_key' => 'required|string',
            'machine_id' => 'required|string|size:32',
        ]);

        $license = LicenseKey::where('license_key', strtoupper($validated['license_key']))
            ->whereHas('product', fn ($q) => $q->where('slug', self::PRODUCT_SLUG))
            ->first();

        if (! $license) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid license key',
                'error_code' => 'INVALID_LICENSE',
            ], 404);
        }

        // Verify machine ID matches
        if ($license->machine_id !== $validated['machine_id']) {
            return response()->json([
                'success' => false,
                'message' => 'License is not activated on this device',
                'error_code' => 'DEVICE_MISMATCH',
            ], 403);
        }

        // Deactivate
        $license->update([
            'machine_id' => null,
            'device_id' => null,
            'machine_fingerprint' => null,
            'activated_at' => null,
            'activations' => max(0, $license->activations - 1),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'License deactivated successfully',
        ]);
    }

    /**
     * Get license status
     *
     * GET /api/v1/autotradex/status/{license_key}
     */
    public function status(string $licenseKey)
    {
        $license = LicenseKey::where('license_key', strtoupper($licenseKey))
            ->whereHas('product', fn ($q) => $q->where('slug', self::PRODUCT_SLUG))
            ->first();

        if (! $license) {
            return response()->json([
                'success' => false,
                'message' => 'License not found',
                'error_code' => 'NOT_FOUND',
            ], 404);
        }

        $features = $this->getFeaturesByType($license->license_type);
        $exchanges = $this->getExchangesByType($license->license_type);

        return response()->json([
            'success' => true,
            'data' => [
                'license_key' => $license->license_key,
                'status' => $license->status,
                'license_type' => $license->license_type,
                'expires_at' => $license->expires_at?->toISOString(),
                'activations' => $license->activations,
                'max_activations' => $license->max_activations,
                'is_valid' => $license->isValid(),
                'days_remaining' => $license->daysRemaining(),
                'features' => $features,
                'exchanges' => $exchanges,
            ],
        ]);
    }

    /**
     * Start a demo/trial period
     * Enhanced with abuse detection using AutoTradeXDevice
     *
     * POST /api/v1/autotradex/demo
     */
    public function startDemo(Request $request)
    {
        $validated = $request->validate([
            'machine_id' => 'required|string|min:32|max:64',
            'machine_name' => 'nullable|string|max:255',
            'os_version' => 'nullable|string|max:255',
            'hardware_hash' => 'nullable|string|max:64',
        ]);

        $ip = $request->ip();

        // Find the product
        $product = Product::where('slug', self::PRODUCT_SLUG)->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'error_code' => 'PRODUCT_NOT_FOUND',
            ], 404);
        }

        // Get or create device record
        $device = AutoTradeXDevice::firstOrCreate(
            ['machine_id' => $validated['machine_id']],
            [
                'machine_name' => $validated['machine_name'] ?? 'Unknown',
                'os_version' => $validated['os_version'] ?? 'Unknown',
                'hardware_hash' => $validated['hardware_hash'] ?? null,
                'first_ip' => $ip,
                'last_ip' => $ip,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
                'status' => AutoTradeXDevice::STATUS_PENDING,
            ]
        );

        // Update device info
        $device->update([
            'last_ip' => $ip,
            'last_seen_at' => now(),
            'hardware_hash' => $validated['hardware_hash'] ?? $device->hardware_hash,
        ]);

        // Check for trial abuse BEFORE allowing trial
        $abuseCheck = $device->checkTrialAbuse();
        if ($abuseCheck['is_abuse']) {
            $device->markSuspicious(implode('; ', $abuseCheck['reasons']));

            Log::warning('AutoTradeX: Trial abuse detected', [
                'machine_id' => $validated['machine_id'],
                'reasons' => $abuseCheck['reasons'],
                'ip' => $ip,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Trial is not available for this device. Please purchase a license.',
                'error_code' => 'TRIAL_ABUSE_DETECTED',
                'purchase_url' => $this->getPurchaseUrlForDevice($device),
            ], 403);
        }

        // Check if device is blocked
        if ($device->status === AutoTradeXDevice::STATUS_BLOCKED) {
            return response()->json([
                'success' => false,
                'message' => 'This device has been blocked. Please contact support.',
                'error_code' => 'DEVICE_BLOCKED',
            ], 403);
        }

        // Check if device already has active trial
        if ($device->status === AutoTradeXDevice::STATUS_TRIAL && ! $device->isTrialExpired()) {
            return response()->json([
                'success' => true,
                'message' => 'Trial already active',
                'data' => [
                    'expires_at' => $device->trial_expires_at?->toISOString(),
                    'days_remaining' => $device->trialDaysRemaining(),
                    'features' => self::TRIAL_FEATURES,
                    'exchanges' => self::EXCHANGES['trial'],
                ],
            ]);
        }

        // Check if trial expired
        if ($device->status === AutoTradeXDevice::STATUS_EXPIRED ||
            ($device->trial_expires_at && $device->isTrialExpired())) {
            $device->update(['status' => AutoTradeXDevice::STATUS_EXPIRED]);

            return response()->json([
                'success' => false,
                'message' => 'Your trial period has ended. Please purchase a license to continue using AutoTradeX.',
                'error_code' => 'TRIAL_EXPIRED',
                'expired_at' => $device->trial_expires_at?->toISOString(),
                'purchase_url' => $this->getPurchaseUrlForDevice($device),
            ], 403);
        }

        // Check if can start trial
        if (! $device->canStartTrial()) {
            return response()->json([
                'success' => false,
                'message' => 'Trial is not available for this device.',
                'error_code' => 'TRIAL_NOT_AVAILABLE',
                'purchase_url' => $this->getPurchaseUrlForDevice($device),
            ], 403);
        }

        // Check for existing license key (legacy support)
        $existingDemo = LicenseKey::where('machine_id', $validated['machine_id'])
            ->where('product_id', $product->id)
            ->where('license_type', LicenseKey::TYPE_DEMO)
            ->first();

        if ($existingDemo && ! $existingDemo->isExpired()) {
            return response()->json([
                'success' => true,
                'message' => 'Trial already active',
                'data' => [
                    'license_key' => $existingDemo->license_key,
                    'expires_at' => $existingDemo->expires_at?->toISOString(),
                    'days_remaining' => $existingDemo->daysRemaining(),
                    'features' => self::TRIAL_FEATURES,
                    'exchanges' => self::EXCHANGES['trial'],
                ],
            ]);
        }

        // Start trial on device
        $device->startTrial(7);

        // Create demo license key
        $demoKey = LicenseKey::generateDemoKey();
        $demo = LicenseKey::create([
            'product_id' => $product->id,
            'license_key' => $demoKey,
            'status' => LicenseKey::STATUS_ACTIVE,
            'license_type' => LicenseKey::TYPE_DEMO,
            'machine_id' => $validated['machine_id'],
            'activated_at' => now(),
            'expires_at' => now()->addDays(7),
            'max_activations' => 1,
            'activations' => 1,
            'metadata' => json_encode([
                'machine_name' => $validated['machine_name'] ?? 'Unknown',
                'os_version' => $validated['os_version'] ?? 'Unknown',
                'hardware_hash' => $validated['hardware_hash'] ?? null,
                'ip' => $ip,
                'started_at' => now()->toISOString(),
                'device_id' => $device->id,
            ]),
        ]);

        // Link license to device
        $device->update(['license_id' => $demo->id]);

        Log::info('AutoTradeX: New trial started', [
            'machine_id' => $validated['machine_id'],
            'license_key' => $demoKey,
            'ip' => $ip,
            'device_id' => $device->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trial started successfully. You have 7 days to explore AutoTradeX!',
            'data' => [
                'license_key' => $demo->license_key,
                'expires_at' => $demo->expires_at->toISOString(),
                'days_remaining' => 7,
                'features' => self::TRIAL_FEATURES,
                'exchanges' => self::EXCHANGES['trial'],
            ],
        ]);
    }

    /**
     * Check demo status
     *
     * POST /api/v1/autotradex/demo/check
     */
    public function checkDemo(Request $request)
    {
        $validated = $request->validate([
            'machine_id' => 'required|string|size:32',
        ]);

        $product = Product::where('slug', self::PRODUCT_SLUG)->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'error_code' => 'PRODUCT_NOT_FOUND',
            ], 404);
        }

        $demo = LicenseKey::where('machine_id', $validated['machine_id'])
            ->where('product_id', $product->id)
            ->where('license_type', LicenseKey::TYPE_DEMO)
            ->first();

        if (! $demo) {
            return response()->json([
                'success' => false,
                'message' => 'No trial found for this machine',
                'error_code' => 'NO_TRIAL',
                'can_start_trial' => true,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'is_valid' => $demo->isValid(),
                'is_expired' => $demo->isExpired(),
                'days_remaining' => $demo->daysRemaining(),
                'expires_at' => $demo->expires_at?->toISOString(),
                'features' => self::TRIAL_FEATURES,
                'exchanges' => self::EXCHANGES['trial'],
                'can_start_trial' => false,
            ],
        ]);
    }

    /**
     * Get pricing and feature comparison
     *
     * GET /api/v1/autotradex/pricing
     */
    public function pricing()
    {
        $baseUrl = config('app.url');

        return response()->json([
            'success' => true,
            'data' => [
                'purchase_url' => "{$baseUrl}/autotradex/buy",
                'pricing_page' => "{$baseUrl}/autotradex/pricing",
                'plans' => [
                    'trial' => [
                        'name' => 'Trial',
                        'duration' => '7 days',
                        'price' => 0,
                        'features' => self::TRIAL_FEATURES,
                        'exchanges' => self::EXCHANGES['trial'],
                    ],
                    'monthly' => [
                        'name' => 'Monthly',
                        'duration' => '30 days',
                        'price' => self::PRICING['monthly']['original'],
                        'currency' => self::PRICING['monthly']['currency'],
                        'features' => self::MONTHLY_FEATURES,
                        'exchanges' => self::EXCHANGES['monthly'],
                        'purchase_url' => "{$baseUrl}/autotradex/checkout/monthly",
                    ],
                    'yearly' => [
                        'name' => 'Yearly',
                        'duration' => '365 days',
                        'price' => self::PRICING['yearly']['original'],
                        'currency' => self::PRICING['yearly']['currency'],
                        'features' => self::YEARLY_FEATURES,
                        'exchanges' => self::EXCHANGES['yearly'],
                        'save_percent' => 44, // (299*12 - 1990) / (299*12) * 100 ≈ 44%
                        'purchase_url' => "{$baseUrl}/autotradex/checkout/yearly",
                    ],
                    'lifetime' => [
                        'name' => 'Lifetime',
                        'duration' => 'Forever',
                        'price' => self::PRICING['lifetime']['original'],
                        'currency' => self::PRICING['lifetime']['currency'],
                        'features' => self::LIFETIME_FEATURES,
                        'exchanges' => self::EXCHANGES['lifetime'],
                        'purchase_url' => "{$baseUrl}/autotradex/checkout/lifetime",
                    ],
                ],
            ],
        ]);
    }

    /**
     * Get purchase URL - for app to open browser
     *
     * GET /api/v1/autotradex/purchase-url
     */
    public function purchaseUrl(Request $request)
    {
        $baseUrl = config('app.url');
        $plan = $request->query('plan', 'yearly'); // Default to best value
        $machineId = $request->query('machine_id', '');

        $url = "{$baseUrl}/autotradex/buy?plan={$plan}";

        if ($machineId) {
            $url .= "&machine_id={$machineId}";
        }

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $url,
                'plan' => $plan,
            ],
        ]);
    }

    /**
     * Verify server authenticity (Anti-fake server protection)
     * App sends a challenge, server responds with the same challenge + signature
     *
     * POST /api/v1/autotradex/verify-server
     */
    public function verifyServer(Request $request)
    {
        $validated = $request->validate([
            'challenge' => 'required|string|min:32|max:64',
            'timestamp' => 'required|integer',
            'app_name' => 'required|string|max:50',
            'app_version' => 'required|string|max:20',
        ]);

        // Verify timestamp is within acceptable range (5 minutes)
        $currentTimestamp = time();
        if (abs($currentTimestamp - $validated['timestamp']) > 300) {
            return response()->json([
                'success' => false,
                'message' => 'Timestamp out of range',
                'error_code' => 'TIMESTAMP_INVALID',
            ], 400);
        }

        // Verify app name
        if ($validated['app_name'] !== 'AutoTradeX') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid app name',
                'error_code' => 'APP_INVALID',
            ], 400);
        }

        // Create response with challenge echo and signature
        $responseData = [
            'challenge' => $validated['challenge'],
            'timestamp' => $currentTimestamp,
            'server_version' => '1.0.0',
            'product' => self::PRODUCT_SLUG,
        ];

        // Create signature using server secret
        // In production, use a proper signing key
        $signatureData = $validated['challenge'].$currentTimestamp.self::PRODUCT_SLUG;
        $signature = hash_hmac('sha256', $signatureData, config('app.key'));

        return response()->json([
            'success' => true,
            'challenge' => $validated['challenge'],
            'timestamp' => $currentTimestamp,
            'signature' => $signature,
            'server_version' => '1.0.0',
        ])->withHeaders([
            'X-License-Signature' => $signature,
            'X-License-Timestamp' => (string) $currentTimestamp,
            'X-License-Nonce' => bin2hex(random_bytes(16)),
        ]);
    }

    /**
     * Reset device ID for Lifetime license holders
     * Allows customer to move license to a new device
     *
     * POST /api/v1/autotradex/reset-device
     */
    public function resetDevice(Request $request)
    {
        $validated = $request->validate([
            'license_key' => 'required|string',
            'email' => 'required|email',
            'current_machine_id' => 'nullable|string|max:64',
            'reason' => 'nullable|string|max:500',
        ]);

        // Find the license
        $license = LicenseKey::where('license_key', strtoupper($validated['license_key']))
            ->whereHas('product', fn ($q) => $q->where('slug', self::PRODUCT_SLUG))
            ->first();

        if (! $license) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ License Key นี้ในระบบ',
                'error_code' => 'INVALID_LICENSE',
            ], 404);
        }

        // Verify email matches order
        $order = $license->order;
        if (! $order || strtolower($order->email) !== strtolower($validated['email'])) {
            return response()->json([
                'success' => false,
                'message' => 'อีเมลไม่ตรงกับที่ใช้สั่งซื้อ License',
                'error_code' => 'EMAIL_MISMATCH',
            ], 403);
        }

        // Only Lifetime licenses can reset device
        if (! in_array($license->license_type, [LicenseKey::TYPE_LIFETIME, LicenseKey::TYPE_PRODUCT])) {
            return response()->json([
                'success' => false,
                'message' => 'เฉพาะ Lifetime License เท่านั้นที่สามารถ Reset Device ได้',
                'error_code' => 'NOT_LIFETIME',
                'license_type' => $license->license_type,
            ], 403);
        }

        // Check if license is valid
        if ($license->status === LicenseKey::STATUS_REVOKED) {
            return response()->json([
                'success' => false,
                'message' => 'License นี้ถูกยกเลิกแล้ว',
                'error_code' => 'LICENSE_REVOKED',
            ], 403);
        }

        // Check reset cooldown (max 1 reset per 30 days)
        $metadata = json_decode($license->metadata ?? '{}', true);
        $lastReset = $metadata['last_device_reset'] ?? null;

        if ($lastReset) {
            $lastResetTime = \Carbon\Carbon::parse($lastReset['timestamp']);
            $daysSinceReset = $lastResetTime->diffInDays(now());

            if ($daysSinceReset < 30) {
                $daysRemaining = 30 - $daysSinceReset;

                return response()->json([
                    'success' => false,
                    'message' => "สามารถ Reset Device ได้อีกครั้งใน {$daysRemaining} วัน",
                    'error_code' => 'RESET_COOLDOWN',
                    'cooldown_days_remaining' => $daysRemaining,
                    'last_reset_at' => $lastReset['timestamp'],
                ], 429);
            }
        }

        // Store previous device info for audit
        $previousDevice = [
            'machine_id' => $license->machine_id,
            'device_id' => $license->device_id,
            'machine_fingerprint' => $license->machine_fingerprint,
            'reset_at' => now()->toISOString(),
            'reason' => $validated['reason'] ?? 'Customer request',
            'ip' => $request->ip(),
        ];

        // Update metadata with reset history
        $resetHistory = $metadata['device_reset_history'] ?? [];
        if ($license->machine_id) {
            $resetHistory[] = $previousDevice;
        }

        $metadata['device_reset_history'] = array_slice($resetHistory, -10); // Keep last 10 resets
        $metadata['last_device_reset'] = [
            'timestamp' => now()->toISOString(),
            'reason' => $validated['reason'] ?? 'Customer request',
            'ip' => $request->ip(),
            'previous_machine_id' => $license->machine_id,
        ];
        $metadata['total_device_resets'] = ($metadata['total_device_resets'] ?? 0) + 1;

        // Add old machine_id to revoked list (IMPORTANT: prevents old device from using license)
        if ($license->machine_id) {
            $revokedMachines = $metadata['revoked_machine_ids'] ?? [];
            $revokedMachines[] = [
                'machine_id' => $license->machine_id,
                'revoked_at' => now()->toISOString(),
                'reason' => 'device_reset',
            ];
            $metadata['revoked_machine_ids'] = array_slice($revokedMachines, -20); // Keep last 20
        }

        // Reset device binding
        $license->update([
            'machine_id' => null,
            'device_id' => null,
            'machine_fingerprint' => null,
            'activated_at' => null,
            'activations' => 0,
            'metadata' => json_encode($metadata),
        ]);

        // Also update the device record - mark as BLOCKED not just pending
        if ($previousDevice['machine_id']) {
            $device = AutoTradeXDevice::where('machine_id', $previousDevice['machine_id'])->first();
            if ($device) {
                $device->block('Device reset by customer - license moved to new device');
            }
        }

        Log::info('AutoTradeX: Device reset by customer', [
            'license_key' => $license->license_key,
            'email' => $validated['email'],
            'previous_machine_id' => $previousDevice['machine_id'],
            'ip' => $request->ip(),
            'total_resets' => $metadata['total_device_resets'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reset Device สำเร็จ! คุณสามารถ Activate บนเครื่องใหม่ได้แล้ว',
            'data' => [
                'license_key' => $license->license_key,
                'can_activate' => true,
                'next_reset_available_at' => now()->addDays(30)->toISOString(),
                'total_resets' => $metadata['total_device_resets'],
            ],
        ]);
    }

    /**
     * Admin: Reset device for any license
     * Requires admin authentication
     *
     * POST /api/v1/autotradex/admin/reset-device
     */
    public function adminResetDevice(Request $request)
    {
        // Verify admin token (simple token check, in production use proper auth)
        $adminToken = $request->header('X-Admin-Token');
        $expectedToken = hash('sha256', config('app.key').'autotradex-admin');

        if (! $adminToken || ! hash_equals($expectedToken, $adminToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'error_code' => 'UNAUTHORIZED',
            ], 401);
        }

        $validated = $request->validate([
            'license_key' => 'required|string',
            'reason' => 'required|string|max:500',
            'admin_name' => 'required|string|max:100',
            'bypass_cooldown' => 'nullable|boolean',
        ]);

        // Find the license
        $license = LicenseKey::where('license_key', strtoupper($validated['license_key']))
            ->whereHas('product', fn ($q) => $q->where('slug', self::PRODUCT_SLUG))
            ->first();

        if (! $license) {
            return response()->json([
                'success' => false,
                'message' => 'License not found',
                'error_code' => 'INVALID_LICENSE',
            ], 404);
        }

        // Store previous device info for audit
        $metadata = json_decode($license->metadata ?? '{}', true);
        $previousDevice = [
            'machine_id' => $license->machine_id,
            'device_id' => $license->device_id,
            'machine_fingerprint' => $license->machine_fingerprint,
            'reset_at' => now()->toISOString(),
            'reset_by' => 'admin',
            'admin_name' => $validated['admin_name'],
            'reason' => $validated['reason'],
            'ip' => $request->ip(),
        ];

        // Update metadata with admin reset history
        $adminResetHistory = $metadata['admin_reset_history'] ?? [];
        if ($license->machine_id) {
            $adminResetHistory[] = $previousDevice;
        }

        $metadata['admin_reset_history'] = array_slice($adminResetHistory, -20); // Keep last 20
        $metadata['last_admin_reset'] = [
            'timestamp' => now()->toISOString(),
            'admin_name' => $validated['admin_name'],
            'reason' => $validated['reason'],
            'ip' => $request->ip(),
            'previous_machine_id' => $license->machine_id,
        ];
        $metadata['total_admin_resets'] = ($metadata['total_admin_resets'] ?? 0) + 1;

        // Add old machine_id to revoked list (IMPORTANT: prevents old device from using license)
        if ($license->machine_id) {
            $revokedMachines = $metadata['revoked_machine_ids'] ?? [];
            $revokedMachines[] = [
                'machine_id' => $license->machine_id,
                'revoked_at' => now()->toISOString(),
                'reason' => 'admin_reset',
                'admin_name' => $validated['admin_name'],
            ];
            $metadata['revoked_machine_ids'] = array_slice($revokedMachines, -20); // Keep last 20
        }

        // If bypass cooldown, clear the customer reset cooldown
        if ($validated['bypass_cooldown'] ?? false) {
            unset($metadata['last_device_reset']);
        }

        // Reset device binding
        $license->update([
            'machine_id' => null,
            'device_id' => null,
            'machine_fingerprint' => null,
            'activated_at' => null,
            'activations' => 0,
            'metadata' => json_encode($metadata),
        ]);

        // Also update the device record - mark as BLOCKED
        if ($previousDevice['machine_id']) {
            $device = AutoTradeXDevice::where('machine_id', $previousDevice['machine_id'])->first();
            if ($device) {
                $device->block("Admin reset by {$validated['admin_name']}: {$validated['reason']}");
            }
        }

        Log::info('AutoTradeX: Device reset by admin', [
            'license_key' => $license->license_key,
            'admin_name' => $validated['admin_name'],
            'reason' => $validated['reason'],
            'previous_machine_id' => $previousDevice['machine_id'],
            'ip' => $request->ip(),
            'total_admin_resets' => $metadata['total_admin_resets'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Device reset successfully by admin',
            'data' => [
                'license_key' => $license->license_key,
                'license_type' => $license->license_type,
                'previous_machine_id' => $previousDevice['machine_id'],
                'reset_by' => $validated['admin_name'],
                'total_admin_resets' => $metadata['total_admin_resets'],
            ],
        ]);
    }

    /**
     * Admin: Get license details
     *
     * GET /api/v1/autotradex/admin/license/{license_key}
     */
    public function adminGetLicense(Request $request, string $licenseKey)
    {
        // Verify admin token
        $adminToken = $request->header('X-Admin-Token');
        $expectedToken = hash('sha256', config('app.key').'autotradex-admin');

        if (! $adminToken || ! hash_equals($expectedToken, $adminToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'error_code' => 'UNAUTHORIZED',
            ], 401);
        }

        $license = LicenseKey::where('license_key', strtoupper($licenseKey))
            ->whereHas('product', fn ($q) => $q->where('slug', self::PRODUCT_SLUG))
            ->with(['order', 'product'])
            ->first();

        if (! $license) {
            return response()->json([
                'success' => false,
                'message' => 'License not found',
                'error_code' => 'NOT_FOUND',
            ], 404);
        }

        $metadata = json_decode($license->metadata ?? '{}', true);

        // Get device info if activated
        $deviceInfo = null;
        if ($license->machine_id) {
            $device = AutoTradeXDevice::where('machine_id', $license->machine_id)->first();
            if ($device) {
                $deviceInfo = [
                    'machine_id' => $device->machine_id,
                    'machine_name' => $device->machine_name,
                    'os_version' => $device->os_version,
                    'app_version' => $device->app_version,
                    'first_ip' => $device->first_ip,
                    'last_ip' => $device->last_ip,
                    'first_seen_at' => $device->first_seen_at?->toISOString(),
                    'last_seen_at' => $device->last_seen_at?->toISOString(),
                    'status' => $device->status,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'license' => [
                    'license_key' => $license->license_key,
                    'status' => $license->status,
                    'license_type' => $license->license_type,
                    'expires_at' => $license->expires_at?->toISOString(),
                    'activated_at' => $license->activated_at?->toISOString(),
                    'machine_id' => $license->machine_id,
                    'activations' => $license->activations,
                    'max_activations' => $license->max_activations,
                    'created_at' => $license->created_at?->toISOString(),
                ],
                'order' => $license->order ? [
                    'order_id' => $license->order->order_id,
                    'email' => $license->order->email,
                    'customer_name' => $license->order->customer_name,
                    'status' => $license->order->status,
                    'total' => $license->order->total,
                    'created_at' => $license->order->created_at?->toISOString(),
                ] : null,
                'device' => $deviceInfo,
                'reset_history' => [
                    'customer_resets' => $metadata['device_reset_history'] ?? [],
                    'admin_resets' => $metadata['admin_reset_history'] ?? [],
                    'total_customer_resets' => $metadata['total_device_resets'] ?? 0,
                    'total_admin_resets' => $metadata['total_admin_resets'] ?? 0,
                    'last_customer_reset' => $metadata['last_device_reset'] ?? null,
                    'last_admin_reset' => $metadata['last_admin_reset'] ?? null,
                ],
            ],
        ]);
    }
}
