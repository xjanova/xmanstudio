@extends($adminLayout ?? 'layouts.admin')

@section('page-title', 'รายละเอียด SMS #' . $notification->id)

@section('content')
<!-- Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">รายละเอียด SMS #{{ $notification->id }}</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">ข้อมูล SMS Payment ที่ได้รับจากธนาคาร</p>
    </div>
    <a href="{{ route('admin.sms-payment.notifications') }}" class="inline-flex items-center px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        กลับ
    </a>
</div>

@if(session('success'))
<div class="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800">
    <div class="flex items-center">
        <svg class="w-5 h-5 text-emerald-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span class="text-emerald-700 dark:text-emerald-300 font-medium">{{ session('success') }}</span>
    </div>
</div>
@endif

@if(session('error'))
<div class="mb-6 p-4 rounded-xl bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800">
    <div class="flex items-center">
        <svg class="w-5 h-5 text-rose-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span class="text-rose-700 dark:text-rose-300 font-medium">{{ session('error') }}</span>
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Notification Details -->
    <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                </svg>
                ข้อมูล SMS
            </h2>
        </div>
        <div class="p-6 space-y-4">
            <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-sm text-gray-500 dark:text-gray-400">ธนาคาร</span>
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 shadow mr-2">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ config('smschecker.banks.' . $notification->bank, $notification->bank) }}</span>
                </div>
            </div>
            <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-sm text-gray-500 dark:text-gray-400">ประเภท</span>
                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full {{ $notification->type === 'credit' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400' }}">
                    {{ $notification->type === 'credit' ? 'เงินเข้า' : 'เงินออก' }}
                </span>
            </div>
            <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-sm text-gray-500 dark:text-gray-400">จำนวนเงิน</span>
                <span class="text-2xl font-bold {{ $notification->type === 'credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ $notification->type === 'credit' ? '+' : '-' }}&#3647;{{ number_format($notification->amount, 2) }}
                </span>
            </div>
            <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-sm text-gray-500 dark:text-gray-400">สถานะ</span>
                @switch($notification->status)
                    @case('pending')
                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                            <span class="w-2 h-2 bg-amber-500 rounded-full mr-2 animate-pulse"></span>
                            Pending
                        </span>
                        @break
                    @case('matched')
                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                            <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                            Matched
                        </span>
                        @break
                    @case('confirmed')
                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                            <span class="w-2 h-2 bg-emerald-500 rounded-full mr-2"></span>
                            Confirmed
                        </span>
                        @break
                    @case('expired')
                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400">
                            <span class="w-2 h-2 bg-gray-500 rounded-full mr-2"></span>
                            Expired
                        </span>
                        @break
                    @default
                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400">
                            {{ ucfirst($notification->status) }}
                        </span>
                @endswitch
            </div>
            @if($notification->account_number)
            <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-sm text-gray-500 dark:text-gray-400">เลขบัญชี</span>
                <span class="font-mono text-sm text-gray-900 dark:text-white">{{ $notification->account_number }}</span>
            </div>
            @endif
            @if($notification->sender_or_receiver)
            <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-sm text-gray-500 dark:text-gray-400">ผู้โอน/ผู้รับ</span>
                <span class="text-sm text-gray-900 dark:text-white">{{ $notification->sender_or_receiver }}</span>
            </div>
            @endif
            @if($notification->reference_number)
            <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-sm text-gray-500 dark:text-gray-400">เลขอ้างอิง</span>
                <span class="font-mono text-sm text-gray-900 dark:text-white">{{ $notification->reference_number }}</span>
            </div>
            @endif
            <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-sm text-gray-500 dark:text-gray-400">เวลา SMS</span>
                <span class="text-sm text-gray-900 dark:text-white">{{ $notification->sms_timestamp ? $notification->sms_timestamp->format('d/m/Y H:i:s') : '-' }}</span>
            </div>
            <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-sm text-gray-500 dark:text-gray-400">บันทึกเมื่อ</span>
                <span class="text-sm text-gray-900 dark:text-white">{{ $notification->created_at->format('d/m/Y H:i:s') }}</span>
            </div>
            @if($notification->device)
            <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-sm text-gray-500 dark:text-gray-400">Device</span>
                <a href="{{ route('admin.sms-payment.devices.show', $notification->device) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                    {{ $notification->device->device_name ?? $notification->device_id }}
                </a>
            </div>
            @endif
            @if($notification->ip_address)
            <div class="flex justify-between items-center py-3">
                <span class="text-sm text-gray-500 dark:text-gray-400">IP Address</span>
                <span class="font-mono text-sm text-gray-900 dark:text-white">{{ $notification->ip_address }}</span>
            </div>
            @endif
        </div>
    </div>

    <!-- Matched Order + Manual Match -->
    <div class="space-y-6">
        <!-- Matched Order Card -->
        @if($notification->matchedOrder)
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-emerald-50 dark:bg-emerald-900/20">
                <h2 class="text-lg font-semibold text-emerald-800 dark:text-emerald-300 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Order ที่จับคู่ได้
                </h2>
            </div>
            <div class="p-6 space-y-4">
                @php $order = $notification->matchedOrder; @endphp
                <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">เลขคำสั่งซื้อ</span>
                    <a href="{{ route('orders.show', $order) }}" target="_blank" class="font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                        {{ $order->order_number }}
                    </a>
                </div>
                <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">ลูกค้า</span>
                    <span class="text-sm text-gray-900 dark:text-white">{{ $order->customer_name }}</span>
                </div>
                <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">ยอดชำระ</span>
                    <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400">&#3647;{{ number_format($order->total, 2) }}</span>
                </div>
                <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">สถานะชำระ</span>
                    <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full
                        @if($order->payment_status === 'paid') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400
                        @elseif($order->payment_status === 'pending') bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400
                        @elseif($order->payment_status === 'processing') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                        @else bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400
                        @endif">
                        {{ ucfirst($order->payment_status) }}
                    </span>
                </div>
                <div class="flex justify-between items-center py-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400">สั่งซื้อเมื่อ</span>
                    <span class="text-sm text-gray-900 dark:text-white">{{ $order->created_at->format('d/m/Y H:i:s') }}</span>
                </div>

                <!-- Confirm / Reject buttons -->
                @if(in_array($order->payment_status, ['pending', 'processing']))
                <div class="flex items-center gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <form action="{{ route('admin.sms-payment.confirm', $order) }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl hover:from-emerald-600 hover:to-teal-700 transition-all duration-200 shadow-lg shadow-emerald-500/30">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            ยืนยันการชำระเงิน
                        </button>
                    </form>
                    <form action="{{ route('admin.sms-payment.reject', $order) }}" method="POST" class="flex-1" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะปฏิเสธการชำระเงินนี้?')">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/30 rounded-xl hover:bg-rose-100 dark:hover:bg-rose-900/50 transition-all duration-200 border border-rose-200 dark:border-rose-800">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            ปฏิเสธ
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Manual Match Form (only for pending credit notifications) -->
        @if($notification->status === 'pending' && $notification->type === 'credit')
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-amber-50 dark:bg-amber-900/20">
                <h2 class="text-lg font-semibold text-amber-800 dark:text-amber-300 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    จับคู่ด้วยตนเอง
                </h2>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">เลือก Order ที่ต้องการจับคู่กับ SMS นี้</p>
                <form action="{{ route('admin.sms-payment.notifications.match', $notification) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="order_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Order ID</label>
                        <input type="number" name="order_id" id="order_id" required
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all duration-200"
                            placeholder="ใส่ Order ID">
                        @error('order_id')
                        <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-amber-500 to-orange-600 rounded-xl hover:from-amber-600 hover:to-orange-700 transition-all duration-200 shadow-lg shadow-amber-500/30">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        จับคู่
                    </button>
                </form>
            </div>
        </div>
        @endif

        <!-- No match info -->
        @if(!$notification->matchedOrder && $notification->status !== 'pending')
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gray-50 dark:bg-gray-700 mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-gray-500 dark:text-gray-400">ไม่พบ Order ที่ตรงกัน</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
