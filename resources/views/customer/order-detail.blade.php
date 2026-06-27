@extends($customerLayout ?? 'layouts.customer')

@section('title', 'คำสั่งซื้อ #' . ($order->order_number ?? $order->id))
@section('page-title')<x-bi th="รายละเอียดคำสั่งซื้อ" en="Order Details" />@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('customer.orders') }}" class="text-orange-600 dark:text-orange-400 hover:text-orange-700 dark:hover:text-orange-300 flex items-center font-medium transition-colors">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        <x-bi th="กลับไปหน้าประวัติคำสั่งซื้อ" en="Back to order history" />
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Order Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Order Header Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
            <div class="relative overflow-hidden bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 p-6">
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-blob"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-orange-300/20 rounded-full blur-3xl animate-blob animation-delay-2000"></div>

                <div class="relative flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-white"><x-bi th="คำสั่งซื้อ" en="Order" /> #{{ $order->order_number ?? $order->id }}</h2>
                        <p class="text-white/80 mt-1">{{ $order->created_at->format('d/m/Y H:i') }} น.</p>
                    </div>
                    @php
                        $statusGradients = [
                            'completed' => 'bg-gradient-to-r from-green-400 to-emerald-500 text-white',
                            'pending' => 'bg-gradient-to-r from-yellow-400 to-orange-400 text-white',
                            'processing' => 'bg-gradient-to-r from-blue-400 to-indigo-500 text-white',
                            'cancelled' => 'bg-gradient-to-r from-red-400 to-rose-500 text-white',
                        ];
                        $statusLabels = [
                            'completed' => 'สำเร็จ / Completed',
                            'pending' => 'รอดำเนินการ / Pending',
                            'processing' => 'กำลังดำเนินการ / Processing',
                            'cancelled' => 'ยกเลิก / Cancelled',
                        ];
                    @endphp
                    <span class="px-4 py-1.5 text-sm font-semibold rounded-full shadow {{ $statusGradients[$order->status] ?? 'bg-white/20 text-white' }}">
                        {{ $statusLabels[$order->status] ?? ucfirst($order->status ?? 'pending') }}
                    </span>
                </div>
            </div>

            <!-- Order Items -->
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><x-bi th="รายการสินค้า" en="Order Items" /></h3>
                <div class="space-y-4">
                    @foreach($order->items as $item)
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
                        <div class="flex items-center min-w-0">
                            @if($item->product && $item->product->image)
                                <img src="{{ Storage::url($item->product->image) }}" alt="{{ $item->product_name ?? $item->product->name }}"
                                     class="w-14 h-14 rounded-xl object-cover mr-4 flex-shrink-0">
                            @else
                                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center mr-4 flex-shrink-0 shadow">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $item->product_name ?? $item->product?->name ?? 'สินค้า / Product' }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($item->price, 2) }} <x-bi th="บาท" en="THB" /> x {{ $item->quantity }}</p>
                                @if($item->custom_requirements)
                                    @php $reqs = json_decode($item->custom_requirements, true); @endphp
                                    @if(is_array($reqs) && isset($reqs['license_type']))
                                        <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                                            {{ ucfirst($reqs['license_type']) }}
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <p class="font-bold text-gray-900 dark:text-white flex-shrink-0 ml-4">
                            {{ number_format(($item->total ?? $item->price * $item->quantity), 2) }} <x-bi th="บาท" en="THB" />
                        </p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <x-bi th="ข้อมูลการชำระเงิน" en="Payment Information" />
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                    <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1"><x-bi k="common.payment_method" /></label>
                    <p class="text-gray-900 dark:text-white font-medium">{{ ucfirst($order->payment_method ?? '-') }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                    <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1"><x-bi th="สถานะการชำระเงิน" en="Payment Status" /></label>
                    @php
                        $paymentStatusLabels = [
                            'paid' => ['สำเร็จ / Paid', 'text-green-600 dark:text-green-400'],
                            'pending' => ['รอชำระเงิน / Pending', 'text-yellow-600 dark:text-yellow-400'],
                            'failed' => ['ล้มเหลว / Failed', 'text-red-600 dark:text-red-400'],
                            'refunded' => ['คืนเงินแล้ว / Refunded', 'text-blue-600 dark:text-blue-400'],
                        ];
                        $ps = $paymentStatusLabels[$order->payment_status] ?? [ucfirst($order->payment_status ?? 'pending'), 'text-gray-600 dark:text-gray-400'];
                    @endphp
                    <p class="font-medium {{ $ps[1] }}">{{ $ps[0] }}</p>
                </div>
                @if($order->paid_at)
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                    <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1"><x-bi th="ชำระเมื่อ" en="Paid On" /></label>
                    <p class="text-gray-900 dark:text-white font-medium">{{ $order->paid_at->format('d/m/Y H:i') }} น.</p>
                </div>
                @endif
                @if($order->coupon_code)
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                    <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1"><x-bi th="คูปอง" en="Coupon" /></label>
                    <p class="text-gray-900 dark:text-white font-medium">{{ $order->coupon_code }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Notes -->
        @if($order->notes)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <x-bi th="หมายเหตุ" en="Notes" />
            </h3>
            <p class="text-gray-600 dark:text-gray-400">{{ $order->notes }}</p>
        </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Price Summary -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><x-bi th="สรุปยอด" en="Order Summary" /></h3>
            <div class="space-y-3">
                <div class="flex justify-between text-gray-600 dark:text-gray-400">
                    <span><x-bi th="ยอดรวมสินค้า" en="Subtotal" /></span>
                    <span>{{ number_format($order->subtotal ?? $order->total, 2) }} <x-bi th="บาท" en="THB" /></span>
                </div>
                @if($order->discount && $order->discount > 0)
                <div class="flex justify-between text-green-600 dark:text-green-400">
                    <span><x-bi th="ส่วนลด" en="Discount" /></span>
                    <span>-{{ number_format($order->discount, 2) }} <x-bi th="บาท" en="THB" /></span>
                </div>
                @endif
                @if($order->tax && $order->tax > 0)
                <div class="flex justify-between text-gray-600 dark:text-gray-400">
                    <span><x-bi th="ภาษี" en="Tax" /></span>
                    <span>{{ number_format($order->tax, 2) }} <x-bi th="บาท" en="THB" /></span>
                </div>
                @endif
                <div class="border-t border-gray-200 dark:border-gray-700 pt-3 flex justify-between">
                    <span class="text-lg font-bold text-gray-900 dark:text-white"><x-bi th="ยอดรวมทั้งหมด" en="Total" /></span>
                    <span class="text-lg font-bold bg-gradient-to-r from-orange-500 to-red-500 bg-clip-text text-transparent">{{ number_format($order->total ?? 0, 2) }} <x-bi th="บาท" en="THB" /></span>
                </div>
            </div>
        </div>

        <!-- Order Info -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><x-bi th="ข้อมูลคำสั่งซื้อ" en="Order Information" /></h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400"><x-bi k="common.order_number" /></span>
                    <span class="font-semibold text-gray-900 dark:text-white">#{{ $order->order_number ?? $order->id }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400"><x-bi th="วันที่สั่งซื้อ" en="Order Date" /></span>
                    <span class="text-gray-900 dark:text-white">{{ $order->created_at->format('d/m/Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400"><x-bi th="เวลา" en="Time" /></span>
                    <span class="text-gray-900 dark:text-white">{{ $order->created_at->format('H:i') }} น.</span>
                </div>
                @if($order->customer_name)
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400"><x-bi th="ชื่อลูกค้า" en="Customer Name" /></span>
                    <span class="text-gray-900 dark:text-white">{{ $order->customer_name }}</span>
                </div>
                @endif
                @if($order->customer_email)
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400"><x-bi k="common.email" /></span>
                    <span class="text-gray-900 dark:text-white">{{ $order->customer_email }}</span>
                </div>
                @endif
                @if($order->customer_phone)
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400"><x-bi k="common.phone" /></span>
                    <span class="text-gray-900 dark:text-white">{{ $order->customer_phone }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Help Card -->
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl p-6 border border-blue-200 dark:border-blue-700">
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center mb-4 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="font-semibold text-blue-900 dark:text-blue-300 mb-2"><x-bi th="มีปัญหาเกี่ยวกับคำสั่งซื้อ?" en="Have a question about your order?" /></h3>
            <p class="text-sm text-blue-800 dark:text-blue-200 mb-3">
                <x-bi th="หากมีข้อสงสัยหรือปัญหาเกี่ยวกับคำสั่งซื้อนี้ ติดต่อทีมสนับสนุนของเราได้เลย" en="If you have any questions or issues with this order, feel free to contact our support team." />
            </p>
            <a href="{{ route('support.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-semibold flex items-center transition-colors">
                <x-bi th="ติดต่อเรา" en="Contact Us" />
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</div>
@endsection
