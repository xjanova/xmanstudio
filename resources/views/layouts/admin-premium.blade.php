<!DOCTYPE html>
<html lang="th" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - XMAN Studio Admin</title>

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

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 5px rgba(99, 102, 241, 0.5); }
            50% { box-shadow: 0 0 20px rgba(99, 102, 241, 0.8); }
        }

        .animate-blob { animation: blob 7s infinite; }
        .animate-slide-in { animation: slideIn 0.5s ease-out; }
        .animate-fade-in { animation: fadeIn 0.5s ease-out; }
        .animation-delay-2000 { animation-delay: 2s; }
        .animation-delay-4000 { animation-delay: 4s; }

        /* Premium Sidebar Gradient */
        .premium-sidebar {
            background: linear-gradient(180deg, #1e1b4b 0%, #312e81 50%, #4c1d95 100%);
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

        /* Dashed borders */
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

        /* Alert/Banner backgrounds */
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

        /* Pagination */
        .pagination a, .pagination span {
            background: rgba(30, 27, 75, 0.6) !important;
            border-color: rgba(99, 102, 241, 0.2) !important;
            color: #a5b4fc !important;
        }

        .pagination a:hover {
            background: rgba(99, 102, 241, 0.3) !important;
        }

        /* Dropdown menus */
        [x-show], .dropdown-menu {
            background: rgba(30, 27, 75, 0.95) !important;
            border-color: rgba(99, 102, 241, 0.2) !important;
        }
    </style>
</head>
<body class="bg-gray-900 overflow-hidden">
    <div class="h-screen flex">
        <!-- Premium Sidebar -->
        <aside class="w-64 premium-sidebar text-white flex-shrink-0 h-screen overflow-y-auto premium-scrollbar">
            <!-- Logo Section with Glow -->
            <div class="p-4 sticky top-0 z-10" style="background: linear-gradient(180deg, #1e1b4b 0%, rgba(30, 27, 75, 0.9) 100%);">
                @php
                    $siteLogo = \App\Models\Setting::getValue('site_logo');
                @endphp
                <a href="/admin" class="flex items-center gap-3 group">
                    @if($siteLogo)
                        <img src="{{ asset('storage/' . $siteLogo) }}" alt="XMAN Admin" class="h-10 w-auto">
                    @else
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg group-hover:shadow-indigo-500/50 transition-all duration-300">
                            <span class="text-white font-bold text-lg">X</span>
                        </div>
                        <div>
                            <span class="text-lg font-bold bg-gradient-to-r from-indigo-200 to-purple-200 bg-clip-text text-transparent">XMAN Admin</span>
                            <div class="text-xs text-indigo-300/70">Premium Dashboard</div>
                        </div>
                    @endif
                </a>
            </div>

            <nav class="mt-2 px-2 space-y-1">
                <a href="{{ route('admin.analytics.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.analytics*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-400 to-cyan-400 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    Analytics Dashboard
                </a>

                <a href="{{ route('admin.mockup') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.mockup') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-400 to-purple-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                    </div>
                    Premium Dashboard ✨
                </a>

                <div class="px-4 py-3 mt-4">
                    <span class="text-xs font-semibold text-indigo-300/50 uppercase tracking-wider">การเช่า</span>
                </div>

                <a href="{{ route('admin.rentals.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.rentals.index') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-400 to-green-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    รายการเช่า
                </a>

                <a href="{{ route('admin.rentals.payments') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.rentals.payments') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    การชำระเงิน
                </a>

                <a href="{{ route('admin.rentals.packages') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.rentals.packages*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-400 to-purple-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    แพ็กเกจ
                </a>

                <a href="{{ route('admin.rentals.reports') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.rentals.reports') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-pink-400 to-rose-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    รายงาน
                </a>

                <div class="px-4 py-3 mt-4">
                    <span class="text-xs font-semibold text-indigo-300/50 uppercase tracking-wider">จัดการเนื้อหา</span>
                </div>

                <a href="{{ route('admin.services.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.services*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-400 to-teal-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    บริการ
                </a>

                <div class="px-4 py-3 mt-4">
                    <span class="text-xs font-semibold text-indigo-300/50 uppercase tracking-wider">ผลิตภัณฑ์ & โปรแกรม</span>
                </div>

                <a href="{{ route('admin.products.categories.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.products.categories*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-400 to-blue-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    หมวดหมู่ผลิตภัณฑ์
                </a>

                <a href="{{ route('admin.products.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.products.index') || request()->routeIs('admin.products.create') || request()->routeIs('admin.products.edit') || request()->routeIs('admin.products.versions*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-fuchsia-400 to-pink-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    รายการผลิตภัณฑ์
                </a>

                <div class="px-4 py-3 mt-4">
                    <span class="text-xs font-semibold text-indigo-300/50 uppercase tracking-wider">License & Devices</span>
                </div>

                <a href="{{ route('admin.licenses.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.licenses*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-yellow-400 to-amber-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                    </div>
                    จัดการ License
                </a>

                <a href="{{ route('admin.devices.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.devices*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-400 to-teal-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    จัดการ Devices
                </a>

                <div class="px-4 py-3 mt-4">
                    <span class="text-xs font-semibold text-indigo-300/50 uppercase tracking-wider">จัดการสมาชิก</span>
                </div>

                <a href="{{ route('admin.users.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.users*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    รายชื่อสมาชิก
                    @php
                        $totalUsers = \App\Models\User::count();
                    @endphp
                    <span class="ml-auto bg-white/10 text-indigo-200 text-xs px-2 py-0.5 rounded-full">{{ $totalUsers }}</span>
                </a>

                <div class="px-4 py-3 mt-4">
                    <span class="text-xs font-semibold text-indigo-300/50 uppercase tracking-wider">Line OA</span>
                </div>

                <a href="{{ route('admin.line-messaging.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.line-messaging.index') || request()->routeIs('admin.line-messaging.send') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                    </div>
                    ส่งข้อความ
                </a>

                <a href="{{ route('admin.line-messaging.users') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.line-messaging.users') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-400 to-cyan-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    จัดการ Line UID
                </a>

                <a href="{{ route('admin.line-settings.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.line-settings*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-lime-400 to-green-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    ตั้งค่า Line OA
                </a>

                <div class="px-4 py-3 mt-4">
                    <span class="text-xs font-semibold text-indigo-300/50 uppercase tracking-wider">Metal-X YouTube</span>
                </div>

                <a href="{{ route('admin.metal-x.analytics') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.metal-x.analytics*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-red-400 to-rose-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    Dashboard
                </a>

                <a href="{{ route('admin.metal-x.videos.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.metal-x.videos*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                        </svg>
                    </div>
                    วิดีโอ
                </a>

                <a href="{{ route('admin.metal-x.playlists.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.metal-x.playlists*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                    </div>
                    เพลย์ลิสต์
                </a>

                <a href="{{ route('admin.metal-x.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.metal-x.index') || request()->routeIs('admin.metal-x.create') || request()->routeIs('admin.metal-x.edit') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-pink-400 to-rose-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    สมาชิกทีม
                </a>

                <a href="{{ route('admin.metal-x.settings') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.metal-x.settings*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-slate-400 to-gray-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    ตั้งค่า Channel
                </a>

                <div class="px-4 py-3 mt-4">
                    <span class="text-xs font-semibold text-indigo-300/50 uppercase tracking-wider">ใบสั่งงาน & ราคา</span>
                </div>

                <a href="{{ route('admin.quotations.categories.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.quotations.categories*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    หมวดหมู่บริการ
                </a>

                <a href="{{ route('admin.quotations.options.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.quotations.options*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-400 to-violet-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                    </div>
                    ตัวเลือกบริการ & ราคา
                </a>

                <a href="{{ route('admin.projects.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.projects*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    จัดการโครงการ
                </a>

                <div class="px-4 py-3 mt-4">
                    <span class="text-xs font-semibold text-indigo-300/50 uppercase tracking-wider">Support</span>
                </div>

                <a href="{{ route('admin.support.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.support*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-sky-400 to-blue-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                    </div>
                    Support Tickets
                </a>

                <div class="px-4 py-3 mt-4">
                    <span class="text-xs font-semibold text-indigo-300/50 uppercase tracking-wider">การเงิน & โปรโมชั่น</span>
                </div>

                <a href="{{ route('admin.wallets.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.wallets*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    Wallet
                    @php
                        $pendingTopups = \App\Models\WalletTopup::where('status', 'pending')->count();
                    @endphp
                    @if($pendingTopups > 0)
                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">{{ $pendingTopups }}</span>
                    @endif
                </a>

                <a href="{{ route('admin.coupons.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.coupons*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                        </svg>
                    </div>
                    คูปอง
                </a>

                <div class="px-4 py-3 mt-4">
                    <span class="text-xs font-semibold text-indigo-300/50 uppercase tracking-wider">การตั้งค่า</span>
                </div>

                <a href="{{ route('admin.theme.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.theme*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-400 to-purple-600 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                        </svg>
                    </div>
                    ธีม
                </a>

                <a href="{{ route('admin.branding.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.branding*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-rose-400 to-pink-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    โลโก้และ Favicon
                </a>

                <a href="{{ route('admin.payment-settings.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.payment-settings*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    ตั้งค่าการชำระเงิน
                </a>

                <a href="{{ route('admin.custom-code.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.custom-code*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-gray-400 to-slate-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                    </div>
                    Custom Code
                </a>

                <a href="{{ route('admin.ads-txt.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.ads-txt*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-400 to-sky-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    Ads.txt (Google Ads)
                </a>

                <a href="{{ route('admin.seo.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.seo*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    SEO & Google Search
                </a>

                <a href="{{ route('admin.ads.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.ads*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    Google Ads (โฆษณา)
                </a>

                <a href="{{ route('admin.banners.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.banners*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-fuchsia-400 to-pink-500 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    Banners (แบนเนอร์)
                </a>

                <a href="{{ route('admin.ai-settings.index') }}"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg {{ request()->routeIs('admin.ai-settings*') ? 'active bg-white/10' : '' }}">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-400 to-purple-600 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    AI Settings
                </a>

                <div class="px-4 py-3 mt-4">
                    <span class="text-xs font-semibold text-indigo-300/50 uppercase tracking-wider">อื่นๆ</span>
                </div>

                <a href="/"
                   class="premium-nav-item flex items-center px-4 py-3 text-indigo-100 rounded-lg mb-8">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-slate-500 to-gray-600 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </div>
                    กลับหน้าเว็บ
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
            <!-- Premium Top Bar -->
            <header class="premium-header flex-shrink-0">
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <h1 class="text-xl font-bold bg-gradient-to-r from-indigo-200 to-purple-200 bg-clip-text text-transparent">@yield('page-title', 'Dashboard')</h1>
                        <p class="text-sm text-indigo-300/60">@yield('page-description', '')</p>
                    </div>

                    <div class="flex items-center space-x-4">
                        <a href="/" class="inline-flex items-center px-3 py-2 text-sm font-medium text-indigo-200 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-300 border border-indigo-500/30">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            กลับหน้าเว็บ
                        </a>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
                                <span class="text-white font-bold">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</span>
                            </div>
                            <div class="hidden sm:block">
                                <div class="text-sm font-medium text-white">{{ auth()->user()->name ?? 'Admin' }}</div>
                                <div class="text-xs text-indigo-300/60">Administrator</div>
                            </div>
                        </div>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 text-sm text-indigo-200 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-300">
                                ออกจากระบบ
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-6 overflow-y-auto premium-scrollbar">
                @if(session('success'))
                    <div class="mb-6 bg-gradient-to-r from-emerald-500/20 to-green-500/20 border border-emerald-500/30 text-emerald-200 px-4 py-3 rounded-xl animate-fade-in flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 bg-gradient-to-r from-red-500/20 to-rose-500/20 border border-red-500/30 text-red-200 px-4 py-3 rounded-xl animate-fade-in flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        {{ session('error') }}
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
                    <div class="flex items-center space-x-3 mt-1 sm:mt-0">
                        <span class="text-indigo-400/50">Version {{ trim(file_get_contents(base_path('VERSION'))) }}</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script>
        // Scroll to active menu item on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Find the active menu item in the sidebar
            const sidebar = document.querySelector('aside.premium-sidebar');
            if (sidebar) {
                const activeMenuItem = sidebar.querySelector('a.premium-nav-item.active');
                if (activeMenuItem) {
                    // Scroll the active item into view smoothly
                    activeMenuItem.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest',
                        inline: 'nearest'
                    });
                }
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
