<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'My Account') - XMAN Studio</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 overflow-hidden">
    <div class="h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg flex-shrink-0 hidden md:flex md:flex-col h-screen">
            <div class="p-6 border-b flex-shrink-0">
                <a href="/" class="text-xl font-bold text-primary-600">XMAN Studio</a>
                <p class="text-sm text-gray-500 mt-1">Customer Portal</p>
            </div>

            <nav class="p-4 space-y-1 flex-1 overflow-y-auto">
                <a href="{{ route('customer.dashboard') }}"
                   class="flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.dashboard') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </a>

                <a href="{{ route('customer.licenses') }}"
                   class="flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.licenses*') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    Licenses
                </a>

                <a href="{{ route('customer.subscriptions') }}"
                   class="flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.subscriptions*') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Subscriptions
                </a>

                <a href="{{ route('customer.orders') }}"
                   class="flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.orders*') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    Orders
                </a>

                <a href="{{ route('customer.invoices') }}"
                   class="flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.invoices') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Invoices
                </a>

                <a href="{{ route('customer.downloads') }}"
                   class="flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.downloads') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Downloads
                </a>

                <hr class="my-4 border-gray-200">

                <a href="{{ route('customer.support.index') }}"
                   class="flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('customer.support*') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                    Support Tickets
                </a>

                <a href="{{ route('profile.edit') }}"
                   class="flex items-center px-4 py-3 rounded-lg text-gray-600 hover:bg-gray-50">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Profile
                </a>
            </nav>

            <div class="p-4 border-t bg-gray-50 flex-shrink-0">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                            <span class="text-primary-600 font-medium">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        </div>
                    </div>
                    <div class="ml-3 flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm flex-shrink-0 z-10">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center">
                        <!-- Mobile menu button -->
                        <button type="button" class="md:hidden mr-4 text-gray-500" onclick="toggleMobileMenu()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                        <h1 class="text-xl font-semibold text-gray-900">@yield('page-title', 'Dashboard')</h1>
                    </div>

                    <div class="flex items-center space-x-4">
                        <a href="/" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        </a>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-500 hover:text-gray-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-6 overflow-y-auto">
                @if(session('success'))
                    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                        {{ session('error') }}
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg">
                        {{ session('warning') }}
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t py-4 px-6 flex-shrink-0">
                <p class="text-sm text-gray-500 text-center">
                    &copy; {{ date('Y') }} XMAN Studio. All rights reserved.
                </p>
            </footer>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden md:hidden">
        <div class="bg-white w-64 h-full overflow-y-auto">
            <div class="p-4 border-b flex justify-between items-center">
                <span class="font-bold text-primary-600">XMAN Studio</span>
                <button onclick="toggleMobileMenu()" class="text-gray-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <nav class="p-4 space-y-2">
                <a href="{{ route('customer.dashboard') }}" class="block px-4 py-2 rounded-lg {{ request()->routeIs('customer.dashboard') ? 'bg-primary-50 text-primary-700' : 'text-gray-600' }}">Dashboard</a>
                <a href="{{ route('customer.licenses') }}" class="block px-4 py-2 rounded-lg {{ request()->routeIs('customer.licenses*') ? 'bg-primary-50 text-primary-700' : 'text-gray-600' }}">Licenses</a>
                <a href="{{ route('customer.subscriptions') }}" class="block px-4 py-2 rounded-lg {{ request()->routeIs('customer.subscriptions*') ? 'bg-primary-50 text-primary-700' : 'text-gray-600' }}">Subscriptions</a>
                <a href="{{ route('customer.orders') }}" class="block px-4 py-2 rounded-lg {{ request()->routeIs('customer.orders*') ? 'bg-primary-50 text-primary-700' : 'text-gray-600' }}">Orders</a>
                <a href="{{ route('customer.invoices') }}" class="block px-4 py-2 rounded-lg {{ request()->routeIs('customer.invoices') ? 'bg-primary-50 text-primary-700' : 'text-gray-600' }}">Invoices</a>
                <a href="{{ route('customer.downloads') }}" class="block px-4 py-2 rounded-lg {{ request()->routeIs('customer.downloads') ? 'bg-primary-50 text-primary-700' : 'text-gray-600' }}">Downloads</a>
                <hr class="my-2 border-gray-200">
                <a href="{{ route('customer.support.index') }}" class="block px-4 py-2 rounded-lg {{ request()->routeIs('customer.support*') ? 'bg-primary-50 text-primary-700' : 'text-gray-600' }}">Support Tickets</a>
            </nav>
        </div>
    </div>

    <script>
        function toggleMobileMenu() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        }
    </script>
    @stack('scripts')
</body>
</html>
