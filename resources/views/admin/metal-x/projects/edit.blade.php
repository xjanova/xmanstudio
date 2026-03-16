@extends($adminLayout ?? 'layouts.admin')

@section('title', 'แก้ไขโปรเจกต์ #' . $project->id)
@section('page-title', 'แก้ไขโปรเจกต์วิดีโอ')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('admin.metal-x.projects.show', $project) }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            กลับ
        </a>
    </div>

    <form method="POST" action="{{ route('admin.metal-x.projects.update', $project) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Channel --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">เลือกช่อง YouTube</h3>
            <select name="channel_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                @foreach($channels as $channel)
                    <option value="{{ $channel->id }}" {{ old('channel_id', $project->channel_id) == $channel->id ? 'selected' : '' }}>{{ $channel->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Music --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <span>ตั้งค่าเพลง</span>
                <span class="ml-auto inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($sunoMode ?? 'api') === 'onsite' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                    {{ ($sunoMode ?? 'api') === 'onsite' ? 'Onsite Mode' : 'API Mode' }}
                </span>
            </h3>
            @if(($sunoMode ?? 'api') === 'onsite')
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 mb-4">
                    <p class="text-xs text-amber-700 dark:text-amber-300">โหมด Onsite — ไปสร้างเพลงที่ <a href="{{ $sunoCreateUrl ?? 'https://suno.com/create' }}" target="_blank" class="underline font-medium">suno.com/create</a> แล้วอัปโหลด MP3 ในหน้าโปรเจกต์</p>
                </div>
            @endif
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบายเพลง</label>
                    <textarea name="music_prompt" rows="3" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">{{ old('music_prompt', $project->getTemplateSetting('music_prompt')) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สไตล์เพลง</label>
                    <input type="text" name="music_style" value="{{ old('music_style', $project->getTemplateSetting('music_style')) }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Suno Model</label>
                    @php $currentModel = old('music_model', $project->getTemplateSetting('music_model', 'V4')); @endphp
                    <select name="music_model" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        <option value="V4" {{ $currentModel === 'V4' ? 'selected' : '' }}>V4 (Standard)</option>
                        <option value="V4_5" {{ $currentModel === 'V4_5' ? 'selected' : '' }}>V4.5</option>
                        <option value="V4_5PLUS" {{ $currentModel === 'V4_5PLUS' ? 'selected' : '' }}>V4.5 Plus</option>
                        <option value="V4_5ALL" {{ $currentModel === 'V4_5ALL' ? 'selected' : '' }}>V4.5 All</option>
                        <option value="V5" {{ $currentModel === 'V5' ? 'selected' : '' }}>V5 (Latest)</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Images --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">รูปภาพสไลด์</h3>
            @if($project->images && count($project->images) > 0)
                <div class="grid grid-cols-4 gap-3 mb-4">
                    @foreach($project->images as $i => $image)
                        <div class="aspect-video rounded-lg overflow-hidden bg-gray-200 dark:bg-gray-700">
                            <img src="{{ Storage::disk('local')->url($image) }}" alt="Slide {{ $i + 1 }}" class="w-full h-full object-cover">
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">มี {{ count($project->images) }} รูปภาพอยู่แล้ว</p>
            @endif
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เพิ่มรูปภาพ</label>
            <input type="file" name="new_images[]" multiple accept="image/*" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-indigo-50 dark:file:bg-indigo-900 file:text-indigo-700 dark:file:text-indigo-200">
        </div>

        {{-- Template --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">รูปแบบวิดีโอ</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Template</label>
                    <select name="template" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        @foreach(\App\Models\MetalXVideoProject::TEMPLATES as $key => $label)
                            <option value="{{ $key }}" {{ old('template', $project->template) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Transition</label>
                    <select name="transition" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        <option value="crossfade" {{ old('transition', $project->getTemplateSetting('transition')) === 'crossfade' ? 'selected' : '' }}>Crossfade</option>
                        <option value="fade" {{ old('transition', $project->getTemplateSetting('transition')) === 'fade' ? 'selected' : '' }}>Fade</option>
                        <option value="concat" {{ old('transition', $project->getTemplateSetting('transition')) === 'concat' ? 'selected' : '' }}>ตัดตรง</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Effect</label>
                    <select name="effect" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        <option value="ken_burns" {{ old('effect', $project->getTemplateSetting('effect')) === 'ken_burns' ? 'selected' : '' }}>Ken Burns</option>
                        <option value="slide_left" {{ old('effect', $project->getTemplateSetting('effect')) === 'slide_left' ? 'selected' : '' }}>เลื่อนซ้าย</option>
                        <option value="zoom" {{ old('effect', $project->getTemplateSetting('effect')) === 'zoom' ? 'selected' : '' }}>Zoom In/Out</option>
                        <option value="none" {{ old('effect', $project->getTemplateSetting('effect')) === 'none' ? 'selected' : '' }}>ไม่มี</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สีพื้นหลัง</label>
                    <input type="color" name="background_color" value="{{ old('background_color', $project->getTemplateSetting('background_color', '#000000')) }}" class="w-full h-10 rounded-lg cursor-pointer">
                </div>
            </div>
        </div>

        {{-- Metadata --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลวิดีโอ</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อวิดีโอ</label>
                    <input type="text" name="title" value="{{ old('title', $project->title) }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">{{ old('description', $project->description) }}</textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะเผยแพร่</label>
                        <select name="privacy_status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                            <option value="private" {{ old('privacy_status', $project->privacy_status) === 'private' ? 'selected' : '' }}>ส่วนตัว</option>
                            <option value="unlisted" {{ old('privacy_status', $project->privacy_status) === 'unlisted' ? 'selected' : '' }}>ไม่แสดงในรายการ</option>
                            <option value="public" {{ old('privacy_status', $project->privacy_status) === 'public' ? 'selected' : '' }}>สาธารณะ</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ตั้งเวลา</label>
                        <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', $project->scheduled_at?->format('Y-m-d\TH:i')) }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('admin.metal-x.projects.show', $project) }}" class="px-6 py-3 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm">ยกเลิก</a>
            <button type="submit" class="px-8 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-bold">บันทึกการแก้ไข</button>
        </div>
    </form>
</div>
@endsection
