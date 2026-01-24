@extends($customerLayout ?? 'layouts.customer')

@section('title', $project->project_name)
@section('page-title', $project->project_name)
@section('page-description', 'ติดตามความคืบหน้าโครงการของคุณ')

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
<!-- Back Link -->
<a href="{{ route('customer.projects') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 mb-6 font-medium transition-colors">
    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
    </svg>
    กลับไปรายการโครงการ
</a>

<!-- Premium Project Header -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600 p-6 sm:p-8 shadow-2xl mb-6">
    <div class="absolute top-0 left-0 w-72 h-72 bg-violet-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
    <div class="absolute top-0 right-0 w-72 h-72 bg-blue-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-8 left-20 w-72 h-72 bg-indigo-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>

    <div class="relative">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ $project->project_name }}</h1>
                    @php
                        $statusGradients = [
                            'green' => 'bg-gradient-to-r from-green-400 to-emerald-500',
                            'blue' => 'bg-gradient-to-r from-blue-400 to-indigo-500',
                            'yellow' => 'bg-gradient-to-r from-yellow-400 to-amber-500',
                            'purple' => 'bg-gradient-to-r from-purple-400 to-violet-500',
                            'orange' => 'bg-gradient-to-r from-orange-400 to-red-500',
                            'red' => 'bg-gradient-to-r from-red-500 to-rose-600',
                        ];
                    @endphp
                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full text-white shadow {{ $statusGradients[$project->status_color] ?? 'bg-white/20' }}">
                        {{ $project->status_label }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-sm text-blue-100">
                    <span class="font-mono bg-white/20 px-2 py-0.5 rounded">{{ $project->project_number }}</span>
                    <span class="px-2 py-0.5 bg-white/20 rounded">{{ $project->type_label }}</span>
                </div>
            </div>
        </div>

        @if($project->project_description)
            <p class="text-blue-100 mb-6">{{ $project->project_description }}</p>
        @endif

        <!-- Progress Bar -->
        <div class="mb-6">
            <div class="flex justify-between text-sm text-white mb-2">
                <span class="font-medium">ความคืบหน้าโดยรวม</span>
                <span class="font-bold text-lg">{{ $project->progress_percent }}%</span>
            </div>
            <div class="w-full bg-white/20 rounded-full h-4 backdrop-blur-sm">
                <div class="h-4 rounded-full transition-all duration-500
                    @if($project->progress_percent >= 100) bg-gradient-to-r from-green-400 to-emerald-400
                    @elseif($project->progress_percent >= 50) bg-gradient-to-r from-blue-400 to-indigo-400
                    @else bg-gradient-to-r from-yellow-400 to-amber-400 @endif"
                    style="width: {{ $project->progress_percent }}%"></div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-white/10 backdrop-blur-sm rounded-xl">
                <p class="text-sm text-blue-200 mb-1">ฟีเจอร์เสร็จ</p>
                <p class="text-2xl font-bold text-white">{{ $project->completed_features_count }}/{{ $project->total_features_count }}</p>
            </div>
            <div class="text-center p-4 bg-white/10 backdrop-blur-sm rounded-xl">
                <p class="text-sm text-blue-200 mb-1">วันเริ่มงาน</p>
                <p class="text-lg font-semibold text-white">{{ $project->start_date?->format('d/m/Y') ?? '-' }}</p>
            </div>
            <div class="text-center p-4 {{ $project->isOverdue() ? 'bg-red-500/30' : 'bg-white/10' }} backdrop-blur-sm rounded-xl">
                <p class="text-sm {{ $project->isOverdue() ? 'text-red-200' : 'text-blue-200' }} mb-1">กำหนดส่ง</p>
                <p class="text-lg font-semibold text-white">
                    {{ $project->expected_end_date?->format('d/m/Y') ?? '-' }}
                    @if($project->isOverdue())
                        <span class="block text-xs text-red-200">(เกินกำหนด)</span>
                    @endif
                </p>
            </div>
            <div class="text-center p-4 bg-white/10 backdrop-blur-sm rounded-xl">
                <p class="text-sm text-blue-200 mb-1">มูลค่าโครงการ</p>
                <p class="text-lg font-semibold text-white">{{ number_format($project->total_price, 0) }} บาท</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Features -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                ฟีเจอร์/ไมล์สโตน
            </h2>

            @if($project->features->count() > 0)
                <div class="space-y-3">
                    @foreach($project->features as $feature)
                        @php
                            $featureStyles = [
                                'completed' => 'bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-green-200 dark:border-green-700',
                                'overdue' => 'bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-red-200 dark:border-red-700',
                                'default' => 'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600',
                            ];
                            $featureStyle = $feature->status === 'completed' ? $featureStyles['completed'] : ($feature->isOverdue() ? $featureStyles['overdue'] : $featureStyles['default']);
                        @endphp
                        <div class="border rounded-xl p-4 {{ $featureStyle }}">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 mt-0.5 text-xl">
                                    {{ $feature->status_icon }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium text-gray-900 dark:text-white {{ $feature->status === 'completed' ? 'line-through text-gray-500 dark:text-gray-400' : '' }}">
                                        {{ $feature->name }}
                                    </h4>
                                    @if($feature->description)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $feature->description }}</p>
                                    @endif
                                    <div class="flex flex-wrap items-center gap-3 mt-2 text-xs">
                                        @php
                                            $statusBadgeColors = [
                                                'green' => 'bg-gradient-to-r from-green-400 to-emerald-500 text-white',
                                                'blue' => 'bg-gradient-to-r from-blue-400 to-indigo-500 text-white',
                                                'red' => 'bg-gradient-to-r from-red-400 to-rose-500 text-white',
                                            ];
                                        @endphp
                                        <span class="inline-flex px-2 py-0.5 rounded-full font-semibold shadow {{ $statusBadgeColors[$feature->status_color] ?? 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300' }}">
                                            {{ $feature->status_label }}
                                        </span>
                                        @if($feature->due_date)
                                            <span class="{{ $feature->isOverdue() ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-500 dark:text-gray-400' }}">
                                                กำหนด: {{ $feature->due_date->format('d/m/Y') }}
                                            </span>
                                        @endif
                                        @if($feature->completed_at)
                                            <span class="text-green-600 dark:text-green-400">
                                                เสร็จเมื่อ: {{ $feature->completed_at->format('d/m/Y') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400">ยังไม่มีฟีเจอร์</p>
                </div>
            @endif
        </div>

        <!-- Progress Updates -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                รายงานความคืบหน้า
            </h2>

            @if($project->progress->count() > 0)
                <div class="space-y-4">
                    @foreach($project->progress as $progress)
                        @php
                            $progressColors = [
                                'milestone' => 'border-blue-300 dark:border-blue-600',
                                'issue' => 'border-red-300 dark:border-red-600',
                                'delivery' => 'border-purple-300 dark:border-purple-600',
                                'meeting' => 'border-yellow-300 dark:border-yellow-600',
                            ];
                            $dotColors = [
                                'milestone' => 'bg-gradient-to-r from-blue-400 to-indigo-500',
                                'issue' => 'bg-gradient-to-r from-red-400 to-rose-500',
                                'delivery' => 'bg-gradient-to-r from-purple-400 to-violet-500',
                                'meeting' => 'bg-gradient-to-r from-yellow-400 to-amber-500',
                            ];
                        @endphp
                        <div class="relative pl-6 pb-4 border-l-2 {{ $progressColors[$progress->type] ?? 'border-gray-200 dark:border-gray-600' }}">
                            <div class="absolute -left-2 top-0 w-4 h-4 rounded-full {{ $dotColors[$progress->type] ?? 'bg-gray-400 dark:bg-gray-500' }}"></div>

                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-lg">
                                        @switch($progress->type)
                                            @case('milestone') @break
                                            @case('issue') @break
                                            @case('delivery') @break
                                            @case('meeting') @break
                                            @case('change_request') @break
                                            @default
                                        @endswitch
                                    </span>
                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ $progress->title }}</h4>
                                </div>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">{{ $progress->description }}</p>
                                <div class="flex flex-wrap items-center gap-3 mt-3 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="px-2 py-0.5 bg-white dark:bg-gray-600 rounded border border-gray-200 dark:border-gray-500">{{ $progress->type_label }}</span>
                                    @if($progress->feature)
                                        <span class="text-indigo-500 dark:text-indigo-400">{{ $progress->feature->name }}</span>
                                    @endif
                                    <span>{{ $progress->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400">ยังไม่มีรายงานความคืบหน้า</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Team Members -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                ทีมผู้รับผิดชอบ
            </h3>

            @if($project->members->count() > 0)
                <div class="space-y-3">
                    @foreach($project->members as $member)
                        <div class="flex items-center gap-3 p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="w-10 h-10 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-xl flex items-center justify-center text-white font-semibold shadow">
                                {{ strtoupper(substr($member->name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 dark:text-white flex items-center gap-1">
                                    {{ $member->name }}
                                    @if($member->is_lead)
                                        <span class="text-yellow-500 text-xs" title="หัวหน้าโครงการ">(Lead)</span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $member->role_label }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-sm">ยังไม่ได้กำหนดทีมงาน</p>
            @endif
        </div>

        <!-- Timeline -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-100 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                ไทม์ไลน์
            </h3>

            @if($project->timeline->count() > 0)
                <div class="relative">
                    <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-600"></div>
                    <div class="space-y-4">
                        @foreach($project->timeline as $event)
                            <div class="relative pl-10">
                                <div class="absolute left-0 w-8 h-8 rounded-full flex items-center justify-center text-base shadow
                                    {{ $event->is_completed ? 'bg-gradient-to-br from-green-400 to-emerald-500' : ($event->isOverdue() ? 'bg-gradient-to-br from-red-400 to-rose-500' : 'bg-gray-100 dark:bg-gray-700') }}">
                                    {{ $event->type_icon }}
                                </div>
                                <div>
                                    <span class="font-medium text-sm {{ $event->is_completed ? 'text-green-600 dark:text-green-400' : ($event->isOverdue() ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white') }}">
                                        {{ $event->title }}
                                        @if($event->is_completed)
                                            <svg class="inline w-4 h-4 text-green-500 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                    </span>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $event->event_date->format('d/m/Y') }}</div>
                                    @if($event->description)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $event->description }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-sm">ยังไม่มีเหตุการณ์</p>
            @endif
        </div>

        <!-- Customer Notes -->
        @if($project->customer_notes)
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-700 rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-300 mb-2 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    หมายเหตุจากทีมงาน
                </h3>
                <p class="text-blue-800 dark:text-blue-200">{{ $project->customer_notes }}</p>
            </div>
        @endif

        <!-- Links -->
        @if($project->staging_url || $project->production_url)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </div>
                    ลิงก์
                </h3>
                <div class="space-y-2">
                    @if($project->staging_url)
                        <a href="{{ $project->staging_url }}" target="_blank"
                           class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            เว็บไซต์ทดสอบ (Staging)
                        </a>
                    @endif
                    @if($project->production_url)
                        <a href="{{ $project->production_url }}" target="_blank"
                           class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                            เว็บไซต์จริง (Production)
                        </a>
                    @endif
                </div>
            </div>
        @endif

        <!-- Need Help? -->
        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-2xl p-6 text-center border border-indigo-100 dark:border-indigo-800">
            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center mx-auto mb-4 shadow-lg">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h4 class="font-semibold text-gray-900 dark:text-white mb-1">มีคำถาม?</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">ติดต่อทีมงานได้ทันที</p>
            <a href="{{ route('customer.support.create') }}" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white text-sm font-semibold rounded-xl hover:from-indigo-600 hover:via-purple-600 hover:to-pink-600 transition-all shadow-lg">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
                ติดต่อซัพพอร์ต
            </a>
        </div>
    </div>
</div>
@endsection
