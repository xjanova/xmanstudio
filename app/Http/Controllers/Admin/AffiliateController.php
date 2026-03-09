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
 * - Tree view (org chart)
 * - Move, suspend, activate, delete affiliates
 * - Change commission rates
 */
class AffiliateController extends Controller
{
    /**
     * List all affiliates.
     */
    public function index(Request $request)
    {
        $query = Affiliate::with('user', 'parent.user');

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
     * Show affiliate tree/org chart.
     */
    public function tree()
    {
        // Get root-level affiliates with recursive children
        $roots = Affiliate::root()
            ->with(['user', 'allChildren.user'])
            ->orderByDesc('total_earned')
            ->get();

        $treeData = $roots->map(fn ($a) => $a->toTreeArray())->toArray();
        $totalAffiliates = Affiliate::count();
        $totalRoots = $roots->count();

        // Get all affiliates for the move dropdown
        $allAffiliates = Affiliate::with('user')
            ->orderBy('referral_code')
            ->get(['id', 'user_id', 'referral_code', 'status'])
            ->load('user:id,name,email');

        return view('admin.affiliates.tree', compact('treeData', 'totalAffiliates', 'totalRoots', 'allAffiliates'));
    }

    /**
     * Show affiliate detail + commissions.
     */
    public function show(Affiliate $affiliate)
    {
        $affiliate->load('user', 'parent.user', 'children.user');
        $commissions = $affiliate->commissions()
            ->with('order', 'referredUser')
            ->orderByDesc('created_at')
            ->paginate(20);

        // Get all affiliates for move dropdown (exclude self and descendants)
        $allAffiliates = Affiliate::with('user')
            ->where('id', '!=', $affiliate->id)
            ->orderBy('referral_code')
            ->get()
            ->filter(fn ($a) => ! $a->isDescendantOf($affiliate->id));

        return view('admin.affiliates.show', compact('affiliate', 'commissions', 'allAffiliates'));
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

    /**
     * Move affiliate to a new parent (change upline).
     */
    public function move(Request $request, Affiliate $affiliate)
    {
        $validated = $request->validate([
            'new_parent_id' => 'nullable|exists:affiliates,id',
        ]);

        $newParentId = $validated['new_parent_id'] ?: null;

        // Prevent moving to self
        if ($newParentId == $affiliate->id) {
            return back()->with('error', 'ไม่สามารถย้ายไปอยู่ใต้ตัวเองได้');
        }

        // Prevent circular reference
        if ($newParentId) {
            $newParent = Affiliate::findOrFail($newParentId);
            if ($newParent->isDescendantOf($affiliate->id)) {
                return back()->with('error', 'ไม่สามารถย้ายไปอยู่ใต้ลูกทีมของตัวเองได้');
            }
        }

        $affiliate->update(['parent_id' => $newParentId]);
        $affiliate->updatePath();
        $affiliate->updateDescendantPaths();

        return back()->with('success', 'ย้ายสายงาน Affiliate สำเร็จ');
    }

    /**
     * Suspend/block an affiliate.
     */
    public function suspend(Affiliate $affiliate)
    {
        $affiliate->update(['status' => 'suspended']);

        return back()->with('success', "ระงับ Affiliate {$affiliate->referral_code} แล้ว");
    }

    /**
     * Activate a suspended affiliate.
     */
    public function activate(Affiliate $affiliate)
    {
        $affiliate->update(['status' => 'active']);

        return back()->with('success', "เปิดใช้งาน Affiliate {$affiliate->referral_code} แล้ว");
    }

    /**
     * Delete an affiliate (reassign children to parent).
     */
    public function destroy(Affiliate $affiliate)
    {
        // Reassign children to this affiliate's parent (move up)
        $childCount = $affiliate->children()->count();
        if ($childCount > 0) {
            $affiliate->children()->update(['parent_id' => $affiliate->parent_id]);
            // Update children's paths
            foreach (Affiliate::where('parent_id', $affiliate->parent_id)->get() as $child) {
                $child->updatePath();
                $child->updateDescendantPaths();
            }
        }

        // Reject pending commissions
        $affiliate->commissions()->where('status', 'pending')->each(function ($c) {
            $c->reject('Affiliate ถูกลบ');
        });

        $code = $affiliate->referral_code;
        $affiliate->delete();

        return redirect()->route('admin.affiliates.index')
            ->with('success', "ลบ Affiliate {$code} แล้ว (ลูกทีม {$childCount} คนถูกย้ายขึ้น)");
    }
}
