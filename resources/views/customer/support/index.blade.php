@extends($customerLayout ?? 'layouts.customer')

@section('title', 'Support Tickets')
@section('page-title', 'Support Tickets')

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
<div class="space-y-6">
    <!-- Premium Header Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 p-8 shadow-2xl">
        <div class="absolute top-0 left-0 w-72 h-72 bg-pink-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-indigo-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-purple-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>

        <div class="relative">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                        </div>
                        <h1 class="text-3xl font-bold text-white">Support Tickets</h1>
                    </div>
                    <p class="text-purple-100 text-lg">จัดการ Ticket และติดตามสถานะการช่วยเหลือ</p>
                </div>
                <a href="{{ route('customer.support.create') }}"
                   class="inline-flex items-center px-6 py-3 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all font-semibold shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    สร้าง Ticket ใหม่
                </a>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-5 border border-gray-100 dark:border-gray-700">
        <form action="{{ route('customer.support.index') }}" method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหาหมายเลข Ticket หรือหัวข้อ..."
                       class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500 transition-all">
            </div>
            <div class="w-40">
                <select name="status" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500 transition-all">
                    <option value="">สถานะทั้งหมด</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>เปิดอยู่</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>ปิดแล้ว</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <select name="category" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500 transition-all">
                    <option value="">หมวดหมู่ทั้งหมด</option>
                    @foreach($categories as $value => $label)
                        <option value="{{ $value }}" {{ request('category') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl hover:from-indigo-600 hover:to-purple-700 font-medium shadow-lg transition-all">
                ค้นหา
            </button>
        </form>
    </div>

    {{-- Tickets List --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
        @forelse($tickets as $ticket)
        <a href="{{ route('customer.support.show', $ticket) }}"
           class="block border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors {{ $loop->last ? 'border-b-0' : '' }}">
            <div class="p-5 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    {{-- Status Badge --}}
                    <div class="flex-shrink-0">
                        @php
                            $statusColors = [
                                'open' => 'bg-gradient-to-r from-blue-400 to-indigo-500 text-white',
                                'in_progress' => 'bg-gradient-to-r from-yellow-400 to-amber-500 text-white',
                                'waiting_reply' => 'bg-gradient-to-r from-orange-400 to-red-500 text-white',
                                'resolved' => 'bg-gradient-to-r from-green-400 to-emerald-500 text-white',
                                'closed' => 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold shadow {{ $statusColors[$ticket->status] ?? 'bg-gray-200 text-gray-700' }}">
                            {{ $ticket->status_label }}
                        </span>
                    </div>

                    {{-- Ticket Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-mono text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ $ticket->ticket_number }}</span>
                            @if($ticket->priority === 'urgent')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gradient-to-r from-red-500 to-rose-600 text-white shadow">
                                    เร่งด่วน
                                </span>
                            @elseif($ticket->priority === 'high')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gradient-to-r from-orange-400 to-amber-500 text-white shadow">
                                    สำคัญ
                                </span>
                            @endif
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mt-1 truncate">{{ $ticket->subject }}</h3>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-sm text-gray-500 dark:text-gray-400">
                            <span>{{ $ticket->category_label }}</span>
                            <span>&bull;</span>
                            <span>{{ $ticket->created_at->diffForHumans() }}</span>
                            @if($ticket->replies_count > 0)
                                <span>&bull;</span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    {{ $ticket->replies_count }} ข้อความ
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Arrow --}}
                    <div class="flex-shrink-0 text-gray-400 dark:text-gray-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </div>
        </a>
        @empty
        <div class="p-12 text-center">
            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 flex items-center justify-center mx-auto mb-4">
                <svg class="h-10 w-10 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ยังไม่มี Support Ticket</h3>
            <p class="mt-2 text-gray-500 dark:text-gray-400">มีคำถามหรือต้องการความช่วยเหลือ? สร้าง Ticket ใหม่เลย</p>
            <div class="mt-6">
                <a href="{{ route('customer.support.create') }}"
                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white rounded-xl hover:from-indigo-600 hover:via-purple-600 hover:to-pink-600 font-semibold shadow-lg transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    สร้าง Ticket ใหม่
                </a>
            </div>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($tickets->hasPages())
    <div class="mt-4">
        {{ $tickets->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
