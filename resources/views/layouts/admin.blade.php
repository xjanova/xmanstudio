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
        <aside class="w-52 bg-gray-900 text-white flex-shrink-0 h-screen flex flex-col">
            <div class="px-3 py-3 flex-shrink-0 border-b border-gray-800">
                @php
                    $siteLogo = \App\Models\Setting::getValue('site_logo');
                @endphp
                <a href="/admin" class="flex items-center">
                    @if($siteLogo)
                        <img src="{{ asset('storage/' . $siteLogo) }}" alt="XMAN Admin" class="h-7 w-auto">
                    @else
                        <span class="text-lg font-bold">XMAN Admin</span>
                    @endif
                </a>
            </div>

            <nav class="flex-1 overflow-y-auto px-2 py-2 space-y-0.5" x-data>
                @include('partials.admin-sidebar-nav')
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
            const sidebar = document.querySelector('aside');
            if (sidebar) {
                const activeMenuItem = sidebar.querySelector('a.bg-white\\/10');
                if (activeMenuItem) {
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
