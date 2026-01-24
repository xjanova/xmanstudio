@extends($customerLayout ?? 'layouts.customer')

@section('title', 'ตั้งค่าธีม')
@section('page-title', 'ตั้งค่าธีม')
@section('page-description', 'เลือกธีมที่ต้องการใช้งานสำหรับบัญชีของคุณ')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header Card -->
    <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl mb-8 animate-fade-in">
        <div class="absolute inset-0 bg-black/10"></div>

        <!-- Animated Background Blobs -->
        <div class="absolute inset-0 opacity-30">
            <div class="absolute top-0 -left-4 w-40 h-40 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl animate-blob"></div>
            <div class="absolute top-0 -right-4 w-40 h-40 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl animate-blob" style="animation-delay: 2s;"></div>
            <div class="absolute -bottom-8 left-20 w-40 h-40 bg-pink-300 rounded-full mix-blend-multiply filter blur-xl animate-blob" style="animation-delay: 4s;"></div>
        </div>

        <div class="relative px-8 py-10">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">
                        <svg class="w-8 h-8 inline-block mr-2 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                        </svg>
                        ปรับแต่งธีม
                    </h1>
                    <p class="text-white/80 text-lg">เลือกธีมที่เหมาะกับสไตล์การใช้งานของคุณ</p>
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

    <!-- Current Theme Info -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-8 animate-fade-in" style="animation-delay: 0.1s;">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br {{ $themes[$currentTheme]['gradient'] ?? 'from-indigo-500 to-purple-600' }} flex items-center justify-center shadow-lg">
                    @if(($themes[$currentTheme]['icon'] ?? '') === 'sun')
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    @else
                        <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z"/>
                        </svg>
                    @endif
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">ธีมที่ใช้งานอยู่</h2>
                    <p class="text-gray-500 dark:text-gray-400">
                        <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $themes[$currentTheme]['name'] }}</span>
                        @if($userTheme)
                            <span class="text-xs ml-2 px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300 rounded-full">เลือกเอง</span>
                        @else
                            <span class="text-xs ml-2 px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-full">ค่าเริ่มต้นของระบบ</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Theme Selection -->
    <form action="{{ route('customer.settings.theme.update') }}" method="POST" id="themeForm">
        @csrf
        @method('PUT')

        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
            </svg>
            เลือกธีม
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Use Site Default Option -->
            <label class="theme-card group cursor-pointer animate-fade-in" style="animation-delay: 0.2s;">
                <input type="radio" name="theme" value="default"
                       class="sr-only peer"
                       {{ !$userTheme ? 'checked' : '' }}>

                <div class="relative h-full bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden border-3 transition-all duration-300
                            {{ !$userTheme ? 'border-indigo-500 ring-4 ring-indigo-500/20' : 'border-gray-200 dark:border-gray-700' }}
                            peer-checked:border-indigo-500 peer-checked:ring-4 peer-checked:ring-indigo-500/20
                            hover:shadow-2xl hover:border-indigo-400 hover:-translate-y-1 transform">

                    <!-- Theme Preview -->
                    <div class="relative h-36 bg-gradient-to-br from-gray-100 via-gray-200 to-gray-300 dark:from-gray-700 dark:via-gray-600 dark:to-gray-500 overflow-hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="w-16 h-16 rounded-full bg-white/50 dark:bg-gray-800/50 backdrop-blur-lg flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-500 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Hover Overlay -->
                        <div class="absolute inset-0 bg-indigo-600/0 group-hover:bg-indigo-600/10 transition-all duration-300"></div>

                        <!-- Selection Badge -->
                        @if(!$userTheme)
                            <div class="absolute top-3 right-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-500 text-white shadow-lg animate-pulse-slow">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    เลือกอยู่
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Theme Info -->
                    <div class="p-4">
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-1">ใช้ค่าเริ่มต้น</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">ใช้ธีม {{ $themes[$siteDefaultTheme]['name'] }} ตามการตั้งค่าของระบบ</p>

                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                อัปเดตอัตโนมัติ
                            </span>
                        </div>
                    </div>

                    <!-- Selection Indicator -->
                    <div class="absolute bottom-4 right-4 w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center shadow-lg opacity-0 peer-checked:opacity-100 transition-all duration-300 transform scale-75 peer-checked:scale-100">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>
            </label>

            @foreach($themes as $themeKey => $theme)
                <label class="theme-card group cursor-pointer animate-fade-in" style="animation-delay: {{ 0.3 + ($loop->index * 0.1) }}s;">
                    <input type="radio" name="theme" value="{{ $themeKey }}"
                           class="sr-only peer"
                           {{ $userTheme === $themeKey ? 'checked' : '' }}>

                    <div class="relative h-full bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden border-3 transition-all duration-300
                                {{ $userTheme === $themeKey ? 'border-indigo-500 ring-4 ring-indigo-500/20' : 'border-gray-200 dark:border-gray-700' }}
                                peer-checked:border-indigo-500 peer-checked:ring-4 peer-checked:ring-indigo-500/20
                                hover:shadow-2xl hover:border-indigo-400 hover:-translate-y-1 transform">

                        <!-- Theme Preview -->
                        <div class="relative h-36 {{ $themeKey === 'premium' ? 'bg-gradient-to-br from-gray-900 via-indigo-900 to-purple-900' : 'bg-gradient-to-br from-gray-100 via-white to-gray-200 dark:from-gray-700 dark:via-gray-600 dark:to-gray-500' }} overflow-hidden">
                            @if($themeKey === 'classic')
                                <!-- Classic Theme Preview -->
                                <div class="absolute inset-0 flex p-3">
                                    <div class="w-10 bg-gray-800 dark:bg-gray-900 rounded-lg"></div>
                                    <div class="flex-1 pl-2 space-y-2">
                                        <div class="h-4 w-full bg-white dark:bg-gray-600 rounded"></div>
                                        <div class="grid grid-cols-3 gap-1">
                                            <div class="h-8 bg-white dark:bg-gray-600 rounded"></div>
                                            <div class="h-8 bg-white dark:bg-gray-600 rounded"></div>
                                            <div class="h-8 bg-white dark:bg-gray-600 rounded"></div>
                                        </div>
                                        <div class="h-12 bg-white dark:bg-gray-600 rounded"></div>
                                    </div>
                                </div>
                            @else
                                <!-- Premium Theme Preview -->
                                <div class="absolute inset-0 flex p-3">
                                    <div class="w-10 bg-gradient-to-b from-indigo-950 to-purple-900 rounded-lg"></div>
                                    <div class="flex-1 pl-2 space-y-2">
                                        <div class="h-4 w-full bg-gradient-to-r from-indigo-500/30 to-purple-500/30 rounded"></div>
                                        <div class="grid grid-cols-3 gap-1">
                                            <div class="h-8 bg-gradient-to-br from-emerald-400/50 to-green-500/50 rounded"></div>
                                            <div class="h-8 bg-gradient-to-br from-blue-400/50 to-cyan-500/50 rounded"></div>
                                            <div class="h-8 bg-gradient-to-br from-purple-400/50 to-pink-500/50 rounded"></div>
                                        </div>
                                        <div class="h-12 bg-gray-800/50 rounded"></div>
                                    </div>
                                </div>

                                <!-- Animated Blobs -->
                                <div class="absolute top-2 right-2 w-6 h-6 bg-purple-400/40 rounded-full blur-lg animate-pulse"></div>
                                <div class="absolute bottom-4 left-14 w-4 h-4 bg-pink-400/40 rounded-full blur-lg animate-pulse" style="animation-delay: 1s;"></div>
                            @endif

                            <!-- Hover Overlay -->
                            <div class="absolute inset-0 bg-indigo-600/0 group-hover:bg-indigo-600/10 transition-all duration-300"></div>

                            <!-- Selection Badge -->
                            @if($userTheme === $themeKey)
                                <div class="absolute top-3 right-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-500 text-white shadow-lg animate-pulse-slow">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        เลือกอยู่
                                    </span>
                                </div>
                            @endif

                            @if($themeKey === 'premium')
                                <div class="absolute top-3 left-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-gradient-to-r from-amber-400 to-orange-500 text-white shadow">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        Premium
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- Theme Info -->
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-1">{{ $theme['name'] }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ $theme['description'] }}</p>

                            <div class="flex flex-wrap gap-2">
                                @foreach($theme['features'] ?? [] as $feature)
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs
                                        @if($themeKey === 'premium')
                                            @if($feature === 'Animations')
                                                bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300
                                            @elseif($feature === 'Gradients')
                                                bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-300
                                            @elseif($feature === 'Dark Mode')
                                                bg-gray-800 text-gray-200
                                            @else
                                                bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300
                                            @endif
                                        @else
                                            bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300
                                        @endif
                                    ">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ $feature }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <!-- Selection Indicator -->
                        <div class="absolute bottom-4 right-4 w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center shadow-lg opacity-0 peer-checked:opacity-100 transition-all duration-300 transform scale-75 peer-checked:scale-100">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </label>
            @endforeach
        </div>

        <!-- Info Box -->
        <div class="bg-indigo-50 dark:bg-indigo-900/30 rounded-xl border border-indigo-200 dark:border-indigo-700 p-4 mb-8 animate-fade-in" style="animation-delay: 0.5s;">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-indigo-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-sm text-indigo-700 dark:text-indigo-300">
                        <strong>หมายเหตุ:</strong> ธีมที่คุณเลือกจะมีผลกับหน้า Dashboard และหน้าต่างๆ ของบัญชีคุณเท่านั้น การเปลี่ยนธีมจะมีผลทันทีหลังจากบันทึก
                    </p>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end animate-fade-in" style="animation-delay: 0.6s;">
            <button type="submit"
                    class="group inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-xl shadow-lg text-white bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-300 transform hover:scale-105 hover:shadow-xl">
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

/* Theme card hover effects */
.theme-card:hover .theme-card-preview {
    transform: scale(1.02);
}

/* Radio button selection animation */
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
