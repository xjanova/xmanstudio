@extends($publicLayout ?? 'layouts.app')

@section('title', 'ชำระเงิน - XMAN Studio')

@section('content')
<div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">ชำระเงิน</h1>

    @if($errors->any())
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('orders.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Customer Info -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลผู้สั่งซื้อ</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                            <input type="text" name="customer_name" value="{{ old('customer_name', auth()->user()->name ?? '') }}" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">อีเมล <span class="text-red-500">*</span></label>
                            <input type="email" name="customer_email" value="{{ old('customer_email', auth()->user()->email ?? '') }}" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์ <span class="text-red-500">*</span></label>
                            <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">วิธีการชำระเงิน</h2>

                    <div class="space-y-3">
                        @php $firstActive = true; @endphp
                        @foreach($paymentMethods as $method)
                            @if($method['is_active'])
                                <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:border-primary-500 transition payment-method-option {{ $firstActive ? 'border-primary-500 bg-primary-50' : '' }}">
                                    <input type="radio" name="payment_method" value="{{ $method['id'] }}" {{ $firstActive ? 'checked' : '' }}
                                           class="text-primary-600 focus:ring-primary-500"
                                           onchange="selectPaymentMethod(this)">
                                    <div class="ml-3 flex-1">
                                        <div class="flex items-center">
                                            @if($method['icon'] === 'promptpay')
                                                <svg class="w-6 h-6 mr-2 text-primary-600" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                                </svg>
                                            @elseif($method['icon'] === 'bank')
                                                <svg class="w-6 h-6 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                                </svg>
                                            @elseif($method['icon'] === 'card')
                                                <svg class="w-6 h-6 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                                </svg>
                                            @endif
                                            <span class="font-medium text-gray-900">{{ $method['name'] }}</span>
                                        </div>
                                        <p class="text-sm text-gray-500 ml-8">{{ $method['description'] }}</p>
                                    </div>
                                </label>
                                @php $firstActive = false; @endphp
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">หมายเหตุ</h2>
                    <textarea name="notes" rows="3" placeholder="หมายเหตุเพิ่มเติม (ถ้ามี)"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('notes') }}</textarea>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-24">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">รายการสั่งซื้อ</h2>

                    <div class="space-y-3 mb-6">
                        @foreach($cart->items as $item)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">
                                    {{ $item->product->name }} x {{ $item->quantity }}
                                </span>
                                <span class="text-gray-900">{{ number_format($item->price * $item->quantity, 2) }} บาท</span>
                            </div>
                        @endforeach

                        @php
                            $subtotal = $cart->items->sum(fn($item) => $item->price * $item->quantity);
                            $vatRate = config('app.vat_rate', 0.07);
                            $vat = round($subtotal * $vatRate, 2);
                            $total = $subtotal + $vat;
                        @endphp

                        <div class="border-t pt-3 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">ยอดสินค้า</span>
                                <span class="text-gray-700">{{ number_format($subtotal, 2) }} บาท</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">VAT ({{ number_format($vatRate * 100, 0) }}%)</span>
                                <span class="text-gray-700">{{ number_format($vat, 2) }} บาท</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold text-gray-900 pt-2 border-t">
                                <span>ยอดรวมทั้งหมด</span>
                                <span class="text-primary-600">{{ number_format($total, 2) }} บาท</span>
                            </div>
                        </div>
                    </div>

                    <button type="submit"
                            class="block w-full text-center px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-semibold">
                        ยืนยันสั่งซื้อ
                    </button>

                    <p class="mt-4 text-xs text-gray-500 text-center">
                        เมื่อกดยืนยัน ถือว่าคุณยอมรับ<a href="#" class="text-primary-600 hover:underline">ข้อตกลงและเงื่อนไข</a>
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function selectPaymentMethod(radio) {
    // Remove highlight from all options
    document.querySelectorAll('.payment-method-option').forEach(el => {
        el.classList.remove('border-primary-500', 'bg-primary-50');
        el.classList.add('border-gray-300');
    });

    // Add highlight to selected option
    const label = radio.closest('.payment-method-option');
    label.classList.remove('border-gray-300');
    label.classList.add('border-primary-500', 'bg-primary-50');
}
</script>
@endpush
