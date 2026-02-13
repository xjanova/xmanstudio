<!DOCTYPE html>
<html lang="th">
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
</head>
<body class="bg-gray-100 overflow-hidden">
    <div class="h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 text-white flex-shrink-0 h-screen overflow-y-auto">
            <div class="p-4 sticky top-0 bg-gray-900 z-10">
                @php
                    $siteLogo = \App\Models\Setting::getValue('site_logo');
                @endphp
                <a href="/admin" class="flex items-center">
                    @if($siteLogo)
                        <img src="{{ asset('storage/' . $siteLogo) }}" alt="XMAN Admin" class="h-8 w-auto">
                    @else
                        <span class="text-xl font-bold">XMAN Admin</span>
                    @endif
                </a>
            </div>

            <nav class="mt-4">
                <a href="{{ route('admin.analytics.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.analytics*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Analytics Dashboard
                </a>

                <a href="{{ route('admin.mockup') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.mockup') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                    Premium Dashboard ✨
                </a>

                <div class="px-4 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    การเช่า
                </div>
                <a href="{{ route('admin.rentals.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.rentals.index') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    รายการเช่า
                </a>
                <a href="{{ route('admin.rentals.payments') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.rentals.payments') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    การชำระเงิน
                </a>
                <a href="{{ route('admin.rentals.packages') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.rentals.packages*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    แพ็กเกจ
                </a>
                <a href="{{ route('admin.rentals.reports') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.rentals.reports') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    รายงาน
                </a>

                <div class="px-4 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    จัดการเนื้อหา
                </div>
                <a href="{{ route('admin.services.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.services*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                    บริการ
                </a>

                <div class="px-4 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    ผลิตภัณฑ์ & โปรแกรม
                </div>
                <a href="{{ route('admin.products.categories.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.products.categories*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    หมวดหมู่ผลิตภัณฑ์
                </a>
                <a href="{{ route('admin.products.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.products.index') || request()->routeIs('admin.products.create') || request()->routeIs('admin.products.edit') || request()->routeIs('admin.products.versions*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    รายการผลิตภัณฑ์
                </a>

                <div class="px-4 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    License & Devices
                </div>
                <a href="{{ route('admin.licenses.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.licenses*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    จัดการ License
                </a>
                <a href="{{ route('admin.devices.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.devices*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    จัดการ Devices
                </a>

                @permission('users.view')
                <div class="px-4 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    จัดการสมาชิก
                </div>
                <a href="{{ route('admin.users.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.users*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    รายชื่อสมาชิก
                    @php
                        $totalUsers = \App\Models\User::count();
                    @endphp
                    <span class="ml-auto bg-gray-700 text-gray-300 text-xs px-2 py-0.5 rounded-full">{{ $totalUsers }}</span>
                </a>
                @endpermission
                @permission('roles.view')
                <a href="{{ route('admin.roles.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.roles*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    จัดการบทบาท
                </a>
                @endpermission

                <div class="px-4 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Line OA
                </div>
                <a href="{{ route('admin.line-messaging.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.line-messaging.index') || request()->routeIs('admin.line-messaging.send') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                    </svg>
                    ส่งข้อความ
                </a>
                <a href="{{ route('admin.line-messaging.users') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.line-messaging.users') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    จัดการ Line UID
                </a>
                <a href="{{ route('admin.line-settings.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.line-settings*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    ตั้งค่า Line OA
                </a>

                <div class="px-4 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Metal-X YouTube
                </div>
                <a href="{{ route('admin.metal-x.analytics') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.metal-x.analytics*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Dashboard
                </a>
                <a href="{{ route('admin.metal-x.videos.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.metal-x.videos*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                    </svg>
                    วิดีโอ
                </a>
                <a href="{{ route('admin.metal-x.playlists.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.metal-x.playlists*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    เพลย์ลิสต์
                </a>
                <a href="{{ route('admin.metal-x.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.metal-x.index') || request()->routeIs('admin.metal-x.create') || request()->routeIs('admin.metal-x.edit') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    สมาชิกทีม
                </a>
                <a href="{{ route('admin.metal-x.settings') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.metal-x.settings*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    ตั้งค่า Channel
                </a>

                <div class="px-4 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    ใบสั่งงาน & ราคา
                </div>
                <a href="{{ route('admin.quotations.categories.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.quotations.categories*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    หมวดหมู่บริการ
                </a>
                <a href="{{ route('admin.quotations.options.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.quotations.options*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    ตัวเลือกบริการ & ราคา
                </a>
                <a href="{{ route('admin.projects.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.projects*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    จัดการโครงการ
                </a>

                <div class="px-4 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Support
                </div>
                <a href="{{ route('admin.support.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.support*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                    Support Tickets
                </a>

                <div class="px-4 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    ใบเสนอราคา & คำสั่งซื้อ
                </div>
                <a href="{{ route('admin.quotations.list') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.quotations.list') || request()->routeIs('admin.quotations.detail') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    ใบเสนอราคา
                    @php
                        $pendingQuotations = \App\Models\Quotation::pending()->count();
                    @endphp
                    @if($pendingQuotations > 0)
                    <span class="ml-auto bg-indigo-500 text-white text-xs px-2 py-0.5 rounded-full">{{ $pendingQuotations }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.orders.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.orders.*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    คำสั่งซื้อทั้งหมด
                    @php
                        $pendingPaymentOrders = \App\Models\Order::where('payment_status', 'verifying')->count();
                    @endphp
                    @if($pendingPaymentOrders > 0)
                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">{{ $pendingPaymentOrders }}</span>
                    @endif
                </a>

                <div class="px-4 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    การเงิน & โปรโมชั่น
                </div>
                <a href="{{ route('admin.wallets.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.wallets.index') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Wallet Dashboard
                </a>
                <a href="{{ route('admin.wallets.wallets') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.wallets.wallets') || request()->routeIs('admin.wallets.show') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    กระเป๋าเงินทั้งหมด
                </a>
                <a href="{{ route('admin.wallets.topups') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.wallets.topups*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    การเติมเงิน
                    @php
                        $pendingTopups = \App\Models\WalletTopup::where('status', 'pending')->count();
                    @endphp
                    @if($pendingTopups > 0)
                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">{{ $pendingTopups }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.wallets.transactions') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.wallets.transactions') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    ธุรกรรม
                </a>
                <a href="{{ route('admin.wallets.bonus-tiers') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.wallets.bonus-tiers') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                    </svg>
                    โบนัสเติมเงิน
                </a>
                <a href="{{ route('admin.wallets.settings') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.wallets.settings') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    ตั้งค่า Wallet
                </a>
                <a href="{{ route('admin.coupons.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.coupons*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                    </svg>
                    คูปอง
                </a>

                <div class="px-4 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    SMS Payment
                </div>
                <a href="{{ route('admin.sms-payment.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.sms-payment.index') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Dashboard
                </a>
                <a href="{{ route('admin.sms-payment.settings') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.sms-payment.settings') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    ตั้งค่าการเชื่อมต่อ
                </a>
                <a href="{{ route('admin.sms-payment.devices') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.sms-payment.devices*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    อุปกรณ์
                    @php
                        $activeDevices = \App\Models\SmsCheckerDevice::where('status', 'active')->count();
                    @endphp
                    @if($activeDevices > 0)
                    <span class="ml-auto bg-green-500 text-white text-xs px-2 py-0.5 rounded-full">{{ $activeDevices }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.sms-payment.notifications') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.sms-payment.notifications*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                    SMS Notifications
                </a>
                <a href="{{ route('admin.sms-payment.pending-orders') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.sms-payment.pending-orders') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    รอตรวจสอบ
                    @php
                        $pendingOrders = \App\Models\Order::whereNotNull('unique_payment_amount_id')
                            ->whereIn('sms_verification_status', ['pending', 'matched'])
                            ->where('payment_status', '!=', 'paid')
                            ->count();
                    @endphp
                    @if($pendingOrders > 0)
                    <span class="ml-auto bg-amber-500 text-white text-xs px-2 py-0.5 rounded-full">{{ $pendingOrders }}</span>
                    @endif
                </a>

                <div class="px-4 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    การตั้งค่า
                </div>
                <a href="{{ route('admin.theme.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.theme*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                    ธีม
                </a>
                <a href="{{ route('admin.branding.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.branding*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    โลโก้และ Favicon
                </a>
                <a href="{{ route('admin.payment-settings.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.payment-settings*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    ตั้งค่าการชำระเงิน
                </a>
                <a href="{{ route('admin.custom-code.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.custom-code*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                    Custom Code
                </a>
                <a href="{{ route('admin.ads-txt.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.ads-txt*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Ads.txt (Google Ads)
                </a>
                <a href="{{ route('admin.seo.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.seo*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    SEO & Google Search
                </a>
                <a href="{{ route('admin.ads.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.ads*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Google Ads (โฆษณา)
                </a>
                <a href="{{ route('admin.banners.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.banners*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Banners (แบนเนอร์)
                </a>
                <a href="{{ route('admin.ai-settings.index') }}"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.ai-settings*') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    AI Settings
                </a>

                <div class="px-4 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    อื่นๆ
                </div>
                <a href="/"
                   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    กลับหน้าเว็บ
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm flex-shrink-0">
                <div class="flex items-center justify-between px-6 py-4">
                    <h1 class="text-xl font-semibold text-gray-900">@yield('page-title', 'Dashboard')</h1>

                    <div class="flex items-center space-x-4">
                        <a href="/" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-primary-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            กลับหน้าเว็บ
                        </a>
                        <span class="text-gray-600">{{ auth()->user()->name ?? 'Admin' }}</span>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-600 hover:text-gray-900">ออกจากระบบ</button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-6 overflow-y-auto">
                @if(session('success'))
                    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 py-3 px-6 flex-shrink-0">
                <div class="flex flex-col sm:flex-row justify-between items-center text-xs text-gray-500">
                    <div class="flex items-center space-x-2">
                        <span>&copy; {{ date('Y') }} XMAN Studio. สงวนลิขสิทธิ์</span>
                        <span class="hidden sm:inline">|</span>
                        <span class="hidden sm:inline">MIT License</span>
                    </div>
                    <div class="flex items-center space-x-3 mt-1 sm:mt-0">
                        <span class="text-gray-400">Version {{ trim(file_get_contents(base_path('VERSION'))) }}</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script>
        // Scroll to active menu item on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Find the active menu item in the sidebar
            const sidebar = document.querySelector('aside.w-64');
            if (sidebar) {
                const activeMenuItem = sidebar.querySelector('a.bg-gray-800.text-white');
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
