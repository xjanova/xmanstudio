<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Services\LicenseService;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function __construct(
        protected LicenseService $licenseService
    ) {}

    /**
     * Display a listing of licenses
     */
    public function index(Request $request)
    {
        $query = LicenseKey::with('product')->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('license_type', $request->type);
        }

        // Search by license key
        if ($request->filled('search')) {
            $query->where('license_key', 'like', '%'.$request->search.'%');
        }

        $licenses = $query->paginate(20);

        // Stats
        $stats = [
            'total' => LicenseKey::count(),
            'active' => LicenseKey::where('status', LicenseKey::STATUS_ACTIVE)->count(),
            'expired' => LicenseKey::where('status', LicenseKey::STATUS_EXPIRED)->count(),
            'revoked' => LicenseKey::where('status', LicenseKey::STATUS_REVOKED)->count(),
            'activated' => LicenseKey::whereNotNull('machine_id')->count(),
        ];

        return view('admin.licenses.index', compact('licenses', 'stats'));
    }

    /**
     * Show license details
     */
    public function show(LicenseKey $license)
    {
        $license->load('product', 'order');

        return view('admin.licenses.show', compact('license'));
    }

    /**
     * Show create license form
     */
    public function create()
    {
        $products = Product::where('requires_license', true)->get();

        return view('admin.licenses.create', compact('products'));
    }

    /**
     * Generate new licenses
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:monthly,yearly,lifetime',
            'quantity' => 'required|integer|min:1|max:100',
            'max_activations' => 'required|integer|min:1|max:10',
            'product_id' => 'nullable|exists:products,id',
        ]);

        $licenses = $this->licenseService->generateLicenses(
            $validated['type'],
            $validated['quantity'],
            $validated['max_activations'],
            $validated['product_id'] ?? null
        );

        return redirect()
            ->route('admin.licenses.index')
            ->with('success', sprintf('สร้าง %d license keys สำเร็จ', count($licenses)));
    }

    /**
     * Revoke a license
     */
    public function revoke(Request $request, LicenseKey $license)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $this->licenseService->revoke($license->license_key, $request->reason);

        return redirect()
            ->back()
            ->with('success', "License '{$license->license_key}' ถูกยกเลิกแล้ว");
    }

    /**
     * Reactivate a revoked license
     */
    public function reactivate(LicenseKey $license)
    {
        $license->update([
            'status' => LicenseKey::STATUS_ACTIVE,
        ]);

        return redirect()
            ->back()
            ->with('success', "License '{$license->license_key}' ถูกเปิดใช้งานใหม่แล้ว");
    }

    /**
     * Reset machine activation
     */
    public function resetMachine(LicenseKey $license)
    {
        $license->update([
            'machine_id' => null,
            'machine_fingerprint' => null,
        ]);

        return redirect()
            ->back()
            ->with('success', "รีเซ็ตเครื่องสำหรับ License '{$license->license_key}' แล้ว");
    }

    /**
     * Extend license expiry
     */
    public function extend(Request $request, LicenseKey $license)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        $newExpiry = $license->expires_at
            ? $license->expires_at->addDays($request->days)
            : now()->addDays($request->days);

        $license->update([
            'expires_at' => $newExpiry,
            'status' => LicenseKey::STATUS_ACTIVE,
        ]);

        return redirect()
            ->back()
            ->with('success', "ขยายเวลา License '{$license->license_key}' ไปถึง {$newExpiry->format('d/m/Y')}");
    }
}
