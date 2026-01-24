@extends('layouts.app')

@section('title', 'เติมเงิน Wallet')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Gradient Header Banner -->
    <div class="bg-gradient-to-r from-purple-600 via-purple-500 to-indigo-600 dark:from-purple-700 dark:via-purple-600 dark:to-indigo-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="mb-4 md:mb-0">
                    <nav class="flex items-center text-purple-200 text-sm mb-2">
                        <a href="{{ route('user.wallet.index') }}" class="hover:text-white transition-colors duration-200">กระเป๋าเงิน</a>
                        <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span class="text-white">เติมเงิน</span>
                    </nav>
                    <h1 class="text-3xl font-bold text-white flex items-center">
                        <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        เติมเงิน Wallet
                    </h1>
                    <p class="mt-1 text-purple-100">ยอดคงเหลือ: <span class="font-bold text-white text-xl">฿{{ number_format($wallet->balance, 2) }}</span></p>
                </div>
                <a href="{{ route('user.wallet.index') }}" class="inline-flex items-center px-5 py-2.5 bg-white/10 text-white font-semibold rounded-xl hover:bg-white/20 transition-all duration-200 backdrop-blur-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    กลับ
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Bonus Tiers -->
        @if($bonusTiers->count() > 0)
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 rounded-2xl shadow-xl p-6 mb-6 border border-green-200 dark:border-green-800">
            <h3 class="text-lg font-semibold text-green-800 dark:text-green-200 flex items-center mb-4">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                </svg>
                โปรโมชั่นเติมเงิน
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($bonusTiers as $tier)
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 text-center shadow-md hover:shadow-lg transition-shadow duration-200 border border-green-100 dark:border-green-900">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $tier->range_label }}</p>
                    <span class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-green-500 to-emerald-500 text-white text-sm font-medium rounded-full shadow-md">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                        </svg>
                        รับโบนัส {{ $tier->bonus_label }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Topup Form -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    ข้อมูลการเติมเงิน
                </h3>
            </div>
            <div class="p-6">
                <form action="{{ route('user.wallet.submit-topup') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- Amount -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            จำนวนเงินที่ต้องการเติม <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400 text-lg font-medium">฿</span>
                            </div>
                            <input type="number" name="amount" id="amount"
                                   class="block w-full pl-10 pr-4 py-4 text-xl font-semibold border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('amount') border-red-500 @enderror"
                                   value="{{ old('amount') }}" min="100" max="100000" step="1" required placeholder="ขั้นต่ำ 100 บาท">
                        </div>
                        @error('amount')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        <!-- Quick amounts -->
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach([100, 300, 500, 1000, 2000, 5000] as $amount)
                            <button type="button" class="quick-amount px-4 py-2 text-sm font-medium text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/50 transition-all duration-200 border border-purple-200 dark:border-purple-700" data-amount="{{ $amount }}">
                                ฿{{ number_format($amount) }}
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Bonus Preview -->
                    <div id="bonusPreview" class="hidden mb-6 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 rounded-xl p-4 border border-green-200 dark:border-green-800">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-emerald-500 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                                </svg>
                            </div>
                            <h4 class="font-semibold text-green-800 dark:text-green-200">ยอดที่จะได้รับ</h4>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-3">
                                <p class="text-sm text-gray-500 dark:text-gray-400">โบนัสที่จะได้รับ</p>
                                <p class="text-xl font-bold text-green-600 dark:text-green-400" id="bonusAmount">฿0</p>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-3">
                                <p class="text-sm text-gray-500 dark:text-gray-400">ยอดรวมที่จะได้</p>
                                <p class="text-xl font-bold text-purple-600 dark:text-purple-400" id="totalAmount">฿0</p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            ช่องทางชำระเงิน <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <label class="relative">
                                <input type="radio" class="peer hidden" name="payment_method" id="bank_transfer" value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'checked' : '' }} required>
                                <div class="p-4 border-2 border-gray-200 dark:border-gray-600 rounded-xl cursor-pointer transition-all duration-200 hover:border-purple-300 dark:hover:border-purple-600 peer-checked:border-purple-500 peer-checked:bg-purple-50 dark:peer-checked:bg-purple-900/30 peer-checked:ring-2 peer-checked:ring-purple-500/20">
                                    <div class="text-center">
                                        <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                        </div>
                                        <p class="font-medium text-gray-900 dark:text-white">โอนเงิน</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">ธนาคารทุกธนาคาร</p>
                                    </div>
                                </div>
                            </label>
                            <label class="relative">
                                <input type="radio" class="peer hidden" name="payment_method" id="promptpay" value="promptpay" {{ old('payment_method', 'promptpay') === 'promptpay' ? 'checked' : '' }}>
                                <div class="p-4 border-2 border-gray-200 dark:border-gray-600 rounded-xl cursor-pointer transition-all duration-200 hover:border-purple-300 dark:hover:border-purple-600 peer-checked:border-purple-500 peer-checked:bg-purple-50 dark:peer-checked:bg-purple-900/30 peer-checked:ring-2 peer-checked:ring-purple-500/20">
                                    <div class="text-center">
                                        <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <p class="font-medium text-gray-900 dark:text-white">PromptPay</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">สแกน QR Code</p>
                                    </div>
                                </div>
                            </label>
                            <label class="relative">
                                <input type="radio" class="peer hidden" name="payment_method" id="truemoney" value="truemoney" {{ old('payment_method') === 'truemoney' ? 'checked' : '' }}>
                                <div class="p-4 border-2 border-gray-200 dark:border-gray-600 rounded-xl cursor-pointer transition-all duration-200 hover:border-purple-300 dark:hover:border-purple-600 peer-checked:border-purple-500 peer-checked:bg-purple-50 dark:peer-checked:bg-purple-900/30 peer-checked:ring-2 peer-checked:ring-purple-500/20">
                                    <div class="text-center">
                                        <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-red-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                            </svg>
                                        </div>
                                        <p class="font-medium text-gray-900 dark:text-white">TrueMoney</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Wallet</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                        @error('payment_method')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Payment Reference -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            หมายเลขอ้างอิง/หมายเหตุ
                        </label>
                        <input type="text" name="payment_reference"
                               class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('payment_reference') border-red-500 @enderror"
                               value="{{ old('payment_reference') }}" placeholder="เช่น เลขที่รายการโอน">
                        @error('payment_reference')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Payment Proof -->
                    <div class="mb-8">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            หลักฐานการชำระเงิน (สลิป)
                        </label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-xl hover:border-purple-400 dark:hover:border-purple-500 transition-colors duration-200">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                    <label for="payment_proof" class="relative cursor-pointer bg-white dark:bg-gray-700 rounded-md font-medium text-purple-600 dark:text-purple-400 hover:text-purple-500 focus-within:outline-none">
                                        <span>อัพโหลดไฟล์</span>
                                        <input id="payment_proof" name="payment_proof" type="file" class="sr-only" accept="image/*">
                                    </label>
                                    <p class="pl-1">หรือลากไฟล์มาวางที่นี่</p>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">รองรับไฟล์ภาพ ขนาดไม่เกิน 5MB</p>
                            </div>
                        </div>
                        @error('payment_proof')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="w-full flex items-center justify-center px-6 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        ส่งคำขอเติมเงิน
                    </button>
                </form>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="mt-6 bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    ข้อมูลการชำระเงิน
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-500 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <h4 class="font-semibold text-green-800 dark:text-green-200">ธนาคารกสิกรไทย</h4>
                        </div>
                        <div class="space-y-2 text-sm">
                            <p class="text-gray-600 dark:text-gray-400"><span class="font-medium text-gray-800 dark:text-gray-200">ชื่อบัญชี:</span> XmanStudio</p>
                            <p class="text-gray-600 dark:text-gray-400"><span class="font-medium text-gray-800 dark:text-gray-200">เลขบัญชี:</span> <code class="px-2 py-1 bg-white dark:bg-gray-700 rounded text-purple-600 dark:text-purple-400 font-mono">xxx-x-xxxxx-x</code></p>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl p-4 border border-purple-200 dark:border-purple-800">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-500 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h4 class="font-semibold text-purple-800 dark:text-purple-200">PromptPay</h4>
                        </div>
                        <div class="space-y-2 text-sm">
                            <p class="text-gray-600 dark:text-gray-400"><span class="font-medium text-gray-800 dark:text-gray-200">หมายเลข:</span> <code class="px-2 py-1 bg-white dark:bg-gray-700 rounded text-purple-600 dark:text-purple-400 font-mono">xxx-xxx-xxxx</code></p>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex items-start p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                    <svg class="w-5 h-5 text-amber-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-amber-800 dark:text-amber-200">หลังจากชำระเงินแล้ว กรุณารอการตรวจสอบ 1-24 ชั่วโมง (ในวันทำการ)</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('amount');
    const bonusPreview = document.getElementById('bonusPreview');
    const bonusAmount = document.getElementById('bonusAmount');
    const totalAmount = document.getElementById('totalAmount');

    // Quick amount buttons
    document.querySelectorAll('.quick-amount').forEach(btn => {
        btn.addEventListener('click', function() {
            amountInput.value = this.dataset.amount;
            updateBonus();
            // Add active state
            document.querySelectorAll('.quick-amount').forEach(b => {
                b.classList.remove('bg-purple-600', 'text-white', 'border-purple-600');
                b.classList.add('bg-purple-50', 'text-purple-600', 'border-purple-200');
            });
            this.classList.remove('bg-purple-50', 'text-purple-600', 'border-purple-200');
            this.classList.add('bg-purple-600', 'text-white', 'border-purple-600');
        });
    });

    // Update bonus on amount change
    amountInput.addEventListener('input', updateBonus);

    function updateBonus() {
        const amount = parseFloat(amountInput.value) || 0;

        if (amount >= 100) {
            fetch('{{ route("user.wallet.bonus-preview") }}?amount=' + amount)
                .then(r => r.json())
                .then(data => {
                    if (data.bonus > 0) {
                        bonusPreview.classList.remove('hidden');
                        bonusAmount.textContent = '฿' + data.bonus.toLocaleString('th-TH', {minimumFractionDigits: 2});
                        totalAmount.textContent = '฿' + data.total.toLocaleString('th-TH', {minimumFractionDigits: 2});
                    } else {
                        bonusPreview.classList.add('hidden');
                    }
                });
        } else {
            bonusPreview.classList.add('hidden');
        }
    }
});
</script>
@endpush
@endsection
