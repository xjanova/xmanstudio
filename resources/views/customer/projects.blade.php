@extends($customerLayout ?? 'layouts.customer')

@section('title', 'โครงการของฉัน')
@section('page-title', 'โครงการของฉัน')
@section('page-description', 'ติดตามความคืบหน้าโครงการทั้งหมดของคุณ')

@section('content')
<!-- Premium Header Banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 p-6 sm:p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-blob"></div>
    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-purple-300/20 rounded-full blur-3xl animate-blob animation-delay-2000"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                โครงการของฉัน
            </h1>
            <p class="mt-2 text-white/80 text-sm sm:text-base">ติดตามความคืบหน้าและจัดการโครงการทั้งหมดของคุณ</p>
        </div>
        <a href="{{ route('support.index') }}" class="inline-flex items-center px-5 py-2.5 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all font-medium shadow-lg">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            ขอใบเสนอราคา
        </a>
    </div>
</div>

<!-- Stats Cards with Gradients -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-8">
    <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl p-5 sm:p-6 transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex-shrink-0 p-3 bg-gradient-to-br from-blue-400 to-indigo-600 rounded-xl shadow-lg group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div class="text-right">
                <p class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">โครงการทั้งหมด</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
            </div>
        </div>
    </div>

    <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl p-5 sm:p-6 transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex-shrink-0 p-3 bg-gradient-to-br from-emerald-400 to-green-600 rounded-xl shadow-lg group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div class="text-right">
                <p class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">กำลังดำเนินการ</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['active'] }}</p>
            </div>
        </div>
    </div>

    <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl p-5 sm:p-6 transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex-shrink-0 p-3 bg-gradient-to-br from-purple-400 to-violet-600 rounded-xl shadow-lg group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="text-right">
                <p class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">เสร็จสิ้น</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['completed'] }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Premium Filter -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4 sm:p-6 mb-6 border border-gray-100 dark:border-gray-700">
    <form action="{{ route('customer.projects') }}" method="GET" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
            <select name="status" onchange="this.form.submit()"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-purple-500 focus:border-purple-500 shadow-sm">
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
        <a href="{{ route('customer.projects.show', $project) }}" class="block group bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-5 sm:p-6 border border-gray-100 dark:border-gray-700 hover:-translate-y-1">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl shadow-lg flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $project->project_name }}</h3>
                        @php
                            $statusGradients = [
                                'green' => 'from-emerald-400 to-green-500',
                                'blue' => 'from-blue-400 to-indigo-500',
                                'yellow' => 'from-yellow-400 to-orange-400',
                                'purple' => 'from-purple-400 to-violet-500',
                                'orange' => 'from-orange-400 to-red-400',
                                'red' => 'from-red-400 to-rose-500',
                            ];
                        @endphp
                        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-gradient-to-r {{ $statusGradients[$project->status_color] ?? 'from-gray-400 to-gray-500' }} text-white shadow-sm">
                            {{ $project->status_label }}
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 dark:text-gray-400 ml-11">
                        <span class="font-mono text-xs px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">{{ $project->project_number }}</span>
                        <span class="px-2 py-0.5 bg-gradient-to-r from-purple-100 to-indigo-100 dark:from-purple-900/30 dark:to-indigo-900/30 text-purple-700 dark:text-purple-300 rounded-lg text-xs font-medium">{{ $project->type_label }}</span>
                        @if($project->project_lead)
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                {{ $project->project_lead->name }}
                            </span>
                        @endif
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-4 ml-11">
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1.5">
                            <span>ความคืบหน้า</span>
                            <span class="font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">{{ $project->progress_percent }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                            <div class="h-3 rounded-full transition-all duration-500 bg-gradient-to-r
                                @if($project->progress_percent >= 100) from-emerald-400 to-green-500
                                @elseif($project->progress_percent >= 50) from-blue-400 to-indigo-500
                                @else from-yellow-400 to-orange-400 @endif"
                                style="width: {{ $project->progress_percent }}%"></div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="flex items-center gap-6 mt-3 text-sm ml-11">
                        <span class="flex items-center text-gray-500 dark:text-gray-400">
                            <svg class="w-4 h-4 mr-1.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $project->completed_features_count }}</span>/<span>{{ $project->total_features_count }}</span>
                            <span class="ml-1">ฟีเจอร์</span>
                        </span>
                        @if($project->expected_end_date)
                            <span class="flex items-center {{ $project->isOverdue() ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-500 dark:text-gray-400' }}">
                                <svg class="w-4 h-4 mr-1.5 {{ $project->isOverdue() ? 'text-red-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                กำหนดส่ง: {{ $project->expected_end_date->format('d/m/Y') }}
                                @if($project->isOverdue())
                                    <span class="ml-1 px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full text-xs">(เกินกำหนด)</span>
                                @endif
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex-shrink-0">
                    <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-xl group-hover:bg-gradient-to-r group-hover:from-purple-500 group-hover:to-indigo-500 transition-all">
                        <svg class="w-6 h-6 text-gray-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </div>
        </a>
    @empty
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-12 text-center border border-gray-100 dark:border-gray-700">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-violet-500 to-purple-600 rounded-full mb-6 shadow-xl">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
            </div>
            <p class="text-lg font-bold text-gray-900 dark:text-white">ยังไม่มีโครงการ</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">เมื่อคุณมีโครงการ จะแสดงที่นี่</p>
            <a href="{{ route('support.index') }}" class="mt-6 inline-flex items-center px-6 py-3 bg-gradient-to-r from-violet-600 to-purple-600 text-white text-sm font-medium rounded-xl hover:from-violet-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
