<?php

namespace App\Http\Controllers;

use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\RentalInvoice;
use App\Models\RentalPayment;
use App\Models\SupportTicket;
use App\Models\UserRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerPortalController extends Controller
{
    /**
     * Customer Dashboard - ภาพรวมทั้งหมด
     * GET /my-account
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Active Subscriptions
        $activeRentals = UserRental::where('user_id', $user->id)
            ->where('status', UserRental::STATUS_ACTIVE)
            ->where('expires_at', '>', now())
            ->with('rentalPackage')
            ->get();

        // Active Licenses (through orders)
        $userOrderIds = Order::where('user_id', $user->id)->pluck('id');
        $activeLicenses = LicenseKey::whereIn('order_id', $userOrderIds)
            ->where('status', LicenseKey::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with('product')
            ->limit(5)
            ->get();

        // Recent Orders
        $recentOrders = Order::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Open Support Tickets
        $openTickets = SupportTicket::where('user_id', $user->id)
            ->whereNotIn('status', ['closed', 'resolved'])
            ->count();

        // Stats
        $stats = [
            'active_subscriptions' => $activeRentals->count(),
            'active_licenses' => LicenseKey::whereIn('order_id', $userOrderIds)
                ->where('status', LicenseKey::STATUS_ACTIVE)
                ->count(),
            'total_orders' => Order::where('user_id', $user->id)->count(),
            'open_tickets' => $openTickets,
        ];

        // Expiring Soon (within 7 days) - Subscriptions
        $expiringSoon = UserRental::where('user_id', $user->id)
            ->where('status', UserRental::STATUS_ACTIVE)
            ->whereBetween('expires_at', [now(), now()->addDays(7)])
            ->with('rentalPackage')
            ->get();

        // Expiring/Expired Licenses
        $expiringLicenses = LicenseKey::whereIn('order_id', $userOrderIds)
            ->where('status', LicenseKey::STATUS_ACTIVE)
            ->where('license_type', '!=', 'lifetime')
            ->whereBetween('expires_at', [now(), now()->addDays(7)])
            ->with('product')
            ->get();

        $expiredLicenses = LicenseKey::whereIn('order_id', $userOrderIds)
            ->where('status', LicenseKey::STATUS_ACTIVE)
            ->where('license_type', '!=', 'lifetime')
            ->where('expires_at', '<=', now())
            ->with('product')
            ->get();

        return view('customer.dashboard', compact(
            'user',
            'activeRentals',
            'activeLicenses',
            'recentOrders',
            'stats',
            'expiringSoon',
            'expiringLicenses',
            'expiredLicenses'
        ));
    }

    /**
     * My Licenses - รายการ License ทั้งหมด
     * GET /my-account/licenses
     */
    public function licenses(Request $request)
    {
        $user = Auth::user();
        $userOrderIds = Order::where('user_id', $user->id)->pluck('id');

        $query = LicenseKey::whereIn('order_id', $userOrderIds)
            ->with('product');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('license_type', $request->type);
        }

        $licenses = $query->orderBy('created_at', 'desc')->paginate(10);

        $stats = [
            'total' => LicenseKey::whereIn('order_id', $userOrderIds)->count(),
            'active' => LicenseKey::whereIn('order_id', $userOrderIds)
                ->where('status', LicenseKey::STATUS_ACTIVE)->count(),
            'expired' => LicenseKey::whereIn('order_id', $userOrderIds)
                ->where('status', LicenseKey::STATUS_EXPIRED)->count(),
        ];

        return view('customer.licenses', compact('licenses', 'stats'));
    }

    /**
     * License Detail
     * GET /my-account/licenses/{license}
     */
    public function licenseShow(LicenseKey $license)
    {
        $this->authorize('view', $license);

        $license->load('product', 'activations');

        return view('customer.license-detail', compact('license'));
    }

    /**
     * My Subscriptions - รายการ Subscription
     * GET /my-account/subscriptions
     */
    public function subscriptions(Request $request)
    {
        $user = Auth::user();

        $query = UserRental::where('user_id', $user->id)
            ->with(['rentalPackage', 'payments']);

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $subscriptions = $query->orderBy('created_at', 'desc')->paginate(10);

        $stats = [
            'active' => UserRental::where('user_id', $user->id)
                ->where('status', UserRental::STATUS_ACTIVE)->count(),
            'expired' => UserRental::where('user_id', $user->id)
                ->where('status', UserRental::STATUS_EXPIRED)->count(),
            'total_spent' => RentalPayment::where('user_id', $user->id)
                ->where('status', RentalPayment::STATUS_COMPLETED)
                ->sum('amount'),
        ];

        return view('customer.subscriptions', compact('subscriptions', 'stats'));
    }

    /**
     * Subscription Detail
     * GET /my-account/subscriptions/{rental}
     */
    public function subscriptionShow(UserRental $rental)
    {
        $this->authorize('view', $rental);

        $rental->load(['rentalPackage', 'payments', 'invoices']);

        return view('customer.subscription-detail', compact('rental'));
    }

    /**
     * Order History
     * GET /my-account/orders
     */
    public function orders(Request $request)
    {
        $user = Auth::user();

        $query = Order::where('user_id', $user->id)
            ->with('items.product');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10);

        $stats = [
            'total_orders' => Order::where('user_id', $user->id)->count(),
            'completed' => Order::where('user_id', $user->id)
                ->where('status', 'completed')->count(),
            'total_spent' => Order::where('user_id', $user->id)
                ->where('status', 'completed')
                ->sum('total'),
        ];

        return view('customer.orders', compact('orders', 'stats'));
    }

    /**
     * Order Detail
     * GET /my-account/orders/{order}
     */
    public function orderShow(Order $order)
    {
        $this->authorize('view', $order);

        $order->load('items.product');

        return view('customer.order-detail', compact('order'));
    }

    /**
     * Invoices
     * GET /my-account/invoices
     */
    public function invoices(Request $request)
    {
        $user = Auth::user();

        $invoices = RentalInvoice::where('user_id', $user->id)
            ->with('userRental.rentalPackage')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('customer.invoices', compact('invoices'));
    }

    /**
     * Download Center
     * GET /my-account/downloads
     */
    public function downloads()
    {
        $user = Auth::user();
        $userOrderIds = Order::where('user_id', $user->id)->pluck('id');

        // Get products from licenses
        $licensedProducts = LicenseKey::whereIn('order_id', $userOrderIds)
            ->where('status', LicenseKey::STATUS_ACTIVE)
            ->with('product')
            ->get()
            ->pluck('product')
            ->filter()
            ->unique('id');

        // Get products from active rentals (if rental includes software)
        $rentalProducts = UserRental::where('user_id', $user->id)
            ->where('status', UserRental::STATUS_ACTIVE)
            ->where('expires_at', '>', now())
            ->with('rentalPackage')
            ->get();

        return view('customer.downloads', compact('licensedProducts', 'rentalProducts'));
    }
}
