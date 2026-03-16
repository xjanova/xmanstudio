@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Pipeline Dashboard')
@section('page-title', 'Video Pipeline')

@section('content')
@php
    $isPremium = ($adminLayout ?? '') === 'layouts.admin-premium';
    $pipelineSteps = [
        'draft' => ['label' => 'แบบร่าง', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
        'generating_music' => ['label' => 'สร้างเพลง', 'icon' => 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3'],
        'music_ready' => ['label' => 'เพลงพร้อม', 'icon' => 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3'],
        'rendering' => ['label' => 'เรนเดอร์', 'icon' => 'M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z'],
        'rendered' => ['label' => 'วิดีโอพร้อม', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
        'uploading' => ['label' => 'อัปโหลด', 'icon' => 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12'],
        'uploaded' => ['label' => 'เสร็จสิ้น', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
    ];
    $stepKeys = array_keys($pipelineSteps);
@endphp

<div x-data="pipelineDashboard()" x-init="startPolling()">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-cyan-600 via-purple-600 to-pink-600 rounded-xl shadow-lg p-6 mb-6 text-white">
        <div class="flex flex-col md:flex-row items-center gap-6">
            <div class="w-20 h-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
            </div>
            <div class="text-center md:text-left flex-1">
                <h2 class="text-2xl font-bold">Video Pipeline Control</h2>
                <p class="text-white/80 text-sm">ควบคุมและติดตามกระบวนการสร้างวิดีโอทั้งหมดแบบ Real-time</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 bg-white/20 backdrop-blur-sm rounded-lg px-3 py-2">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75" x-show="polling"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500" x-show="polling"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-gray-400" x-show="!polling"></span>
                    </span>
                    <span class="text-xs" x-text="polling ? 'Live' : 'Paused'"></span>
                </div>
                <button @click="togglePolling()" class="px-3 py-2 bg-white/20 backdrop-blur-sm rounded-lg hover:bg-white/30 text-sm">
                    <span x-text="polling ? 'หยุด' : 'เริ่ม'"></span> Auto-refresh
                </button>
                <a href="{{ route('admin.metal-x.projects.create') }}" class="px-4 py-2 bg-white text-purple-700 font-bold rounded-lg hover:bg-purple-50 text-sm">
                    + สร้างโปรเจกต์
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">ทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['total'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-yellow-200 dark:border-yellow-800 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-yellow-600 dark:text-yellow-400 uppercase tracking-wider">กำลังทำงาน</p>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ $stats['active'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-blue-200 dark:border-blue-800 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-blue-600 dark:text-blue-400 uppercase tracking-wider">รอดำเนินการ</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $stats['waiting'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-green-200 dark:border-green-800 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-green-600 dark:text-green-400 uppercase tracking-wider">สำเร็จ</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $stats['completed'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-red-200 dark:border-red-800 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-red-600 dark:text-red-400 uppercase tracking-wider">ล้มเหลว</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $stats['failed'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Pipeline Legend --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mb-6">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pipeline Steps:</span>
            @foreach($pipelineSteps as $key => $step)
                <div class="flex items-center gap-1.5">
                    <div class="w-5 h-5 rounded-full flex items-center justify-center
                        {{ in_array($key, ['uploaded', 'published']) ? 'bg-green-500' : (in_array($key, ['generating_music', 'rendering', 'uploading']) ? 'bg-yellow-500' : 'bg-gray-300 dark:bg-gray-600') }}">
                        <span class="text-[9px] text-white font-bold">{{ $loop->iteration }}</span>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ $step['label'] }}</span>
                </div>
                @if(!$loop->last)
                    <svg class="w-3 h-3 text-gray-300 dark:text-gray-600 -mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Project Pipeline Cards --}}
    <div class="space-y-4">
        @forelse($projects as $project)
            @php
                $currentIdx = array_search($project->status, $stepKeys);
                if ($currentIdx === false) $currentIdx = ($project->status === 'published') ? count($stepKeys) - 1 : -1;
                $isFailed = $project->status === 'failed';
                $isActive = in_array($project->status, ['generating_music', 'rendering', 'uploading']);
                $isDone = in_array($project->status, ['uploaded', 'published']);
            @endphp

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border {{ $isFailed ? 'border-red-300 dark:border-red-700' : ($isActive ? 'border-yellow-300 dark:border-yellow-700' : ($isDone ? 'border-green-300 dark:border-green-700' : 'border-gray-100 dark:border-gray-700')) }} overflow-hidden"
                 x-data="{ expanded: false }">

                {{-- Card Header --}}
                <div class="p-4 cursor-pointer" @click="expanded = !expanded">
                    <div class="flex items-center gap-4">
                        {{-- Status Indicator --}}
                        <div class="relative shrink-0">
                            @if($isActive)
                                <div class="w-12 h-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-yellow-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </div>
                            @elseif($isFailed)
                                <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </div>
                            @elseif($isDone)
                                <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                            @else
                                <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                    {{ $project->title ?: 'โปรเจกต์ #' . $project->id }}
                                </h4>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                                    {{ $isActive ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300' : '' }}
                                    {{ $isFailed ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' : '' }}
                                    {{ $isDone ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' : '' }}
                                    {{ !$isActive && !$isFailed && !$isDone ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' : '' }}">
                                    {{ $project->status_label }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                @if($project->channel)
                                    <span>{{ $project->channel->name }}</span>
                                @endif
                                <span>{{ $project->updated_at->diffForHumans() }}</span>
                            </div>
                        </div>

                        {{-- Pipeline Progress Bar --}}
                        <div class="hidden md:flex items-center gap-1 shrink-0">
                            @foreach($pipelineSteps as $key => $step)
                                @php
                                    $idx = array_search($key, $stepKeys);
                                    $stepDone = !$isFailed && $idx < $currentIdx;
                                    $stepCurrent = $key === $project->status;
                                    $stepFailed = $isFailed && $key === 'draft'; // show failed state
                                @endphp
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-[9px] font-bold transition-all
                                    {{ $stepDone ? 'bg-green-500 text-white' : '' }}
                                    {{ $stepCurrent && $isActive ? 'bg-yellow-500 text-white animate-pulse' : '' }}
                                    {{ $stepCurrent && !$isActive && !$isFailed ? 'bg-indigo-500 text-white' : '' }}
                                    {{ $isFailed && $stepCurrent ? 'bg-red-500 text-white' : '' }}
                                    {{ !$stepDone && !$stepCurrent ? 'bg-gray-200 dark:bg-gray-700 text-gray-400' : '' }}"
                                    title="{{ $step['label'] }}">
                                    @if($stepDone)
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    @elseif($isFailed && $stepCurrent)
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                    @else
                                        {{ $idx + 1 }}
                                    @endif
                                </div>
                                @if(!$loop->last)
                                    <div class="w-3 h-0.5 {{ $stepDone ? 'bg-green-400' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                                @endif
                            @endforeach
                        </div>

                        {{-- Quick Actions --}}
                        <div class="flex items-center gap-2 shrink-0" @click.stop>
                            @if(in_array($project->status, ['draft', 'failed']))
                                <button @click="action({{ $project->id }}, 'start-full')"
                                    class="px-3 py-1.5 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:from-purple-700 hover:to-pink-700 text-xs font-bold shadow-sm">
                                    <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                                    เริ่มทั้งหมด
                                </button>
                            @endif
                            @if($project->status === 'failed')
                                <button @click="action({{ $project->id }}, 'retry')"
                                    class="px-3 py-1.5 bg-orange-500 text-white rounded-lg hover:bg-orange-600 text-xs font-bold">
                                    ลองใหม่
                                </button>
                            @endif
                            @if($project->status === 'music_ready')
                                <button @click="action({{ $project->id }}, 'start-render')"
                                    class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-xs font-bold">
                                    เรนเดอร์
                                </button>
                            @endif
                            @if($project->status === 'rendered')
                                <button @click="action({{ $project->id }}, 'start-upload')"
                                    class="px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 text-xs font-bold">
                                    อัปโหลด
                                </button>
                            @endif
                            @if($isDone && $project->video)
                                <a href="https://www.youtube.com/watch?v={{ $project->video->youtube_id }}" target="_blank"
                                    class="px-3 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 text-xs font-bold">
                                    YouTube
                                </a>
                            @endif

                            {{-- Expand Toggle --}}
                            <button class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4 transition-transform" :class="expanded && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Error Message --}}
                    @if($project->error_message)
                        <div class="mt-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-2.5 flex items-start gap-2">
                            <svg class="w-4 h-4 text-red-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <p class="text-xs text-red-700 dark:text-red-300">{{ $project->error_message }}</p>
                        </div>
                    @endif
                </div>

                {{-- Expanded Detail Panel --}}
                <div x-show="expanded" x-collapse class="border-t border-gray-100 dark:border-gray-700">
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/50">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {{-- Pipeline Visual --}}
                            <div class="md:col-span-2">
                                <h5 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Pipeline Progress</h5>
                                <div class="space-y-3">
                                    @foreach($pipelineSteps as $key => $step)
                                        @php
                                            $idx = array_search($key, $stepKeys);
                                            $stepDone = !$isFailed && $idx < $currentIdx;
                                            $stepCurrent = $key === $project->status;
                                        @endphp
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0
                                                {{ $stepDone ? 'bg-green-500 text-white' : '' }}
                                                {{ $stepCurrent && $isActive ? 'bg-yellow-500 text-white animate-pulse' : '' }}
                                                {{ $stepCurrent && !$isActive && !$isFailed ? 'bg-indigo-500 text-white' : '' }}
                                                {{ $isFailed && $stepCurrent ? 'bg-red-500 text-white' : '' }}
                                                {{ !$stepDone && !$stepCurrent ? 'bg-gray-200 dark:bg-gray-700 text-gray-400' : '' }}">
                                                @if($stepDone)
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $step['icon'] }}"/></svg>
                                                @endif
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium {{ $stepCurrent ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400' }}">
                                                    {{ $step['label'] }}
                                                    @if($stepCurrent && $isActive)
                                                        <span class="ml-1 text-yellow-600 dark:text-yellow-400 text-xs">(กำลังดำเนินการ...)</span>
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="shrink-0">
                                                @if($stepDone)
                                                    <span class="text-[10px] text-green-600 font-medium">สำเร็จ</span>
                                                @elseif($stepCurrent && $isActive)
                                                    <span class="inline-flex items-center gap-1 text-[10px] text-yellow-600 font-medium">
                                                        <svg class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                        ทำงาน
                                                    </span>
                                                @elseif($isFailed && $stepCurrent)
                                                    <span class="text-[10px] text-red-600 font-medium">ล้มเหลว</span>
                                                @else
                                                    <span class="text-[10px] text-gray-400">รอ</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Controls & Info --}}
                            <div>
                                <h5 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">ข้อมูล & ควบคุม</h5>
                                <div class="space-y-3">
                                    <div class="text-xs space-y-1.5">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">ID</span>
                                            <span class="text-gray-900 dark:text-white font-mono">#{{ $project->id }}</span>
                                        </div>
                                        @if($project->channel)
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">ช่อง</span>
                                            <span class="text-gray-900 dark:text-white">{{ $project->channel->name }}</span>
                                        </div>
                                        @endif
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Template</span>
                                            <span class="text-gray-900 dark:text-white">{{ $project->template_label ?? '-' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Privacy</span>
                                            <span class="text-gray-900 dark:text-white">{{ $project->privacy_status ?? 'private' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">สร้างเมื่อ</span>
                                            <span class="text-gray-900 dark:text-white">{{ $project->created_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                        @if($project->musicGeneration)
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">เพลง</span>
                                            <span class="text-gray-900 dark:text-white">{{ $project->musicGeneration->status ?? '-' }}</span>
                                        </div>
                                        @endif
                                    </div>

                                    {{-- Music Preview --}}
                                    @if($project->musicGeneration && $project->musicGeneration->audio_url)
                                        <div>
                                            <p class="text-xs text-gray-500 mb-1">ตัวอย่างเพลง</p>
                                            <audio controls class="w-full h-8" style="height: 32px;"><source src="{{ $project->musicGeneration->audio_url }}"></audio>
                                        </div>
                                    @endif

                                    {{-- Step-specific Actions --}}
                                    <div class="space-y-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                        @if(in_array($project->status, ['draft', 'failed']))
                                            <button @click="action({{ $project->id }}, 'start-full')"
                                                class="w-full px-3 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:from-purple-700 hover:to-pink-700 text-xs font-bold text-center">
                                                เริ่ม Pipeline ทั้งหมด
                                            </button>
                                            <button @click="action({{ $project->id }}, 'start-music')"
                                                class="w-full px-3 py-2 bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-300 rounded-lg hover:bg-pink-200 dark:hover:bg-pink-900/50 text-xs font-medium text-center">
                                                สร้างเพลงอย่างเดียว
                                            </button>
                                        @endif
                                        @if($project->status === 'music_ready')
                                            <button @click="action({{ $project->id }}, 'start-render')"
                                                class="w-full px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-xs font-bold text-center">
                                                เรนเดอร์วิดีโอ
                                            </button>
                                        @endif
                                        @if($project->status === 'rendered')
                                            <button @click="action({{ $project->id }}, 'start-upload')"
                                                class="w-full px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-xs font-bold text-center">
                                                อัปโหลดไป YouTube
                                            </button>
                                        @endif
                                        @if($project->status === 'generating_music')
                                            <button @click="checkMusic({{ $project->id }})"
                                                class="w-full px-3 py-2 bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300 rounded-lg text-xs font-medium text-center">
                                                เช็คสถานะเพลง
                                            </button>
                                        @endif
                                        @if($project->status === 'failed')
                                            <button @click="action({{ $project->id }}, 'retry')"
                                                class="w-full px-3 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 text-xs font-bold text-center">
                                                ลองใหม่ (Auto-detect step)
                                            </button>
                                        @endif

                                        @if($isActive)
                                            <button @click="action({{ $project->id }}, 'cancel')"
                                                class="w-full px-3 py-2 bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 text-xs font-medium text-center">
                                                ยกเลิก
                                            </button>
                                        @endif

                                        <div class="flex gap-2">
                                            <a href="{{ route('admin.metal-x.projects.show', $project) }}"
                                                class="flex-1 px-3 py-2 bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 text-xs text-center">
                                                รายละเอียด
                                            </a>
                                            @if(!$isActive && !$isDone)
                                                <button @click="if(confirm('ลบโปรเจกต์นี้?')) destroy({{ $project->id }})"
                                                    class="px-3 py-2 bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-800 text-xs">
                                                    ลบ
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-12 text-center">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
                <p class="text-lg font-medium text-gray-500 dark:text-gray-400">ยังไม่มีโปรเจกต์ใน Pipeline</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1 mb-4">สร้างโปรเจกต์ใหม่เพื่อเริ่มกระบวนการสร้างวิดีโอ</p>
                <a href="{{ route('admin.metal-x.projects.create') }}" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    สร้างโปรเจกต์ใหม่
                </a>
            </div>
        @endforelse

        @if($projects->hasPages())
            <div class="mt-6">{{ $projects->withQueryString()->links() }}</div>
        @endif
    </div>

    {{-- Recent Activity Log --}}
    @if($recentActivity->count() > 0)
    <div class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
        <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">กิจกรรมล่าสุด</h3>
        <div class="space-y-2">
            @foreach($recentActivity as $item)
                <div class="flex items-center gap-3 text-sm py-2 {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                    <div class="w-2 h-2 rounded-full {{ $item->status === 'failed' ? 'bg-red-500' : 'bg-green-500' }}"></div>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $item->title ?: 'โปรเจกต์ #' . $item->id }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium
                        {{ $item->status === 'failed' ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' : 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' }}">
                        {{ $item->status_label }}
                    </span>
                    @if($item->channel)
                        <span class="text-xs text-gray-500">{{ $item->channel->name }}</span>
                    @endif
                    <span class="text-xs text-gray-400 ml-auto">{{ $item->updated_at->diffForHumans() }}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Toast Notification --}}
    <div x-show="toast.show" x-transition
         class="fixed bottom-6 right-6 z-50 max-w-sm text-white rounded-lg shadow-lg px-4 py-3"
         :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'">
        <div class="flex items-center gap-2">
            <template x-if="toast.type === 'success'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </template>
            <template x-if="toast.type === 'error'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </template>
            <span class="text-sm font-medium" x-text="toast.message"></span>
        </div>
    </div>
</div>

@push('scripts')
<script>
function pipelineDashboard() {
    return {
        polling: true,
        pollInterval: null,
        toast: { show: false, message: '', type: 'success' },

        startPolling() {
            this.poll();
            this.pollInterval = setInterval(() => this.poll(), 8000);
        },

        togglePolling() {
            this.polling = !this.polling;
            if (this.polling) {
                this.startPolling();
            } else {
                clearInterval(this.pollInterval);
            }
        },

        async poll() {
            if (!this.polling) return;
            try {
                const res = await fetch('{{ route("admin.metal-x.pipeline.status") }}');
                const data = await res.json();
                // Update active project statuses in the UI
                data.projects.forEach(p => {
                    const el = document.querySelector(`[data-project-id="${p.id}"]`);
                    if (el) {
                        el.querySelector('.status-label').textContent = p.status_label;
                    }
                });
            } catch (e) {
                // Silent fail for polling
            }
        },

        async action(projectId, actionName) {
            try {
                const res = await fetch(`{{ url('admin/metal-x/pipeline') }}/${projectId}/${actionName}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });
                const data = await res.json();
                this.showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    setTimeout(() => location.reload(), 1500);
                }
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        async checkMusic(projectId) {
            try {
                const res = await fetch(`{{ url('admin/metal-x/pipeline') }}/${projectId}/check-music`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.showToast(data.message, data.success ? 'success' : 'error');
                if (data.status === 'completed') {
                    setTimeout(() => location.reload(), 1000);
                }
            } catch (e) {
                this.showToast('เช็คสถานะไม่สำเร็จ', 'error');
            }
        },

        async destroy(projectId) {
            try {
                const res = await fetch(`{{ url('admin/metal-x/pipeline') }}/${projectId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });
                const data = await res.json();
                this.showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    setTimeout(() => location.reload(), 1000);
                }
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 4000);
        }
    };
}
</script>
@endpush
@endsection
