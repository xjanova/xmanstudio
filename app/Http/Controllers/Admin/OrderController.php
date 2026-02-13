<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Services\LicenseService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display listing of orders
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.product'])
            ->orderBy('created_at', 'desc');

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Filter by payment_status
        if ($paymentStatus = $request->get('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        // Filter by payment_method
        if ($method = $request->get('payment_method')) {
            $query->where('payment_method', $method);
        }

        $orders = $query->paginate(20)->withQueryString();

        // Summary counts
        $counts = [
            'all' => Order::count(),
            'pending' => Order::where('payment_status', 'pending')->count(),
            'verifying' => Order::where('payment_status', 'verifying')->count(),
            'paid' => Order::where('payment_status', 'paid')->count(),
        ];

        return view('admin.orders.index', compact('orders', 'counts'));
    }

    /**
     * Display order details
     */
    public function show(Order $order)
    {
        $order->load(['user', 'items.product', 'items.licenseKey', 'coupon', 'uniquePaymentAmount', 'smsNotification']);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Update payment status (approve / reject)
     */
    public function updatePaymentStatus(Request $request, Order $order)
    {
        $request->validate([
            'payment_status' => 'required|in:paid,pending,verifying,rejected',
            'admin_note' => 'nullable|string|max:500',
        ]);

        $newStatus = $request->payment_status;
        $note = $request->admin_note;

        $updateData = ['payment_status' => $newStatus];

        if ($newStatus === 'paid') {
            $updateData['paid_at'] = now();
            $updateData['status'] = 'processing';

            // Auto-assign license keys for digital products
            $this->assignLicenseKeys($order);
        }

        if ($newStatus === 'rejected') {
            $updateData['status'] = 'cancelled';
        }

        if ($note) {
            $updateData['notes'] = ($order->notes ? $order->notes . "\n" : '')
                . '[Admin] ' . $note . ' — ' . now()->format('d/m/Y H:i');
        }

        $order->update($updateData);

        $statusLabels = [
            'paid' => 'อนุมัติการชำระเงิน',
            'rejected' => 'ปฏิเสธการชำระเงิน',
            'pending' => 'เปลี่ยนสถานะเป็นรอชำระ',
            'verifying' => 'เปลี่ยนสถานะเป็นรอตรวจสอบ',
        ];

        return redirect()
            ->back()
            ->with('success', $statusLabels[$newStatus] . ' #' . $order->order_number . ' สำเร็จ');
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled',
        ]);

        $order->update(['status' => $request->status]);

        return redirect()
            ->back()
            ->with('success', 'อัปเดตสถานะคำสั่งซื้อ #' . $order->order_number . ' สำเร็จ');
    }

    /**
     * Assign license keys to order items
     */
    protected function assignLicenseKeys(Order $order): void
    {
        if (! app()->bound(LicenseService::class)) {
            return;
        }

        $licenseService = app(LicenseService::class);

        foreach ($order->items as $item) {
            if ($item->license_key_id || ! $item->product) {
                continue;
            }

            // Check if product has available license keys
            $licenseKey = LicenseKey::where('product_id', $item->product_id)
                ->where('status', 'available')
                ->first();

            if ($licenseKey) {
                $licenseKey->update([
                    'status' => 'sold',
                    'sold_at' => now(),
                ]);
                $item->update(['license_key_id' => $licenseKey->id]);
            }
        }
    }
}
