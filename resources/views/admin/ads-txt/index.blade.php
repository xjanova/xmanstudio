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
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 p-8 shadow-2xl">
        <div class="absolute top-0 left-0 w-72 h-72 bg-emerald-400 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-teal-400 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-cyan-400 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-4000"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">Ads.txt Management</h1>
                    <p class="text-teal-100 text-lg">จัดการไฟล์ Ads.txt สำหรับ Google Ads และป้องกันการฉ้อโกงโฆษณา</p>
                </div>
                <div class="hidden md:block">
                    <div class="w-16 h-16 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-6 py-4 rounded-xl flex items-center shadow-lg">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center mr-4">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-6 py-4 rounded-xl flex items-center shadow-lg">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-400 to-rose-500 flex items-center justify-center mr-4">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            </div>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-6 py-4 rounded-xl shadow-lg">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Info Box -->
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-6">
        <div class="flex items-start">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center mr-4 shadow-lg flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div>
                <h4 class="font-bold text-blue-900 dark:text-blue-100 mb-3 text-lg">เกี่ยวกับ ads.txt</h4>
                <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-2">
                    <li class="flex items-start">
                        <span class="w-2 h-2 rounded-full bg-blue-500 mr-2 mt-1.5 flex-shrink-0"></span>
                        <span>ads.txt (Authorized Digital Sellers) คือไฟล์ที่ใช้ระบุผู้ขายโฆษณาที่ได้รับอนุญาตบนเว็บไซต์ของคุณ</span>
                    </li>
                    <li class="flex items-start">
                        <span class="w-2 h-2 rounded-full bg-blue-500 mr-2 mt-1.5 flex-shrink-0"></span>
                        <span>ไฟล์นี้ช่วยป้องกันการฉ้อโกงโฆษณาและเพิ่มความน่าเชื่อถือให้กับเว็บไซต์</span>
                    </li>
                    <li class="flex items-start">
                        <span class="w-2 h-2 rounded-full bg-blue-500 mr-2 mt-1.5 flex-shrink-0"></span>
                        <span>URL: <a href="{{ route('ads-txt') }}" target="_blank" class="underline font-semibold hover:text-blue-900 dark:hover:text-blue-50">{{ url('/ads.txt') }}</a></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.ads-txt.update') }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Enable/Disable Toggle -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center mr-4 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">เปิด/ปิดใช้งาน Ads.txt</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            เมื่อปิดใช้งาน ไฟล์ ads.txt จะไม่สามารถเข้าถึงได้ (404 Not Found)
                        </p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="enabled" value="1" class="sr-only peer" {{ $setting->enabled ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-emerald-500 peer-checked:to-teal-600"></div>
                    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">{{ $setting->enabled ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}</span>
                </label>
            </div>
        </div>

        <!-- Content Editor -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-cyan-400 to-blue-600 flex items-center justify-center mr-4 shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">เนื้อหา Ads.txt</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        ระบุรายการผู้ขายโฆษณาที่ได้รับอนุญาต (หนึ่งรายการต่อบรรทัด)
                    </p>
                </div>
                @if($setting->enabled && !empty($setting->content))
                    <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold bg-gradient-to-r from-green-400 to-emerald-500 text-white shadow-lg">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Active
                    </span>
                @endif
            </div>

            <div class="mb-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 p-3 rounded-xl border border-gray-200 dark:border-gray-600">
                    <strong class="text-gray-700 dark:text-gray-300">รูปแบบ:</strong> domain, publisher_id, relationship, certification_authority_id
                </p>
            </div>

            <textarea
                name="content"
                rows="15"
                class="w-full font-mono text-sm border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl p-4 focus:ring-4 focus:ring-emerald-300 dark:focus:ring-emerald-800 focus:border-emerald-500 dark:focus:border-emerald-400 transition-all duration-300"
                placeholder="# Google AdSense
google.com, pub-0000000000000000, DIRECT, f08c47fec0942fa0

# Google Ad Manager
google.com, pub-0000000000000000, RESELLER, f08c47fec0942fa0

# Add more entries here..."
            >{{ old('content', $setting->content) }}</textarea>

            <div class="mt-4 bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
                <p class="text-sm font-semibold text-yellow-900 dark:text-yellow-100 mb-2">💡 คำแนะนำ:</p>
                <ul class="text-sm text-yellow-800 dark:text-yellow-200 space-y-1.5">
                    <li class="flex items-start">
                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 mr-2 mt-1.5 flex-shrink-0"></span>
                        <span>บรรทัดที่ขึ้นต้นด้วย # คือหมายเหตุ (comment)</span>
                    </li>
                    <li class="flex items-start">
                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 mr-2 mt-1.5 flex-shrink-0"></span>
                        <span>ใช้ DIRECT สำหรับบัญชีที่คุณเป็นเจ้าของโดยตรง</span>
                    </li>
                    <li class="flex items-start">
                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 mr-2 mt-1.5 flex-shrink-0"></span>
                        <span>ใช้ RESELLER สำหรับบัญชีที่ขายต่อจากผู้อื่น</span>
                    </li>
                    <li class="flex items-start">
                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 mr-2 mt-1.5 flex-shrink-0"></span>
                        <span>Google AdSense ใช้ certification authority ID: f08c47fec0942fa0</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('ads-txt') }}" target="_blank"
               class="px-6 py-3 bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 text-gray-700 dark:text-gray-200 rounded-xl hover:from-gray-200 hover:to-gray-300 dark:hover:from-gray-600 dark:hover:to-gray-500 transition-all duration-300 font-medium shadow-lg hover:shadow-xl">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                ดูไฟล์ Ads.txt
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl hover:from-emerald-600 hover:to-teal-700 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl focus:ring-4 focus:ring-emerald-300 dark:focus:ring-emerald-800">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                บันทึกการเปลี่ยนแปลง
            </button>
        </div>
    </form>

    <!-- How to get Google AdSense Publisher ID -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-400 to-indigo-600 flex items-center justify-center mr-4 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">วิธีหา Publisher ID จาก Google AdSense</h3>
        </div>

        <div class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-purple-500 to-indigo-600 text-white rounded-xl flex items-center justify-center mr-3 mt-0.5 font-bold shadow-lg">1</span>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">เข้าสู่ระบบ Google AdSense</p>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">ไปที่ <a href="https://www.google.com/adsense/" target="_blank" class="text-emerald-600 dark:text-emerald-400 hover:underline font-medium">https://www.google.com/adsense/</a></p>
                </div>
            </div>

            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-purple-500 to-indigo-600 text-white rounded-xl flex items-center justify-center mr-3 mt-0.5 font-bold shadow-lg">2</span>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">ไปที่เมนู Settings (การตั้งค่า)</p>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">คลิกที่เมนูด้านซ้าย แล้วเลือก Settings > Account > Account information</p>
                </div>
            </div>

            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-purple-500 to-indigo-600 text-white rounded-xl flex items-center justify-center mr-3 mt-0.5 font-bold shadow-lg">3</span>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">คัดลอก Publisher ID</p>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">จะเป็นรูปแบบ <code class="bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 px-3 py-1 rounded-lg font-mono text-sm border border-gray-300 dark:border-gray-500">pub-0000000000000000</code></p>
                </div>
            </div>

            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-purple-500 to-indigo-600 text-white rounded-xl flex items-center justify-center mr-3 mt-0.5 font-bold shadow-lg">4</span>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">ใส่ในไฟล์ ads.txt</p>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">ใช้รูปแบบ: <code class="bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 px-3 py-1 rounded-lg font-mono text-xs border border-gray-300 dark:border-gray-500">google.com, pub-XXXXXXXXXXXXXXXX, DIRECT, f08c47fec0942fa0</code></p>
                </div>
            </div>
        </div>

        <!-- Example -->
        <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                <svg class="w-5 h-5 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                ตัวอย่างไฟล์ ads.txt ที่ถูกต้อง:
            </h4>
            <pre class="bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-xl p-4 text-xs overflow-x-auto border-2 border-gray-300 dark:border-gray-500 font-mono text-gray-900 dark:text-gray-100"># Google AdSense - บัญชีหลัก
google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0

# Google Ad Manager (ถ้ามี)
google.com, pub-9876543210987654, RESELLER, f08c47fec0942fa0

# Other ad networks (ถ้ามี)
# partner.com, partner-id, DIRECT, partner-cert-id</pre>
        </div>
    </div>
</div>
@endsection
