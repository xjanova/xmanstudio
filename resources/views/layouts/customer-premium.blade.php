<!DOCTYPE html>
<html lang="th" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'บัญชีของฉัน') - XMAN Studio</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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

        .animate-blob { animation: blob 7s infinite; }
        .animate-slide-in { animation: slideIn 0.5s ease-out; }
        .animate-fade-in { animation: fadeIn 0.5s ease-out; }
        .animate-shimmer {
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }

        /* Premium Sidebar Gradient */
        .premium-sidebar {
            background: linear-gradient(180deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
        }

        /* Premium Nav Item Hover */
        .premium-nav-item {
            position: relative;
            transition: all 0.3s ease;
        }

        .premium-nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(180deg, #818cf8, #c084fc);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .premium-nav-item:hover::before,
        .premium-nav-item.active::before {
            transform: scaleY(1);
        }

        .premium-nav-item:hover,
        .premium-nav-item.active {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Premium Header */
        .premium-header {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(168, 85, 247, 0.1) 100%);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(99, 102, 241, 0.2);
        }

        /* Premium scrollbar */
        .premium-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .premium-scrollbar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
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
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        .toast.hiding { animation: slideOut 0.3s ease-in forwards; }

        /* Premium Content Dark Mode Overrides */
        /* Cards and containers */
        .bg-white {
            background: rgba(30, 27, 75, 0.6) !important;
            border: 1px solid rgba(99, 102, 241, 0.2);
        }

        .bg-gray-50 {
            background: rgba(49, 46, 129, 0.4) !important;
        }

        .bg-gray-100 {
            background: rgba(99, 102, 241, 0.2) !important;
        }

        /* Text colors */
        .text-gray-900 {
            color: #e0e7ff !important;
        }

        .text-gray-800 {
            color: #c7d2fe !important;
        }

        .text-gray-700 {
            color: #a5b4fc !important;
        }

        .text-gray-600 {
            color: #a5b4fc !important;
        }

        .text-gray-500 {
            color: rgba(165, 180, 252, 0.7) !important;
        }

        .text-gray-400 {
            color: rgba(165, 180, 252, 0.5) !important;
        }

        /* Borders */
        .border-gray-100,
        .border-gray-200,
        .border-gray-300 {
            border-color: rgba(99, 102, 241, 0.2) !important;
        }

        .divide-gray-100 > :not([hidden]) ~ :not([hidden]),
        .divide-gray-200 > :not([hidden]) ~ :not([hidden]) {
            border-color: rgba(99, 102, 241, 0.2) !important;
        }

        /* Shadows */
        .shadow-sm, .shadow, .shadow-md, .shadow-lg {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2) !important;
        }

        /* Hover states */
        .hover\:bg-gray-50:hover {
            background: rgba(99, 102, 241, 0.15) !important;
        }

        .hover\:shadow-md:hover {
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.2), 0 4px 6px -2px rgba(0, 0, 0, 0.3) !important;
        }

        /* Dashed borders for quick actions */
        .border-dashed {
            border-color: rgba(99, 102, 241, 0.3) !important;
        }

        .hover\:border-primary-300:hover,
        .hover\:border-blue-300:hover,
        .hover\:border-orange-300:hover,
        .hover\:border-gray-300:hover {
            border-color: rgba(129, 140, 248, 0.5) !important;
        }

        .hover\:bg-primary-50:hover,
        .hover\:bg-blue-50:hover,
        .hover\:bg-orange-50:hover,
        .hover\:bg-gray-50:hover {
            background: rgba(99, 102, 241, 0.15) !important;
        }

        /* Code blocks */
        code {
            background: rgba(99, 102, 241, 0.2) !important;
            color: #c7d2fe !important;
        }

        /* Alert/Banner backgrounds - keep gradients but adjust */
        .bg-gradient-to-r.from-red-50,
        .bg-gradient-to-r.from-amber-50,
        .bg-gradient-to-r.from-yellow-50 {
            background: rgba(30, 27, 75, 0.8) !important;
        }

        /* Table styles */
        table {
            border-color: rgba(99, 102, 241, 0.2);
        }

        th {
            background: rgba(49, 46, 129, 0.5) !important;
            color: #c7d2fe !important;
        }

        td {
            border-color: rgba(99, 102, 241, 0.2) !important;
        }

        /* Form inputs */
        input:not([type="checkbox"]):not([type="radio"]),
        select,
        textarea {
            background: rgba(30, 27, 75, 0.8) !important;
            border-color: rgba(99, 102, 241, 0.3) !important;
            color: #e0e7ff !important;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: rgba(129, 140, 248, 0.6) !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2) !important;
        }

        input::placeholder,
        textarea::placeholder {
            color: rgba(165, 180, 252, 0.5) !important;
        }

        /* Labels */
        label {
            color: #c7d2fe !important;
        }
    </style>
</head>
<body class="bg-gray-900 overflow-hidden">
    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>

    <div class="h-screen flex">
        <!-- Premium Sidebar -->
        <aside class="w-64 premium-sidebar text-white flex-shrink-0 hidden md:flex md:flex-col h-screen">
            <!-- Logo Section with Glow -->
            <div class="p-6 border-b border-indigo-500/20 flex-shrink-0">
                @php
                    $siteLogo = \App\Models\Setting::getValue('site_logo');
                @endphp
                <a href="/" class="flex items-center gap-3 group">
                    @if($siteLogo)
                        <img src="{{ asset('storage/' . $siteLogo) }}" alt="XMAN STUDIO" class="h-10 w-auto">
                    @else
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg group-hover:shadow-indigo-500/50 transition-all duration-300">
                            <span class="text-white font-bold text-lg">X</span>
                        </div>
                        <div>
                            <span class="text-lg font-bold bg-gradient-to-r from-indigo-200 to-purple-200 bg-clip-text text-transparent">XMAN</span>
                            <span class="text-lg font-light text-indigo-300">Studio</span>
                        </div>
                    @endif
                </a>
                <p class="text-sm text-indigo-400/60 mt-2">ศูนย์จัดการบัญชีสมาชิก</p>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-1 overflow-y-auto premium-scrollbar">
                <p class="px-4 py-2 text-xs font-semibold text-indigo-400/50 uppercase tracking-wider">เมนูหลัก</p>

                <a href="{{ route('customer.dashboard') }}"
                   class="premium-nav-item flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.dashboard') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-400 to-green-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </div>
                    <span class="text-indigo-100">แดชบอร์ด</span>
                </a>

                <a href="{{ route('customer.licenses') }}"
                   class="premium-nav-item flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.licenses*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                    </div>
                    <span class="text-indigo-100">ใบอนุญาต</span>
                </a>

                <a href="{{ route('customer.subscriptions') }}"
                   class="premium-nav-item flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.subscriptions*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-400 to-purple-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <span class="text-indigo-100">การสมัครสมาชิก</span>
                </a>

                <p class="px-4 py-2 mt-4 text-xs font-semibold text-indigo-400/50 uppercase tracking-wider">ธุรกรรม</p>

                <a href="{{ route('customer.orders') }}"
                   class="premium-nav-item flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.orders*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-orange-400 to-amber-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <span class="text-indigo-100">คำสั่งซื้อ</span>
                </a>

                <a href="{{ route('customer.invoices') }}"
                   class="premium-nav-item flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.invoices') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-rose-400 to-pink-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <span class="text-indigo-100">ใบแจ้งหนี้</span>
                </a>

                <a href="{{ route('customer.downloads') }}"
                   class="premium-nav-item flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.downloads') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-400 to-emerald-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </div>
                    <span class="text-indigo-100">ดาวน์โหลด</span>
                </a>

                <a href="{{ route('customer.projects') }}"
                   class="premium-nav-item flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.projects*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <span class="text-indigo-100">โครงการของฉัน</span>
                </a>

                <p class="px-4 py-2 mt-4 text-xs font-semibold text-indigo-400/50 uppercase tracking-wider">ช่วยเหลือ</p>

                <a href="{{ route('customer.support.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.support*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-400 to-blue-600 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                    </div>
                    <span class="text-indigo-100">ติดต่อสนับสนุน</span>
                </a>

                <a href="{{ route('profile.edit') }}"
                   class="premium-nav-item flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('profile.*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-fuchsia-400 to-pink-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <span class="text-indigo-100">โปรไฟล์</span>
                </a>
            </nav>

            <!-- User Card -->
            <div class="p-4 border-t border-indigo-500/20 bg-indigo-950/50 flex-shrink-0">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        @if(auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}"
                                 class="h-10 w-10 rounded-full object-cover ring-2 ring-indigo-500/50">
                        @else
                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-400 to-purple-600 flex items-center justify-center ring-2 ring-indigo-500/50">
                                <span class="text-white font-semibold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="ml-3 flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-indigo-300/60 truncate">{{ auth()->user()->email }}</p>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="ml-2">
                        @csrf
                        <button type="submit" class="p-2 text-indigo-300/60 hover:text-red-400 transition-colors" title="ออกจากระบบ">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
            <!-- Premium Top Bar -->
            <header class="premium-header flex-shrink-0 z-10">
                <div class="flex items-center justify-between px-4 sm:px-6 py-4">
                    <div class="flex items-center">
                        <!-- Mobile menu button -->
                        <button type="button" class="md:hidden mr-4 p-2 text-indigo-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors" onclick="toggleMobileMenu()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                        <div>
                            <h1 class="text-xl font-bold bg-gradient-to-r from-indigo-200 to-purple-200 bg-clip-text text-transparent">@yield('page-title', 'แดชบอร์ด')</h1>
                            <p class="text-sm text-indigo-300/60 hidden sm:block">@yield('page-description', '')</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 sm:space-x-4">
                        <a href="/" class="p-2 text-indigo-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors" title="หน้าแรก">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        </a>
                        <a href="{{ route('customer.support.create') }}" class="hidden sm:flex items-center px-4 py-2 text-sm font-medium text-indigo-100 hover:text-white bg-indigo-600/30 hover:bg-indigo-600/50 rounded-lg transition-all border border-indigo-500/30">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            ขอความช่วยเหลือ
                        </a>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-4 sm:p-6 overflow-y-auto premium-scrollbar">
                @if(session('success'))
                    <div class="mb-6 bg-gradient-to-r from-emerald-500/20 to-green-500/20 border border-emerald-500/30 text-emerald-200 px-4 py-3 rounded-xl flex items-center animate-fade-in">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 bg-gradient-to-r from-red-500/20 to-rose-500/20 border border-red-500/30 text-red-200 px-4 py-3 rounded-xl flex items-center animate-fade-in">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mb-6 bg-gradient-to-r from-yellow-500/20 to-amber-500/20 border border-yellow-500/30 text-yellow-200 px-4 py-3 rounded-xl flex items-center animate-fade-in">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ session('warning') }}</span>
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="bg-gray-900/50 border-t border-indigo-500/20 py-3 px-6 flex-shrink-0">
                <div class="flex flex-col sm:flex-row justify-between items-center text-xs text-indigo-300/60">
                    <div class="flex items-center space-x-2">
                        <span>&copy; {{ date('Y') }} XMAN Studio. สงวนลิขสิทธิ์</span>
                        <span class="hidden sm:inline">|</span>
                        <span class="hidden sm:inline">MIT License</span>
                    </div>
                    <div class="flex items-center space-x-4 mt-1 sm:mt-0">
                        <span class="text-indigo-400/50">Version {{ trim(file_get_contents(base_path('VERSION'))) }}</span>
                        <a href="{{ route('customer.support.create') }}" class="hover:text-indigo-200 transition-colors">ติดต่อเรา</a>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="fixed inset-0 z-50 hidden md:hidden">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm" onclick="toggleMobileMenu()"></div>

        <!-- Menu Panel -->
        <div class="fixed inset-y-0 left-0 w-72 premium-sidebar shadow-2xl transform transition-transform duration-300">
            <!-- Header -->
            <div class="p-4 border-b border-indigo-500/20 flex justify-between items-center bg-gradient-to-r from-indigo-600 to-purple-600">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">X</span>
                    </div>
                    <span class="font-bold text-white">XMAN Studio</span>
                </div>
                <button onclick="toggleMobileMenu()" class="p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- User Info -->
            <div class="p-4 bg-indigo-950/50 border-b border-indigo-500/20">
                <div class="flex items-center">
                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-400 to-purple-600 flex items-center justify-center">
                        <span class="text-white font-semibold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-indigo-300/60">{{ auth()->user()->email }}</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="p-4 space-y-1 overflow-y-auto max-h-[calc(100vh-200px)] premium-scrollbar">
                <a href="{{ route('customer.dashboard') }}" class="premium-nav-item flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.dashboard') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-400 to-green-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </div>
                    <span class="text-indigo-100">แดชบอร์ด</span>
                </a>

                <a href="{{ route('customer.licenses') }}" class="premium-nav-item flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.licenses*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                    </div>
                    <span class="text-indigo-100">ใบอนุญาต</span>
                </a>

                <a href="{{ route('customer.subscriptions') }}" class="premium-nav-item flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.subscriptions*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-400 to-purple-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <span class="text-indigo-100">การสมัครสมาชิก</span>
                </a>

                <div class="pt-2 pb-1">
                    <p class="px-4 text-xs font-semibold text-indigo-400/50 uppercase">ธุรกรรม</p>
                </div>

                <a href="{{ route('customer.orders') }}" class="premium-nav-item flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.orders*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-orange-400 to-amber-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <span class="text-indigo-100">คำสั่งซื้อ</span>
                </a>

                <a href="{{ route('customer.invoices') }}" class="premium-nav-item flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.invoices') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-rose-400 to-pink-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <span class="text-indigo-100">ใบแจ้งหนี้</span>
                </a>

                <a href="{{ route('customer.downloads') }}" class="premium-nav-item flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.downloads') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-400 to-emerald-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </div>
                    <span class="text-indigo-100">ดาวน์โหลด</span>
                </a>

                <a href="{{ route('customer.projects') }}" class="premium-nav-item flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.projects*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <span class="text-indigo-100">โครงการของฉัน</span>
                </a>

                <div class="pt-2 pb-1">
                    <p class="px-4 text-xs font-semibold text-indigo-400/50 uppercase">ช่วยเหลือ</p>
                </div>

                <a href="{{ route('customer.support.index') }}" class="premium-nav-item flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.support*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-400 to-blue-600 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                    </div>
                    <span class="text-indigo-100">ติดต่อสนับสนุน</span>
                </a>

                <a href="{{ route('profile.edit') }}" class="premium-nav-item flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('profile.*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-fuchsia-400 to-pink-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <span class="text-indigo-100">โปรไฟล์</span>
                </a>
            </nav>

            <!-- Logout -->
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-indigo-500/20 bg-indigo-950/80">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center px-4 py-2 text-red-400 hover:text-red-300 hover:bg-red-500/10 rounded-lg transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        ออกจากระบบ
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Mobile Menu Toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }

        // Toast Notification System
        function showToast(message, type = 'info', duration = 4000) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;

            const icons = {
                success: '<svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>',
                error: '<svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>',
                warning: '<svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>',
                info: '<svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>'
            };

            toast.innerHTML = `
                ${icons[type] || icons.info}
                <span class="flex-1">${message}</span>
                <button onclick="this.parentElement.remove()" class="ml-2 opacity-60 hover:opacity-100">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
            `;

            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('hiding');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        // Copy to Clipboard with Toast
        function copyToClipboard(text, successMessage = 'คัดลอกเรียบร้อยแล้ว!') {
            navigator.clipboard.writeText(text).then(function() {
                showToast(successMessage, 'success');
            }).catch(function() {
                showToast('ไม่สามารถคัดลอกได้', 'error');
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
