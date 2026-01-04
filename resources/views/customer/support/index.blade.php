@extends('layouts.customer')

@section('title', 'Support Tickets')
@section('page-title', 'Support Tickets')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <p class="text-gray-600">จัดการ Ticket และติดตามสถานะการช่วยเหลือ</p>
        </div>
        <a href="{{ route('customer.support.create') }}"
           class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            สร้าง Ticket ใหม่
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow p-4">
        <form action="{{ route('customer.support.index') }}" method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหาหมายเลข Ticket หรือหัวข้อ..."
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
            </div>
            <div class="w-40">
                <select name="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">สถานะทั้งหมด</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>เปิดอยู่</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>ปิดแล้ว</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <select name="category" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">หมวดหมู่ทั้งหมด</option>
                    @foreach($categories as $value => $label)
                        <option value="{{ $value }}" {{ request('category') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                ค้นหา
            </button>
        </form>
    </div>

    {{-- Tickets List --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @forelse($tickets as $ticket)
        <a href="{{ route('customer.support.show', $ticket) }}"
           class="block border-b border-gray-200 hover:bg-gray-50 transition-colors {{ $loop->last ? 'border-b-0' : '' }}">
            <div class="p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    {{-- Status Badge --}}
                    <div class="flex-shrink-0">
                        @php
                            $statusColors = [
                                'open' => 'bg-blue-100 text-blue-800',
                                'in_progress' => 'bg-yellow-100 text-yellow-800',
                                'waiting_reply' => 'bg-orange-100 text-orange-800',
                                'resolved' => 'bg-green-100 text-green-800',
                                'closed' => 'bg-gray-100 text-gray-800',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$ticket->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $ticket->status_label }}
                        </span>
                    </div>

                    {{-- Ticket Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-mono text-gray-500">{{ $ticket->ticket_number }}</span>
                            @if($ticket->priority === 'urgent')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                    เร่งด่วน
                                </span>
                            @elseif($ticket->priority === 'high')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">
                                    สำคัญ
                                </span>
                            @endif
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mt-1 truncate">{{ $ticket->subject }}</h3>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-sm text-gray-500">
                            <span>{{ $ticket->category_label }}</span>
                            <span>&bull;</span>
                            <span>{{ $ticket->created_at->diffForHumans() }}</span>
                            @if($ticket->replies_count > 0)
                                <span>&bull;</span>
                                <span>{{ $ticket->replies_count }} ข้อความ</span>
                            @endif
                        </div>
                    </div>

                    {{-- Arrow --}}
                    <div class="flex-shrink-0 text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </div>
        </a>
        @empty
        <div class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">ยังไม่มี Support Ticket</h3>
            <p class="mt-2 text-gray-500">มีคำถามหรือต้องการความช่วยเหลือ? สร้าง Ticket ใหม่เลย</p>
            <div class="mt-6">
                <a href="{{ route('customer.support.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
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
