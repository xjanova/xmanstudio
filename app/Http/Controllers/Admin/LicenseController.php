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

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('license_type', $request->type);
        }

        // Filter by activation status
        if ($request->filled('activated')) {
            if ($request->activated === 'yes') {
                $query->whereNotNull('machine_id');
            } else {
                $query->whereNull('machine_id');
            }
        }

        // Filter expiring soon (within 7 days)
        if ($request->filled('expiring_soon') && $request->expiring_soon === 'yes') {
            $query->where('license_type', '!=', 'lifetime')
                  ->whereNotNull('expires_at')
                  ->where('expires_at', '>', now())
                  ->where('expires_at', '<=', now()->addDays(7));
        }

        // Filter by created date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by license key or machine_id
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('license_key', 'like', '%'.$search.'%')
                  ->orWhere('machine_id', 'like', '%'.$search.'%');
            });
        }

        $licenses = $query->paginate(20)->withQueryString();

        // Get products for filter dropdown
        $products = Product::where('requires_license', true)->get();

        // Stats - filtered by product if selected
        $statsQuery = LicenseKey::query();
        if ($request->filled('product_id')) {
            $statsQuery->where('product_id', $request->product_id);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->where('status', LicenseKey::STATUS_ACTIVE)->count(),
            'expired' => (clone $statsQuery)->where('status', LicenseKey::STATUS_EXPIRED)->count(),
            'revoked' => (clone $statsQuery)->where('status', LicenseKey::STATUS_REVOKED)->count(),
            'activated' => (clone $statsQuery)->whereNotNull('machine_id')->count(),
            'expiring_soon' => (clone $statsQuery)
                ->where('license_type', '!=', 'lifetime')
                ->whereNotNull('expires_at')
                ->where('expires_at', '>', now())
                ->where('expires_at', '<=', now()->addDays(7))
                ->count(),
        ];

        return view('admin.licenses.index', compact('licenses', 'products', 'stats'));
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
            'product_id' => 'required|exists:products,id',
        ]);

        $licenses = $this->licenseService->generateLicenses(
            $validated['type'],
            $validated['quantity'],
            $validated['max_activations'],
            $validated['product_id']
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

    /**
     * Delete a license
     */
    public function destroy(LicenseKey $license)
    {
        $key = $license->license_key;
        $license->delete();

        return redirect()
            ->route('admin.licenses.index')
            ->with('success', "ลบ License '{$key}' เรียบร้อยแล้ว");
    }

    /**
     * Bulk revoke licenses
     */
    public function bulkRevoke(Request $request)
    {
        $request->validate([
            'license_ids' => 'required|array|min:1',
            'license_ids.*' => 'exists:license_keys,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $count = LicenseKey::whereIn('id', $request->license_ids)
            ->where('status', LicenseKey::STATUS_ACTIVE)
            ->update([
                'status' => LicenseKey::STATUS_REVOKED,
                'revoke_reason' => $request->reason,
            ]);

        return redirect()
            ->back()
            ->with('success', "ยกเลิก {$count} License(s) เรียบร้อยแล้ว");
    }

    /**
     * Bulk extend licenses
     */
    public function bulkExtend(Request $request)
    {
        $request->validate([
            'license_ids' => 'required|array|min:1',
            'license_ids.*' => 'exists:license_keys,id',
            'days' => 'required|integer|min:1|max:365',
        ]);

        $count = 0;
        $licenses = LicenseKey::whereIn('id', $request->license_ids)->get();

        foreach ($licenses as $license) {
            if ($license->license_type !== 'lifetime') {
                $newExpiry = $license->expires_at
                    ? $license->expires_at->addDays($request->days)
                    : now()->addDays($request->days);

                $license->update([
                    'expires_at' => $newExpiry,
                    'status' => LicenseKey::STATUS_ACTIVE,
                ]);
                $count++;
            }
        }

        return redirect()
            ->back()
            ->with('success', "ขยายเวลา {$count} License(s) เรียบร้อยแล้ว");
    }

    /**
     * Bulk delete licenses
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'license_ids' => 'required|array|min:1',
            'license_ids.*' => 'exists:license_keys,id',
        ]);

        $count = LicenseKey::whereIn('id', $request->license_ids)->delete();

        return redirect()
            ->back()
            ->with('success', "ลบ {$count} License(s) เรียบร้อยแล้ว");
    }

    /**
     * Export licenses to CSV
     */
    public function export(Request $request)
    {
        $query = LicenseKey::with('product')->latest();

        // Apply same filters as index
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('license_type', $request->type);
        }

        $licenses = $query->get();

        $filename = 'licenses_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($licenses) {
            $file = fopen('php://output', 'w');
            // Add BOM for Excel UTF-8 support
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers
            fputcsv($file, [
                'License Key',
                'Product',
                'Type',
                'Status',
                'Machine ID',
                'Expires At',
                'Created At',
            ]);

            foreach ($licenses as $license) {
                fputcsv($file, [
                    $license->license_key,
                    $license->product?->name ?? '-',
                    ucfirst($license->license_type),
                    ucfirst($license->status),
                    $license->machine_id ?? '-',
                    $license->license_type === 'lifetime' ? 'Lifetime' : ($license->expires_at?->format('d/m/Y') ?? '-'),
                    $license->created_at?->format('d/m/Y H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
