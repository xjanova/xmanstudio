@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ตั้งค่าอีเมล')
@section('page-title', 'ตั้งค่าอีเมล')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 p-8 shadow-2xl">
        <div class="relative z-10">
            <div class="flex items-center space-x-4">
                <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-white">ตั้งค่าอีเมล (Resend)</h2>
                    <p class="text-purple-100 mt-1">ตั้งค่าระบบส่งอีเมลสำหรับแจ้งยืนยันคำสั่งซื้อ, ชำระเงิน, และ License Keys</p>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
        <p class="text-green-700 dark:text-green-300 text-sm flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </p>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
        <p class="text-red-700 dark:text-red-300 text-sm flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('error') }}
        </p>
    </div>
    @endif

    <form action="{{ route('admin.email-settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Resend API Settings -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        Resend API
                    </h3>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="mail_enabled" value="0">
                        <input type="checkbox" name="mail_enabled" value="1" class="sr-only peer" {{ ($settings['mail_enabled'] ?? true) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-500 peer-checked:bg-indigo-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                    </label>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Resend API Key</label>
                        <input type="password" name="resend_api_key" placeholder="{{ $settings['resend_api_key'] ? '••••••••••••••••• (ตั้งค่าแล้ว)' : 're_xxxxxxxxxx' }}" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            รับ API Key จาก <a href="https://resend.com/api-keys" target="_blank" class="text-indigo-500 hover:underline">resend.com/api-keys</a>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">อีเมลผู้ส่ง (From)</label>
                            <input type="email" name="mail_from_address" value="{{ $settings['mail_from_address'] ?? '' }}" placeholder="noreply@xman4289.com" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อผู้ส่ง (From Name)</label>
                            <input type="text" name="mail_from_name" value="{{ $settings['mail_from_name'] ?? '' }}" placeholder="XMANStudio" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-indigo-50 dark:bg-indigo-900/20">
                <h4 class="text-sm font-semibold text-indigo-800 dark:text-indigo-300 mb-3">อีเมลที่ระบบส่งอัตโนมัติ</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div class="flex items-center gap-2 text-indigo-700 dark:text-indigo-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                        ยืนยันคำสั่งซื้อ (Order Confirmation)
                    </div>
                    <div class="flex items-center gap-2 text-indigo-700 dark:text-indigo-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                        ชำระเงินสำเร็จ + License Keys (Payment Confirmed)
                    </div>
                </div>
            </div>

            <div class="p-6 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                    บันทึกการตั้งค่า
                </button>
            </div>
        </div>
    </form>

    <!-- Test Email Section -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                ส่งอีเมลทดสอบ
            </h3>
            <form action="{{ route('admin.email-settings.test') }}" method="POST" class="flex flex-col sm:flex-row gap-3">
                @csrf
                <input type="email" name="test_email" value="{{ auth()->user()->email }}" placeholder="email@example.com" required class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-medium rounded-lg transition-colors whitespace-nowrap">
                    ส่งอีเมลทดสอบ
                </button>
            </form>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">ส่งอีเมลทดสอบเพื่อตรวจสอบว่าระบบทำงานปกติ</p>
        </div>
    </div>
</div>
@endsection
