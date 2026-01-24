@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Support Tickets')
@section('page-title', 'จัดการ Support Tickets')

@section('content')
<div class="space-y-6">
    <!-- Premium Header Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-rose-600 via-pink-600 to-fuchsia-600 p-6 sm:p-8 shadow-xl">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/5 rounded-full blur-3xl animate-blob"></div>
        <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-rose-400/10 rounded-full blur-3xl animate-blob animation-delay-2000"></div>
        <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
                    <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    Support Tickets
                </h1>
                <p class="mt-2 text-white/80 text-sm sm:text-base">จัดการและติดตาม Support Tickets ทั้งหมด</p>
            </div>
        </div>
    </div>

    <!-- Premium Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-400/10 to-indigo-600/10"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">เปิดใหม่</h3>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['open'] }}</p>
            </div>
        </div>

        <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="absolute inset-0 bg-gradient-to-br from-amber-400/10 to-yellow-600/10"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-400 to-yellow-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">กำลังดำเนินการ</h3>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['in_progress'] }}</p>
            </div>
        </div>

        <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="absolute inset-0 bg-gradient-to-br from-gray-400/10 to-slate-600/10"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-gray-400 to-slate-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">ยังไม่มอบหมาย</h3>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['unassigned'] }}</p>
            </div>
        </div>

        <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="absolute inset-0 bg-gradient-to-br from-red-400/10 to-rose-600/10"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-red-400 to-rose-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">เร่งด่วน</h3>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['urgent'] }}</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
        <form action="{{ route('admin.support.index') }}" method="GET" class="flex flex-wrap gap-4 items-center">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหาหมายเลข, หัวข้อ, ชื่อ, อีเมล..."
                       class="w-full px-4 py-2.5 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-rose-500 focus:ring-rose-500">
            </div>
            <select name="status" class="px-4 py-2.5 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-rose-500 focus:ring-rose-500">
                <option value="">สถานะทั้งหมด</option>
                <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>เปิดอยู่</option>
                <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>ปิดแล้ว</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="priority" class="px-4 py-2.5 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-rose-500 focus:ring-rose-500">
                <option value="">Priority ทั้งหมด</option>
                @foreach($priorities as $value => $label)
                    <option value="{{ $value }}" {{ request('priority') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="category" class="px-4 py-2.5 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-rose-500 focus:ring-rose-500">
                <option value="">หมวดหมู่ทั้งหมด</option>
                @foreach($categories as $value => $label)
                    <option value="{{ $value }}" {{ request('category') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="assigned" class="px-4 py-2.5 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-rose-500 focus:ring-rose-500">
                <option value="">มอบหมายทั้งหมด</option>
                <option value="me" {{ request('assigned') === 'me' ? 'selected' : '' }}>ของฉัน</option>
                <option value="unassigned" {{ request('assigned') === 'unassigned' ? 'selected' : '' }}>ยังไม่มอบหมาย</option>
                @foreach($staff as $user)
                    <option value="{{ $user->id }}" {{ request('assigned') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-rose-500 to-pink-600 text-white rounded-xl hover:from-rose-600 hover:to-pink-700 transition-all font-medium shadow-lg hover:shadow-xl transform hover:scale-105">
                ค้นหา
            </button>
            @if(request()->hasAny(['search', 'status', 'priority', 'category', 'assigned']))
                <a href="{{ route('admin.support.index') }}" class="px-4 py-2.5 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
                    ล้าง
                </a>
            @endif
        </form>
    </div>

    <!-- Tickets Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-750">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Ticket</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ผู้ส่ง</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Priority</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">มอบหมาย</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">อัพเดทล่าสุด</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($tickets as $ticket)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 cursor-pointer transition-colors" onclick="window.location='{{ route('admin.support.show', $ticket) }}'">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-400 to-pink-600 flex items-center justify-center mr-4 shadow-md">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $ticket->ticket_number }}</div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white truncate max-w-xs">{{ $ticket->subject }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $ticket->category_label }} &bull; {{ $ticket->replies_count }} ข้อความ</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $ticket->name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $ticket->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusGradients = [
                                    'open' => 'bg-gradient-to-r from-blue-400 to-indigo-500 text-white',
                                    'in_progress' => 'bg-gradient-to-r from-amber-400 to-yellow-500 text-white',
                                    'waiting_reply' => 'bg-gradient-to-r from-orange-400 to-red-500 text-white',
                                    'resolved' => 'bg-gradient-to-r from-green-400 to-emerald-500 text-white',
                                    'closed' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
                                ];
                            @endphp
                            <span class="inline-flex px-3 py-1.5 rounded-full text-xs font-semibold shadow-sm {{ $statusGradients[$ticket->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $ticket->status_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $priorityGradients = [
                                    'low' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
                                    'medium' => 'bg-gradient-to-r from-blue-400 to-indigo-500 text-white',
                                    'high' => 'bg-gradient-to-r from-orange-400 to-amber-500 text-white',
                                    'urgent' => 'bg-gradient-to-r from-red-400 to-rose-500 text-white',
                                ];
                            @endphp
                            <span class="inline-flex px-3 py-1.5 rounded-full text-xs font-semibold shadow-sm {{ $priorityGradients[$ticket->priority] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $ticket->priority_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            @if($ticket->assignedTo)
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                                    {{ $ticket->assignedTo->name }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $ticket->last_reply_at ? $ticket->last_reply_at->diffForHumans() : $ticket->created_at->diffForHumans() }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-rose-400 to-pink-600 rounded-full mb-4 shadow-xl">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">ไม่พบ Support Ticket</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ยังไม่มี Ticket ในระบบ</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
            {{ $tickets->withQueryString()->links() }}
        </div>
        @endif
    </div>
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
