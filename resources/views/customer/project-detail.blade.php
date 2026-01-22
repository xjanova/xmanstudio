@extends('layouts.customer')

@section('title', $project->project_name)
@section('page-title', $project->project_name)
@section('page-description', 'ติดตามความคืบหน้าโครงการของคุณ')

@section('content')
<!-- Back Link -->
<a href="{{ route('customer.projects') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-primary-600 mb-6">
    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
    </svg>
    กลับไปรายการโครงการ
</a>

<!-- Project Header -->
<div class="bg-white rounded-xl shadow-sm p-6 sm:p-8 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $project->project_name }}</h1>
                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
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
            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
                <span class="font-mono bg-gray-100 px-2 py-0.5 rounded">{{ $project->project_number }}</span>
                <span class="px-2 py-0.5 bg-gray-100 rounded">{{ $project->type_label }}</span>
            </div>
        </div>
    </div>

    @if($project->project_description)
        <p class="text-gray-600 mb-6">{{ $project->project_description }}</p>
    @endif

    <!-- Progress Bar -->
    <div class="mb-6">
        <div class="flex justify-between text-sm text-gray-600 mb-2">
            <span class="font-medium">ความคืบหน้าโดยรวม</span>
            <span class="font-bold text-lg">{{ $project->progress_percent }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-4">
            <div class="h-4 rounded-full transition-all duration-500
                @if($project->progress_percent >= 100) bg-green-500
                @elseif($project->progress_percent >= 50) bg-blue-500
                @else bg-yellow-500 @endif"
                style="width: {{ $project->progress_percent }}%"></div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-6 border-t">
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-500 mb-1">ฟีเจอร์เสร็จ</p>
            <p class="text-2xl font-bold text-gray-900">{{ $project->completed_features_count }}/{{ $project->total_features_count }}</p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-500 mb-1">วันเริ่มงาน</p>
            <p class="text-lg font-semibold text-gray-900">{{ $project->start_date?->format('d/m/Y') ?? '-' }}</p>
        </div>
        <div class="text-center p-4 {{ $project->isOverdue() ? 'bg-red-50' : 'bg-gray-50' }} rounded-lg">
            <p class="text-sm {{ $project->isOverdue() ? 'text-red-500' : 'text-gray-500' }} mb-1">กำหนดส่ง</p>
            <p class="text-lg font-semibold {{ $project->isOverdue() ? 'text-red-600' : 'text-gray-900' }}">
                {{ $project->expected_end_date?->format('d/m/Y') ?? '-' }}
                @if($project->isOverdue())
                    <span class="block text-xs text-red-500">(เกินกำหนด)</span>
                @endif
            </p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-500 mb-1">มูลค่าโครงการ</p>
            <p class="text-lg font-semibold text-gray-900">{{ number_format($project->total_price, 0) }} บาท</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Features -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">ฟีเจอร์/ไมล์สโตน</h2>

            @if($project->features->count() > 0)
                <div class="space-y-3">
                    @foreach($project->features as $feature)
                        <div class="border rounded-lg p-4 {{ $feature->status === 'completed' ? 'bg-green-50 border-green-200' : ($feature->isOverdue() ? 'bg-red-50 border-red-200' : 'bg-gray-50') }}">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 mt-0.5 text-xl">
                                    {{ $feature->status_icon }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium text-gray-900 {{ $feature->status === 'completed' ? 'line-through text-gray-500' : '' }}">
                                        {{ $feature->name }}
                                    </h4>
                                    @if($feature->description)
                                        <p class="text-sm text-gray-600 mt-1">{{ $feature->description }}</p>
                                    @endif
                                    <div class="flex flex-wrap items-center gap-3 mt-2 text-xs text-gray-500">
                                        <span class="inline-flex px-2 py-0.5 rounded-full
                                            @switch($feature->status_color)
                                                @case('green') bg-green-100 text-green-700 @break
                                                @case('blue') bg-blue-100 text-blue-700 @break
                                                @case('red') bg-red-100 text-red-700 @break
                                                @default bg-gray-100 text-gray-700
                                            @endswitch">
                                            {{ $feature->status_label }}
                                        </span>
                                        @if($feature->due_date)
                                            <span class="{{ $feature->isOverdue() ? 'text-red-600 font-semibold' : '' }}">
                                                กำหนด: {{ $feature->due_date->format('d/m/Y') }}
                                            </span>
                                        @endif
                                        @if($feature->completed_at)
                                            <span class="text-green-600">
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
                <div class="text-center py-8 text-gray-500">
                    <p>ยังไม่มีฟีเจอร์</p>
                </div>
            @endif
        </div>

        <!-- Progress Updates -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">รายงานความคืบหน้า</h2>

            @if($project->progress->count() > 0)
                <div class="space-y-4">
                    @foreach($project->progress as $progress)
                        <div class="relative pl-6 pb-4 border-l-2
                            @switch($progress->type)
                                @case('milestone') border-blue-300 @break
                                @case('issue') border-red-300 @break
                                @case('delivery') border-purple-300 @break
                                @case('meeting') border-yellow-300 @break
                                @default border-gray-200
                            @endswitch">
                            <div class="absolute -left-2 top-0 w-4 h-4 rounded-full
                                @switch($progress->type)
                                    @case('milestone') bg-blue-500 @break
                                    @case('issue') bg-red-500 @break
                                    @case('delivery') bg-purple-500 @break
                                    @case('meeting') bg-yellow-500 @break
                                    @default bg-gray-400
                                @endswitch"></div>

                            <div class="bg-gray-50 rounded-lg p-4">
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
                                    <h4 class="font-medium text-gray-900">{{ $progress->title }}</h4>
                                </div>
                                <p class="text-gray-600 text-sm">{{ $progress->description }}</p>
                                <div class="flex flex-wrap items-center gap-3 mt-3 text-xs text-gray-500">
                                    <span class="px-2 py-0.5 bg-white rounded border">{{ $progress->type_label }}</span>
                                    @if($progress->feature)
                                        <span class="text-gray-400">{{ $progress->feature->name }}</span>
                                    @endif
                                    <span>{{ $progress->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <p>ยังไม่มีรายงานความคืบหน้า</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Team Members -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ทีมผู้รับผิดชอบ</h3>

            @if($project->members->count() > 0)
                <div class="space-y-3">
                    @foreach($project->members as $member)
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-semibold">
                                {{ strtoupper(substr($member->name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 flex items-center gap-1">
                                    {{ $member->name }}
                                    @if($member->is_lead)
                                        <span class="text-yellow-500 text-xs" title="หัวหน้าโครงการ">(Lead)</span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-500">{{ $member->role_label }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm">ยังไม่ได้กำหนดทีมงาน</p>
            @endif
        </div>

        <!-- Timeline -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ไทม์ไลน์</h3>

            @if($project->timeline->count() > 0)
                <div class="relative">
                    <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                    <div class="space-y-4">
                        @foreach($project->timeline as $event)
                            <div class="relative pl-10">
                                <div class="absolute left-0 w-8 h-8 rounded-full flex items-center justify-center text-base
                                    {{ $event->is_completed ? 'bg-green-100' : ($event->isOverdue() ? 'bg-red-100' : 'bg-gray-100') }}">
                                    {{ $event->type_icon }}
                                </div>
                                <div>
                                    <span class="font-medium text-sm {{ $event->is_completed ? 'text-green-600' : ($event->isOverdue() ? 'text-red-600' : 'text-gray-900') }}">
                                        {{ $event->title }}
                                        @if($event->is_completed)
                                            <svg class="inline w-4 h-4 text-green-500 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                    </span>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $event->event_date->format('d/m/Y') }}</div>
                                    @if($event->description)
                                        <p class="text-sm text-gray-600 mt-1">{{ $event->description }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="text-gray-500 text-sm">ยังไม่มีเหตุการณ์</p>
            @endif
        </div>

        <!-- Customer Notes -->
        @if($project->customer_notes)
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-blue-900 mb-2">หมายเหตุจากทีมงาน</h3>
                <p class="text-blue-800">{{ $project->customer_notes }}</p>
            </div>
        @endif

        <!-- Links -->
        @if($project->staging_url || $project->production_url)
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ลิงก์</h3>
                <div class="space-y-2">
                    @if($project->staging_url)
                        <a href="{{ $project->staging_url }}" target="_blank"
                           class="flex items-center gap-2 text-primary-600 hover:text-primary-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            เว็บไซต์ทดสอบ (Staging)
                        </a>
                    @endif
                    @if($project->production_url)
                        <a href="{{ $project->production_url }}" target="_blank"
                           class="flex items-center gap-2 text-primary-600 hover:text-primary-700">
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
        <div class="bg-gray-50 rounded-xl p-6 text-center">
            <svg class="w-10 h-10 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h4 class="font-medium text-gray-900 mb-1">มีคำถาม?</h4>
            <p class="text-sm text-gray-500 mb-3">ติดต่อทีมงานได้ทันที</p>
            <a href="{{ route('customer.support.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
                ติดต่อซัพพอร์ต
            </a>
        </div>
    </div>
</div>
@endsection
