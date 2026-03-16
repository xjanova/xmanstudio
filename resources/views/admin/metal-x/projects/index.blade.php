@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Video Projects')
@section('page-title', 'สร้างวิดีโอเพลง')

@section('content')
<!-- Header -->
<div class="bg-gradient-to-r from-purple-600 to-indigo-800 rounded-lg shadow-lg p-6 mb-6 text-white">
    <div class="flex flex-col md:flex-row items-center gap-6">
        <div class="w-20 h-20 rounded-full bg-white/20 flex items-center justify-center">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
            </svg>
        </div>
        <div class="text-center md:text-left flex-1">
            <h2 class="text-2xl font-bold">Music Video Creator</h2>
            <p class="text-purple-200 text-sm">สร้างคลิปเพลงด้วย AI (Suno) + Visualizer แล้วโพสขึ้น YouTube อัตโนมัติ</p>
        </div>
        <a href="{{ route('admin.metal-x.projects.create') }}" class="px-6 py-3 bg-white text-purple-700 font-bold rounded-lg hover:bg-purple-50 flex items-center text-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            สร้างโปรเจกต์ใหม่
        </a>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">ทั้งหมด</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">แบบร่าง</p>
        <p class="text-2xl font-bold text-yellow-600">{{ $stats['drafts'] }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">กำลังสร้าง</p>
        <p class="text-2xl font-bold text-blue-600">{{ $stats['rendering'] }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">อัปโหลดแล้ว</p>
        <p class="text-2xl font-bold text-green-600">{{ $stats['uploaded'] }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">ล้มเหลว</p>
        <p class="text-2xl font-bold text-red-600">{{ $stats['failed'] }}</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                <option value="">ทุกสถานะ</option>
                @foreach(\App\Models\MetalXVideoProject::STATUSES as $key => $label)
                    <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select name="channel_id" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                <option value="">ทุกช่อง</option>
                @foreach($channels as $channel)
                    <option value="{{ $channel->id }}" {{ request('channel_id') == $channel->id ? 'selected' : '' }}>{{ $channel->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">กรอง</button>
        @if(request()->hasAny(['status', 'channel_id']))
            <a href="{{ route('admin.metal-x.projects.index') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm">ล้าง</a>
        @endif
    </form>
</div>

<!-- Projects List -->
<div class="space-y-4">
    @forelse($projects as $project)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4 flex-1">
                    {{-- Thumbnail from first image --}}
                    @if($project->images && count($project->images) > 0)
                        <img src="{{ Storage::disk('local')->url($project->images[0]) }}" alt="" class="w-24 h-16 rounded-lg object-cover bg-gray-200 dark:bg-gray-700">
                    @else
                        <div class="w-24 h-16 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                    @endif

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $project->status_color }}-100 text-{{ $project->status_color }}-800 dark:bg-{{ $project->status_color }}-900 dark:text-{{ $project->status_color }}-200">
                                {{ $project->status_label }}
                            </span>
                            @if($project->ai_metadata_generated)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">AI</span>
                            @endif
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $project->template_label }}</span>
                        </div>

                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                            {{ $project->title ?: 'โปรเจกต์ #' . $project->id }}
                        </h4>

                        <div class="flex items-center gap-4 mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @if($project->channel)
                                <span>ช่อง: {{ $project->channel->name }}</span>
                            @endif
                            <span>{{ count($project->images ?? []) }} รูปภาพ</span>
                            <span>{{ $project->created_at->format('d/m/Y H:i') }}</span>
                        </div>

                        @if($project->error_message)
                            <p class="text-xs text-red-600 dark:text-red-400 mt-1 truncate">{{ $project->error_message }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('admin.metal-x.projects.show', $project) }}" class="px-3 py-1.5 bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-800 text-xs font-medium">ดูรายละเอียด</a>
                    @if(in_array($project->status, ['draft', 'failed']))
                        <a href="{{ route('admin.metal-x.projects.edit', $project) }}" class="px-3 py-1.5 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 text-xs font-medium">แก้ไข</a>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
            </svg>
            <p class="text-lg font-medium text-gray-500 dark:text-gray-400">ยังไม่มีโปรเจกต์</p>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">กดปุ่ม "สร้างโปรเจกต์ใหม่" เพื่อเริ่มสร้างคลิปเพลง</p>
        </div>
    @endforelse

    @if($projects->hasPages())
        <div class="mt-6">{{ $projects->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
