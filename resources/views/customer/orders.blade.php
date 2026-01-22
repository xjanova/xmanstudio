@extends('layouts.customer')

@section('title', 'ประวัติคำสั่งซื้อ')
@section('page-title', 'ประวัติคำสั่งซื้อ')
@section('page-description', 'ดูประวัติการสั่งซื้อทั้งหมดของคุณ')

@section('content')
<!-- Stats -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-5 sm:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">คำสั่งซื้อทั้งหมด</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1">{{ $stats['total_orders'] }}</p>
            </div>
            <div class="p-3 bg-gray-100 rounded-xl">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5 sm:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">สำเร็จ</p>
                <p class="text-2xl sm:text-3xl font-bold text-green-600 mt-1">{{ $stats['completed'] }}</p>
            </div>
            <div class="p-3 bg-green-100 rounded-xl">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5 sm:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">ยอดใช้จ่ายรวม</p>
                <p class="text-2xl sm:text-3xl font-bold text-primary-600 mt-1">฿{{ number_format($stats['total_spent']) }}</p>
            </div>
            <div class="p-3 bg-primary-100 rounded-xl">
                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form action="" method="GET" class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4">
        <div class="flex-1 sm:flex-none">
            <select name="status" class="w-full sm:w-auto rounded-lg border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                <option value="all">สถานะทั้งหมด</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm font-medium transition-colors">
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    กรอง
                </span>
            </button>
            @if(request()->hasAny(['status']))
            <a href="{{ route('customer.orders') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition-colors">
                ล้าง
            </a>
            @endif
        </div>
    </form>
</div>

<!-- Order List -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <!-- Desktop Table -->
    <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">เลขที่คำสั่งซื้อ</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">วันที่</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">รายการ</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ยอดรวม</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">สถานะ</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($orders as $order)
                @php
                    $statusColors = [
                        'completed' => 'bg-green-100 text-green-700',
                        'pending' => 'bg-yellow-100 text-yellow-700',
                        'processing' => 'bg-blue-100 text-blue-700',
                        'cancelled' => 'bg-red-100 text-red-700',
                    ];
                    $statusLabels = [
                        'completed' => 'สำเร็จ',
                        'pending' => 'รอดำเนินการ',
                        'processing' => 'กำลังดำเนินการ',
                        'cancelled' => 'ยกเลิก',
                    ];
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-semibold text-gray-900">#{{ $order->order_number ?? $order->id }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $order->created_at->format('d/m/Y') }}</div>
                        <div class="text-xs text-gray-500">{{ $order->created_at->format('H:i') }} น.</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                            @if($order->items->count() > 0)
                                <span class="font-medium">{{ $order->items->first()->product?->name ?? 'Product' }}</span>
                                @if($order->items->count() > 1)
                                    <span class="text-gray-500">และอีก {{ $order->items->count() - 1 }} รายการ</span>
                                @endif
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-semibold text-gray-900">฿{{ number_format($order->total ?? 0) }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $statusLabels[$order->status] ?? ucfirst($order->status ?? 'pending') }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('customer.orders.show', $order) }}" class="inline-flex items-center px-3 py-1.5 text-primary-600 hover:text-primary-700 hover:bg-primary-50 rounded-lg transition-colors font-medium">
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
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <p class="text-lg font-medium text-gray-900">ยังไม่มีคำสั่งซื้อ</p>
                        <p class="text-sm text-gray-500 mt-1">ประวัติคำสั่งซื้อของคุณจะปรากฏที่นี่</p>
                        <a href="{{ route('products.index') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
    <div class="md:hidden divide-y divide-gray-200">
        @forelse($orders as $order)
        @php
            $statusColors = [
                'completed' => 'bg-green-100 text-green-700',
                'pending' => 'bg-yellow-100 text-yellow-700',
                'processing' => 'bg-blue-100 text-blue-700',
                'cancelled' => 'bg-red-100 text-red-700',
            ];
            $statusLabels = [
                'completed' => 'สำเร็จ',
                'pending' => 'รอดำเนินการ',
                'processing' => 'กำลังดำเนินการ',
                'cancelled' => 'ยกเลิก',
            ];
        @endphp
        <a href="{{ route('customer.orders.show', $order) }}" class="block p-4 hover:bg-gray-50 transition-colors">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-gray-900">#{{ $order->order_number ?? $order->id }}</span>
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $statusLabels[$order->status] ?? ucfirst($order->status ?? 'pending') }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $order->created_at->format('d/m/Y H:i') }} น.
                    </p>
                    <p class="text-sm text-gray-600 mt-1">
                        @if($order->items->count() > 0)
                            {{ $order->items->first()->product?->name ?? 'Product' }}
                            @if($order->items->count() > 1)
                                <span class="text-gray-400">+{{ $order->items->count() - 1 }}</span>
                            @endif
                        @endif
                    </p>
                    <p class="text-lg font-semibold text-gray-900 mt-2">฿{{ number_format($order->total ?? 0) }}</p>
                </div>
                <svg class="w-5 h-5 text-gray-400 flex-shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>
        @empty
        <div class="p-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <p class="text-lg font-medium text-gray-900">ยังไม่มีคำสั่งซื้อ</p>
            <p class="text-sm text-gray-500 mt-1">ประวัติคำสั่งซื้อของคุณจะปรากฏที่นี่</p>
            <a href="{{ route('products.index') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                ดูผลิตภัณฑ์
            </a>
        </div>
        @endforelse
    </div>

    @if($orders->hasPages())
    <div class="px-4 sm:px-6 py-4 border-t border-gray-200 bg-gray-50">
        {{ $orders->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
