@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ตั้งค่าโลโก้และ Favicon')
@section('page-title', 'ตั้งค่าโลโก้และ Favicon')

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
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-fuchsia-600 via-pink-600 to-rose-600 p-8 shadow-2xl">
        <div class="absolute top-0 left-0 w-72 h-72 bg-fuchsia-400 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-pink-400 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-rose-400 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-4000"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">ตั้งค่าโลโก้และ Favicon</h1>
                    <p class="text-pink-100 text-lg">จัดการ Branding และรูปลักษณ์ของเว็บไซต์</p>
                </div>
                <div class="hidden md:block">
                    <div class="w-16 h-16 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
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

    <!-- Logo Settings -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-fuchsia-400 to-pink-600 flex items-center justify-center mr-4 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">โลโก้เว็บไซต์</h3>
        </div>

        <form action="{{ route('admin.branding.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="space-y-6">
                <!-- Current Logo Preview -->
                @if($settings['site_logo'])
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">โลโก้ปัจจุบัน</label>
                        <div class="flex items-center space-x-4">
                            <div class="p-4 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 border border-gray-200 dark:border-gray-600 rounded-xl">
                                <img src="{{ asset('storage/' . $settings['site_logo']) }}"
                                     alt="Current Logo"
                                     class="max-h-16 object-contain">
                            </div>
                            <form action="{{ route('admin.branding.logo.delete') }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบโลโก้นี้?')"
                                        class="px-4 py-2 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl hover:from-red-600 hover:to-rose-700 transition-all duration-300 font-medium shadow-lg text-sm">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    ลบโลโก้
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 border-2 border-dashed border-gray-300 dark:border-gray-500 rounded-xl p-8 text-center">
                        <div class="w-16 h-16 mx-auto rounded-full bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-500 flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">ยังไม่มีโลโก้</p>
                    </div>
                @endif

                <!-- Logo Upload -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">อัปโหลดโลโก้ใหม่</label>
                    <input type="file"
                           name="logo"
                           accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp"
                           class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-gradient-to-r file:from-fuchsia-500 file:to-pink-600 file:text-white hover:file:from-fuchsia-600 hover:file:to-pink-700 file:cursor-pointer file:shadow-lg file:transition-all file:duration-300">
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">รองรับไฟล์: PNG, JPG, JPEG, SVG, WEBP (ขนาดไม่เกิน 2MB)</p>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="px-6 py-3 bg-gradient-to-r from-fuchsia-500 to-pink-600 text-white rounded-xl hover:from-fuchsia-600 hover:to-pink-700 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        บันทึกโลโก้
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Favicon Settings -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-400 to-purple-600 flex items-center justify-center mr-4 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Favicon</h3>
        </div>

        <form action="{{ route('admin.branding.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="space-y-6">
                <!-- Current Favicon Preview -->
                @if($settings['site_favicon'])
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Favicon ปัจจุบัน</label>
                        <div class="flex items-center space-x-4">
                            <div class="p-3 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 border border-gray-200 dark:border-gray-600 rounded-xl">
                                <img src="{{ asset('storage/' . $settings['site_favicon']) }}"
                                     alt="Current Favicon"
                                     class="h-8 w-8">
                            </div>
                            <form action="{{ route('admin.branding.favicon.delete') }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบ favicon นี้?')"
                                        class="px-4 py-2 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl hover:from-red-600 hover:to-rose-700 transition-all duration-300 font-medium shadow-lg text-sm">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    ลบ Favicon
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 border-2 border-dashed border-gray-300 dark:border-gray-500 rounded-xl p-8 text-center">
                        <div class="w-12 h-12 mx-auto rounded-lg bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-500 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">ยังไม่มี favicon</p>
                    </div>
                @endif

                <!-- Favicon Upload -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">อัปโหลด Favicon ใหม่</label>
                    <input type="file"
                           name="favicon"
                           accept="image/png,image/jpeg,image/jpg,image/x-icon,image/svg+xml"
                           class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-gradient-to-r file:from-violet-500 file:to-purple-600 file:text-white hover:file:from-violet-600 hover:file:to-purple-700 file:cursor-pointer file:shadow-lg file:transition-all file:duration-300">
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">รองรับไฟล์: PNG, JPG, JPEG, ICO, SVG (ขนาดไม่เกิน 512KB, แนะนำ 32x32px)</p>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="px-6 py-3 bg-gradient-to-r from-violet-500 to-purple-600 text-white rounded-xl hover:from-violet-600 hover:to-purple-700 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        บันทึก Favicon
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Help Section -->
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-6">
        <div class="flex items-start">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center mr-4 shadow-lg flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h4 class="font-bold text-blue-900 dark:text-blue-100 mb-3 text-lg">คำแนะนำ</h4>
                <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-2">
                    <li class="flex items-start">
                        <span class="w-2 h-2 rounded-full bg-blue-500 mr-2 mt-1.5 flex-shrink-0"></span>
                        <span><strong>โลโก้:</strong> แนะนำให้ใช้ไฟล์ PNG หรือ SVG พื้นหลังโปร่งใส ขนาดประมาณ 200-300px กว้าง</span>
                    </li>
                    <li class="flex items-start">
                        <span class="w-2 h-2 rounded-full bg-blue-500 mr-2 mt-1.5 flex-shrink-0"></span>
                        <span><strong>Favicon:</strong> แนะนำให้ใช้ไฟล์ PNG ขนาด 32x32px หรือ ICO ขนาด 16x16px</span>
                    </li>
                    <li class="flex items-start">
                        <span class="w-2 h-2 rounded-full bg-blue-500 mr-2 mt-1.5 flex-shrink-0"></span>
                        <span>หลังจากอัปโหลดแล้ว โลโก้และ favicon จะถูกแสดงในทุกหน้าของเว็บไซต์</span>
                    </li>
                    <li class="flex items-start">
                        <span class="w-2 h-2 rounded-full bg-blue-500 mr-2 mt-1.5 flex-shrink-0"></span>
                        <span>หากต้องการเปลี่ยนกลับเป็นค่าเริ่มต้น ให้กดปุ่ม "ลบโลโก้" หรือ "ลบ Favicon"</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
