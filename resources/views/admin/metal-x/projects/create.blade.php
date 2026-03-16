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
                <span class="ml-auto inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($sunoMode ?? 'api') === 'onsite' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                    {{ ($sunoMode ?? 'api') === 'onsite' ? 'Onsite Mode' : 'API Mode' }}
                </span>
            </h3>

            @if(($sunoMode ?? 'api') === 'onsite')
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 mb-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-amber-500 mt-0.5 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <p class="text-sm font-medium text-amber-800 dark:text-amber-200">โหมด Onsite — สร้างเพลงที่ suno.com แล้วอัปโหลดเอง</p>
                            <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">หลังสร้างโปรเจกต์ ไปที่ <a href="{{ $sunoCreateUrl ?? 'https://suno.com/create' }}" target="_blank" class="underline font-medium">suno.com/create</a> สร้างเพลง แล้วดาวน์โหลด MP3 มาอัปโหลดในหน้าโปรเจกต์</p>
                        </div>
                    </div>
                </div>
            @endif

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
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Suno Model</label>
                    <select name="music_model" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        <option value="V4" {{ old('music_model', 'V4') === 'V4' ? 'selected' : '' }}>V4 (Standard)</option>
                        <option value="V4_5" {{ old('music_model') === 'V4_5' ? 'selected' : '' }}>V4.5</option>
                        <option value="V4_5PLUS" {{ old('music_model') === 'V4_5PLUS' ? 'selected' : '' }}>V4.5 Plus</option>
                        <option value="V4_5ALL" {{ old('music_model') === 'V4_5ALL' ? 'selected' : '' }}>V4.5 All</option>
                        <option value="V5" {{ old('music_model') === 'V5' ? 'selected' : '' }}>V5 (Latest)</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Step 3: Media (Images + Video Clips) --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <span class="w-7 h-7 rounded-full bg-indigo-600 text-white text-sm flex items-center justify-center mr-3">3</span>
                สื่อวิดีโอ (รูปภาพ / คลิป AI)
            </h3>

            {{-- Media Mode Selector --}}
            <div x-data="{ mediaMode: '{{ old('media_mode', 'images') }}' }" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภทสื่อ</label>
                    <div class="flex flex-wrap gap-3">
                        <label class="flex items-center gap-2 px-4 py-2 rounded-lg border cursor-pointer transition-colors"
                               :class="mediaMode === 'images' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'">
                            <input type="radio" name="media_mode" value="images" x-model="mediaMode" class="sr-only">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="text-sm font-medium">ภาพนิ่ง</span>
                        </label>
                        <label class="flex items-center gap-2 px-4 py-2 rounded-lg border cursor-pointer transition-colors"
                               :class="mediaMode === 'video_clips' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300' : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'">
                            <input type="radio" name="media_mode" value="video_clips" x-model="mediaMode" class="sr-only">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span class="text-sm font-medium">คลิปวิดีโอ AI (Freepik)</span>
                        </label>
                        <label class="flex items-center gap-2 px-4 py-2 rounded-lg border cursor-pointer transition-colors"
                               :class="mediaMode === 'mixed' ? 'border-teal-500 bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300' : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'">
                            <input type="radio" name="media_mode" value="mixed" x-model="mediaMode" class="sr-only">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            <span class="text-sm font-medium">ผสม (ภาพ + คลิป)</span>
                        </label>
                    </div>
                </div>

                {{-- Image Upload (shown for images and mixed modes) --}}
                <div x-show="mediaMode === 'images' || mediaMode === 'mixed'" x-data="{ imageFiles: [] }">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปภาพสไลด์</label>
                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-500 transition-colors"
                         @click="$refs.imageInput.click()">
                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <p class="text-sm text-gray-600 dark:text-gray-400">คลิกเพื่ออัปโหลดรูปภาพ (1-50 รูป)</p>
                        <input type="file" name="images[]" multiple accept="image/*" x-ref="imageInput" class="hidden"
                               :required="mediaMode === 'images'"
                               @change="imageFiles = Array.from($event.target.files)">
                    </div>
                    <template x-if="imageFiles.length > 0">
                        <p class="text-sm text-green-600 dark:text-green-400 mt-2" x-text="imageFiles.length + ' รูปถูกเลือก'"></p>
                    </template>
                </div>

                {{-- Video Clip Upload (shown for video_clips and mixed modes) --}}
                <div x-show="mediaMode === 'video_clips' || mediaMode === 'mixed'" x-data="{ clipFiles: [] }">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คลิปวิดีโอ AI</label>
                    <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-3 mb-3">
                        <p class="text-xs text-purple-700 dark:text-purple-300">สร้างคลิปวิดีโอ AI จาก <a href="https://www.freepik.com/pikaso/ai-video-generator" target="_blank" class="underline font-medium">Freepik AI Video Generator</a> แล้วอัปโหลดที่นี่ (MP4, WebM, MOV สูงสุด 100MB ต่อคลิป)</p>
                    </div>
                    <div class="border-2 border-dashed border-purple-300 dark:border-purple-600 rounded-lg p-6 text-center cursor-pointer hover:border-purple-400 dark:hover:border-purple-500 transition-colors"
                         @click="$refs.clipInput.click()">
                        <svg class="w-8 h-8 mx-auto mb-2 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <p class="text-sm text-purple-600 dark:text-purple-400">คลิกเพื่ออัปโหลดคลิปวิดีโอ (1-20 คลิป)</p>
                        <input type="file" name="video_clips[]" multiple accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov" x-ref="clipInput" class="hidden"
                               :required="mediaMode === 'video_clips'"
                               @change="clipFiles = Array.from($event.target.files)">
                    </div>
                    <template x-if="clipFiles.length > 0">
                        <p class="text-sm text-purple-600 dark:text-purple-400 mt-2" x-text="clipFiles.length + ' คลิปถูกเลือก'"></p>
                    </template>
                </div>

                @error('images') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                @error('images.*') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                @error('video_clips') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                @error('video_clips.*') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
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

        {{-- Step 5: Visual EQ --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <span class="w-7 h-7 rounded-full bg-indigo-600 text-white text-sm flex items-center justify-center mr-3">5</span>
                Visual EQ Overlay
            </h3>
            <div x-data="{ eqEnabled: {{ old('eq_enabled') ? 'true' : 'false' }} }">
                <label class="flex items-center gap-3 cursor-pointer mb-4">
                    <div class="relative">
                        <input type="checkbox" name="eq_enabled" value="1" x-model="eqEnabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 rounded-full peer-checked:bg-indigo-600 transition-colors"></div>
                        <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform peer-checked:translate-x-5 transition-transform"></div>
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เปิดใช้ Visual Equalizer ซ้อนบนวิดีโอ</span>
                </label>

                <div x-show="eqEnabled" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สไตล์ EQ</label>
                        <select name="eq_style" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                            <option value="showcqt" {{ old('eq_style') === 'showcqt' ? 'selected' : '' }}>Frequency Bars (CQT) - แท่งความถี่</option>
                            <option value="showwaves" {{ old('eq_style') === 'showwaves' ? 'selected' : '' }}>Waveform - คลื่นเสียง</option>
                            <option value="showfreqs" {{ old('eq_style') === 'showfreqs' ? 'selected' : '' }}>Spectrum Bars - สเปกตรัม</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ตำแหน่ง</label>
                        <select name="eq_position" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                            <option value="bottom" {{ old('eq_position', 'bottom') === 'bottom' ? 'selected' : '' }}>ด้านล่าง</option>
                            <option value="top" {{ old('eq_position') === 'top' ? 'selected' : '' }}>ด้านบน</option>
                            <option value="center" {{ old('eq_position') === 'center' ? 'selected' : '' }}>ตรงกลาง</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความสูง EQ (<span x-text="$refs.eqHeight?.value || 20"></span>%)</label>
                        <input type="range" name="eq_height_percent" x-ref="eqHeight" min="10" max="50" value="{{ old('eq_height_percent', 20) }}" class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความโปร่งใส (<span x-text="$refs.eqOpacity?.value || 0.6"></span>)</label>
                        <input type="range" name="eq_opacity" x-ref="eqOpacity" min="0.1" max="1.0" step="0.1" value="{{ old('eq_opacity', '0.6') }}" class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สี EQ</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="eq_color" value="{{ old('eq_color', '#ff00ff') }}" class="w-10 h-10 rounded-lg cursor-pointer border-0">
                            <span class="text-xs text-gray-500 dark:text-gray-400">ใช้กับ Waveform style</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 6: Metadata --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <span class="w-7 h-7 rounded-full bg-indigo-600 text-white text-sm flex items-center justify-center mr-3">6</span>
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
