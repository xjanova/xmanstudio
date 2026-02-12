@extends($adminLayout ?? 'layouts.admin')

@section('page-title', 'อุปกรณ์: ' . $device->name)

@section('content')
<!-- Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.sms-payment.devices') }}" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $device->name }}</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">รายละเอียดและ QR Code สำหรับเชื่อมต่อ</p>
        </div>
    </div>
    <div class="flex gap-3">
        <form action="{{ route('admin.sms-payment.devices.regenerate-key', $device) }}" method="POST" onsubmit="return confirm('การสร้าง API Key ใหม่จะทำให้อุปกรณ์ที่เชื่อมต่ออยู่ใช้งานไม่ได้ ต้องสแกน QR Code ใหม่')">
            @csrf
            <button type="submit" class="inline-flex items-center px-4 py-2.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 font-medium rounded-xl hover:bg-amber-200 dark:hover:bg-amber-900/50 transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                สร้าง Key ใหม่
            </button>
        </form>
        <form action="{{ route('admin.sms-payment.devices.toggle', $device) }}" method="POST">
            @csrf
            <button type="submit" class="inline-flex items-center px-4 py-2.5 {{ $device->status === 'active' ? 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 hover:bg-rose-200 dark:hover:bg-rose-900/50' : 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-200 dark:hover:bg-emerald-900/50' }} font-medium rounded-xl transition-all duration-200">
                {{ $device->status === 'active' ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}
            </button>
        </form>
    </div>
</div>

@if(session('success'))
<div class="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800">
    <div class="flex items-center">
        <svg class="w-5 h-5 text-emerald-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span class="text-emerald-700 dark:text-emerald-300 font-medium">{{ session('success') }}</span>
    </div>
</div>
@endif

@if(session('new_device'))
<div class="mb-6 p-4 rounded-xl bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800">
    <div class="flex">
        <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div class="text-sm text-blue-700 dark:text-blue-300">
            <p class="font-medium mb-1">อุปกรณ์ถูกสร้างเรียบร้อย!</p>
            <p class="text-blue-600 dark:text-blue-400">บันทึก API Key และ Secret Key ไว้ เพราะจะไม่แสดงอีกหลังจากออกจากหน้านี้</p>
        </div>
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- QR Code Card -->
    <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">QR Code สำหรับเชื่อมต่อ</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">สแกนด้วยแอพ SmsChecker บน Android</p>
        </div>
        <div class="p-6">
            <div class="flex flex-col items-center">
                <!-- QR Code -->
                <div class="p-4 bg-white rounded-2xl shadow-lg mb-6">
                    <div id="qrcode" class="flex items-center justify-center" style="width: 250px; height: 250px;"></div>
                </div>

                <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-4">
                    สแกน QR Code นี้ในแอพ SmsChecker<br>
                    เพื่อเชื่อมต่ออุปกรณ์กับระบบ
                </p>

                <!-- Warning -->
                <div class="w-full p-4 rounded-xl bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800">
                    <div class="flex">
                        <svg class="w-5 h-5 text-amber-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div class="text-sm text-amber-700 dark:text-amber-300">
                            <p class="font-medium mb-1">คำเตือน</p>
                            <p class="text-amber-600 dark:text-amber-400">อย่าแชร์ QR Code นี้กับผู้อื่น เพราะมี API Key สำหรับเข้าถึงระบบ</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Device Info Card -->
    <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">ข้อมูลอุปกรณ์</h2>
        </div>
        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400">ชื่ออุปกรณ์</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ $device->name }}</span>
            </div>

            <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400">Device ID</span>
                <code class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 rounded">{{ $device->device_id }}</code>
            </div>

            <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400">สถานะ</span>
                @if($device->status === 'active')
                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full mr-2 animate-pulse"></span>
                    Active
                </span>
                @else
                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400">
                    <span class="w-2 h-2 bg-gray-500 rounded-full mr-2"></span>
                    Inactive
                </span>
                @endif
            </div>

            <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400">โหมดการอนุมัติ</span>
                <div class="flex items-center gap-2">
                    <form id="approvalModeForm" action="{{ route('admin.sms-payment.devices.update', $device) }}" method="POST" class="flex items-center gap-2">
                        @csrf
                        @method('PUT')
                        <select name="approval_mode" id="approvalModeSelect"
                            onchange="document.getElementById('approvalModeForm').submit()"
                            class="text-xs font-semibold rounded-full px-3 py-1.5 border-0 cursor-pointer focus:ring-2 focus:ring-indigo-500
                                {{ ($device->approval_mode ?? 'auto') === 'auto' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : '' }}
                                {{ ($device->approval_mode ?? 'auto') === 'manual' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : '' }}
                                {{ ($device->approval_mode ?? 'auto') === 'smart' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : '' }}">
                            <option value="auto" {{ ($device->approval_mode ?? 'auto') === 'auto' ? 'selected' : '' }}>🟢 Auto</option>
                            <option value="manual" {{ ($device->approval_mode ?? 'auto') === 'manual' ? 'selected' : '' }}>🟡 Manual</option>
                            <option value="smart" {{ ($device->approval_mode ?? 'auto') === 'smart' ? 'selected' : '' }}>🔵 Smart</option>
                        </select>
                    </form>
                </div>
            </div>

            <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400">IP Address</span>
                <span class="font-mono text-gray-900 dark:text-white">{{ $device->ip_address ?? '-' }}</span>
            </div>

            <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400">Active ล่าสุด</span>
                <span class="text-gray-900 dark:text-white">{{ $device->last_active_at?->format('d/m/Y H:i') ?? 'ยังไม่เคย' }}</span>
            </div>

            <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400">สร้างเมื่อ</span>
                <span class="text-gray-900 dark:text-white">{{ $device->created_at->format('d/m/Y H:i') }}</span>
            </div>

            @if($device->description)
            <div class="py-3">
                <span class="block text-gray-500 dark:text-gray-400 mb-2">รายละเอียด</span>
                <p class="text-gray-900 dark:text-white">{{ $device->description }}</p>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Manual Configuration (API Keys) -->
<div class="mt-8 rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">ตั้งค่าแบบ Manual</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ใช้สำหรับกรอกข้อมูลในแอพด้วยตนเอง (ไม่ต้องสแกน QR)</p>
            </div>
            <button type="button" onclick="toggleManualConfig()" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <svg id="toggleIcon" class="w-4 h-4 mr-1.5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
                <span id="toggleText">แสดง</span>
            </button>
        </div>
    </div>
    <div id="manualConfig" class="p-6 hidden">
        <div class="grid grid-cols-1 gap-6">
            <!-- Server URL -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Server URL</label>
                <div class="relative">
                    <input type="text" readonly value="{{ config('app.url') }}/api/v1/sms-payment"
                        class="w-full px-4 py-3 pr-12 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-mono text-gray-900 dark:text-white">
                    <button type="button" onclick="copyToClipboard('{{ config('app.url') }}/api/v1/sms-payment', this)"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Device ID -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Device ID</label>
                <div class="relative">
                    <input type="text" readonly value="{{ $device->device_id }}"
                        class="w-full px-4 py-3 pr-12 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-mono text-gray-900 dark:text-white">
                    <button type="button" onclick="copyToClipboard('{{ $device->device_id }}', this)"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- API Key -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    API Key
                    <span class="text-rose-500 text-xs ml-1">(เก็บเป็นความลับ)</span>
                </label>
                <div class="relative">
                    <input type="password" id="apiKeyInput" readonly value="{{ $config['apiKey'] }}"
                        class="w-full px-4 py-3 pr-24 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-mono text-gray-900 dark:text-white">
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-2">
                        <button type="button" onclick="togglePasswordVisibility('apiKeyInput', this)"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                        <button type="button" onclick="copyToClipboard('{{ $config['apiKey'] }}', this)"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Secret Key -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Secret Key
                    <span class="text-rose-500 text-xs ml-1">(เก็บเป็นความลับ)</span>
                </label>
                <div class="relative">
                    <input type="password" id="secretKeyInput" readonly value="{{ $config['secretKey'] }}"
                        class="w-full px-4 py-3 pr-24 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-mono text-gray-900 dark:text-white">
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-2">
                        <button type="button" onclick="togglePasswordVisibility('secretKeyInput', this)"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                        <button type="button" onclick="copyToClipboard('{{ $config['secretKey'] }}', this)"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Warning -->
        <div class="mt-6 p-4 rounded-xl bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800">
            <div class="flex">
                <svg class="w-5 h-5 text-rose-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div class="text-sm text-rose-700 dark:text-rose-300">
                    <p class="font-medium mb-1">คำเตือนด้านความปลอดภัย</p>
                    <ul class="list-disc list-inside text-rose-600 dark:text-rose-400 space-y-1">
                        <li>อย่าเปิดเผย API Key และ Secret Key กับผู้อื่น</li>
                        <li>หาก Key รั่วไหล ให้กด "สร้าง Key ใหม่" ทันที</li>
                        <li>ใช้ HTTPS เสมอเมื่อเชื่อมต่อกับ Server</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- API Information (for developers) -->
<div class="mt-8 rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">ข้อมูล API (สำหรับนักพัฒนา)</h2>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">API Endpoint</label>
                <code class="block w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 rounded-xl text-sm font-mono text-gray-900 dark:text-white overflow-x-auto">
                    {{ config('app.url') }}/api/v1/sms-payment/notify
                </code>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Device ID</label>
                <code class="block w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 rounded-xl text-sm font-mono text-gray-900 dark:text-white overflow-x-auto">
                    {{ $device->device_id }}
                </code>
            </div>
        </div>

        <div class="mt-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-600">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <strong class="text-gray-700 dark:text-gray-300">Required Headers:</strong><br>
                <code class="text-xs">X-Api-Key: [API Key]</code><br>
                <code class="text-xs">X-Device-Id: {{ $device->device_id }}</code><br>
                <code class="text-xs">X-Signature: [HMAC-SHA256 signature]</code><br>
                <code class="text-xs">X-Nonce: [unique nonce]</code><br>
                <code class="text-xs">X-Timestamp: [unix timestamp in milliseconds]</code>
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- QR Code Library (Browser-compatible) -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    // QR Code configuration data
    const qrData = @json($config);

    // Generate QR Code
    document.addEventListener('DOMContentLoaded', function() {
        const qrContainer = document.getElementById('qrcode');
        if (qrContainer && typeof QRCode !== 'undefined') {
            try {
                // Clear container
                qrContainer.innerHTML = '';

                // Create QR Code using qrcodejs library
                // Use correctLevel M (Medium) for better scanning - H makes QR too dense
                new QRCode(qrContainer, {
                    text: JSON.stringify(qrData),
                    width: 280,
                    height: 280,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
            } catch (error) {
                console.error('QR Code error:', error);
                qrContainer.innerHTML = '<p class="text-red-500 text-sm text-center">ไม่สามารถสร้าง QR Code ได้</p>';
            }
        } else {
            console.error('QRCode library not loaded');
            if (qrContainer) {
                qrContainer.innerHTML = '<p class="text-red-500 text-sm text-center">QR Code library ไม่พร้อมใช้งาน</p>';
            }
        }
    });

    // Toggle manual config visibility
    function toggleManualConfig() {
        const config = document.getElementById('manualConfig');
        const icon = document.getElementById('toggleIcon');
        const text = document.getElementById('toggleText');

        if (config.classList.contains('hidden')) {
            config.classList.remove('hidden');
            icon.classList.add('rotate-180');
            text.textContent = 'ซ่อน';
        } else {
            config.classList.add('hidden');
            icon.classList.remove('rotate-180');
            text.textContent = 'แสดง';
        }
    }

    // Toggle password visibility
    function togglePasswordVisibility(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('.eye-icon');

        if (input.type === 'password') {
            input.type = 'text';
            icon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
            `;
        } else {
            input.type = 'password';
            icon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            `;
        }
    }

    // Copy to clipboard
    function copyToClipboard(text, button) {
        navigator.clipboard.writeText(text).then(function() {
            const originalHTML = button.innerHTML;
            button.innerHTML = `
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            `;
            setTimeout(function() {
                button.innerHTML = originalHTML;
            }, 2000);
        }).catch(function(err) {
            console.error('Could not copy text: ', err);
        });
    }
</script>
@endpush
