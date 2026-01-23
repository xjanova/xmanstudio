@extends($adminLayout ?? 'layouts.admin')

@section('title', 'จัดการโครงการ')
@section('page-title', 'จัดการโครงการ')

@section('content')
<!-- Success/Error Messages -->
@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
        {{ session('error') }}
    </div>
@endif

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">ทั้งหมด</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">กำลังดำเนินการ</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['active'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">เสร็จสิ้น</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['completed'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">เกินกำหนด</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['overdue'] }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters & Actions -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex flex-wrap gap-4 justify-between items-center">
        <form action="{{ route('admin.projects.index') }}" method="GET" class="flex flex-wrap gap-4 flex-1">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                       placeholder="ค้นหาโครงการ...">
            </div>
            <div>
                <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    <option value="">ทุกสถานะ</option>
                    @foreach(\App\Models\ProjectOrder::STATUS_LABELS as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="type" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    <option value="">ทุกประเภท</option>
                    @foreach(\App\Models\ProjectOrder::TYPE_LABELS as $value => $label)
                        <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                ค้นหา
            </button>
        </form>
        <a href="{{ route('admin.projects.create') }}"
           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            + สร้างโครงการ
        </a>
    </div>
</div>

<!-- Projects Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">โครงการ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ลูกค้า</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ความคืบหน้า</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">กำหนดส่ง</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การดำเนินการ</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($projects as $project)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $project->project_name }}</div>
                            <div class="text-xs text-gray-500">{{ $project->project_number }}</div>
                            <span class="inline-flex px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-600 mt-1">
                                {{ $project->type_label }}
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-semibold text-sm">
                                {{ strtoupper(substr($project->user->name ?? 'U', 0, 1)) }}
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">{{ $project->user->name ?? '-' }}</div>
                                @if($project->project_lead)
                                    <div class="text-xs text-gray-500">หัวหน้า: {{ $project->project_lead->name }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="w-32">
                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                <span>{{ $project->progress_percent }}%</span>
                                <span>{{ $project->completed_features_count }}/{{ $project->total_features_count }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all duration-300
                                    @if($project->progress_percent >= 100) bg-green-500
                                    @elseif($project->progress_percent >= 50) bg-blue-500
                                    @else bg-yellow-500 @endif"
                                    style="width: {{ $project->progress_percent }}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($project->expected_end_date)
                            <div class="{{ $project->isOverdue() ? 'text-red-600 font-semibold' : 'text-gray-900' }}">
                                {{ $project->expected_end_date->format('d/m/Y') }}
                            </div>
                            @if($project->isOverdue())
                                <div class="text-xs text-red-500">เกินกำหนด {{ $project->expected_end_date->diffInDays(now()) }} วัน</div>
                            @endif
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                            @switch($project->status_color)
                                @case('green') bg-green-100 text-green-800 @break
                                @case('blue') bg-blue-100 text-blue-800 @break
                                @case('yellow') bg-yellow-100 text-yellow-800 @break
                                @case('purple') bg-purple-100 text-purple-800 @break
                                @case('orange') bg-orange-100 text-orange-800 @break
                                @case('red') bg-red-100 text-red-800 @break
                                @default bg-gray-100 text-gray-800
                            @endswitch">
                            {{ $project->status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex items-center space-x-1">
                            <!-- View -->
                            <a href="{{ route('admin.projects.show', $project) }}"
                               class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition"
                               title="ดูรายละเอียด">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>

                            <!-- Edit -->
                            <a href="{{ route('admin.projects.edit', $project) }}"
                               class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                               title="แก้ไข">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>

                            <!-- Delete -->
                            <form action="{{ route('admin.projects.destroy', $project) }}" method="POST" class="inline"
                                  onsubmit="return confirm('ยืนยันการลบโครงการนี้?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="p-2 text-red-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                        title="ลบ">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-gray-500">ไม่พบโครงการ</p>
                        <a href="{{ route('admin.projects.create') }}" class="mt-2 inline-block text-primary-600 hover:underline">
                            + สร้างโครงการใหม่
                        </a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($projects->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $projects->links() }}
        </div>
    @endif
</div>
@endsection
