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
            $updateData['fcm_token_updated_at'] = now();
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
        // Map sms_verification_status to approval_status for Android
        $approvalStatus = match ($order->sms_verification_status) {
            'pending', null => 'pending_review',
            'matched' => 'pending_review',
            'confirmed' => 'auto_approved',
            'rejected' => 'rejected',
            'cancelled' => 'cancelled',
            'timeout' => 'expired',
            'expired' => 'expired',
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
        // Map sms_verification_status to approval_status for Android
        $approvalStatus = match ($topup->sms_verification_status) {
            'pending', null => 'pending_review',
            'matched' => 'pending_review',
            'confirmed' => 'auto_approved',
            'rejected' => 'rejected',
            'cancelled' => 'cancelled',
            'timeout' => 'expired',
            'expired' => 'expired',
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
     * POST /api/v1/sms-payment/orders/{id}/approve
     */
    public function approveOrder(Request $request, int $id): JsonResponse
    {
        $device = $request->attributes->get('sms_checker_device');
        if (! $device instanceof SmsCheckerDevice) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $order = Order::with(['smsNotification', 'uniquePaymentAmount'])->find($id);
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
            'ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ids = $request->input('ids');
        $approved = 0;
        $failed = 0;
        $errors = [];

        foreach ($ids as $id) {
            $order = Order::find($id);
            if (! $order) {
                $failed++;
                $errors[] = "Order {$id} not found";

                continue;
            }

            if (! in_array($order->sms_verification_status, ['pending', 'matched'])) {
                $failed++;
                $errors[] = "Order {$id} cannot be approved in current status";

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

        // Find Order with matching unique_amount (status=reserved → ยังไม่ถูก match)
        $order = Order::with(['smsNotification', 'uniquePaymentAmount'])
            ->whereHas('uniquePaymentAmount', function ($q) use ($amount) {
                $q->where('unique_amount', $amount)
                    ->where('status', 'reserved');
            })
            ->whereIn('sms_verification_status', ['pending', null])
            ->where('payment_status', '!=', 'paid')
            ->orderBy('created_at', 'desc')
            ->first();

        // Fallback: ถ้า /notify → attemptMatch() ทำงานก่อนแล้ว (status='used')
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

        // ถ้าไม่พบ Order → ลองหา Wallet Topup (เติมเงิน)
        $topup = null;
        if (! $order) {
            $topup = WalletTopup::with(['uniquePaymentAmount', 'user'])
                ->whereHas('uniquePaymentAmount', function ($q) use ($amount) {
                    $q->where('unique_amount', $amount)
                        ->whereIn('status', ['reserved', 'used']);
                })
                ->orderBy('created_at', 'desc')
                ->first();

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

                    // Approve topup → adds money to wallet
                    $topup->approve(0); // 0 = system approved

                    $topup = $topup->fresh(['uniquePaymentAmount', 'user']);

                    Log::info('SMS Payment: Auto-approved WalletTopup on match', [
                        'device_id' => $device->device_id,
                        'amount' => $amount,
                        'topup_id' => $topup->id,
                        'was_already_matched' => $alreadyMatched,
                    ]);
                } catch (\Exception $e) {
                    Log::error('SMS Payment: Auto-approve WalletTopup failed', [
                        'topup_id' => $topup->id,
                        'error' => $e->getMessage(),
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
}
