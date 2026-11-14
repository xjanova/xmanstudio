<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RentalAddon;
use App\Models\RentalPackage;
use App\Models\RentalPayment;
use App\Models\UserRental;
use App\Services\Rentals\RentalLifecycle;
use App\Services\RentalService;
use App\Support\Satang;
use Illuminate\Http\Request;

class RentalController extends Controller
{
    public function __construct(
        protected RentalService $rentalService,
        protected RentalLifecycle $lifecycle,
    ) {}

    /**
     * Dashboard
     * GET /admin/rentals
     */
    public function index(Request $request)
    {
        $query = UserRental::with(['user', 'rentalPackage']);

        // Filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $rentals = $query->orderBy('created_at', 'desc')->paginate(20);

        // Stats
        $stats = [
            'active' => UserRental::where('status', 'active')->where('expires_at', '>', now())->count(),
            'pending' => UserRental::where('status', 'pending')->count(),
            'expiring_soon' => UserRental::where('status', 'active')
                ->whereBetween('expires_at', [now(), now()->addDays(7)])->count(),
            'expired' => UserRental::where('status', 'expired')->count(),
        ];

        return view('admin.rentals.index', compact('rentals', 'stats'));
    }

    /**
     * Show rental details
     * GET /admin/rentals/{rental}
     */
    public function show(UserRental $rental)
    {
        $rental->load(['user', 'rentalPackage', 'payments', 'addons.addon']);

        return view('admin.rentals.show', compact('rental'));
    }

    /**
     * Extend rental
     * POST /admin/rentals/{rental}/extend
     */
    public function extend(Request $request, UserRental $rental)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365',
            'reason' => 'required|string|max:500',
        ]);

        $baseDate = $rental->expires_at > now() ? $rental->expires_at : now();
        $newExpiresAt = $baseDate->addDays($request->days);

        $rental->update([
            'expires_at' => $newExpiresAt,
            'status' => UserRental::STATUS_ACTIVE,
            'notes' => ($rental->notes ? $rental->notes . "\n" : '') .
                '[' . now()->format('Y-m-d H:i') . "] ขยายเวลา {$request->days} วัน: {$request->reason}",
        ]);

        // Add-ons follow the new expiry; an MLM rental's site hears about it (rental.renewed).
        $this->lifecycle->extended($rental);

        return back()->with('success', "ขยายเวลาสำเร็จ {$request->days} วัน");
    }

    // ==================== Add-ons ====================

    /**
     * Add-on catalogue (XMAN MLM, …): prices per month and per year, on/off.
     * GET /admin/rentals/addons
     */
    public function addons()
    {
        $addons = RentalAddon::withCount([
            'subscriptions as active_count' => fn ($q) => $q->where('status', 'active'),
        ])->orderBy('product_key')->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.rentals.addons', compact('addons'));
    }

    /**
     * PUT /admin/rentals/addons/{addon}
     *
     * Prices are typed in baht and stored in satang. A change applies to new purchases and to the
     * next renewal; what customers already paid is not touched.
     */
    public function updateAddon(Request $request, RentalAddon $addon)
    {
        $validated = $request->validate([
            'name_th' => 'required|string|max:255',
            'description_th' => 'nullable|string|max:500',
            'price_monthly' => 'required|string|max:20',
            'price_yearly' => 'required|string|max:20',
            'max_quantity' => 'required|integer|min:1|max:99',
            'sort_order' => 'nullable|integer',
        ]);

        $monthly = Satang::parseInput($validated['price_monthly']);
        $yearly = Satang::parseInput($validated['price_yearly']);
        if ($monthly === null || $yearly === null) {
            return back()->withInput()->with('error', 'ราคาไม่ถูกต้อง — ใส่เป็นบาท เช่น 190 หรือ 190.50');
        }

        $addon->update([
            'name_th' => $validated['name_th'],
            'description_th' => $validated['description_th'] ?? null,
            'price_monthly_satang' => $monthly,
            'price_yearly_satang' => $yearly,
            'max_quantity' => (int) $validated['max_quantity'],
            'sort_order' => (int) ($validated['sort_order'] ?? $addon->sort_order),
            'is_active' => $request->boolean('is_active'),
            // Saving a price here is the admin settling it.
            'price_is_proposal' => $request->boolean('price_is_proposal'),
        ]);

        return back()->with('success', "บันทึก {$addon->name_th} แล้ว");
    }

    /**
     * Suspend rental
     * POST /admin/rentals/{rental}/suspend
     */
    public function suspend(Request $request, UserRental $rental)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $rental->update([
            'status' => UserRental::STATUS_SUSPENDED,
            'notes' => ($rental->notes ? $rental->notes . "\n" : '') .
                '[' . now()->format('Y-m-d H:i') . "] ระงับ: {$request->reason}",
        ]);

        return back()->with('success', 'ระงับการใช้งานสำเร็จ');
    }

    /**
     * Reactivate rental
     * POST /admin/rentals/{rental}/reactivate
     */
    public function reactivate(UserRental $rental)
    {
        if ($rental->expires_at < now()) {
            return back()->with('error', 'Rental หมดอายุแล้ว กรุณาขยายเวลาแทน');
        }

        $rental->update([
            'status' => UserRental::STATUS_ACTIVE,
            'notes' => ($rental->notes ? $rental->notes . "\n" : '') .
                '[' . now()->format('Y-m-d H:i') . '] เปิดใช้งานอีกครั้ง',
        ]);

        return back()->with('success', 'เปิดใช้งานอีกครั้งสำเร็จ');
    }

    // ==================== Payments ====================

    /**
     * Payments list
     * GET /admin/rentals/payments
     */
    public function payments(Request $request)
    {
        $query = RentalPayment::with(['user', 'userRental.rentalPackage']);

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(20);

        // Stats
        $stats = [
            'pending' => RentalPayment::where('status', 'pending')->count(),
            'processing' => RentalPayment::where('status', 'processing')->count(),
            'today_revenue' => RentalPayment::where('status', 'completed')
                ->whereDate('paid_at', today())->sum('amount'),
            'month_revenue' => RentalPayment::where('status', 'completed')
                ->whereMonth('paid_at', now()->month)->sum('amount'),
        ];

        return view('admin.rentals.payments', compact('payments', 'stats'));
    }

    /**
     * Verify payment
     * POST /admin/rentals/payments/{payment}/verify
     */
    public function verifyPayment(Request $request, RentalPayment $payment)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $result = $this->rentalService->verifyBankTransfer(
            $payment,
            auth()->id(),
            $request->notes
        );

        if (! $result['success']) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', 'ยืนยันการชำระเงินสำเร็จ');
    }

    /**
     * Reject payment
     * POST /admin/rentals/payments/{payment}/reject
     */
    public function rejectPayment(Request $request, RentalPayment $payment)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if (! $this->rentalService->rejectPayment($payment, $request->reason)) {
            return back()->with('error', 'รายการนี้ถูกดำเนินการไปแล้ว ปฏิเสธไม่ได้');
        }

        return back()->with('success', 'ปฏิเสธการชำระเงินแล้ว');
    }

    // ==================== Packages ====================

    /**
     * Packages list
     * GET /admin/rentals/packages
     */
    public function packages()
    {
        $packages = RentalPackage::withCount([
            'userRentals',
            'userRentals as active_count' => fn ($q) => $q->where('status', 'active'),
        ])->orderBy('sort_order')->get();

        return view('admin.rentals.packages', compact('packages'));
    }

    /**
     * Create package form
     * GET /admin/rentals/packages/create
     */
    public function createPackage()
    {
        return view('admin.rentals.package-form', ['package' => null]);
    }

    /**
     * Store package
     * POST /admin/rentals/packages
     */
    public function storePackage(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'name_th' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'description_th' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'duration_type' => 'required|in:hourly,daily,weekly,monthly,yearly',
            'duration_value' => 'required|integer|min:1',
            'features' => 'nullable|array',
            'limits' => 'nullable|array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        RentalPackage::create($validated);

        return redirect()->route('admin.rentals.packages')
            ->with('success', 'สร้างแพ็กเกจสำเร็จ');
    }

    /**
     * Edit package form
     * GET /admin/rentals/packages/{package}/edit
     */
    public function editPackage(RentalPackage $package)
    {
        return view('admin.rentals.package-form', compact('package'));
    }

    /**
     * Update package
     * PUT /admin/rentals/packages/{package}
     */
    public function updatePackage(Request $request, RentalPackage $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'name_th' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'description_th' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'duration_type' => 'required|in:hourly,daily,weekly,monthly,yearly',
            'duration_value' => 'required|integer|min:1',
            'features' => 'nullable|array',
            'limits' => 'nullable|array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'nullable|integer',
            // Only product packages show this box; for the others the key is absent and untouched.
            'price_is_proposal' => 'nullable|boolean',
        ]);

        $package->update($validated);

        return redirect()->route('admin.rentals.packages')
            ->with('success', 'อัพเดทแพ็กเกจสำเร็จ');
    }

    /**
     * Toggle package active status
     * POST /admin/rentals/packages/{package}/toggle
     */
    public function togglePackage(RentalPackage $package)
    {
        $package->update(['is_active' => ! $package->is_active]);

        $status = $package->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return back()->with('success', "{$status}แพ็กเกจแล้ว");
    }

    // ==================== Reports ====================

    /**
     * Revenue report
     * GET /admin/rentals/reports
     */
    public function reports(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $payments = RentalPayment::where('status', 'completed')
            ->whereBetween('paid_at', [$startDate, $endDate . ' 23:59:59'])
            ->get();

        $stats = [
            'total_revenue' => $payments->sum('amount'),
            'total_transactions' => $payments->count(),
            'average_transaction' => $payments->avg('amount') ?? 0,
        ];

        // Group by day
        $chartData = $payments->groupBy(fn ($p) => $p->paid_at->format('Y-m-d'))
            ->map(fn ($items, $date) => [
                'date' => $date,
                'revenue' => $items->sum('amount'),
                'count' => $items->count(),
            ])->values();

        // By package
        $byPackage = $payments->groupBy(fn ($p) => $p->userRental?->rentalPackage?->name ?? 'ไม่ระบุ')
            ->map(fn ($items, $name) => [
                'name' => $name,
                'revenue' => $items->sum('amount'),
                'count' => $items->count(),
            ])->values();

        return view('admin.rentals.reports', compact('stats', 'chartData', 'byPackage', 'startDate', 'endDate'));
    }
}
