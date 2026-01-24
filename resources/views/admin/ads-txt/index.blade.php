@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Ads.txt Management')
@section('page-title', 'Ads.txt Management (จัดการไฟล์ Ads.txt สำหรับ Google Ads)')

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
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-600 via-orange-600 to-red-500 p-8 shadow-2xl">
        <div class="absolute top-0 left-0 w-72 h-72 bg-yellow-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-orange-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-red-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>

        <div class="relative">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h1 class="text-3xl font-bold text-white">Ads.txt Management</h1>
                    </div>
                    <p class="text-orange-100 text-lg">จัดการไฟล์ Ads.txt สำหรับ Google Ads และผู้ขายโฆษณา</p>
                </div>
                <a href="{{ route('ads-txt') }}" target="_blank"
                   class="inline-flex items-center px-6 py-3 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all font-semibold shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    ดูไฟล์ Ads.txt
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-xl flex items-center shadow-lg">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-xl flex items-center shadow-lg">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-xl shadow-lg">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Info Box -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-5 shadow-xl">
        <div class="flex">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center mr-4 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div>
                <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-1">เกี่ยวกับ ads.txt</h4>
                <div class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                    <p>ads.txt (Authorized Digital Sellers) คือไฟล์ที่ใช้ระบุผู้ขายโฆษณาที่ได้รับอนุญาตบนเว็บไซต์ของคุณ</p>
                    <p>ไฟล์นี้ช่วยป้องกันการฉ้อโกงโฆษณาและเพิ่มความน่าเชื่อถือให้กับเว็บไซต์</p>
                    <p>URL: <a href="{{ route('ads-txt') }}" target="_blank" class="underline font-medium hover:text-blue-600 dark:hover:text-blue-300">{{ url('/ads.txt') }}</a></p>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.ads-txt.update') }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Enable/Disable Toggle -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">เปิด/ปิดใช้งาน Ads.txt</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        เมื่อปิดใช้งาน ไฟล์ ads.txt จะไม่สามารถเข้าถึงได้ (404 Not Found)
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="enabled" value="1" class="sr-only peer" {{ $setting->enabled ? 'checked' : '' }}>
                    <div class="w-14 h-7 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 dark:peer-focus:ring-orange-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-orange-500 peer-checked:to-amber-500"></div>
                    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">{{ $setting->enabled ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}</span>
                </label>
            </div>
        </div>

        <!-- Content Editor -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">เนื้อหา Ads.txt</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        ระบุรายการผู้ขายโฆษณาที่ได้รับอนุญาต (หนึ่งรายการต่อบรรทัด)
                    </p>
                </div>
                @if($setting->enabled && !empty($setting->content))
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gradient-to-r from-green-400 to-emerald-500 text-white shadow">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Active
                    </span>
                @endif
            </div>

            <div class="mb-3">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                    <strong>รูปแบบ:</strong> domain, publisher_id, relationship, certification_authority_id
                </p>
            </div>

            <textarea
                name="content"
                rows="15"
                class="w-full font-mono text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl p-4 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all"
                placeholder="# Google AdSense
google.com, pub-0000000000000000, DIRECT, f08c47fec0942fa0

# Google Ad Manager
google.com, pub-0000000000000000, RESELLER, f08c47fec0942fa0

# Add more entries here..."
            >{{ old('content', $setting->content) }}</textarea>

            <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl text-xs text-gray-600 dark:text-gray-300">
                <strong class="text-gray-900 dark:text-white">คำแนะนำ:</strong>
                <ul class="list-disc list-inside mt-2 space-y-1">
                    <li>บรรทัดที่ขึ้นต้นด้วย # คือหมายเหตุ (comment)</li>
                    <li>ใช้ DIRECT สำหรับบัญชีที่คุณเป็นเจ้าของโดยตรง</li>
                    <li>ใช้ RESELLER สำหรับบัญชีที่ขายต่อจากผู้อื่น</li>
                    <li>Google AdSense ใช้ certification authority ID: f08c47fec0942fa0</li>
                </ul>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('ads-txt') }}" target="_blank"
               class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors font-medium shadow">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                ดูไฟล์ Ads.txt
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-orange-500 to-amber-600 text-white rounded-xl hover:from-orange-600 hover:to-amber-700 focus:ring-4 focus:ring-orange-300 transition-all font-medium shadow-lg">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                บันทึกการเปลี่ยนแปลง
            </button>
        </div>
    </form>

    <!-- How to get Google AdSense Publisher ID -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center mr-3 shadow-lg">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            วิธีหา Publisher ID จาก Google AdSense
        </h3>

        <div class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-orange-500 to-amber-600 text-white rounded-xl flex items-center justify-center mr-3 mt-0.5 text-sm font-bold shadow">1</span>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">เข้าสู่ระบบ Google AdSense</p>
                    <p class="text-gray-600 dark:text-gray-400">ไปที่ <a href="https://www.google.com/adsense/" target="_blank" class="text-orange-600 dark:text-orange-400 hover:underline">https://www.google.com/adsense/</a></p>
                </div>
            </div>

            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-orange-500 to-amber-600 text-white rounded-xl flex items-center justify-center mr-3 mt-0.5 text-sm font-bold shadow">2</span>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">ไปที่เมนู Settings (การตั้งค่า)</p>
                    <p class="text-gray-600 dark:text-gray-400">คลิกที่เมนูด้านซ้าย แล้วเลือก Settings > Account > Account information</p>
                </div>
            </div>

            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-orange-500 to-amber-600 text-white rounded-xl flex items-center justify-center mr-3 mt-0.5 text-sm font-bold shadow">3</span>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">คัดลอก Publisher ID</p>
                    <p class="text-gray-600 dark:text-gray-400">จะเป็นรูปแบบ <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">pub-0000000000000000</code></p>
                </div>
            </div>

            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-orange-500 to-amber-600 text-white rounded-xl flex items-center justify-center mr-3 mt-0.5 text-sm font-bold shadow">4</span>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">ใส่ในไฟล์ ads.txt</p>
                    <p class="text-gray-600 dark:text-gray-400">ใช้รูปแบบ: <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs">google.com, pub-XXXXXXXXXXXXXXXX, DIRECT, f08c47fec0942fa0</code></p>
                </div>
            </div>
        </div>

        <!-- Example -->
        <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
            <h4 class="font-medium text-gray-900 dark:text-white mb-2">ตัวอย่างไฟล์ ads.txt ที่ถูกต้อง:</h4>
            <pre class="bg-gray-100 dark:bg-gray-700 rounded-xl p-4 text-xs overflow-x-auto text-gray-800 dark:text-gray-200"># Google AdSense - บัญชีหลัก
google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0

# Google Ad Manager (ถ้ามี)
google.com, pub-9876543210987654, RESELLER, f08c47fec0942fa0

# Other ad networks (ถ้ามี)
# partner.com, partner-id, DIRECT, partner-cert-id</pre>
        </div>
    </div>
</div>
@endsection
