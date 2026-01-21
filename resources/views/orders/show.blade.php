@extends('layouts.app')

@section('title', 'คำสั่งซื้อ #' . $order->order_number . ' - XMAN Studio')

@section('content')
<div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-gray-900">คำสั่งซื้อ #{{ $order->order_number }}</h1>
        <span class="inline-flex px-4 py-2 text-sm font-semibold rounded-full
            @if($order->status === 'completed') bg-green-100 text-green-800
            @elseif($order->status === 'paid') bg-blue-100 text-blue-800
            @elseif($order->status === 'pending') bg-yellow-100 text-yellow-800
            @elseif($order->status === 'cancelled') bg-red-100 text-red-800
            @else bg-gray-100 text-gray-800 @endif">
            @switch($order->status)
                @case('pending')
                    รอชำระเงิน
                    @break
                @case('paid')
                    ชำระเงินแล้ว
                    @break
                @case('processing')
                    กำลังดำเนินการ
                    @break
                @case('completed')
                    เสร็จสมบูรณ์
                    @break
                @case('cancelled')
                    ยกเลิก
                    @break
                @default
                    {{ $order->status }}
            @endswitch
        </span>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Order Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Items -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">รายการสินค้า</h2>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สินค้า</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ราคา</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวน</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รวม</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($order->items as $item)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $item->product->name ?? 'สินค้าถูกลบ' }}</div>
                                    @if($item->custom_requirements)
                                        <div class="mt-3 border border-primary-200 rounded-lg overflow-hidden">
                                            <div class="bg-gradient-to-r from-primary-500 to-primary-600 px-4 py-2 flex items-center">
                                                <svg class="w-4 h-4 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                <span class="font-medium text-white text-sm">รายละเอียดที่ต้องการ</span>
                                            </div>
                                            <div class="bg-gradient-to-b from-primary-50 to-white p-4">
                                                <x-page-builder-render :content="$item->custom_requirements" />
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ number_format($item->price, 2) }} บาท
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ $item->quantity }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900">
                                    {{ number_format($item->price * $item->quantity, 2) }} บาท
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-right font-bold text-gray-900">ยอดรวมทั้งหมด</td>
                            <td class="px-6 py-4 font-bold text-primary-600 text-lg">{{ number_format($order->total, 2) }} บาท</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- License Keys -->
            @if($order->status === 'completed' && $order->items->whereNotNull('license_key_id')->isNotEmpty())
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">License Keys</h2>
                    <div class="space-y-3">
                        @foreach($order->items as $item)
                            @if($item->licenseKey)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <div class="text-sm text-gray-500">{{ $item->product->name ?? 'Product' }}</div>
                                        <div class="font-mono font-bold text-lg text-gray-900">{{ $item->licenseKey->license_key }}</div>
                                    </div>
                                    <button onclick="copyToClipboard('{{ $item->licenseKey->license_key }}')"
                                            class="px-3 py-1 text-sm bg-primary-600 text-white rounded hover:bg-primary-700">
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
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลการชำระเงิน</h2>

                    @if($order->payment_method === 'promptpay' && isset($paymentInfo['qr_image_url']))
                        <div class="text-center">
                            <p class="text-gray-600 mb-4">สแกน QR Code ด้วยแอปธนาคารเพื่อชำระเงิน</p>
                            <div class="inline-block p-4 bg-white border-2 border-primary-200 rounded-xl shadow-lg">
                                <img src="{{ $paymentInfo['qr_image_url'] }}" alt="PromptPay QR Code" class="w-64 h-64">
                            </div>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500">พร้อมเพย์: {{ $paymentInfo['promptpay_number'] ?? 'N/A' }}</p>
                                <p class="mt-2 text-3xl font-bold text-primary-600">{{ number_format($order->total, 2) }} บาท</p>
                            </div>
                            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <p class="text-sm text-yellow-700">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    อ้างอิง: {{ $order->order_number }}
                                </p>
                            </div>
                        </div>
                    @elseif($order->payment_method === 'bank_transfer' && is_array($paymentInfo))
                        <div class="space-y-4">
                            <p class="text-gray-600">โปรดโอนเงินไปยังบัญชีด้านล่าง:</p>
                            @foreach($paymentInfo as $bank)
                                <div class="p-4 bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg border border-gray-200">
                                    <div class="flex items-center mb-2">
                                        <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center mr-3">
                                            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900">{{ $bank['bank'] }}</p>
                                            @if(isset($bank['branch']))
                                                <p class="text-xs text-gray-500">สาขา: {{ $bank['branch'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="ml-13 pl-13">
                                        <p class="font-mono text-xl font-bold text-gray-900 tracking-wider">{{ $bank['account_number'] }}</p>
                                        <p class="text-gray-600">{{ $bank['account_name'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                            <div class="text-center pt-4 border-t">
                                <p class="text-sm text-gray-500 mb-1">ยอดที่ต้องชำระ</p>
                                <p class="text-3xl font-bold text-primary-600">{{ number_format($order->total, 2) }} บาท</p>
                            </div>
                            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <p class="text-sm text-yellow-700">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    กรุณาระบุเลขที่คำสั่งซื้อ <strong>{{ $order->order_number }}</strong> ในช่องหมายเหตุ
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <p class="text-gray-500">ข้อมูลการชำระเงินไม่พร้อมใช้งาน</p>
                            <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($order->total, 2) }} บาท</p>
                        </div>
                    @endif

                    <!-- Upload Payment Slip -->
                    <div class="mt-6 pt-6 border-t">
                        <h3 class="text-md font-semibold text-gray-900 mb-3">แนบหลักฐานการชำระเงิน</h3>
                        <form action="{{ route('orders.confirm-payment', $order) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">อัพโหลดสลิปโอนเงิน</label>
                                    <input type="file" name="payment_slip" accept="image/*" required
                                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                                </div>
                                <button type="submit"
                                        class="w-full px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-semibold transition">
                                    ยืนยันการชำระเงิน
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @elseif($order->payment_status === 'verifying')
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-center py-6">
                        <div class="w-16 h-16 mx-auto bg-yellow-100 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">กำลังตรวจสอบการชำระเงิน</h3>
                        <p class="text-gray-500 mt-2">เราได้รับหลักฐานการชำระเงินแล้ว และกำลังดำเนินการตรวจสอบ</p>
                        @if($order->payment_slip)
                            <div class="mt-4">
                                <img src="{{ asset('storage/' . $order->payment_slip) }}" alt="Payment Slip" class="max-w-xs mx-auto rounded-lg shadow">
                            </div>
                        @endif
                    </div>
                </div>
            @elseif($order->payment_status === 'paid')
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-center py-6">
                        <div class="w-16 h-16 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">ชำระเงินสำเร็จ</h3>
                        <p class="text-gray-500 mt-2">ขอบคุณสำหรับการชำระเงิน คำสั่งซื้อของคุณกำลังดำเนินการ</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Summary Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6 sticky top-24">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลคำสั่งซื้อ</h2>

                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">เลขที่คำสั่งซื้อ</dt>
                        <dd class="font-mono text-gray-900">{{ $order->order_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">วันที่สั่งซื้อ</dt>
                        <dd class="text-gray-900">{{ $order->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">ชื่อผู้สั่ง</dt>
                        <dd class="text-gray-900">{{ $order->customer_name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">อีเมล</dt>
                        <dd class="text-gray-900">{{ $order->customer_email }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">โทรศัพท์</dt>
                        <dd class="text-gray-900">{{ $order->customer_phone }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">วิธีชำระเงิน</dt>
                        <dd class="text-gray-900">
                            {{ $order->payment_method === 'promptpay' ? 'PromptPay' : 'โอนเงิน' }}
                        </dd>
                    </div>
                </dl>

                @if($order->notes)
                    <div class="mt-4 pt-4 border-t">
                        <dt class="text-gray-500 mb-1">หมายเหตุ</dt>
                        <dd class="text-gray-900">{{ $order->notes }}</dd>
                    </div>
                @endif

                @if($order->status === 'completed')
                    <a href="{{ route('orders.download', $order) }}"
                       class="mt-6 block w-full text-center px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-semibold">
                        ดาวน์โหลดใบเสร็จ
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('คัดลอก License Key แล้ว');
    });
}
</script>
@endpush
@endsection
