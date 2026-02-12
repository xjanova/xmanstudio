@extends($adminLayout ?? 'layouts.admin')

@section('page-title', 'ตั้งค่า SMS Payment')

@section('content')
<!-- Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ตั้งค่า SMS Payment Checker</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">ตั้งค่าการเชื่อมต่อแอพ SmsChecker บน Android</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Getting Started -->
    <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-blue-500 to-indigo-600">
            <h2 class="text-lg font-semibold text-white flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                เริ่มต้นใช้งาน
            </h2>
        </div>
        <div class="p-6">
            <div class="space-y-6">
                <!-- Step 1 -->
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <span class="text-blue-600 dark:text-blue-400 font-bold">1</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">ดาวน์โหลดแอพ SmsChecker</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">ติดตั้งแอพ SmsChecker บนมือถือ Android ที่รับ SMS จากธนาคาร</p>
                        <a href="https://github.com/xjanova/SmsChecker/releases/latest" target="_blank" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                            </svg>
                            ดาวน์โหลด APK
                        </a>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <span class="text-blue-600 dark:text-blue-400 font-bold">2</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">สร้างอุปกรณ์ใหม่</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">สร้าง Device ใหม่เพื่อรับ API Key และ QR Code</p>
                        <a href="{{ route('admin.sms-payment.devices.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            สร้างอุปกรณ์ใหม่
                        </a>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <span class="text-blue-600 dark:text-blue-400 font-bold">3</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">สแกน QR Code</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">เปิดแอพ SmsChecker แล้วสแกน QR Code ที่แสดงหลังสร้าง Device เพื่อเชื่อมต่ออัตโนมัติ</p>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <span class="text-blue-600 dark:text-blue-400 font-bold">4</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">ให้สิทธิ์อ่าน SMS</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">อนุญาตให้แอพอ่าน SMS เพื่อตรวจจับการแจ้งเตือนจากธนาคาร</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Server Info -->
    <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                <svg class="w-6 h-6 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                </svg>
                ข้อมูล Server
            </h2>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Server URL</label>
                <div class="flex items-center gap-2">
                    <code class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 rounded-xl text-sm font-mono text-gray-900 dark:text-white overflow-x-auto">
                        {{ config('app.url') }}
                    </code>
                    <button onclick="copyToClipboard('{{ config('app.url') }}')" class="px-3 py-3 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 rounded-xl transition-colors" title="คัดลอก">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">API Endpoint</label>
                <code class="block w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 rounded-xl text-sm font-mono text-gray-900 dark:text-white overflow-x-auto">
                    {{ config('app.url') }}/api/v1/sms-payment/notify
                </code>
            </div>

            <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">สถานะระบบ</h4>
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">อุปกรณ์ที่ Active</span>
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                            {{ $activeDevices }} เครื่อง
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">SMS รับวันนี้</span>
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                            {{ $smsToday }} รายการ
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FCM Configuration -->
<div class="mt-8 rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-orange-500 to-amber-600">
        <h2 class="text-lg font-semibold text-white flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            Firebase Cloud Messaging (FCM)
        </h2>
    </div>
    <div class="p-6">
        {{-- สถานะ FCM --}}
        <div class="mb-6 p-4 rounded-xl {{ $fcmServiceAccount ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800' : 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800' }}">
            <div class="flex items-center gap-3">
                @if($fcmServiceAccount)
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-emerald-700 dark:text-emerald-400">เชื่อมต่อ Firebase แล้ว</p>
                        <p class="text-sm text-emerald-600 dark:text-emerald-500 font-mono">{{ $fcmServiceAccount }}</p>
                    </div>
                @else
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-amber-700 dark:text-amber-400">ยังไม่ได้ตั้งค่า Firebase</p>
                        <p class="text-sm text-amber-600 dark:text-amber-500">อัพโหลด Service Account JSON เพื่อเปิดใช้งาน Push Notification</p>
                    </div>
                @endif
            </div>
        </div>

        <form action="{{ route('admin.sms-payment.settings.fcm') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- FCM Enabled --}}
            <div class="mb-5">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="fcm_enabled" value="0">
                    <input type="checkbox" name="fcm_enabled" value="1" {{ $fcmEnabled ? 'checked' : '' }}
                        class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เปิดใช้งาน FCM Push Notification</span>
                </label>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-8">ส่ง push notification ไปยังแอพ Android เมื่อมีบิลใหม่</p>
            </div>

            {{-- Firebase Project ID --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Firebase Project ID</label>
                <input type="text" name="fcm_project_id" value="{{ old('fcm_project_id', $fcmProjectId) }}"
                    class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-mono text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="your-project-id">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ดูได้จาก Firebase Console → Project Settings → General</p>
            </div>

            {{-- Service Account JSON --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Service Account JSON</label>
                <input type="file" name="fcm_credentials" accept=".json"
                    class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm text-gray-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 dark:file:bg-blue-900/30 dark:file:text-blue-400 hover:file:bg-blue-100 dark:hover:file:bg-blue-900/50">
                @if($fcmCredentialsPath)
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">
                        ไฟล์ปัจจุบัน: {{ basename($fcmCredentialsPath) }}
                    </p>
                @else
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ดาวน์โหลดจาก Firebase Console → Project Settings → Service accounts → Generate new private key</p>
                @endif
            </div>

            {{-- Buttons --}}
            <div class="flex items-center gap-3">
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    บันทึก
                </button>
            </div>
        </form>

        {{-- Test FCM Button (แยกฟอร์ม) --}}
        @if($fcmServiceAccount)
        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
            <form action="{{ route('admin.sms-payment.settings.fcm-test') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-medium rounded-xl transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    ทดสอบส่ง FCM
                </button>
                <span class="text-xs text-gray-500 dark:text-gray-400 ml-3">ส่ง silent push ไปยังอุปกรณ์ที่ active ทั้งหมด</span>
            </form>
        </div>
        @endif

        {{-- คู่มือ --}}
        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
            <a href="https://console.firebase.google.com" target="_blank" class="inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:underline">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
                เปิด Firebase Console
            </a>
        </div>
    </div>
</div>

<!-- Supported Banks -->
<div class="mt-8 rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">ธนาคารที่รองรับ</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">แอพจะตรวจจับ SMS แจ้งเงินเข้าจากธนาคารเหล่านี้อัตโนมัติ</p>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @php
                $banks = config('smschecker.banks', [
                    'kbank' => 'กสิกรไทย',
                    'scb' => 'ไทยพาณิชย์',
                    'bbl' => 'กรุงเทพ',
                    'ktb' => 'กรุงไทย',
                    'bay' => 'กรุงศรี',
                    'ttb' => 'ทีทีบี',
                    'gsb' => 'ออมสิน',
                    'promptpay' => 'พร้อมเพย์',
                ]);
            @endphp
            @foreach($banks as $code => $name)
            <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                <div class="w-10 h-10 rounded-full bg-white dark:bg-gray-600 flex items-center justify-center shadow">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">{{ strtoupper(substr($code, 0, 3)) }}</span>
                </div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $name }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Devices List -->
<div class="mt-8 rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">อุปกรณ์ที่เชื่อมต่อ</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">รายการอุปกรณ์ทั้งหมดที่ลงทะเบียนในระบบ</p>
        </div>
        <a href="{{ route('admin.sms-payment.devices.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            เพิ่มอุปกรณ์
        </a>
    </div>
    <div class="overflow-x-auto">
        @if($devices->isEmpty())
        <div class="p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีอุปกรณ์</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">สร้างอุปกรณ์ใหม่เพื่อเริ่มต้นใช้งาน SMS Payment</p>
            <a href="{{ route('admin.sms-payment.devices.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors">
                สร้างอุปกรณ์แรก
            </a>
        </div>
        @else
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">อุปกรณ์</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">โหมด</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Active ล่าสุด</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($devices as $device)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold mr-3">
                                {{ strtoupper(substr($device->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $device->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ Str::limit($device->device_id, 15) }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @if($device->status === 'active')
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                            <span class="w-2 h-2 bg-emerald-500 rounded-full mr-1.5 animate-pulse"></span>
                            Active
                        </span>
                        @else
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                            Inactive
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <form action="{{ route('admin.sms-payment.devices.update', $device) }}" method="POST" class="inline">
                            @csrf
                            @method('PUT')
                            <select name="approval_mode"
                                onchange="this.form.submit()"
                                class="text-xs font-semibold rounded-full px-2.5 py-1 border-0 cursor-pointer focus:ring-2 focus:ring-indigo-500
                                    {{ ($device->approval_mode ?? 'auto') === 'auto' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : '' }}
                                    {{ ($device->approval_mode ?? 'auto') === 'manual' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : '' }}
                                    {{ ($device->approval_mode ?? 'auto') === 'smart' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : '' }}">
                                <option value="auto" {{ ($device->approval_mode ?? 'auto') === 'auto' ? 'selected' : '' }}>🟢 Auto</option>
                                <option value="manual" {{ ($device->approval_mode ?? 'auto') === 'manual' ? 'selected' : '' }}>🟡 Manual</option>
                                <option value="smart" {{ ($device->approval_mode ?? 'auto') === 'smart' ? 'selected' : '' }}>🔵 Smart</option>
                            </select>
                        </form>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        {{ $device->last_active_at?->diffForHumans() ?? 'ยังไม่เคย' }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('admin.sms-payment.devices.show', $device) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 font-medium rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors text-sm">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                            </svg>
                            QR Code
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

@push('scripts')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Show toast or notification
        alert('คัดลอกแล้ว!');
    });
}
</script>
@endpush
@endsection
