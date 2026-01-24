@extends($publicLayout ?? 'layouts.app')

@section('title', 'คำสั่งซื้อของฉัน - XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-slate-900 dark:to-indigo-950 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                คำสั่งซื้อของฉัน
            </h1>
            <p class="mt-2 text-gray-500 dark:text-gray-400">ประวัติการสั่งซื้อและสถานะคำสั่งซื้อทั้งหมด</p>
        </div>

        @if($orders->isEmpty())
            <div class="text-center py-16 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700">
                <div class="w-20 h-20 mx-auto bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">ยังไม่มีคำสั่งซื้อ</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">เริ่มต้นช้อปปิ้งเลย!</p>
                <a href="{{ route('products.index') }}"
                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 font-semibold transition shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    เลือกซื้อสินค้า
                </a>
            </div>
        @else
            <!-- Desktop Table View -->
            <div class="hidden md:block bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-xl rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-700">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        รายการคำสั่งซื้อ
                        <span class="ml-2 px-2 py-0.5 text-xs bg-white/20 rounded-full">{{ $orders->total() }} รายการ</span>
                    </h2>
                </div>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">เลขที่</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">วันที่</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ชำระผ่าน</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ยอดรวม</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($orders as $order)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-mono font-medium text-gray-900 dark:text-white">{{ $order->order_number }}</span>
                                    @if($order->coupon_code)
                                        <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300" title="ใช้คูปอง">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                            </svg>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                    {{ $order->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($order->payment_method === 'wallet')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                            </svg>
                                            Wallet
                                        </span>
                                    @elseif($order->payment_method === 'promptpay')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300">
                                            PromptPay
                                        </span>
                                    @elseif($order->payment_method === 'bank_transfer')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                            โอนเงิน
                                        </span>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400 text-sm">{{ $order->payment_method ?? '-' }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-semibold text-gray-900 dark:text-white">฿{{ number_format($order->total, 2) }}</span>
                                    @if($order->discount > 0)
                                        <span class="block text-xs text-green-600 dark:text-green-400">-฿{{ number_format($order->discount, 2) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full
                                        @if($order->status === 'completed') bg-gradient-to-r from-green-500 to-emerald-500 text-white
                                        @elseif($order->status === 'processing') bg-gradient-to-r from-blue-500 to-indigo-500 text-white
                                        @elseif($order->status === 'pending') bg-gradient-to-r from-yellow-500 to-amber-500 text-white
                                        @elseif($order->status === 'cancelled') bg-gradient-to-r from-red-500 to-rose-500 text-white
                                        @else bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 @endif">
                                        @switch($order->status)
                                            @case('pending') รอชำระเงิน @break
                                            @case('processing') กำลังดำเนินการ @break
                                            @case('completed') เสร็จสมบูรณ์ @break
                                            @case('cancelled') ยกเลิก @break
                                            @default {{ $order->status }}
                                        @endswitch
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <a href="{{ route('orders.show', $order) }}"
                                       class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition">
                                        ดูรายละเอียด
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="md:hidden space-y-4">
                @foreach($orders as $order)
                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="font-mono font-medium text-gray-900 dark:text-white">{{ $order->order_number }}</span>
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full
                                    @if($order->status === 'completed') bg-gradient-to-r from-green-500 to-emerald-500 text-white
                                    @elseif($order->status === 'processing') bg-gradient-to-r from-blue-500 to-indigo-500 text-white
                                    @elseif($order->status === 'pending') bg-gradient-to-r from-yellow-500 to-amber-500 text-white
                                    @elseif($order->status === 'cancelled') bg-gradient-to-r from-red-500 to-rose-500 text-white
                                    @else bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 @endif">
                                    @switch($order->status)
                                        @case('pending') รอชำระเงิน @break
                                        @case('processing') กำลังดำเนินการ @break
                                        @case('completed') เสร็จสมบูรณ์ @break
                                        @case('cancelled') ยกเลิก @break
                                        @default {{ $order->status }}
                                    @endswitch
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-3">
                                <span>{{ $order->created_at->format('d/m/Y H:i') }}</span>
                                @if($order->payment_method === 'wallet')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300">
                                        Wallet
                                    </span>
                                @elseif($order->payment_method === 'promptpay')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300">
                                        PromptPay
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-xl font-bold text-gray-900 dark:text-white">฿{{ number_format($order->total, 2) }}</span>
                                    @if($order->discount > 0)
                                        <span class="ml-2 text-sm text-green-600 dark:text-green-400">-฿{{ number_format($order->discount, 2) }}</span>
                                    @endif
                                </div>
                                <a href="{{ route('orders.show', $order) }}"
                                   class="inline-flex items-center px-4 py-2 text-sm font-medium bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition">
                                    ดูรายละเอียด
                                </a>
                            </div>
                            @if($order->coupon_code)
                                <div class="mt-3 pt-3 border-t dark:border-gray-700">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                        ใช้คูปอง: {{ $order->coupon_code }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
