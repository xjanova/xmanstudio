@extends('layouts.admin')

@section('page-title', 'ระบบ Wallet')

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
                <h1 class="text-2xl md:text-3xl font-bold text-white">ระบบ Wallet</h1>
            </div>
            <p class="text-purple-100 text-lg">ภาพรวมกระเป๋าเงินและธุรกรรม</p>
        </div>
        <div>
            <a href="{{ route('admin.wallets.topups') }}" class="relative inline-flex items-center px-5 py-2.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-medium rounded-xl transition-all duration-200 border border-white/25">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                รอตรวจสอบ
                @if($stats['pending_topups'] > 0)
                <span class="absolute -top-2 -right-2 inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-red-500 rounded-full shadow-lg animate-pulse">
                    {{ $stats['pending_topups'] }}
                </span>
                @endif
            </a>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Balance -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-600 p-6 shadow-xl">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
        <div class="relative">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium mb-1">ยอดรวมในระบบ</p>
                    <h3 class="text-2xl font-bold text-white">{{ number_format($stats['total_balance'], 2) }}</h3>
                </div>
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Deposited -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-green-600 p-6 shadow-xl">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
        <div class="relative">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-sm font-medium mb-1">เติมเงินทั้งหมด</p>
                    <h3 class="text-2xl font-bold text-white">{{ number_format($stats['total_deposited'], 2) }}</h3>
                </div>
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Spent -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-rose-500 to-red-600 p-6 shadow-xl">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
        <div class="relative">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-rose-100 text-sm font-medium mb-1">ใช้จ่ายทั้งหมด</p>
                    <h3 class="text-2xl font-bold text-white">{{ number_format($stats['total_spent'], 2) }}</h3>
                </div>
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Wallets -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-600 p-6 shadow-xl">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
        <div class="relative">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium mb-1">กระเป๋าทั้งหมด</p>
                    <h3 class="text-2xl font-bold text-white">{{ number_format($stats['total_wallets']) }}</h3>
                </div>
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Links -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <a href="{{ route('admin.wallets.wallets') }}" class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-xl border border-gray-100 dark:border-gray-700 hover:shadow-2xl hover:scale-[1.02] transition-all duration-300">
        <div class="absolute inset-0 bg-gradient-to-br from-purple-500/5 to-indigo-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        <div class="relative text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-600 mb-4 shadow-lg shadow-purple-500/30 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
            </div>
            <h5 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">กระเป๋าเงินทั้งหมด</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">ดูและจัดการกระเป๋า</p>
        </div>
    </a>

    <a href="{{ route('admin.wallets.topups') }}" class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-xl border border-gray-100 dark:border-gray-700 hover:shadow-2xl hover:scale-[1.02] transition-all duration-300">
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-green-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        <div class="relative text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-green-600 mb-4 shadow-lg shadow-emerald-500/30 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
            </div>
            <h5 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">รายการเติมเงิน</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">อนุมัติการเติมเงิน</p>
        </div>
    </a>

    <a href="{{ route('admin.wallets.transactions') }}" class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-xl border border-gray-100 dark:border-gray-700 hover:shadow-2xl hover:scale-[1.02] transition-all duration-300">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-cyan-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        <div class="relative text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-600 mb-4 shadow-lg shadow-blue-500/30 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                </svg>
            </div>
            <h5 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">ธุรกรรมทั้งหมด</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">ดูประวัติทั้งหมด</p>
        </div>
    </a>

    <a href="{{ route('admin.wallets.bonus-tiers') }}" class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-xl border border-gray-100 dark:border-gray-700 hover:shadow-2xl hover:scale-[1.02] transition-all duration-300">
        <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-orange-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        <div class="relative text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 mb-4 shadow-lg shadow-amber-500/30 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                </svg>
            </div>
            <h5 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">โบนัสเติมเงิน</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">ตั้งค่าโบนัส</p>
        </div>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Pending Topups -->
    <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 shadow-lg shadow-amber-500/30">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white">รอตรวจสอบ</h5>
            </div>
            <a href="{{ route('admin.wallets.topups', ['status' => 'pending']) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30 rounded-xl hover:bg-purple-100 dark:hover:bg-purple-900/50 transition-colors duration-200">
                ดูทั้งหมด
            </a>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($pendingTopups as $topup)
            <a href="{{ route('admin.wallets.topups.show', $topup) }}" class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                <div>
                    <h6 class="font-semibold text-gray-900 dark:text-white">{{ $topup->user->name }}</h6>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $topup->topup_id }} - {{ $topup->payment_method_label }}</p>
                </div>
                <div class="text-right">
                    <span class="font-bold text-emerald-600 dark:text-emerald-400">+{{ number_format($topup->total_amount, 2) }}</span>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $topup->created_at->diffForHumans() }}</p>
                </div>
            </a>
            @empty
            <div class="px-6 py-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 mb-4">
                    <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-gray-500 dark:text-gray-400">ไม่มีรายการรอตรวจสอบ</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 shadow-lg shadow-blue-500/30">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                </div>
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white">ธุรกรรมล่าสุด</h5>
            </div>
            <a href="{{ route('admin.wallets.transactions') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30 rounded-xl hover:bg-purple-100 dark:hover:bg-purple-900/50 transition-colors duration-200">
                ดูทั้งหมด
            </a>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($recentTransactions as $transaction)
            <div class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                <div class="flex items-center gap-4">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl {{ $transaction->isCredit() ? 'bg-emerald-50 dark:bg-emerald-900/30' : 'bg-rose-50 dark:bg-rose-900/30' }}">
                        <svg class="w-5 h-5 {{ $transaction->isCredit() ? 'text-emerald-500' : 'text-rose-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($transaction->isCredit())
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                            @endif
                        </svg>
                    </div>
                    <div>
                        <h6 class="font-semibold text-gray-900 dark:text-white">{{ $transaction->user->name }}</h6>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $transaction->type_label }} - {{ $transaction->description }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="font-bold {{ $transaction->isCredit() ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                        {{ $transaction->isCredit() ? '+' : '' }}{{ number_format($transaction->amount, 2) }}
                    </span>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $transaction->created_at->diffForHumans() }}</p>
                </div>
            </div>
            @empty
            <div class="px-6 py-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gray-50 dark:bg-gray-700 mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                </div>
                <p class="text-gray-500 dark:text-gray-400">ยังไม่มีธุรกรรม</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
