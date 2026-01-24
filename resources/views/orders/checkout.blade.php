@extends($publicLayout ?? 'layouts.app')

@section('title', 'ชำระเงิน - XMAN Studio')

@section('content')
<div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <!-- Premium Header -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-primary-600 via-primary-500 to-teal-500 p-6 sm:p-8 mb-8 shadow-xl">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
        <div class="relative">
            <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                ชำระเงิน
            </h1>
            <p class="mt-2 text-white/80">ตรวจสอบรายการและยืนยันการสั่งซื้อ</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('orders.store') }}" method="POST" id="checkoutForm">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Customer Info & Payment Methods -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Customer Info Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        ข้อมูลผู้สั่งซื้อ
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                            <input type="text" name="customer_name" value="{{ old('customer_name', auth()->user()->name ?? '') }}" required
                                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">อีเมล <span class="text-red-500">*</span></label>
                            <input type="email" name="customer_email" value="{{ old('customer_email', auth()->user()->email ?? '') }}" required
                                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เบอร์โทรศัพท์</label>
                            <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}"
                                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition">
                        </div>
                    </div>
                </div>

                <!-- Payment Methods Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        วิธีการชำระเงิน
                    </h2>

                    <div class="space-y-3">
                        <!-- Wallet Payment Option -->
                        @auth
                            @if($wallet && $wallet->balance > 0)
                            <label class="flex items-center p-4 border-2 border-purple-200 dark:border-purple-800 rounded-xl cursor-pointer hover:border-purple-500 transition payment-method-option bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20"
                                   data-method="wallet">
                                <input type="radio" name="payment_method" value="wallet"
                                       class="text-purple-600 focus:ring-purple-500"
                                       onchange="selectPaymentMethod(this)">
                                <div class="ml-3 flex-1">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center mr-3">
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <span class="font-semibold text-gray-900 dark:text-white">Wallet</span>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">ชำระด้วยยอดเงินใน Wallet</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-lg font-bold text-purple-600 dark:text-purple-400">฿{{ number_format($wallet->balance, 2) }}</span>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">ยอดคงเหลือ</p>
                                        </div>
                                    </div>
                                </div>
                            </label>
                            @endif
                        @endauth

                        <!-- Other Payment Methods -->
                        @php $firstActive = !isset($wallet) || $wallet->balance <= 0; @endphp
                        @foreach($paymentMethods as $method)
                            @if($method['is_active'])
                                <label class="flex items-center p-4 border-2 rounded-xl cursor-pointer hover:border-primary-500 transition payment-method-option {{ $firstActive ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-600' }}"
                                       data-method="{{ $method['id'] }}">
                                    <input type="radio" name="payment_method" value="{{ $method['id'] }}" {{ $firstActive ? 'checked' : '' }}
                                           class="text-primary-600 focus:ring-primary-500"
                                           onchange="selectPaymentMethod(this)">
                                    <div class="ml-3 flex-1">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center mr-3">
                                                @if($method['icon'] === 'promptpay')
                                                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                                    </svg>
                                                @elseif($method['icon'] === 'bank')
                                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                                    </svg>
                                                @endif
                                            </div>
                                            <div>
                                                <span class="font-semibold text-gray-900 dark:text-white">{{ $method['name'] }}</span>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $method['description'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                                @php $firstActive = false; @endphp
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Notes Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        หมายเหตุ
                    </h2>
                    <textarea name="notes" rows="3" placeholder="หมายเหตุเพิ่มเติม (ถ้ามี)"
                              class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition">{{ old('notes') }}</textarea>
                </div>
            </div>

            <!-- Order Summary Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700 sticky top-24">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        รายการสั่งซื้อ
                    </h2>

                    <!-- Cart Items -->
                    <div class="space-y-3 mb-6">
                        @foreach($cart->items as $item)
                            <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->product->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">x {{ $item->quantity }}</p>
                                </div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">฿{{ number_format($item->price * $item->quantity, 2) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Coupon Section -->
                    <div class="mb-6 p-4 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            โค้ดส่วนลด
                        </label>

                        @if($appliedCoupon ?? false)
                            <!-- Applied Coupon Display -->
                            <div class="flex items-center justify-between bg-green-100 dark:bg-green-900/30 px-3 py-2 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="font-mono font-semibold text-green-700 dark:text-green-300">{{ $appliedCoupon->code }}</span>
                                    <span class="text-sm text-green-600 dark:text-green-400">({{ $appliedCoupon->discount_label }})</span>
                                </div>
                                <button type="button" onclick="removeCoupon()" class="text-red-500 hover:text-red-700 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            <input type="hidden" name="coupon_code" value="{{ $appliedCoupon->code }}">
                        @else
                            <!-- Coupon Input -->
                            <div class="flex gap-2">
                                <input type="text" id="couponInput" placeholder="ใส่โค้ดส่วนลด"
                                       class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-white uppercase">
                                <button type="button" onclick="applyCoupon()"
                                        class="px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-lg hover:from-amber-600 hover:to-orange-600 text-sm font-medium transition">
                                    ใช้
                                </button>
                            </div>
                            <p id="couponError" class="mt-2 text-sm text-red-500 hidden"></p>
                            <p id="couponSuccess" class="mt-2 text-sm text-green-500 hidden"></p>
                        @endif
                    </div>

                    <!-- Price Summary -->
                    @php
                        $subtotal = $cart->items->sum(fn($item) => $item->price * $item->quantity);
                        $vatRate = config('app.vat_rate', 0.07);
                        $vat = round($subtotal * $vatRate, 2);
                        $discount = $couponDiscount ?? 0;
                        $total = max(0, $subtotal + $vat - $discount);
                    @endphp

                    <div class="space-y-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">ยอดสินค้า</span>
                            <span class="text-gray-700 dark:text-gray-300">฿{{ number_format($subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">VAT ({{ number_format($vatRate * 100, 0) }}%)</span>
                            <span class="text-gray-700 dark:text-gray-300">฿{{ number_format($vat, 2) }}</span>
                        </div>
                        @if($discount > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-green-600 dark:text-green-400 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                                ส่วนลด
                            </span>
                            <span class="text-green-600 dark:text-green-400 font-semibold">-฿{{ number_format($discount, 2) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between text-xl font-bold text-gray-900 dark:text-white pt-3 border-t border-gray-200 dark:border-gray-700">
                            <span>ยอดรวมทั้งหมด</span>
                            <span class="text-primary-600 dark:text-primary-400" id="totalAmount">฿{{ number_format($total, 2) }}</span>
                        </div>
                    </div>

                    <!-- Wallet Balance Warning -->
                    @auth
                        @if($wallet ?? false)
                            <div id="walletWarning" class="hidden mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                                <p class="text-sm text-red-600 dark:text-red-400 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    ยอด Wallet ไม่เพียงพอ
                                </p>
                                <a href="{{ route('user.wallet.topup') }}" class="mt-2 inline-block text-sm text-red-700 dark:text-red-300 underline hover:no-underline">
                                    เติมเงิน Wallet →
                                </a>
                            </div>
                        @endif
                    @endauth

                    <!-- Submit Button -->
                    <button type="submit" id="submitBtn"
                            class="mt-6 block w-full text-center px-6 py-3.5 bg-gradient-to-r from-primary-600 to-teal-600 text-white rounded-xl hover:from-primary-700 hover:to-teal-700 font-semibold shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5">
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            ยืนยันสั่งซื้อ
                        </span>
                    </button>

                    <p class="mt-4 text-xs text-gray-500 dark:text-gray-400 text-center">
                        เมื่อกดยืนยัน ถือว่าคุณยอมรับ
                        <a href="{{ route('terms') }}" class="text-primary-600 hover:underline">ข้อตกลงและเงื่อนไข</a>
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const totalAmount = {{ $total }};
const walletBalance = {{ $wallet->balance ?? 0 }};

function selectPaymentMethod(radio) {
    // Remove highlight from all options
    document.querySelectorAll('.payment-method-option').forEach(el => {
        el.classList.remove('border-primary-500', 'border-purple-500', 'bg-primary-50', 'dark:bg-primary-900/20');
        el.classList.add('border-gray-200', 'dark:border-gray-600');
    });

    // Add highlight to selected option
    const label = radio.closest('.payment-method-option');
    label.classList.remove('border-gray-200', 'dark:border-gray-600');

    if (radio.value === 'wallet') {
        label.classList.add('border-purple-500');
        checkWalletBalance();
    } else {
        label.classList.add('border-primary-500', 'bg-primary-50', 'dark:bg-primary-900/20');
        hideWalletWarning();
    }
}

function checkWalletBalance() {
    const warning = document.getElementById('walletWarning');
    const submitBtn = document.getElementById('submitBtn');

    if (warning && totalAmount > walletBalance) {
        warning.classList.remove('hidden');
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
    } else if (warning) {
        hideWalletWarning();
    }
}

function hideWalletWarning() {
    const warning = document.getElementById('walletWarning');
    const submitBtn = document.getElementById('submitBtn');

    if (warning) {
        warning.classList.add('hidden');
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

function applyCoupon() {
    const code = document.getElementById('couponInput').value.trim().toUpperCase();
    const errorEl = document.getElementById('couponError');
    const successEl = document.getElementById('couponSuccess');

    if (!code) {
        errorEl.textContent = 'กรุณาใส่โค้ดส่วนลด';
        errorEl.classList.remove('hidden');
        successEl.classList.add('hidden');
        return;
    }

    // Apply coupon via form submission (simple approach)
    const form = document.getElementById('checkoutForm');
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'apply_coupon';
    input.value = code;
    form.appendChild(input);

    // Store in session via redirect
    window.location.href = '{{ route("orders.checkout") }}?coupon=' + encodeURIComponent(code);
}

function removeCoupon() {
    window.location.href = '{{ route("orders.checkout") }}?remove_coupon=1';
}

// Check wallet balance on page load if wallet is selected
document.addEventListener('DOMContentLoaded', function() {
    const walletRadio = document.querySelector('input[name="payment_method"][value="wallet"]');
    if (walletRadio && walletRadio.checked) {
        checkWalletBalance();
    }
});
</script>
@endpush
