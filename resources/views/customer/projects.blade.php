@extends('layouts.customer')

@section('title', 'โครงการของฉัน')
@section('page-title', 'โครงการของฉัน')
@section('page-description', 'ติดตามความคืบหน้าโครงการทั้งหมดของคุณ')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-5 sm:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div class="flex-shrink-0 p-3 bg-blue-100 rounded-xl">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div class="text-right">
                <p class="text-xs sm:text-sm font-medium text-gray-500">โครงการทั้งหมด</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $stats['total'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5 sm:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div class="flex-shrink-0 p-3 bg-green-100 rounded-xl">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div class="text-right">
                <p class="text-xs sm:text-sm font-medium text-gray-500">กำลังดำเนินการ</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $stats['active'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5 sm:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div class="flex-shrink-0 p-3 bg-purple-100 rounded-xl">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="text-right">
                <p class="text-xs sm:text-sm font-medium text-gray-500">เสร็จสิ้น</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $stats['completed'] }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 mb-6">
    <form action="{{ route('customer.projects') }}" method="GET" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
            <select name="status" onchange="this.form.submit()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                <option value="all">ทุกสถานะ</option>
                @foreach(\App\Models\ProjectOrder::STATUS_LABELS as $value => $label)
                    <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>
</div>

<!-- Projects List -->
<div class="space-y-4">
    @forelse($projects as $project)
        <a href="{{ route('customer.projects.show', $project) }}" class="block bg-white rounded-xl shadow-sm hover:shadow-md transition-all p-5 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $project->project_name }}</h3>
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
                    </div>

                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                        <span class="font-mono text-xs">{{ $project->project_number }}</span>
                        <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded">{{ $project->type_label }}</span>
                        @if($project->project_lead)
                            <span>หัวหน้า: {{ $project->project_lead->name }}</span>
                        @endif
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-4">
                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                            <span>ความคืบหน้า</span>
                            <span class="font-semibold">{{ $project->progress_percent }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="h-2.5 rounded-full transition-all duration-500
                                @if($project->progress_percent >= 100) bg-green-500
                                @elseif($project->progress_percent >= 50) bg-blue-500
                                @else bg-yellow-500 @endif"
                                style="width: {{ $project->progress_percent }}%"></div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="flex items-center gap-6 mt-3 text-sm">
                        <span class="text-gray-500">
                            <span class="font-medium text-gray-900">{{ $project->completed_features_count }}</span>/{{ $project->total_features_count }} ฟีเจอร์
                        </span>
                        @if($project->expected_end_date)
                            <span class="{{ $project->isOverdue() ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                                กำหนดส่ง: {{ $project->expected_end_date->format('d/m/Y') }}
                                @if($project->isOverdue())
                                    (เกินกำหนด)
                                @endif
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </div>
        </a>
    @empty
        <div class="bg-white rounded-xl shadow-sm p-12 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
            </div>
            <p class="text-gray-600 font-medium">ยังไม่มีโครงการ</p>
            <p class="text-sm text-gray-500 mt-1">เมื่อคุณมีโครงการ จะแสดงที่นี่</p>
            <a href="{{ route('support.index') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                ขอใบเสนอราคา
            </a>
        </div>
    @endforelse
</div>

@if($projects->hasPages())
    <div class="mt-6">
        {{ $projects->links() }}
    </div>
@endif
@endsection
