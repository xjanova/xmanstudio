<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PaymentConfirmedMail;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\Setting;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Services\FcmNotificationService;
use App\Services\LicenseService;
use App\Services\SmsPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SmsPaymentController extends Controller
{
    public function __construct(
        protected SmsPaymentService $smsPaymentService,
        protected FcmNotificationService $fcmService
    ) {}

    /**
     * Display SMS Payment settings page.
     */
    public function settings()
    {
        $activeDevices = SmsCheckerDevice::where('status', 'active')->count();
        $smsToday = SmsPaymentNotification::whereDate('created_at', today())->count();
        $devices = SmsCheckerDevice::orderBy('created_at', 'desc')->get();

        // FCM settings
        $fcmEnabled = Setting::getValue('fcm_enabled', false);
        $fcmProjectId = Setting::getValue('fcm_project_id', '');
        $fcmCredentialsPath = Setting::getValue('fcm_credentials_path', '');
        $fcmServiceAccount = null;

        if ($fcmCredentialsPath && file_exists($fcmCredentialsPath)) {
            try {
                $creds = json_decode(file_get_contents($fcmCredentialsPath), true);
                $fcmServiceAccount = $creds['client_email'] ?? null;
            } catch (\Exception $e) {
                // ignore
            }
        }

        return view('admin.sms-payment.settings', compact(
            'activeDevices', 'smsToday', 'devices',
            'fcmEnabled', 'fcmProjectId', 'fcmCredentialsPath', 'fcmServiceAccount'
        ));
    }

    /**
     * Update FCM settings.
     */
    public function updateFcmSettings(Request $request)
    {
        $request->validate([
            'fcm_project_id' => 'required|string|max:100',
            'fcm_credentials' => 'nullable|file|mimes:json|max:512',
            'fcm_enabled' => 'nullable|boolean',
        ]);

        // Save Project ID
        Setting::setValue('fcm_project_id', $request->fcm_project_id, 'string', 'fcm', 'Firebase Project ID');

        // Save enabled status
        Setting::setValue('fcm_enabled', $request->boolean('fcm_enabled') ? '1' : '0', 'boolean', 'fcm', 'FCM Enabled');

        // Handle JSON file upload
        if ($request->hasFile('fcm_credentials')) {
            $file = $request->file('fcm_credentials');

            // Validate JSON content
            $content = file_get_contents($file->getRealPath());
            $json = json_decode($content, true);

            if (! $json || empty($json['project_id']) || empty($json['private_key']) || empty($json['client_email'])) {
                return redirect()->back()->with('error', 'ไฟล์ JSON ไม่ถูกต้อง ต้องเป็น Firebase Service Account JSON');
            }

            // Save to storage
            $storagePath = storage_path('app/firebase-credentials.json');
            file_put_contents($storagePath, $content);
            chmod($storagePath, 0600);

            Setting::setValue('fcm_credentials_path', $storagePath, 'string', 'fcm', 'Firebase Credentials Path');

            Log::info('FCM: Credentials file uploaded', [
                'admin_id' => auth()->id(),
                'service_account' => $json['client_email'],
            ]);
        }

        return redirect()
            ->route('admin.sms-payment.settings')
            ->with('success', 'บันทึกการตั้งค่า FCM เรียบร้อยแล้ว');
    }

    /**
     * Test FCM push notification.
     */
    public function testFcm()
    {
        // Debug: Check device state before sending
        $allDevices = SmsCheckerDevice::where('status', 'active')->get();
        $devicesWithToken = $allDevices->filter(fn ($d) => ! empty($d->fcm_token));

        \Log::info('FCM Test: Device state check', [
            'active_devices' => $allDevices->count(),
            'devices_with_fcm_token' => $devicesWithToken->count(),
            'device_details' => $allDevices->map(fn ($d) => [
                'id' => $d->id,
                'device_id' => $d->device_id,
                'device_name' => $d->device_name ?? 'N/A',
                'status' => $d->status,
                'fcm_token' => $d->fcm_token ? substr($d->fcm_token, 0, 30) . '...' : '(NULL)',
                'last_active_at' => $d->last_active_at,
            ])->toArray(),
        ]);

        if ($devicesWithToken->isEmpty()) {
            $msg = "ส่ง FCM test push ล้มเหลว: มีอุปกรณ์ active {$allDevices->count()} เครื่อง แต่ไม่มีเครื่องไหนมี FCM token (อุปกรณ์ยังไม่ได้ส่ง token มาเซิร์ฟเวอร์)";

            return redirect()
                ->route('admin.sms-payment.settings')
                ->with('error', $msg);
        }

        $fcmService = app(FcmNotificationService::class);
        $result = $fcmService->triggerSync();

        if ($result) {
            return redirect()
                ->route('admin.sms-payment.settings')
                ->with('success', "ส่ง FCM test push สำเร็จ! ส่งไปยัง {$devicesWithToken->count()} อุปกรณ์");
        }

        return redirect()
            ->route('admin.sms-payment.settings')
            ->with('error', 'ส่ง FCM test push ล้มเหลว (มี token แต่ส่งไม่สำเร็จ) ตรวจสอบ log สำหรับรายละเอียด');
    }

    /**
     * Display SMS payment dashboard.
     */
    public function index()
    {
        $stats = [
            'verified_today' => SmsPaymentNotification::whereDate('created_at', today())
                ->whereIn('status', ['matched', 'confirmed'])->count(),
            'pending_orders' => Order::whereNotNull('unique_payment_amount_id')
                ->whereIn('sms_verification_status', ['pending', 'matched'])
                ->where('payment_status', '!=', 'paid')
                ->count(),
            'active_devices' => SmsCheckerDevice::where('status', 'active')->count(),
            'sms_today' => SmsPaymentNotification::whereDate('created_at', today())->count(),
        ];

        $recentMatched = SmsPaymentNotification::with('matchedOrder')
            ->whereIn('status', ['matched', 'confirmed'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentNotifications = SmsPaymentNotification::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.sms-payment.index', compact('stats', 'recentMatched', 'recentNotifications'));
    }

    /**
     * Display list of devices.
     */
    public function devices()
    {
        $devices = SmsCheckerDevice::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.sms-payment.devices', compact('devices'));
    }

    /**
     * Show create device form.
     */
    public function createDevice()
    {
        return view('admin.sms-payment.device-create');
    }

    /**
     * Store new device.
     */
    public function storeDevice(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'approval_mode' => 'nullable|in:auto,manual,smart',
            'description' => 'nullable|string|max:500',
        ]);

        $deviceId = 'SMSCHK-' . strtoupper(bin2hex(random_bytes(4)));
        $apiKey = SmsCheckerDevice::generateApiKey();
        $secretKey = SmsCheckerDevice::generateSecretKey();

        $device = SmsCheckerDevice::create([
            'device_id' => $deviceId,
            'name' => $request->name,
            'api_key' => $apiKey,
            'secret_key' => $secretKey,
            'status' => 'active',
            'approval_mode' => $request->approval_mode ?? 'auto',
            'description' => $request->description,
            'user_id' => auth()->id(),
        ]);

        session()->flash('new_device', [
            'device' => $device,
            'api_key' => $apiKey,
            'secret_key' => $secretKey,
        ]);

        return redirect()
            ->route('admin.sms-payment.devices.show', $device)
            ->with('success', 'สร้างอุปกรณ์เรียบร้อยแล้ว');
    }

    /**
     * Show device details with QR code.
     */
    public function showDevice(SmsCheckerDevice $device)
    {
        // Config for QR Code - must match Android app expected format (camelCase)
        // URL should be base URL only (e.g., https://example.com) - Android app adds /api/v1/sms-payment/* paths
        $config = [
            'type' => 'smschecker_config',
            'version' => 2,
            'url' => config('app.url'),
            'apiKey' => $device->api_key,
            'secretKey' => $device->secret_key,
            'deviceId' => $device->device_id,
            'deviceName' => $device->name,
            'sync_interval' => (int) config('smschecker.sync.interval', 5),
        ];

        $qrCode = '';
        if (class_exists(QrCode::class)) {
            $qrCode = QrCode::format('svg')
                ->size(250)
                ->errorCorrection('H')
                ->generate(json_encode($config));
        }

        return view('admin.sms-payment.device-show', compact('device', 'config', 'qrCode'));
    }

    /**
     * Toggle device status.
     */
    public function toggleDevice(SmsCheckerDevice $device)
    {
        $device->status = $device->status === 'active' ? 'inactive' : 'active';
        $device->save();

        return redirect()
            ->back()
            ->with('success', 'เปลี่ยนสถานะอุปกรณ์เรียบร้อยแล้ว');
    }

    /**
     * Regenerate device API key.
     */
    public function regenerateKey(SmsCheckerDevice $device)
    {
        $device->api_key = SmsCheckerDevice::generateApiKey();
        $device->secret_key = SmsCheckerDevice::generateSecretKey();
        $device->save();

        return redirect()
            ->back()
            ->with('success', 'สร้าง API Key ใหม่เรียบร้อยแล้ว ต้องสแกน QR Code ใหม่');
    }

    /**
     * Display notifications history.
     */
    public function notifications(Request $request)
    {
        $query = SmsPaymentNotification::with('matchedOrder')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('bank')) {
            $query->where('bank', $request->bank);
        }

        $notifications = $query->paginate(20);

        return view('admin.sms-payment.notifications', compact('notifications'));
    }

    /**
     * Manual match SMS notification to an order.
     */
    public function manualMatch(Request $request, SmsPaymentNotification $notification)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::find($request->order_id);

        if ($order->payment_status === 'paid') {
            return redirect()
                ->back()
                ->with('error', 'Order นี้ได้รับการชำระเงินแล้ว');
        }

        $notification->matched_transaction_id = $order->id;
        $notification->status = 'matched';
        $notification->save();

        $order->update([
            'sms_notification_id' => $notification->id,
            'sms_verification_status' => 'matched',
        ]);

        return redirect()
            ->back()
            ->with('success', 'จับคู่ SMS กับ Order เรียบร้อยแล้ว');
    }

    /**
     * Update device status.
     */
    public function updateDevice(Request $request, SmsCheckerDevice $device)
    {
        $request->validate([
            'status' => 'sometimes|in:active,inactive,blocked',
            'approval_mode' => 'sometimes|in:auto,manual,smart',
            'device_name' => 'sometimes|string|max:100',
        ]);

        $oldApprovalMode = $device->approval_mode;
        $device->update($request->only(['status', 'approval_mode', 'device_name']));

        // Send FCM push when approval_mode changes → device syncs immediately
        if ($request->has('approval_mode') && $request->input('approval_mode') !== $oldApprovalMode) {
            try {
                $fcmService = app(\App\Services\FcmNotificationService::class);
                $fcmService->notifySettingsChanged($device, 'approval_mode', $request->input('approval_mode'));
            } catch (\Exception $e) {
                \Log::warning('Failed to send FCM for approval_mode change', ['error' => $e->getMessage()]);
            }
        }

        return redirect()
            ->back()
            ->with('success', 'อัพเดท Device เรียบร้อยแล้ว');
    }

    /**
     * Delete device.
     */
    public function destroyDevice(SmsCheckerDevice $device)
    {
        $device->delete();

        return redirect()
            ->route('admin.sms-payment.devices')
            ->with('success', 'ลบ Device เรียบร้อยแล้ว');
    }

    /**
     * Show notification details.
     */
    public function showNotification(SmsPaymentNotification $notification)
    {
        $notification->load('matchedOrder', 'device');

        return view('admin.sms-payment.show-notification', compact('notification'));
    }

    /**
     * Manually confirm payment for an order.
     */
    public function confirmPayment(Order $order)
    {
        if (! $order->usesSmsPayment()) {
            return redirect()
                ->back()
                ->with('error', 'Order นี้ไม่ได้ใช้ SMS Payment');
        }

        if ($order->payment_status === 'confirmed' || $order->payment_status === 'paid') {
            return redirect()
                ->back()
                ->with('error', 'Order นี้ได้รับการยืนยันแล้ว');
        }

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
        $this->generateLicensesForOrder($order);

        // Send FCM push to Android app so it updates immediately
        try {
            $this->fcmService->notifyOrderApproved($order);
        } catch (\Exception $e) {
            Log::error('FCM: Failed to send order_approved push', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Order payment manually confirmed by admin', [
            'order_id' => $order->id,
            'admin_id' => auth()->id(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'ยืนยันการชำระเงินเรียบร้อยแล้ว');
    }

    /**
     * Reject payment for an order.
     */
    public function rejectPayment(Request $request, Order $order)
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        if (! $order->usesSmsPayment()) {
            return redirect()
                ->back()
                ->with('error', 'Order นี้ไม่ได้ใช้ SMS Payment');
        }

        $reason = $request->input('reason', 'ปฏิเสธโดย Admin');

        $order->update([
            'sms_verification_status' => 'rejected',
            'payment_status' => 'failed',
            'notes' => $order->notes . "\n[SMS Rejected] " . $reason,
        ]);

        if ($order->smsNotification) {
            $order->smsNotification->update(['status' => 'rejected']);
        }

        if ($order->uniquePaymentAmount) {
            $order->uniquePaymentAmount->cancel();
        }

        // Send FCM push to Android app so it updates immediately
        try {
            $this->fcmService->notifyOrderRejected($order);
        } catch (\Exception $e) {
            Log::error('FCM: Failed to send order_rejected push', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Order payment rejected by admin', [
            'order_id' => $order->id,
            'admin_id' => auth()->id(),
            'reason' => $reason,
        ]);

        return redirect()
            ->back()
            ->with('success', 'ปฏิเสธการชำระเงินเรียบร้อยแล้ว');
    }

    /**
     * Show orders waiting for SMS verification.
     */
    public function pendingOrders()
    {
        $orders = Order::with(['uniquePaymentAmount', 'smsNotification'])
            ->whereNotNull('unique_payment_amount_id')
            ->whereIn('sms_verification_status', ['pending', 'matched'])
            ->where('payment_status', '!=', 'paid')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.sms-payment.pending-orders', compact('orders'));
    }

    /**
     * Run cleanup command.
     */
    public function cleanup()
    {
        $stats = $this->smsPaymentService->cleanup();

        return redirect()
            ->back()
            ->with('success', "Cleanup เรียบร้อย: หมดอายุ {$stats['expired_amounts']} ยอด, ลบ {$stats['deleted_nonces']} nonces, หมดอายุ {$stats['expired_notifications']} notifications");
    }

    /**
     * Auto-generate license keys for order items that require them.
     */
    private function generateLicensesForOrder(Order $order): void
    {
        $order->load('items.product');
        $licenseService = app(LicenseService::class);
        $generated = false;

        foreach ($order->items as $item) {
            if (! $item->product || ! $item->product->requires_license) {
                continue;
            }

            // Check if licenses already exist for this order+product
            $existingCount = LicenseKey::where('order_id', $order->id)
                ->where('product_id', $item->product_id)
                ->count();

            if ($existingCount >= $item->quantity) {
                continue;
            }

            // Determine license type from custom_requirements or default to yearly
            $licenseType = 'yearly';
            if ($item->custom_requirements) {
                $requirements = json_decode($item->custom_requirements, true);
                if (! empty($requirements['license_type'])) {
                    $licenseType = $requirements['license_type'];
                }
            }

            $expiresAt = match ($licenseType) {
                'monthly' => now()->addDays(30),
                'yearly' => now()->addYear(),
                'lifetime' => null,
                default => now()->addYear(),
            };

            $toGenerate = $item->quantity - $existingCount;
            $licenses = $licenseService->generateLicenses(
                $licenseType,
                $toGenerate,
                1,
                $item->product_id
            );

            // Link licenses to the order
            foreach ($licenses as $license) {
                LicenseKey::where('id', $license['id'])->update([
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'expires_at' => $expiresAt,
                ]);
            }

            $generated = true;
        }

        // Send payment confirmed email with license keys
        if ($generated && $order->customer_email) {
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
}
