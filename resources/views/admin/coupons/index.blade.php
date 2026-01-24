@extends($adminLayout ?? 'layouts.admin')

@section('title', 'จัดการคูปอง')
@section('page-title', 'จัดการคูปอง')

@section('content')
<!-- Premium Header Banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 p-6 sm:p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/5 rounded-full blur-3xl animate-blob"></div>
    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-yellow-400/10 rounded-full blur-3xl animate-blob animation-delay-2000"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-white/20 text-white backdrop-blur-sm">
                    Coupon Management
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white">จัดการคูปองส่วนลด</h1>
            <p class="text-white/80 mt-1">สร้างและจัดการโค้ดส่วนลดสำหรับลูกค้า</p>
        </div>
        <a href="{{ route('admin.coupons.create') }}"
           class="inline-flex items-center px-5 py-2.5 bg-white/20 text-white rounded-xl hover:bg-white/30 transition-all font-medium backdrop-blur-sm shadow-lg">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            สร้างคูปองใหม่
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <!-- Total Coupons -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">คูปองทั้งหมด</p>
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</h3>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Active -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">ใช้งานได้</p>
                <h3 class="text-3xl font-bold text-green-600">{{ number_format($stats['active']) }}</h3>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center shadow-lg">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Expired -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">หมดอายุ</p>
                <h3 class="text-3xl font-bold text-red-600">{{ number_format($stats['expired']) }}</h3>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-red-400 to-rose-600 flex items-center justify-center shadow-lg">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Usage -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">ใช้งานแล้ว</p>
                <h3 class="text-3xl font-bold text-purple-600">{{ number_format($stats['total_usage']) }}</h3>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-400 to-violet-600 flex items-center justify-center shadow-lg">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filter -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 mb-6">
    <form method="GET" class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 dark:bg-gray-700 dark:text-white transition"
                       placeholder="ค้นหาโค้ดหรือชื่อคูปอง...">
            </div>
        </div>
        <div class="sm:w-48">
            <select name="status"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 dark:bg-gray-700 dark:text-white transition">
                <option value="">ทุกสถานะ</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งานได้</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>หมดอายุ</option>
                <option value="used_up" {{ request('status') === 'used_up' ? 'selected' : '' }}>ใช้ครบแล้ว</option>
            </select>
        </div>
        <button type="submit"
                class="px-6 py-2.5 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-xl hover:from-orange-600 hover:to-red-600 font-medium shadow-lg transition transform hover:scale-105">
            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            ค้นหา
        </button>
        @if(request()->hasAny(['search', 'status']))
        <a href="{{ route('admin.coupons.index') }}"
           class="px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 font-medium transition">
            ล้าง
        </a>
        @endif
    </form>
</div>

<!-- Coupons Table -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">โค้ด</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ชื่อ/รายละเอียด</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ส่วนลด</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">การใช้งาน</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ระยะเวลา</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($coupons as $coupon)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                            </div>
                            <code class="text-lg font-mono font-semibold text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-lg">{{ $coupon->code }}</code>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900 dark:text-white">{{ $coupon->name }}</div>
                        @if($coupon->description)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ Str::limit($coupon->description, 40) }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-3 py-1.5 text-sm font-bold rounded-full bg-gradient-to-r from-green-400 to-emerald-500 text-white shadow">
                            {{ $coupon->discount_label }}
                        </span>
                        @if($coupon->min_order_amount)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ขั้นต่ำ ฿{{ number_format($coupon->min_order_amount, 0) }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $coupon->used_count }}</span>
                            <span class="text-gray-400">/</span>
                            <span class="text-gray-500 dark:text-gray-400">{{ $coupon->usage_limit ?? '∞' }}</span>
                        </div>
                        @if($coupon->usage_limit)
                        <div class="w-24 h-2 bg-gray-200 dark:bg-gray-600 rounded-full mt-2 overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-blue-400 to-indigo-500 rounded-full transition-all"
                                 style="width: {{ min(100, ($coupon->used_count / $coupon->usage_limit) * 100) }}%"></div>
                        </div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($coupon->starts_at || $coupon->expires_at)
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            @if($coupon->starts_at)
                            <span>{{ $coupon->starts_at->format('d/m/y') }}</span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                            <span class="text-gray-400 mx-1">→</span>
                            @if($coupon->expires_at)
                            <span>{{ $coupon->expires_at->format('d/m/y') }}</span>
                            @else
                            <span class="text-purple-500">∞</span>
                            @endif
                        </div>
                        @else
                        <span class="text-gray-400">ไม่จำกัด</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $statusColors = [
                                'success' => 'from-green-400 to-emerald-500',
                                'danger' => 'from-red-400 to-rose-500',
                                'warning' => 'from-amber-400 to-orange-500',
                                'secondary' => 'from-gray-400 to-gray-500',
                            ];
                            $colorClass = $statusColors[$coupon->status_color] ?? 'from-gray-400 to-gray-500';
                        @endphp
                        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-gradient-to-r {{ $colorClass }} text-white shadow">
                            {{ $coupon->status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.coupons.show', $coupon) }}"
                               class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition"
                               title="ดูรายละเอียด">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <a href="{{ route('admin.coupons.edit', $coupon) }}"
                               class="p-2 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition"
                               title="แก้ไข">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <form action="{{ route('admin.coupons.toggle', $coupon) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="p-2 {{ $coupon->is_active ? 'text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/30' : 'text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30' }} rounded-lg transition"
                                        title="{{ $coupon->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}">
                                    @if($coupon->is_active)
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    @endif
                                </button>
                            </form>
                            <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" class="inline" onsubmit="return confirm('ต้องการลบคูปองนี้?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition"
                                        title="ลบ">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center">
                            <div class="w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 text-lg">ไม่พบคูปอง</p>
                            <a href="{{ route('admin.coupons.create') }}" class="mt-4 text-orange-600 hover:text-orange-700 font-medium">
                                สร้างคูปองใหม่ →
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($coupons->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
        {{ $coupons->links() }}
    </div>
    @endif
</div>

@push('scripts')
<style>
@keyframes blob {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
}
.animate-blob { animation: blob 7s infinite; }
.animation-delay-2000 { animation-delay: 2s; }
</style>
@endpush
@endsection
