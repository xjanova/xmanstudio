@extends($adminLayout ?? 'layouts.admin')

@section('title', 'แผนเนื้อหาอัตโนมัติ')
@section('page-title', 'Content Plans')

@section('content')
{{-- Header --}}
<div class="bg-gradient-to-r from-green-600 to-teal-800 rounded-lg shadow-lg p-6 mb-6 text-white">
    <div class="flex flex-col md:flex-row items-center gap-6">
        <div class="w-20 h-20 rounded-full bg-white/20 flex items-center justify-center">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
        </div>
        <div class="text-center md:text-left flex-1">
            <h2 class="text-2xl font-bold">แผนเนื้อหาอัตโนมัติ</h2>
            <p class="text-green-200 text-sm">กำหนดแผนสร้างวิดีโอ AI อัตโนมัติ -- ระบบจะสร้างโปรเจกต์ตามตารางที่ตั้งไว้</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap justify-center">
            <a href="{{ route('admin.metal-x.pipeline.index') }}" class="px-4 py-2 bg-white/20 text-white rounded-lg hover:bg-white/30 text-sm font-medium">
                <svg class="w-4 h-4 inline -mt-0.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                Pipeline
            </a>
            <a href="{{ route('admin.metal-x.content-plans.create') }}" class="px-6 py-2 bg-white text-teal-700 font-bold rounded-lg hover:bg-green-50 flex items-center text-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                สร้างแผนใหม่
            </a>
        </div>
    </div>
</div>

{{-- Flash Messages --}}
@if(session('success'))
    <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    </div>
@endif
@if(session('error'))
    <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
        </div>
    </div>
@endif

{{-- Stats Cards --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">แผนทั้งหมด</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['total_plans'] }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-xs text-green-600 dark:text-green-400 uppercase tracking-wider">เปิดใช้งาน</p>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $stats['active_plans'] }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-xs text-blue-600 dark:text-blue-400 uppercase tracking-wider">สร้างแล้วทั้งหมด</p>
        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $stats['total_generated'] }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-xs text-purple-600 dark:text-purple-400 uppercase tracking-wider">สื่อในคลัง</p>
        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ $stats['media_count'] }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-xs text-pink-600 dark:text-pink-400 uppercase tracking-wider">เพลงในคลัง</p>
        <p class="text-2xl font-bold text-pink-600 dark:text-pink-400 mt-1">{{ $stats['music_count'] }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-xs text-yellow-600 dark:text-yellow-400 uppercase tracking-wider">ใน Pipeline</p>
        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ $stats['projects_in_pipeline'] }}</p>
    </div>
</div>

{{-- Content Plans Table --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    @if($plans->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">ชื่อแผน</th>
                        <th class="px-4 py-3">ช่อง</th>
                        <th class="px-4 py-3">Template</th>
                        <th class="px-4 py-3">ความถี่</th>
                        <th class="px-4 py-3">สร้างถัดไป</th>
                        <th class="px-4 py-3 text-center">สร้างแล้ว</th>
                        <th class="px-4 py-3 text-center">คิว</th>
                        <th class="px-4 py-3 text-center">สถานะ</th>
                        <th class="px-4 py-3 text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($plans as $plan)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                            {{-- Name --}}
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $plan->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ Str::limit($plan->topic_prompt, 60) }}</div>
                            </td>
                            {{-- Channel --}}
                            <td class="px-4 py-3">
                                <span class="text-gray-700 dark:text-gray-300">{{ $plan->channel->name ?? '-' }}</span>
                            </td>
                            {{-- Template --}}
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                                    {{ \App\Models\MetalXVideoProject::TEMPLATES[$plan->template] ?? $plan->template }}
                                </span>
                            </td>
                            {{-- Frequency --}}
                            <td class="px-4 py-3">
                                <span class="text-gray-700 dark:text-gray-300">{{ $plan->frequency_label }}</span>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $plan->preferred_days_label }}</div>
                            </td>
                            {{-- Next Generation --}}
                            <td class="px-4 py-3">
                                @if($plan->next_generation_at)
                                    <span class="text-gray-700 dark:text-gray-300" title="{{ $plan->next_generation_at->format('d/m/Y H:i') }}">
                                        {{ $plan->next_generation_at->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            {{-- Total Generated --}}
                            <td class="px-4 py-3 text-center">
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $plan->total_generated ?? 0 }}</span>
                            </td>
                            {{-- Queue --}}
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ ($plan->active_projects_count ?? 0) >= $plan->max_queue_size ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">
                                    {{ $plan->active_projects_count ?? 0 }}/{{ $plan->max_queue_size }}
                                </span>
                            </td>
                            {{-- Status Toggle --}}
                            <td class="px-4 py-3 text-center">
                                <form method="POST" action="{{ route('admin.metal-x.content-plans.toggle', $plan) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 {{ $plan->is_enabled ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}" title="{{ $plan->is_enabled ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform {{ $plan->is_enabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                    </button>
                                </form>
                            </td>
                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- Edit --}}
                                    <a href="{{ route('admin.metal-x.content-plans.edit', $plan) }}" class="p-1.5 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700" title="แก้ไข">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    {{-- Generate Now --}}
                                    @if($plan->is_enabled)
                                        <form method="POST" action="{{ route('admin.metal-x.content-plans.generate-now', $plan) }}" class="inline" onsubmit="return confirm('สร้างโปรเจกต์จากแผนนี้ตอนนี้เลย?')">
                                            @csrf
                                            <button type="submit" class="p-1.5 text-gray-400 hover:text-green-600 dark:hover:text-green-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700" title="สร้างตอนนี้">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('admin.metal-x.content-plans.destroy', $plan) }}" class="inline" onsubmit="return confirm('ลบแผน &quot;{{ $plan->name }}&quot; หรือไม่?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700" title="ลบ">
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

        @if($plans->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                {{ $plans->withQueryString()->links() }}
            </div>
        @endif
    @else
        {{-- Empty State --}}
        <div class="p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <p class="text-lg font-medium text-gray-500 dark:text-gray-400">ยังไม่มีแผนเนื้อหา</p>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1 mb-4">สร้างแผนเพื่อให้ระบบสร้างวิดีโออัตโนมัติตามตารางที่กำหนด</p>
            <a href="{{ route('admin.metal-x.content-plans.create') }}" class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 text-sm font-medium">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                สร้างแผนเนื้อหาแรก
            </a>
        </div>
    @endif
</div>
@endsection
