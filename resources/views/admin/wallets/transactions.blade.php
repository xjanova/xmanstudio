@extends($adminLayout ?? 'layouts.admin')

@section('page-title', 'ธุรกรรมทั้งหมด')

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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-white">ธุรกรรมทั้งหมด</h1>
            </div>
            <p class="text-purple-100 text-lg">ดูประวัติธุรกรรมทั้งหมดในระบบ</p>
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
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหา ID หรือชื่อ..."
                   class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
        </div>
        <div class="md:w-48">
            <select name="type" class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                <option value="">-- ประเภททั้งหมด --</option>
                <option value="deposit" {{ request('type') === 'deposit' ? 'selected' : '' }}>เติมเงิน</option>
                <option value="payment" {{ request('type') === 'payment' ? 'selected' : '' }}>ชำระเงิน</option>
                <option value="refund" {{ request('type') === 'refund' ? 'selected' : '' }}>คืนเงิน</option>
                <option value="bonus" {{ request('type') === 'bonus' ? 'selected' : '' }}>โบนัส</option>
                <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>ปรับยอด</option>
            </select>
        </div>
        <div class="md:w-48">
            <select name="status" class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                <option value="">-- สถานะทั้งหมด --</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>ล้มเหลว</option>
            </select>
        </div>
        <button type="submit" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-medium rounded-xl shadow-lg shadow-purple-500/30 hover:shadow-purple-500/40 transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            ค้นหา
        </button>
        @if(request()->hasAny(['search', 'type', 'status']))
        <a href="{{ route('admin.wallets.transactions') }}" class="inline-flex items-center justify-center px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            ล้าง
        </a>
        @endif
    </form>
</div>

<!-- Transactions Table -->
<div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">รหัส</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ผู้ใช้</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ประเภท</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">รายละเอียด</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">จำนวน</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
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
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-9 h-9 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-purple-500/30">
                                <span class="text-xs font-bold text-white">{{ strtoupper(substr($transaction->user->name, 0, 1)) }}</span>
                            </div>
                            <div class="font-semibold text-gray-900 dark:text-white">{{ $transaction->user->name }}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                            @if($transaction->type === 'deposit') bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400
                            @elseif($transaction->type === 'payment') bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400
                            @elseif($transaction->type === 'refund') bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400
                            @elseif($transaction->type === 'bonus') bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400
                            @else bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400
                            @endif">
                            {{ $transaction->type_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ Str::limit($transaction->description, 30) }}</td>
                    <td class="px-6 py-4">
                        <span class="font-bold {{ $transaction->isCredit() ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            {{ $transaction->isCredit() ? '+' : '' }}{{ number_format($transaction->amount, 2) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                            @if($transaction->status === 'completed') bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400
                            @elseif($transaction->status === 'pending') bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400
                            @else bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400
                            @endif">
                            <span class="w-1.5 h-1.5 rounded-full mr-2
                                @if($transaction->status === 'completed') bg-emerald-500
                                @elseif($transaction->status === 'pending') bg-amber-500
                                @else bg-rose-500
                                @endif"></span>
                            {{ $transaction->status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-16 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gray-100 dark:bg-gray-700 mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">ไม่พบธุรกรรม</p>
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
@endsection
