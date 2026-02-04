<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Services\SmsPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SmsPaymentController extends Controller
{
    public function __construct(
        protected SmsPaymentService $smsPaymentService
    ) {}

    /**
     * Display SMS Payment settings page.
     */
    public function settings()
    {
        $activeDevices = SmsCheckerDevice::where('status', 'active')->count();
        $smsToday = SmsPaymentNotification::whereDate('created_at', today())->count();
        $devices = SmsCheckerDevice::orderBy('created_at', 'desc')->get();

        return view('admin.sms-payment.settings', compact('activeDevices', 'smsToday', 'devices'));
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

        $deviceId = 'SMSCHK-'.strtoupper(bin2hex(random_bytes(4)));
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
        $config = [
            'type' => 'smschecker_config',
            'version' => 2,
            'url' => config('app.url').'/api/v1/sms-payment',
            'apiKey' => $device->api_key,
            'secretKey' => $device->secret_key,
            'deviceId' => $device->device_id,
            'deviceName' => $device->name,
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

        $device->update($request->only(['status', 'approval_mode', 'device_name']));

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
            'notes' => $order->notes."\n[SMS Rejected] ".$reason,
        ]);

        if ($order->smsNotification) {
            $order->smsNotification->update(['status' => 'rejected']);
        }

        if ($order->uniquePaymentAmount) {
            $order->uniquePaymentAmount->cancel();
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
}
