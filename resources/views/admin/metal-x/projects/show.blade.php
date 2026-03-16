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
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center justify-between">
            <span>การดำเนินการ</span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($sunoMode ?? 'api') === 'onsite' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                Suno: {{ ($sunoMode ?? 'api') === 'onsite' ? 'Onsite' : 'API' }}
            </span>
        </h3>
        <div class="flex flex-wrap gap-3">
            @if(in_array($project->status, ['draft', 'failed']))
                @if(($sunoMode ?? 'api') === 'api')
                    <button onclick="actionProject('publish')" class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 text-sm font-medium">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        เริ่มสร้างทั้งหมด
                    </button>
                    <button onclick="actionProject('generate-music')" class="px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 text-sm font-medium">สร้างเพลง (API)</button>
                @else
                    {{-- Onsite mode: link to suno.com + upload form --}}
                    <a href="{{ $sunoCreateUrl ?? 'https://suno.com/create' }}" target="_blank" class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:from-purple-700 hover:to-pink-700 text-sm font-medium inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        เปิด Suno.com สร้างเพลง
                    </a>
                @endif
            @endif

            @if(($sunoMode ?? 'api') === 'onsite' && in_array($project->status, ['draft', 'failed', 'generating_music']))
                <button onclick="document.getElementById('audioUploadPanel').classList.toggle('hidden')" class="px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 text-sm font-medium inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    อัปโหลดเพลง (MP3)
                </button>
            @endif

            @if(in_array($project->status, ['draft', 'failed', 'music_ready']))
                <button onclick="document.getElementById('clipUploadPanel').classList.toggle('hidden')" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm font-medium inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    อัปโหลดคลิป Freepik
                </button>
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

    {{-- Audio Upload Panel (Onsite Mode) --}}
    @if(($sunoMode ?? 'api') === 'onsite' && in_array($project->status, ['draft', 'failed', 'generating_music']))
        <div id="audioUploadPanel" class="hidden bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6 border-2 border-dashed border-purple-300 dark:border-purple-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                อัปโหลดไฟล์เพลงจาก Suno
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">ดาวน์โหลดเพลงจาก suno.com แล้วอัปโหลดที่นี่ (รองรับ MP3, WAV, OGG, M4A, AAC สูงสุด 50MB)</p>
            <form id="audioUploadForm" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ไฟล์เพลง <span class="text-red-500">*</span></label>
                    <input type="file" name="audio_file" accept=".mp3,.wav,.ogg,.m4a,.aac" required
                           class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-purple-50 dark:file:bg-purple-900 file:text-purple-700 dark:file:text-purple-200 file:cursor-pointer">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL เพลงจาก Suno (ไม่บังคับ)</label>
                    <input type="url" name="audio_url" placeholder="https://suno.com/song/..."
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ใส่ลิงก์เพลงจาก Suno เพื่อบันทึกเป็น reference</p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm font-medium inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        อัปโหลดเพลง
                    </button>
                    <span id="uploadStatus" class="text-sm text-gray-500 dark:text-gray-400 hidden"></span>
                </div>
            </form>
        </div>
    @endif

    {{-- Video Clip Upload Panel --}}
    @if(in_array($project->status, ['draft', 'failed', 'music_ready']))
        <div id="clipUploadPanel" class="hidden bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6 border-2 border-dashed border-purple-300 dark:border-purple-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                อัปโหลดคลิปวิดีโอ AI จาก Freepik
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                สร้างคลิปวิดีโอ AI จาก <a href="{{ $freepikCreateUrl ?? 'https://www.freepik.com/pikaso/ai-video-generator' }}" target="_blank" class="text-purple-600 dark:text-purple-400 underline">Freepik AI Video Generator</a>
                แล้วอัปโหลดที่นี่ (MP4, WebM, MOV สูงสุด 100MB ต่อคลิป)
            </p>
            <form id="clipUploadForm" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <input type="file" name="video_clips[]" multiple accept=".mp4,.webm,.mov" required
                           class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-purple-50 dark:file:bg-purple-900 file:text-purple-700 dark:file:text-purple-200 file:cursor-pointer">
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm font-medium inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        อัปโหลดคลิป
                    </button>
                    <span id="clipUploadStatus" class="text-sm text-gray-500 dark:text-gray-400 hidden"></span>
                </div>
            </form>
        </div>
    @endif

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

    {{-- Video Clips --}}
    @if($project->video_clips && count($project->video_clips) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                คลิปวิดีโอ AI ({{ count($project->video_clips) }} คลิป)
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach($project->video_clips as $i => $clip)
                    <div class="aspect-video rounded-lg overflow-hidden bg-gray-900 relative group">
                        <video class="w-full h-full object-cover" preload="metadata">
                            <source src="{{ Storage::disk('local')->url($clip) }}">
                        </video>
                        <div class="absolute inset-0 flex items-center justify-center bg-black/30 group-hover:bg-black/50 transition-colors">
                            <span class="text-white text-xs font-bold bg-purple-600 px-2 py-1 rounded">คลิป {{ $i + 1 }}</span>
                        </div>
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

        @if($project->eq_settings && ($project->eq_settings['enabled'] ?? false))
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                    <svg class="w-4 h-4 mr-1.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                    Visual EQ Overlay
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">สไตล์</p>
                        <p class="text-gray-900 dark:text-white font-medium">{{ \App\Models\MetalXVideoProject::EQ_STYLES[$project->eq_settings['style'] ?? 'showcqt'] ?? $project->eq_settings['style'] }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">ตำแหน่ง</p>
                        <p class="text-gray-900 dark:text-white font-medium">{{ $project->eq_settings['position'] ?? 'bottom' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">ความสูง / โปร่งใส</p>
                        <p class="text-gray-900 dark:text-white font-medium">{{ $project->eq_settings['height_percent'] ?? 20 }}% / {{ $project->eq_settings['opacity'] ?? 0.6 }}</p>
                    </div>
                </div>
            </div>
        @endif
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
    const notification = document.getElementById('actionNotification');
    if (notification) notification.remove();

    fetch(`{{ url('admin/metal-x/projects') }}/{{ $project->id }}/${action}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        showNotification(data.message || 'กำลังดำเนินการ...', data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => location.reload(), 2000);
    })
    .catch(() => showNotification('เกิดข้อผิดพลาด', 'error'));
}

function showNotification(message, type = 'success') {
    const colors = type === 'success'
        ? 'bg-green-100 text-green-800 border-green-300 dark:bg-green-900 dark:text-green-200 dark:border-green-700'
        : 'bg-red-100 text-red-800 border-red-300 dark:bg-red-900 dark:text-red-200 dark:border-red-700';
    const div = document.createElement('div');
    div.id = 'actionNotification';
    div.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg border shadow-lg text-sm font-medium ${colors}`;
    div.textContent = message;
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 5000);
}

// Audio upload form handler (Onsite mode)
const audioForm = document.getElementById('audioUploadForm');
if (audioForm) {
    audioForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const status = document.getElementById('uploadStatus');
        const submitBtn = this.querySelector('button[type="submit"]');

        status.classList.remove('hidden');
        status.textContent = 'กำลังอัปโหลด...';
        submitBtn.disabled = true;

        fetch(`{{ url('admin/metal-x/projects') }}/{{ $project->id }}/upload-audio`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                status.textContent = data.message || 'อัปโหลดล้มเหลว';
                submitBtn.disabled = false;
            }
        })
        .catch(err => {
            status.textContent = 'เกิดข้อผิดพลาดในการอัปโหลด';
            submitBtn.disabled = false;
        });
    });
}

// Video clip upload form handler
const clipForm = document.getElementById('clipUploadForm');
if (clipForm) {
    clipForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const status = document.getElementById('clipUploadStatus');
        const submitBtn = this.querySelector('button[type="submit"]');

        status.classList.remove('hidden');
        status.textContent = 'กำลังอัปโหลดคลิป...';
        submitBtn.disabled = true;

        fetch(`{{ url('admin/metal-x/projects') }}/{{ $project->id }}/upload-video-clips`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                status.textContent = data.message || 'อัปโหลดล้มเหลว';
                submitBtn.disabled = false;
            }
        })
        .catch(err => {
            status.textContent = 'เกิดข้อผิดพลาดในการอัปโหลด';
            submitBtn.disabled = false;
        });
    });
}
</script>
@endpush
@endsection
