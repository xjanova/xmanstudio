<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LicenseActivity;
use App\Models\LicenseKey;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LicenseAnalyticsController extends Controller
{
    /**
     * Display the license analytics dashboard.
     */
    public function index(Request $request)
    {
        $period = $request->get('period', '30'); // days
        $productId = $request->get('product_id');
        $startDate = now()->subDays((int) $period);

        // Base query for filtering
        $baseQuery = LicenseKey::query();
        if ($productId) {
            $baseQuery->where('product_id', $productId);
        }

        // Overview stats
        $stats = [
            'total_licenses' => (clone $baseQuery)->count(),
            'active_licenses' => (clone $baseQuery)->where('status', LicenseKey::STATUS_ACTIVE)->count(),
            'activated_licenses' => (clone $baseQuery)->whereNotNull('machine_id')->count(),
            'lifetime_licenses' => (clone $baseQuery)->where('license_type', 'lifetime')->count(),
            'monthly_licenses' => (clone $baseQuery)->where('license_type', 'monthly')->count(),
            'yearly_licenses' => (clone $baseQuery)->where('license_type', 'yearly')->count(),
            'demo_licenses' => (clone $baseQuery)->where('license_type', 'demo')->count(),
            'expired_licenses' => (clone $baseQuery)->where('status', LicenseKey::STATUS_EXPIRED)->count(),
            'revoked_licenses' => (clone $baseQuery)->where('status', LicenseKey::STATUS_REVOKED)->count(),
            'expiring_soon' => (clone $baseQuery)
                ->where('license_type', '!=', 'lifetime')
                ->whereNotNull('expires_at')
                ->where('expires_at', '>', now())
                ->where('expires_at', '<=', now()->addDays(7))
                ->count(),
        ];

        // Licenses created over time (for chart)
        $licensesOverTime = LicenseKey::query()
            ->when($productId, fn($q) => $q->where('product_id', $productId))
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Fill missing dates
        $chartData = [];
        for ($i = (int) $period; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData[$date] = $licensesOverTime[$date] ?? 0;
        }

        // Activations over time
        $activationsOverTime = LicenseActivity::query()
            ->when($productId, function ($q) use ($productId) {
                $q->whereHas('license', fn($lq) => $lq->where('product_id', $productId));
            })
            ->where('action', LicenseActivity::ACTION_ACTIVATED)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        $activationChartData = [];
        for ($i = (int) $period; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $activationChartData[$date] = $activationsOverTime[$date] ?? 0;
        }

        // License types distribution
        $typeDistribution = LicenseKey::query()
            ->when($productId, fn($q) => $q->where('product_id', $productId))
            ->select('license_type', DB::raw('COUNT(*) as count'))
            ->groupBy('license_type')
            ->pluck('count', 'license_type')
            ->toArray();

        // Status distribution
        $statusDistribution = LicenseKey::query()
            ->when($productId, fn($q) => $q->where('product_id', $productId))
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Top products by licenses
        $topProducts = LicenseKey::query()
            ->select('product_id', DB::raw('COUNT(*) as count'))
            ->groupBy('product_id')
            ->with('product:id,name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Recent activities
        $recentActivities = LicenseActivity::with(['license', 'user'])
            ->when($productId, function ($q) use ($productId) {
                $q->whereHas('license', fn($lq) => $lq->where('product_id', $productId));
            })
            ->latest()
            ->limit(20)
            ->get();

        // Activity by type (last 30 days)
        $activityByType = LicenseActivity::query()
            ->when($productId, function ($q) use ($productId) {
                $q->whereHas('license', fn($lq) => $lq->where('product_id', $productId));
            })
            ->where('created_at', '>=', $startDate)
            ->select('action', DB::raw('COUNT(*) as count'))
            ->groupBy('action')
            ->pluck('count', 'action')
            ->toArray();

        // Revenue estimate (based on license types)
        $revenueData = $this->calculateRevenue($productId, $startDate);

        // Get products for filter
        $products = Product::where('requires_license', true)->get();

        return view('admin.licenses.analytics', compact(
            'stats',
            'chartData',
            'activationChartData',
            'typeDistribution',
            'statusDistribution',
            'topProducts',
            'recentActivities',
            'activityByType',
            'revenueData',
            'products',
            'period',
            'productId'
        ));
    }

    /**
     * Calculate estimated revenue.
     */
    private function calculateRevenue($productId, $startDate)
    {
        // Get product pricing (simplified - you may want to store actual prices)
        $prices = [
            'demo' => 0,
            'monthly' => 490,
            'yearly' => 2990,
            'lifetime' => 19900,
        ];

        $revenue = [];

        // Revenue by type
        $typeQuery = LicenseKey::query()
            ->when($productId, fn($q) => $q->where('product_id', $productId))
            ->where('created_at', '>=', $startDate);

        $revenueByType = [];
        foreach ($prices as $type => $price) {
            $count = (clone $typeQuery)->where('license_type', $type)->count();
            $revenueByType[$type] = [
                'count' => $count,
                'revenue' => $count * $price,
            ];
        }

        // Total estimated revenue
        $totalRevenue = array_sum(array_column($revenueByType, 'revenue'));

        // Revenue over time
        $revenueOverTime = [];
        for ($i = 30; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayRevenue = 0;

            foreach ($prices as $type => $price) {
                $count = LicenseKey::query()
                    ->when($productId, fn($q) => $q->where('product_id', $productId))
                    ->where('license_type', $type)
                    ->whereDate('created_at', $date)
                    ->count();
                $dayRevenue += $count * $price;
            }

            $revenueOverTime[$date] = $dayRevenue;
        }

        return [
            'by_type' => $revenueByType,
            'total' => $totalRevenue,
            'over_time' => $revenueOverTime,
        ];
    }

    /**
     * Get activity data for API (AJAX).
     */
    public function activityData(Request $request)
    {
        $licenseId = $request->get('license_id');
        $limit = $request->get('limit', 50);

        $activities = LicenseActivity::with('user')
            ->when($licenseId, fn($q) => $q->where('license_id', $licenseId))
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'action' => $activity->action,
                    'action_label' => $activity->action_label,
                    'action_color' => $activity->action_color,
                    'action_icon' => $activity->action_icon,
                    'ip_address' => $activity->ip_address,
                    'machine_id' => $activity->machine_id,
                    'user' => $activity->user?->name,
                    'actor_type' => $activity->actor_type,
                    'notes' => $activity->notes,
                    'created_at' => $activity->created_at->diffForHumans(),
                    'created_at_full' => $activity->created_at->format('d/m/Y H:i:s'),
                ];
            });

        return response()->json($activities);
    }
}
