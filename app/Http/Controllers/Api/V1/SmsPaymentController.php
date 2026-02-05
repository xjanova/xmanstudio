<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Services\FcmNotificationService;
use App\Services\SmsPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Pusher\Pusher;

class SmsPaymentController extends Controller
{
    public function __construct(
        private SmsPaymentService $smsPaymentService,
        private FcmNotificationService $fcmService
    ) {}

    /**
     * Receive an SMS payment notification from the Android app.
     *
     * POST /api/v1/sms-payment/notify
     */
    public function notify(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate required headers
        $signature = $request->header('X-Signature');
        $nonce = $request->header('X-Nonce');
        $timestamp = $request->header('X-Timestamp');

        if (! $signature || ! $nonce || ! $timestamp) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required security headers',
            ], 400);
        }

        // Check timestamp freshness (within 5 minutes)
        $requestTime = intval($timestamp);
        $currentTime = intval(round(microtime(true) * 1000));
        $tolerance = config('smschecker.timestamp_tolerance', 300) * 1000;

        if (abs($currentTime - $requestTime) > $tolerance) {
            return response()->json([
                'success' => false,
                'message' => 'Request timestamp expired',
            ], 400);
        }

        // Get encrypted data
        $encryptedData = $request->input('data');
        if (! $encryptedData) {
            return response()->json([
                'success' => false,
                'message' => 'No payload data',
            ], 400);
        }

        // Verify HMAC signature
        $signatureData = $encryptedData . $nonce . $timestamp;
        if (! $this->smsPaymentService->verifySignature($signatureData, $signature, $device->secret_key)) {
            Log::warning('SMS Payment: Invalid signature', [
                'device_id' => $device->device_id,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid signature',
            ], 401);
        }

        // Decrypt payload
        $payload = $this->smsPaymentService->decryptPayload($encryptedData, $device->secret_key);
        if (! $payload) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to decrypt payload',
            ], 400);
        }

        // Validate payload fields
        $validator = Validator::make($payload, [
            'bank' => 'required|string|max:20',
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0.01',
            'account_number' => 'nullable|string|max:50',
            'sender_or_receiver' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:100',
            'sms_timestamp' => 'required|numeric',
            'device_id' => 'required|string',
            'nonce' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payload data',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Process the notification
        $result = $this->smsPaymentService->processNotification(
            $payload,
            $device,
            $request->ip()
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Check device status and pending count.
     *
     * GET /api/v1/sms-payment/status
     */
    public function status(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $pendingCount = SmsPaymentNotification::where('device_id', $device->device_id)
            ->where('status', 'pending')
            ->count();

        return response()->json([
            'success' => true,
            'status' => $device->status,
            'pending_count' => $pendingCount,
            'approval_mode' => $device->getApprovalMode(),
            'message' => null,
        ]);
    }

    /**
     * Register a new device.
     *
     * POST /api/v1/sms-payment/register-device
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:50',
            'device_name' => 'required|string|max:100',
            'platform' => 'required|string|max:20',
            'app_version' => 'required|string|max:20',
            'fcm_token' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $device->update([
            'device_name' => $request->input('device_name'),
            'platform' => $request->input('platform'),
            'app_version' => $request->input('app_version'),
            'last_active_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Device registered successfully',
        ]);
    }

    /**
     * Generate a unique payment amount for checkout.
     * Called by the web checkout process, NOT by the Android app.
     *
     * POST /api/v1/sms-payment/generate-amount
     */
    public function generateAmount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'base_amount' => 'required|numeric|min:1',
            'transaction_id' => 'nullable|integer',
            'transaction_type' => 'nullable|string|max:50',
            'expiry_minutes' => 'nullable|integer|min:5|max:60',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $uniqueAmount = $this->smsPaymentService->generateUniqueAmount(
            $request->input('base_amount'),
            $request->input('transaction_id'),
            $request->input('transaction_type', 'order'),
            $request->input('expiry_minutes', 30)
        );

        if (! $uniqueAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to generate unique amount. Too many pending transactions at this price.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Unique amount generated',
            'data' => [
                'id' => $uniqueAmount->id,
                'base_amount' => number_format((float) $uniqueAmount->base_amount, 2, '.', ''),
                'unique_amount' => number_format((float) $uniqueAmount->unique_amount, 2, '.', ''),
                'expires_at' => $uniqueAmount->expires_at->toIso8601String(),
                'display_amount' => '฿' . number_format((float) $uniqueAmount->unique_amount, 2),
            ],
        ]);
    }

    /**
     * Get notification history for admin dashboard.
     *
     * GET /api/v1/sms-payment/notifications
     */
    public function notifications(Request $request): JsonResponse
    {
        $query = SmsPaymentNotification::orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('bank')) {
            $query->where('bank', $request->input('bank'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $notifications = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    // ============================================
    // ORDER APPROVAL ENDPOINTS (for Android app)
    // ============================================

    /**
     * Get orders for approval in Android app.
     * Format matches Thaiprompt-Affiliate for compatibility.
     *
     * GET /api/v1/sms-payment/orders
     */
    public function getOrders(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $query = Order::with(['smsNotification', 'uniquePaymentAmount'])
            ->whereNotNull('unique_payment_amount_id')
            ->orderBy('created_at', 'desc');

        // Filter by status (default: pending)
        $status = $request->input('status', 'pending');
        if ($status !== 'all') {
            if ($status === 'pending') {
                $query->whereIn('sms_verification_status', ['pending', null])
                    ->where('payment_status', '!=', 'paid');
            } elseif ($status === 'matched') {
                $query->where('sms_verification_status', 'matched');
            } elseif ($status === 'confirmed') {
                $query->where('sms_verification_status', 'confirmed');
            }
        }

        // Date filters
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $orders = $query->paginate($request->input('per_page', 20));

        // Transform to Android app format (matching Thaiprompt-Affiliate)
        $transformedOrders = $orders->getCollection()->map(function ($order) {
            return $this->transformOrderForAndroid($order);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $transformedOrders,
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Transform Order to Android app format (matching Thaiprompt-Affiliate).
     */
    private function transformOrderForAndroid(Order $order): array
    {
        // Map sms_verification_status to approval_status for Android
        $approvalStatus = match ($order->sms_verification_status) {
            'pending', null => 'pending_review',
            'matched' => 'pending_review',
            'confirmed' => 'auto_approved',
            'rejected' => 'rejected',
            default => 'pending_review',
        };

        // Get the amount to match (unique_amount or total)
        $amount = $order->uniquePaymentAmount
            ? (float) $order->uniquePaymentAmount->unique_amount
            : (float) $order->total;

        // Build order_details_json
        $orderDetails = [
            'order_number' => $order->order_number,
            'product_name' => $order->items?->first()?->product?->name ?? $order->product_name ?? null,
            'product_details' => null,
            'quantity' => $order->items?->count() ?? 1,
            'website_name' => config('app.name'),
            'customer_name' => $order->customer_name,
            'amount' => $amount,
        ];

        // Build notification object
        $notification = $order->smsNotification ? [
            'id' => $order->smsNotification->id,
            'bank' => $order->smsNotification->bank,
            'type' => $order->smsNotification->type ?? 'credit',
            'amount' => sprintf('%.2f', (float) $order->smsNotification->amount),
            'sms_timestamp' => $order->smsNotification->sms_timestamp,
            'sender_or_receiver' => $order->smsNotification->sender_or_receiver ?? '',
        ] : [
            // Dummy notification for orders without SMS match yet
            'id' => $order->id,
            'bank' => 'PROMPTPAY',
            'type' => 'credit',
            'amount' => sprintf('%.2f', $amount),
            'sms_timestamp' => $order->created_at?->format('Y-m-d H:i:s'),
            'sender_or_receiver' => $order->customer_name ?? '',
        ];

        return [
            'id' => $order->id,
            'notification_id' => $order->smsNotification?->id,
            'matched_transaction_id' => $order->smsNotification?->id,
            'device_id' => $order->smsNotification?->device_id,
            'approval_status' => $approvalStatus,
            'confidence' => $order->smsNotification ? 'high' : 'medium',
            'approved_by' => null,
            'approved_at' => $order->paid_at?->toIso8601String(),
            'rejected_at' => $order->sms_verification_status === 'rejected' ? $order->updated_at?->toIso8601String() : null,
            'rejection_reason' => null,
            'order_details_json' => $orderDetails,
            'synced_version' => $order->updated_at ? intval($order->updated_at->timestamp * 1000) : 0,
            'created_at' => $order->created_at?->toIso8601String(),
            'updated_at' => $order->updated_at?->toIso8601String(),
            'notification' => $notification,
        ];
    }

    /**
     * Approve an order payment (manual approval from Android app).
     *
     * POST /api/v1/sms-payment/orders/{id}/approve
     */
    public function approveOrder(Request $request, int $id): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $order = Order::find($id);
        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        if (! in_array($order->sms_verification_status, ['pending', 'matched'])) {
            return response()->json([
                'success' => false,
                'message' => 'Order cannot be approved in current status',
            ], 400);
        }

        $order->update([
            'sms_verification_status' => 'confirmed',
            'payment_status' => 'confirmed',
            'paid_at' => now(),
        ]);

        // Update notification if exists
        if ($order->smsNotification) {
            $order->smsNotification->update(['status' => 'confirmed']);
        }

        Log::info('Order approved via SMS Checker', [
            'order_id' => $order->id,
            'device_id' => $device->device_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order approved successfully',
        ]);
    }

    /**
     * Reject an order payment (from Android app).
     *
     * POST /api/v1/sms-payment/orders/{id}/reject
     */
    public function rejectOrder(Request $request, int $id): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $order = Order::find($id);
        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $reason = $request->input('reason', '');

        $order->update([
            'sms_verification_status' => 'rejected',
            'payment_status' => 'failed',
            'notes' => $order->notes . "\n[SMS Rejected] " . $reason,
        ]);

        // Update notification if exists
        if ($order->smsNotification) {
            $order->smsNotification->update(['status' => 'rejected']);
        }

        // Cancel unique amount
        if ($order->uniquePaymentAmount) {
            $order->uniquePaymentAmount->cancel();
        }

        Log::info('Order rejected via SMS Checker', [
            'order_id' => $order->id,
            'device_id' => $device->device_id,
            'reason' => $reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order rejected',
        ]);
    }

    /**
     * Match order by amount - find pending order that matches the SMS amount.
     *
     * Android app calls this when SMS is received instead of fetching all orders.
     * Returns only the order that matches the exact amount (unique decimal).
     *
     * GET /api/v1/sms-payment/orders/match?amount=500.37
     */
    public function matchOrderByAmount(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $amount = $request->input('amount');
        if (! $amount || ! is_numeric($amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Amount is required and must be numeric',
            ], 400);
        }

        $amount = (float) $amount;

        // Find Order with matching unique_amount
        $order = Order::with(['smsNotification', 'uniquePaymentAmount'])
            ->whereHas('uniquePaymentAmount', function ($q) use ($amount) {
                $q->where('unique_amount', $amount)
                    ->where('status', 'reserved');
            })
            ->whereIn('sms_verification_status', ['pending', null])
            ->where('payment_status', '!=', 'paid')
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $order) {
            return response()->json([
                'success' => true,
                'data' => [
                    'matched' => false,
                    'order' => null,
                    'message' => 'No pending order found with amount ' . number_format($amount, 2),
                ],
            ]);
        }

        // Transform to Android app format
        $orderData = $this->transformOrderForAndroid($order);

        // Update device last_active_at
        $device->update(['last_active_at' => now()]);

        Log::info('Order matched by amount', [
            'device_id' => $device->device_id,
            'amount' => $amount,
            'order_id' => $order->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'matched' => true,
                'order' => $orderData,
                'message' => 'Found matching order',
            ],
        ]);
    }

    /**
     * Get device settings (for Android app).
     *
     * GET /api/v1/sms-payment/device-settings
     */
    public function getDeviceSettings(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'approval_mode' => $device->getApprovalMode(),
                'device_name' => $device->device_name,
                'status' => $device->status,
            ],
        ]);
    }

    /**
     * Update device settings (from Android app).
     *
     * PUT /api/v1/sms-payment/device-settings
     */
    public function updateDeviceSettings(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'approval_mode' => 'sometimes|in:auto,manual,smart',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('approval_mode')) {
            $device->update(['approval_mode' => $request->input('approval_mode')]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated',
        ]);
    }

    /**
     * Get dashboard statistics (for Android app).
     *
     * GET /api/v1/sms-payment/dashboard-stats
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $days = $request->input('days', 7);
        $startDate = now()->subDays($days)->startOfDay();

        $stats = [
            'total_orders' => Order::whereNotNull('unique_payment_amount_id')
                ->where('created_at', '>=', $startDate)->count(),
            'auto_approved' => Order::where('sms_verification_status', 'confirmed')
                ->where('created_at', '>=', $startDate)->count(),
            'pending_review' => Order::where('sms_verification_status', 'matched')
                ->orWhere('sms_verification_status', 'pending')
                ->where('created_at', '>=', $startDate)->count(),
            'rejected' => Order::where('sms_verification_status', 'rejected')
                ->where('created_at', '>=', $startDate)->count(),
            'total_amount' => Order::where('sms_verification_status', 'confirmed')
                ->where('created_at', '>=', $startDate)
                ->sum('total'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    // ============================================
    // REAL-TIME & FCM ENDPOINTS
    // ============================================

    /**
     * Register FCM token for push notifications.
     *
     * POST /api/v1/sms-payment/register-fcm-token
     */
    public function registerFcmToken(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $this->fcmService->registerToken($device, $request->input('fcm_token'));

        return response()->json([
            'success' => true,
            'message' => 'FCM token registered successfully',
        ]);
    }

    /**
     * Authenticate for Pusher private channels.
     *
     * POST /api/v1/sms-payment/pusher/auth
     */
    public function pusherAuth(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $socketId = $request->input('socket_id');
        $channelName = $request->input('channel_name');

        if (! $socketId || ! $channelName) {
            return response()->json([
                'success' => false,
                'message' => 'Missing socket_id or channel_name',
            ], 400);
        }

        // Validate that this device can access this channel
        $allowedChannels = [
            'sms-checker.broadcast',
            'private-sms-checker.device.' . $device->device_id,
        ];

        $isAllowed = in_array($channelName, $allowedChannels)
            || str_starts_with($channelName, 'sms-checker.broadcast');

        if (! $isAllowed) {
            return response()->json([
                'success' => false,
                'message' => 'Channel not authorized',
            ], 403);
        }

        try {
            $pusher = new Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                config('broadcasting.connections.pusher.options')
            );

            // For private channels
            if (str_starts_with($channelName, 'private-')) {
                $auth = $pusher->authorizeChannel($channelName, $socketId);
            } else {
                // For public channels, just return success
                return response()->json(['success' => true]);
            }

            return response()->json(json_decode($auth, true));
        } catch (\Exception $e) {
            Log::error('Pusher auth failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Auth failed',
            ], 500);
        }
    }

    /**
     * Get changes since last sync (for delta sync).
     * Format matches Thaiprompt-Affiliate /orders/sync endpoint.
     *
     * GET /api/v1/sms-payment/orders/sync
     */
    public function syncOrders(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Support both since_version (Android app) and since (legacy)
        $sinceVersion = $request->input('since_version') ?? $request->input('since') ?? 0;

        // Get changed orders since the given version (timestamp in milliseconds)
        $query = Order::with(['smsNotification', 'uniquePaymentAmount'])
            ->whereNotNull('unique_payment_amount_id');

        if ($sinceVersion > 0) {
            // Get orders updated after the given timestamp (milliseconds)
            $query->where('updated_at', '>', date('Y-m-d H:i:s', $sinceVersion / 1000));
        }

        $orders = $query->orderBy('updated_at', 'desc')
            ->limit($request->input('limit', 100))
            ->get();

        // Transform to Android app format
        $transformedOrders = $orders->map(function ($order) {
            return $this->transformOrderForAndroid($order);
        });

        $latestVersion = intval(round(microtime(true) * 1000));

        // Update device last_active_at
        $device->update(['last_active_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $transformedOrders,
                'latest_version' => $latestVersion,
            ],
        ]);
    }

    /**
     * Legacy sync endpoint - redirects to syncOrders.
     *
     * GET /api/v1/sms-payment/sync
     */
    public function sync(Request $request): JsonResponse
    {
        return $this->syncOrders($request);
    }

    /**
     * Get current sync version.
     *
     * GET /api/v1/sms-payment/sync-version
     */
    public function getSyncVersion(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'version' => $this->getSyncVersionNumber(),
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get the current sync version number.
     * This increments whenever orders change.
     */
    private function getSyncVersionNumber(): int
    {
        return Cache::remember('sms_payment_sync_version', 60, function () {
            $lastOrder = Order::whereNotNull('unique_payment_amount_id')
                ->orderBy('updated_at', 'desc')
                ->first();

            return $lastOrder ? $lastOrder->updated_at->timestamp : time();
        });
    }
}
