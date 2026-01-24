@extends($customerLayout ?? 'layouts.customer')

@section('title', 'ประวัติคำสั่งซื้อ')
@section('page-title', 'ประวัติคำสั่งซื้อ')
@section('page-description', 'ดูประวัติการสั่งซื้อทั้งหมดของคุณ')

@section('content')
<!-- Premium Header Banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 p-6 sm:p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-blob"></div>
    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-orange-300/20 rounded-full blur-3xl animate-blob animation-delay-2000"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                ประวัติคำสั่งซื้อ
            </h1>
            <p class="mt-2 text-white/80 text-sm sm:text-base">ดูและติดตามคำสั่งซื้อทั้งหมดของคุณ</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-xl text-white text-sm font-medium">
                <span class="opacity-80">รวม</span>
                <span class="ml-1 text-lg font-bold">{{ $stats['total_orders'] }}</span>
                <span class="opacity-80 ml-1">คำสั่งซื้อ</span>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards with Gradients -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-8">
    <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl p-5 sm:p-6 transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">คำสั่งซื้อทั้งหมด</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['total_orders'] }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl shadow-lg group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl p-5 sm:p-6 transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">สำเร็จ</p>
                <p class="text-2xl sm:text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $stats['completed'] }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-emerald-400 to-green-600 rounded-xl shadow-lg group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl p-5 sm:p-6 transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">ยอดใช้จ่ายรวม</p>
                <p class="text-2xl sm:text-3xl font-bold text-orange-600 dark:text-orange-400 mt-1">฿{{ number_format($stats['total_spent']) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-orange-400 to-red-500 rounded-xl shadow-lg group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Premium Filter -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4 mb-6 border border-gray-100 dark:border-gray-700">
    <form action="" method="GET" class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4">
        <div class="flex-1 sm:flex-none">
            <select name="status" class="w-full sm:w-auto rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-orange-500 focus:border-orange-500 shadow-sm">
                <option value="all">สถานะทั้งหมด</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-xl hover:from-orange-600 hover:to-red-600 text-sm font-medium transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    กรอง
                </span>
            </button>
            @if(request()->hasAny(['status']))
            <a href="{{ route('customer.orders') }}" class="px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 text-sm font-medium transition-all">
                ล้าง
            </a>
            @endif
        </div>
    </form>
</div>

<!-- Order List -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
    <!-- Desktop Table -->
    <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-750">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">เลขที่คำสั่งซื้อ</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">วันที่</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">รายการ</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ยอดรวม</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider"></th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($orders as $order)
                @php
                    $statusGradients = [
                        'completed' => 'from-emerald-400 to-green-500',
                        'pending' => 'from-yellow-400 to-orange-400',
                        'processing' => 'from-blue-400 to-indigo-500',
                        'cancelled' => 'from-red-400 to-rose-500',
                    ];
                    $statusLabels = [
                        'completed' => 'สำเร็จ',
                        'pending' => 'รอดำเนินการ',
                        'processing' => 'กำลังดำเนินการ',
                        'cancelled' => 'ยกเลิก',
                    ];
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="p-2 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl shadow-lg mr-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <span class="font-semibold text-gray-900 dark:text-white">#{{ $order->order_number ?? $order->id }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900 dark:text-white">{{ $order->created_at->format('d/m/Y') }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $order->created_at->format('H:i') }} น.</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900 dark:text-white">
                            @if($order->items->count() > 0)
                                <span class="font-medium">{{ $order->items->first()->product?->name ?? 'Product' }}</span>
                                @if($order->items->count() > 1)
                                    <span class="text-gray-500 dark:text-gray-400">และอีก {{ $order->items->count() - 1 }} รายการ</span>
                                @endif
                            @else
                                <span class="text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-bold text-lg bg-gradient-to-r from-orange-500 to-red-500 bg-clip-text text-transparent">฿{{ number_format($order->total ?? 0) }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1.5 text-xs font-semibold rounded-full bg-gradient-to-r {{ $statusGradients[$order->status] ?? 'from-gray-400 to-gray-500' }} text-white shadow-sm">
                            {{ $statusLabels[$order->status] ?? ucfirst($order->status ?? 'pending') }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('customer.orders.show', $order) }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-xl hover:from-orange-600 hover:to-red-600 transition-all font-medium shadow-lg hover:shadow-xl transform hover:scale-105">
                            ดูรายละเอียด
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-amber-400 to-orange-500 rounded-full mb-4 shadow-xl">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">ยังไม่มีคำสั่งซื้อ</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ประวัติคำสั่งซื้อของคุณจะปรากฏที่นี่</p>
                        <a href="{{ route('products.index') }}" class="mt-6 inline-flex items-center px-6 py-3 bg-gradient-to-r from-orange-500 to-red-500 text-white text-sm font-medium rounded-xl hover:from-orange-600 hover:to-red-600 transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            ดูผลิตภัณฑ์
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile Cards -->
    <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-700">
        @forelse($orders as $order)
        @php
            $statusGradients = [
                'completed' => 'from-emerald-400 to-green-500',
                'pending' => 'from-yellow-400 to-orange-400',
                'processing' => 'from-blue-400 to-indigo-500',
                'cancelled' => 'from-red-400 to-rose-500',
            ];
            $statusLabels = [
                'completed' => 'สำเร็จ',
                'pending' => 'รอดำเนินการ',
                'processing' => 'กำลังดำเนินการ',
                'cancelled' => 'ยกเลิก',
            ];
        @endphp
        <a href="{{ route('customer.orders.show', $order) }}" class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-gray-900 dark:text-white">#{{ $order->order_number ?? $order->id }}</span>
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gradient-to-r {{ $statusGradients[$order->status] ?? 'from-gray-400 to-gray-500' }} text-white shadow-sm">
                            {{ $statusLabels[$order->status] ?? ucfirst($order->status ?? 'pending') }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ $order->created_at->format('d/m/Y H:i') }} น.
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                        @if($order->items->count() > 0)
                            {{ $order->items->first()->product?->name ?? 'Product' }}
                            @if($order->items->count() > 1)
                                <span class="text-gray-400">+{{ $order->items->count() - 1 }}</span>
                            @endif
                        @endif
                    </p>
                    <p class="text-lg font-bold bg-gradient-to-r from-orange-500 to-red-500 bg-clip-text text-transparent mt-2">฿{{ number_format($order->total ?? 0) }}</p>
                </div>
                <svg class="w-5 h-5 text-gray-400 flex-shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>
        @empty
        <div class="p-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-amber-400 to-orange-500 rounded-full mb-4 shadow-xl">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <p class="text-lg font-medium text-gray-900 dark:text-white">ยังไม่มีคำสั่งซื้อ</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ประวัติคำสั่งซื้อของคุณจะปรากฏที่นี่</p>
            <a href="{{ route('products.index') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-gradient-to-r from-orange-500 to-red-500 text-white text-sm font-medium rounded-xl hover:from-orange-600 hover:to-red-600 transition-all shadow-lg">
                ดูผลิตภัณฑ์
            </a>
        </div>
        @endforelse
    </div>

    @if($orders->hasPages())
    <div class="px-4 sm:px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
        {{ $orders->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
