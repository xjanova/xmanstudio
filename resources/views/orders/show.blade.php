@extends($publicLayout ?? 'layouts.app')

@section('title', 'คำสั่งซื้อ #' . $order->order_number . ' - XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-slate-900 dark:to-indigo-950 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <a href="{{ route('orders.index') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 mb-2">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        กลับไปรายการคำสั่งซื้อ
                    </a>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                        คำสั่งซื้อ #{{ $order->order_number }}
                    </h1>
                </div>
                <span class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-full shadow-sm
                    @if($order->status === 'completed') bg-gradient-to-r from-green-500 to-emerald-500 text-white
                    @elseif($order->status === 'processing') bg-gradient-to-r from-blue-500 to-indigo-500 text-white
                    @elseif($order->status === 'pending') bg-gradient-to-r from-yellow-500 to-amber-500 text-white
                    @elseif($order->status === 'cancelled') bg-gradient-to-r from-red-500 to-rose-500 text-white
                    @else bg-gradient-to-r from-gray-400 to-gray-500 text-white @endif">
                    @switch($order->status)
                        @case('pending')
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            รอชำระเงิน
                            @break
                        @case('processing')
                            <svg class="w-4 h-4 mr-1.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            กำลังดำเนินการ
                            @break
                        @case('completed')
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            เสร็จสมบูรณ์
                            @break
                        @case('cancelled')
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            ยกเลิก
                            @break
                        @default
                            {{ $order->status }}
                    @endswitch
                </span>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-xl">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Order Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Items -->
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                        <h2 class="text-lg font-semibold text-white flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            รายการสินค้า
                        </h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สินค้า</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ราคา</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จำนวน</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">รวม</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($order->items as $item)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $item->product->name ?? $item->product_name ?? 'สินค้าถูกลบ' }}</div>
                                            @if($item->custom_requirements)
                                                <div class="mt-3 border border-blue-200 dark:border-blue-700 rounded-lg overflow-hidden">
                                                    <div class="bg-gradient-to-r from-blue-500 to-indigo-500 px-4 py-2 flex items-center">
                                                        <svg class="w-4 h-4 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                        </svg>
                                                        <span class="font-medium text-white text-sm">รายละเอียดที่ต้องการ</span>
                                                    </div>
                                                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4">
                                                        <x-page-builder-render :content="$item->custom_requirements" />
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                            ฿{{ number_format($item->price, 2) }}
                                        </td>
                                        <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                            {{ $item->quantity }}
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                            ฿{{ number_format($item->price * $item->quantity, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <td colspan="3" class="px-6 py-3 text-right text-sm text-gray-500 dark:text-gray-400">ยอดรวมสินค้า</td>
                                    <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">฿{{ number_format($order->subtotal, 2) }}</td>
                                </tr>
                                @if($order->tax > 0)
                                <tr>
                                    <td colspan="3" class="px-6 py-3 text-right text-sm text-gray-500 dark:text-gray-400">ภาษีมูลค่าเพิ่ม (7%)</td>
                                    <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">฿{{ number_format($order->tax, 2) }}</td>
                                </tr>
                                @endif
                                @if($order->discount > 0)
                                <tr class="bg-green-50 dark:bg-green-900/20">
                                    <td colspan="3" class="px-6 py-3 text-right text-sm text-green-600 dark:text-green-400 font-medium">
                                        ส่วนลด
                                        @if($order->coupon_code)
                                            <span class="ml-1 px-2 py-0.5 bg-green-100 dark:bg-green-800 text-green-700 dark:text-green-300 rounded-full text-xs font-mono">{{ $order->coupon_code }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 font-medium text-green-600 dark:text-green-400">-฿{{ number_format($order->discount, 2) }}</td>
                                </tr>
                                @endif
                                <tr class="border-t-2 border-gray-200 dark:border-gray-600">
                                    <td colspan="3" class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">ยอดรวมทั้งหมด</td>
                                    <td class="px-6 py-4 font-bold text-xl bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">฿{{ number_format($order->total, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- License Keys -->
                @if($order->status === 'completed' && $order->items->whereNotNull('license_key_id')->isNotEmpty())
                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                            License Keys
                        </h2>
                        <div class="space-y-3">
                            @foreach($order->items as $item)
                                @if($item->licenseKey)
                                    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 rounded-xl">
                                        <div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $item->product->name ?? 'Product' }}</div>
                                            <div class="font-mono font-bold text-lg text-gray-900 dark:text-white">{{ $item->licenseKey->license_key }}</div>
                                        </div>
                                        <button onclick="copyToClipboard('{{ $item->licenseKey->license_key }}')"
                                                class="px-4 py-2 text-sm bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition shadow-md">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                            </svg>
                                            คัดลอก
                                        </button>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Payment Info -->
                @if($order->payment_status === 'pending')
                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
                        <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                ข้อมูลการชำระเงิน
                            </h2>
                        </div>
                        <div class="p-6">
                            @if($order->payment_method === 'promptpay' && isset($paymentInfo['qr_image_url']))
                                <div class="text-center">
                                    <p class="text-gray-600 dark:text-gray-400 mb-4">สแกน QR Code ด้วยแอปธนาคารเพื่อชำระเงิน</p>
                                    <div class="inline-block p-4 bg-white border-2 border-blue-200 dark:border-blue-700 rounded-2xl shadow-lg">
                                        <img src="{{ $paymentInfo['qr_image_url'] }}" alt="PromptPay QR Code" class="w-64 h-64">
                                    </div>
                                    <div class="mt-4">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">พร้อมเพย์: {{ $paymentInfo['promptpay_number'] ?? 'N/A' }}</p>
                                        <p class="mt-2 text-3xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">฿{{ number_format($order->total, 2) }}</p>
                                    </div>
                                    <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
                                        <p class="text-sm text-amber-700 dark:text-amber-300">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            อ้างอิง: {{ $order->order_number }}
                                        </p>
                                    </div>
                                </div>
                            @elseif($order->payment_method === 'bank_transfer' && is_array($paymentInfo))
                                <div class="space-y-4">
                                    <p class="text-gray-600 dark:text-gray-400">โปรดโอนเงินไปยังบัญชีด้านล่าง:</p>
                                    @foreach($paymentInfo as $bank)
                                        <div class="p-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 rounded-xl border border-gray-200 dark:border-gray-600">
                                            <div class="flex items-center mb-2">
                                                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mr-3">
                                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $bank['bank'] }}</p>
                                                    @if(isset($bank['branch']))
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">สาขา: {{ $bank['branch'] }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="ml-13 pl-13">
                                                <p class="font-mono text-xl font-bold text-gray-900 dark:text-white tracking-wider">{{ $bank['account_number'] }}</p>
                                                <p class="text-gray-600 dark:text-gray-400">{{ $bank['account_name'] }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                    <div class="text-center pt-4 border-t dark:border-gray-700">
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">ยอดที่ต้องชำระ</p>
                                        <p class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">฿{{ number_format($order->total, 2) }}</p>
                                    </div>
                                    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
                                        <p class="text-sm text-amber-700 dark:text-amber-300">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            กรุณาระบุเลขที่คำสั่งซื้อ <strong>{{ $order->order_number }}</strong> ในช่องหมายเหตุ
                                        </p>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <p class="text-gray-500 dark:text-gray-400">ข้อมูลการชำระเงินไม่พร้อมใช้งาน</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">฿{{ number_format($order->total, 2) }}</p>
                                </div>
                            @endif

                            <!-- Upload Payment Slip -->
                            <div class="mt-6 pt-6 border-t dark:border-gray-700">
                                <h3 class="text-md font-semibold text-gray-900 dark:text-white mb-3">แนบหลักฐานการชำระเงิน</h3>
                                <form action="{{ route('orders.confirm-payment', $order) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">อัพโหลดสลิปโอนเงิน</label>
                                            <input type="file" name="payment_slip" accept="image/*" required
                                                   class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 dark:file:bg-blue-900 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-800">
                                        </div>
                                        <button type="submit"
                                                class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 font-semibold transition shadow-lg">
                                            ยืนยันการชำระเงิน
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @elseif($order->payment_status === 'verifying')
                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                        <div class="text-center py-6">
                            <div class="w-16 h-16 mx-auto bg-gradient-to-r from-amber-100 to-orange-100 dark:from-amber-900 dark:to-orange-900 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-amber-600 dark:text-amber-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">กำลังตรวจสอบการชำระเงิน</h3>
                            <p class="text-gray-500 dark:text-gray-400 mt-2">เราได้รับหลักฐานการชำระเงินแล้ว และกำลังดำเนินการตรวจสอบ</p>
                            @if($order->payment_slip)
                                <div class="mt-4">
                                    <img src="{{ asset('storage/' . $order->payment_slip) }}" alt="Payment Slip" class="max-w-xs mx-auto rounded-xl shadow-lg">
                                </div>
                            @endif
                        </div>
                    </div>
                @elseif($order->payment_status === 'paid')
                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                        <div class="text-center py-6">
                            <div class="w-16 h-16 mx-auto bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900 dark:to-emerald-900 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ชำระเงินสำเร็จ</h3>
                            <p class="text-gray-500 dark:text-gray-400 mt-2">ขอบคุณสำหรับการชำระเงิน คำสั่งซื้อของคุณกำลังดำเนินการ</p>
                            @if($order->paid_at)
                                <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">ชำระเมื่อ: {{ $order->paid_at->format('d/m/Y H:i') }}</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Summary Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl p-6 sticky top-24 border border-gray-100 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        ข้อมูลคำสั่งซื้อ
                    </h2>

                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">เลขที่คำสั่งซื้อ</dt>
                            <dd class="font-mono text-gray-900 dark:text-white">{{ $order->order_number }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">วันที่สั่งซื้อ</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $order->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">ชื่อผู้สั่ง</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $order->customer_name }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">อีเมล</dt>
                            <dd class="text-gray-900 dark:text-white text-sm break-all">{{ $order->customer_email }}</dd>
                        </div>
                        @if($order->customer_phone)
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">โทรศัพท์</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $order->customer_phone }}</dd>
                        </div>
                        @endif
                        <div class="flex justify-between items-center">
                            <dt class="text-gray-500 dark:text-gray-400">วิธีชำระเงิน</dt>
                            <dd class="text-gray-900 dark:text-white">
                                @if($order->payment_method === 'wallet')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                        </svg>
                                        Wallet
                                    </span>
                                @elseif($order->payment_method === 'promptpay')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                        PromptPay
                                    </span>
                                @elseif($order->payment_method === 'bank_transfer')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                        โอนเงิน
                                    </span>
                                @else
                                    {{ $order->payment_method }}
                                @endif
                            </dd>
                        </div>
                        @if($order->coupon_code)
                        <div class="flex justify-between items-center pt-2 border-t dark:border-gray-700">
                            <dt class="text-gray-500 dark:text-gray-400">คูปองที่ใช้</dt>
                            <dd>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 font-mono">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    {{ $order->coupon_code }}
                                </span>
                            </dd>
                        </div>
                        @endif
                    </dl>

                    @if($order->notes)
                        <div class="mt-4 pt-4 border-t dark:border-gray-700">
                            <dt class="text-gray-500 dark:text-gray-400 mb-1 text-sm">หมายเหตุ</dt>
                            <dd class="text-gray-900 dark:text-white text-sm bg-gray-50 dark:bg-gray-700 rounded-lg p-3">{{ $order->notes }}</dd>
                        </div>
                    @endif

                    @if($order->status === 'completed')
                        <a href="{{ route('orders.download', $order) }}"
                           class="mt-6 flex items-center justify-center w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 font-semibold transition shadow-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            ดาวน์โหลดใบเสร็จ
                        </a>
                    @endif

                    <!-- Payment Status Badge -->
                    <div class="mt-4 pt-4 border-t dark:border-gray-700">
                        <div class="text-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">สถานะการชำระเงิน</span>
                            <div class="mt-1">
                                @if($order->payment_status === 'paid')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        ชำระแล้ว
                                    </span>
                                @elseif($order->payment_status === 'verifying')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200">
                                        <svg class="w-4 h-4 mr-1 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        กำลังตรวจสอบ
                                    </span>
                                @elseif($order->payment_status === 'pending')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        รอชำระเงิน
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                        {{ $order->payment_status }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-2 rounded-lg shadow-lg z-50 animate-fade-in';
        toast.textContent = 'คัดลอก License Key แล้ว';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2000);
    });
}
</script>
@endpush
@endsection
