<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::withCount('usages')->latest();

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'expired') {
                $query->where(function ($q) {
                    $q->where('expires_at', '<', now())
                        ->orWhere('is_active', false);
                });
            } elseif ($request->status === 'used_up') {
                $query->whereColumn('used_count', '>=', 'usage_limit');
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $coupons = $query->paginate(20)->withQueryString();

        // Stats
        $stats = [
            'total' => Coupon::count(),
            'active' => Coupon::valid()->count(),
            'expired' => Coupon::where('expires_at', '<', now())->count(),
            'total_usage' => Coupon::sum('used_count'),
        ];

        return view('admin.coupons.index', compact('coupons', 'stats'));
    }

    public function create()
    {
        $products = Product::where('is_active', true)->get();
        $categories = Category::all();

        return view('admin.coupons.create', compact('products', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50|unique:coupons,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'required|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'applicable_products' => 'nullable|array',
            'applicable_products.*' => 'exists:products,id',
            'applicable_license_types' => 'nullable|array',
            'first_order_only' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // Generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = Coupon::generateCode();
        }

        $validated['code'] = strtoupper($validated['code']);

        Coupon::create($validated);

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', "สร้างคูปอง '{$validated['code']}' สำเร็จ");
    }

    public function show(Coupon $coupon)
    {
        $coupon->load(['usages.user', 'usages.order']);

        return view('admin.coupons.show', compact('coupon'));
    }

    public function edit(Coupon $coupon)
    {
        $products = Product::where('is_active', true)->get();
        $categories = Category::all();

        return view('admin.coupons.edit', compact('coupon', 'products', 'categories'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code,'.$coupon->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'required|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'applicable_products' => 'nullable|array',
            'applicable_products.*' => 'exists:products,id',
            'applicable_license_types' => 'nullable|array',
            'first_order_only' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['code'] = strtoupper($validated['code']);

        $coupon->update($validated);

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', "อัพเดทคูปอง '{$coupon->code}' สำเร็จ");
    }

    public function destroy(Coupon $coupon)
    {
        $code = $coupon->code;
        $coupon->delete();

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', "ลบคูปอง '{$code}' สำเร็จ");
    }

    public function toggle(Coupon $coupon)
    {
        $coupon->update(['is_active' => ! $coupon->is_active]);

        $status = $coupon->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return redirect()
            ->back()
            ->with('success', "{$status}คูปอง '{$coupon->code}' สำเร็จ");
    }

    public function generateCode()
    {
        return response()->json([
            'code' => Coupon::generateCode(),
        ]);
    }
}
