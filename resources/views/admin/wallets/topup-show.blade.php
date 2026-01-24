@extends('layouts.admin')

@section('page-title', 'รายการเติมเงิน: ' . $topup->topup_id)

@section('content')
<!-- Premium Gradient Header -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-purple-600 via-violet-600 to-indigo-600 p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-grid-white/10"></div>
    <div class="absolute top-0 right-0 -mt-16 -mr-16 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 -mb-16 -ml-16 w-64 h-64 bg-purple-400/20 rounded-full blur-3xl"></div>
    <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-white">รายละเอียดการเติมเงิน</h1>
            </div>
            <p class="text-purple-100 text-lg">รหัส: <span class="font-mono bg-white/20 px-2 py-1 rounded-lg">{{ $topup->topup_id }}</span></p>
        </div>
        <a href="{{ route('admin.wallets.topups') }}" class="inline-flex items-center px-5 py-2.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-medium rounded-xl transition-all duration-200 border border-white/25">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            กลับ
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 space-y-8">
        <!-- Topup Info -->
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white">ข้อมูลรายการ</h5>
                <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-semibold
                    @if($topup->status === 'pending') bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400
                    @elseif($topup->status === 'approved') bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400
                    @else bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400
                    @endif">
                    {{ $topup->status_label }}
                </span>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="text-center p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">ยอดเติมเงิน</p>
                        <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($topup->amount, 2) }}</h4>
                    </div>
                    <div class="text-center p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20">
                        <p class="text-sm text-emerald-600 dark:text-emerald-400 mb-1">โบนัส</p>
                        <h4 class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">+{{ number_format($topup->bonus_amount, 2) }}</h4>
                    </div>
                    <div class="text-center p-4 rounded-xl bg-purple-50 dark:bg-purple-900/20">
                        <p class="text-sm text-purple-600 dark:text-purple-400 mb-1">ยอดรวม</p>
                        <h4 class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($topup->total_amount, 2) }}</h4>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">ช่องทางชำระเงิน</p>
                        <p class="text-lg font-medium text-gray-900 dark:text-white">{{ $topup->payment_method_label }}</p>
                    </div>
                    @if($topup->payment_reference)
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">หมายเลขอ้างอิง</p>
                        <code class="text-sm px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">{{ $topup->payment_reference }}</code>
                    </div>
                    @endif
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">วันที่สร้าง</p>
                        <p class="text-gray-900 dark:text-white">{{ $topup->created_at->format('d/m/Y H:i:s') }}</p>
                    </div>
                    @if($topup->approved_at)
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">วันที่{{ $topup->status === 'approved' ? 'อนุมัติ' : 'ปฏิเสธ' }}</p>
                        <p class="text-gray-900 dark:text-white">{{ $topup->approved_at->format('d/m/Y H:i:s') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Payment Proof -->
        @if($topup->payment_proof)
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white">หลักฐานการชำระเงิน</h5>
            </div>
            <div class="p-6 text-center">
                <a href="{{ asset('storage/' . $topup->payment_proof) }}" target="_blank" class="inline-block">
                    <img src="{{ asset('storage/' . $topup->payment_proof) }}" class="max-h-96 rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-200" alt="Payment Proof">
                </a>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-3">คลิกเพื่อดูขนาดเต็ม</p>
            </div>
        </div>
        @endif

        <!-- Reject Reason -->
        @if($topup->status === 'rejected' && $topup->reject_reason)
        <div class="rounded-2xl bg-rose-50 dark:bg-rose-900/20 border-2 border-rose-200 dark:border-rose-800 overflow-hidden">
            <div class="px-6 py-4 bg-rose-100 dark:bg-rose-900/40 border-b border-rose-200 dark:border-rose-800">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h5 class="text-lg font-semibold text-rose-800 dark:text-rose-300">เหตุผลที่ปฏิเสธ</h5>
                </div>
            </div>
            <div class="p-6">
                <p class="text-rose-800 dark:text-rose-300">{{ $topup->reject_reason }}</p>
            </div>
        </div>
        @endif
    </div>

    <div class="space-y-8">
        <!-- User Info -->
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white">ข้อมูลผู้ใช้</h5>
            </div>
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-600 mb-4 shadow-lg shadow-purple-500/30">
                        <span class="text-2xl font-bold text-white">{{ strtoupper(substr($topup->user->name, 0, 1)) }}</span>
                    </div>
                    <h5 class="text-xl font-semibold text-gray-900 dark:text-white mb-1">{{ $topup->user->name }}</h5>
                    <p class="text-gray-500 dark:text-gray-400">{{ $topup->user->email }}</p>
                </div>
                <div class="border-t border-gray-100 dark:border-gray-700 pt-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">ยอดคงเหลือปัจจุบัน</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ number_format($topup->wallet->balance ?? 0, 2) }}</span>
                    </div>
                </div>
                <a href="{{ route('admin.wallets.show', $topup->wallet) }}" class="mt-6 w-full inline-flex items-center justify-center px-4 py-2.5 bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 font-medium rounded-xl hover:bg-purple-100 dark:hover:bg-purple-900/50 transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    ดูกระเป๋าเงิน
                </a>
            </div>
        </div>

        <!-- Actions -->
        @if($topup->status === 'pending')
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white">ดำเนินการ</h5>
            </div>
            <div class="p-6 space-y-3">
                <form action="{{ route('admin.wallets.topups.approve', $topup) }}" method="POST" onsubmit="return confirm('ยืนยันอนุมัติรายการนี้?')">
                    @csrf
                    <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-medium rounded-xl shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/40 transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        อนุมัติ
                    </button>
                </form>
                <button type="button" onclick="document.getElementById('rejectModal').classList.remove('hidden')" class="w-full inline-flex items-center justify-center px-6 py-3 border-2 border-rose-200 dark:border-rose-800 text-rose-600 dark:text-rose-400 font-medium rounded-xl hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-all duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    ปฏิเสธ
                </button>
            </div>
        </div>

        <!-- Reject Modal -->
        <div id="rejectModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="document.getElementById('rejectModal').classList.add('hidden')"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6">
                    <form action="{{ route('admin.wallets.topups.reject', $topup) }}" method="POST">
                        @csrf
                        <div class="flex items-center justify-between mb-6">
                            <h5 class="text-lg font-semibold text-gray-900 dark:text-white">ปฏิเสธรายการ</h5>
                            <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล <span class="text-rose-500">*</span></label>
                            <textarea name="reason" rows="3" required placeholder="ระบุเหตุผลในการปฏิเสธ..."
                                      class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"></textarea>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200">
                                ยกเลิก
                            </button>
                            <button type="submit" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-rose-500 to-red-600 text-white font-medium rounded-xl hover:from-rose-600 hover:to-red-700 shadow-lg shadow-rose-500/30 transition-all duration-200">
                                ปฏิเสธ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        <!-- Approver Info -->
        @if($topup->approvedBy)
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $topup->status === 'approved' ? 'อนุมัติโดย' : 'ปฏิเสธโดย' }}</h5>
            </div>
            <div class="p-6">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-gray-400 to-gray-600 flex items-center justify-center shadow-lg">
                        <span class="text-sm font-bold text-white">{{ strtoupper(substr($topup->approvedBy->name, 0, 1)) }}</span>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $topup->approvedBy->name }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $topup->approved_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
