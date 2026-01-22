@extends('layouts.customer')

@section('title', 'การสมัครสมาชิก')
@section('page-title', 'การสมัครสมาชิก')
@section('page-description', 'จัดการแพ็คเกจและการสมัครสมาชิกของคุณ')

@section('content')
<!-- Stats -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-5 sm:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">ใช้งานอยู่</p>
                <p class="text-2xl sm:text-3xl font-bold text-green-600 mt-1">{{ $stats['active'] }}</p>
            </div>
            <div class="p-3 bg-green-100 rounded-xl">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5 sm:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">หมดอายุ</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-600 mt-1">{{ $stats['expired'] }}</p>
            </div>
            <div class="p-3 bg-gray-100 rounded-xl">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5 sm:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">ยอดใช้จ่ายรวม</p>
                <p class="text-2xl sm:text-3xl font-bold text-primary-600 mt-1">฿{{ number_format($stats['total_spent']) }}</p>
            </div>
            <div class="p-3 bg-primary-100 rounded-xl">
                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form action="" method="GET" class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 items-start sm:items-center">
        <div class="flex-1 sm:flex-none">
            <select name="status" class="w-full sm:w-auto rounded-lg border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                <option value="all">สถานะทั้งหมด</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งานอยู่</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>หมดอายุ</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm font-medium transition-colors">
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    กรอง
                </span>
            </button>
            @if(request()->hasAny(['status']))
            <a href="{{ route('customer.subscriptions') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition-colors">
                ล้าง
            </a>
            @endif
        </div>

        <a href="{{ route('rental.index') }}" class="sm:ml-auto px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm font-medium transition-colors flex items-center">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            สมัครสมาชิกใหม่
        </a>
    </form>
</div>

<!-- Subscription List -->
<div class="space-y-4">
    @forelse($subscriptions as $subscription)
    <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow">
        <div class="p-5 sm:p-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $subscription->rentalPackage->display_name }}</h3>
                        @php
                            $statusColors = [
                                'active' => 'bg-green-100 text-green-700',
                                'pending' => 'bg-yellow-100 text-yellow-700',
                                'expired' => 'bg-red-100 text-red-700',
                                'cancelled' => 'bg-gray-100 text-gray-700',
                                'suspended' => 'bg-orange-100 text-orange-700',
                            ];
                            $statusLabels = [
                                'active' => 'ใช้งานอยู่',
                                'pending' => 'รอดำเนินการ',
                                'expired' => 'หมดอายุ',
                                'cancelled' => 'ยกเลิก',
                                'suspended' => 'ระงับชั่วคราว',
                            ];
                        @endphp
                        <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $statusColors[$subscription->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $statusLabels[$subscription->status] ?? ucfirst($subscription->status) }}
                        </span>
                        @if($subscription->auto_renew)
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-lg text-xs font-medium flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            ต่ออายุอัตโนมัติ
                        </span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 mt-2">{{ $subscription->rentalPackage->display_description }}</p>
                </div>

                <div class="mt-4 lg:mt-0 lg:ml-6 lg:text-right">
                    <p class="text-2xl font-bold text-gray-900">฿{{ number_format($subscription->rentalPackage->price) }}</p>
                    <p class="text-sm text-gray-500">{{ $subscription->rentalPackage->duration_text }}</p>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex flex-wrap gap-4 sm:gap-6 text-sm">
                    <div class="flex items-center text-gray-500">
                        <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>เริ่มใช้งาน:</span>
                        <span class="font-medium text-gray-900 ml-1">{{ $subscription->starts_at?->format('d/m/Y') ?? '-' }}</span>
                    </div>
                    <div class="flex items-center text-gray-500">
                        <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>หมดอายุ:</span>
                        <span class="font-medium {{ $subscription->expires_at?->isPast() ? 'text-red-600' : 'text-gray-900' }} ml-1">
                            {{ $subscription->expires_at?->format('d/m/Y') ?? '-' }}
                            @if($subscription->expires_at && !$subscription->expires_at->isPast())
                                <span class="text-gray-400 font-normal">({{ $subscription->expires_at->diffForHumans() }})</span>
                            @endif
                        </span>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-3">
                    <a href="{{ route('customer.subscriptions.show', $subscription) }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        ดูรายละเอียด
                    </a>
                    @if($subscription->status === 'active' && $subscription->expires_at?->diffInDays() < 30)
                    <a href="{{ route('rental.checkout', $subscription->rentalPackage) }}"
                       class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm font-medium transition-colors">
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
    <div class="bg-white rounded-xl shadow-sm p-12 text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-6">
            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900">ยังไม่มีการสมัครสมาชิก</h3>
        <p class="text-gray-500 mt-2">เริ่มต้นใช้งานด้วยการสมัครแพ็คเกจที่เหมาะกับคุณ</p>
        <a href="{{ route('rental.index') }}" class="mt-6 inline-flex items-center px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium transition-colors">
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
