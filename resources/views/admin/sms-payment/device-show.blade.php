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
                    {!! $qrCode !!}
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
                <code class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 rounded">{{ Str::limit($device->device_id, 20) }}</code>
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
                <span class="px-3 py-1 text-xs font-semibold rounded-full
                    @switch($device->approval_mode)
                        @case('auto') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 @break
                        @case('manual') bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 @break
                        @case('smart') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 @break
                        @default bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400
                    @endswitch">
                    {{ ucfirst($device->approval_mode ?? 'auto') }}
                </span>
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

<!-- API Information (for debugging) -->
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
                <strong class="text-gray-700 dark:text-gray-300">Headers:</strong><br>
                <code>X-Api-Key: [API Key จาก QR Code]</code><br>
                <code>X-Device-Id: {{ $device->device_id }}</code><br>
                <code>X-Signature: [HMAC-SHA256 signature]</code>
            </p>
        </div>
    </div>
</div>
@endsection
