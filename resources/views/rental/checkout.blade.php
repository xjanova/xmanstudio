@extends('layouts.app')

@section('title', 'ชำระเงิน - ' . $package->display_name . ' - XMAN Studio')

@section('content')
<div class="bg-gray-50 min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 text-sm text-gray-500">
                <li><a href="{{ route('rental.packages') }}" class="hover:text-primary-600">แพ็กเกจ</a></li>
                <li><span>/</span></li>
                <li class="text-gray-900">ชำระเงิน</li>
            </ol>
        </nav>

        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="md:flex">
                <!-- Order Summary -->
                <div class="md:w-1/3 bg-gray-50 p-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">สรุปคำสั่งซื้อ</h2>

                    <div class="space-y-4">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $package->display_name }}</h3>
                            <p class="text-sm text-gray-500">{{ $package->duration_text }}</p>
                        </div>

                        <div class="pt-4 border-t border-gray-200">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">ราคาปกติ</span>
                                <span class="text-gray-900">฿{{ number_format($package->original_price ?? $package->price) }}</span>
                            </div>

                            @if($package->original_price && $package->original_price > $package->price)
                                <div class="flex justify-between text-sm mt-2">
                                    <span class="text-gray-600">ส่วนลด</span>
                                    <span class="text-red-600">-฿{{ number_format($package->original_price - $package->price) }}</span>
                                </div>
                            @endif

                            <div id="promoDiscount" class="flex justify-between text-sm mt-2 hidden">
                                <span class="text-gray-600">โค้ดส่วนลด</span>
                                <span class="text-red-600" id="promoDiscountAmount"></span>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-200">
                            <div class="flex justify-between">
                                <span class="font-semibold text-gray-900">รวมทั้งสิ้น</span>
                                <span class="text-2xl font-bold text-primary-600" id="totalAmount">฿{{ number_format($package->price) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Promo Code -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">มีโค้ดส่วนลด?</label>
                        <div class="flex space-x-2">
                            <input type="text" id="promoCode"
                                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                   placeholder="กรอกโค้ด">
                            <button type="button" onclick="applyPromoCode()"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                ใช้
                            </button>
                        </div>
                        <p id="promoMessage" class="mt-2 text-sm hidden"></p>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="md:w-2/3 p-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">เลือกวิธีชำระเงิน</h2>

                    <form action="{{ route('rental.subscribe', $package) }}" method="POST" id="checkoutForm">
                        @csrf
                        <input type="hidden" name="promo_code" id="promoCodeInput">

                        <div class="space-y-4">
                            <!-- PromptPay -->
                            <label class="block cursor-pointer">
                                <input type="radio" name="payment_method" value="promptpay" class="sr-only peer" checked>
                                <div class="flex items-center p-4 border-2 rounded-lg peer-checked:border-primary-500 peer-checked:bg-primary-50">
                                    <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="font-semibold text-gray-900">พร้อมเพย์ (PromptPay)</p>
                                        <p class="text-sm text-gray-500">สแกน QR Code เพื่อชำระเงิน</p>
                                    </div>
                                </div>
                            </label>

                            <!-- Bank Transfer -->
                            <label class="block cursor-pointer">
                                <input type="radio" name="payment_method" value="bank_transfer" class="sr-only peer">
                                <div class="flex items-center p-4 border-2 rounded-lg peer-checked:border-primary-500 peer-checked:bg-primary-50">
                                    <div class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="font-semibold text-gray-900">โอนเงินธนาคาร</p>
                                        <p class="text-sm text-gray-500">โอนเงินแล้วแนบสลิป</p>
                                    </div>
                                </div>
                            </label>

                            <!-- Credit Card (if enabled) -->
                            @if(config('payment.card.enabled', false))
                                <label class="block cursor-pointer">
                                    <input type="radio" name="payment_method" value="credit_card" class="sr-only peer">
                                    <div class="flex items-center p-4 border-2 rounded-lg peer-checked:border-primary-500 peer-checked:bg-primary-50">
                                        <div class="flex-shrink-0 w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                            </svg>
                                        </div>
                                        <div class="ml-4">
                                            <p class="font-semibold text-gray-900">บัตรเครดิต/เดบิต</p>
                                            <p class="text-sm text-gray-500">Visa, Mastercard, JCB</p>
                                        </div>
                                    </div>
                                </label>
                            @endif
                        </div>

                        <div class="mt-8">
                            <button type="submit"
                                    class="w-full py-4 px-6 rounded-lg text-white bg-primary-600 hover:bg-primary-700 transition-colors font-semibold text-lg">
                                ดำเนินการชำระเงิน
                            </button>
                        </div>

                        <p class="mt-4 text-sm text-gray-500 text-center">
                            การดำเนินการต่อถือว่าคุณยอมรับ
                            <a href="/terms" class="text-primary-600 hover:underline">ข้อกำหนดการใช้งาน</a>
                            และ
                            <a href="/privacy" class="text-primary-600 hover:underline">นโยบายความเป็นส่วนตัว</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const originalPrice = {{ $package->price }};
    let currentDiscount = 0;

    function applyPromoCode() {
        const code = document.getElementById('promoCode').value.trim();
        if (!code) return;

        fetch('{{ route("rental.validate-promo") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                code: code,
                package_id: {{ $package->id }}
            })
        })
        .then(response => response.json())
        .then(data => {
            const msgEl = document.getElementById('promoMessage');
            msgEl.classList.remove('hidden', 'text-green-600', 'text-red-600');

            if (data.valid) {
                msgEl.textContent = data.message;
                msgEl.classList.add('text-green-600');
                currentDiscount = data.discount_amount;
                document.getElementById('promoCodeInput').value = code;
                document.getElementById('promoDiscount').classList.remove('hidden');
                document.getElementById('promoDiscountAmount').textContent = '-฿' + numberFormat(data.discount_amount);
            } else {
                msgEl.textContent = data.message;
                msgEl.classList.add('text-red-600');
                currentDiscount = 0;
                document.getElementById('promoCodeInput').value = '';
                document.getElementById('promoDiscount').classList.add('hidden');
            }

            updateTotal();
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    function updateTotal() {
        const total = Math.max(0, originalPrice - currentDiscount);
        document.getElementById('totalAmount').textContent = '฿' + numberFormat(total);
    }

    function numberFormat(num) {
        return num.toLocaleString('th-TH');
    }
</script>
@endsection
