<!DOCTYPE html>
<html lang="th" class="scroll-smooth dark">
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

    <!-- Premium Theme Styles -->
    <style>
        /* Premium Theme Animations */
        @keyframes blob {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }

        @keyframes slideIn {
            from { transform: translateX(-100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 5px rgba(99, 102, 241, 0.5); }
            50% { box-shadow: 0 0 20px rgba(99, 102, 241, 0.8); }
        }

        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .animate-blob { animation: blob 7s infinite; }
        .animate-slide-in { animation: slideIn 0.5s ease-out; }
        .animate-fade-in { animation: fadeIn 0.5s ease-out; }
        .animate-shimmer {
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }
        .animate-pulse-glow { animation: pulse-glow 2s ease-in-out infinite; }
        .animate-gradient {
            background-size: 200% 200%;
            animation: gradient-shift 3s ease infinite;
        }

        /* Premium Navigation */
        .premium-nav {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(30, 27, 75, 0.95) 100%);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(99, 102, 241, 0.2);
        }

        .premium-nav-link {
            position: relative;
            transition: all 0.3s ease;
        }

        .premium-nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #818cf8, #c084fc);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .premium-nav-link:hover::after,
        .premium-nav-link.active::after {
            width: 80%;
        }

        /* Premium Card Hover */
        .premium-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.8) 100%);
            border: 1px solid rgba(99, 102, 241, 0.2);
            transition: all 0.3s ease;
        }

        .premium-card:hover {
            border-color: rgba(99, 102, 241, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 10px 40px -10px rgba(99, 102, 241, 0.3);
        }

        /* Premium Button */
        .premium-btn {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
            background-size: 200% 200%;
            animation: gradient-shift 3s ease infinite;
            transition: all 0.3s ease;
        }

        .premium-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -10px rgba(139, 92, 246, 0.5);
        }

        /* Premium scrollbar */
        .premium-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .premium-scrollbar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        .premium-scrollbar::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #818cf8, #c084fc);
            border-radius: 3px;
        }

        /* Toast Notification Styles */
        .toast-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .toast {
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideInRight 0.3s ease-out;
            max-width: 24rem;
        }
        .toast-success { background: linear-gradient(135deg, rgba(16, 185, 129, 0.9), rgba(5, 150, 105, 0.9)); color: white; }
        .toast-error { background: linear-gradient(135deg, rgba(239, 68, 68, 0.9), rgba(220, 38, 38, 0.9)); color: white; }
        .toast-warning { background: linear-gradient(135deg, rgba(245, 158, 11, 0.9), rgba(217, 119, 6, 0.9)); color: white; }
        .toast-info { background: linear-gradient(135deg, rgba(59, 130, 246, 0.9), rgba(37, 99, 235, 0.9)); color: white; }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Fireflies for Premium */
        .fireflies-fixed-layer {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 1;
            overflow: hidden;
        }

        .firefly-wrapper {
            position: absolute;
        }

        .firefly-core {
            border-radius: 50%;
        }

        .firefly-gold {
            background: radial-gradient(circle, rgba(251, 191, 36, 1) 0%, rgba(245, 158, 11, 0.8) 40%, transparent 70%);
            box-shadow: 0 0 10px rgba(251, 191, 36, 0.8), 0 0 20px rgba(251, 191, 36, 0.4);
        }

        .firefly-purple {
            background: radial-gradient(circle, rgba(168, 85, 247, 1) 0%, rgba(139, 92, 246, 0.8) 40%, transparent 70%);
            box-shadow: 0 0 10px rgba(168, 85, 247, 0.8), 0 0 20px rgba(168, 85, 247, 0.4);
        }

        @keyframes firefly-float {
            0%, 100% { transform: translate(0, 0); }
            25% { transform: translate(50px, -30px); }
            50% { transform: translate(-30px, 50px); }
            75% { transform: translate(30px, 30px); }
        }

        @keyframes firefly-glow {
            0%, 100% { opacity: 0.3; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.2); }
        }

        /* Premium Background */
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
            background-attachment: fixed;
        }
    </style>

    <!-- Custom Head Code (Tracking & Verification) -->
    @php
        $customHeadCode = \App\Models\Setting::getValue('custom_code_head', '');
    @endphp
    @if($customHeadCode)
        {!! $customHeadCode !!}
    @endif
</head>
<body class="min-h-screen premium-scrollbar">
    <!-- Custom Body Start Code (Tracking noscript) -->
    @php
        $customBodyStartCode = \App\Models\Setting::getValue('custom_code_body_start', '');
    @endphp
    @if($customBodyStartCode)
        {!! $customBodyStartCode !!}
    @endif

    <!-- Premium Fireflies Effect -->
    <div id="fireflies-layer" class="fireflies-fixed-layer"></div>

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Premium Navigation -->
    <nav class="premium-nav sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo & Desktop Menu -->
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        @php
                            $siteLogo = \App\Models\Setting::getValue('site_logo');
                        @endphp
                        <a href="/" class="flex items-center group">
                            @if($siteLogo)
                                {{-- Logo only - full width, no frame, no text (Classic style) --}}
                                <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ config('app.name', 'XMAN STUDIO') }}" class="h-12 w-auto max-w-[240px] object-contain transition-all duration-300 group-hover:opacity-80">
                            @else
                                {{-- Fallback: Show icon with text when no logo --}}
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg group-hover:shadow-indigo-500/50 transition-all duration-300 mr-3">
                                    <span class="text-white font-bold text-lg">X</span>
                                </div>
                                <span class="text-xl font-bold bg-gradient-to-r from-indigo-200 to-purple-200 bg-clip-text text-transparent hidden sm:block">
                                    XMAN STUDIO
                                </span>
                            @endif
                        </a>
                    </div>
                    <div class="hidden md:ml-8 md:flex md:items-center md:space-x-1">
                        <a href="/" class="premium-nav-link px-4 py-2 text-sm font-medium rounded-lg transition-all duration-300 {{ request()->is('/') ? 'active text-white bg-white/10' : 'text-indigo-200 hover:text-white hover:bg-white/5' }}">
                            หน้าหลัก
                        </a>
                        <a href="/services" class="premium-nav-link px-4 py-2 text-sm font-medium rounded-lg transition-all duration-300 {{ request()->is('services*') ? 'active text-white bg-white/10' : 'text-indigo-200 hover:text-white hover:bg-white/5' }}">
                            บริการ
                        </a>
                        <a href="/products" class="premium-nav-link px-4 py-2 text-sm font-medium rounded-lg transition-all duration-300 {{ request()->is('products*') ? 'active text-white bg-white/10' : 'text-indigo-200 hover:text-white hover:bg-white/5' }}">
                            ผลิตภัณฑ์
                        </a>
                        <a href="/rental" class="premium-nav-link px-4 py-2 text-sm font-medium rounded-lg transition-all duration-300 {{ request()->is('rental*') ? 'active text-white bg-white/10' : 'text-indigo-200 hover:text-white hover:bg-white/5' }}">
                            เช่าบริการ
                        </a>
                        <a href="/portfolio" class="premium-nav-link px-4 py-2 text-sm font-medium rounded-lg transition-all duration-300 {{ request()->is('portfolio*') ? 'active text-white bg-white/10' : 'text-indigo-200 hover:text-white hover:bg-white/5' }}">
                            ผลงาน
                        </a>
                        <a href="/support" class="premium-nav-link px-4 py-2 text-sm font-medium rounded-lg transition-all duration-300 {{ request()->is('support*') ? 'active text-white bg-white/10' : 'text-indigo-200 hover:text-white hover:bg-white/5' }}">
                            ติดต่อ/สั่งซื้อ
                        </a>
                    </div>
                </div>

                <!-- Right Side Icons -->
                <div class="flex items-center space-x-3">
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
                    <a href="/cart" class="relative p-2 text-indigo-300 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        @if($cartCount > 0)
                        <span class="absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-gradient-to-r from-pink-500 to-rose-500 rounded-full animate-pulse">{{ $cartCount }}</span>
                        @endif
                    </a>

                    <!-- Notifications -->
                    @auth
                    @php
                        $unreadNotifications = auth()->user()->unreadNotifications()->count();

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
                        <button @click="open = !open" class="relative p-2 text-indigo-300 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if($totalAlerts > 0)
                            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white {{ $licenseAlertCount > 0 ? 'bg-gradient-to-r from-amber-500 to-orange-500' : 'bg-gradient-to-r from-red-500 to-pink-500' }} rounded-full animate-pulse">{{ $totalAlerts > 9 ? '9+' : $totalAlerts }}</span>
                            @endif
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-80 premium-card rounded-xl shadow-2xl py-2 z-50">
                            <div class="px-4 py-2 border-b border-indigo-500/20">
                                <h3 class="text-sm font-semibold text-white">การแจ้งเตือน</h3>
                            </div>
                            <div class="max-h-80 overflow-y-auto premium-scrollbar">
                                @foreach($expiredLicenses as $license)
                                    <a href="{{ route('products.show', $license->product->slug ?? 'products') }}" class="block px-4 py-3 hover:bg-white/5 bg-red-500/10 border-l-4 border-red-500">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0">
                                                <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <p class="text-sm font-medium text-red-400">License หมดอายุแล้ว!</p>
                                                <p class="text-xs text-indigo-300 mt-1">{{ $license->product->name ?? 'Product' }}</p>
                                                <p class="text-xs text-red-400 mt-1">กรุณาต่ออายุเพื่อใช้งานต่อ</p>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach

                                @foreach($expiringLicenses as $license)
                                    @php $daysLeft = max(0, (int) now()->diffInDays($license->expires_at, false)); @endphp
                                    <a href="{{ route('products.show', $license->product->slug ?? 'products') }}" class="block px-4 py-3 hover:bg-white/5 bg-amber-500/10 border-l-4 border-amber-500">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0">
                                                <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <p class="text-sm font-medium text-amber-400">License ใกล้หมดอายุ!</p>
                                                <p class="text-xs text-indigo-300 mt-1">{{ $license->product->name ?? 'Product' }}</p>
                                                <p class="text-xs text-amber-400 mt-1">เหลืออีก {{ $daysLeft }} วัน</p>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach

                                @forelse(auth()->user()->notifications()->take(5)->get() as $notification)
                                    <a href="{{ $notification->data['url'] ?? '#' }}" class="block px-4 py-3 hover:bg-white/5 {{ $notification->read_at ? '' : 'bg-indigo-500/10' }}">
                                        <p class="text-sm text-white">{{ $notification->data['message'] ?? 'การแจ้งเตือนใหม่' }}</p>
                                        <p class="text-xs text-indigo-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                    </a>
                                @empty
                                    @if($licenseAlertCount == 0)
                                    <div class="px-4 py-6 text-center text-indigo-400">
                                        <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                        </svg>
                                        <p class="text-sm">ไม่มีการแจ้งเตือน</p>
                                    </div>
                                    @endif
                                @endforelse
                            </div>
                            @if(auth()->user()->notifications()->count() > 0 || $licenseAlertCount > 0)
                            <div class="px-4 py-2 border-t border-indigo-500/20 flex justify-between">
                                <a href="{{ route('customer.dashboard') }}" class="text-sm text-indigo-400 hover:text-indigo-200 transition-colors">ดูทั้งหมด</a>
                                @if($licenseAlertCount > 0)
                                <a href="{{ route('products.index') }}" class="text-sm text-amber-400 hover:text-amber-200 transition-colors">ดู License</a>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                    @endauth

                    <!-- User Menu / Login -->
                    @auth
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 p-2 text-indigo-300 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-300">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-purple-600 flex items-center justify-center">
                                    <span class="text-white font-semibold text-sm">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                                </div>
                                <span class="text-sm font-medium hidden sm:block">{{ Auth::user()->name }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 premium-card rounded-xl shadow-2xl py-1 z-50">
                                @if(Auth::user()->isAdmin())
                                <a href="/admin/rentals" class="block px-4 py-2 text-sm text-indigo-200 hover:text-white hover:bg-white/10 transition-colors">Admin Panel</a>
                                @endif
                                <a href="{{ route('customer.dashboard') }}" class="block px-4 py-2 text-sm text-indigo-200 hover:text-white hover:bg-white/10 transition-colors">บัญชีของฉัน</a>
                                @if(Route::has('logout'))
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-400 hover:text-red-300 hover:bg-white/10 transition-colors">ออกจากระบบ</button>
                                </form>
                                @endif
                            </div>
                        </div>
                    @else
                        @if(Route::has('register'))
                        <a href="{{ route('register') }}" class="hidden sm:inline-flex items-center px-4 py-2 text-sm font-medium text-indigo-200 hover:text-white transition-colors">
                            สมัครสมาชิก
                        </a>
                        @endif
                        @if(Route::has('login'))
                        <a href="{{ route('login') }}" class="hidden sm:inline-flex items-center px-4 py-2 text-sm font-medium text-white premium-btn rounded-lg">
                            เข้าสู่ระบบ
                        </a>
                        @endif
                    @endauth

                    <!-- Mobile Menu Button (Hamburger) -->
                    <button id="mobileMenuBtn" type="button" class="md:hidden p-2 text-indigo-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors" aria-label="Toggle menu" aria-expanded="false">
                        <!-- Hamburger Icon -->
                        <svg id="hamburgerIcon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <!-- Close Icon (hidden by default) -->
                        <svg id="closeIcon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobileMenu" class="hidden md:hidden border-t border-indigo-500/20">
            <div class="px-4 py-3 space-y-2">
                <a href="/" class="block px-4 py-2.5 text-base font-medium rounded-lg transition-all duration-300 {{ request()->is('/') ? 'bg-white/10 text-white' : 'text-indigo-200 hover:bg-white/5 hover:text-white' }}">หน้าหลัก</a>
                <a href="/services" class="block px-4 py-2.5 text-base font-medium rounded-lg transition-all duration-300 {{ request()->is('services*') ? 'bg-white/10 text-white' : 'text-indigo-200 hover:bg-white/5 hover:text-white' }}">บริการ</a>
                <a href="/products" class="block px-4 py-2.5 text-base font-medium rounded-lg transition-all duration-300 {{ request()->is('products*') ? 'bg-white/10 text-white' : 'text-indigo-200 hover:bg-white/5 hover:text-white' }}">ผลิตภัณฑ์</a>
                <a href="/rental" class="block px-4 py-2.5 text-base font-medium rounded-lg transition-all duration-300 {{ request()->is('rental*') ? 'bg-white/10 text-white' : 'text-indigo-200 hover:bg-white/5 hover:text-white' }}">เช่าบริการ</a>
                <a href="/portfolio" class="block px-4 py-2.5 text-base font-medium rounded-lg transition-all duration-300 {{ request()->is('portfolio*') ? 'bg-white/10 text-white' : 'text-indigo-200 hover:bg-white/5 hover:text-white' }}">ผลงาน</a>
                <a href="/support" class="block px-4 py-2.5 text-base font-medium rounded-lg transition-all duration-300 {{ request()->is('support*') ? 'bg-white/10 text-white' : 'text-indigo-200 hover:bg-white/5 hover:text-white' }}">ติดต่อ/สั่งซื้อ</a>
                @guest
                    @if(Route::has('register'))
                    <a href="{{ route('register') }}" class="block px-4 py-2.5 text-base font-medium text-indigo-200 hover:bg-white/5 hover:text-white rounded-lg">สมัครสมาชิก</a>
                    @endif
                    @if(Route::has('login'))
                    <a href="{{ route('login') }}" class="block px-4 py-2.5 text-base font-medium text-white premium-btn rounded-lg text-center">เข้าสู่ระบบ</a>
                    @endif
                @endguest
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="animate-fade-in">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-gradient-to-r from-emerald-500/20 to-green-500/20 border border-emerald-500/30 text-emerald-200 px-4 py-3 rounded-xl flex items-center justify-between" role="alert">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.parentElement.parentElement.remove()" class="text-emerald-300 hover:text-emerald-100">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="animate-fade-in">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-gradient-to-r from-red-500/20 to-rose-500/20 border border-red-500/30 text-red-200 px-4 py-3 rounded-xl flex items-center justify-between" role="alert">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.parentElement.parentElement.remove()" class="text-red-300 hover:text-red-100">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Main Content -->
    <main class="min-h-screen relative z-10">
        @yield('content')
    </main>

    <!-- Premium Footer -->
    <footer class="relative z-30 border-t border-indigo-500/20 bg-gradient-to-b from-transparent to-black/50">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div class="lg:col-span-1">
                    @php
                        $siteLogo = \App\Models\Setting::getValue('site_logo');
                    @endphp
                    <div class="flex items-center gap-3 mb-4">
                        @if($siteLogo)
                            {{-- Logo only - full width, no frame, no text (Classic style) --}}
                            <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ config('app.name', 'XMAN STUDIO') }}" class="h-12 w-auto max-w-[240px] object-contain">
                        @else
                            {{-- Fallback: Show icon with text when no logo --}}
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
                                <span class="text-white font-bold text-lg">X</span>
                            </div>
                            <span class="text-xl font-bold bg-gradient-to-r from-indigo-200 to-purple-200 bg-clip-text text-transparent">XMAN STUDIO</span>
                        @endif
                    </div>
                    <p class="text-indigo-300/60 mb-4">ผู้เชี่ยวชาญด้าน IT Solutions ครบวงจร พัฒนาซอฟต์แวร์และบริการเทคโนโลยีสารสนเทศ</p>
                    <!-- Social Links -->
                    <div class="flex space-x-4">
                        <a href="https://youtube.com/@metal-xproject" target="_blank" class="p-2 text-indigo-400 hover:text-red-400 hover:bg-white/5 rounded-lg transition-all duration-300">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                        </a>
                        <a href="#" class="p-2 text-indigo-400 hover:text-green-400 hover:bg-white/5 rounded-lg transition-all duration-300">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                            </svg>
                        </a>
                        <a href="#" class="p-2 text-indigo-400 hover:text-blue-400 hover:bg-white/5 rounded-lg transition-all duration-300">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Services -->
                <div>
                    <h4 class="text-lg font-semibold text-white mb-4">บริการ</h4>
                    <ul class="space-y-2">
                        <li><a href="/services" class="text-indigo-300/60 hover:text-indigo-200 transition-colors">Blockchain Development</a></li>
                        <li><a href="/services" class="text-indigo-300/60 hover:text-indigo-200 transition-colors">พัฒนาเว็บไซต์</a></li>
                        <li><a href="/services" class="text-indigo-300/60 hover:text-indigo-200 transition-colors">แอพพลิเคชัน</a></li>
                        <li><a href="/services" class="text-indigo-300/60 hover:text-indigo-200 transition-colors">AI Services</a></li>
                        <li><a href="/services" class="text-indigo-300/60 hover:text-indigo-200 transition-colors">IoT Solutions</a></li>
                    </ul>
                </div>

                <!-- Help -->
                <div>
                    <h4 class="text-lg font-semibold text-white mb-4">ช่วยเหลือ</h4>
                    <ul class="space-y-2">
                        <li><a href="/support" class="text-indigo-300/60 hover:text-indigo-200 transition-colors">ติดต่อ/สั่งซื้อ</a></li>
                        <li><a href="/rental" class="text-indigo-300/60 hover:text-indigo-200 transition-colors">เช่าบริการ</a></li>
                        <li><a href="/products" class="text-indigo-300/60 hover:text-indigo-200 transition-colors">ผลิตภัณฑ์</a></li>
                        <li><a href="/portfolio" class="text-indigo-300/60 hover:text-indigo-200 transition-colors">ผลงาน</a></li>
                    </ul>

                    <h4 class="text-lg font-semibold text-white mb-4 mt-6">กฎหมาย</h4>
                    <ul class="space-y-2">
                        <li><a href="/privacy" class="text-indigo-300/60 hover:text-indigo-200 transition-colors">นโยบายความเป็นส่วนตัว</a></li>
                        <li><a href="/terms" class="text-indigo-300/60 hover:text-indigo-200 transition-colors">ข้อกำหนดการใช้งาน</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="text-lg font-semibold text-white mb-4">ติดต่อเรา</h4>
                    <ul class="space-y-3">
                        <li class="flex items-center text-indigo-300/60">
                            <svg class="w-5 h-5 mr-2 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            info@xmanstudio.com
                        </li>
                        <li class="flex items-center text-indigo-300/60">
                            <svg class="w-5 h-5 mr-2 text-green-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                            </svg>
                            Line OA: @xmanstudio
                        </li>
                        <li class="flex items-center text-indigo-300/60">
                            <svg class="w-5 h-5 mr-2 text-red-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814z"/>
                                <polygon fill="#fff" points="9.545,15.568 15.818,12 9.545,8.432"/>
                            </svg>
                            Metal-X Project
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="mt-12 pt-8 border-t border-indigo-500/20">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-center md:text-left">
                        <p class="text-indigo-300/60 text-sm">&copy; {{ date('Y') }} {{ config('app.name', 'XMAN Studio') }}. All rights reserved.</p>
                        <p class="text-indigo-400/40 text-xs mt-1">IT Solutions & Software Development</p>
                    </div>
                    <div class="flex flex-wrap justify-center space-x-4 md:space-x-6 mt-4 md:mt-0">
                        <a href="/privacy" class="text-indigo-300/60 hover:text-indigo-200 text-sm transition-colors">นโยบายความเป็นส่วนตัว</a>
                        <a href="/terms" class="text-indigo-300/60 hover:text-indigo-200 text-sm transition-colors">ข้อกำหนดการใช้งาน</a>
                        <a href="/support" class="text-indigo-300/60 hover:text-indigo-200 text-sm transition-colors">ติดต่อเรา</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Mobile Menu Toggle with icon animation
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        const hamburgerIcon = document.getElementById('hamburgerIcon');
        const closeIcon = document.getElementById('closeIcon');

        mobileMenuBtn?.addEventListener('click', () => {
            const isOpen = !mobileMenu?.classList.contains('hidden');

            // Toggle menu visibility
            mobileMenu?.classList.toggle('hidden');

            // Toggle icons
            if (isOpen) {
                hamburgerIcon?.classList.remove('hidden');
                closeIcon?.classList.add('hidden');
                mobileMenuBtn?.setAttribute('aria-expanded', 'false');
            } else {
                hamburgerIcon?.classList.add('hidden');
                closeIcon?.classList.remove('hidden');
                mobileMenuBtn?.setAttribute('aria-expanded', 'true');
            }
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!mobileMenuBtn?.contains(e.target) && !mobileMenu?.contains(e.target)) {
                mobileMenu?.classList.add('hidden');
                hamburgerIcon?.classList.remove('hidden');
                closeIcon?.classList.add('hidden');
                mobileMenuBtn?.setAttribute('aria-expanded', 'false');
            }
        });

        // Toast Notification Function
        window.showToast = function(message, type = 'info', duration = 5000) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
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

        // Premium Fireflies Effect
        (function() {
            const container = document.getElementById('fireflies-layer');
            if (!container) return;

            // Create 25 gold fireflies and 15 purple fireflies
            const fireflies = [
                { count: 25, class: 'firefly-gold' },
                { count: 15, class: 'firefly-purple' }
            ];

            fireflies.forEach(({ count, class: colorClass }) => {
                for (let i = 0; i < count; i++) {
                    const x = Math.random() * 100;
                    const y = Math.random() * 100;
                    const size = 2 + Math.random() * 4;
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
                    core.className = `firefly-core ${colorClass}`;
                    core.style.cssText = `
                        width: ${size}px;
                        height: ${size}px;
                        animation: firefly-glow ${glowDuration}s ease-in-out infinite alternate;
                        animation-delay: ${delay + 0.5}s;
                    `;

                    wrapper.appendChild(core);
                    container.appendChild(wrapper);
                }
            });
        })();
    </script>

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
