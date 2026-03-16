@extends($adminLayout ?? 'layouts.admin')

@section('title', $project->title ?: 'โปรเจกต์ #' . $project->id)
@section('page-title', 'รายละเอียดโปรเจกต์')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('admin.metal-x.projects.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            กลับ
        </a>
        @if(in_array($project->status, ['draft', 'failed']))
            <a href="{{ route('admin.metal-x.projects.edit', $project) }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm">แก้ไข</a>
        @endif
    </div>

    {{-- Status & Title --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <div class="flex items-center gap-3 mb-3">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $project->status_color }}-100 text-{{ $project->status_color }}-800 dark:bg-{{ $project->status_color }}-900 dark:text-{{ $project->status_color }}-200">
                {{ $project->status_label }}
            </span>
            @if($project->channel)
                <span class="text-sm text-gray-500 dark:text-gray-400">ช่อง: <strong>{{ $project->channel->name }}</strong></span>
            @endif
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $project->template_label }}</span>
        </div>

        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            {{ $project->title ?: 'โปรเจกต์ #' . $project->id }}
        </h2>

        @if($project->description)
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-2 whitespace-pre-wrap">{{ Str::limit($project->description, 300) }}</p>
        @endif

        @if($project->error_message)
            <div class="mt-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
                <p class="text-sm text-red-700 dark:text-red-300">{{ $project->error_message }}</p>
            </div>
        @endif

        @if($project->scheduled_at)
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">ตั้งเวลาเผยแพร่: {{ $project->scheduled_at->format('d/m/Y H:i') }}</p>
        @endif
    </div>

    {{-- Pipeline Status --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">กระบวนการ</h3>

        <div class="flex items-center gap-2 overflow-x-auto pb-2">
            @php
                $steps = ['draft' => 'สร้าง', 'generating_music' => 'สร้างเพลง', 'music_ready' => 'เพลงพร้อม', 'rendering' => 'เรนเดอร์', 'rendered' => 'เรนเดอร์เสร็จ', 'uploading' => 'อัปโหลด', 'uploaded' => 'เสร็จ'];
                $stepKeys = array_keys($steps);
                $currentIdx = array_search($project->status, $stepKeys);
                if ($currentIdx === false) $currentIdx = -1;
            @endphp

            @foreach($steps as $key => $label)
                @php
                    $idx = array_search($key, $stepKeys);
                    $isDone = $idx < $currentIdx;
                    $isCurrent = $key === $project->status;
                @endphp
                <div class="flex items-center gap-2 shrink-0">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                        {{ $isDone ? 'bg-green-500 text-white' : ($isCurrent ? 'bg-indigo-500 text-white animate-pulse' : 'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400') }}">
                        @if($isDone)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            {{ $idx + 1 }}
                        @endif
                    </div>
                    <span class="text-xs {{ $isCurrent ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-gray-500 dark:text-gray-400' }}">{{ $label }}</span>
                    @if(!$loop->last)
                        <svg class="w-4 h-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Actions --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">การดำเนินการ</h3>
        <div class="flex flex-wrap gap-3">
            @if(in_array($project->status, ['draft', 'failed']))
                <button onclick="actionProject('publish')" class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 text-sm font-medium">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    เริ่มสร้างทั้งหมด
                </button>
                <button onclick="actionProject('generate-music')" class="px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 text-sm font-medium">สร้างเพลง</button>
            @endif

            @if($project->status === 'music_ready')
                <button onclick="actionProject('render')" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 text-sm font-medium">เรนเดอร์วิดีโอ</button>
            @endif

            @if($project->status === 'rendered')
                <button onclick="actionProject('upload')" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">อัปโหลดไป YouTube</button>
            @endif

            <button onclick="actionProject('generate-metadata')" class="px-4 py-2 bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-800 text-sm font-medium">AI สร้างข้อมูล</button>

            @if($project->video)
                <a href="{{ $project->video->youtube_url }}" target="_blank" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium">ดูบน YouTube</a>
            @endif
        </div>
    </div>

    {{-- Music Info --}}
    @if($project->musicGeneration)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">ข้อมูลเพลง</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-500 dark:text-gray-400">สถานะ</p>
                    <p class="text-gray-900 dark:text-white font-medium">{{ $project->musicGeneration->status_label }}</p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400">สไตล์</p>
                    <p class="text-gray-900 dark:text-white font-medium">{{ $project->musicGeneration->style ?: '-' }}</p>
                </div>
                @if($project->musicGeneration->audio_url)
                    <div class="col-span-2">
                        <p class="text-gray-500 dark:text-gray-400 mb-1">ตัวอย่างเพลง</p>
                        <audio controls class="w-full"><source src="{{ $project->musicGeneration->audio_url }}"></audio>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Images --}}
    @if($project->images && count($project->images) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">รูปภาพสไลด์ ({{ count($project->images) }} รูป)</h3>
            <div class="grid grid-cols-3 md:grid-cols-5 gap-3">
                @foreach($project->images as $i => $image)
                    <div class="aspect-video rounded-lg overflow-hidden bg-gray-200 dark:bg-gray-700">
                        <img src="{{ Storage::disk('local')->url($image) }}" alt="Slide {{ $i + 1 }}" class="w-full h-full object-cover">
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Template Settings --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">ตั้งค่า Template</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
            @foreach($project->template_settings ?? [] as $key => $value)
                <div>
                    <p class="text-gray-500 dark:text-gray-400">{{ str_replace('_', ' ', ucfirst($key)) }}</p>
                    <p class="text-gray-900 dark:text-white font-medium">{{ is_array($value) ? json_encode($value) : $value }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Delete --}}
    <div class="flex justify-end">
        <form method="POST" action="{{ route('admin.metal-x.projects.destroy', $project) }}" onsubmit="return confirm('ยืนยันการลบโปรเจกต์นี้?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200 rounded-lg hover:bg-red-200 dark:hover:bg-red-800 text-sm font-medium">ลบโปรเจกต์</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
function actionProject(action) {
    fetch(`{{ url('admin/metal-x/projects') }}/{{ $project->id }}/${action}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message || 'กำลังดำเนินการ...');
        setTimeout(() => location.reload(), 2000);
    })
    .catch(() => alert('เกิดข้อผิดพลาด'));
}
</script>
@endpush
@endsection
