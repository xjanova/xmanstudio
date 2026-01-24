@extends('layouts.app')

@section('title', 'ประวัติธุรกรรม')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Gradient Header Banner -->
    <div class="bg-gradient-to-r from-purple-600 via-purple-500 to-indigo-600 dark:from-purple-700 dark:via-purple-600 dark:to-indigo-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="mb-4 md:mb-0">
                    <nav class="flex items-center text-purple-200 text-sm mb-2">
                        <a href="{{ route('user.wallet.index') }}" class="hover:text-white transition-colors duration-200">กระเป๋าเงิน</a>
                        <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span class="text-white">ประวัติธุรกรรม</span>
                    </nav>
                    <h1 class="text-3xl font-bold text-white flex items-center">
                        <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        ประวัติธุรกรรม
                    </h1>
                    <p class="mt-1 text-purple-100">ยอดคงเหลือ: <span class="font-bold text-white text-xl">฿{{ number_format($wallet->balance, 2) }}</span></p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('user.wallet.topup') }}" class="inline-flex items-center px-5 py-2.5 bg-white text-purple-600 font-semibold rounded-xl shadow-lg hover:bg-purple-50 transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        เติมเงิน
                    </a>
                    <a href="{{ route('user.wallet.index') }}" class="inline-flex items-center px-5 py-2.5 bg-white/10 text-white font-semibold rounded-xl hover:bg-white/20 transition-all duration-200 backdrop-blur-sm">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        กลับ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Summary Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-5">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">รายรับ</p>
                        <p class="text-xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($wallet->total_deposited, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-5">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-red-400 to-rose-500 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">รายจ่าย</p>
                        <p class="text-xl font-bold text-red-600 dark:text-red-400">฿{{ number_format($wallet->total_spent, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-5">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-indigo-500 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ยอดคงเหลือ</p>
                        <p class="text-xl font-bold text-purple-600 dark:text-purple-400">฿{{ number_format($wallet->balance, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions List -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    รายการทั้งหมด
                    <span class="ml-2 px-2.5 py-0.5 bg-purple-200 dark:bg-purple-700 text-purple-800 dark:text-purple-100 text-xs font-medium rounded-full">
                        {{ $transactions->total() }} รายการ
                    </span>
                </h3>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($transactions as $transaction)
                <div class="p-4 sm:p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-start sm:items-center">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-gradient-to-br
                                @if($transaction->isCredit()) from-green-400 to-emerald-500 @else from-red-400 to-rose-500 @endif
                                rounded-xl flex items-center justify-center mr-4 shadow-lg flex-shrink-0">
                                @if($transaction->isCredit())
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                @else
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                </svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ $transaction->type_label }}</h4>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($transaction->isCredit())
                                            bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                                        @else
                                            bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300
                                        @endif">
                                        {{ $transaction->isCredit() ? 'เข้า' : 'ออก' }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $transaction->description }}</p>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2">
                                    <code class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-600 dark:text-gray-400 font-mono">
                                        {{ $transaction->transaction_id }}
                                    </code>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ $transaction->created_at->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between sm:justify-end sm:flex-col sm:items-end gap-2 sm:gap-1 ml-0 sm:ml-4 pl-16 sm:pl-0">
                            <div class="text-right">
                                <p class="text-xl font-bold {{ $transaction->isCredit() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $transaction->isCredit() ? '+' : '' }}฿{{ number_format($transaction->amount, 2) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    ยอดคงเหลือ: <span class="font-medium text-gray-700 dark:text-gray-300">฿{{ number_format($transaction->balance_after, 2) }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-12 text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีประวัติธุรกรรม</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">เริ่มเติมเงินเพื่อใช้งาน Wallet ของคุณ</p>
                    <a href="{{ route('user.wallet.topup') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        เติมเงินเลย
                    </a>
                </div>
                @endforelse
            </div>

            @if($transactions->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        แสดง {{ $transactions->firstItem() ?? 0 }} - {{ $transactions->lastItem() ?? 0 }} จาก {{ $transactions->total() }} รายการ
                    </p>
                    <div class="flex items-center gap-2">
                        @if($transactions->onFirstPage())
                        <span class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 rounded-lg cursor-not-allowed">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </span>
                        @else
                        <a href="{{ $transactions->previousPageUrl() }}" class="px-4 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-purple-50 dark:hover:bg-purple-900/30 transition-colors duration-200 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                        @endif

                        <span class="px-4 py-2 bg-purple-600 text-white rounded-lg font-medium shadow-md">
                            {{ $transactions->currentPage() }}
                        </span>

                        @if($transactions->hasMorePages())
                        <a href="{{ $transactions->nextPageUrl() }}" class="px-4 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-purple-50 dark:hover:bg-purple-900/30 transition-colors duration-200 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                        @else
                        <span class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 rounded-lg cursor-not-allowed">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
