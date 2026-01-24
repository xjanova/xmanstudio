@extends('layouts.admin')

@section('page-title', 'กระเป๋าของ ' . $wallet->user->name)

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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-white">กระเป๋าเงิน</h1>
            </div>
            <p class="text-purple-100 text-lg">{{ $wallet->user->name }} - {{ $wallet->user->email }}</p>
        </div>
        <a href="{{ route('admin.wallets.wallets') }}" class="inline-flex items-center px-5 py-2.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-medium rounded-xl transition-all duration-200 border border-white/25">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            กลับ
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 space-y-8">
        <!-- Balance Card -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-purple-600 via-violet-600 to-indigo-600 p-8 shadow-xl">
            <div class="absolute top-0 right-0 -mt-8 -mr-8 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute bottom-0 left-0 -mb-8 -ml-8 w-40 h-40 bg-purple-400/20 rounded-full blur-2xl"></div>
            <div class="relative grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center md:border-r md:border-white/20">
                    <p class="text-purple-100 text-sm font-medium mb-2">ยอดคงเหลือ</p>
                    <h2 class="text-3xl font-bold text-white">{{ number_format($wallet->balance, 2) }}</h2>
                </div>
                <div class="text-center md:border-r md:border-white/20">
                    <p class="text-purple-100 text-sm font-medium mb-2">เติมเงินรวม</p>
                    <h2 class="text-3xl font-bold text-white">{{ number_format($wallet->total_deposited, 2) }}</h2>
                </div>
                <div class="text-center">
                    <p class="text-purple-100 text-sm font-medium mb-2">ใช้จ่ายรวม</p>
                    <h2 class="text-3xl font-bold text-white">{{ number_format($wallet->total_spent, 2) }}</h2>
                </div>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white">ประวัติธุรกรรม</h5>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">รหัส</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ประเภท</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">รายละเอียด</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">จำนวน</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ยอดคงเหลือ</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">วันที่</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($transactions as $transaction)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                            <td class="px-6 py-4">
                                <code class="text-sm px-2 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">{{ $transaction->transaction_id }}</code>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                    @if($transaction->type === 'deposit') bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400
                                    @elseif($transaction->type === 'payment') bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400
                                    @elseif($transaction->type === 'bonus') bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400
                                    @else bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400
                                    @endif">
                                    {{ $transaction->type_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $transaction->description }}</td>
                            <td class="px-6 py-4">
                                <span class="font-bold {{ $transaction->isCredit() ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    {{ $transaction->isCredit() ? '+' : '' }}{{ number_format($transaction->amount, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ number_format($transaction->balance_after, 2) }}</td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gray-100 dark:bg-gray-700 mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400">ยังไม่มีธุรกรรม</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($transactions->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                {{ $transactions->links() }}
            </div>
            @endif
        </div>
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
                        <span class="text-2xl font-bold text-white">{{ strtoupper(substr($wallet->user->name, 0, 1)) }}</span>
                    </div>
                    <h5 class="text-xl font-semibold text-gray-900 dark:text-white mb-1">{{ $wallet->user->name }}</h5>
                    <p class="text-gray-500 dark:text-gray-400">{{ $wallet->user->email }}</p>
                </div>
                <div class="border-t border-gray-100 dark:border-gray-700 pt-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">สมาชิกตั้งแต่</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $wallet->user->created_at->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">สถานะกระเป๋า</span>
                        @if($wallet->is_active)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">
                            ใช้งานได้
                        </span>
                        @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400">
                            ปิดใช้งาน
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Adjust Balance -->
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white">ปรับยอดเงิน</h5>
            </div>
            <div class="p-6">
                <form action="{{ route('admin.wallets.adjust', $wallet) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนเงิน <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">฿</span>
                            <input type="number" name="amount" required step="0.01" placeholder="ใส่ค่าลบเพื่อหัก"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ใส่ค่าบวกเพื่อเพิ่ม, ค่าลบเพื่อหัก</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รายละเอียด <span class="text-rose-500">*</span></label>
                        <input type="text" name="description" required placeholder="เช่น แก้ไขยอดเงิน"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมายเหตุ (เฉพาะแอดมิน)</label>
                        <textarea name="admin_note" rows="2"
                                  class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"></textarea>
                    </div>
                    <button type="submit" onclick="return confirm('ยืนยันการปรับยอดเงิน?')"
                            class="w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-medium rounded-xl shadow-lg shadow-amber-500/30 hover:shadow-amber-500/40 transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                        </svg>
                        ปรับยอด
                    </button>
                </form>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white">การจัดการ</h5>
            </div>
            <div class="p-6">
                <form action="{{ route('admin.wallets.adjust', $wallet) }}" method="POST">
                    @csrf
                    <input type="hidden" name="amount" value="0">
                    <input type="hidden" name="description" value="{{ $wallet->is_active ? 'ปิดใช้งานกระเป๋า' : 'เปิดใช้งานกระเป๋า' }}">
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-6 py-3 {{ $wallet->is_active ? 'bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 shadow-amber-500/30 hover:shadow-amber-500/40' : 'bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 shadow-emerald-500/30 hover:shadow-emerald-500/40' }} text-white font-medium rounded-xl shadow-lg transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($wallet->is_active)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            @endif
                        </svg>
                        {{ $wallet->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
