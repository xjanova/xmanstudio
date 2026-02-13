@extends($adminLayout ?? 'layouts.admin')

@section('title', 'จัดการคำสั่งซื้อ')
@section('page-title', 'จัดการคำสั่งซื้อ')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-500 p-8 shadow-2xl">
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-white">จัดการคำสั่งซื้อ</h1>
                </div>
                <p class="text-blue-100 text-lg">ดูและจัดการคำสั่งซื้อจากหน้าเว็บไซต์ทั้งหมด</p>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('admin.orders.index') }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg transition {{ !request('payment_status') ? 'ring-2 ring-blue-500' : '' }}">
            <p class="text-sm text-gray-500 dark:text-gray-400">ทั้งหมด</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $counts['all'] }}</p>
        </a>
        <a href="{{ route('admin.orders.index', ['payment_status' => 'pending']) }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg transition {{ request('payment_status') === 'pending' ? 'ring-2 ring-yellow-500' : '' }}">
            <p class="text-sm text-gray-500 dark:text-gray-400">รอชำระเงิน</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $counts['pending'] }}</p>
        </a>
        <a href="{{ route('admin.orders.index', ['payment_status' => 'verifying']) }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg transition {{ request('payment_status') === 'verifying' ? 'ring-2 ring-amber-500' : '' }}">
            <p class="text-sm text-gray-500 dark:text-gray-400">รอตรวจสลิป</p>
            <p class="text-2xl font-bold text-amber-600">{{ $counts['verifying'] }}</p>
        </a>
        <a href="{{ route('admin.orders.index', ['payment_status' => 'paid']) }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg transition {{ request('payment_status') === 'paid' ? 'ring-2 ring-green-500' : '' }}">
            <p class="text-sm text-gray-500 dark:text-gray-400">ชำระแล้ว</p>
            <p class="text-2xl font-bold text-green-600">{{ $counts['paid'] }}</p>
        </a>
    </div>

    <!-- Search & Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="เลขที่, ชื่อ, อีเมล..."
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">สถานะ</label>
                <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>เสร็จสมบูรณ์</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">การชำระเงิน</label>
                <select name="payment_status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>รอชำระ</option>
                    <option value="verifying" {{ request('payment_status') === 'verifying' ? 'selected' : '' }}>รอตรวจสลิป</option>
                    <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>ชำระแล้ว</option>
                    <option value="expired" {{ request('payment_status') === 'expired' ? 'selected' : '' }}>หมดอายุ</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">ช่องทาง</label>
                <select name="payment_method" class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                    <option value="">ทั้งหมด</option>
                    <option value="promptpay" {{ request('payment_method') === 'promptpay' ? 'selected' : '' }}>PromptPay</option>
                    <option value="bank_transfer" {{ request('payment_method') === 'bank_transfer' ? 'selected' : '' }}>โอนเงิน</option>
                    <option value="wallet" {{ request('payment_method') === 'wallet' ? 'selected' : '' }}>Wallet</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                ค้นหา
            </button>
            @if(request()->hasAny(['search', 'status', 'payment_status', 'payment_method']))
                <a href="{{ route('admin.orders.index') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium hover:bg-gray-300 transition">
                    ล้าง
                </a>
            @endif
        </form>
    </div>

    <!-- Orders Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">เลขที่</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">ลูกค้า</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">สินค้า</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">ยอดรวม</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">ช่องทาง</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">การชำระ</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">วันที่</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($orders as $order)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-mono text-sm font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                                {{ $order->order_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $order->customer_name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $order->customer_email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900 dark:text-white">
                                @foreach($order->items->take(2) as $item)
                                    <div>{{ Str::limit($item->product->name ?? $item->product_name ?? '-', 30) }}</div>
                                @endforeach
                                @if($order->items->count() > 2)
                                    <div class="text-xs text-gray-400">+{{ $order->items->count() - 2 }} รายการ</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                            ฿{{ number_format($order->total, 2) }}
                        </td>
                        <td class="px-4 py-3">
                            @switch($order->payment_method)
                                @case('promptpay')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">PromptPay</span>
                                    @break
                                @case('bank_transfer')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300">โอนเงิน</span>
                                    @break
                                @case('wallet')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">Wallet</span>
                                    @break
                                @default
                                    <span class="text-xs text-gray-500">{{ $order->payment_method }}</span>
                            @endswitch
                        </td>
                        <td class="px-4 py-3">
                            @switch($order->payment_status)
                                @case('pending')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300">รอชำระ</span>
                                    @break
                                @case('verifying')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 animate-pulse">รอตรวจสลิป</span>
                                    @break
                                @case('paid')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">ชำระแล้ว</span>
                                    @break
                                @case('expired')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300">หมดอายุ</span>
                                    @break
                                @default
                                    <span class="text-xs text-gray-500">{{ $order->payment_status }}</span>
                            @endswitch
                        </td>
                        <td class="px-4 py-3">
                            @switch($order->status)
                                @case('pending')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300">รอดำเนินการ</span>
                                    @break
                                @case('processing')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">กำลังดำเนินการ</span>
                                    @break
                                @case('completed')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">เสร็จสมบูรณ์</span>
                                    @break
                                @case('cancelled')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300">ยกเลิก</span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            {{ $order->created_at->format('d/m/Y') }}<br>
                            {{ $order->created_at->format('H:i') }}
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.orders.show', $order) }}" class="px-3 py-1.5 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 transition">
                                ดูรายละเอียด
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400 font-medium">ไม่พบคำสั่งซื้อ</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
