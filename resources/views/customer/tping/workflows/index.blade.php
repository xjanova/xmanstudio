@extends($customerLayout ?? 'layouts.customer')

@section('title', 'Tping Workflows')
@section('page-title', 'Tping Workflows')
@section('page-description', 'จัดการ Workflow ที่ซิงค์จากแอพ Tping')

@section('content')
<!-- Tabs -->
<div class="flex gap-2 mb-6">
    <a href="{{ route('customer.tping.workflows.index') }}"
       class="px-5 py-2.5 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('customer.tping.workflows.*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-500/30' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
        Workflow
    </a>
    <a href="{{ route('customer.tping.data-profiles.index') }}"
       class="px-5 py-2.5 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('customer.tping.data-profiles.*') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-500/30' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
        Data Profile
    </a>
</div>

<!-- Header Banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 p-6 sm:p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-cyan-300/20 rounded-full blur-3xl"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                Tping Workflows
            </h1>
            <p class="mt-2 text-white/80 text-sm sm:text-base">จัดการ Workflow ที่ซิงค์จากแอพ Tping ของคุณ</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-xl text-white text-sm font-medium">
                <span class="opacity-80">รวม</span>
                <span class="ml-1 text-lg font-bold">{{ $stats['total'] }}</span>
                <span class="opacity-80 ml-1">workflow</span>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-center gap-3">
        <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
@endif

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-8">
    <div class="group bg-white rounded-2xl shadow-lg hover:shadow-xl p-5 sm:p-6 transition-all duration-300 hover:-translate-y-1 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Workflow ทั้งหมด</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1">{{ $stats['total'] }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-cyan-400 to-blue-600 rounded-xl shadow-lg group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="group bg-white rounded-2xl shadow-lg hover:shadow-xl p-5 sm:p-6 transition-all duration-300 hover:-translate-y-1 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">กำลังแชร์</p>
                <p class="text-2xl sm:text-3xl font-bold text-purple-600 mt-1">{{ $stats['shared'] }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-purple-400 to-violet-600 rounded-xl shadow-lg group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="group bg-white rounded-2xl shadow-lg hover:shadow-xl p-5 sm:p-6 transition-all duration-300 hover:-translate-y-1 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Data Profile</p>
                <p class="text-2xl sm:text-3xl font-bold text-emerald-600 mt-1">{{ $stats['profiles'] }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-emerald-400 to-green-600 rounded-xl shadow-lg group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl shadow-lg p-4 sm:p-6 mb-6 border border-gray-100">
    <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหา workflow..."
                   class="w-full rounded-xl border-gray-200 shadow-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 text-sm">
        </div>
        @if(count($apps) > 0)
        <select name="app" class="rounded-xl border-gray-200 shadow-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 text-sm">
            <option value="">ทุกแอพ</option>
            @foreach($apps as $pkg => $appName)
                <option value="{{ $pkg }}" {{ request('app') === $pkg ? 'selected' : '' }}>{{ $appName ?: $pkg }}</option>
            @endforeach
        </select>
        @endif
        <button type="submit" class="px-6 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 text-white rounded-xl text-sm font-medium hover:shadow-lg transition-all">
            ค้นหา
        </button>
        @if(request('search') || request('app'))
        <a href="{{ route('customer.tping.workflows.index') }}" class="px-6 py-2 bg-gray-100 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-200 transition-all text-center">
            ล้าง
        </a>
        @endif
    </form>
</div>

<!-- Workflow Table -->
<div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
    @if($workflows->isEmpty())
        <div class="p-12 text-center">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <p class="text-gray-500 text-lg font-medium">ยังไม่มี Workflow</p>
            <p class="text-gray-400 text-sm mt-1">ซิงค์ Workflow จากแอพ Tping เพื่อดูที่นี่</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">ชื่อ</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">แอพเป้าหมาย</th>
                        <th class="px-6 py-4 text-center font-semibold text-gray-600">ขั้นตอน</th>
                        <th class="px-6 py-4 text-center font-semibold text-gray-600">แชร์</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">อัปเดต</th>
                        <th class="px-6 py-4 text-center font-semibold text-gray-600">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($workflows as $wf)
                        @php
                            $stepCount = count(json_decode($wf->steps_json, true) ?? []);
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <a href="{{ route('customer.tping.workflows.show', $wf) }}" class="font-medium text-gray-900 hover:text-cyan-600 transition-colors">
                                    {{ $wf->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ $wf->target_app_name ?: $wf->target_app_package ?: '-' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                    {{ $stepCount }} steps
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($wf->share_token)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">แชร์อยู่</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs">
                                {{ $wf->updated_at->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('customer.tping.workflows.show', $wf) }}" class="p-2 text-gray-400 hover:text-cyan-600 rounded-lg hover:bg-cyan-50 transition-all" title="ดูรายละเอียด">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    <a href="{{ route('customer.tping.workflows.edit', $wf) }}" class="p-2 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition-all" title="แก้ไข">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <form method="POST" action="{{ route('customer.tping.workflows.destroy', $wf) }}" onsubmit="return confirm('ลบ workflow นี้?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-all" title="ลบ">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($workflows->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $workflows->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
