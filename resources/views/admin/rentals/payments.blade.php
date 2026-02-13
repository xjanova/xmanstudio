@extends($adminLayout ?? 'layouts.admin')

@section('title', 'การชำระเงิน')
@section('page-title', 'การชำระเงิน')

@push('styles')
<style>
    .animate-blob {
        animation: blob 7s infinite;
    }
    .animation-delay-2000 {
        animation-delay: 2s;
    }
    .animation-delay-4000 {
        animation-delay: 4s;
    }
    @keyframes blob {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
    }
</style>
@endpush

@section('content')
<!-- Premium Header Banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-pink-500 via-rose-500 to-red-500 p-6 sm:p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="absolute top-0 left-0 w-72 h-72 bg-pink-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
    <div class="absolute top-0 right-0 w-72 h-72 bg-rose-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-8 left-20 w-72 h-72 bg-red-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                การชำระเงิน
            </h1>
            <p class="mt-2 text-white/80 text-sm sm:text-base">จัดการและตรวจสอบการชำระเงินทั้งหมด</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.rentals.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับ
            </a>
        </div>
    </div>
</div>

<!-- Premium Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
        <div class="absolute inset-0 bg-gradient-to-br from-amber-400/10 to-yellow-600/10"></div>
        <div class="relative p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-400 to-yellow-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">รอยืนยัน</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['pending'] }}</p>
        </div>
    </div>

    <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-400/10 to-indigo-600/10"></div>
        <div class="relative p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">กำลังดำเนินการ</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['processing'] }}</p>
        </div>
    </div>

    <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
        <div class="absolute inset-0 bg-gradient-to-br from-green-400/10 to-emerald-600/10"></div>
        <div class="relative p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">รายได้วันนี้</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">฿{{ number_format($stats['today_revenue']) }}</p>
        </div>
    </div>

    <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
        <div class="absolute inset-0 bg-gradient-to-br from-pink-400/10 to-rose-600/10"></div>
        <div class="relative p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-pink-400 to-rose-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">รายได้เดือนนี้</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">฿{{ number_format($stats['month_revenue']) }}</p>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
    <form action="{{ route('admin.rentals.payments') }}" method="GET" class="flex flex-wrap gap-4 items-center">
        <div class="flex-1 min-w-[200px]">
            <select name="status" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 dark:bg-gray-700 dark:text-white transition-all">
                <option value="all">ทุกสถานะ</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอยืนยัน</option>
                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>ไม่สำเร็จ</option>
            </select>
        </div>
        <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-pink-500 to-rose-600 text-white rounded-xl hover:from-pink-600 hover:to-rose-700 transition-all font-medium shadow-lg hover:shadow-xl transform hover:scale-105">
            กรอง
        </button>
    </form>
</div>

<!-- Payments Table -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-750">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">วันที่</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ผู้ใช้</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">แพ็กเกจ</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">จำนวน</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">วิธี</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">การดำเนินการ</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($payments as $payment)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <div class="font-medium">{{ $payment->created_at->format('d/m/Y') }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs">{{ $payment->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-pink-400 to-rose-600 flex items-center justify-center mr-4 shadow-md">
                                    <span class="text-white font-bold text-sm">{{ strtoupper(substr($payment->user->name, 0, 2)) }}</span>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $payment->user->name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $payment->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-300">
                                {{ $payment->userRental?->rentalPackage?->display_name ?? '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold text-gray-900 dark:text-white bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 px-3 py-1.5 rounded-lg">
                                ฿{{ number_format($payment->amount) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400">
                                @if($payment->payment_method === 'promptpay')
                                    <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                    </svg>
                                    พร้อมเพย์
                                @elseif($payment->payment_method === 'bank_transfer')
                                    <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M4 10v7h3v-7H4zm6 0v7h3v-7h-3zM2 22h19v-3H2v3zm14-12v7h3v-7h-3zm-4.5-9L2 6v2h19V6l-9.5-5z"/>
                                    </svg>
                                    โอนเงิน
                                @else
                                    {{ $payment->payment_method }}
                                @endif
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-3 py-1.5 text-xs font-semibold rounded-full shadow-sm
                                @if($payment->status === 'completed') bg-gradient-to-r from-green-400 to-emerald-500 text-white
                                @elseif($payment->status === 'pending') bg-gradient-to-r from-amber-400 to-yellow-500 text-white
                                @elseif($payment->status === 'processing') bg-gradient-to-r from-blue-400 to-indigo-500 text-white
                                @else bg-gradient-to-r from-red-400 to-rose-500 text-white @endif">
                                {{ $payment->status_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center space-x-1">
                                @if($payment->status === 'pending' || $payment->status === 'processing')
                                    @if($payment->transfer_slip_url)
                                        <a href="{{ Storage::url($payment->transfer_slip_url) }}" target="_blank"
                                           class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition"
                                           title="ดูสลิป">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </a>
                                    @endif
                                    <button type="button" onclick="showVerifyModal({{ $payment->id }})"
                                            class="p-2 text-green-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition"
                                            title="ยืนยัน">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </button>
                                    <button type="button" onclick="showRejectModal({{ $payment->id }})"
                                            class="p-2 text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition"
                                            title="ปฏิเสธ">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </button>
                                @elseif($payment->status === 'completed')
                                    <span class="text-gray-400 dark:text-gray-500 text-sm flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        ยืนยันแล้ว
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-pink-400 to-rose-600 rounded-full mb-4 shadow-xl">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">ไม่พบข้อมูล</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ยังไม่มีการชำระเงินในระบบ</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
            {{ $payments->links() }}
        </div>
    @endif
</div>

<!-- Verify Modal -->
<div id="verifyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden">
        <form id="verifyForm" method="POST">
            @csrf
            <div class="p-6">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 text-center">ยืนยันการชำระเงิน</h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมายเหตุ (ถ้ามี)</label>
                    <textarea name="notes" rows="3"
                              class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white transition-all"
                              placeholder="เพิ่มหมายเหตุ..."></textarea>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 flex justify-end space-x-3">
                <button type="button" onclick="hideVerifyModal()"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium transition">ยกเลิก</button>
                <button type="submit"
                        class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:from-green-600 hover:to-emerald-700 font-medium shadow-lg transition transform hover:scale-105">ยืนยัน</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden">
        <form id="rejectForm" method="POST">
            @csrf
            <div class="p-6">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-red-400 to-rose-600 flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 text-center">ปฏิเสธการชำระเงิน</h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล *</label>
                    <textarea name="reason" rows="3" required
                              class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white transition-all"
                              placeholder="ระบุเหตุผลในการปฏิเสธ..."></textarea>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 flex justify-end space-x-3">
                <button type="button" onclick="hideRejectModal()"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium transition">ยกเลิก</button>
                <button type="submit"
                        class="px-4 py-2 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl hover:from-red-600 hover:to-rose-700 font-medium shadow-lg transition transform hover:scale-105">ปฏิเสธ</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function showVerifyModal(paymentId) {
        document.getElementById('verifyForm').action = `/admin/rentals/payments/${paymentId}/verify`;
        document.getElementById('verifyModal').classList.remove('hidden');
        document.getElementById('verifyModal').classList.add('flex');
    }

    function hideVerifyModal() {
        document.getElementById('verifyModal').classList.add('hidden');
        document.getElementById('verifyModal').classList.remove('flex');
    }

    function showRejectModal(paymentId) {
        document.getElementById('rejectForm').action = `/admin/rentals/payments/${paymentId}/reject`;
        document.getElementById('rejectModal').classList.remove('hidden');
        document.getElementById('rejectModal').classList.add('flex');
    }

    function hideRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
        document.getElementById('rejectModal').classList.remove('flex');
    }
</script>
@endpush
@endsection
