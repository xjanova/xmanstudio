<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BugReport;
use App\Models\LicenseActivity;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\ProductDevice;
use App\Services\TpixPay\TpixPriceService;
use App\Support\CloudStorage;
use App\Support\LicensePlans;
use App\Support\LicenseSeats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Generic Product License Controller
 *
 * API Controller ที่รองรับระบบ License สำหรับทุกผลิตภัณฑ์
 * ใช้ product slug เป็น route parameter เพื่อกำหนดผลิตภัณฑ์
 */
class ProductLicenseController extends Controller
{
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
        // BrainX Pro (แอปเดสก์ท็อป) ทดลองได้ 7 วัน — key ที่ซื้อของ `brainx` ใบเดียวปลดทั้ง Pro และ Cloud
        'brainx' => 7,
        // Anti X (กันเจาะเซิร์ฟเวอร์ Windows) ทดลอง Pro ได้ 14 วัน — เจ้าของกำหนด 2026-10-10
        // ไม่ใช่ freemium: ไม่มีคีย์ที่จ่ายแล้ว ตัวแอปเองให้ส่วนฟรี (ตัวจับพื้นฐาน) ไม่ต้องให้ check-machine แจกคีย์ FREE
        'anti-x' => 14,
        // LocalVPN ทดลอง Pro เต็มตัว 30 วัน ครั้งเดียวต่อเครื่อง — เจ้าของกำหนด 2026-10-11 (ดู FREE_TIER_AFTER_TRIAL)
        'localvpn' => 30,
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
        // BrainX บน Windows: machine_id เปลี่ยนเมื่อลง Windows ใหม่เหมือน WinXTools
        'brainx',
        // Anti X บน Windows Server: ลงเครื่องใหม่/คืนค่า snapshot ได้ machine_id ใหม่ แต่ฮาร์ดแวร์เดิม
        'anti-x',
        // LocalVPN ส่ง hash รายเครื่องจริง (Windows: SMBIOS UUID · Android: ANDROID_ID + MediaDrm) ไม่ใช่ hash ระดับรุ่น
        // + drm_id / android_id บน Android — ลบแอปลงใหม่ / ลง Windows ใหม่ก็ไม่ได้ทดลองรอบสอง
        'localvpn',
    ];

    /**
     * Freemium apps that also give a Pro trial — LocalVPN (owner 2026-10-11).
     *
     * The free tier needs a FREE key (FREEMIUM_PRODUCTS), and on top of it every device may run
     * the full Pro app for TRIAL_DAYS, once:
     *   — holding the FREE key does not count as "licensed": a device that has never had a trial
     *     (existing ones included) can still start it
     *   — exactly one trial per device (machine_id), and per hardware (HARDWARE_BOUND_TRIAL_PRODUCTS)
     *   — check-machine and validate answer with the best key on the machine (paid > trial > free):
     *     when the trial or a paid term ends, the device is handed its FREE key again (created if missing)
     */
    private const FREE_TIER_AFTER_TRIAL = [
        'localvpn',
    ];

    /**
     * Freemium apps: check-machine hands every new device a FREE license instead of answering
     * "no license", so the app works on first launch without a key.
     *
     * GigGok (2026-10-05): the app itself is free; what is sold are avatar packs, owned per account
     * through the pack store. Its license key is how the app identifies itself to /api/packs.
     */
    private const FREEMIUM_PRODUCTS = [
        'localvpn',
        'giggok',
    ];

    /**
     * Features per license type where a product differs from the defaults in getFeaturesByType().
     *
     * GigGok's free license is the whole app — nothing is held back for a paid tier, so it must not
     * report the demo set ('trial_mode'). LocalVPN's 'free' is deliberately the default set (its app
     * still reads it); its trial is the full Pro app, so 'demo' says so.
     */
    private const PRODUCT_FEATURES = [
        'giggok' => [
            'free' => ['all_features', 'pack_store', 'in_app_updates'],
        ],
        'localvpn' => [
            'demo' => ['all_features', 'trial_mode'],
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

        $canStartTrial = $this->isFreeTierTrial($product)
            ? $this->freeTierTrialRefusal($product, $device, $validated['machine_id'], $validated['hardware_hash'] ?? null, $validated['drm_id'] ?? null, $validated['android_id'] ?? null) === null
            : $device->canStartTrial();

        return response()->json([
            'success' => true,
            'data' => [
                'device_status' => $device->status,
                'is_suspicious' => $device->is_suspicious,
                'can_start_trial' => $canStartTrial,
                'trial_info' => $device->status === ProductDevice::STATUS_TRIAL ? [
                    'expires_at' => $device->trial_expires_at?->toISOString(),
                    'days_remaining' => $device->trialDaysRemaining(),
                    'is_expired' => $device->isTrialExpired(),
                ] : null,
                // LocalVPN's checkouts give no early-bird discount — never promise one
                'early_bird' => ! $this->isFreeTierTrial($product) && $device->isEligibleForEarlyBird() ? [
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
            'android_id' => 'nullable|string|max:64',
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
        if (! empty($validated['android_id']) && $this->isFreeTierTrial($product)) {
            $createData['android_id'] = $validated['android_id'];
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
        if ($this->isFreeTierTrial($product)) {
            if (! empty($validated['android_id']) && ! $device->android_id) {
                $updateData['android_id'] = $validated['android_id'];
            }
            // เครื่องที่ลงทะเบียนไว้ตั้งแต่แอปรุ่นเก่า (ไม่มี hash) — เก็บไว้ตอนนี้ เครื่องอื่นที่ฮาร์ดแวร์เดียวกันจะเห็นว่าใช้สิทธิ์แล้ว
            if (! empty($validated['hardware_hash']) && ! $device->hardware_hash) {
                $updateData['hardware_hash'] = $validated['hardware_hash'];
            }
        }
        $device->update($updateData);

        if ($this->isFreeTierTrial($product)) {
            return $this->startFreeTierTrial($request, $product, $device, $validated);
        }

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
        $usedOnThisHardware = $this->trialUsedOnThisHardware($product, $device, $request->input('hardware_hash'), $drmId, $request->input('android_id'));

        // LocalVPN: ทดลองได้ครั้งเดียวต่อเครื่อง และคีย์ FREE ไม่นับเป็น "มี License" — กฎเดียวกับที่ /demo ใช้
        $freeTierRefusal = $this->isFreeTierTrial($product)
            ? $this->freeTierTrialRefusal($product, $device, $validated['machine_id'], $request->input('hardware_hash'), $drmId, $request->input('android_id'))
            : null;

        if (! $device) {
            return response()->json([
                'success' => true,
                'data' => [
                    'has_used_demo' => $usedOnThisHardware || in_array($freeTierRefusal, ['TRIAL_ALREADY_USED', 'TRIAL_ACTIVE'], true),
                    'can_start_demo' => $this->isFreeTierTrial($product) ? $freeTierRefusal === null : ! $usedOnThisHardware,
                ],
            ]);
        }

        $isTrialActive = $device->status === ProductDevice::STATUS_TRIAL && ! $device->isTrialExpired();

        return response()->json([
            'success' => true,
            'server_time' => now()->toISOString(),
            'data' => [
                'has_used_demo' => $device->trial_attempts > 0 || $usedOnThisHardware
                    || in_array($freeTierRefusal, ['TRIAL_ALREADY_USED', 'TRIAL_ACTIVE'], true),
                'can_start_demo' => $this->isFreeTierTrial($product)
                    ? $freeTierRefusal === null
                    : $device->canStartTrial() && ! $usedOnThisHardware,
                'is_trial_active' => $isTrialActive,
                'trial_info' => $device->trial_expires_at ? [
                    'expires_at' => $device->trial_expires_at->toISOString(),
                    'days_remaining' => $device->trialDaysRemaining(),
                    'hours_remaining' => $device->trialHoursRemaining(),
                    'seconds_remaining' => $device->trialSecondsRemaining(),
                    'is_expired' => $device->isTrialExpired(),
                ] : null,
                'early_bird' => ! $this->isFreeTierTrial($product) && $device->isEligibleForEarlyBird() ? [
                    'eligible' => true,
                    'discount_percent' => 20,
                    'days_remaining' => $device->trialDaysRemaining(),
                ] : ['eligible' => false],
            ],
        ]);
    }

    private function isFreeTierTrial(Product $product): bool
    {
        return in_array($product->slug, self::FREE_TIER_AFTER_TRIAL, true);
    }

    /**
     * FREE_TIER_AFTER_TRIAL: the key the device should switch to instead of $license, or null to keep it.
     *
     * A key that no longer runs (trial over, term ended, revoked) gives way to the machine's best
     * key — and to its FREE key, created if missing, when there is none. A key that still runs
     * gives way only to a better tier: the FREE key to a trial or a paid key the machine has now,
     * a trial to a key bought meanwhile.
     */
    private function keyToHandOver(Product $product, LicenseKey $license, bool $isValid, string $machineId): ?LicenseKey
    {
        $best = LicenseSeats::best($product, $machineId);

        if ($isValid && ($best === null || LicenseSeats::tier($best) >= LicenseSeats::tier($license))) {
            return null;
        }

        $best ??= LicenseKey::freeFor($product, $machineId);

        return $best->id === $license->id ? null : $best;
    }

    /** The keys check-machine looks at come best first for a product whose machines can hold several. */
    private function picksBestKey(Product $product): bool
    {
        return $this->isFreeTierTrial($product) || LicenseSeats::perKey($product) > 1;
    }

    /** First match of a check-machine query — the best of them where a machine can hold several keys. */
    private function firstKey($query, Product $product): ?LicenseKey
    {
        return $this->picksBestKey($product)
            ? LicenseSeats::sortBest($query->get()->reject(fn (LicenseKey $key) => $key->isExpired()))->first()
            : $query->first();
    }

    /**
     * FREE_TIER_AFTER_TRIAL: why this device cannot start its one trial, or null when it can.
     *
     * The FREE key every device holds is not a licence here. What stops a trial: a blocked
     * device, a trial running now, a paid key on the machine, any trial this machine ever had
     * (a trial_attempts count, or a DEMO key issued to it), or a trial on the same hardware
     * under another machine id.
     */
    private function freeTierTrialRefusal(Product $product, ?ProductDevice $device, string $machineId, mixed $hardwareHash, mixed $drmId = null, mixed $androidId = null): ?string
    {
        if ($device?->status === ProductDevice::STATUS_BLOCKED) {
            return 'DEVICE_BLOCKED';
        }

        $best = LicenseSeats::best($product, $machineId);

        if ($best && LicenseSeats::tier($best) === LicenseSeats::TIER_PAID) {
            return 'ALREADY_LICENSED';
        }

        if (($device?->status === ProductDevice::STATUS_TRIAL && ! $device->isTrialExpired())
            || ($best && LicenseSeats::tier($best) === LicenseSeats::TIER_TRIAL)) {
            return 'TRIAL_ACTIVE';
        }

        if (($device && ($device->trial_attempts > 0 || $device->first_trial_at !== null))
            || LicenseKey::where('product_id', $product->id)
                ->where('machine_id', $machineId)
                ->where('license_type', LicenseKey::TYPE_DEMO)
                ->exists()) {
            return 'TRIAL_ALREADY_USED';
        }

        if ($this->trialUsedOnThisHardware($product, $device, $hardwareHash, $drmId, $androidId)) {
            return 'TRIAL_USED_ON_THIS_HARDWARE';
        }

        return null;
    }

    /**
     * FREE_TIER_AFTER_TRIAL (LocalVPN): the one Pro trial of this device.
     *
     * Same answers as the generic path (a DEMO key, TRIAL_DAYS long). A device the app identifies
     * properly (hardware_hash / drm_id / android_id) is judged by freeTierTrialRefusal() alone —
     * the old shared-IP heuristics would only turn away friends behind one router or on a mobile
     * network's shared address. An app that sends no hardware id still goes through them.
     */
    private function startFreeTierTrial(Request $request, Product $product, ProductDevice $device, array $validated)
    {
        $machineId = $validated['machine_id'];
        $hardwareHash = $validated['hardware_hash'] ?? null;
        $drmId = $validated['drm_id'] ?? null;
        $androidId = $validated['android_id'] ?? null;

        if ($refusal = $this->freeTierTrialRefusal($product, $device, $machineId, $hardwareHash, $drmId, $androidId)) {
            return $this->trialRefused($device, $refusal);
        }

        if (! $hardwareHash && ! $drmId && ! $androidId) {
            if ($device->is_suspicious) {
                return $this->trialRefused($device, 'TRIAL_NOT_AVAILABLE');
            }

            $abuseCheck = $device->checkTrialAbuse();
            if ($abuseCheck['is_abuse']) {
                $device->markSuspicious(implode('; ', $abuseCheck['reasons']));

                if (count($abuseCheck['reasons']) >= 2) {
                    $device->block('Multiple abuse indicators detected');

                    return response()->json([
                        'success' => false,
                        'error_code' => 'TRIAL_ABUSE_DETECTED',
                        'message' => 'ตรวจพบการใช้งาน Trial ไม่เหมาะสม',
                        'reasons' => $abuseCheck['reasons'],
                    ], 403);
                }

                return $this->trialRefused($device->fresh(), 'TRIAL_NOT_AVAILABLE');
            }
        }

        $trialDays = self::TRIAL_DAYS[$product->slug] ?? 1;

        // One device row at a time: a double tap must not hand out two DEMO keys
        $started = DB::transaction(function () use ($device, $product, $machineId, $trialDays, $request) {
            $locked = ProductDevice::whereKey($device->id)->lockForUpdate()->first();

            if (! $locked || $locked->trial_attempts > 0 || $locked->first_trial_at !== null) {
                return null;
            }

            $locked->update([
                'status' => ProductDevice::STATUS_TRIAL,
                'trial_attempts' => $locked->trial_attempts + 1,
                'first_trial_at' => now(),
                'trial_expires_at' => now()->addDays($trialDays),
            ]);

            $license = LicenseKey::create([
                'product_id' => $product->id,
                'license_key' => LicenseKey::generateDemoKey(),
                'license_type' => LicenseKey::TYPE_DEMO,
                'status' => LicenseKey::STATUS_ACTIVE,
                'machine_id' => $machineId,
                'activated_at' => now(),
                'expires_at' => now()->addDays($trialDays),
                'max_activations' => 1,
                'activations' => 1,
                'metadata' => [
                    'product_slug' => $product->slug,
                    'started_at' => now()->toISOString(),
                    'ip' => $request->ip(),
                ],
            ]);

            $locked->update(['license_id' => $license->id]);

            return [$locked, $license];
        });

        if ($started === null) {
            return $this->trialRefused($device->fresh(), 'TRIAL_ACTIVE');
        }

        [$device, $license] = $started;
        $device->updateRelatedDevices();

        LicenseActivity::log($license, LicenseActivity::ACTION_CREATED, LicenseActivity::ACTOR_API, null, $machineId,
            'เริ่มใช้งาน Trial', ['product_slug' => $product->slug, 'trial_days' => $trialDays]);
        LicenseActivity::log($license, LicenseActivity::ACTION_ACTIVATED, LicenseActivity::ACTOR_API, null, $machineId,
            'เปิดใช้งาน Trial', ['product_slug' => $product->slug]);

        return response()->json([
            'success' => true,
            'server_time' => now()->toISOString(),
            'message' => "เริ่มใช้งาน Trial {$trialDays} วันสำเร็จ",
            'data' => [
                'license_key' => $license->license_key,
                'license_type' => LicenseKey::TYPE_DEMO,
                'expires_at' => $license->expires_at->toISOString(),
                'days_remaining' => $trialDays,
                'hours_remaining' => $trialDays * 24,
                'seconds_remaining' => $trialDays * 24 * 3600,
                'features' => $this->getFeaturesByType($product->slug, LicenseKey::TYPE_DEMO),
                'early_bird' => ['eligible' => false],
            ],
        ]);
    }

    /** The 403 a refused trial gets — the same shape the generic path answers with. */
    private function trialRefused(?ProductDevice $device, string $reason)
    {
        return response()->json([
            'success' => false,
            'server_time' => now()->toISOString(),
            'error_code' => $reason,
            'message' => match ($reason) {
                'TRIAL_ALREADY_USED', 'TRIAL_USED_ON_THIS_HARDWARE' => 'เครื่องนี้เคยใช้สิทธิ์ทดลองใช้ไปแล้ว',
                'TRIAL_ACTIVE' => 'เครื่องนี้กำลังทดลองใช้อยู่',
                'ALREADY_LICENSED' => 'เครื่องนี้มี License แล้ว',
                'DEVICE_BLOCKED' => 'เครื่องนี้ถูกระงับการใช้งาน',
                default => 'ไม่สามารถเริ่ม Trial ได้',
            },
            'trial_info' => $device?->status === ProductDevice::STATUS_TRIAL ? [
                'expires_at' => $device->trial_expires_at?->toISOString(),
                'days_remaining' => $device->trialDaysRemaining(),
                'hours_remaining' => $device->trialHoursRemaining(),
                'seconds_remaining' => $device->trialSecondsRemaining(),
            ] : null,
        ], 403);
    }

    /**
     * สิทธิ์ทดลองของฮาร์ดแวร์นี้ถูกใช้ไปแล้วบนเครื่องอื่น (machine_id อื่น) หรือยัง
     *
     * เฉพาะผลิตภัณฑ์ใน HARDWARE_BOUND_TRIAL_PRODUCTS — ตัวอื่นได้ false เสมอ (พฤติกรรมเดิม)
     * ใช้ hardware_hash ที่ส่งมากับคำขอก่อน ไม่มีค่อยใช้ค่าที่เครื่องนี้ลงทะเบียนไว้ ไม่มีทั้งคู่ = ไม่ตัดสิทธิ์
     * (แอปส่ง null เมื่อ firmware เป็นค่าขยะ ดีกว่าให้หลายเครื่องได้ hash เดียวกันแล้วโดนตัดสิทธิ์พร้อมกัน)
     *
     * drm_id (MediaDrm) / android_id ผูกสิทธิ์ด้วยเหมือน hash — เครื่อง Android เครื่องเดิมที่ได้ machine_id ใหม่
     * (แอป Windows ไม่ส่งสองค่านี้ จึงไม่มีผลกับ WinXTools / BrainX / Anti X)
     */
    private function trialUsedOnThisHardware(Product $product, ?ProductDevice $device, mixed $hardwareHash, mixed $drmId = null, mixed $androidId = null): bool
    {
        if (! in_array($product->slug, self::HARDWARE_BOUND_TRIAL_PRODUCTS, true)) {
            return false;
        }

        $pick = fn (mixed $sent, ?string $stored, int $max) => is_string($sent) && $sent !== '' && strlen($sent) <= $max ? $sent : $stored;

        $hardwareHash = $pick($hardwareHash, $device?->hardware_hash, 64);
        $drmId = $pick($drmId, $device?->drm_id, 128);
        $androidId = $pick($androidId, $device?->android_id, 64);

        // ไม่มีอะไรให้เทียบเลย = ไม่ตัดสิทธิ์ (และกัน nested where ว่างที่ Laravel ทิ้งทั้งก้อนแล้วได้ "ทุกเครื่อง")
        if (empty($hardwareHash) && empty($drmId) && empty($androidId)) {
            return false;
        }

        return ProductDevice::where('product_id', $product->id)
            ->where(function ($query) use ($hardwareHash, $drmId, $androidId) {
                if (! empty($hardwareHash)) {
                    $query->orWhere('hardware_hash', $hardwareHash);
                }
                if (! empty($drmId)) {
                    $query->orWhere('drm_id', $drmId);
                }
                if (! empty($androidId)) {
                    $query->orWhere('android_id', $androidId);
                }
            })
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

        // หนึ่งคีย์หลายเครื่อง (LocalVPN: PC + มือถือ) — เครื่องถัดไปเป็น "ที่นั่ง" ของคีย์เดียวกัน ไม่แย่งเครื่องแรก
        if (LicenseSeats::isShared($license, $product)) {
            return $this->activateSeat($request, $product, $license, $validated);
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
                $this->moveDeviceRow($product, $previousMachineId, $validated['machine_id']);
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
     * Activate a shared key (config/licenses.php devices_per_key — LocalVPN: one key, a PC + a phone).
     *
     *   — a machine that already holds the key: refreshed, nothing else changes
     *   — a free place: the first machine goes on the key itself, every further one is a seat
     *   — the same phone under a new machine id (drm_id / android_id match): its old place is freed first
     *   — full: ALREADY_ACTIVATED_OTHER_DEVICE, or with force_rebind the machine seen least recently
     *     gives up its place (a replaced phone, not the PC that is still in use)
     */
    private function activateSeat(Request $request, Product $product, LicenseKey $license, array $validated)
    {
        $machineId = $validated['machine_id'];
        $drmId = $validated['drm_id'] ?? null;
        $androidId = $validated['android_id'] ?? null;
        $forceRebind = filter_var($validated['force_rebind'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // One change of a key's seats at a time — two devices activating at once must not both take the last place
        $outcome = DB::transaction(function () use ($request, $product, $license, $validated, $machineId, $drmId, $androidId, $forceRebind) {
            $license = LicenseKey::whereKey($license->id)->lockForUpdate()->first();
            $released = [];

            if (! LicenseSeats::holds($license, $product, $machineId)) {
                foreach (LicenseSeats::machines($license, $product) as $machine) {
                    if ($this->sameDevice($product, $license, $machine, $drmId, $androidId)) {
                        LicenseSeats::release($license, $product, $machine);
                        $released[] = $machine;
                    }
                }

                if (LicenseSeats::machines($license->refresh(), $product)->count() >= $license->max_activations) {
                    if (! $forceRebind) {
                        return ['full' => true, 'license' => $license];
                    }

                    $stalest = LicenseSeats::stalest($license, $product);
                    LicenseSeats::release($license, $product, $stalest);
                    $released[] = $stalest;
                }
            }

            $license->refresh();
            $isKeyMachine = ! $license->machine_id || $license->machine_id === $machineId;

            if ($isKeyMachine && ! $license->activateOnMachine($machineId, $validated['machine_fingerprint'])) {
                return ['failed' => true];
            }

            // drm_id / android_id on the key name its own machine — a seat keeps them on its device row,
            // so check-machine's fallbacks can never move the key away from the PC to the phone
            if ($isKeyMachine && ($drmId || $androidId)) {
                $license->update(array_filter(['drm_id' => $drmId, 'android_id' => $androidId]));
            }

            ProductDevice::updateOrCreate(
                ['product_id' => $product->id, 'machine_id' => $machineId],
                array_filter([
                    'status' => ProductDevice::STATUS_LICENSED,
                    'license_id' => $license->id,
                    'last_ip' => $request->ip(),
                    'last_seen_at' => now(),
                    'app_version' => $validated['app_version'] ?? null,
                    'drm_id' => $drmId,
                    'android_id' => $androidId,
                ], fn ($value) => $value !== null)
            );

            LicenseSeats::recount($license, $product);

            return ['license' => $license->fresh(), 'released' => $released];
        });

        if (! empty($outcome['failed'])) {
            return response()->json([
                'success' => false,
                'error_code' => 'ACTIVATION_FAILED',
                'message' => 'ไม่สามารถเปิดใช้งาน License ได้',
            ], 500);
        }

        $license = $outcome['license'];
        $maxDevices = (int) $license->max_activations;

        if (! empty($outcome['full'])) {
            return response()->json([
                'success' => false,
                'error_code' => 'ALREADY_ACTIVATED_OTHER_DEVICE',
                'message' => "License นี้ใช้ครบ {$maxDevices} เครื่องแล้ว — ยกเลิกบนเครื่องเดิมก่อน หรือย้ายมาเครื่องนี้",
                'max_devices' => $maxDevices,
            ], 403);
        }

        foreach ($outcome['released'] as $machine) {
            LicenseSeats::logRelease($license, $machine, $machineId, 'ย้ายที่นั่งของคีย์มาเครื่องนี้');
        }

        LicenseActivity::log(
            $license,
            LicenseActivity::ACTION_ACTIVATED,
            LicenseActivity::ACTOR_API,
            null,
            $machineId,
            'เปิดใช้งาน License ผ่าน API',
            array_filter([
                'product_slug' => $product->slug,
                'app_version' => $validated['app_version'] ?? null,
                'devices' => $license->activations,
                'previous_machine_ids' => $outcome['released'] ?: null,
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
                'features' => $this->getFeaturesByType($product->slug, $license->license_type),
                'devices_used' => (int) $license->activations,
                'max_devices' => $maxDevices,
            ],
        ]);
    }

    /** Whether $machine (a holder of the key) is the device now asking under another id — same drm_id / android_id. */
    private function sameDevice(Product $product, LicenseKey $license, string $machine, ?string $drmId, ?string $androidId): bool
    {
        if (! $drmId && ! $androidId) {
            return false;
        }

        $known = ProductDevice::where('product_id', $product->id)->where('machine_id', $machine)->first();
        $pairs = [[$drmId, $known?->drm_id], [$androidId, $known?->android_id]];

        if ($license->machine_id === $machine) {
            $pairs[] = [$drmId, $license->drm_id];
            $pairs[] = [$androidId, $license->android_id];
        }

        foreach ($pairs as [$sent, $stored]) {
            if ($sent && $stored && $sent === $stored) {
                return true;
            }
        }

        return false;
    }

    /**
     * Point a device's row at its new machine id (HWID migration).
     *
     * The app usually registers the new id first (register-device), so a row for it may exist
     * already — renaming the old row onto it broke the unique (product_id, machine_id) index and
     * answered 500. Then the new row takes over the old one's licence and trial history, and the
     * old row is left unlinked.
     */
    private function moveDeviceRow(Product $product, ?string $from, string $to): void
    {
        if (! $from || $from === $to) {
            return;
        }

        $source = ProductDevice::where('product_id', $product->id)->where('machine_id', $from)->first();
        if (! $source) {
            return;
        }

        $target = ProductDevice::where('product_id', $product->id)->where('machine_id', $to)->first();
        if (! $target) {
            $source->update(['machine_id' => $to]);

            return;
        }

        $target->update(array_filter([
            'status' => $source->status,
            'license_id' => $source->license_id,
            'trial_attempts' => max((int) $source->trial_attempts, (int) $target->trial_attempts),
            'first_trial_at' => $target->first_trial_at ?? $source->first_trial_at,
            'trial_expires_at' => $source->trial_expires_at ?? $target->trial_expires_at,
            'drm_id' => $target->drm_id ?? $source->drm_id,
            'android_id' => $target->android_id ?? $source->android_id,
        ], fn ($value) => $value !== null));

        $source->update(['status' => ProductDevice::STATUS_PENDING, 'license_id' => null]);
    }

    /**
     * check-machine found a shared key through drm_id / android_id under another machine id: the same
     * phone (or PC) came back with a new id. Its own place on the key moves to the new id; the
     * other device keeps its place. False when the old id holds no place and the key is full —
     * then nothing moves.
     */
    private function moveSeat(Product $product, LicenseKey $license, ?string $from, string $to, ?string $drmId, ?string $androidId): bool
    {
        return DB::transaction(function () use ($product, $license, $from, $to, $drmId, $androidId) {
            $license = LicenseKey::whereKey($license->id)->lockForUpdate()->first();

            if ($from && $from !== $to && LicenseSeats::holds($license, $product, $from)) {
                LicenseSeats::release($license, $product, $from);
            } elseif (! LicenseSeats::holds($license, $product, $to)
                && LicenseSeats::machines($license, $product)->count() >= $license->max_activations) {
                return false;
            }

            $license->refresh();

            if (! $license->machine_id || $license->machine_id === $to) {
                $license->update(array_filter([
                    'machine_id' => $to,
                    'last_validated_at' => now(),
                    'drm_id' => $drmId,
                    'android_id' => $androidId,
                ]));
            }

            ProductDevice::updateOrCreate(
                ['product_id' => $product->id, 'machine_id' => $to],
                array_filter([
                    'status' => ProductDevice::STATUS_LICENSED,
                    'license_id' => $license->id,
                    'last_seen_at' => now(),
                    'drm_id' => $drmId,
                    'android_id' => $androidId,
                ])
            );

            LicenseSeats::recount($license, $product);

            return true;
        });
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
            ->first();

        // ของเครื่องนี้: machine_id ของคีย์ — หรือ "ที่นั่ง" ของคีย์ที่ใช้ได้หลายเครื่อง (LicenseSeats)
        if ($license && ! LicenseSeats::holds($license, $product, $validated['machine_id'])) {
            $license = null;
        }

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

        // LocalVPN: answer with the key the device should run under now — a trial or term that ended
        // falls back to the machine's best key, and to its FREE key when there is nothing better
        if ($this->isFreeTierTrial($product)) {
            $better = $this->keyToHandOver($product, $license, $isValid, $validated['machine_id']);

            if ($better !== null) {
                return response()->json([
                    'success' => true,
                    'is_valid' => true,
                    'license_changed' => true,
                    'previous_license' => [
                        'license_key' => $license->license_key,
                        'license_type' => $license->license_type,
                        'status' => $license->status,
                        'is_expired' => $license->isExpired(),
                    ],
                    'data' => [
                        'license_key' => $better->license_key,
                        'license_type' => $better->license_type,
                        'status' => $better->status,
                        'expires_at' => $better->expires_at?->toISOString(),
                        'days_remaining' => $better->daysRemaining(),
                        'is_expired' => false,
                        'features' => $this->getFeaturesByType($productSlug, $better->license_type),
                    ],
                ]);
            }
        }

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
            ] + CloudStorage::apiFields($license->setRelation('product', $product)),
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
        $fromMachine = null; // the machine id the key was found under when a fallback matched

        // ── Primary: lookup by machine_id ──────────────────────────────────
        // LocalVPN: the best of the machine's keys (paid, then a running trial, then FREE), seats of a shared key included
        $license = $this->picksBestKey($product)
            ? LicenseSeats::best($product, $validated['machine_id'])
            : LicenseKey::where('product_id', $product->id)
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
            $license = $this->firstKey(LicenseKey::where('product_id', $product->id)
                ->where('drm_id', $drmId)
                ->where('status', LicenseKey::STATUS_ACTIVE)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                }), $product);
            $fromMachine = $license?->machine_id;

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
                    $fromMachine = $device->machine_id;
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
            $license = $this->firstKey(LicenseKey::where('product_id', $product->id)
                ->where('android_id', $androidId)
                ->where('status', LicenseKey::STATUS_ACTIVE)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                }), $product);
            $fromMachine = $license?->machine_id;

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
                    $fromMachine = $device->machine_id;
                }
            }

            if ($license) {
                $migratedFrom = 'android_id';
            }
        }

        // A shared key moves only the place of the device that was found — never the other device's
        if ($license && $migratedFrom && LicenseSeats::isShared($license, $product)) {
            if (! $this->moveSeat($product, $license, $fromMachine, $validated['machine_id'], $drmId, $androidId)) {
                $license = null;
                $migratedFrom = null;
            }
        } elseif ($license && $migratedFrom) {
            // ── If found via fallback: migrate machine_id to current one ────────
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
                $this->moveDeviceRow($product, $previousMachineId, $validated['machine_id']);

                $deviceUpdate = array_filter(['drm_id' => $drmId, 'android_id' => $androidId]);
                if ($deviceUpdate) {
                    ProductDevice::where('product_id', $product->id)
                        ->where('machine_id', $validated['machine_id'])
                        ->update($deviceUpdate);
                }
            }
        }

        if (! $license) {
            // Freemium (LocalVPN, GigGok): auto-create a free license for new devices.
            // Use firstOrCreate to prevent duplicates from concurrent requests.
            if (in_array($productSlug, self::FREEMIUM_PRODUCTS, true)) {
                $license = LicenseKey::freeFor($product, $validated['machine_id'], $drmId, $androidId);

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
        // (a shared key keeps the ids of its own machine only — a seat's ids stay on its device row)
        $updateData = ['last_validated_at' => now()];
        $idsBelongToKey = ! LicenseSeats::isShared($license, $product) || $license->machine_id === $validated['machine_id'];
        if ($drmId && ! $license->drm_id && $idsBelongToKey) {
            $updateData['drm_id'] = $drmId;
        }
        if ($androidId && ! $license->android_id && $idsBelongToKey) {
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
            ->first();

        if ($license && ! LicenseSeats::holds($license, $product, $validated['machine_id'])) {
            $license = null;
        }

        if (! $license) {
            return response()->json([
                'success' => false,
                'error_code' => 'INVALID_LICENSE',
                'message' => 'License ไม่ถูกต้องหรือไม่ตรงกับเครื่อง',
            ], 404);
        }

        // A shared key frees only this machine's place — the other device keeps running
        if (LicenseSeats::isShared($license, $product)) {
            LicenseSeats::release($license, $product, $validated['machine_id']);
            $license->refresh();

            LicenseActivity::log(
                $license,
                LicenseActivity::ACTION_DEACTIVATED,
                LicenseActivity::ACTOR_API,
                null,
                $validated['machine_id'],
                'ยกเลิกการเปิดใช้งาน License ผ่าน API',
                ['product_slug' => $productSlug, 'previous_machine_id' => $validated['machine_id'], 'devices' => $license->activations]
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

        // cloud_storage_bytes: the space this key carries on the product's cloud (BrainX Cloud
        // enforces it, App\Support\CloudStorage) — absent for a product without cloud space
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
            ] + CloudStorage::apiFields($license->setRelation('product', $product)),
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

        // เฉพาะแผนที่ผลิตภัณฑ์นี้ขายจริง ตามลำดับในตารางราคา (config/licenses.php 'plans')
        // สินค้าที่ไม่ขายแผนไหนเลย (Aipray ฟรี, ตัวที่ยังไม่เปิดขาย) ได้ plans ว่าง ไม่ใช่ราคาที่แต่งขึ้น
        // TPIX (additive, only while TPIX prices are shown — the payload is otherwise unchanged):
        // each plan's price in TPIX at the live TPIX DEX price, and the rate behind them
        $tpix = app(TpixPriceService::class);
        $tpixRate = $tpix->apiRate();

        $plans = [];
        foreach (LicensePlans::for($product->slug) as $type => $price) {
            $plans[$type] = [
                'price' => $price,
                'currency' => LicensePlans::CURRENCY,
                'duration_days' => self::PLAN_DURATION_DAYS[$type],
                'features' => $this->getFeaturesByType($productSlug, $type),
            ] + ($tpixRate !== null ? ['price_tpix' => $tpix->apiPrice($price)] : []);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'product' => [
                    'name' => $product->name,
                    'slug' => $product->slug,
                ],
                // {} ไม่ใช่ [] เมื่อว่าง — แอปอ่าน plans เป็น object/map เสมอ
                'plans' => $plans ?: new \stdClass,
                // หน้าเว็บที่ซื้อได้ — null ถ้าสินค้าปิดขายอยู่ (หน้าสินค้าตอบ 404) หรือยังเป็น Coming Soon (ยังไม่เปิดขาย 2026-10-12)
                'purchase_url' => $product->isAvailable() ? route('products.show', $product->slug) : null,
            ] + ($tpixRate !== null ? ['tpix' => $tpixRate] : []),
        ]);
    }

    /**
     * Get features based on license type
     */
    private function getFeaturesByType(string $productSlug, string $type): array
    {
        if (isset(self::PRODUCT_FEATURES[$productSlug][$type])) {
            return self::PRODUCT_FEATURES[$productSlug][$type];
        }

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
