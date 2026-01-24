@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Analytics Dashboard')
@section('page-title', 'Analytics Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Premium Header Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 p-6 sm:p-8 shadow-xl">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/5 rounded-full blur-3xl animate-blob"></div>
        <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-emerald-400/10 rounded-full blur-3xl animate-blob animation-delay-2000"></div>
        <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
                    <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    Analytics Dashboard
                </h1>
                <p class="mt-2 text-white/80 text-sm sm:text-base">ภาพรวมประสิทธิภาพและสถิติของระบบ</p>
            </div>
            <form action="{{ route('admin.analytics.index') }}" method="GET" class="flex items-center gap-3">
                <label class="text-sm font-medium text-white/80">ช่วงเวลา:</label>
                <select name="period" onchange="this.form.submit()"
                        class="px-4 py-2 rounded-xl border-0 bg-white/20 backdrop-blur-sm text-white shadow-sm focus:ring-2 focus:ring-white/50">
                    <option value="7" {{ $period === '7' ? 'selected' : '' }} class="text-gray-900">7 วัน</option>
                    <option value="30" {{ $period === '30' ? 'selected' : '' }} class="text-gray-900">30 วัน</option>
                    <option value="90" {{ $period === '90' ? 'selected' : '' }} class="text-gray-900">90 วัน</option>
                    <option value="365" {{ $period === '365' ? 'selected' : '' }} class="text-gray-900">1 ปี</option>
                    <option value="all" {{ $period === 'all' ? 'selected' : '' }} class="text-gray-900">ทั้งหมด</option>
                </select>
            </form>
        </div>
    </div>

    <!-- Premium Overview Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Revenue -->
        <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="absolute inset-0 bg-gradient-to-br from-green-400/10 to-emerald-600/10"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br {{ $overview['revenue_growth'] >= 0 ? 'from-green-400 to-emerald-600' : 'from-red-400 to-rose-600' }} flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($overview['revenue_growth'] >= 0)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"/>
                            @endif
                        </svg>
                    </div>
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold {{ $overview['revenue_growth'] >= 0 ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300' }}">
                        {{ $overview['revenue_growth'] >= 0 ? '+' : '' }}{{ $overview['revenue_growth'] }}%
                    </span>
                </div>
                <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">รายได้รวม</h3>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">฿{{ number_format($overview['revenue']) }}</p>
            </div>
        </div>

        <!-- New Customers -->
        <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-400/10 to-indigo-600/10"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold {{ $overview['customer_growth'] >= 0 ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300' }}">
                        {{ $overview['customer_growth'] >= 0 ? '+' : '' }}{{ $overview['customer_growth'] }}%
                    </span>
                </div>
                <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">ลูกค้าใหม่</h3>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($overview['new_customers']) }}</p>
            </div>
        </div>

        <!-- Active Subscriptions -->
        <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="absolute inset-0 bg-gradient-to-br from-purple-400/10 to-violet-600/10"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-purple-400 to-violet-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">Subscriptions ที่ใช้งาน</h3>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($overview['active_subscriptions']) }}</p>
            </div>
        </div>

        <!-- Total Orders -->
        <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="absolute inset-0 bg-gradient-to-br from-amber-400/10 to-orange-600/10"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-400 to-orange-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold {{ $overview['order_growth'] >= 0 ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300' }}">
                        {{ $overview['order_growth'] >= 0 ? '+' : '' }}{{ $overview['order_growth'] }}%
                    </span>
                </div>
                <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">คำสั่งซื้อ</h3>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($overview['total_orders']) }}</p>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Revenue Chart -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center mr-3 shadow">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                รายได้
            </h3>
            <div class="h-64">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Rental Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-400 to-violet-600 flex items-center justify-center mr-3 shadow">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                สถิติการเช่า
            </h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <span class="text-gray-600 dark:text-gray-400">Subscriptions ที่ใช้งาน</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ $rentalStats['active'] }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <span class="text-gray-600 dark:text-gray-400">จะหมดอายุใน 7 วัน</span>
                    <span class="font-bold {{ $rentalStats['expiring_soon'] > 0 ? 'text-orange-500' : 'text-gray-900 dark:text-white' }}">{{ $rentalStats['expiring_soon'] }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <span class="text-gray-600 dark:text-gray-400">Renewal Rate</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ $rentalStats['renewal_rate'] }}%</span>
                </div>
                <hr class="border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl border border-green-200 dark:border-green-700">
                    <span class="text-gray-700 dark:text-gray-300 font-medium">MRR</span>
                    <span class="font-bold text-lg text-green-600 dark:text-green-400">฿{{ number_format($rentalStats['mrr']) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Products -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center mr-3 shadow">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                Top แพ็กเกจ
            </h3>
            @if($topProducts->isNotEmpty())
            <div class="space-y-3">
                @foreach($topProducts as $product)
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center mr-3 shadow">
                            <span class="text-white font-bold text-sm">{{ $loop->iteration }}</span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $product['name'] }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $product['count'] }} ครั้ง</p>
                        </div>
                    </div>
                    <span class="font-bold text-green-600 dark:text-green-400">฿{{ number_format($product['revenue']) }}</span>
                </div>
                @endforeach
            </div>
            @else
            <div class="py-8 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-gray-400 to-slate-600 rounded-full mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <p class="text-gray-500 dark:text-gray-400">ยังไม่มีข้อมูล</p>
            </div>
            @endif
        </div>

        <!-- Support Tickets -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-rose-400 to-pink-600 flex items-center justify-center mr-3 shadow">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                Support Tickets
            </h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl text-center border border-blue-200 dark:border-blue-700">
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $ticketStats['open'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">เปิดใหม่</p>
                </div>
                <div class="p-4 bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 rounded-xl text-center border border-amber-200 dark:border-amber-700">
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $ticketStats['in_progress'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">กำลังดำเนินการ</p>
                </div>
                <div class="p-4 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl text-center border border-green-200 dark:border-green-700">
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $ticketStats['resolved'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">แก้ไขแล้ว</p>
                </div>
                <div class="p-4 bg-gradient-to-br from-purple-50 to-violet-50 dark:from-purple-900/20 dark:to-violet-900/20 rounded-xl text-center border border-purple-200 dark:border-purple-700">
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $ticketStats['avg_response_hours'] }}h</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">เวลาตอบกลับ (เฉลี่ย)</p>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('admin.support.index') }}" class="inline-flex items-center text-rose-600 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300 text-sm font-medium transition">
                    ดู Tickets ทั้งหมด
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-400 to-orange-600 flex items-center justify-center mr-3 shadow">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                คำสั่งซื้อล่าสุด
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-750">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">หมายเลข</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ลูกค้า</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ยอดเงิน</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">วันที่</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recentOrders as $order)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900 dark:text-white">
                            {{ $order->order_number ?? '#' . $order->id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-400 to-orange-600 flex items-center justify-center mr-3 shadow">
                                    <span class="text-white font-bold text-xs">{{ strtoupper(substr($order->user->name ?? 'G', 0, 1)) }}</span>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $order->user->name ?? 'Guest' }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $order->user->email ?? '-' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusGradients = [
                                    'pending' => 'bg-gradient-to-r from-amber-400 to-yellow-500 text-white',
                                    'processing' => 'bg-gradient-to-r from-blue-400 to-indigo-500 text-white',
                                    'completed' => 'bg-gradient-to-r from-green-400 to-emerald-500 text-white',
                                    'cancelled' => 'bg-gradient-to-r from-red-400 to-rose-500 text-white',
                                ];
                            @endphp
                            <span class="inline-flex px-3 py-1.5 rounded-full text-xs font-semibold shadow-sm {{ $statusGradients[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                            ฿{{ number_format($order->total_amount) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $order->created_at->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-gray-400 to-slate-600 rounded-full mb-4 shadow-lg">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">ยังไม่มีคำสั่งซื้อ</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Customer Stats -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-400 to-teal-600 flex items-center justify-center mr-3 shadow">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            สถิติลูกค้า
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="text-center p-6 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl border border-blue-200 dark:border-blue-700">
                <p class="text-4xl font-bold text-gray-900 dark:text-white">{{ number_format($customerStats['total']) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">ลูกค้าทั้งหมด</p>
            </div>
            <div class="text-center p-6 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl border border-green-200 dark:border-green-700">
                <p class="text-4xl font-bold text-green-600 dark:text-green-400">{{ number_format($customerStats['active']) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">ลูกค้าที่ใช้งานอยู่</p>
            </div>
            <div class="text-center p-6 bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-700/50 dark:to-slate-700/50 rounded-2xl border border-gray-200 dark:border-gray-600">
                <p class="text-4xl font-bold text-gray-400 dark:text-gray-500">{{ number_format($customerStats['inactive']) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">ลูกค้าที่ไม่ได้ใช้งาน</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<style>
@keyframes blob {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
}
.animate-blob { animation: blob 7s infinite; }
.animation-delay-2000 { animation-delay: 2s; }
</style>
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
                tension: 0.4,
                borderWidth: 3,
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
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '฿' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
});
</script>
@endpush
@endsection
