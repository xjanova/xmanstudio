<?php

namespace App\Http\Controllers;

use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProjectOrder;
use App\Models\RentalInvoice;
use App\Models\RentalPayment;
use App\Models\SupportTicket;
use App\Models\UserRental;
use App\Models\Wallet;
use App\Services\LicenseRenewalException;
use App\Services\LicenseRenewalService;
use App\Support\CloudStorage;
use Illuminate\Http\RedirectResponse;
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

        // Active Licenses (bought, or claimed free without an order)
        $activeLicenses = $this->ownedLicenses($user->id)
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

        // Active Projects
        $activeProjects = ProjectOrder::where('user_id', $user->id)
            ->active()
            ->with(['features', 'members' => function ($q) {
                $q->where('is_lead', true);
            }])
            ->limit(5)
            ->get();

        // Stats
        $stats = [
            'active_subscriptions' => $activeRentals->count(),
            'active_licenses' => $this->ownedLicenses($user->id)
                ->where('status', LicenseKey::STATUS_ACTIVE)
                ->count(),
            'total_orders' => Order::where('user_id', $user->id)->count(),
            'open_tickets' => $openTickets,
            'active_projects' => $activeProjects->count(),
        ];

        // Expiring Soon (within 7 days) - Subscriptions
        $expiringSoon = UserRental::where('user_id', $user->id)
            ->where('status', UserRental::STATUS_ACTIVE)
            ->whereBetween('expires_at', [now(), now()->addDays(7)])
            ->with('rentalPackage')
            ->get();

        // Expiring/Expired Licenses
        $expiringLicenses = $this->ownedLicenses($user->id)
            ->where('status', LicenseKey::STATUS_ACTIVE)
            ->where('license_type', '!=', 'lifetime')
            ->whereBetween('expires_at', [now(), now()->addDays(7)])
            ->with('product')
            ->get();

        $expiredLicenses = $this->ownedLicenses($user->id)
            ->where('status', LicenseKey::STATUS_ACTIVE)
            ->where('license_type', '!=', 'lifetime')
            ->where('expires_at', '<=', now())
            ->with('product')
            ->get();

        return view('customer.dashboard', compact(
            'user',
            'activeRentals',
            'activeLicenses',
            'activeProjects',
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

        $query = $this->ownedLicenses($user->id)
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

        // Which rows get a "ต่ออายุ" button — the same test the license page and the payment use
        $renewals = app(LicenseRenewalService::class);
        $renewable = $licenses->getCollection()
            ->mapWithKeys(fn (LicenseKey $license) => [$license->id => $renewals->offerFor($license, $user->id) !== null])
            ->all();

        $stats = [
            'total' => $this->ownedLicenses($user->id)->count(),
            'active' => $this->ownedLicenses($user->id)
                ->where('status', LicenseKey::STATUS_ACTIVE)->count(),
            'expired' => $this->ownedLicenses($user->id)
                ->where('status', LicenseKey::STATUS_EXPIRED)->count(),
        ];

        // มีคีย์ Anti X = แนะนำแอปมือถือฟรี (คีย์ของแอปออกให้ต่อมือถือตอนเปิดครั้งแรก จึงไม่เป็นแถวในรายการนี้)
        $mobileOffer = $this->ownedLicenses($user->id)->whereHas('product', fn ($q) => $q->where('slug', 'anti-x'))->exists()
            ? Product::antiXMobileOffer()
            : null;

        return view('customer.licenses', compact('licenses', 'stats', 'mobileOffer', 'renewable'));
    }

    /**
     * License Detail
     * GET /my-account/licenses/{license}
     */
    public function licenseShow(LicenseKey $license)
    {
        if ($license->user_id !== Auth::id()) {
            abort(403);
        }

        $license->load('product', 'activations');

        // "ต่ออายุ" on this key: what one more term costs and when it would end — null when the
        // key cannot be renewed online (lifetime, revoked, off sale, a term not sold). The same
        // test the payment applies, so the page never promises a renewal that becomes a new key.
        $renewal = app(LicenseRenewalService::class)->offerFor($license, Auth::id());
        $walletBalance = $renewal ? (float) Wallet::getOrCreateForUser(Auth::id())->balance : 0.0;
        $expiryStamp = LicenseRenewalService::expiryStamp($license);

        // A cloud product (BrainX Cloud): the space this key carries, part by part, and the packs
        // the customer can buy for it — only those that still fit under max_extra_gb, and none
        // for a revoked key (the cart refuses those anyway)
        $cloudStorage = null;
        $slug = $license->product?->slug;
        if (CloudStorage::offers($slug)) {
            $addonProduct = ($addonSlug = CloudStorage::addonProductSlug($slug))
                ? Product::where('slug', $addonSlug)->where('is_active', true)->first()
                : null;
            $room = CloudStorage::roomLeftGb($license);
            $canBuy = $addonProduct && in_array($license->status, [LicenseKey::STATUS_ACTIVE, LicenseKey::STATUS_EXPIRED], true);

            $cloudStorage = [
                'base' => CloudStorage::baseGb($slug),
                'purchased' => CloudStorage::purchasedGb($license),
                'bonus' => max(0, (int) $license->extra_storage_gb),
                'total' => CloudStorage::totalGb($license),
                'addonProduct' => $addonProduct,
                'packs' => $canBuy
                    ? array_filter(CloudStorage::addons($slug), fn ($price, $gb) => $gb <= $room, ARRAY_FILTER_USE_BOTH)
                    : [],
            ];
        }

        // คีย์ Anti X: ปุ่มโหลดตัวโปรแกรมบน Windows และแอปมือถือฟรี ในหน้าเดียวกัน
        $mobileOffer = $license->product?->slug === 'anti-x' ? Product::antiXMobileOffer() : null;

        return view('customer.license-detail', compact('license', 'renewal', 'walletBalance', 'expiryStamp', 'cloudStorage', 'mobileOffer'));
    }

    /**
     * Renew a license from the wallet, one click
     * POST /my-account/licenses/{license}/renew
     */
    public function renewLicense(Request $request, LicenseKey $license): RedirectResponse
    {
        if ($license->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'expected_expiry' => 'required|string|max:20',
        ]);

        try {
            $order = app(LicenseRenewalService::class)
                ->renewFromWallet($license, $request->user(), false, $validated['expected_expiry']);
        } catch (LicenseRenewalException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'ต่ออายุไม่สำเร็จ ยังไม่มีการตัดเงิน กรุณาลองใหม่อีกครั้ง หรือติดต่อทีมงาน');
        }

        $license->refresh();

        return redirect()
            ->route('customer.licenses.show', $license)
            ->with('success', 'ต่ออายุแล้ว — คีย์เดิมใช้ได้ถึง ' . $license->expires_at?->format('d/m/Y')
                . ' (คำสั่งซื้อ #' . $order->order_number . ')');
    }

    /**
     * Switch automatic renewal from the wallet on or off
     * POST /my-account/licenses/{license}/auto-renew
     */
    public function toggleAutoRenew(Request $request, LicenseKey $license): RedirectResponse
    {
        if ($license->user_id !== Auth::id()) {
            abort(403);
        }

        $enabled = $request->boolean('auto_renew');

        // Switching it off always works; on only for a key the run could actually renew
        if ($enabled && ! app(LicenseRenewalService::class)->offerFor($license, Auth::id())) {
            return back()->with('error', 'คีย์นี้ต่ออายุอัตโนมัติไม่ได้ (ตลอดชีพ ถูกยกเลิก หรือสินค้าไม่เปิดขายแบบต่ออายุ)');
        }

        $license->update(['auto_renew' => $enabled]);

        return back()->with('success', $enabled
            ? 'เปิดต่ออายุอัตโนมัติแล้ว — เราจะแจ้งทางอีเมลล่วงหน้า ' . LicenseRenewalService::NOTICE_DAYS
                . ' วัน และตัดเงินจาก Wallet เมื่อเหลือ ' . LicenseRenewalService::CHARGE_DAYS . ' วันก่อนหมดอายุ'
            : 'ปิดต่ออายุอัตโนมัติแล้ว — เราจะส่งอีเมลเตือนก่อนคีย์หมดอายุ ให้กดต่ออายุเองได้ทุกเมื่อ');
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
        if ($rental->user_id !== Auth::id()) {
            abort(403);
        }

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
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

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

        // มี Anti X = เห็นแอปมือถือ (ฟรี) ในศูนย์ดาวน์โหลดด้วย — คีย์ฟรีของแอปผูกกับมือถือ ไม่ใช่กับบัญชี จึงไม่มาทางไลเซนส์
        if ($licensedProducts->contains('slug', 'anti-x') && ! $licensedProducts->contains('slug', 'anti-x-mobile')
            && ($mobile = Product::where('slug', 'anti-x-mobile')->where('is_active', true)->first())) {
            $licensedProducts->push($mobile);
        }

        // Get products from active rentals (if rental includes software)
        $rentalProducts = UserRental::where('user_id', $user->id)
            ->where('status', UserRental::STATUS_ACTIVE)
            ->where('expires_at', '>', now())
            ->with('rentalPackage')
            ->get();

        return view('customer.downloads', compact('licensedProducts', 'rentalProducts'));
    }

    /**
     * My Projects - รายการโครงการทั้งหมด
     * GET /my-account/projects
     */
    public function projects(Request $request)
    {
        $user = Auth::user();

        $query = ProjectOrder::where('user_id', $user->id)
            ->with(['members' => function ($q) {
                $q->where('is_lead', true);
            }, 'features']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $projects = $query->orderBy('created_at', 'desc')->paginate(10);

        $stats = [
            'total' => ProjectOrder::where('user_id', $user->id)->count(),
            'active' => ProjectOrder::where('user_id', $user->id)->active()->count(),
            'completed' => ProjectOrder::where('user_id', $user->id)->completed()->count(),
        ];

        return view('customer.projects', compact('projects', 'stats'));
    }

    /**
     * Project Detail - ดูรายละเอียดโครงการ
     * GET /my-account/projects/{project}
     */
    public function projectShow(ProjectOrder $project)
    {
        // Ensure user can only view their own projects
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }

        $project->load([
            'members',
            // งวดชำระของงาน — ลูกค้าต้องเห็นว่าจ่ายอะไรไปแล้ว และค้างงวดไหน
            'invoices',
            'features' => function ($q) {
                $q->orderBy('order');
            },
            'progress' => function ($q) {
                $q->where('is_public', true)
                    ->with('feature')
                    ->latest()
                    ->limit(20);
            },
            'timeline' => function ($q) {
                $q->orderBy('event_date');
            },
        ]);

        return view('customer.project-detail', compact('project'));
    }

    /**
     * Keys this account owns: those on its orders, and those put on the account directly
     * without an order (a free key claimed on the web, e.g. MacDock — order_id is null).
     */
    private function ownedLicenses(int $userId)
    {
        return LicenseKey::where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhereIn('order_id', Order::where('user_id', $userId)->select('id'));
        });
    }
}
