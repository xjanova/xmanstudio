@extends($adminLayout ?? 'layouts.admin')

@section('page-title', 'กระเป๋าเงินทั้งหมด')

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
                <h1 class="text-2xl md:text-3xl font-bold text-white">กระเป๋าเงินทั้งหมด</h1>
            </div>
            <p class="text-purple-100 text-lg">ดูและจัดการกระเป๋าเงินของผู้ใช้</p>
        </div>
        <a href="{{ route('admin.wallets.index') }}" class="inline-flex items-center px-5 py-2.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-medium rounded-xl transition-all duration-200 border border-white/25">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            กลับ
        </a>
    </div>
</div>

<!-- Filters -->
<div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 p-6 mb-8">
    <form method="GET" class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาชื่อหรืออีเมล..."
                   class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
        </div>
        <div class="md:w-48">
            <input type="number" name="min_balance" value="{{ request('min_balance') }}" placeholder="ยอดขั้นต่ำ"
                   class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
        </div>
        <button type="submit" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-medium rounded-xl shadow-lg shadow-purple-500/30 hover:shadow-purple-500/40 transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            ค้นหา
        </button>
    </form>
</div>

<!-- Wallets Table -->
<div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ผู้ใช้</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ยอดคงเหลือ</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">เติมเงินรวม</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ใช้จ่ายรวม</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">อัพเดทล่าสุด</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($wallets as $wallet)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-4">
                            <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-purple-500/30">
                                <span class="text-sm font-bold text-white">{{ strtoupper(substr($wallet->user->name, 0, 1)) }}</span>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $wallet->user->name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $wallet->user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-bold {{ $wallet->balance > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-600 dark:text-gray-400' }}">
                            {{ number_format($wallet->balance, 2) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ number_format($wallet->total_deposited, 2) }}</td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ number_format($wallet->total_spent, 2) }}</td>
                    <td class="px-6 py-4">
                        @if($wallet->is_active)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2"></span>
                            ใช้งานได้
                        </span>
                        @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-2"></span>
                            ปิดใช้งาน
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $wallet->updated_at->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('admin.wallets.show', $wallet) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30 rounded-xl hover:bg-purple-100 dark:hover:bg-purple-900/50 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            ดูรายละเอียด
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-16 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gray-100 dark:bg-gray-700 mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">ไม่พบกระเป๋าเงิน</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($wallets->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
        {{ $wallets->links() }}
    </div>
    @endif
</div>
@endsection
