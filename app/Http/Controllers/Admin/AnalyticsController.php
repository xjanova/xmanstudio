<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\RentalPayment;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\UserRental;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Display the analytics dashboard
     */
    public function index(Request $request)
    {
        $period = $request->get('period', '30'); // Default 30 days

        $startDate = match($period) {
            '7' => now()->subDays(7),
            '30' => now()->subDays(30),
            '90' => now()->subDays(90),
            '365' => now()->subDays(365),
            'all' => Carbon::create(2020, 1, 1),
            default => now()->subDays(30),
        };

        return view('admin.analytics.index', [
            'period' => $period,
            'overview' => $this->getOverviewStats($startDate),
            'revenueChart' => $this->getRevenueChartData($startDate),
            'topProducts' => $this->getTopProducts($startDate),
            'recentOrders' => $this->getRecentOrders(),
            'customerStats' => $this->getCustomerStats($startDate),
            'ticketStats' => $this->getTicketStats($startDate),
            'rentalStats' => $this->getRentalStats($startDate),
        ]);
    }

    /**
     * Get overview statistics
     */
    private function getOverviewStats(Carbon $startDate): array
    {
        // Revenue from payments
        $revenue = RentalPayment::where('status', 'approved')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        // Order revenue
        $orderRevenue = Order::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->sum('total_amount');

        $totalRevenue = $revenue + $orderRevenue;

        // Previous period for comparison
        $periodDays = now()->diffInDays($startDate);
        $previousStart = $startDate->copy()->subDays($periodDays);

        $previousRevenue = RentalPayment::where('status', 'approved')
            ->whereBetween('created_at', [$previousStart, $startDate])
            ->sum('amount');
        $previousOrderRevenue = Order::where('status', 'completed')
            ->whereBetween('created_at', [$previousStart, $startDate])
            ->sum('total_amount');
        $previousTotalRevenue = $previousRevenue + $previousOrderRevenue;

        $revenueGrowth = $previousTotalRevenue > 0
            ? (($totalRevenue - $previousTotalRevenue) / $previousTotalRevenue) * 100
            : 0;

        // New customers
        $newCustomers = User::where('role', 'user')
            ->where('created_at', '>=', $startDate)
            ->count();

        $previousCustomers = User::where('role', 'user')
            ->whereBetween('created_at', [$previousStart, $startDate])
            ->count();

        $customerGrowth = $previousCustomers > 0
            ? (($newCustomers - $previousCustomers) / $previousCustomers) * 100
            : 0;

        // Active subscriptions
        $activeSubscriptions = UserRental::where('status', 'active')
            ->where('end_date', '>', now())
            ->count();

        // Total orders
        $totalOrders = Order::where('created_at', '>=', $startDate)->count();
        $previousOrders = Order::whereBetween('created_at', [$previousStart, $startDate])->count();
        $orderGrowth = $previousOrders > 0
            ? (($totalOrders - $previousOrders) / $previousOrders) * 100
            : 0;

        return [
            'revenue' => $totalRevenue,
            'revenue_growth' => round($revenueGrowth, 1),
            'new_customers' => $newCustomers,
            'customer_growth' => round($customerGrowth, 1),
            'active_subscriptions' => $activeSubscriptions,
            'total_orders' => $totalOrders,
            'order_growth' => round($orderGrowth, 1),
        ];
    }

    /**
     * Get revenue chart data
     */
    private function getRevenueChartData(Carbon $startDate): array
    {
        $periodDays = now()->diffInDays($startDate);

        // Determine grouping based on period
        if ($periodDays <= 30) {
            $format = 'Y-m-d';
            $labelFormat = 'd/m';
        } elseif ($periodDays <= 90) {
            $format = 'Y-W';
            $labelFormat = 'W';
        } else {
            $format = 'Y-m';
            $labelFormat = 'm/Y';
        }

        // Rental payments
        $rentalRevenue = RentalPayment::where('status', 'approved')
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw("DATE_FORMAT(created_at, '{$format}') as period"), DB::raw('SUM(amount) as total'))
            ->groupBy('period')
            ->pluck('total', 'period')
            ->toArray();

        // Order revenue
        $orderRevenue = Order::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw("DATE_FORMAT(created_at, '{$format}') as period"), DB::raw('SUM(total_amount) as total'))
            ->groupBy('period')
            ->pluck('total', 'period')
            ->toArray();

        // Merge and create chart data
        $labels = [];
        $rentalData = [];
        $orderData = [];

        $current = $startDate->copy();
        while ($current <= now()) {
            $key = $current->format($format);
            $labels[] = $current->format($labelFormat);
            $rentalData[] = $rentalRevenue[$key] ?? 0;
            $orderData[] = $orderRevenue[$key] ?? 0;

            if ($periodDays <= 30) {
                $current->addDay();
            } elseif ($periodDays <= 90) {
                $current->addWeek();
            } else {
                $current->addMonth();
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Rental Revenue',
                    'data' => $rentalData,
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
                [
                    'label' => 'Order Revenue',
                    'data' => $orderData,
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
            ],
        ];
    }

    /**
     * Get top products
     */
    private function getTopProducts(Carbon $startDate): \Illuminate\Support\Collection
    {
        // Top rental packages
        $topPackages = UserRental::where('created_at', '>=', $startDate)
            ->select('package_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_price) as revenue'))
            ->groupBy('package_id')
            ->with('package')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->package->name ?? 'Unknown Package',
                    'type' => 'rental',
                    'count' => $item->count,
                    'revenue' => $item->revenue,
                ];
            });

        return $topPackages;
    }

    /**
     * Get recent orders
     */
    private function getRecentOrders(): \Illuminate\Support\Collection
    {
        return Order::with('user')
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * Get customer statistics
     */
    private function getCustomerStats(Carbon $startDate): array
    {
        $totalCustomers = User::where('role', 'user')->count();
        $activeCustomers = User::where('role', 'user')
            ->whereHas('rentals', function ($q) {
                $q->where('status', 'active');
            })
            ->count();

        // Customer signups over time
        $signupsByDay = User::where('role', 'user')
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        return [
            'total' => $totalCustomers,
            'active' => $activeCustomers,
            'inactive' => $totalCustomers - $activeCustomers,
            'signups' => $signupsByDay,
        ];
    }

    /**
     * Get ticket statistics
     */
    private function getTicketStats(Carbon $startDate): array
    {
        $openTickets = SupportTicket::where('status', SupportTicket::STATUS_OPEN)->count();
        $inProgress = SupportTicket::whereIn('status', [
            SupportTicket::STATUS_IN_PROGRESS,
            SupportTicket::STATUS_WAITING_REPLY
        ])->count();
        $resolved = SupportTicket::where('status', SupportTicket::STATUS_RESOLVED)
            ->where('created_at', '>=', $startDate)
            ->count();

        // Average response time (in hours)
        $avgResponseTime = SupportTicket::whereNotNull('responded_at')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, responded_at)) as avg_hours')
            ->value('avg_hours');

        return [
            'open' => $openTickets,
            'in_progress' => $inProgress,
            'resolved' => $resolved,
            'avg_response_hours' => round($avgResponseTime ?? 0, 1),
        ];
    }

    /**
     * Get rental statistics
     */
    private function getRentalStats(Carbon $startDate): array
    {
        $activeRentals = UserRental::where('status', 'active')
            ->where('end_date', '>', now())
            ->count();

        $expiringThisWeek = UserRental::where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays(7)])
            ->count();

        $renewalRate = 0;
        $totalExpired = UserRental::where('end_date', '<=', now())
            ->where('created_at', '>=', $startDate)
            ->count();
        $renewedCount = UserRental::where('end_date', '<=', now())
            ->where('created_at', '>=', $startDate)
            ->whereHas('user', function ($q) {
                $q->whereHas('rentals', function ($q2) {
                    $q2->where('status', 'active');
                });
            })
            ->count();
        if ($totalExpired > 0) {
            $renewalRate = ($renewedCount / $totalExpired) * 100;
        }

        // Monthly Recurring Revenue (MRR)
        $mrr = UserRental::where('status', 'active')
            ->where('end_date', '>', now())
            ->with('package')
            ->get()
            ->sum(function ($rental) {
                $months = max(1, $rental->package->duration_months ?? 1);
                return $rental->total_price / $months;
            });

        return [
            'active' => $activeRentals,
            'expiring_soon' => $expiringThisWeek,
            'renewal_rate' => round($renewalRate, 1),
            'mrr' => $mrr,
        ];
    }
}
