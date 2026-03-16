@extends($adminLayout ?? 'layouts.admin')

@section('title', 'สร้างโปรเจกต์วิดีโอ')
@section('page-title', 'สร้างโปรเจกต์วิดีโอเพลง')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('admin.metal-x.projects.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            กลับไปรายการโปรเจกต์
        </a>
    </div>

    <form method="POST" action="{{ route('admin.metal-x.projects.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Step 1: Channel --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <span class="w-7 h-7 rounded-full bg-indigo-600 text-white text-sm flex items-center justify-center mr-3">1</span>
                เลือกช่อง YouTube
            </h3>
            @if($channels->isEmpty())
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">ยังไม่มีช่องที่เชื่อมต่อ <a href="{{ route('youtube.redirect') }}" class="underline font-medium">เชื่อมต่อช่องใหม่</a></p>
                </div>
            @else
                <select name="channel_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    <option value="">-- เลือกช่อง --</option>
                    @foreach($channels as $channel)
                        <option value="{{ $channel->id }}" {{ old('channel_id') == $channel->id ? 'selected' : '' }}>
                            {{ $channel->name }} ({{ number_format($channel->subscriber_count) }} subs)
                        </option>
                    @endforeach
                </select>
                @error('channel_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            @endif
        </div>

        {{-- Step 2: Music --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <span class="w-7 h-7 rounded-full bg-indigo-600 text-white text-sm flex items-center justify-center mr-3">2</span>
                ตั้งค่าเพลง (Suno AI)
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบายเพลง (Prompt)</label>
                    <textarea name="music_prompt" rows="3" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm" placeholder="เช่น: เพลง metal หนักๆ บรรยากาศมืดมิด กลองหนัก กีต้าร์ไฟฟ้าดุดัน">{{ old('music_prompt') }}</textarea>
                    @error('music_prompt')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สไตล์เพลง</label>
                    <input type="text" name="music_style" value="{{ old('music_style', 'metal') }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm" placeholder="metal, rock, ambient, lo-fi...">
                </div>
            </div>
        </div>

        {{-- Step 3: Images --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <span class="w-7 h-7 rounded-full bg-indigo-600 text-white text-sm flex items-center justify-center mr-3">3</span>
                อัปโหลดรูปภาพสไลด์
            </h3>
            <div x-data="{ files: [] }" class="space-y-4">
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-500 transition-colors"
                     @click="$refs.imageInput.click()">
                    <svg class="w-10 h-10 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-sm text-gray-600 dark:text-gray-400">คลิกเพื่ออัปโหลดรูปภาพ (อย่างน้อย 1 รูป, สูงสุด 50 รูป)</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">รูปจะสไลด์สลับกันตลอดความยาวเพลง</p>
                    <input type="file" name="images[]" multiple accept="image/*" required x-ref="imageInput" class="hidden"
                           @change="files = Array.from($event.target.files)">
                </div>
                <template x-if="files.length > 0">
                    <p class="text-sm text-green-600 dark:text-green-400" x-text="files.length + ' ไฟล์ถูกเลือก'"></p>
                </template>
                @error('images')
                    <p class="text-red-500 text-xs">{{ $message }}</p>
                @enderror
                @error('images.*')
                    <p class="text-red-500 text-xs">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Step 4: Template --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <span class="w-7 h-7 rounded-full bg-indigo-600 text-white text-sm flex items-center justify-center mr-3">4</span>
                รูปแบบวิดีโอ
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Template</label>
                    <select name="template" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        @foreach(\App\Models\MetalXVideoProject::TEMPLATES as $key => $label)
                            <option value="{{ $key }}" {{ old('template', 'visualizer') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Transition</label>
                    <select name="transition" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        <option value="crossfade" {{ old('transition') === 'crossfade' ? 'selected' : '' }}>Crossfade</option>
                        <option value="fade" {{ old('transition') === 'fade' ? 'selected' : '' }}>Fade</option>
                        <option value="concat" {{ old('transition') === 'concat' ? 'selected' : '' }}>ตัดตรง</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Effect</label>
                    <select name="effect" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        <option value="ken_burns" {{ old('effect') === 'ken_burns' ? 'selected' : '' }}>Ken Burns (Zoom ช้าๆ)</option>
                        <option value="slide_left" {{ old('effect') === 'slide_left' ? 'selected' : '' }}>เลื่อนซ้าย</option>
                        <option value="zoom" {{ old('effect') === 'zoom' ? 'selected' : '' }}>Zoom In/Out</option>
                        <option value="none" {{ old('effect') === 'none' ? 'selected' : '' }}>ไม่มี</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สีพื้นหลัง</label>
                    <input type="color" name="background_color" value="{{ old('background_color', '#000000') }}" class="w-full h-10 rounded-lg cursor-pointer">
                </div>
            </div>
        </div>

        {{-- Step 5: Metadata --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <span class="w-7 h-7 rounded-full bg-indigo-600 text-white text-sm flex items-center justify-center mr-3">5</span>
                ข้อมูลวิดีโอ (สามารถให้ AI สร้างทีหลังได้)
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อวิดีโอ</label>
                    <input type="text" name="title" value="{{ old('title') }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm" placeholder="(ปล่อยว่างให้ AI สร้างได้)">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm" placeholder="(ปล่อยว่างให้ AI สร้างได้)">{{ old('description') }}</textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะเผยแพร่</label>
                        <select name="privacy_status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                            <option value="private" {{ old('privacy_status') === 'private' ? 'selected' : '' }}>ส่วนตัว</option>
                            <option value="unlisted" {{ old('privacy_status') === 'unlisted' ? 'selected' : '' }}>ไม่แสดงในรายการ</option>
                            <option value="public" {{ old('privacy_status') === 'public' ? 'selected' : '' }}>สาธารณะ</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ตั้งเวลาเผยแพร่</label>
                        <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('admin.metal-x.projects.index') }}" class="px-6 py-3 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium">ยกเลิก</a>
            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 text-sm font-bold">
                <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                สร้างโปรเจกต์
            </button>
        </div>
    </form>
</div>
@endsection
