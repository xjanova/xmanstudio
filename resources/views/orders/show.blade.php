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
            @if($order->status === 'pending')
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลการชำระเงิน</h2>

                    @if($order->payment_method === 'promptpay')
                        <div class="text-center">
                            <p class="text-gray-600 mb-4">สแกน QR Code เพื่อชำระเงิน</p>
                            <div class="inline-block p-4 bg-white border-2 border-gray-200 rounded-lg">
                                <!-- QR Code would be generated here -->
                                <div class="w-48 h-48 bg-gray-100 flex items-center justify-center">
                                    <span class="text-gray-400">QR Code</span>
                                </div>
                            </div>
                            <p class="mt-4 text-2xl font-bold text-gray-900">{{ number_format($order->total, 2) }} บาท</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            <p class="text-gray-600">โปรดโอนเงินไปยังบัญชีด้านล่าง:</p>
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <p class="font-semibold">ธนาคาร: กสิกรไทย</p>
                                <p class="font-mono text-lg">123-4-56789-0</p>
                                <p class="text-gray-600">ชื่อบัญชี: บริษัท XMAN Studio จำกัด</p>
                            </div>
                            <p class="text-2xl font-bold text-center text-gray-900">{{ number_format($order->total, 2) }} บาท</p>
                        </div>
                    @endif
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
