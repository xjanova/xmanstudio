<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'XMAN Studio - IT Solutions & Software Development')</title>
    <meta name="description" content="@yield('meta_description', 'XMAN Studio ผู้เชี่ยวชาญด้าน IT Solutions ครบวงจร ทำเว็บไซต์ แอพพลิเคชัน Blockchain IoT Network Security AI และอื่นๆ')">

    <!-- Favicon -->
    @php
        $siteFavicon = \App\Models\Setting::getValue('site_favicon');
    @endphp
    @if($siteFavicon)
        <link rel="icon" type="image/x-icon" href="{{ asset('storage/' . $siteFavicon) }}">
        <link rel="shortcut icon" type="image/x-icon" href="{{ asset('storage/' . $siteFavicon) }}">
    @else
        <link rel="icon" type="image/x-icon" href="{{ asset('public_html/favicon.ico') }}">
    @endif

    <!-- Preconnect to Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Dark Mode Script (prevent flash) -->
    <script>
        if (localStorage.getItem('darkMode') === 'true' || (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>

    <!-- Custom Head Code (Tracking & Verification) -->
    @php
        $customHeadCode = \App\Models\Setting::getValue('custom_code_head', '');
    @endphp
    @if($customHeadCode)
        {!! $customHeadCode !!}
    @endif
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
    <!-- Custom Body Start Code (Tracking noscript) -->
    @php
        $customBodyStartCode = \App\Models\Setting::getValue('custom_code_body_start', '');
    @endphp
    @if($customBodyStartCode)
        {!! $customBodyStartCode !!}
    @endif

    <!-- RGB Fireflies - Fixed Top Layer (Global) -->
    <div id="fireflies-layer" class="fireflies-fixed-layer"></div>

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg sticky top-0 z-50 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo & Desktop Menu -->
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        @php
                            $siteLogo = \App\Models\Setting::getValue('site_logo');
                        @endphp
                        <a href="/" class="flex items-center">
                            @if($siteLogo)
                                <img src="{{ asset('storage/' . $siteLogo) }}" alt="XMAN STUDIO" class="h-10 w-auto">
                            @else
                                <span class="text-2xl font-bold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors">
                                    XMAN STUDIO
                                </span>
                            @endif
                        </a>
                    </div>
                    <div class="hidden md:ml-8 md:flex md:space-x-1 md:items-center">
                        <a href="/" class="group flex items-center gap-1.5 px-3 py-2 text-sm font-medium transition-all duration-300 rounded-xl {{ request()->is('/') ? 'bg-gradient-to-r from-emerald-500 to-green-500 text-white shadow-lg shadow-green-500/30' : 'text-gray-600 dark:text-gray-300 hover:bg-gradient-to-r hover:from-emerald-500 hover:to-green-500 hover:text-white hover:shadow-lg hover:shadow-green-500/30' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1"/></svg>
                            หน้าหลัก
                        </a>
                        <a href="/services" class="group flex items-center gap-1.5 px-3 py-2 text-sm font-medium transition-all duration-300 rounded-xl {{ request()->is('services*') ? 'bg-gradient-to-r from-yellow-400 to-amber-500 text-white shadow-lg shadow-amber-500/30' : 'text-gray-600 dark:text-gray-300 hover:bg-gradient-to-r hover:from-yellow-400 hover:to-amber-500 hover:text-white hover:shadow-lg hover:shadow-amber-500/30' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            บริการ
                        </a>
                        <a href="/products" class="group flex items-center gap-1.5 px-3 py-2 text-sm font-medium transition-all duration-300 rounded-xl {{ request()->is('products*') ? 'bg-gradient-to-r from-orange-500 to-red-500 text-white shadow-lg shadow-orange-500/30' : 'text-gray-600 dark:text-gray-300 hover:bg-gradient-to-r hover:from-orange-500 hover:to-red-500 hover:text-white hover:shadow-lg hover:shadow-orange-500/30' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            ผลิตภัณฑ์
                        </a>
                        <a href="/rental" class="group flex items-center gap-1.5 px-3 py-2 text-sm font-medium transition-all duration-300 rounded-xl {{ request()->is('rental*') ? 'bg-gradient-to-r from-pink-500 to-rose-500 text-white shadow-lg shadow-pink-500/30' : 'text-gray-600 dark:text-gray-300 hover:bg-gradient-to-r hover:from-pink-500 hover:to-rose-500 hover:text-white hover:shadow-lg hover:shadow-pink-500/30' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            เช่าบริการ
                        </a>
                        <a href="/portfolio" class="group flex items-center gap-1.5 px-3 py-2 text-sm font-medium transition-all duration-300 rounded-xl {{ request()->is('portfolio*') ? 'bg-gradient-to-r from-purple-500 to-violet-500 text-white shadow-lg shadow-purple-500/30' : 'text-gray-600 dark:text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-violet-500 hover:text-white hover:shadow-lg hover:shadow-purple-500/30' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            ผลงาน
                        </a>
                        <a href="/support" class="group flex items-center gap-1.5 px-3 py-2 text-sm font-medium transition-all duration-300 rounded-xl {{ request()->is('support') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-lg shadow-blue-500/30' : 'text-gray-600 dark:text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white hover:shadow-lg hover:shadow-blue-500/30' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            ติดต่อ/สั่งซื้อ
                        </a>
                    </div>
                </div>

                <!-- Right Side Icons -->
                <div class="flex items-center space-x-3">
                    <!-- Dark Mode Toggle -->
                    <button id="darkModeToggle" type="button" class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors" aria-label="Toggle dark mode">
                        <!-- Sun Icon (shown in dark mode) -->
                        <svg class="w-5 h-5 hidden dark:block" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
                        </svg>
                        <!-- Moon Icon (shown in light mode) -->
                        <svg class="w-5 h-5 block dark:hidden" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                        </svg>
                    </button>

                    <!-- Wallet Balance (for authenticated users) -->
                    @auth
                    @php
                        $userWallet = \App\Models\Wallet::getOrCreateForUser(auth()->id());
                    @endphp
                    <a href="{{ route('user.wallet.index') }}" class="hidden sm:flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-gradient-to-r from-purple-500 to-indigo-500 text-white hover:from-purple-600 hover:to-indigo-600 transition-all shadow-sm">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        <span>{{ number_format($userWallet->balance, 0) }}</span>
                    </a>
                    @endauth

                    <!-- Cart -->
                    @php
                        $cartCount = 0;
                        if (auth()->check()) {
                            $cart = \App\Models\Cart::where('user_id', auth()->id())->first();
                        } else {
                            $cart = \App\Models\Cart::where('session_id', session()->getId())->first();
                        }
                        if ($cart) {
                            $cartCount = $cart->items()->sum('quantity');
                        }
                    @endphp
                    <a href="/cart" class="relative p-2 text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        @if($cartCount > 0)
                        <span class="absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-primary-600 rounded-full">{{ $cartCount }}</span>
                        @endif
                    </a>

                    <!-- Notifications -->
                    @auth
                    @php
                        $unreadNotifications = auth()->user()->unreadNotifications()->count();

                        // Get expiring/expired licenses
                        $expiringLicenses = \App\Models\LicenseKey::whereHas('order', function ($query) {
                            $query->where('user_id', auth()->id());
                        })
                        ->where('status', 'active')
                        ->where('license_type', '!=', 'lifetime')
                        ->where(function ($q) {
                            $q->where('expires_at', '<=', now()->addDays(7))
                              ->where('expires_at', '>', now());
                        })
                        ->with('product')
                        ->get();

                        $expiredLicenses = \App\Models\LicenseKey::whereHas('order', function ($query) {
                            $query->where('user_id', auth()->id());
                        })
                        ->where('status', 'active')
                        ->where('license_type', '!=', 'lifetime')
                        ->where('expires_at', '<=', now())
                        ->with('product')
                        ->get();

                        $licenseAlertCount = $expiringLicenses->count() + $expiredLicenses->count();
                        $totalAlerts = $unreadNotifications + $licenseAlertCount;
                    @endphp
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="relative p-2 text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if($totalAlerts > 0)
                            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white {{ $licenseAlertCount > 0 ? 'bg-amber-500 animate-pulse' : 'bg-red-500' }} rounded-full">{{ $totalAlerts > 9 ? '9+' : $totalAlerts }}</span>
                            @endif
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-2 z-50">
                            <div class="px-4 py-2 border-b dark:border-gray-700">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">การแจ้งเตือน</h3>
                            </div>
                            <div class="max-h-80 overflow-y-auto">
                                {{-- License Expiry Alerts --}}
                                @foreach($expiredLicenses as $license)
                                    <a href="{{ route('products.show', $license->product->slug ?? 'products') }}" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0">
                                                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <p class="text-sm font-medium text-red-700 dark:text-red-400">License หมดอายุแล้ว!</p>
                                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">{{ $license->product->name ?? 'Product' }}</p>
                                                <p class="text-xs text-red-600 dark:text-red-400 mt-1">กรุณาต่ออายุเพื่อใช้งานต่อ</p>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach

                                @foreach($expiringLicenses as $license)
                                    @php $daysLeft = max(0, (int) now()->diffInDays($license->expires_at, false)); @endphp
                                    <a href="{{ route('products.show', $license->product->slug ?? 'products') }}" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0">
                                                <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <p class="text-sm font-medium text-amber-700 dark:text-amber-400">License ใกล้หมดอายุ!</p>
                                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">{{ $license->product->name ?? 'Product' }}</p>
                                                <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">เหลืออีก {{ $daysLeft }} วัน</p>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach

                                {{-- Regular Notifications --}}
                                @forelse(auth()->user()->notifications()->take(5)->get() as $notification)
                                    <a href="{{ $notification->data['url'] ?? '#' }}" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 {{ $notification->read_at ? '' : 'bg-primary-50 dark:bg-primary-900/20' }}">
                                        <p class="text-sm text-gray-900 dark:text-white">{{ $notification->data['message'] ?? 'การแจ้งเตือนใหม่' }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                    </a>
                                @empty
                                    @if($licenseAlertCount == 0)
                                    <div class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                        <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                        </svg>
                                        <p class="text-sm">ไม่มีการแจ้งเตือน</p>
                                    </div>
                                    @endif
                                @endforelse
                            </div>
                            @if(auth()->user()->notifications()->count() > 0 || $licenseAlertCount > 0)
                            <div class="px-4 py-2 border-t dark:border-gray-700 flex justify-between">
                                <a href="{{ route('customer.dashboard') }}" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">ดูทั้งหมด</a>
                                @if($licenseAlertCount > 0)
                                <a href="{{ route('products.index') }}" class="text-sm text-amber-600 dark:text-amber-400 hover:underline">ดู License</a>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                    @endauth

                    <!-- User Menu / Login -->
                    @auth
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold shadow-md ring-2 ring-white dark:ring-gray-800">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 hidden sm:block max-w-[100px] truncate">{{ Auth::user()->name }}</span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                 x-cloak
                                 class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 py-1 z-50 overflow-hidden">
                                {{-- User Info Header --}}
                                <div class="px-4 py-3 bg-gradient-to-r from-indigo-500/10 to-purple-500/10 dark:from-indigo-500/20 dark:to-purple-500/20 border-b border-gray-100 dark:border-gray-700">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-sm font-bold shadow">
                                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ Auth::user()->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Auth::user()->email }}</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Wallet Balance Card --}}
                                <a href="{{ route('user.wallet.index') }}" class="block mx-3 my-2 p-3 rounded-xl bg-gradient-to-r from-purple-500 to-indigo-600 text-white hover:from-purple-600 hover:to-indigo-700 transition-all shadow-sm">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                            <span class="text-xs opacity-80">Wallet</span>
                                        </div>
                                        <span class="text-sm font-bold">{{ number_format($userWallet->balance, 0) }} ฿</span>
                                    </div>
                                </a>

                                {{-- Menu Items --}}
                                <div class="py-1">
                                    @if(Auth::user()->isAdmin())
                                    <a href="/admin/rentals" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                        Admin Panel
                                    </a>
                                    @endif
                                    <a href="{{ route('customer.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        บัญชีของฉัน
                                    </a>
                                    <a href="/support/tracking" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors {{ request()->is('support/tracking*') ? 'bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-400' : '' }}">
                                        <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                        ติดตามงาน
                                    </a>
                                </div>

                                {{-- Logout --}}
                                @if(Route::has('logout'))
                                <div class="border-t border-gray-100 dark:border-gray-700 pt-1 mt-1">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="flex items-center gap-2.5 w-full px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                            ออกจากระบบ
                                        </button>
                                    </form>
                                </div>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="hidden sm:flex items-center gap-2">
                            @if(Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                สมัครสมาชิก
                            </a>
                            @endif
                            @if(Route::has('login'))
                            <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 rounded-xl transition-all shadow-sm hover:shadow-md">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                เข้าสู่ระบบ
                            </a>
                            @endif
                        </div>
                    @endauth

                    <!-- Mobile Menu Button -->
                    <button id="mobileMenuBtn" type="button" class="md:hidden p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobileMenu" class="hidden md:hidden bg-white dark:bg-gray-800 border-t dark:border-gray-700">
            <div class="px-4 py-3 space-y-1.5">
                {{-- Main Navigation --}}
                <a href="/" class="flex items-center gap-3 px-4 py-2.5 text-base font-medium rounded-xl transition-all duration-300 {{ request()->is('/') ? 'bg-gradient-to-r from-emerald-500 to-green-500 text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1"/></svg>
                    หน้าหลัก
                </a>
                <a href="/services" class="flex items-center gap-3 px-4 py-2.5 text-base font-medium rounded-xl transition-all duration-300 {{ request()->is('services*') ? 'bg-gradient-to-r from-yellow-400 to-amber-500 text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    บริการ
                </a>
                <a href="/products" class="flex items-center gap-3 px-4 py-2.5 text-base font-medium rounded-xl transition-all duration-300 {{ request()->is('products*') ? 'bg-gradient-to-r from-orange-500 to-red-500 text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    ผลิตภัณฑ์
                </a>
                <a href="/rental" class="flex items-center gap-3 px-4 py-2.5 text-base font-medium rounded-xl transition-all duration-300 {{ request()->is('rental*') ? 'bg-gradient-to-r from-pink-500 to-rose-500 text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    เช่าบริการ
                </a>
                <a href="/portfolio" class="flex items-center gap-3 px-4 py-2.5 text-base font-medium rounded-xl transition-all duration-300 {{ request()->is('portfolio*') ? 'bg-gradient-to-r from-purple-500 to-violet-500 text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    ผลงาน
                </a>
                <a href="/support" class="flex items-center gap-3 px-4 py-2.5 text-base font-medium rounded-xl transition-all duration-300 {{ request()->is('support') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    ติดต่อ/สั่งซื้อ
                </a>

                {{-- Divider --}}
                <div class="border-t border-gray-200 dark:border-gray-700 my-2"></div>

                {{-- User Section --}}
                @auth
                    <a href="/support/tracking" class="flex items-center gap-3 px-4 py-2.5 text-base font-medium rounded-xl transition-all duration-300 {{ request()->is('support/tracking*') ? 'bg-gradient-to-r from-teal-500 to-emerald-500 text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        ติดตามงาน
                    </a>
                    <a href="{{ route('customer.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-base font-medium rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        บัญชีของฉัน
                    </a>
                    <a href="{{ route('user.wallet.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-base font-medium rounded-xl bg-gradient-to-r from-purple-500 to-indigo-500 text-white shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Wallet: {{ number_format(\App\Models\Wallet::getOrCreateForUser(auth()->id())->balance, 0) }} ฿
                    </a>
                @endauth
                @guest
                    <a href="/support/tracking" class="flex items-center gap-3 px-4 py-2.5 text-base font-medium rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        ติดตามงาน
                    </a>
                    @if(Route::has('register'))
                    <a href="{{ route('register') }}" class="flex items-center gap-3 px-4 py-2.5 text-base font-medium rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        สมัครสมาชิก
                    </a>
                    @endif
                    @if(Route::has('login'))
                    <a href="{{ route('login') }}" class="flex items-center gap-3 px-4 py-2.5 text-base font-medium rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        เข้าสู่ระบบ
                    </a>
                    @endif
                @endguest
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="animate-fade-in-down">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-400 px-4 py-3 rounded-lg flex items-center justify-between" role="alert">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-green-700 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="animate-fade-in-down">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg flex items-center justify-between" role="alert">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-red-700 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Main Content -->
    <main class="min-h-screen pb-20 md:pb-0">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 dark:bg-black text-white relative z-30">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div class="lg:col-span-1">
                    @php
                        $siteLogo = \App\Models\Setting::getValue('site_logo');
                    @endphp
                    @if($siteLogo)
                        <img src="{{ asset('storage/' . $siteLogo) }}" alt="XMAN STUDIO" class="h-12 w-auto mb-4">
                    @else
                        <h3 class="text-2xl font-bold mb-4 bg-gradient-to-r from-primary-400 to-primary-600 bg-clip-text text-transparent">XMAN STUDIO</h3>
                    @endif
                    <p class="text-gray-400 mb-4">ผู้เชี่ยวชาญด้าน IT Solutions ครบวงจร พัฒนาซอฟต์แวร์และบริการเทคโนโลยีสารสนเทศ</p>
                    <!-- Social Links -->
                    <div class="flex space-x-4">
                        <a href="https://www.facebook.com/xmanenterprise/" target="_blank" class="text-gray-400 hover:text-blue-500 transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <a href="https://youtube.com/@metal-xproject" target="_blank" class="text-gray-400 hover:text-red-500 transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-green-500 transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Services -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">บริการ</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="/services" class="hover:text-white transition-colors">Blockchain Development</a></li>
                        <li><a href="/services" class="hover:text-white transition-colors">พัฒนาเว็บไซต์</a></li>
                        <li><a href="/services" class="hover:text-white transition-colors">แอพพลิเคชัน</a></li>
                        <li><a href="/services" class="hover:text-white transition-colors">AI Services</a></li>
                        <li><a href="/services" class="hover:text-white transition-colors">IoT Solutions</a></li>
                    </ul>
                </div>

                <!-- Help -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">ช่วยเหลือ</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="/support" class="hover:text-white transition-colors">ติดต่อ/สั่งซื้อ</a></li>
                        <li><a href="/rental" class="hover:text-white transition-colors">เช่าบริการ</a></li>
                        <li><a href="/products" class="hover:text-white transition-colors">ผลิตภัณฑ์</a></li>
                        <li><a href="/portfolio" class="hover:text-white transition-colors">ผลงาน</a></li>
                    </ul>

                    <h4 class="text-lg font-semibold mb-4 mt-6">กฎหมาย</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="/privacy" class="hover:text-white transition-colors">นโยบายความเป็นส่วนตัว</a></li>
                        <li><a href="/terms" class="hover:text-white transition-colors">ข้อกำหนดการใช้งาน</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">ติดต่อเรา</h4>
                    @php
                        $footerPhone = \App\Models\Setting::getValue('contact_phone', '080-6038278');
                        $footerPhoneName = \App\Models\Setting::getValue('contact_phone_name', 'คุณกรณิภา');
                        $footerEmail = \App\Models\Setting::getValue('contact_email', 'xjanovax@gmail.com');
                        $footerFbName = \App\Models\Setting::getValue('contact_facebook_name', 'XMAN Enterprise');
                        $footerLineId = \App\Models\Setting::getValue('contact_line_id', '@xmanstudio');
                    @endphp
                    <ul class="space-y-3 text-gray-400">
                        @if($footerPhone)
                        <li class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            {{ $footerPhone }}{{ $footerPhoneName ? " ({$footerPhoneName})" : '' }}
                        </li>
                        @endif
                        @if($footerEmail)
                        <li class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            {{ $footerEmail }}
                        </li>
                        @endif
                        @if($footerFbName)
                        <li class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            Facebook: {{ $footerFbName }}
                        </li>
                        @endif
                        @if($footerLineId)
                        <li class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                            </svg>
                            Line OA: {{ $footerLineId }}
                        </li>
                        @endif
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="mt-12 pt-8 border-t border-gray-800">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-center md:text-left">
                        <p class="text-gray-400 text-sm">&copy; {{ date('Y') }} {{ config('app.name', 'XMAN Studio') }}. All rights reserved.</p>
                        <p class="text-gray-500 text-xs mt-1">IT Solutions & Software Development</p>
                    </div>
                    <div class="flex flex-wrap justify-center space-x-4 md:space-x-6 mt-4 md:mt-0">
                        <a href="/privacy" class="text-gray-400 hover:text-white text-sm transition-colors">นโยบายความเป็นส่วนตัว</a>
                        <a href="/terms" class="text-gray-400 hover:text-white text-sm transition-colors">ข้อกำหนดการใช้งาน</a>
                        <a href="/support" class="text-gray-400 hover:text-white text-sm transition-colors">ติดต่อเรา</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        darkModeToggle?.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
        });

        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        mobileMenuBtn?.addEventListener('click', () => {
            mobileMenu?.classList.toggle('hidden');
        });

        // Toast Notification Function
        window.showToast = function(message, type = 'info', duration = 5000) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast-${type}`;
            toast.innerHTML = `
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" class="ml-2 hover:opacity-75">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            `;
            container?.appendChild(toast);
            setTimeout(() => toast.remove(), duration);
        };

        // Golden Fireflies Effect - Pure JavaScript
        (function() {
            const container = document.getElementById('fireflies-layer');
            if (!container) return;

            // Create 35 small golden fireflies
            for (let i = 0; i < 35; i++) {
                const x = Math.random() * 100;
                const y = Math.random() * 100;
                const size = 2 + Math.random() * 3; // Small: 2-5px
                const floatDuration = 8 + Math.random() * 12;
                const glowDuration = 2 + Math.random() * 4;
                const delay = Math.random() * 10;

                const wrapper = document.createElement('div');
                wrapper.className = 'firefly-wrapper';
                wrapper.style.cssText = `
                    position: absolute;
                    left: ${x}%;
                    top: ${y}%;
                    animation: firefly-float ${floatDuration}s ease-in-out infinite;
                    animation-delay: ${delay}s;
                `;

                const core = document.createElement('div');
                core.className = 'firefly-core firefly-gold';
                core.style.cssText = `
                    width: ${size}px;
                    height: ${size}px;
                    animation: firefly-glow ${glowDuration}s ease-in-out infinite alternate;
                    animation-delay: ${delay + 0.5}s;
                `;

                wrapper.appendChild(core);
                container.appendChild(wrapper);
            }
        })();
    </script>

    {{-- Mobile Bottom Navigation Bar --}}
    @include('components.mobile-bottom-nav')

    {{-- AI Chat Floating Widget --}}
    @include('components.ai-chat-widget')

    @stack('scripts')

    <!-- Custom Body End Code (Chat widgets, Tracking pixels) -->
    @php
        $customBodyEndCode = \App\Models\Setting::getValue('custom_code_body_end', '');
    @endphp
    @if($customBodyEndCode)
        {!! $customBodyEndCode !!}
    @endif
</body>
</html>
