<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use Illuminate\Http\Request;

/**
 * Admin Affiliate Management.
 *
 * - View all affiliates + stats
 * - Approve/reject/pay commissions
 * - Suspend/activate affiliates
 * - Change commission rates
 */
class AffiliateController extends Controller
{
    /**
     * List all affiliates.
     */
    public function index(Request $request)
    {
        $query = Affiliate::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('referral_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $affiliates = $query->orderByDesc('total_earned')->paginate(20);

        // Summary
        $totalAffiliates = Affiliate::count();
        $totalEarned = Affiliate::sum('total_earned');
        $totalPending = AffiliateCommission::where('status', 'pending')->sum('commission_amount');

        return view('admin.affiliates.index', compact(
            'affiliates',
            'totalAffiliates',
            'totalEarned',
            'totalPending',
        ));
    }

    /**
     * Show affiliate detail + commissions.
     */
    public function show(Affiliate $affiliate)
    {
        $affiliate->load('user');
        $commissions = $affiliate->commissions()
            ->with('order', 'referredUser')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.affiliates.show', compact('affiliate', 'commissions'));
    }

    /**
     * List all pending commissions across all affiliates.
     */
    public function commissions(Request $request)
    {
        $query = AffiliateCommission::with('affiliate.user', 'order', 'referredUser');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'pending');
        }

        $commissions = $query->orderByDesc('created_at')->paginate(20);

        return view('admin.affiliates.commissions', compact('commissions'));
    }

    /**
     * Approve and pay a commission to wallet.
     */
    public function approveCommission(AffiliateCommission $commission)
    {
        if ($commission->approveAndPay(auth()->id())) {
            return back()->with('success', "จ่ายค่าคอมมิชชั่น ฿{$commission->commission_amount} เข้า Wallet แล้ว");
        }

        return back()->with('error', 'ไม่สามารถอนุมัติได้ (อาจจ่ายแล้วหรือถูกปฏิเสธ)');
    }

    /**
     * Reject a commission.
     */
    public function rejectCommission(Request $request, AffiliateCommission $commission)
    {
        $reason = $request->input('reason', 'ปฏิเสธโดย Admin');

        if ($commission->reject($reason)) {
            return back()->with('success', 'ปฏิเสธคอมมิชชั่นแล้ว');
        }

        return back()->with('error', 'ไม่สามารถปฏิเสธได้');
    }

    /**
     * Bulk approve all pending commissions.
     */
    public function bulkApprove()
    {
        $pending = AffiliateCommission::where('status', 'pending')->get();
        $count = 0;
        $total = 0;

        foreach ($pending as $commission) {
            if ($commission->approveAndPay(auth()->id())) {
                $count++;
                $total += $commission->commission_amount;
            }
        }

        return back()->with('success', "อนุมัติ {$count} รายการ รวม ฿" . number_format($total) . ' เข้า Wallet แล้ว');
    }

    /**
     * Update affiliate status or commission rate.
     */
    public function update(Request $request, Affiliate $affiliate)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:active,suspended',
            'commission_rate' => 'nullable|numeric|min:1|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        $affiliate->update(array_filter($validated));

        return back()->with('success', 'อัพเดทข้อมูล Affiliate แล้ว');
    }
}
