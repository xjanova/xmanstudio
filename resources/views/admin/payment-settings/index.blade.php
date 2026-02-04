@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ตั้งค่าการชำระเงิน')
@section('page-title', 'ตั้งค่าการชำระเงิน')

@push('styles')
<style>
    .animate-blob {
        animation: blob 7s infinite;
    }
    .animation-delay-2000 {
        animation-delay: 2s;
    }
    .animation-delay-4000 {
        animation-delay: 4s;
    }
    @keyframes blob {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    <!-- Premium Header Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-500 p-8 shadow-2xl">
        <div class="absolute top-0 left-0 w-72 h-72 bg-cyan-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-emerald-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-teal-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>

        <div class="relative">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <h1 class="text-3xl font-bold text-white">ตั้งค่าการชำระเงิน</h1>
                    </div>
                    <p class="text-emerald-100 text-lg">จัดการช่องทางการชำระเงินและบัญชีธนาคาร</p>
                </div>
                <div class="hidden md:block">
                    <div class="w-24 h-24 rounded-2xl bg-white/10 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Methods Settings -->
    <form action="{{ route('admin.payment-settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">ช่องทางการชำระเงิน</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">เปิด/ปิดช่องทางการชำระเงินต่างๆ</p>
                </div>
            </div>

            <div class="space-y-6">
                <!-- PromptPay Settings -->
                <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-gray-900 dark:text-white text-lg">พร้อมเพย์ (PromptPay)</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">เบอร์พร้อมเพย์</label>
                            <input type="text" name="promptpay_number"
                                   value="{{ $settings['promptpay']['promptpay_number'] ?? '' }}"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all"
                                   placeholder="0812345678 หรือ เลขบัตรประชาชน">
                            <p class="mt-1 text-xs text-gray-500">เบอร์โทรศัพท์หรือเลขบัตรประชาชน 13 หลัก</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ชื่อบัญชีพร้อมเพย์</label>
                            <input type="text" name="promptpay_name"
                                   value="{{ $settings['promptpay']['promptpay_name'] ?? '' }}"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all"
                                   placeholder="XMAN Studio Co., Ltd.">
                            <p class="mt-1 text-xs text-gray-500">ชื่อที่จะแสดงหน้าชำระเงิน</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ค่าธรรมเนียม</label>
                            <div class="flex space-x-2">
                                <select name="promptpay_fee_type"
                                        class="px-3 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                                    <option value="fixed" {{ ($settings['fees']['promptpay_fee_type'] ?? 'fixed') === 'fixed' ? 'selected' : '' }}>บาท</option>
                                    <option value="percent" {{ ($settings['fees']['promptpay_fee_type'] ?? '') === 'percent' ? 'selected' : '' }}>%</option>
                                </select>
                                <input type="number" name="promptpay_fee_amount" step="0.01" min="0"
                                       value="{{ $settings['fees']['promptpay_fee_amount'] ?? 0 }}"
                                       class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all"
                                       placeholder="0">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">0 = ไม่มีค่าธรรมเนียม</p>
                        </div>
                        <div class="flex items-end">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="promptpay_enabled" value="1"
                                       {{ ($settings['promptpay']['promptpay_enabled'] ?? true) ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-emerald-500 peer-checked:to-teal-500"></div>
                                <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">เปิดใช้งานพร้อมเพย์</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Bank Transfer Settings -->
                <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-gray-900 dark:text-white text-lg">โอนเงินธนาคาร</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ค่าธรรมเนียม</label>
                            <div class="flex space-x-2">
                                <select name="bank_transfer_fee_type"
                                        class="px-3 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                                    <option value="fixed" {{ ($settings['fees']['bank_transfer_fee_type'] ?? 'fixed') === 'fixed' ? 'selected' : '' }}>บาท</option>
                                    <option value="percent" {{ ($settings['fees']['bank_transfer_fee_type'] ?? '') === 'percent' ? 'selected' : '' }}>%</option>
                                </select>
                                <input type="number" name="bank_transfer_fee_amount" step="0.01" min="0"
                                       value="{{ $settings['fees']['bank_transfer_fee_amount'] ?? 0 }}"
                                       class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all"
                                       placeholder="0">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">0 = ไม่มีค่าธรรมเนียม</p>
                        </div>
                        <div class="flex items-end">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="bank_transfer_enabled" value="1"
                                       {{ ($settings['bank_transfer']['bank_transfer_enabled'] ?? true) ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-amber-500 peer-checked:to-orange-500"></div>
                                <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">เปิดใช้งานโอนเงินธนาคาร</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Card Payment Settings -->
                <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-gray-900 dark:text-white text-lg">บัตรเครดิต/เดบิต</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ค่าธรรมเนียม</label>
                            <div class="flex space-x-2">
                                <select name="card_fee_type"
                                        class="px-3 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all">
                                    <option value="fixed" {{ ($settings['fees']['card_fee_type'] ?? '') === 'fixed' ? 'selected' : '' }}>บาท</option>
                                    <option value="percent" {{ ($settings['fees']['card_fee_type'] ?? 'percent') === 'percent' ? 'selected' : '' }}>%</option>
                                </select>
                                <input type="number" name="card_fee_amount" step="0.01" min="0"
                                       value="{{ $settings['fees']['card_fee_amount'] ?? 3 }}"
                                       class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all"
                                       placeholder="3">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">ค่าเริ่มต้น 3% (Payment Gateway fee)</p>
                        </div>
                        <div class="flex items-end">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="card_payment_enabled" value="1"
                                       {{ ($settings['card']['card_payment_enabled'] ?? false) ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-violet-300 dark:peer-focus:ring-violet-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-violet-500 peer-checked:to-purple-500"></div>
                                <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">เปิดใช้งานบัตรเครดิต/เดบิต</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- General Payment Settings -->
                <div>
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-gray-500 to-slate-600 flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-gray-900 dark:text-white text-lg">ตั้งค่าทั่วไป</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ระยะเวลารอชำระเงิน (ชั่วโมง)</label>
                            <input type="number" name="payment_timeout_hours"
                                   value="{{ $settings['general']['payment_timeout_hours'] ?? 24 }}"
                                   min="1" max="168"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ยกเลิกอัตโนมัติหลัง (ชั่วโมง)</label>
                            <input type="number" name="auto_cancel_pending_after_hours"
                                   value="{{ $settings['general']['auto_cancel_pending_after_hours'] ?? 48 }}"
                                   min="1" max="168"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <button type="submit" class="px-8 py-3 bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 text-white rounded-xl hover:from-emerald-600 hover:via-teal-600 hover:to-cyan-600 transition-all font-semibold shadow-lg hover:shadow-xl flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    บันทึกการตั้งค่า
                </button>
            </div>
        </div>
    </form>

    <!-- Bank Accounts -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">บัญชีธนาคาร</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">จัดการบัญชีธนาคารสำหรับรับชำระเงิน</p>
                </div>
            </div>
            <button type="button" onclick="showAddBankModal()"
                    class="px-5 py-2.5 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl hover:from-blue-600 hover:to-indigo-700 transition-all font-medium shadow-lg hover:shadow-xl flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                เพิ่มบัญชี
            </button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ธนาคาร</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">เลขบัญชี</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ชื่อบัญชี</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สาขา</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($bankAccounts as $account)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $account->bank_name }}</span>
                                    <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300 rounded-full">{{ $account->bank_code }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-mono">
                                {{ $account->account_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ $account->account_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $account->branch ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($account->is_active)
                                    <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-gradient-to-r from-green-400 to-emerald-500 text-white shadow">
                                        เปิดใช้งาน
                                    </span>
                                @else
                                    <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300">
                                        ปิดใช้งาน
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center space-x-2">
                                    <button type="button" onclick="showEditBankModal({{ json_encode($account) }})"
                                            class="px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors font-medium">
                                        แก้ไข
                                    </button>
                                    <form action="{{ route('admin.payment-settings.bank.toggle', $account) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 {{ $account->is_active ? 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 hover:bg-orange-200 dark:hover:bg-orange-900/50' : 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/50' }} rounded-lg transition-colors font-medium">
                                            {{ $account->is_active ? 'ปิด' : 'เปิด' }}
                                        </button>
                                    </form>
                                    <button type="button" onclick="confirmDeleteBank({{ $account->id }}, '{{ $account->bank_name }}')"
                                            class="px-3 py-1.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors font-medium">
                                        ลบ
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 font-medium">ไม่พบบัญชีธนาคาร</p>
                                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">คลิกปุ่ม "เพิ่มบัญชี" เพื่อเริ่มต้น</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Bank Modal -->
<div id="addBankModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full mx-4 transform transition-all">
        <form action="{{ route('admin.payment-settings.bank.store') }}" method="POST">
            @csrf
            <div class="p-6">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">เพิ่มบัญชีธนาคาร</h3>
                </div>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ชื่อธนาคาร</label>
                            <input type="text" name="bank_name" required
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                   placeholder="ธนาคารกสิกรไทย">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">รหัสธนาคาร</label>
                            <input type="text" name="bank_code" required
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                   placeholder="KBANK">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">เลขบัญชี</label>
                        <input type="text" name="account_number" required
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all font-mono"
                               placeholder="xxx-x-xxxxx-x">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ชื่อบัญชี</label>
                        <input type="text" name="account_name" required
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                               placeholder="XMAN Studio Co., Ltd.">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">สาขา</label>
                            <input type="text" name="branch"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ลำดับ</label>
                            <input type="number" name="order" value="0" min="0"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer mt-2">
                        <input type="checkbox" name="is_active" value="1" checked class="sr-only peer">
                        <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-indigo-500"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">เปิดใช้งาน</span>
                    </label>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-end space-x-3 rounded-b-2xl">
                <button type="button" onclick="hideAddBankModal()"
                        class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors font-medium">ยกเลิก</button>
                <button type="submit"
                        class="px-5 py-2.5 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl hover:from-blue-600 hover:to-indigo-700 transition-all font-medium shadow-lg">เพิ่ม</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Bank Modal -->
<div id="editBankModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full mx-4 transform transition-all">
        <form id="editBankForm" method="POST">
            @csrf
            @method('PUT')
            <div class="p-6">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-orange-600 flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">แก้ไขบัญชีธนาคาร</h3>
                </div>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ชื่อธนาคาร</label>
                            <input type="text" name="bank_name" id="edit_bank_name" required
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">รหัสธนาคาร</label>
                            <input type="text" name="bank_code" id="edit_bank_code" required
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">เลขบัญชี</label>
                        <input type="text" name="account_number" id="edit_account_number" required
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ชื่อบัญชี</label>
                        <input type="text" name="account_name" id="edit_account_name" required
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">สาขา</label>
                            <input type="text" name="branch" id="edit_branch"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ลำดับ</label>
                            <input type="number" name="order" id="edit_order" min="0"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer mt-2">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1" class="sr-only peer">
                        <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-amber-500 peer-checked:to-orange-500"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">เปิดใช้งาน</span>
                    </label>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-end space-x-3 rounded-b-2xl">
                <button type="button" onclick="hideEditBankModal()"
                        class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors font-medium">ยกเลิก</button>
                <button type="submit"
                        class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl hover:from-amber-600 hover:to-orange-700 transition-all font-medium shadow-lg">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Bank Modal -->
<div id="deleteBankModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all">
        <form id="deleteBankForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-400 to-rose-600 flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">ยืนยันการลบ</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-300">คุณต้องการลบบัญชี "<span id="deleteBankName" class="font-semibold text-gray-900 dark:text-white"></span>" หรือไม่?</p>
                <p class="text-sm text-red-500 dark:text-red-400 mt-2">การดำเนินการนี้ไม่สามารถย้อนกลับได้</p>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-end space-x-3 rounded-b-2xl">
                <button type="button" onclick="hideDeleteBankModal()"
                        class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors font-medium">ยกเลิก</button>
                <button type="submit"
                        class="px-5 py-2.5 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl hover:from-red-600 hover:to-rose-700 transition-all font-medium shadow-lg">ลบ</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function showAddBankModal() {
        document.getElementById('addBankModal').classList.remove('hidden');
        document.getElementById('addBankModal').classList.add('flex');
    }

    function hideAddBankModal() {
        document.getElementById('addBankModal').classList.add('hidden');
        document.getElementById('addBankModal').classList.remove('flex');
    }

    function showEditBankModal(account) {
        document.getElementById('editBankForm').action = `/admin/payment-settings/bank/${account.id}`;
        document.getElementById('edit_bank_name').value = account.bank_name;
        document.getElementById('edit_bank_code').value = account.bank_code;
        document.getElementById('edit_account_number').value = account.account_number;
        document.getElementById('edit_account_name').value = account.account_name;
        document.getElementById('edit_branch').value = account.branch || '';
        document.getElementById('edit_order').value = account.order || 0;
        document.getElementById('edit_is_active').checked = account.is_active;
        document.getElementById('editBankModal').classList.remove('hidden');
        document.getElementById('editBankModal').classList.add('flex');
    }

    function hideEditBankModal() {
        document.getElementById('editBankModal').classList.add('hidden');
        document.getElementById('editBankModal').classList.remove('flex');
    }

    function confirmDeleteBank(accountId, bankName) {
        document.getElementById('deleteBankForm').action = `/admin/payment-settings/bank/${accountId}`;
        document.getElementById('deleteBankName').textContent = bankName;
        document.getElementById('deleteBankModal').classList.remove('hidden');
        document.getElementById('deleteBankModal').classList.add('flex');
    }

    function hideDeleteBankModal() {
        document.getElementById('deleteBankModal').classList.add('hidden');
        document.getElementById('deleteBankModal').classList.remove('flex');
    }
</script>
@endpush
@endsection
