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
            background: rgba(255, 255, 255, 0.05);
        }

        .premium-scrollbar::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #818cf8, #c084fc);
            border-radius: 3px;
        }

        /* ===== Premium Sidebar Nav Overrides ===== */
        /* Section headers - prominent premium style */
        .premium-sidebar nav button,
        .premium-sidebar nav > div > div:first-child:not(a) {
            color: rgba(196, 181, 253, 0.7) !important;
            border-bottom-color: rgba(129, 140, 248, 0.15) !important;
        }
        .premium-sidebar nav button:hover {
            color: rgba(232, 225, 255, 0.95) !important;
        }

        /* Nav links - glass morphism depth */
        .premium-sidebar nav a {
            position: relative;
            border: 1px solid transparent;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .premium-sidebar nav a:hover {
            background: rgba(255, 255, 255, 0.08) !important;
            border-color: rgba(129, 140, 248, 0.15);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.05);
            transform: translateX(2px);
        }

        /* Active link - premium glow */
        .premium-sidebar nav a.bg-white\/10 {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(168, 85, 247, 0.15)) !important;
            border-color: rgba(129, 140, 248, 0.25) !important;
            box-shadow: 0 2px 12px rgba(99, 102, 241, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }
        .premium-sidebar nav a.bg-white\/10::before {
            content: '';
            position: absolute;
            left: 0;
            top: 4px;
            bottom: 4px;
            width: 3px;
            border-radius: 0 2px 2px 0;
            background: linear-gradient(180deg, #818cf8, #c084fc);
            box-shadow: 0 0 8px rgba(129, 140, 248, 0.5);
        }

        /* Collapsible sections - subtle depth */
        .premium-sidebar nav [x-show] {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            padding: 2px 0;
            margin: 2px 0;
        }

        /* Badges - premium glow */
        .premium-sidebar nav span.bg-red-500,
        .premium-sidebar nav span.bg-yellow-600,
        .premium-sidebar nav span.bg-indigo-500,
        .premium-sidebar nav span.bg-amber-500,
        .premium-sidebar nav span.bg-green-500 {
            box-shadow: 0 0 6px currentColor;
        }

        /* Chevron & icon colors */
        .premium-sidebar nav button svg {
            color: rgba(129, 140, 248, 0.5);
        }
        .premium-sidebar nav svg {
            color: rgba(165, 180, 252, 0.7);
        }
        .premium-sidebar nav a:hover svg,
        .premium-sidebar nav a.bg-white\/10 svg {
            color: rgba(199, 210, 254, 0.95);
        }

        /* ===== Premium Content Dark Mode Overrides ===== */
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
        .text-gray-900 { color: #e0e7ff !important; }
        .text-gray-800 { color: #c7d2fe !important; }
        .text-gray-700 { color: #a5b4fc !important; }
        .text-gray-600 { color: #a5b4fc !important; }
        .text-gray-500 { color: rgba(165, 180, 252, 0.7) !important; }
        .text-gray-400 { color: rgba(165, 180, 252, 0.5) !important; }

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
        .hover\:bg-gray-50:hover { background: rgba(99, 102, 241, 0.15) !important; }
        .hover\:shadow-md:hover { box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.2), 0 4px 6px -2px rgba(0, 0, 0, 0.3) !important; }

        /* Dashed borders */
        .border-dashed { border-color: rgba(99, 102, 241, 0.3) !important; }

        .hover\:border-primary-300:hover,
        .hover\:border-blue-300:hover,
        .hover\:border-orange-300:hover,
        .hover\:border-gray-300:hover {
            border-color: rgba(129, 140, 248, 0.5) !important;
        }

        .hover\:bg-primary-50:hover,
        .hover\:bg-blue-50:hover,
        .hover\:bg-orange-50:hover {
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
        table { border-color: rgba(99, 102, 241, 0.2); }
        th { background: rgba(49, 46, 129, 0.5) !important; color: #c7d2fe !important; }
        td { border-color: rgba(99, 102, 241, 0.2) !important; }

        /* Form inputs */
        input:not([type="checkbox"]):not([type="radio"]),
        select,
        textarea {
            background: rgba(30, 27, 75, 0.8) !important;
            border-color: rgba(99, 102, 241, 0.3) !important;
            color: #e0e7ff !important;
        }

        input:focus, select:focus, textarea:focus {
            border-color: rgba(129, 140, 248, 0.6) !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2) !important;
        }

        input::placeholder, textarea::placeholder {
            color: rgba(165, 180, 252, 0.5) !important;
        }

        label { color: #c7d2fe !important; }

        /* Pagination */
        .pagination a, .pagination span {
            background: rgba(30, 27, 75, 0.6) !important;
            border-color: rgba(99, 102, 241, 0.2) !important;
            color: #a5b4fc !important;
        }
        .pagination a:hover { background: rgba(99, 102, 241, 0.3) !important; }

        /* Dropdown menus */
        [x-show]:not(.premium-sidebar [x-show]), .dropdown-menu {
            background: rgba(30, 27, 75, 0.95) !important;
            border-color: rgba(99, 102, 241, 0.2) !important;
        }
    </style>
</head>
<body class="bg-gray-900 overflow-hidden">
    <div class="h-screen flex">
        <!-- Premium Sidebar -->
        <aside class="w-52 premium-sidebar text-white flex-shrink-0 h-screen flex flex-col">
            <!-- Logo Section with Glow -->
            <div class="px-3 py-3 flex-shrink-0 border-b border-indigo-500/20">
                @php
                    $siteLogo = \App\Models\Setting::getValue('site_logo');
                @endphp
                <a href="/admin" class="flex items-center gap-2.5 group">
                    @if($siteLogo)
                        <img src="{{ asset('storage/' . $siteLogo) }}" alt="XMAN Admin" class="h-7 w-auto">
                    @else
                        <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg group-hover:shadow-indigo-500/50 transition-all duration-300">
                            <span class="text-white font-bold text-sm">X</span>
                        </div>
                        <div>
                            <span class="text-sm font-bold bg-gradient-to-r from-indigo-200 to-purple-200 bg-clip-text text-transparent">XMAN Admin</span>
                            <div class="text-[10px] text-indigo-300/50">Premium</div>
                        </div>
                    @endif
                </a>
            </div>

            <nav class="flex-1 overflow-y-auto premium-scrollbar px-2 py-2 space-y-0.5" x-data>
                @include('partials.admin-sidebar-nav')
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
