@extends('layouts.admin')

@section('title', 'Analytics Dashboard')
@section('page-title', 'Analytics Dashboard')

@section('content')
<div class="space-y-6">
    {{-- Period Filter --}}
    <div class="bg-white rounded-lg shadow p-4">
        <form action="{{ route('admin.analytics.index') }}" method="GET" class="flex items-center gap-4">
            <label class="text-sm font-medium text-gray-700">ช่วงเวลา:</label>
            <select name="period" onchange="this.form.submit()"
                    class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="7" {{ $period === '7' ? 'selected' : '' }}>7 วัน</option>
                <option value="30" {{ $period === '30' ? 'selected' : '' }}>30 วัน</option>
                <option value="90" {{ $period === '90' ? 'selected' : '' }}>90 วัน</option>
                <option value="365" {{ $period === '365' ? 'selected' : '' }}>1 ปี</option>
                <option value="all" {{ $period === 'all' ? 'selected' : '' }}>ทั้งหมด</option>
            </select>
        </form>
    </div>

    {{-- Overview Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Revenue --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">รายได้รวม</p>
                    <p class="text-2xl font-bold text-gray-900">฿{{ number_format($overview['revenue']) }}</p>
                </div>
                <div class="p-3 rounded-full {{ $overview['revenue_growth'] >= 0 ? 'bg-green-100' : 'bg-red-100' }}">
                    <svg class="w-6 h-6 {{ $overview['revenue_growth'] >= 0 ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($overview['revenue_growth'] >= 0)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"/>
                        @endif
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-sm {{ $overview['revenue_growth'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                {{ $overview['revenue_growth'] >= 0 ? '+' : '' }}{{ $overview['revenue_growth'] }}% จากช่วงก่อน
            </p>
        </div>

        {{-- New Customers --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">ลูกค้าใหม่</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($overview['new_customers']) }}</p>
                </div>
                <div class="p-3 rounded-full bg-blue-100">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-sm {{ $overview['customer_growth'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                {{ $overview['customer_growth'] >= 0 ? '+' : '' }}{{ $overview['customer_growth'] }}% จากช่วงก่อน
            </p>
        </div>

        {{-- Active Subscriptions --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Subscriptions ที่ใช้งาน</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($overview['active_subscriptions']) }}</p>
                </div>
                <div class="p-3 rounded-full bg-purple-100">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Total Orders --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">คำสั่งซื้อ</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($overview['total_orders']) }}</p>
                </div>
                <div class="p-3 rounded-full bg-yellow-100">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-sm {{ $overview['order_growth'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                {{ $overview['order_growth'] >= 0 ? '+' : '' }}{{ $overview['order_growth'] }}% จากช่วงก่อน
            </p>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Revenue Chart --}}
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">รายได้</h3>
            <div class="h-64">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        {{-- Rental Stats --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">สถิติการเช่า</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Subscriptions ที่ใช้งาน</span>
                    <span class="font-bold text-gray-900">{{ $rentalStats['active'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">จะหมดอายุใน 7 วัน</span>
                    <span class="font-bold {{ $rentalStats['expiring_soon'] > 0 ? 'text-orange-600' : 'text-gray-900' }}">{{ $rentalStats['expiring_soon'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Renewal Rate</span>
                    <span class="font-bold text-gray-900">{{ $rentalStats['renewal_rate'] }}%</span>
                </div>
                <hr class="my-2">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">MRR (Monthly Recurring Revenue)</span>
                    <span class="font-bold text-green-600">฿{{ number_format($rentalStats['mrr']) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Second Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Products --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Top แพ็กเกจ</h3>
            @if($topProducts->isNotEmpty())
            <div class="space-y-3">
                @foreach($topProducts as $product)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900">{{ $product['name'] }}</p>
                        <p class="text-sm text-gray-500">{{ $product['count'] }} ครั้ง</p>
                    </div>
                    <p class="font-bold text-green-600">฿{{ number_format($product['revenue']) }}</p>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-500 text-center py-8">ยังไม่มีข้อมูล</p>
            @endif
        </div>

        {{-- Support Tickets --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Support Tickets</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-blue-50 rounded-lg text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ $ticketStats['open'] }}</p>
                    <p class="text-sm text-gray-600">เปิดใหม่</p>
                </div>
                <div class="p-4 bg-yellow-50 rounded-lg text-center">
                    <p class="text-2xl font-bold text-yellow-600">{{ $ticketStats['in_progress'] }}</p>
                    <p class="text-sm text-gray-600">กำลังดำเนินการ</p>
                </div>
                <div class="p-4 bg-green-50 rounded-lg text-center">
                    <p class="text-2xl font-bold text-green-600">{{ $ticketStats['resolved'] }}</p>
                    <p class="text-sm text-gray-600">แก้ไขแล้ว</p>
                </div>
                <div class="p-4 bg-purple-50 rounded-lg text-center">
                    <p class="text-2xl font-bold text-purple-600">{{ $ticketStats['avg_response_hours'] }}h</p>
                    <p class="text-sm text-gray-600">เวลาตอบกลับ (เฉลี่ย)</p>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('admin.support.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                    ดู Tickets ทั้งหมด &rarr;
                </a>
            </div>
        </div>
    </div>

    {{-- Recent Orders --}}
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">คำสั่งซื้อล่าสุด</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">หมายเลข</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ลูกค้า</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ยอดเงิน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recentOrders as $order)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                            {{ $order->order_number ?? '#' . $order->id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $order->user->name ?? 'Guest' }}</div>
                            <div class="text-sm text-gray-500">{{ $order->user->email ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'processing' => 'bg-blue-100 text-blue-800',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            ฿{{ number_format($order->total_amount) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $order->created_at->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">ยังไม่มีคำสั่งซื้อ</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Customer Stats --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">สถิติลูกค้า</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="text-center">
                <p class="text-3xl font-bold text-gray-900">{{ number_format($customerStats['total']) }}</p>
                <p class="text-sm text-gray-500">ลูกค้าทั้งหมด</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold text-green-600">{{ number_format($customerStats['active']) }}</p>
                <p class="text-sm text-gray-500">ลูกค้าที่ใช้งานอยู่</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold text-gray-400">{{ number_format($customerStats['inactive']) }}</p>
                <p class="text-sm text-gray-500">ลูกค้าที่ไม่ได้ใช้งาน</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const chartData = @json($revenueChart);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: chartData.datasets.map(dataset => ({
                ...dataset,
                fill: true,
                tension: 0.3,
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '฿' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
@endsection
