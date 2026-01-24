@extends($adminLayout ?? 'layouts.admin')

@section('title', 'SEO Management')
@section('page-title', 'SEO Management (เพิ่มประสิทธิภาพการค้นหา Google)')

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
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-500 p-8 shadow-2xl">
        <div class="absolute top-0 left-0 w-72 h-72 bg-yellow-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-orange-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-amber-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>

        <div class="relative">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <h1 class="text-3xl font-bold text-white">SEO Management</h1>
                    </div>
                    <p class="text-orange-100 text-lg">ปรับแต่งการตั้งค่า SEO เพื่อเพิ่มอันดับในผลการค้นหา Google</p>
                </div>
                <div class="hidden md:block">
                    <div class="w-24 h-24 rounded-2xl bg-white/10 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-xl flex items-center shadow-lg">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center mr-3">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-xl flex items-center shadow-lg">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-400 to-rose-500 flex items-center justify-center mr-3">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
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
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 border border-blue-200 dark:border-blue-800 rounded-2xl p-5 shadow-lg">
        <div class="flex">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center mr-4 flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div>
                <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-1">เกี่ยวกับ SEO</h4>
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    SEO (Search Engine Optimization) ช่วยให้เว็บไซต์ของคุณปรากฏใน Google Search และเพิ่มโอกาสที่ลูกค้าจะค้นหาและเข้าถึงบริการของคุณ
                </p>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.seo.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- Basic SEO -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-400 to-amber-600 flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">ข้อมูล SEO พื้นฐาน</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ตั้งค่าข้อมูลพื้นฐานสำหรับการค้นหา</p>
                </div>
            </div>

            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ชื่อเว็บไซต์ *</label>
                    <input type="text" name="site_name" value="{{ old('site_name', $setting->site_name) }}"
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl p-3 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all" required>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">ชื่อเว็บไซต์ของคุณ (จะแสดงในผลลัพธ์การค้นหา)</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">หัวข้อหลัก (Title Tag)</label>
                    <input type="text" name="site_title" value="{{ old('site_title', $setting->site_title) }}"
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl p-3 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all"
                           maxlength="60"
                           placeholder="XMAN Studio - รับทำเว็บไซต์ ออกแบบเว็บไซต์">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">หัวข้อหลักที่จะแสดงใน Google (แนะนำ 50-60 ตัวอักษร)</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">คำอธิบาย (Meta Description)</label>
                    <textarea name="site_description" rows="3"
                              class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl p-3 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all"
                              maxlength="160"
                              placeholder="XMAN Studio ให้บริการรับทำเว็บไซต์ ออกแบบเว็บไซต์ พัฒนาระบบ CMS ครบวงจร ด้วยทีมงานมืออาชีพ ราคาย่อมเยา">{{ old('site_description', $setting->site_description) }}</textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">คำอธิบายที่จะแสดงใน Google (แนะนำ 150-160 ตัวอักษร)</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">คำค้นหา (Keywords)</label>
                    <input type="text" name="site_keywords" value="{{ old('site_keywords', $setting->site_keywords) }}"
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl p-3 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all"
                           placeholder="รับทำเว็บไซต์, ออกแบบเว็บไซต์, CMS, พัฒนาเว็บ">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">คำค้นหาหลักที่เกี่ยวข้องกับเว็บไซต์ (คั่นด้วยเครื่องหมาย ,)</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ผู้เขียน</label>
                    <input type="text" name="site_author" value="{{ old('site_author', $setting->site_author) }}"
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl p-3 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all">
                </div>
            </div>
        </div>

        <!-- Social Media -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18.77 7.46H14.5v-1.9c0-.9.6-1.1 1-1.1h3V.5h-4.33C10.24.5 9.5 3.44 9.5 5.32v2.15h-3v4h3v12h5v-12h3.85l.42-4z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Social Media (Facebook, Twitter)</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ตั้งค่าการแสดงผลเมื่อแชร์บนโซเชียลมีเดีย</p>
                </div>
            </div>

            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">รูปภาพ Open Graph (OG Image)</label>
                    @if($setting->og_image)
                        <div class="mb-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                            <img src="{{ asset('storage/' . $setting->og_image) }}" alt="OG Image" class="max-w-xs rounded-lg shadow">
                        </div>
                    @endif
                    <div class="relative">
                        <input type="file" name="og_image" accept="image/*"
                               class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">รูปที่จะแสดงเมื่อแชร์ลิงก์บน Facebook, Twitter (แนะนำ 1200x630 pixels)</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Twitter Site (@username)</label>
                        <input type="text" name="twitter_site" value="{{ old('twitter_site', $setting->twitter_site) }}"
                               class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                               placeholder="@xmanstudio">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Twitter Creator (@username)</label>
                        <input type="text" name="twitter_creator" value="{{ old('twitter_creator', $setting->twitter_creator) }}"
                               class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                               placeholder="@username">
                    </div>
                </div>
            </div>
        </div>

        <!-- Google Services -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-400 via-yellow-500 to-green-500 flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Google Services</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">เชื่อมต่อกับบริการต่างๆ ของ Google</p>
                </div>
            </div>

            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Google Site Verification</label>
                    <input type="text" name="google_site_verification" value="{{ old('google_site_verification', $setting->google_site_verification) }}"
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl p-3 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all font-mono text-sm"
                           placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">รหัสยืนยันจาก Google Search Console</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Google Analytics ID</label>
                    <input type="text" name="google_analytics_id" value="{{ old('google_analytics_id', $setting->google_analytics_id) }}"
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl p-3 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all font-mono text-sm"
                           placeholder="G-XXXXXXXXXX">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Google Analytics 4 Measurement ID</p>
                </div>
            </div>
        </div>

        <!-- Sitemap -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Sitemap.xml</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ไฟล์แผนผังเว็บไซต์สำหรับ Google Search</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="sitemap_enabled" value="1" class="sr-only peer" {{ $setting->sitemap_enabled ? 'checked' : '' }}>
                    <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-emerald-500 peer-checked:to-teal-500"></div>
                    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">เปิดใช้งาน</span>
                </label>
            </div>

            <div class="flex items-center space-x-3">
                <a href="{{ url('/sitemap.xml') }}" target="_blank"
                   class="px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-all font-medium flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    ดู Sitemap
                </a>
                <button type="button" onclick="generateSitemap()"
                        class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-xl hover:from-emerald-600 hover:to-teal-600 transition-all font-medium shadow-lg hover:shadow-xl flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    สร้าง Sitemap ใหม่
                </button>
            </div>
        </div>

        <!-- Robots.txt -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-400 to-purple-600 flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Robots.txt</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ควบคุมว่าหน้าไหนสามารถถูก index ได้</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="robots_txt_enabled" value="1" class="sr-only peer" {{ $setting->robots_txt_enabled ? 'checked' : '' }}>
                    <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-violet-300 dark:peer-focus:ring-violet-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-violet-500 peer-checked:to-purple-500"></div>
                    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">เปิดใช้งาน</span>
                </label>
            </div>

            <textarea name="robots_txt_content" rows="10"
                      class="w-full font-mono text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl p-4 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all"
                      placeholder="User-agent: *&#10;Allow: /&#10;Disallow: /admin/&#10;&#10;Sitemap: {{ url('/sitemap.xml') }}">{{ old('robots_txt_content', $setting->robots_txt_content) }}</textarea>
        </div>

        <!-- Structured Data -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-cyan-400 to-blue-600 flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Structured Data (JSON-LD)</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ข้อมูลโครงสร้างสำหรับ Google Rich Results</p>
                </div>
            </div>

            <textarea name="structured_data_json" rows="15"
                      class="w-full font-mono text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl p-4 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all"
                      placeholder='{"@context": "https://schema.org", "@type": "Organization", "name": "XMAN Studio"}'>{{ old('structured_data_json', json_encode($setting->structured_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) }}</textarea>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">รูปแบบ JSON-LD สำหรับ Schema.org</p>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end">
            <button type="submit"
                    class="px-8 py-3 bg-gradient-to-r from-orange-500 via-amber-500 to-yellow-500 text-white rounded-xl hover:from-orange-600 hover:via-amber-600 hover:to-yellow-600 focus:ring-4 focus:ring-orange-300 transition-all font-semibold shadow-lg hover:shadow-xl flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                บันทึกการตั้งค่า SEO
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function generateSitemap() {
    if (confirm('สร้าง sitemap.xml ใหม่?')) {
        window.location.href = '{{ route("admin.seo.generate-sitemap") }}';
    }
}
</script>
@endpush
@endsection
