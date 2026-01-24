@extends($customerLayout ?? 'layouts.customer')

@section('title', 'แดชบอร์ด')
@section('page-title', 'แดชบอร์ด')
@section('page-description', 'ภาพรวมบัญชีของคุณ')

@section('content')
<!-- Premium Welcome Banner -->
<div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl mb-8 animate-fade-in">
    <div class="absolute inset-0 bg-black/10"></div>

    <!-- Animated Background Blobs -->
    <div class="absolute inset-0 opacity-30">
        <div class="absolute top-0 -left-4 w-48 h-48 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl animate-blob"></div>
        <div class="absolute top-0 -right-4 w-48 h-48 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl animate-blob" style="animation-delay: 2s;"></div>
        <div class="absolute -bottom-8 left-20 w-48 h-48 bg-pink-300 rounded-full mix-blend-multiply filter blur-xl animate-blob" style="animation-delay: 4s;"></div>
    </div>

    <div class="relative px-6 sm:px-8 py-8 sm:py-10">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="h-14 w-14 sm:h-16 sm:w-16 rounded-2xl bg-white/20 backdrop-blur-lg flex items-center justify-center shadow-xl">
                    @if($user->avatar)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="h-full w-full rounded-2xl object-cover">
                    @else
                        <span class="text-2xl sm:text-3xl font-bold text-white">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    @endif
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-white mb-1">สวัสดี, {{ $user->name }}!</h1>
                    <p class="text-indigo-100 text-sm sm:text-base">ยินดีต้อนรับกลับมา มาดูกันว่ามีอะไรใหม่บ้าง</p>
                </div>
            </div>
            <div class="hidden lg:block text-right">
                <p class="text-white/70 text-sm">วันที่</p>
                <p class="text-white font-semibold text-lg">{{ now()->locale('th')->translatedFormat('d M Y') }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Premium Stats Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
    <!-- Active Subscriptions -->
    <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 animate-fade-in">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-400/10 to-cyan-600/10"></div>
        <div class="relative p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-blue-400 to-cyan-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-gray-500 dark:text-gray-400 text-xs sm:text-sm font-medium mb-1">สมาชิกใช้งาน</h3>
            <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['active_subscriptions'] }}</p>
            <a href="{{ route('customer.subscriptions') }}" class="mt-3 inline-flex items-center text-sm text-blue-600 hover:text-blue-700 font-medium group-hover:translate-x-1 transition-transform">
                ดูทั้งหมด
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>

    <!-- Active Licenses -->
    <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 animate-fade-in" style="animation-delay: 0.1s;">
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-400/10 to-green-600/10"></div>
        <div class="relative p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-emerald-400 to-green-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-gray-500 dark:text-gray-400 text-xs sm:text-sm font-medium mb-1">ใบอนุญาต</h3>
            <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['active_licenses'] }}</p>
            <a href="{{ route('customer.licenses') }}" class="mt-3 inline-flex items-center text-sm text-emerald-600 hover:text-emerald-700 font-medium group-hover:translate-x-1 transition-transform">
                ดูทั้งหมด
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 animate-fade-in" style="animation-delay: 0.2s;">
        <div class="absolute inset-0 bg-gradient-to-br from-purple-400/10 to-pink-600/10"></div>
        <div class="relative p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-purple-400 to-pink-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-gray-500 dark:text-gray-400 text-xs sm:text-sm font-medium mb-1">คำสั่งซื้อ</h3>
            <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_orders'] }}</p>
            <a href="{{ route('customer.orders') }}" class="mt-3 inline-flex items-center text-sm text-purple-600 hover:text-purple-700 font-medium group-hover:translate-x-1 transition-transform">
                ดูทั้งหมด
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>

    <!-- Open Tickets -->
    <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 animate-fade-in" style="animation-delay: 0.3s;">
        <div class="absolute inset-0 bg-gradient-to-br from-orange-400/10 to-red-600/10"></div>
        <div class="relative p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-gray-500 dark:text-gray-400 text-xs sm:text-sm font-medium mb-1">Ticket เปิดอยู่</h3>
            <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['open_tickets'] }}</p>
            <a href="{{ route('customer.support.index') }}" class="mt-3 inline-flex items-center text-sm text-orange-600 hover:text-orange-700 font-medium group-hover:translate-x-1 transition-transform">
                ดูทั้งหมด
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</div>

<!-- Expired License Alert -->
@if(isset($expiredLicenses) && $expiredLicenses->count() > 0)
<div class="relative overflow-hidden bg-gradient-to-r from-red-500/20 to-rose-500/20 border border-red-500/30 rounded-2xl p-5 sm:p-6 mb-6 animate-fade-in">
    <div class="flex">
        <div class="flex-shrink-0">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center shadow-lg">
                <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            </div>
        </div>
        <div class="ml-4 flex-1">
            <h3 class="text-base font-bold text-red-700 dark:text-red-300">License หมดอายุแล้ว!</h3>
            <div class="mt-3 space-y-2">
                @foreach($expiredLicenses as $license)
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between bg-white/50 dark:bg-gray-800/50 backdrop-blur rounded-xl p-4">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $license->product?->name ?? 'Software License' }}</p>
                        <p class="text-sm text-red-600 dark:text-red-400">
                            หมดอายุเมื่อ {{ $license->expires_at->format('d/m/Y') }} ({{ $license->expires_at->diffForHumans() }})
                        </p>
                    </div>
                    <a href="{{ route('products.show', $license->product?->slug ?? 'products') }}"
                       class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 bg-gradient-to-r from-red-500 to-rose-600 text-white text-sm font-medium rounded-xl hover:from-red-600 hover:to-rose-700 transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        ต่ออายุ
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

<!-- Expiring License Alert -->
@if(isset($expiringLicenses) && $expiringLicenses->count() > 0)
<div class="relative overflow-hidden bg-gradient-to-r from-amber-500/20 to-orange-500/20 border border-amber-500/30 rounded-2xl p-5 sm:p-6 mb-6 animate-fade-in">
    <div class="flex">
        <div class="flex-shrink-0">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg">
                <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
        </div>
        <div class="ml-4 flex-1">
            <h3 class="text-base font-bold text-amber-700 dark:text-amber-300">License ใกล้หมดอายุ</h3>
            <div class="mt-3 space-y-2">
                @foreach($expiringLicenses as $license)
                @php $daysLeft = max(0, (int) now()->diffInDays($license->expires_at, false)); @endphp
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between bg-white/50 dark:bg-gray-800/50 backdrop-blur rounded-xl p-4">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $license->product?->name ?? 'Software License' }}</p>
                        <p class="text-sm text-amber-600 dark:text-amber-400">
                            เหลืออีก {{ $daysLeft }} วัน ({{ $license->expires_at->format('d/m/Y') }})
                        </p>
                    </div>
                    <a href="{{ route('products.show', $license->product?->slug ?? 'products') }}"
                       class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-600 text-white text-sm font-medium rounded-xl hover:from-amber-600 hover:to-orange-700 transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        ต่ออายุ
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

<!-- Expiring Subscription Alert -->
@if($expiringSoon->count() > 0)
<div class="relative overflow-hidden bg-gradient-to-r from-yellow-500/20 to-amber-500/20 border border-yellow-500/30 rounded-2xl p-5 sm:p-6 mb-8 animate-fade-in">
    <div class="flex">
        <div class="flex-shrink-0">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-500 to-amber-600 flex items-center justify-center shadow-lg">
                <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
        </div>
        <div class="ml-4 flex-1">
            <h3 class="text-base font-bold text-yellow-700 dark:text-yellow-300">การสมัครสมาชิกใกล้หมดอายุ</h3>
            <div class="mt-3 space-y-2">
                @foreach($expiringSoon as $rental)
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between bg-white/50 dark:bg-gray-800/50 backdrop-blur rounded-xl p-4">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $rental->rentalPackage->display_name }}</p>
                        <p class="text-sm text-yellow-600 dark:text-yellow-400">
                            หมดอายุ {{ $rental->expires_at->diffForHumans() }} ({{ $rental->expires_at->format('d/m/Y') }})
                        </p>
                    </div>
                    <a href="{{ route('customer.subscriptions.show', $rental) }}"
                       class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 bg-gradient-to-r from-yellow-500 to-amber-600 text-white text-sm font-medium rounded-xl hover:from-yellow-600 hover:to-amber-700 transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        ต่ออายุ
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
    <!-- Active Subscriptions -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden animate-fade-in" style="animation-delay: 0.4s;">
        <div class="px-5 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">การสมัครสมาชิกที่ใช้งานอยู่</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">แพ็คเกจที่คุณกำลังใช้งาน</p>
            </div>
            <a href="{{ route('customer.subscriptions') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 font-medium flex items-center">
                ดูทั้งหมด
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($activeRentals as $rental)
            <a href="{{ route('customer.subscriptions.show', $rental) }}" class="block p-4 sm:p-5 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-400 to-purple-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-semibold text-gray-900 dark:text-white truncate">{{ $rental->rentalPackage->display_name }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                หมดอายุ: {{ $rental->expires_at->format('d/m/Y') }}
                                <span class="text-gray-400 dark:text-gray-500">({{ $rental->expires_at->diffForHumans() }})</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 bg-gradient-to-r from-emerald-400 to-green-500 text-white text-xs font-semibold rounded-full shadow">ใช้งานอยู่</span>
                        <svg class="w-5 h-5 text-gray-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </a>
            @empty
            <div class="p-8 sm:p-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-2xl mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                </div>
                <p class="text-gray-600 dark:text-gray-300 font-medium">ยังไม่มีการสมัครสมาชิก</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">เริ่มต้นใช้งานด้วยการสมัครแพ็คเกจ</p>
                <a href="{{ route('rental.index') }}" class="mt-4 inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-medium rounded-xl hover:from-indigo-600 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    ดูแพ็คเกจ
                </a>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Active Licenses -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden animate-fade-in" style="animation-delay: 0.5s;">
        <div class="px-5 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">ใบอนุญาตที่ใช้งานอยู่</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">License ซอฟต์แวร์ของคุณ</p>
            </div>
            <a href="{{ route('customer.licenses') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 font-medium flex items-center">
                ดูทั้งหมด
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($activeLicenses as $license)
            <a href="{{ route('customer.licenses.show', $license) }}" class="block p-4 sm:p-5 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-400 to-blue-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-semibold text-gray-900 dark:text-white truncate">{{ $license->product?->name ?? 'Software License' }}</h4>
                            <div class="flex items-center mt-1">
                                <code class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded font-mono">{{ Str::limit($license->license_key, 20) }}</code>
                                <button onclick="event.preventDefault(); copyToClipboard('{{ $license->license_key }}', 'คัดลอก License Key แล้ว!')" class="ml-2 p-1 text-gray-400 hover:text-indigo-600 transition-colors" title="คัดลอก">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 text-xs font-semibold rounded-full shadow
                            {{ $license->license_type === 'lifetime' ? 'bg-gradient-to-r from-purple-400 to-purple-600 text-white' : '' }}
                            {{ $license->license_type === 'yearly' ? 'bg-gradient-to-r from-blue-400 to-blue-600 text-white' : '' }}
                            {{ $license->license_type === 'monthly' ? 'bg-gradient-to-r from-gray-400 to-gray-600 text-white' : '' }}
                            {{ $license->license_type === 'demo' ? 'bg-gradient-to-r from-yellow-400 to-amber-500 text-white' : '' }}
                        ">
                            {{ $license->license_type === 'lifetime' ? 'ตลอดชีพ' : '' }}
                            {{ $license->license_type === 'yearly' ? 'รายปี' : '' }}
                            {{ $license->license_type === 'monthly' ? 'รายเดือน' : '' }}
                            {{ $license->license_type === 'demo' ? 'ทดลอง' : '' }}
                        </span>
                        <svg class="w-5 h-5 text-gray-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </a>
            @empty
            <div class="p-8 sm:p-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-2xl mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <p class="text-gray-600 dark:text-gray-300 font-medium">ยังไม่มีใบอนุญาต</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ซื้อผลิตภัณฑ์เพื่อรับใบอนุญาต</p>
                <a href="{{ route('products.index') }}" class="mt-4 inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-medium rounded-xl hover:from-indigo-600 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    ดูผลิตภัณฑ์
                </a>
            </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="mt-8 bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-5 sm:p-6 animate-fade-in" style="animation-delay: 0.6s;">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
        ทางลัด
    </h3>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
        <a href="{{ route('rental.index') }}" class="group flex flex-col items-center p-4 sm:p-5 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/30 dark:to-purple-900/30 rounded-2xl hover:from-indigo-100 hover:to-purple-100 dark:hover:from-indigo-900/50 dark:hover:to-purple-900/50 transition-all border border-indigo-200/50 dark:border-indigo-700/50 hover:border-indigo-300 dark:hover:border-indigo-600 transform hover:scale-105 hover:shadow-lg">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg group-hover:shadow-xl group-hover:scale-110 transition-all">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </div>
            <span class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300 text-center">สมัครสมาชิกใหม่</span>
        </a>

        <a href="{{ route('customer.downloads') }}" class="group flex flex-col items-center p-4 sm:p-5 bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/30 dark:to-cyan-900/30 rounded-2xl hover:from-blue-100 hover:to-cyan-100 dark:hover:from-blue-900/50 dark:hover:to-cyan-900/50 transition-all border border-blue-200/50 dark:border-blue-700/50 hover:border-blue-300 dark:hover:border-blue-600 transform hover:scale-105 hover:shadow-lg">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center shadow-lg group-hover:shadow-xl group-hover:scale-110 transition-all">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </div>
            <span class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300 text-center">ดาวน์โหลด</span>
        </a>

        <a href="{{ route('customer.support.create') }}" class="group flex flex-col items-center p-4 sm:p-5 bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/30 dark:to-amber-900/30 rounded-2xl hover:from-orange-100 hover:to-amber-100 dark:hover:from-orange-900/50 dark:hover:to-amber-900/50 transition-all border border-orange-200/50 dark:border-orange-700/50 hover:border-orange-300 dark:hover:border-orange-600 transform hover:scale-105 hover:shadow-lg">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-500 to-amber-600 flex items-center justify-center shadow-lg group-hover:shadow-xl group-hover:scale-110 transition-all">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300 text-center">ขอความช่วยเหลือ</span>
        </a>

        <a href="{{ route('profile.edit') }}" class="group flex flex-col items-center p-4 sm:p-5 bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-800/50 dark:to-slate-800/50 rounded-2xl hover:from-gray-100 hover:to-slate-100 dark:hover:from-gray-700/50 dark:hover:to-slate-700/50 transition-all border border-gray-200/50 dark:border-gray-600/50 hover:border-gray-300 dark:hover:border-gray-500 transform hover:scale-105 hover:shadow-lg">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-gray-500 to-slate-600 flex items-center justify-center shadow-lg group-hover:shadow-xl group-hover:scale-110 transition-all">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <span class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300 text-center">ตั้งค่าบัญชี</span>
        </a>
    </div>
</div>

@push('scripts')
<style>
@keyframes blob {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
}
.animate-blob { animation: blob 7s infinite; }
</style>
@endpush
@endsection
