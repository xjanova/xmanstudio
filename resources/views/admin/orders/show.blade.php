@extends($adminLayout ?? 'layouts.admin')

@section('title', 'คำสั่งซื้อ #' . $order->order_number)
@section('page-title', 'คำสั่งซื้อ #' . $order->order_number)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                กลับไปรายการคำสั่งซื้อ
            </a>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">คำสั่งซื้อ #{{ $order->order_number }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">สร้างเมื่อ {{ $order->created_at->format('d/m/Y H:i:s') }}</p>
        </div>
        <div class="flex items-center gap-2">
            @switch($order->payment_status)
                @case('pending')
                    <span class="px-3 py-1.5 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">รอชำระเงิน</span>
                    @break
                @case('verifying')
                    <span class="px-3 py-1.5 text-sm font-semibold rounded-full bg-amber-100 text-amber-800 animate-pulse">รอตรวจสลิป</span>
                    @break
                @case('paid')
                    <span class="px-3 py-1.5 text-sm font-semibold rounded-full bg-green-100 text-green-800">ชำระแล้ว</span>
                    @break
                @case('expired')
                    <span class="px-3 py-1.5 text-sm font-semibold rounded-full bg-red-100 text-red-800">หมดอายุ</span>
                    @break
                @case('rejected')
                    <span class="px-3 py-1.5 text-sm font-semibold rounded-full bg-red-100 text-red-800">ถูกปฏิเสธ</span>
                    @break
            @endswitch

            @switch($order->status)
                @case('pending')
                    <span class="px-3 py-1.5 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">รอดำเนินการ</span>
                    @break
                @case('processing')
                    <span class="px-3 py-1.5 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">กำลังดำเนินการ</span>
                    @break
                @case('completed')
                    <span class="px-3 py-1.5 text-sm font-semibold rounded-full bg-green-100 text-green-800">เสร็จสมบูรณ์</span>
                    @break
                @case('cancelled')
                    <span class="px-3 py-1.5 text-sm font-semibold rounded-full bg-red-100 text-red-800">ยกเลิก</span>
                    @break
            @endswitch
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Order Items -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white">รายการสินค้า</h2>
                </div>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">สินค้า</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">ราคา</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">จำนวน</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">รวม</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($order->items as $item)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $item->product->name ?? $item->product_name ?? 'สินค้าถูกลบ' }}</div>
                                @if($item->licenseKey)
                                    <div class="mt-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded inline-block">
                                        Key: {{ $item->licenseKey->license_key }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">฿{{ number_format($item->price, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">฿{{ number_format($item->price * $item->quantity, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-right text-sm text-gray-500">ยอดรวมสินค้า</td>
                            <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">฿{{ number_format($order->subtotal, 2) }}</td>
                        </tr>
                        @if($order->discount > 0)
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-right text-sm text-green-600">
                                ส่วนลด
                                @if($order->coupon_code)
                                    <span class="ml-1 px-1.5 py-0.5 bg-green-100 text-green-700 rounded text-xs font-mono">{{ $order->coupon_code }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 font-medium text-green-600">-฿{{ number_format($order->discount, 2) }}</td>
                        </tr>
                        @endif
                        @if($order->tax > 0)
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-right text-sm text-gray-500">ภาษี 7%</td>
                            <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">฿{{ number_format($order->tax, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                            <td colspan="3" class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">ยอดรวมทั้งหมด</td>
                            <td class="px-4 py-3 font-bold text-xl text-blue-600">฿{{ number_format($order->total, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Payment Slip -->
            @if($order->payment_slip)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สลิปการโอนเงิน</h3>
                <div class="flex justify-center">
                    <a href="{{ asset('storage/' . $order->payment_slip) }}" target="_blank" class="block">
                        <img src="{{ asset('storage/' . $order->payment_slip) }}" alt="Payment Slip" class="max-w-sm rounded-xl shadow-lg border">
                    </a>
                </div>
            </div>
            @endif

            <!-- Admin Actions -->
            @if(in_array($order->payment_status, ['pending', 'verifying']))
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">จัดการการชำระเงิน</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <form action="{{ route('admin.orders.update-payment-status', $order) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="payment_status" value="paid">
                        <div class="mb-3">
                            <input type="text" name="admin_note" placeholder="หมายเหตุ (ไม่บังคับ)"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                        </div>
                        <button type="submit" class="w-full px-4 py-2.5 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition flex items-center justify-center"
                                onclick="return confirm('ยืนยันอนุมัติการชำระเงิน?')">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            อนุมัติการชำระเงิน
                        </button>
                    </form>
                    <form action="{{ route('admin.orders.update-payment-status', $order) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="payment_status" value="rejected">
                        <div class="mb-3">
                            <input type="text" name="admin_note" placeholder="เหตุผลที่ปฏิเสธ"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                        </div>
                        <button type="submit" class="w-full px-4 py-2.5 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 transition flex items-center justify-center"
                                onclick="return confirm('ยืนยันปฏิเสธการชำระเงิน?')">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            ปฏิเสธ
                        </button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Update Order Status -->
            @if($order->payment_status === 'paid' && in_array($order->status, ['pending', 'processing']))
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">อัปเดตสถานะคำสั่งซื้อ</h3>
                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="flex gap-3">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                        <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                        <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>เสร็จสมบูรณ์</option>
                    </select>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                        อัปเดต
                    </button>
                </form>
            </div>
            @endif

            <!-- Notes -->
            @if($order->notes)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">หมายเหตุ</h3>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $order->notes }}</div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Customer Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลลูกค้า</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">ชื่อ</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $order->customer_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">อีเมล</dt>
                        <dd class="font-medium text-gray-900 dark:text-white break-all">{{ $order->customer_email }}</dd>
                    </div>
                    @if($order->customer_phone)
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">โทรศัพท์</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $order->customer_phone }}</dd>
                    </div>
                    @endif
                    @if($order->user)
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">สมาชิก</dt>
                        <dd class="font-medium text-blue-600 dark:text-blue-400">{{ $order->user->name }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            <!-- Payment Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">การชำระเงิน</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">ช่องทาง</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">
                            @switch($order->payment_method)
                                @case('promptpay') PromptPay @break
                                @case('bank_transfer') โอนเงินธนาคาร @break
                                @case('wallet') Wallet @break
                                @default {{ $order->payment_method }}
                            @endswitch
                        </dd>
                    </div>
                    @if($order->paid_at)
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">ชำระเมื่อ</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $order->paid_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    @endif
                    @if($order->coupon_code)
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">คูปอง</dt>
                        <dd><span class="font-mono px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs">{{ $order->coupon_code }}</span></dd>
                    </div>
                    @endif
                    @if($order->usesSmsPayment())
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">SMS Verification</dt>
                        <dd>
                            @switch($order->sms_verification_status)
                                @case('pending')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700">รอตรวจสอบ</span>
                                    @break
                                @case('matched')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">จับคู่สำเร็จ</span>
                                    @break
                                @case('confirmed')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">ยืนยันแล้ว</span>
                                    @break
                            @endswitch
                        </dd>
                    </div>
                    @if($order->uniquePaymentAmount)
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">ยอด Unique</dt>
                        <dd class="font-mono font-bold text-gray-900 dark:text-white">฿{{ number_format($order->uniquePaymentAmount->unique_amount, 2) }}</dd>
                    </div>
                    @endif
                    @endif
                </dl>
            </div>

            <!-- Timeline -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ไทม์ไลน์</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-start">
                        <div class="w-2 h-2 mt-1.5 rounded-full bg-blue-500 mr-3 flex-shrink-0"></div>
                        <div>
                            <p class="text-gray-900 dark:text-white">สร้างคำสั่งซื้อ</p>
                            <p class="text-xs text-gray-500">{{ $order->created_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                    </div>
                    @if($order->paid_at)
                    <div class="flex items-start">
                        <div class="w-2 h-2 mt-1.5 rounded-full bg-green-500 mr-3 flex-shrink-0"></div>
                        <div>
                            <p class="text-gray-900 dark:text-white">ชำระเงินสำเร็จ</p>
                            <p class="text-xs text-gray-500">{{ $order->paid_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                    </div>
                    @endif
                    @if($order->sms_verified_at)
                    <div class="flex items-start">
                        <div class="w-2 h-2 mt-1.5 rounded-full bg-purple-500 mr-3 flex-shrink-0"></div>
                        <div>
                            <p class="text-gray-900 dark:text-white">SMS ยืนยัน</p>
                            <p class="text-xs text-gray-500">{{ $order->sms_verified_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
