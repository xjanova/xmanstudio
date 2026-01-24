@extends($customerLayout ?? 'layouts.customer')

@section('title', 'การสมัครสมาชิก')
@section('page-title', 'การสมัครสมาชิก')
@section('page-description', 'จัดการแพ็คเกจและการสมัครสมาชิกของคุณ')

@section('content')
<!-- Premium Header Banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-cyan-600 to-teal-600 p-6 sm:p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-blob"></div>
    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-cyan-300/20 rounded-full blur-3xl animate-blob animation-delay-2000"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                การสมัครสมาชิก
            </h1>
            <p class="mt-2 text-white/80 text-sm sm:text-base">จัดการแพ็คเกจสมาชิกและติดตามการต่ออายุ</p>
        </div>
        <a href="{{ route('rental.index') }}" class="inline-flex items-center px-5 py-2.5 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all font-medium shadow-lg">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            สมัครสมาชิกใหม่
        </a>
    </div>
</div>

<!-- Stats Cards with Gradients -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-8">
    <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl p-5 sm:p-6 transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">ใช้งานอยู่</p>
                <p class="text-2xl sm:text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $stats['active'] }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-emerald-400 to-green-600 rounded-xl shadow-lg group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl p-5 sm:p-6 transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">หมดอายุ</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-600 dark:text-gray-400 mt-1">{{ $stats['expired'] }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-gray-400 to-gray-600 rounded-xl shadow-lg group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl p-5 sm:p-6 transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">ยอดใช้จ่ายรวม</p>
                <p class="text-2xl sm:text-3xl font-bold text-blue-600 dark:text-blue-400 mt-1">฿{{ number_format($stats['total_spent']) }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-blue-400 to-indigo-600 rounded-xl shadow-lg group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Premium Filter -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4 mb-6 border border-gray-100 dark:border-gray-700">
    <form action="" method="GET" class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 items-start sm:items-center">
        <div class="flex-1 sm:flex-none">
            <select name="status" class="w-full sm:w-auto rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-cyan-500 focus:border-cyan-500 shadow-sm">
                <option value="all">สถานะทั้งหมด</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งานอยู่</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>หมดอายุ</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-cyan-600 to-blue-600 text-white rounded-xl hover:from-cyan-700 hover:to-blue-700 text-sm font-medium transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    กรอง
                </span>
            </button>
            @if(request()->hasAny(['status']))
            <a href="{{ route('customer.subscriptions') }}" class="px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 text-sm font-medium transition-all">
                ล้าง
            </a>
            @endif
        </div>
    </form>
</div>

<!-- Subscription List -->
<div class="space-y-4">
    @forelse($subscriptions as $subscription)
    <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 border border-gray-100 dark:border-gray-700">
        <div class="p-5 sm:p-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                        <div class="p-2 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $subscription->rentalPackage->display_name }}</h3>
                        @php
                            $statusGradients = [
                                'active' => 'from-emerald-400 to-green-500',
                                'pending' => 'from-yellow-400 to-orange-400',
                                'expired' => 'from-red-400 to-rose-500',
                                'cancelled' => 'from-gray-400 to-gray-500',
                                'suspended' => 'from-orange-400 to-red-400',
                            ];
                            $statusLabels = [
                                'active' => 'ใช้งานอยู่',
                                'pending' => 'รอดำเนินการ',
                                'expired' => 'หมดอายุ',
                                'cancelled' => 'ยกเลิก',
                                'suspended' => 'ระงับชั่วคราว',
                            ];
                        @endphp
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gradient-to-r {{ $statusGradients[$subscription->status] ?? 'from-gray-400 to-gray-500' }} text-white shadow-sm">
                            {{ $statusLabels[$subscription->status] ?? ucfirst($subscription->status) }}
                        </span>
                        @if($subscription->auto_renew)
                        <span class="px-2.5 py-1 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-lg text-xs font-medium flex items-center shadow-sm">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            ต่ออายุอัตโนมัติ
                        </span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 ml-11">{{ $subscription->rentalPackage->display_description }}</p>
                </div>

                <div class="mt-4 lg:mt-0 lg:ml-6 lg:text-right">
                    <p class="text-2xl font-bold bg-gradient-to-r from-cyan-600 to-blue-600 bg-clip-text text-transparent">฿{{ number_format($subscription->rentalPackage->price) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $subscription->rentalPackage->duration_text }}</p>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                <div class="flex flex-wrap gap-4 sm:gap-6 text-sm">
                    <div class="flex items-center text-gray-500 dark:text-gray-400">
                        <div class="p-1.5 bg-gradient-to-br from-blue-100 to-cyan-100 dark:from-blue-900/30 dark:to-cyan-900/30 rounded-lg mr-2">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span>เริ่มใช้งาน:</span>
                        <span class="font-medium text-gray-900 dark:text-white ml-1">{{ $subscription->starts_at?->format('d/m/Y') ?? '-' }}</span>
                    </div>
                    <div class="flex items-center text-gray-500 dark:text-gray-400">
                        <div class="p-1.5 bg-gradient-to-br from-orange-100 to-red-100 dark:from-orange-900/30 dark:to-red-900/30 rounded-lg mr-2">
                            <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span>หมดอายุ:</span>
                        <span class="font-medium {{ $subscription->expires_at?->isPast() ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }} ml-1">
                            {{ $subscription->expires_at?->format('d/m/Y') ?? '-' }}
                            @if($subscription->expires_at && !$subscription->expires_at->isPast())
                                <span class="text-gray-400 font-normal">({{ $subscription->expires_at->diffForHumans() }})</span>
                            @endif
                        </span>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-3">
                    <a href="{{ route('customer.subscriptions.show', $subscription) }}"
                       class="inline-flex items-center px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 text-sm font-medium transition-all">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        ดูรายละเอียด
                    </a>
                    @if($subscription->status === 'active' && $subscription->expires_at?->diffInDays() < 30)
                    <a href="{{ route('rental.checkout', $subscription->rentalPackage) }}"
                       class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-cyan-600 to-blue-600 text-white rounded-xl hover:from-cyan-700 hover:to-blue-700 text-sm font-medium transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        ต่ออายุ
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-12 text-center border border-gray-100 dark:border-gray-700">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-full mb-6 shadow-xl">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ยังไม่มีการสมัครสมาชิก</h3>
        <p class="text-gray-500 dark:text-gray-400 mt-2">เริ่มต้นใช้งานด้วยการสมัครแพ็คเกจที่เหมาะกับคุณ</p>
        <a href="{{ route('rental.index') }}" class="mt-6 inline-flex items-center px-6 py-3 bg-gradient-to-r from-cyan-600 to-blue-600 text-white rounded-xl hover:from-cyan-700 hover:to-blue-700 font-medium transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            ดูแพ็คเกจทั้งหมด
        </a>
    </div>
    @endforelse
</div>

@if($subscriptions->hasPages())
<div class="mt-6">
    {{ $subscriptions->withQueryString()->links() }}
</div>
@endif
@endsection
