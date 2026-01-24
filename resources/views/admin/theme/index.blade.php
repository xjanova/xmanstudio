@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ตั้งค่าธีม')
@section('page-title', 'ตั้งค่าธีม')
@section('page-description', 'เลือกธีมหลักที่ต้องการใช้งานสำหรับทั้งระบบ')

@section('content')
<div class="max-w-5xl mx-auto">
    <!-- Premium Header Section -->
    <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl mb-8 animate-fade-in">
        <div class="absolute inset-0 bg-black/10"></div>

        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-0 -left-4 w-48 h-48 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl animate-blob"></div>
            <div class="absolute top-0 -right-4 w-48 h-48 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl animate-blob" style="animation-delay: 2s;"></div>
            <div class="absolute -bottom-8 left-20 w-48 h-48 bg-pink-300 rounded-full mix-blend-multiply filter blur-xl animate-blob" style="animation-delay: 4s;"></div>
        </div>

        <div class="relative px-8 py-10">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2 flex items-center">
                        <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                        </svg>
                        จัดการธีมระบบ
                    </h1>
                    <p class="text-white/80 text-lg">กำหนดธีมหลักสำหรับหน้า Admin และ Customer Dashboard</p>
                </div>
                <div class="hidden lg:block">
                    <div class="w-20 h-20 rounded-2xl bg-white/20 backdrop-blur-lg flex items-center justify-center shadow-xl">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Theme Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-8 animate-fade-in" style="animation-delay: 0.1s;">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg transform hover:scale-105 transition-transform">
                    @if($currentTheme === 'premium')
                        <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z"/>
                        </svg>
                    @else
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    @endif
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">ธีมเริ่มต้นของเว็บไซต์</h2>
                    <p class="text-gray-500 dark:text-gray-400">ผู้ใช้ใหม่จะเห็นธีม <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $themes[$currentTheme]['name'] }}</span> เป็นค่าเริ่มต้น</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    ค่าเริ่มต้น
                </span>
            </div>
        </div>

        @if(isset($adminPersonalTheme) && $adminPersonalTheme && $adminPersonalTheme !== $currentTheme)
        <div class="p-4 bg-amber-50 dark:bg-amber-900/30 rounded-xl border border-amber-200 dark:border-amber-700 mb-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-sm text-amber-700 dark:text-amber-300">
                        <strong>ธีมส่วนตัวของคุณ:</strong> คุณกำลังใช้ธีม <span class="font-semibold">{{ $themes[$adminPersonalTheme]['name'] }}</span> เป็นการส่วนตัว ซึ่งแตกต่างจากธีมเริ่มต้นของเว็บไซต์ คุณสามารถเปลี่ยนธีมส่วนตัวได้ที่ <a href="{{ route('customer.theme.settings') }}" class="underline font-semibold hover:text-amber-800 dark:hover:text-amber-200">หน้าตั้งค่าธีม</a>
                    </p>
                </div>
            </div>
        </div>
        @endif

        <div class="p-4 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl border border-indigo-200 dark:border-indigo-700">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-indigo-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-sm text-indigo-700 dark:text-indigo-300">
                        <strong>หมายเหตุ:</strong> ธีมเริ่มต้นจะใช้กับผู้ใช้ที่ยังไม่ได้ตั้งค่าธีมส่วนตัว ผู้ใช้ที่ตั้งค่าธีมส่วนตัวแล้วจะเห็นธีมของตัวเอง
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Themes -->
    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center animate-fade-in" style="animation-delay: 0.2s;">
        <svg class="w-6 h-6 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
        </svg>
        เลือกธีมหลัก
    </h3>

    <form action="{{ route('admin.theme.update') }}" method="POST" id="themeForm">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            @foreach($themes as $themeKey => $theme)
                <label class="theme-card group cursor-pointer animate-fade-in" style="animation-delay: {{ 0.3 + ($loop->index * 0.1) }}s;">
                    <input type="radio" name="theme" value="{{ $themeKey }}"
                           class="sr-only peer"
                           {{ $currentTheme === $themeKey ? 'checked' : '' }}>

                    <div class="relative h-full bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden border-3 transition-all duration-300
                                {{ $currentTheme === $themeKey ? 'border-indigo-500 ring-4 ring-indigo-500/20 shadow-indigo-500/20' : 'border-gray-200 dark:border-gray-700' }}
                                peer-checked:border-indigo-500 peer-checked:ring-4 peer-checked:ring-indigo-500/20 peer-checked:shadow-indigo-500/20
                                hover:shadow-2xl hover:border-indigo-400 hover:-translate-y-2 transform">

                        <!-- Theme Preview -->
                        <div class="relative h-52 {{ $themeKey === 'premium' ? 'bg-gradient-to-br from-gray-900 via-indigo-900 to-purple-900' : 'bg-gradient-to-br from-gray-100 via-white to-gray-200 dark:from-gray-700 dark:via-gray-600 dark:to-gray-500' }} overflow-hidden">
                            @if($themeKey === 'classic')
                                <!-- Classic Theme Preview -->
                                <div class="absolute inset-0 flex p-4">
                                    <div class="w-14 bg-gray-800 dark:bg-gray-900 rounded-lg shadow-lg"></div>
                                    <div class="flex-1 pl-3 space-y-2">
                                        <div class="h-5 w-full bg-white dark:bg-gray-600 rounded shadow-sm"></div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="h-14 bg-white dark:bg-gray-600 rounded shadow-sm group-hover:shadow-md transition-shadow"></div>
                                            <div class="h-14 bg-white dark:bg-gray-600 rounded shadow-sm group-hover:shadow-md transition-shadow"></div>
                                            <div class="h-14 bg-white dark:bg-gray-600 rounded shadow-sm group-hover:shadow-md transition-shadow"></div>
                                        </div>
                                        <div class="h-20 bg-white dark:bg-gray-600 rounded shadow-sm"></div>
                                    </div>
                                </div>
                            @else
                                <!-- Premium Theme Preview -->
                                <div class="absolute inset-0 flex p-4">
                                    <div class="w-14 bg-gradient-to-b from-indigo-950 to-purple-900 rounded-lg shadow-lg"></div>
                                    <div class="flex-1 pl-3 space-y-2">
                                        <div class="h-5 w-full bg-gradient-to-r from-indigo-500/40 to-purple-500/40 rounded"></div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="h-14 bg-gradient-to-br from-emerald-400/60 to-green-500/60 rounded group-hover:scale-105 transition-transform"></div>
                                            <div class="h-14 bg-gradient-to-br from-blue-400/60 to-cyan-500/60 rounded group-hover:scale-105 transition-transform" style="transition-delay: 50ms;"></div>
                                            <div class="h-14 bg-gradient-to-br from-purple-400/60 to-pink-500/60 rounded group-hover:scale-105 transition-transform" style="transition-delay: 100ms;"></div>
                                        </div>
                                        <div class="h-20 bg-gray-800/60 backdrop-blur rounded"></div>
                                    </div>
                                </div>

                                <!-- Animated Blobs -->
                                <div class="absolute top-4 right-4 w-10 h-10 bg-purple-400/40 rounded-full blur-xl animate-pulse"></div>
                                <div class="absolute bottom-6 left-20 w-8 h-8 bg-pink-400/40 rounded-full blur-xl animate-pulse" style="animation-delay: 1s;"></div>
                                <div class="absolute top-1/2 right-1/4 w-6 h-6 bg-cyan-400/40 rounded-full blur-xl animate-pulse" style="animation-delay: 2s;"></div>
                            @endif

                            <!-- Hover Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-all duration-300"></div>

                            <!-- Current Badge -->
                            @if($currentTheme === $themeKey)
                                <div class="absolute top-4 right-4 z-10">
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-indigo-500 text-white shadow-lg animate-pulse-slow">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        ใช้งานอยู่
                                    </span>
                                </div>
                            @endif

                            @if($themeKey === 'premium')
                                <div class="absolute top-4 left-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-gradient-to-r from-amber-400 to-orange-500 text-white shadow-lg">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        Premium
                                    </span>
                                </div>
                            @endif

                            <!-- Hover Preview Button -->
                            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-2 group-hover:translate-y-0">
                                <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium bg-white/90 dark:bg-gray-800/90 text-gray-900 dark:text-white shadow-xl backdrop-blur-sm">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    คลิกเพื่อเลือก
                                </span>
                            </div>
                        </div>

                        <!-- Theme Info -->
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ $theme['name'] }}</h4>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">{{ $theme['description'] }}</p>

                            <div class="flex flex-wrap gap-2">
                                @if($themeKey === 'classic')
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors hover:bg-gray-200 dark:hover:bg-gray-600">
                                        <svg class="w-3.5 h-3.5 mr-1.5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        เรียบง่าย
                                    </span>
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors hover:bg-gray-200 dark:hover:bg-gray-600">
                                        <svg class="w-3.5 h-3.5 mr-1.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
                                        โหลดเร็ว
                                    </span>
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors hover:bg-gray-200 dark:hover:bg-gray-600">
                                        <svg class="w-3.5 h-3.5 mr-1.5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/></svg>
                                        Light Mode
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300 transition-colors hover:bg-indigo-200 dark:hover:bg-indigo-800/50">
                                        <svg class="w-3.5 h-3.5 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z"/></svg>
                                        Animations
                                    </span>
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-300 transition-colors hover:bg-purple-200 dark:hover:bg-purple-800/50">
                                        <svg class="w-3.5 h-3.5 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 2a2 2 0 00-2 2v11a3 3 0 106 0V4a2 2 0 00-2-2H4zm1 14a1 1 0 100-2 1 1 0 000 2zm5-1.757l4.9-4.9a2 2 0 000-2.828L13.485 5.1a2 2 0 00-2.828 0L10 5.757v8.486zM16 18H9.071l6-6H16a2 2 0 012 2v2a2 2 0 01-2 2z" clip-rule="evenodd"/></svg>
                                        Gradients
                                    </span>
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-gray-800 text-gray-200 transition-colors hover:bg-gray-700">
                                        <svg class="w-3.5 h-3.5 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>
                                        Dark Mode
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Selection Indicator (Checkmark) -->
                        <div class="absolute bottom-6 right-6 w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg opacity-0 peer-checked:opacity-100 transition-all duration-300 transform scale-50 peer-checked:scale-100">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </label>
            @endforeach
        </div>

        <!-- Save Button -->
        <div class="flex justify-end animate-fade-in" style="animation-delay: 0.5s;">
            <button type="submit"
                    class="group inline-flex items-center px-8 py-4 border border-transparent text-base font-bold rounded-xl shadow-lg text-white bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-300 transform hover:scale-105 hover:shadow-xl">
                <svg class="w-5 h-5 mr-2 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                บันทึกการเปลี่ยนแปลง
            </button>
        </div>
    </form>
</div>

@push('scripts')
<style>
@keyframes blob {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
}

@keyframes pulse-slow {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.animate-blob { animation: blob 7s infinite; }
.animate-pulse-slow { animation: pulse-slow 2s infinite; }
.border-3 { border-width: 3px; }

/* Theme card selection animation */
.theme-card input[type="radio"]:checked + div {
    animation: selectPulse 0.3s ease-out;
}

@keyframes selectPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}
</style>
@endpush
@endsection
