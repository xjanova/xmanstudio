@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ตั้งค่าธีม')
@section('page-title', 'ตั้งค่าธีม')
@section('page-description', 'เลือกธีมที่ต้องการใช้งานสำหรับทั้งหน้า Admin และ User')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Current Theme Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">ธีมปัจจุบัน</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">กำลังใช้งานธีม <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $themes[$currentTheme]['name'] }}</span></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                </svg>
            </div>
        </div>

        <div class="p-4 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl border border-indigo-200 dark:border-indigo-700">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-indigo-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-sm text-indigo-700 dark:text-indigo-300">
                        ธีมจะมีผลกับทั้งหน้า Admin Dashboard และหน้า Customer Dashboard การเปลี่ยนธีมจะมีผลทันทีหลังจากบันทึก
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Themes -->
    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">เลือกธีม</h3>

    <form action="{{ route('admin.theme.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            @foreach($themes as $themeKey => $theme)
                <label class="relative cursor-pointer group">
                    <input type="radio" name="theme" value="{{ $themeKey }}"
                           class="sr-only peer"
                           {{ $currentTheme === $themeKey ? 'checked' : '' }}>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden border-2 transition-all duration-300
                                {{ $currentTheme === $themeKey ? 'border-indigo-500 ring-4 ring-indigo-500/20' : 'border-gray-200 dark:border-gray-700' }}
                                peer-checked:border-indigo-500 peer-checked:ring-4 peer-checked:ring-indigo-500/20
                                hover:shadow-xl hover:border-indigo-400">

                        <!-- Theme Preview -->
                        <div class="relative h-48 {{ $themeKey === 'premium' ? 'bg-gradient-to-br from-gray-900 via-indigo-900 to-purple-900' : 'bg-gray-100 dark:bg-gray-700' }}">
                            @if($themeKey === 'classic')
                                <!-- Classic Theme Preview -->
                                <div class="absolute inset-0 flex">
                                    <div class="w-16 bg-gray-800"></div>
                                    <div class="flex-1 p-3">
                                        <div class="h-6 w-full bg-white dark:bg-gray-600 rounded mb-2"></div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="h-12 bg-white dark:bg-gray-600 rounded"></div>
                                            <div class="h-12 bg-white dark:bg-gray-600 rounded"></div>
                                            <div class="h-12 bg-white dark:bg-gray-600 rounded"></div>
                                        </div>
                                        <div class="mt-2 h-16 bg-white dark:bg-gray-600 rounded"></div>
                                    </div>
                                </div>
                            @else
                                <!-- Premium Theme Preview -->
                                <div class="absolute inset-0 flex">
                                    <div class="w-16 bg-gradient-to-b from-indigo-950 to-purple-900"></div>
                                    <div class="flex-1 p-3">
                                        <div class="h-6 w-full bg-gradient-to-r from-indigo-500/30 to-purple-500/30 rounded mb-2"></div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="h-12 bg-gradient-to-br from-emerald-400/50 to-green-500/50 rounded"></div>
                                            <div class="h-12 bg-gradient-to-br from-blue-400/50 to-cyan-500/50 rounded"></div>
                                            <div class="h-12 bg-gradient-to-br from-purple-400/50 to-pink-500/50 rounded"></div>
                                        </div>
                                        <div class="mt-2 h-16 bg-gray-800/50 rounded"></div>
                                    </div>
                                </div>

                                <!-- Animated Blobs -->
                                <div class="absolute top-2 right-2 w-8 h-8 bg-purple-400/30 rounded-full blur-xl animate-pulse"></div>
                                <div class="absolute bottom-4 left-20 w-6 h-6 bg-pink-400/30 rounded-full blur-xl animate-pulse" style="animation-delay: 1s;"></div>
                            @endif

                            <!-- Current Badge -->
                            @if($currentTheme === $themeKey)
                                <div class="absolute top-3 right-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-500 text-white shadow-lg">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        ใช้งานอยู่
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- Theme Info -->
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ $theme['name'] }}</h4>
                                @if($themeKey === 'premium')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gradient-to-r from-amber-400 to-orange-500 text-white">
                                        Premium
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $theme['description'] }}</p>

                            <div class="mt-4 flex flex-wrap gap-2">
                                @if($themeKey === 'classic')
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        เรียบง่าย
                                    </span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        โหลดเร็ว
                                    </span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        Light Mode
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z"/></svg>
                                        Animations
                                    </span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-300">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        Gradients
                                    </span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-800 text-gray-200">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>
                                        Dark Mode
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Selection Indicator -->
                        <div class="absolute inset-0 pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity">
                            <div class="absolute bottom-4 right-4">
                                <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </label>
            @endforeach
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button type="submit"
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl shadow-lg text-white bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-300 transform hover:scale-105">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                บันทึกการเปลี่ยนแปลง
            </button>
        </div>
    </form>
</div>
@endsection
