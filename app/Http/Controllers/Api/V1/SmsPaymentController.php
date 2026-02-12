<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\PaymentMatched;
use App\Http\Controllers\Controller;
use App\Mail\PaymentConfirmedMail;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Models\WalletTopup;
use App\Services\FcmNotificationService;
use App\Services\LicenseService;
use App\Services\SmsPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        // Enrich response with order data when matched (for Android app to update UI immediately)
        if ($result['success'] && ! empty($result['data']['matched']) && ! empty($result['data']['matched_transaction_id'])) {
            $notificationRecord = SmsPaymentNotification::find($result['data']['notification_id'] ?? null);
            if ($notificationRecord) {
                // Try to find the matched order
                $matchedOrder = Order::with(['smsNotification', 'uniquePaymentAmount'])
                    ->find($notificationRecord->matched_transaction_id);

                if ($matchedOrder) {
                    $result['data']['order'] = $this->transformOrderForAndroid($matchedOrder);
                } else {
                    // Try WalletTopup
                    $matchedTopup = WalletTopup::with(['uniquePaymentAmount', 'user'])
                        ->find($notificationRecord->matched_transaction_id);

                    if ($matchedTopup) {
                        $result['data']['order'] = $this->transformWalletTopupForAndroid($matchedTopup);
                    }
                }
            }
        }

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

        $updateData = [
            'device_name' => $request->input('device_name'),
            'platform' => $request->input('platform'),
            'app_version' => $request->input('app_version'),
            'last_active_at' => now(),
            'ip_address' => $request->ip(),
        ];

        // Save FCM token if provided (for push notifications)
        if ($request->filled('fcm_token')) {
            $updateData['fcm_token'] = $request->input('fcm_token');
            \Log::info('registerDevice: FCM token received', [
                'device_id' => $device->device_id,
                'token_prefix' => substr($request->input('fcm_token'), 0, 30) . '...',
            ]);
        } else {
            \Log::debug('registerDevice: No FCM token in request', [
                'device_id' => $device->device_id,
                'request_keys' => array_keys($request->all()),
            ]);
        }

        $device->update($updateData);

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

        $status = $request->input('status', 'pending');
        $perPage = (int) $request->input('per_page', 20);
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // === Query Orders ===
        $orderQuery = Order::with(['smsNotification', 'uniquePaymentAmount'])
            ->whereNotNull('unique_payment_amount_id')
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            if ($status === 'pending') {
                $orderQuery->whereIn('sms_verification_status', ['pending', null])
                    ->where('payment_status', '!=', 'paid');
            } elseif ($status === 'matched') {
                $orderQuery->where('sms_verification_status', 'matched');
            } elseif ($status === 'confirmed') {
                $orderQuery->where('sms_verification_status', 'confirmed');
            }
        }

        if ($dateFrom) {
            $orderQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $orderQuery->whereDate('created_at', '<=', $dateTo);
        }

        $orders = $orderQuery->get();

        // === Query Wallet Topups (เติมเงิน) — รวมให้โชว์ในแอพด้วย ===
        $topupQuery = WalletTopup::with(['uniquePaymentAmount'])
            ->whereNotNull('unique_payment_amount_id')
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            if ($status === 'pending') {
                $topupQuery->whereIn('sms_verification_status', ['pending', null])
                    ->where('status', WalletTopup::STATUS_PENDING);
            } elseif ($status === 'matched') {
                $topupQuery->where('sms_verification_status', 'matched');
            } elseif ($status === 'confirmed') {
                $topupQuery->where('sms_verification_status', 'confirmed');
            }
        }

        if ($dateFrom) {
            $topupQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $topupQuery->whereDate('created_at', '<=', $dateTo);
        }

        $topups = $topupQuery->get();

        // === Merge + Sort + Paginate ===
        $allItems = collect();

        foreach ($orders as $order) {
            $allItems->push($this->transformOrderForAndroid($order));
        }
        foreach ($topups as $topup) {
            $allItems->push($this->transformWalletTopupForAndroid($topup));
        }

        // Sort by created_at descending
        $allItems = $allItems->sortByDesc('created_at')->values();

        // Manual pagination
        $page = (int) $request->input('page', 1);
        $total = $allItems->count();
        $paged = $allItems->forPage($page, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $paged,
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / $perPage)),
                'total' => $total,
            ],
        ]);
    }

    /**
     * Transform Order to Android app format (matching Thaiprompt-Affiliate).
     */
    private function transformOrderForAndroid(Order $order): array
    {
        // Map sms_verification_status + payment_status to approval_status for Android
        // Server is the source of truth — status must reflect actual server state
        $approvalStatus = match (true) {
            // Paid/confirmed = truly approved
            in_array($order->payment_status, ['paid', 'confirmed']) => 'auto_approved',
            // SMS matched but NOT yet paid → still pending admin/system confirmation
            $order->sms_verification_status === 'matched' => 'pending_review',
            // Confirmed by SMS (but payment not yet marked) → approved
            $order->sms_verification_status === 'confirmed' => 'auto_approved',
            // Explicitly rejected
            $order->sms_verification_status === 'rejected' => 'rejected',
            // Cancelled
            $order->sms_verification_status === 'cancelled' || $order->payment_status === 'cancelled' => 'cancelled',
            // Expired/timeout
            in_array($order->sms_verification_status, ['timeout', 'expired']) || $order->payment_status === 'expired' => 'expired',
            // Default: pending
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
            'server_name' => config('app.name'),
            'synced_version' => $order->updated_at ? intval($order->updated_at->timestamp * 1000) : 0,
            'created_at' => $order->created_at?->toIso8601String(),
            'updated_at' => $order->updated_at?->toIso8601String(),
            'notification' => $notification,
        ];
    }

    /**
     * Transform WalletTopup (เติมเงิน) to Android app format — ให้โชว์เหมือน Order.
     */
    private function transformWalletTopupForAndroid(WalletTopup $topup): array
    {
        // Map sms_verification_status + topup status to approval_status for Android
        // Server is the source of truth — status must reflect actual server state
        $approvalStatus = match (true) {
            // Topup approved = truly approved (source of truth: topup.status)
            $topup->status === 'approved' => 'auto_approved',
            // SMS confirmed BUT topup NOT yet approved → still pending (approve() may have failed)
            $topup->sms_verification_status === 'confirmed' && $topup->status === 'pending' => 'pending_review',
            // SMS matched but topup NOT yet approved → still pending
            $topup->sms_verification_status === 'matched' && $topup->status === 'pending' => 'pending_review',
            // Rejected
            $topup->sms_verification_status === 'rejected' || $topup->status === 'rejected' => 'rejected',
            // Cancelled
            $topup->sms_verification_status === 'cancelled' || $topup->status === 'cancelled' => 'cancelled',
            // Expired
            in_array($topup->sms_verification_status, ['timeout', 'expired']) || $topup->status === 'expired' => 'expired',
            // Default: pending
            default => 'pending_review',
        };

        $amount = $topup->uniquePaymentAmount
            ? (float) $topup->uniquePaymentAmount->unique_amount
            : (float) $topup->amount;

        $smsNotification = $topup->smsNotification ?? null;

        $orderDetails = [
            'order_number' => 'TOPUP-' . $topup->topup_id,
            'product_name' => 'เติมเงิน Wallet',
            'product_details' => 'เติมเงินเข้า Wallet ฿' . number_format((float) $topup->amount, 2),
            'quantity' => 1,
            'website_name' => config('app.name'),
            'customer_name' => $topup->user?->name ?? 'N/A',
            'amount' => $amount,
        ];

        $notification = $smsNotification ? [
            'id' => $smsNotification->id,
            'bank' => $smsNotification->bank,
            'type' => $smsNotification->type ?? 'credit',
            'amount' => sprintf('%.2f', (float) $smsNotification->amount),
            'sms_timestamp' => $smsNotification->sms_timestamp,
            'sender_or_receiver' => $smsNotification->sender_or_receiver ?? '',
        ] : [
            'id' => $topup->id,
            'bank' => 'PROMPTPAY',
            'type' => 'credit',
            'amount' => sprintf('%.2f', $amount),
            'sms_timestamp' => $topup->created_at?->format('Y-m-d H:i:s'),
            'sender_or_receiver' => $topup->user?->name ?? '',
        ];

        return [
            'id' => $topup->id,
            'notification_id' => $smsNotification?->id,
            'matched_transaction_id' => $smsNotification?->id,
            'device_id' => $smsNotification?->device_id,
            'approval_status' => $approvalStatus,
            'confidence' => $smsNotification ? 'high' : 'medium',
            'approved_by' => null,
            'approved_at' => $topup->approved_at?->toIso8601String(),
            'rejected_at' => $topup->sms_verification_status === 'rejected' ? $topup->updated_at?->toIso8601String() : null,
            'rejection_reason' => $topup->reject_reason,
            'order_details_json' => $orderDetails,
            'server_name' => config('app.name'),
            'synced_version' => $topup->updated_at ? intval($topup->updated_at->timestamp * 1000) : 0,
            'created_at' => $topup->created_at?->toIso8601String(),
            'updated_at' => $topup->updated_at?->toIso8601String(),
            'notification' => $notification,
            '_type' => 'wallet_topup',
        ];
    }

    /**
     * Approve an order payment (manual approval from Android app).
     *
     * รองรับทั้ง numeric ID (legacy) และ order_number string (ใหม่)
     *
     * POST /api/v1/sms-payment/orders/{identifier}/approve
     */
    public function approveOrder(Request $request, $identifier): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $order = is_numeric($identifier)
            ? Order::with(['smsNotification', 'uniquePaymentAmount'])->find($identifier)
            : Order::with(['smsNotification', 'uniquePaymentAmount'])->where('order_number', $identifier)->first();
        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        // Idempotent: ถ้า approved แล้ว (เช่น auto-approved ตอน match) → return success
        if (in_array($order->sms_verification_status, ['confirmed']) && in_array($order->payment_status, ['confirmed', 'paid'])) {
            return response()->json([
                'success' => true,
                'message' => 'Order already approved',
                'data' => ['order_id' => $order->id, 'status' => $order->payment_status],
            ]);
        }

        if (! in_array($order->sms_verification_status, ['pending', 'matched', null])) {
            return response()->json([
                'success' => false,
                'message' => 'Order cannot be approved in current status (current: ' . $order->sms_verification_status . ')',
            ], 422);
        }

        $order->update([
            'sms_verification_status' => 'confirmed',
            'sms_verified_at' => now(),
            'payment_status' => 'paid',
            'paid_at' => now(),
            'status' => 'completed',
        ]);

        // Update notification if exists
        if ($order->smsNotification) {
            $order->smsNotification->update(['status' => 'confirmed']);
        }

        // Generate license keys + send email (same as admin confirmPayment)
        $this->generateLicensesForOrder($order);

        // Fire PaymentMatched event for notifications
        if ($order->smsNotification) {
            event(new PaymentMatched($order, $order->smsNotification));
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
     * รองรับทั้ง numeric ID (legacy) และ order_number string (ใหม่)
     *
     * POST /api/v1/sms-payment/orders/{identifier}/reject
     */
    public function rejectOrder(Request $request, $identifier): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $order = is_numeric($identifier)
            ? Order::find($identifier)
            : Order::where('order_number', $identifier)->first();
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
     * Bulk approve multiple orders at once (from Android app).
     *
     * POST /api/v1/sms-payment/orders/bulk-approve
     */
    public function bulkApproveOrders(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => ['required'], // รองรับทั้ง integer และ string (order_number)
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $identifiers = $request->input('ids');
        $approved = 0;
        $failed = 0;
        $errors = [];

        foreach ($identifiers as $identifier) {
            $order = is_numeric($identifier)
                ? Order::find($identifier)
                : Order::where('order_number', $identifier)->first();
            if (! $order) {
                $failed++;
                $errors[] = "Order {$identifier} not found";

                continue;
            }

            if (! in_array($order->sms_verification_status, ['pending', 'matched'])) {
                $failed++;
                $errors[] = "Order {$identifier} cannot be approved in current status";

                continue;
            }

            $order->update([
                'sms_verification_status' => 'confirmed',
                'payment_status' => 'paid',
                'paid_at' => now(),
                'status' => 'completed',
            ]);

            // Generate license keys + send email
            $this->generateLicensesForOrder($order);

            if ($order->smsNotification) {
                $order->smsNotification->update(['status' => 'confirmed']);
            }

            $approved++;
        }

        Log::info('Bulk approve via SMS Checker', [
            'device_id' => $device->device_id,
            'approved' => $approved,
            'failed' => $failed,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Approved {$approved} orders" . ($failed > 0 ? ", {$failed} failed" : ''),
            'data' => [
                'approved' => $approved,
                'failed' => $failed,
                'errors' => $errors,
            ],
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

        $graceMinutes = (int) config('smschecker.orphan_match_window', 60);

        // Find Order with matching unique_amount (status=reserved → ยังไม่ถูก match)
        // รวม 'matched' ด้วย กรณี notify→attemptMatch() ทำงานก่อน matchOrderByAmount
        $order = Order::with(['smsNotification', 'uniquePaymentAmount'])
            ->whereHas('uniquePaymentAmount', function ($q) use ($amount) {
                $q->where('unique_amount', $amount)
                    ->whereIn('status', ['reserved', 'used']);
            })
            ->whereIn('sms_verification_status', ['pending', 'matched', null])
            ->where('payment_status', '!=', 'paid')
            ->orderBy('created_at', 'desc')
            ->first();

        // Fallback 1: ถ้า /notify → attemptMatch() ทำงานก่อนแล้ว (status='used')
        // → หา order ที่ match แล้ว (confirmed/paid) เพื่อ return ให้ Android รู้
        $alreadyMatched = false;
        if (! $order) {
            $order = Order::with(['smsNotification', 'uniquePaymentAmount'])
                ->whereHas('uniquePaymentAmount', function ($q) use ($amount) {
                    $q->where('unique_amount', $amount)
                        ->where('status', 'used');
                })
                ->where('created_at', '>=', now()->subHours(1))
                ->orderBy('created_at', 'desc')
                ->first();

            if ($order) {
                $alreadyMatched = true;
            }
        }

        // Fallback 2: หา order ที่ UniquePaymentAmount หมดอายุแล้ว (ภายใน grace period)
        // กรณี SMS มาช้า / cleanup ทำงานแล้ว แต่ order ยัง pending/expired
        if (! $order) {
            $order = Order::with(['smsNotification', 'uniquePaymentAmount'])
                ->whereHas('uniquePaymentAmount', function ($q) use ($amount, $graceMinutes) {
                    $q->where('unique_amount', $amount)
                        ->whereIn('status', ['expired', 'reserved'])
                        ->where('expires_at', '>', now()->subMinutes($graceMinutes));
                })
                ->whereIn('payment_status', ['pending', 'expired'])
                ->orderBy('created_at', 'desc')
                ->first();
        }

        // ถ้าไม่พบ Order → ลองหา Wallet Topup (เติมเงิน)
        $topup = null;
        if (! $order) {
            // Topup query 1: active/used unique amount
            $topup = WalletTopup::with(['uniquePaymentAmount', 'wallet.user'])
                ->whereIn('status', [WalletTopup::STATUS_PENDING, WalletTopup::STATUS_APPROVED])
                ->whereHas('uniquePaymentAmount', function ($q) use ($amount) {
                    $q->where('unique_amount', $amount)
                        ->whereIn('status', ['reserved', 'used']);
                })
                ->orderBy('created_at', 'desc')
                ->first();

            // Topup query 2: expired topup within grace period (cleanup ran but SMS came later)
            if (! $topup) {
                $topup = WalletTopup::with(['uniquePaymentAmount', 'wallet.user'])
                    ->whereIn('status', [WalletTopup::STATUS_EXPIRED, WalletTopup::STATUS_PENDING])
                    ->whereHas('uniquePaymentAmount', function ($q) use ($amount, $graceMinutes) {
                        $q->where('unique_amount', $amount)
                            ->whereIn('status', ['expired', 'reserved'])
                            ->where('expires_at', '>', now()->subMinutes($graceMinutes));
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($topup && $topup->status === WalletTopup::STATUS_EXPIRED) {
                    // Recover expired topup → set back to pending so approve() can work
                    $topup->update(['status' => WalletTopup::STATUS_PENDING]);
                    Log::info('SMS Payment: Recovered expired WalletTopup for match', [
                        'topup_id' => $topup->id,
                        'amount' => $amount,
                    ]);
                }
            }

            if ($topup) {
                $alreadyMatched = in_array($topup->sms_verification_status, ['confirmed', 'matched']);
            }
        }

        if (! $order && ! $topup) {
            return response()->json([
                'success' => true,
                'data' => [
                    'matched' => false,
                    'order' => null,
                    'message' => 'No pending order found with amount ' . number_format($amount, 2),
                ],
            ]);
        }

        // === Auto-approve: อนุมัติทันทีเมื่อจับคู่ได้ ===
        // ถ้า payment_status ยังไม่ใช่ 'paid' → ต้อง approve ไม่ว่า alreadyMatched หรือไม่
        $autoConfirm = config('smschecker.auto_confirm_matched', true);
        $orderData = null;

        if ($order) {
            // Auto-approve Order → trigger license generation + email
            $needsApprove = $autoConfirm && $order->payment_status !== 'paid';
            if ($needsApprove) {
                try {
                    $order->update([
                        'sms_verification_status' => 'confirmed',
                        'sms_verified_at' => now(),
                        'payment_status' => 'paid',
                        'paid_at' => $order->paid_at ?? now(),
                        'status' => 'completed',
                    ]);

                    // Mark unique amount as used
                    if ($order->uniquePaymentAmount && $order->uniquePaymentAmount->status === 'reserved') {
                        $order->uniquePaymentAmount->update([
                            'status' => 'used',
                            'matched_at' => now(),
                        ]);
                    }

                    if ($order->smsNotification) {
                        $order->smsNotification->update(['status' => 'confirmed']);
                    }

                    $this->generateLicensesForOrder($order);

                    if ($order->smsNotification) {
                        event(new PaymentMatched($order, $order->smsNotification));
                    }

                    $order = $order->fresh(['smsNotification', 'uniquePaymentAmount']);

                    Log::info('SMS Payment: Auto-approved Order on match', [
                        'device_id' => $device->device_id,
                        'amount' => $amount,
                        'order_id' => $order->id,
                        'was_already_matched' => $alreadyMatched,
                    ]);
                } catch (\Exception $e) {
                    Log::error('SMS Payment: Auto-approve Order failed', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            $orderData = $this->transformOrderForAndroid($order);
        } elseif ($topup) {
            // Auto-approve Wallet Topup → trigger wallet deposit
            $needsTopupApprove = $autoConfirm && $topup->status !== WalletTopup::STATUS_APPROVED;
            if ($needsTopupApprove) {
                try {
                    $topup->update([
                        'sms_verification_status' => 'confirmed',
                        'sms_verified_at' => now(),
                    ]);

                    if ($topup->uniquePaymentAmount && $topup->uniquePaymentAmount->status === 'reserved') {
                        $topup->uniquePaymentAmount->update([
                            'status' => 'used',
                            'matched_at' => now(),
                        ]);
                    }

                    // ⚠️ CRITICAL: refresh model + load wallet before approve()
                    $topup->refresh();
                    $topup->load('wallet');

                    // ⚠️ ตรวจสอบว่า wallet มีอยู่จริง ถ้าไม่มีให้สร้างให้
                    if (! $topup->wallet) {
                        Log::warning('matchOrderByAmount: Wallet is NULL! Creating for user', [
                            'topup_id' => $topup->id,
                            'wallet_id' => $topup->wallet_id,
                            'user_id' => $topup->user_id,
                        ]);
                        $wallet = \App\Models\Wallet::getOrCreateForUser($topup->user_id);
                        $topup->update(['wallet_id' => $wallet->id]);
                        $topup->refresh();
                        $topup->load('wallet');
                    }

                    Log::info('matchOrderByAmount: Calling topup.approve()', [
                        'topup_id' => $topup->id,
                        'status' => $topup->status,
                        'wallet_id' => $topup->wallet_id,
                        'wallet_exists' => $topup->wallet !== null,
                        'total_amount' => $topup->total_amount,
                    ]);

                    // Approve topup → adds money to wallet
                    $approved = $topup->approve(0); // 0 = system approved

                    if (! $approved) {
                        Log::error('matchOrderByAmount: WalletTopup approve() returned false!', [
                            'topup_id' => $topup->id,
                            'topup_status' => $topup->status,
                            'topup_status_raw' => $topup->getRawOriginal('status'),
                            'reason' => 'Status was not pending when approve() called',
                        ]);
                    } else {
                        Log::info('matchOrderByAmount: WalletTopup approve() SUCCESS', [
                            'topup_id' => $topup->id,
                            'new_status' => $topup->fresh()->status,
                        ]);
                    }

                    $topup = $topup->fresh(['uniquePaymentAmount', 'user', 'wallet']);

                    Log::info('SMS Payment: Auto-approved WalletTopup on match', [
                        'device_id' => $device->device_id,
                        'amount' => $amount,
                        'topup_id' => $topup->id,
                        'approved' => $approved,
                        'was_already_matched' => $alreadyMatched,
                    ]);

                    // Send FCM push to notify device that topup was approved
                    // This triggers the Android app to update its local DB immediately
                    try {
                        $fcmService = app(\App\Services\FcmNotificationService::class);
                        $fcmService->notifySettingsChanged($device, 'topup_approved', (string) $topup->id);
                    } catch (\Exception $e) {
                        Log::warning('FCM push for topup_approved failed', ['error' => $e->getMessage()]);
                    }
                } catch (\Exception $e) {
                    Log::error('SMS Payment: Auto-approve WalletTopup failed', [
                        'topup_id' => $topup->id,
                        'error' => $e->getMessage(),
                        'file' => $e->getFile() . ':' . $e->getLine(),
                        'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 5),
                    ]);
                }
            }
            $orderData = $this->transformWalletTopupForAndroid($topup);
        }

        // Update device last_active_at
        $device->update(['last_active_at' => now()]);

        Log::info('Order matched by amount', [
            'device_id' => $device->device_id,
            'amount' => $amount,
            'order_id' => $order?->id ?? $topup?->id,
            'type' => $order ? 'order' : 'wallet_topup',
            'already_matched' => $alreadyMatched,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'matched' => true,
                'order' => $orderData,
                'message' => $alreadyMatched ? 'Order already matched and approved' : 'Found matching order',
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

        $autoApproved = Order::where('sms_verification_status', 'confirmed')
            ->whereNotNull('sms_notification_id')
            ->where('created_at', '>=', $startDate)->count();

        $totalConfirmed = Order::where('sms_verification_status', 'confirmed')
            ->where('created_at', '>=', $startDate)->count();

        $manuallyApproved = max(0, $totalConfirmed - $autoApproved);

        // Daily breakdown
        $dailyBreakdown = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayStart = now()->subDays($i)->startOfDay();
            $dayEnd = now()->subDays($i)->endOfDay();

            $dayCount = Order::whereNotNull('unique_payment_amount_id')
                ->whereBetween('created_at', [$dayStart, $dayEnd])->count();
            $dayApproved = Order::where('sms_verification_status', 'confirmed')
                ->whereBetween('created_at', [$dayStart, $dayEnd])->count();
            $dayRejected = Order::where('sms_verification_status', 'rejected')
                ->whereBetween('created_at', [$dayStart, $dayEnd])->count();
            $dayAmount = (float) Order::where('sms_verification_status', 'confirmed')
                ->whereBetween('created_at', [$dayStart, $dayEnd])->sum('total');

            $dailyBreakdown[] = [
                'date' => $date,
                'count' => $dayCount,
                'approved' => $dayApproved,
                'rejected' => $dayRejected,
                'amount' => $dayAmount,
            ];
        }

        $stats = [
            'total_orders' => Order::whereNotNull('unique_payment_amount_id')
                ->where('created_at', '>=', $startDate)->count(),
            'auto_approved' => $autoApproved,
            'manually_approved' => $manuallyApproved,
            'pending_review' => Order::where(function ($q) {
                $q->where('sms_verification_status', 'matched')
                    ->orWhere('sms_verification_status', 'pending');
            })->where('created_at', '>=', $startDate)->count(),
            'rejected' => Order::where('sms_verification_status', 'rejected')
                ->where('created_at', '>=', $startDate)->count(),
            'total_amount' => (float) Order::where('sms_verification_status', 'confirmed')
                ->where('created_at', '>=', $startDate)
                ->sum('total'),
            'daily_breakdown' => $dailyBreakdown,
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
        $limit = (int) $request->input('limit', 100);

        // === Sync Orders ===
        $orderQuery = Order::with(['smsNotification', 'uniquePaymentAmount'])
            ->whereNotNull('unique_payment_amount_id');

        if ($sinceVersion > 0) {
            $orderQuery->where('updated_at', '>', date('Y-m-d H:i:s', $sinceVersion / 1000));
        }

        $orders = $orderQuery->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();

        // === Sync Wallet Topups (เติมเงิน) ===
        $topupQuery = WalletTopup::with(['uniquePaymentAmount'])
            ->whereNotNull('unique_payment_amount_id');

        if ($sinceVersion > 0) {
            $topupQuery->where('updated_at', '>', date('Y-m-d H:i:s', $sinceVersion / 1000));
        }

        $topups = $topupQuery->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();

        // Merge and transform
        $allItems = collect();
        foreach ($orders as $order) {
            $allItems->push($this->transformOrderForAndroid($order));
        }
        foreach ($topups as $topup) {
            $allItems->push($this->transformWalletTopupForAndroid($topup));
        }

        $allItems = $allItems->sortByDesc('updated_at')->values()->take($limit);

        $latestVersion = intval(round(microtime(true) * 1000));

        // Update device last_active_at
        $device->update(['last_active_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $allItems,
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

    /**
     * Auto-generate license keys for order items that require them.
     * (Same logic as Admin\SmsPaymentController::generateLicensesForOrder)
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

    /**
     * Receive encrypted action (approve/reject) from Android app.
     *
     * ใช้ security flow เหมือน notify() (signature, nonce, timestamp, decrypt)
     * Payload: { action, order_identifier, amount, bank, sms_reference, device_id, reason, nonce }
     *
     * POST /api/v1/sms-payment/notify-action
     */
    public function notifyAction(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate required security headers
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
            Log::warning('SMS Payment notifyAction: Invalid signature', [
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
            'action' => 'required|in:approve,reject',
            'order_identifier' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'bank' => 'nullable|string|max:20',
            'sms_reference' => 'nullable|string|max:100',
            'device_id' => 'required|string',
            'reason' => 'nullable|string|max:500',
            'nonce' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payload data',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check for duplicate nonce
        $existingNonce = DB::table('sms_payment_nonces')
            ->where('nonce', $payload['nonce'])
            ->exists();

        if ($existingNonce) {
            Log::warning('SMS Payment notifyAction: Duplicate nonce', [
                'nonce' => $payload['nonce'],
                'device_id' => $device->device_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Duplicate request (nonce already used)',
            ], 409);
        }

        // Record nonce
        DB::table('sms_payment_nonces')->insert([
            'nonce' => $payload['nonce'],
            'device_id' => $device->device_id,
            'used_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Find order by identifier (supports both order_number and numeric ID)
        $identifier = $payload['order_identifier'];
        $order = is_numeric($identifier)
            ? Order::with(['smsNotification', 'uniquePaymentAmount'])->find($identifier)
            : Order::with(['smsNotification', 'uniquePaymentAmount'])->where('order_number', $identifier)->first();

        if (! $order) {
            // Try matching WalletTopup
            $topup = null;
            if (str_starts_with($identifier, 'TOPUP-')) {
                $topupId = str_replace('TOPUP-', '', $identifier);
                $topup = WalletTopup::with(['uniquePaymentAmount', 'user'])->where('topup_id', $topupId)->first();
            }

            if (! $topup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found: ' . $identifier,
                ], 404);
            }

            // Handle WalletTopup action
            return $this->handleTopupAction($payload, $topup, $device, $request->ip());
        }

        // Execute action
        $action = $payload['action'];

        if ($action === 'approve') {
            return $this->executeApproveAction($payload, $order, $device, $request->ip());
        } else {
            return $this->executeRejectAction($payload, $order, $device, $request->ip());
        }
    }

    /**
     * Execute encrypted approve action on an order.
     */
    private function executeApproveAction(array $payload, Order $order, SmsCheckerDevice $device, string $ipAddress): JsonResponse
    {
        // Idempotent: already approved → return success with order data
        if (in_array($order->sms_verification_status, ['confirmed']) && in_array($order->payment_status, ['confirmed', 'paid'])) {
            return response()->json([
                'success' => true,
                'message' => 'Order already approved',
                'data' => [
                    'order' => $this->transformOrderForAndroid($order),
                ],
            ]);
        }

        if (! in_array($order->sms_verification_status, ['pending', 'matched', null])) {
            return response()->json([
                'success' => false,
                'message' => 'Order cannot be approved in current status: ' . $order->sms_verification_status,
            ], 422);
        }

        $order->update([
            'sms_verification_status' => 'confirmed',
            'sms_verified_at' => now(),
            'payment_status' => 'paid',
            'paid_at' => now(),
            'status' => 'completed',
        ]);

        // Mark unique amount as used
        if ($order->uniquePaymentAmount && in_array($order->uniquePaymentAmount->status, ['reserved', 'expired'])) {
            $order->uniquePaymentAmount->update([
                'status' => 'used',
                'matched_at' => now(),
            ]);
        }

        // Update notification if exists
        if ($order->smsNotification) {
            $order->smsNotification->update(['status' => 'confirmed']);
        }

        // Generate license keys + send email
        $this->generateLicensesForOrder($order);

        // Fire PaymentMatched event for notifications (LINE, FCM)
        if ($order->smsNotification) {
            event(new PaymentMatched($order, $order->smsNotification));
        }

        // Update device activity
        $device->update([
            'last_active_at' => now(),
            'ip_address' => $ipAddress,
        ]);

        $order = $order->fresh(['smsNotification', 'uniquePaymentAmount']);

        Log::info('SMS Payment: Order approved via encrypted action', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'amount' => $payload['amount'],
            'bank' => $payload['bank'] ?? 'N/A',
            'device_id' => $device->device_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order approved successfully',
            'data' => [
                'order' => $this->transformOrderForAndroid($order),
            ],
        ]);
    }

    /**
     * Execute encrypted reject action on an order.
     */
    private function executeRejectAction(array $payload, Order $order, SmsCheckerDevice $device, string $ipAddress): JsonResponse
    {
        $reason = $payload['reason'] ?? '';

        $order->update([
            'sms_verification_status' => 'rejected',
            'payment_status' => 'failed',
            'notes' => ($order->notes ? $order->notes . "\n" : '') . '[SMS Rejected] ' . $reason,
        ]);

        // Update notification if exists
        if ($order->smsNotification) {
            $order->smsNotification->update(['status' => 'rejected']);
        }

        // Cancel unique amount
        if ($order->uniquePaymentAmount) {
            $order->uniquePaymentAmount->cancel();
        }

        // Update device activity
        $device->update([
            'last_active_at' => now(),
            'ip_address' => $ipAddress,
        ]);

        $order = $order->fresh(['smsNotification', 'uniquePaymentAmount']);

        Log::info('SMS Payment: Order rejected via encrypted action', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'reason' => $reason,
            'device_id' => $device->device_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order rejected',
            'data' => [
                'order' => $this->transformOrderForAndroid($order),
            ],
        ]);
    }

    /**
     * Handle encrypted action on a WalletTopup.
     */
    private function handleTopupAction(array $payload, WalletTopup $topup, SmsCheckerDevice $device, string $ipAddress): JsonResponse
    {
        $action = $payload['action'];

        if ($action === 'approve') {
            // Idempotent check
            if ($topup->status === WalletTopup::STATUS_APPROVED) {
                return response()->json([
                    'success' => true,
                    'message' => 'Topup already approved',
                    'data' => [
                        'order' => $this->transformWalletTopupForAndroid($topup),
                    ],
                ]);
            }

            $topup->update([
                'sms_verification_status' => 'confirmed',
                'sms_verified_at' => now(),
            ]);

            if ($topup->uniquePaymentAmount && in_array($topup->uniquePaymentAmount->status, ['reserved', 'expired'])) {
                $topup->uniquePaymentAmount->update([
                    'status' => 'used',
                    'matched_at' => now(),
                ]);
            }

            // Approve topup → adds money to wallet
            $approved = $topup->approve(0);

            if (! $approved) {
                Log::warning('SMS Payment: WalletTopup approve() returned false', [
                    'topup_id' => $topup->id,
                    'topup_status' => $topup->status,
                    'reason' => 'Status was not pending when approve() called',
                ]);
            }

            $topup = $topup->fresh(['uniquePaymentAmount', 'wallet.user']);

            Log::info('SMS Payment: WalletTopup approved via encrypted action', [
                'topup_id' => $topup->id,
                'amount' => $payload['amount'],
                'device_id' => $device->device_id,
                'approve_result' => $approved,
            ]);

            // Send FCM push to notify topup approved
            if ($approved) {
                try {
                    $fcmService = app(\App\Services\FcmNotificationService::class);
                    $fcmService->notifySettingsChanged($device, 'topup_approved', (string) $topup->id);
                } catch (\Exception $e) {
                    Log::error('handleTopupAction: FCM push failed', [
                        'topup_id' => $topup->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => $approved,
                'message' => $approved ? 'Wallet topup approved successfully' : 'Topup could not be approved (status: ' . $topup->status . ')',
                'data' => [
                    'order' => $this->transformWalletTopupForAndroid($topup),
                ],
            ]);
        } else {
            // Reject topup
            $reason = $payload['reason'] ?? '';

            $topup->update([
                'sms_verification_status' => 'rejected',
                'status' => WalletTopup::STATUS_REJECTED,
                'notes' => '[SMS Rejected] ' . $reason,
            ]);

            if ($topup->uniquePaymentAmount) {
                $topup->uniquePaymentAmount->cancel();
            }

            $topup = $topup->fresh(['uniquePaymentAmount', 'user']);

            Log::info('SMS Payment: WalletTopup rejected via encrypted action', [
                'topup_id' => $topup->id,
                'reason' => $reason,
                'device_id' => $device->device_id,
            ]);

            // Send FCM push to notify topup rejected
            try {
                $fcmService = app(\App\Services\FcmNotificationService::class);
                $fcmService->notifySettingsChanged($device, 'topup_rejected', (string) $topup->id);
            } catch (\Exception $e) {
                Log::error('handleTopupAction: FCM push failed for rejection', [
                    'topup_id' => $topup->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Wallet topup rejected',
                'data' => [
                    'order' => $this->transformWalletTopupForAndroid($topup),
                ],
            ]);
        }
    }

    /**
     * Debug endpoint: Receive diagnostic data from Android app.
     * Used temporarily to debug FCM token registration issues.
     *
     * POST /api/v1/sms-payment/debug-report
     */
    public function debugReport(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        $deviceId = $device instanceof SmsCheckerDevice ? $device->device_id : 'unknown';

        // Log EVERYTHING the app sends us
        Log::info('📱 APP DEBUG REPORT', [
            'device_id' => $deviceId,
            'ip' => $request->ip(),
            'all_data' => $request->all(),
            'all_headers' => collect($request->headers->all())->except([
                'cookie', 'host', 'connection',
            ])->toArray(),
        ]);

        // Update last_active_at
        if ($device instanceof SmsCheckerDevice) {
            $device->update(['last_active_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Debug report received',
            'server_time' => now()->toDateTimeString(),
            'device_fcm_token_on_server' => $device instanceof SmsCheckerDevice
                ? ($device->fcm_token ? 'present (len=' . strlen($device->fcm_token) . ')' : 'NULL')
                : 'N/A',
        ]);
    }

    /**
     * Debug endpoint: ตรวจสอบว่าทำไม WalletTopup.approve() ไม่ทำงาน
     *
     * GET /api/v1/sms-payment/debug-topup?topup_id=15
     */
    public function debugTopup(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $topupId = $request->input('topup_id');
        $debug = ['timestamp' => now()->toDateTimeString()];

        // ดึง topup ล่าสุดทั้งหมดที่เป็น pending
        $pendingTopups = WalletTopup::where('status', WalletTopup::STATUS_PENDING)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'wallet_id', 'user_id', 'topup_id', 'amount', 'total_amount', 'status',
                'sms_verification_status', 'sms_notification_id', 'unique_payment_amount_id',
                'payment_display_amount', 'created_at', 'expires_at']);
        $debug['pending_topups'] = $pendingTopups->toArray();

        // ถ้ามี topup_id ให้ debug เจาะลึก
        if ($topupId) {
            $topup = WalletTopup::find($topupId);
            if ($topup) {
                $debug['target_topup'] = [
                    'id' => $topup->id,
                    'topup_id' => $topup->topup_id,
                    'wallet_id' => $topup->wallet_id,
                    'user_id' => $topup->user_id,
                    'amount' => $topup->amount,
                    'total_amount' => $topup->total_amount,
                    'status' => $topup->status,
                    'sms_verification_status' => $topup->sms_verification_status,
                    'sms_notification_id' => $topup->sms_notification_id,
                    'unique_payment_amount_id' => $topup->unique_payment_amount_id,
                    'payment_display_amount' => $topup->payment_display_amount,
                    'created_at' => $topup->created_at?->toDateTimeString(),
                    'expires_at' => $topup->expires_at?->toDateTimeString(),
                ];

                // เช็ค wallet
                $wallet = $topup->wallet;
                $debug['wallet'] = $wallet ? [
                    'id' => $wallet->id,
                    'user_id' => $wallet->user_id,
                    'balance' => $wallet->balance,
                    'is_active' => $wallet->is_active,
                ] : 'NULL — wallet ไม่มี! นี่คือสาเหตุที่ approve() fail!';

                // เช็ค unique payment amount
                $uniqueAmount = $topup->uniquePaymentAmount;
                $debug['unique_payment_amount'] = $uniqueAmount ? [
                    'id' => $uniqueAmount->id,
                    'unique_amount' => $uniqueAmount->unique_amount,
                    'status' => $uniqueAmount->status,
                    'expires_at' => $uniqueAmount->expires_at?->toDateTimeString(),
                    'matched_at' => $uniqueAmount->matched_at?->toDateTimeString(),
                ] : 'NULL';

                // เช็ค SMS notification
                if ($topup->sms_notification_id) {
                    $smsNotif = SmsPaymentNotification::find($topup->sms_notification_id);
                    $debug['sms_notification'] = $smsNotif ? [
                        'id' => $smsNotif->id,
                        'status' => $smsNotif->status,
                        'amount' => $smsNotif->amount,
                        'bank' => $smsNotif->bank,
                        'type' => $smsNotif->type,
                    ] : 'NULL';
                }

                // ลอง approve เพื่อดูว่าจะ fail ตรงไหน
                $debug['approve_simulation'] = [];
                $debug['approve_simulation']['status_check'] = $topup->status === WalletTopup::STATUS_PENDING
                    ? 'PASS — status is pending'
                    : 'FAIL — status is "' . $topup->status . '" (not pending)';

                if ($wallet) {
                    $debug['approve_simulation']['wallet_exists'] = 'PASS';
                    $debug['approve_simulation']['total_amount'] = $topup->total_amount;
                    $debug['approve_simulation']['total_amount_type'] = gettype($topup->total_amount);
                    $debug['approve_simulation']['total_amount_raw'] = $topup->getRawOriginal('total_amount');

                    // ลอง deposit ใน dry-run mode (ไม่ทำจริง)
                    try {
                        $debug['approve_simulation']['deposit_preview'] = [
                            'amount' => (float) $topup->total_amount,
                            'description' => "เติมเงิน #{$topup->topup_id}",
                            'payment_method' => $topup->payment_method,
                            'payment_reference' => $topup->topup_id,
                            'wallet_balance_before' => $wallet->balance,
                        ];
                    } catch (\Exception $e) {
                        $debug['approve_simulation']['deposit_preview_error'] = $e->getMessage();
                    }
                } else {
                    $debug['approve_simulation']['wallet_exists'] = 'FAIL — wallet is NULL! This is why approve() fails!';
                    $debug['approve_simulation']['wallet_id_on_topup'] = $topup->wallet_id;

                    // ลองหา wallet ด้วย user_id
                    $walletByUser = \App\Models\Wallet::where('user_id', $topup->user_id)->first();
                    $debug['approve_simulation']['wallet_by_user_id'] = $walletByUser ? [
                        'id' => $walletByUser->id,
                        'balance' => $walletByUser->balance,
                        'is_active' => $walletByUser->is_active,
                    ] : 'NULL — ไม่มี wallet เลย!';
                }

                // เช็ค WalletTransaction ที่เกี่ยวข้อง
                $transactions = \App\Models\WalletTransaction::where(function ($q) use ($topup) {
                    $q->where('payment_reference', $topup->topup_id)
                        ->orWhere('description', 'like', "%{$topup->topup_id}%");
                })->get(['id', 'type', 'amount', 'description', 'created_at']);
                $debug['related_transactions'] = $transactions->toArray();

                // เช็ค approval_mode
                $debug['device_approval_mode'] = $device->getApprovalMode();
                $debug['auto_confirm_config'] = config('smschecker.auto_confirm_matched', true);
            } else {
                $debug['target_topup'] = 'NOT FOUND';
            }
        }

        // เช็ค recent SMS notifications ที่ matched
        $recentSms = SmsPaymentNotification::whereIn('status', ['matched', 'confirmed'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'status', 'amount', 'bank', 'type', 'matched_transaction_id', 'created_at']);
        $debug['recent_matched_sms'] = $recentSms->toArray();

        // เช็ค Laravel error log (last few lines)
        try {
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $lines = file($logFile);
                $relevantLines = [];
                $keywords = ['approve', 'topup', 'wallet', 'matchWalletTopup', 'deposit'];
                foreach (array_slice($lines, -200) as $line) {
                    foreach ($keywords as $keyword) {
                        if (stripos($line, $keyword) !== false) {
                            $relevantLines[] = trim($line);
                            break;
                        }
                    }
                }
                $debug['recent_log_entries'] = array_slice($relevantLines, -20);
            }
        } catch (\Exception $e) {
            $debug['log_error'] = $e->getMessage();
        }

        return response()->json([
            'success' => true,
            'data' => $debug,
        ]);
    }

    /**
     * Debug: ลอง approve topup ด้วยมือ + logging ละเอียด
     *
     * POST /api/v1/sms-payment/debug-topup-approve?topup_id=15
     */
    public function debugTopupApprove(Request $request): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $topupId = $request->input('topup_id');
        if (! $topupId) {
            return response()->json(['success' => false, 'message' => 'topup_id is required'], 400);
        }

        $debug = ['timestamp' => now()->toDateTimeString()];

        $topup = WalletTopup::with(['wallet', 'uniquePaymentAmount'])->find($topupId);
        if (! $topup) {
            return response()->json(['success' => false, 'message' => 'Topup not found'], 404);
        }

        $debug['topup_before'] = [
            'id' => $topup->id,
            'status' => $topup->status,
            'wallet_id' => $topup->wallet_id,
            'total_amount' => $topup->total_amount,
            'sms_verification_status' => $topup->sms_verification_status,
        ];

        // Step 1: เช็ค wallet
        if (! $topup->wallet) {
            $debug['error'] = 'Wallet is NULL! wallet_id=' . $topup->wallet_id;

            // ลองสร้าง wallet ให้ user
            $wallet = \App\Models\Wallet::getOrCreateForUser($topup->user_id);
            $topup->update(['wallet_id' => $wallet->id]);
            $topup->refresh();
            $topup->load('wallet');
            $debug['wallet_fix'] = 'Created/found wallet id=' . $wallet->id . ' for user_id=' . $topup->user_id;
        }

        $debug['wallet'] = [
            'id' => $topup->wallet->id,
            'balance_before' => $topup->wallet->balance,
            'is_active' => $topup->wallet->is_active,
        ];

        // Step 2: ลอง approve
        try {
            $debug['status_before_approve'] = $topup->status;
            $debug['status_equals_pending'] = $topup->status === WalletTopup::STATUS_PENDING;

            if ($topup->status !== WalletTopup::STATUS_PENDING) {
                // Force status to pending for retry
                $topup->update(['status' => WalletTopup::STATUS_PENDING]);
                $topup->refresh();
                $debug['force_status_reset'] = 'Reset status from "' . $debug['status_before_approve'] . '" to pending';
            }

            $approved = $topup->approve(0);
            $topup->refresh();

            $debug['approve_result'] = $approved;
            $debug['topup_after'] = [
                'status' => $topup->status,
                'approved_by' => $topup->approved_by,
                'approved_at' => $topup->approved_at?->toDateTimeString(),
            ];
            $debug['wallet_after'] = [
                'balance' => $topup->wallet->fresh()->balance,
            ];
        } catch (\Exception $e) {
            $debug['approve_exception'] = [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 10),
            ];
        }

        Log::info('🔧 DEBUG TOPUP APPROVE', $debug);

        return response()->json([
            'success' => true,
            'data' => $debug,
        ]);
    }
}
