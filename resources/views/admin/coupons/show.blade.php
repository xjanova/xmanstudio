@extends('layouts.admin')

@section('title', 'คูปอง: ' . $coupon->code)
@section('page-title', 'รายละเอียดคูปอง')

@section('content')
<!-- Premium Header Banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 p-6 sm:p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/5 rounded-full blur-3xl animate-blob"></div>
    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-amber-400/10 rounded-full blur-3xl animate-blob animation-delay-2000"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-white/20 text-white backdrop-blur-sm">
                    {{ $coupon->discount_label }}
                </span>
                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
                    @if($coupon->is_active && !$coupon->isExpired()) bg-green-400/30 text-green-100
                    @elseif($coupon->isExpired()) bg-red-400/30 text-red-100
                    @else bg-amber-400/30 text-amber-100 @endif backdrop-blur-sm">
                    {{ $coupon->status_label }}
                </span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl sm:text-2xl font-mono font-bold text-white" id="couponCode">{{ $coupon->code }}</h1>
                <button onclick="copyCouponCode()" class="p-2 bg-white/20 hover:bg-white/30 rounded-lg transition-all" title="คัดลอก">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>
            @if($coupon->name)
                <p class="mt-2 text-white/80 text-sm">{{ $coupon->name }}</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.coupons.edit', $coupon) }}"
               class="inline-flex items-center px-4 py-2 bg-white/20 text-white rounded-xl hover:bg-white/30 transition-all font-medium backdrop-blur-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                แก้ไข
            </a>
            <a href="{{ route('admin.coupons.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white/20 text-white rounded-xl hover:bg-white/30 transition-all font-medium backdrop-blur-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับ
            </a>
        </div>
    </div>
</div>

<!-- Premium Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <!-- Usage Count -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-5 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">ใช้งานแล้ว</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $coupon->used_count }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        @if($coupon->usage_limit)
            <div class="mt-3">
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                    <span>{{ number_format(($coupon->used_count / $coupon->usage_limit) * 100, 0) }}%</span>
                    <span>{{ $coupon->usage_limit - $coupon->used_count }} เหลือ</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-gradient-to-r from-amber-400 to-orange-500 h-2 rounded-full transition-all" style="width: {{ min(100, ($coupon->used_count / $coupon->usage_limit) * 100) }}%"></div>
                </div>
            </div>
        @else
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">ไม่จำกัดจำนวน</p>
        @endif
    </div>

    <!-- Total Discount -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-5 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">ส่วนลดรวม</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">฿{{ number_format($coupon->usages->sum('discount_amount'), 0) }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">จากทั้งหมด {{ $coupon->usages->count() }} ครั้ง</p>
    </div>

    <!-- Discount Value -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-5 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">มูลค่าส่วนลด</p>
                <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">{{ $coupon->discount_label }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            @if($coupon->min_order_amount)
                ขั้นต่ำ ฿{{ number_format($coupon->min_order_amount, 0) }}
            @else
                ไม่มีขั้นต่ำ
            @endif
        </p>
    </div>

    <!-- Expiry Status -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-5 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">สถานะ</p>
                @if($coupon->expires_at && $coupon->isExpired())
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">หมดอายุ</p>
                @elseif($coupon->expires_at)
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $coupon->expires_at->diffForHumans() }}</p>
                @else
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">ไม่จำกัด</p>
                @endif
            </div>
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-400 to-violet-500 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            @if($coupon->expires_at)
                {{ $coupon->expires_at->format('d/m/Y H:i') }}
            @else
                ใช้ได้ตลอด
            @endif
        </p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Info Column -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Coupon Info Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                ข้อมูลคูปอง
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-gray-500 dark:text-gray-400">ชื่อคูปอง</span>
                        <span class="text-gray-900 dark:text-white font-medium">{{ $coupon->name }}</span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-gray-500 dark:text-gray-400">รหัสคูปอง</span>
                        <code class="font-mono text-orange-600 dark:text-orange-400 bg-orange-100 dark:bg-orange-900/30 px-2 py-0.5 rounded text-sm">{{ $coupon->code }}</code>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-gray-500 dark:text-gray-400">ประเภทส่วนลด</span>
                        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full
                            @if($coupon->discount_type === 'percentage') bg-gradient-to-r from-amber-400 to-orange-500 text-white
                            @else bg-gradient-to-r from-green-400 to-emerald-500 text-white @endif">
                            {{ $coupon->discount_type === 'percentage' ? 'เปอร์เซ็นต์' : 'จำนวนเงิน' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-3">
                        <span class="text-gray-500 dark:text-gray-400">มูลค่าส่วนลด</span>
                        <span class="text-gray-900 dark:text-white font-bold text-lg">{{ $coupon->discount_label }}</span>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-gray-500 dark:text-gray-400">ยอดขั้นต่ำ</span>
                        <span class="text-gray-900 dark:text-white font-medium">
                            {{ $coupon->min_order_amount ? '฿' . number_format($coupon->min_order_amount, 0) : 'ไม่จำกัด' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-gray-500 dark:text-gray-400">วันที่เริ่ม</span>
                        <span class="text-gray-900 dark:text-white font-medium">
                            {{ $coupon->starts_at ? $coupon->starts_at->format('d/m/Y H:i') : 'ทันที' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-gray-500 dark:text-gray-400">วันหมดอายุ</span>
                        @if($coupon->expires_at)
                            <span class="text-gray-900 dark:text-white font-medium {{ $coupon->isExpired() ? 'text-red-500' : '' }}">
                                {{ $coupon->expires_at->format('d/m/Y H:i') }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                ไม่จำกัด
                            </span>
                        @endif
                    </div>
                    <div class="flex justify-between items-center py-3">
                        <span class="text-gray-500 dark:text-gray-400">สร้างเมื่อ</span>
                        <span class="text-gray-900 dark:text-white font-medium">{{ $coupon->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>

            @if($coupon->description)
                <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">รายละเอียด</p>
                    <p class="text-gray-700 dark:text-gray-300">{{ $coupon->description }}</p>
                </div>
            @endif
        </div>

        <!-- Usage History Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    ประวัติการใช้งาน
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                        {{ $coupon->usages->count() }} รายการ
                    </span>
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ผู้ใช้</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ออเดอร์</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ยอดสั่งซื้อ</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ส่วนลด</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">วันที่</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($coupon->usages as $usage)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white font-bold shadow-lg">
                                        {{ strtoupper(substr($usage->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $usage->user->name ?? 'Unknown' }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $usage->user->email ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($usage->order)
                                    <a href="{{ route('admin.orders.show', $usage->order) }}" class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition">
                                        #{{ $usage->order->order_number }}
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">฿{{ number_format($usage->order_amount, 2) }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-bold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                                    -฿{{ number_format($usage->discount_amount, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $usage->created_at->format('d/m/Y H:i') }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 font-medium">ยังไม่มีการใช้งาน</p>
                                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">ประวัติการใช้งานจะแสดงที่นี่</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Sidebar -->
    <div class="space-y-6">
        <!-- QR Code Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
                QR Code
            </h3>
            <div class="text-center">
                <div id="qrcode" class="inline-block p-4 bg-white rounded-xl shadow-inner"></div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">สแกนเพื่อคัดลอกรหัสคูปอง</p>
            </div>
        </div>

        <!-- Restrictions Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                เงื่อนไขการใช้งาน
            </h3>
            <div class="space-y-3">
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">จำกัดต่อคน</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">ใช้ได้ {{ $coupon->usage_limit_per_user ?? 1 }} ครั้ง/คน</p>
                    </div>
                </div>

                @if($coupon->first_order_only)
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-400 to-yellow-500 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">ออเดอร์แรกเท่านั้น</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">สำหรับลูกค้าใหม่</p>
                    </div>
                </div>
                @endif

                @if($coupon->applicable_products && count($coupon->applicable_products) > 0)
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">จำกัดสินค้า</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ count($coupon->applicable_products) }} รายการ</p>
                    </div>
                </div>
                @endif

                @if($coupon->applicable_license_types && count($coupon->applicable_license_types) > 0)
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-400 to-violet-500 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">ประเภท License</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ implode(', ', $coupon->applicable_license_types) }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Quick Actions
            </h3>
            <div class="space-y-2">
                <button onclick="copyCouponCode()" class="w-full flex items-center gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-gray-700 dark:text-gray-300">คัดลอกรหัสคูปอง</span>
                </button>

                <form action="{{ route('admin.coupons.toggle', $coupon) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 {{ $coupon->is_active ? 'bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/30 text-amber-600 dark:text-amber-400' : 'bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 text-green-600 dark:text-green-400' }} rounded-xl transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($coupon->is_active)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            @endif
                        </svg>
                        <span>{{ $coupon->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}</span>
                    </button>
                </form>

                <a href="{{ route('admin.coupons.edit', $coupon) }}" class="w-full flex items-center gap-3 px-4 py-3 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-xl transition text-blue-600 dark:text-blue-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>แก้ไขคูปอง</span>
                </a>

                <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" onsubmit="return confirm('คุณต้องการลบคูปองนี้หรือไม่?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-xl transition text-red-600 dark:text-red-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span>ลบคูปอง</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toast notification -->
<div id="toast" class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-xl shadow-lg transform translate-y-20 opacity-0 transition-all duration-300 z-50">
    <div class="flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <span id="toastMessage">คัดลอกแล้ว!</span>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
<style>
@keyframes blob {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
}
.animate-blob { animation: blob 7s infinite; }
.animation-delay-2000 { animation-delay: 2s; }
</style>
<script>
    // Generate QR Code
    document.addEventListener('DOMContentLoaded', function() {
        QRCode.toCanvas(document.createElement('canvas'), '{{ $coupon->code }}', {
            width: 150,
            margin: 1,
            color: { dark: '#f97316', light: '#ffffff' }
        }, function(error, canvas) {
            if (error) console.error(error);
            document.getElementById('qrcode').appendChild(canvas);
        });
    });

    function copyCouponCode() {
        const couponCode = '{{ $coupon->code }}';
        navigator.clipboard.writeText(couponCode).then(() => {
            showToast('คัดลอกรหัสคูปองแล้ว!');
        }).catch(() => {
            // Fallback
            const textArea = document.createElement('textarea');
            textArea.value = couponCode;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showToast('คัดลอกรหัสคูปองแล้ว!');
        });
    }

    function showToast(message) {
        const toast = document.getElementById('toast');
        document.getElementById('toastMessage').textContent = message;
        toast.classList.remove('translate-y-20', 'opacity-0');
        setTimeout(() => {
            toast.classList.add('translate-y-20', 'opacity-0');
        }, 3000);
    }
</script>
@endpush
@endsection
