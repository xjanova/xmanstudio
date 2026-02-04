@extends($adminLayout ?? 'layouts.admin')

@section('page-title', 'เพิ่มอุปกรณ์ SMS')

@section('content')
<!-- Header -->
<div class="flex items-center gap-4 mb-8">
    <a href="{{ route('admin.sms-payment.devices') }}" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">เพิ่มอุปกรณ์ SMS</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">สร้างอุปกรณ์ใหม่สำหรับรับ SMS จากธนาคาร</p>
    </div>
</div>

<div class="max-w-2xl">
    <form action="{{ route('admin.sms-payment.devices.store') }}" method="POST" class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
        @csrf

        <div class="p-6 space-y-6">
            <!-- Device Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ชื่ออุปกรณ์ <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name') }}"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200"
                    placeholder="เช่น Samsung A54 สำนักงาน">
                @error('name')
                <p class="mt-2 text-sm text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Approval Mode -->
            <div>
                <label for="approval_mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    โหมดการอนุมัติ
                </label>
                <select name="approval_mode" id="approval_mode"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200">
                    <option value="auto" {{ old('approval_mode') === 'auto' ? 'selected' : '' }}>Auto - อนุมัติอัตโนมัติเมื่อตรงยอด</option>
                    <option value="manual" {{ old('approval_mode') === 'manual' ? 'selected' : '' }}>Manual - รอ Admin ยืนยัน</option>
                    <option value="smart" {{ old('approval_mode') === 'smart' ? 'selected' : '' }}>Smart - Auto สำหรับยอดต่ำ, Manual สำหรับยอดสูง</option>
                </select>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    โหมดนี้สามารถเปลี่ยนได้ที่แอพ Android ในภายหลัง
                </p>
                @error('approval_mode')
                <p class="mt-2 text-sm text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    รายละเอียดเพิ่มเติม
                </label>
                <textarea name="description" id="description" rows="3"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200"
                    placeholder="รายละเอียดอุปกรณ์ (ไม่บังคับ)">{{ old('description') }}</textarea>
                @error('description')
                <p class="mt-2 text-sm text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Info Box -->
            <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800">
                <div class="flex">
                    <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="text-sm text-blue-700 dark:text-blue-300">
                        <p class="font-medium mb-1">วิธีการใช้งาน:</p>
                        <ol class="list-decimal list-inside space-y-1 text-blue-600 dark:text-blue-400">
                            <li>สร้างอุปกรณ์ในหน้านี้</li>
                            <li>สแกน QR Code ด้วยแอพ SmsChecker บน Android</li>
                            <li>เปิดให้แอพอ่าน SMS จากธนาคาร</li>
                            <li>ระบบจะเริ่มตรวจสอบการชำระเงินอัตโนมัติ</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 flex items-center justify-end gap-3">
            <a href="{{ route('admin.sms-payment.devices') }}" class="px-5 py-2.5 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200">
                ยกเลิก
            </a>
            <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-medium rounded-xl hover:from-emerald-600 hover:to-teal-700 transition-all duration-200 shadow-lg shadow-emerald-500/30">
                สร้างอุปกรณ์
            </button>
        </div>
    </form>
</div>
@endsection
