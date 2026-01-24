<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * Validate coupon code
     */
    public function validate(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'product_ids' => 'nullable|array',
        ]);

        $code = strtoupper(trim($validated['code']));

        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return response()->json([
                'valid' => false,
                'message' => 'ไม่พบคูปองนี้',
            ], 404);
        }

        // Check if coupon can be used
        $canUse = $coupon->canBeUsedBy(
            auth()->user(),
            $validated['amount'],
            $validated['product_ids'] ?? []
        );

        if (!$canUse['valid']) {
            return response()->json([
                'valid' => false,
                'message' => $canUse['message'],
            ], 422);
        }

        // Calculate discount
        $discount = $coupon->calculateDiscount($validated['amount']);

        return response()->json([
            'valid' => true,
            'message' => 'ใช้คูปองสำเร็จ',
            'coupon' => [
                'code' => $coupon->code,
                'name' => $coupon->name,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
                'discount_label' => $coupon->discount_label,
            ],
            'discount_amount' => $discount,
            'final_amount' => max(0, $validated['amount'] - $discount),
        ]);
    }

    /**
     * Apply coupon to order (called during checkout)
     */
    public function apply(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        $code = strtoupper(trim($validated['code']));

        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบคูปองนี้',
            ], 404);
        }

        // Store coupon in session
        session(['applied_coupon' => $coupon->code]);

        return response()->json([
            'success' => true,
            'message' => 'ใช้คูปองสำเร็จ',
            'coupon' => [
                'code' => $coupon->code,
                'name' => $coupon->name,
                'discount_label' => $coupon->discount_label,
            ],
        ]);
    }

    /**
     * Remove coupon from session
     */
    public function remove()
    {
        session()->forget('applied_coupon');

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกคูปองแล้ว',
        ]);
    }
}
