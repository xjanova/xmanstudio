<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use Illuminate\Http\Request;

/**
 * Customer-facing Affiliate Dashboard.
 *
 * Users can:
 * - Register as affiliate
 * - View their referral link & stats
 * - Track commissions & wallet payouts
 */
class AffiliateController extends Controller
{
    /**
     * Dashboard — stats, referral link, recent commissions.
     */
    public function dashboard()
    {
        $user = auth()->user();
        $affiliate = Affiliate::where('user_id', $user->id)->first();

        $recentCommissions = [];
        $monthlyStats = [];

        if ($affiliate) {
            $recentCommissions = $affiliate->commissions()
                ->with('order', 'referredUser')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            // Monthly stats (last 6 months)
            $monthlyStats = AffiliateCommission::where('affiliate_id', $affiliate->id)
                ->where('created_at', '>=', now()->subMonths(6))
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month")
                ->selectRaw('COUNT(*) as total_orders')
                ->selectRaw('SUM(commission_amount) as total_commission')
                ->selectRaw("SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END) as paid_amount")
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        }

        return view('customer.affiliate.dashboard', compact(
            'affiliate',
            'recentCommissions',
            'monthlyStats',
        ));
    }

    /**
     * Register as affiliate.
     */
    public function register()
    {
        $user = auth()->user();

        // Check if already registered
        $existing = Affiliate::where('user_id', $user->id)->first();

        if ($existing) {
            return redirect()->route('customer.affiliate.dashboard')
                ->with('info', 'คุณเป็นพันธมิตร (Affiliate) อยู่แล้ว');
        }

        $affiliate = Affiliate::getOrCreateForUser($user->id);

        return redirect()->route('customer.affiliate.dashboard')
            ->with('success', 'ลงทะเบียนเป็นพันธมิตร (Affiliate) สำเร็จ!');
    }

    /**
     * Commission history (paginated).
     */
    public function commissions(Request $request)
    {
        $user = auth()->user();
        $affiliate = Affiliate::where('user_id', $user->id)->first();

        if (! $affiliate) {
            return redirect()->route('customer.affiliate.dashboard');
        }

        $query = $affiliate->commissions()->with('order', 'referredUser');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $commissions = $query->orderByDesc('created_at')->paginate(20);

        return view('customer.affiliate.commissions', compact('affiliate', 'commissions'));
    }
}
