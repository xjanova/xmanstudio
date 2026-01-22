@extends('layouts.customer')

@section('title', 'แดชบอร์ด')
@section('page-title', 'แดชบอร์ด')
@section('page-description', 'ภาพรวมบัญชีของคุณ')

@section('content')
<!-- Welcome Banner -->
<div class="bg-gradient-to-r from-primary-600 via-primary-600 to-primary-700 rounded-2xl p-6 sm:p-8 text-white mb-8 relative overflow-hidden">
    <div class="absolute inset-0 bg-grid-white/10 [mask-image:linear-gradient(0deg,transparent,white)]"></div>
    <div class="relative">
        <div class="flex items-center gap-3 mb-2">
            <div class="h-12 w-12 rounded-full bg-white/20 backdrop-blur flex items-center justify-center">
                <span class="text-xl font-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
            </div>
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold">สวัสดี, {{ $user->name }}!</h2>
                <p class="text-primary-100 mt-1">ยินดีต้อนรับกลับมา มาดูกันว่ามีอะไรใหม่บ้าง</p>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
    <!-- Active Subscriptions -->
    <div class="bg-white rounded-xl shadow-sm p-5 sm:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div class="flex-shrink-0 p-3 bg-blue-100 rounded-xl">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <div class="text-right">
                <p class="text-xs sm:text-sm font-medium text-gray-500">สมาชิกใช้งาน</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $stats['active_subscriptions'] }}</p>
            </div>
        </div>
        <a href="{{ route('customer.subscriptions') }}" class="mt-4 block text-sm text-blue-600 hover:text-blue-700 font-medium">
            ดูทั้งหมด →
        </a>
    </div>

    <!-- Active Licenses -->
    <div class="bg-white rounded-xl shadow-sm p-5 sm:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div class="flex-shrink-0 p-3 bg-green-100 rounded-xl">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <div class="text-right">
                <p class="text-xs sm:text-sm font-medium text-gray-500">ใบอนุญาต</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $stats['active_licenses'] }}</p>
            </div>
        </div>
        <a href="{{ route('customer.licenses') }}" class="mt-4 block text-sm text-green-600 hover:text-green-700 font-medium">
            ดูทั้งหมด →
        </a>
    </div>

    <!-- Total Orders -->
    <div class="bg-white rounded-xl shadow-sm p-5 sm:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div class="flex-shrink-0 p-3 bg-purple-100 rounded-xl">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <div class="text-right">
                <p class="text-xs sm:text-sm font-medium text-gray-500">คำสั่งซื้อ</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $stats['total_orders'] }}</p>
            </div>
        </div>
        <a href="{{ route('customer.orders') }}" class="mt-4 block text-sm text-purple-600 hover:text-purple-700 font-medium">
            ดูทั้งหมด →
        </a>
    </div>

    <!-- Open Tickets -->
    <div class="bg-white rounded-xl shadow-sm p-5 sm:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div class="flex-shrink-0 p-3 bg-orange-100 rounded-xl">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
            </div>
            <div class="text-right">
                <p class="text-xs sm:text-sm font-medium text-gray-500">Ticket เปิดอยู่</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $stats['open_tickets'] }}</p>
            </div>
        </div>
        <a href="{{ route('customer.support.index') }}" class="mt-4 block text-sm text-orange-600 hover:text-orange-700 font-medium">
            ดูทั้งหมด →
        </a>
    </div>
</div>

<!-- Expiring Soon Alert -->
@if($expiringSoon->count() > 0)
<div class="bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-xl p-4 sm:p-5 mb-8">
    <div class="flex">
        <div class="flex-shrink-0">
            <div class="p-2 bg-yellow-100 rounded-lg">
                <svg class="h-6 w-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
        </div>
        <div class="ml-4 flex-1">
            <h3 class="text-base font-semibold text-yellow-800">การสมัครสมาชิกใกล้หมดอายุ</h3>
            <div class="mt-3 space-y-2">
                @foreach($expiringSoon as $rental)
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between bg-white/50 rounded-lg p-3">
                    <div>
                        <p class="font-medium text-gray-900">{{ $rental->rentalPackage->display_name }}</p>
                        <p class="text-sm text-yellow-700">
                            หมดอายุ {{ $rental->expires_at->diffForHumans() }} ({{ $rental->expires_at->format('d/m/Y') }})
                        </p>
                    </div>
                    <a href="{{ route('customer.subscriptions.show', $rental) }}"
                       class="mt-2 sm:mt-0 inline-flex items-center px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition-colors">
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
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 sm:px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">การสมัครสมาชิกที่ใช้งานอยู่</h3>
                <p class="text-sm text-gray-500">แพ็คเกจที่คุณกำลังใช้งาน</p>
            </div>
            <a href="{{ route('customer.subscriptions') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                ดูทั้งหมด
            </a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($activeRentals as $rental)
            <a href="{{ route('customer.subscriptions.show', $rental) }}" class="block p-4 sm:p-5 hover:bg-gray-50 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <h4 class="font-medium text-gray-900 truncate">{{ $rental->rentalPackage->display_name }}</h4>
                        <div class="flex items-center mt-1.5 text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            หมดอายุ: {{ $rental->expires_at->format('d/m/Y') }}
                            <span class="ml-1 text-gray-400">({{ $rental->expires_at->diffForHumans() }})</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full">ใช้งานอยู่</span>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </a>
            @empty
            <div class="p-8 sm:p-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                </div>
                <p class="text-gray-600 font-medium">ยังไม่มีการสมัครสมาชิก</p>
                <p class="text-sm text-gray-500 mt-1">เริ่มต้นใช้งานด้วยการสมัครแพ็คเกจ</p>
                <a href="{{ route('rental.index') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
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
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 sm:px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">ใบอนุญาตที่ใช้งานอยู่</h3>
                <p class="text-sm text-gray-500">License ซอฟต์แวร์ของคุณ</p>
            </div>
            <a href="{{ route('customer.licenses') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                ดูทั้งหมด
            </a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($activeLicenses as $license)
            <a href="{{ route('customer.licenses.show', $license) }}" class="block p-4 sm:p-5 hover:bg-gray-50 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <h4 class="font-medium text-gray-900 truncate">{{ $license->product?->name ?? 'Software License' }}</h4>
                        <div class="flex items-center mt-1.5">
                            <code class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded font-mono">{{ Str::limit($license->license_key, 20) }}</code>
                            <button onclick="event.preventDefault(); copyToClipboard('{{ $license->license_key }}', 'คัดลอก License Key แล้ว!')" class="ml-2 p-1 text-gray-400 hover:text-primary-600 transition-colors" title="คัดลอก">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 text-xs font-semibold rounded-full
                            {{ $license->license_type === 'lifetime' ? 'bg-purple-100 text-purple-700' : '' }}
                            {{ $license->license_type === 'yearly' ? 'bg-blue-100 text-blue-700' : '' }}
                            {{ $license->license_type === 'monthly' ? 'bg-gray-100 text-gray-700' : '' }}
                            {{ $license->license_type === 'demo' ? 'bg-yellow-100 text-yellow-700' : '' }}
                        ">
                            {{ $license->license_type === 'lifetime' ? 'ตลอดชีพ' : '' }}
                            {{ $license->license_type === 'yearly' ? 'รายปี' : '' }}
                            {{ $license->license_type === 'monthly' ? 'รายเดือน' : '' }}
                            {{ $license->license_type === 'demo' ? 'ทดลอง' : '' }}
                        </span>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </a>
            @empty
            <div class="p-8 sm:p-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <p class="text-gray-600 font-medium">ยังไม่มีใบอนุญาต</p>
                <p class="text-sm text-gray-500 mt-1">ซื้อผลิตภัณฑ์เพื่อรับใบอนุญาต</p>
                <a href="{{ route('products.index') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
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
<div class="mt-8 bg-white rounded-xl shadow-sm p-5 sm:p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">ทางลัด</h3>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
        <a href="{{ route('rental.index') }}" class="flex flex-col items-center p-4 sm:p-5 border-2 border-dashed border-gray-200 rounded-xl hover:border-primary-300 hover:bg-primary-50 transition-all group">
            <div class="p-3 bg-primary-100 rounded-xl group-hover:bg-primary-200 transition-colors">
                <svg class="w-7 h-7 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </div>
            <span class="mt-3 text-sm font-medium text-gray-700 text-center">สมัครสมาชิกใหม่</span>
        </a>

        <a href="{{ route('customer.downloads') }}" class="flex flex-col items-center p-4 sm:p-5 border-2 border-dashed border-gray-200 rounded-xl hover:border-blue-300 hover:bg-blue-50 transition-all group">
            <div class="p-3 bg-blue-100 rounded-xl group-hover:bg-blue-200 transition-colors">
                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </div>
            <span class="mt-3 text-sm font-medium text-gray-700 text-center">ดาวน์โหลด</span>
        </a>

        <a href="{{ route('customer.support.create') }}" class="flex flex-col items-center p-4 sm:p-5 border-2 border-dashed border-gray-200 rounded-xl hover:border-orange-300 hover:bg-orange-50 transition-all group">
            <div class="p-3 bg-orange-100 rounded-xl group-hover:bg-orange-200 transition-colors">
                <svg class="w-7 h-7 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="mt-3 text-sm font-medium text-gray-700 text-center">ขอความช่วยเหลือ</span>
        </a>

        <a href="{{ route('profile.edit') }}" class="flex flex-col items-center p-4 sm:p-5 border-2 border-dashed border-gray-200 rounded-xl hover:border-gray-300 hover:bg-gray-50 transition-all group">
            <div class="p-3 bg-gray-100 rounded-xl group-hover:bg-gray-200 transition-colors">
                <svg class="w-7 h-7 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <span class="mt-3 text-sm font-medium text-gray-700 text-center">ตั้งค่าบัญชี</span>
        </a>
    </div>
</div>
@endsection
