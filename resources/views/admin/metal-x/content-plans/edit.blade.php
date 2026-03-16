@extends($adminLayout ?? 'layouts.admin')

@section('title', 'แก้ไขแผนเนื้อหา: ' . $plan->name)
@section('page-title', 'แก้ไขแผนเนื้อหา')

@section('content')
@php
    $ts = $plan->template_settings ?? [];
    $eq = $plan->eq_settings ?? [];
@endphp

<div class="max-w-4xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('admin.metal-x.content-plans.index') }}" class="text-sm text-teal-600 dark:text-teal-400 hover:underline flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            กลับไปรายการแผนเนื้อหา
        </a>
    </div>

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-red-500 mt-0.5 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <p class="text-sm font-medium text-red-800 dark:text-red-200">กรุณาตรวจสอบข้อมูล</p>
                    <ul class="mt-1 text-xs text-red-700 dark:text-red-300 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Plan Info Header --}}
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $plan->name }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                สร้างเมื่อ {{ $plan->created_at->format('d/m/Y H:i') }}
                -- สร้างวิดีโอแล้ว {{ $plan->total_generated ?? 0 }} ตัว
            </p>
        </div>
        <div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $plan->is_enabled ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">
                {{ $plan->is_enabled ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
            </span>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.metal-x.content-plans.update', $plan) }}" class="space-y-6" x-data="contentPlanForm()">
        @csrf
        @method('PUT')

        {{-- Section 1: Channel & Name --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <span class="w-7 h-7 rounded-full bg-teal-600 text-white text-sm flex items-center justify-center mr-3">1</span>
                ช่องและชื่อแผน
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ช่อง YouTube <span class="text-red-500">*</span></label>
                    @if($channels->isEmpty())
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">ยังไม่มีช่องที่เชื่อมต่อ</p>
                        </div>
                    @else
                        <select name="channel_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                            <option value="">-- เลือกช่อง --</option>
                            @foreach($channels as $channel)
                                <option value="{{ $channel->id }}" {{ old('channel_id', $plan->channel_id) == $channel->id ? 'selected' : '' }}>
                                    {{ $channel->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                    @error('channel_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อแผน <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $plan->name) }}" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm" placeholder="เช่น: Cyberpunk Metal Daily">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Section 2: Content Topic --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <span class="w-7 h-7 rounded-full bg-teal-600 text-white text-sm flex items-center justify-center mr-3">2</span>
                หัวข้อเนื้อหา
            </h3>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Topic Prompt <span class="text-red-500">*</span></label>
                <textarea name="topic_prompt" rows="4" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm" placeholder="อธิบายธีม/หัวข้อของวิดีโอ">{{ old('topic_prompt', $plan->topic_prompt) }}</textarea>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ใช้สำหรับ AI สร้างชื่อ คำอธิบาย และ tags ให้วิดีโอแต่ละตัวโดยอัตโนมัติ</p>
                @error('topic_prompt')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Section 3: Music Settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <span class="w-7 h-7 rounded-full bg-teal-600 text-white text-sm flex items-center justify-center mr-3">3</span>
                ตั้งค่าเพลง
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Music Prompt</label>
                    <textarea name="music_prompt" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm" placeholder="เช่น: heavy metal, dark atmosphere, aggressive drums, distorted guitar">{{ old('music_prompt', $plan->music_prompt) }}</textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">คำอธิบายเพลงสำหรับ AI สร้างเพลง (ถ้าว่าง จะใช้ topic_prompt แทน)</p>
                    @error('music_prompt')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สไตล์เพลง <span class="text-red-500">*</span></label>
                    <select name="music_style" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        @foreach($musicStyles as $key => $label)
                            <option value="{{ $key }}" {{ old('music_style', $plan->music_style) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('music_style')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความยาวเพลง (วินาที) <span class="text-red-500">*</span></label>
                    <input type="number" name="music_duration" value="{{ old('music_duration', $plan->music_duration) }}" required min="30" max="300" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">30 - 300 วินาที</p>
                    @error('music_duration')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Section 4: Media Settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <span class="w-7 h-7 rounded-full bg-teal-600 text-white text-sm flex items-center justify-center mr-3">4</span>
                ตั้งค่าสื่อ
            </h3>
            <div class="space-y-4">
                {{-- Media Mode --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภทสื่อ <span class="text-red-500">*</span></label>
                    <div class="flex flex-wrap gap-4">
                        @foreach($mediaModes as $key => $label)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="media_mode" value="{{ $key }}" {{ old('media_mode', $plan->media_mode) === $key ? 'checked' : '' }} class="text-teal-600 focus:ring-teal-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('media_mode')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Media Count --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำนวนสื่อต่อวิดีโอ <span class="text-red-500">*</span></label>
                        <input type="number" name="media_count" value="{{ old('media_count', $plan->media_count) }}" required min="3" max="30" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">3 - 30 ชิ้น</p>
                        @error('media_count')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    {{-- Media Pool Tags --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">แท็กสื่อ (ตัวกรอง)</label>
                        <input type="text" name="media_pool_tags" value="{{ old('media_pool_tags', is_array($plan->media_pool_tags) ? implode(', ', $plan->media_pool_tags) : $plan->media_pool_tags) }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm" placeholder="cyberpunk, neon, dark (คั่นด้วยจุลภาค)">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">คั่นด้วยจุลภาค -- ถ้าว่างจะใช้สื่อทั้งหมด</p>
                        @error('media_pool_tags')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                @if($availableTags->count() > 0)
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">แท็กที่มีในคลัง:</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach($availableTags as $tag)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">{{ $tag }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Section 5: Template Settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <span class="w-7 h-7 rounded-full bg-teal-600 text-white text-sm flex items-center justify-center mr-3">5</span>
                ตั้งค่า Template
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Template <span class="text-red-500">*</span></label>
                    <select name="template" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        @foreach($templates as $key => $label)
                            <option value="{{ $key }}" {{ old('template', $plan->template) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('template')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ระยะเวลาสไลด์ (วินาที)</label>
                    <input type="number" name="slide_duration" value="{{ old('slide_duration', $ts['slide_duration'] ?? 5) }}" min="2" max="15" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    @error('slide_duration')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Transition</label>
                    <select name="transition" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        <option value="crossfade" {{ old('transition', $ts['transition'] ?? 'crossfade') === 'crossfade' ? 'selected' : '' }}>Crossfade</option>
                        <option value="fade_black" {{ old('transition', $ts['transition'] ?? '') === 'fade_black' ? 'selected' : '' }}>Fade to Black</option>
                        <option value="slide_left" {{ old('transition', $ts['transition'] ?? '') === 'slide_left' ? 'selected' : '' }}>Slide Left</option>
                        <option value="slide_right" {{ old('transition', $ts['transition'] ?? '') === 'slide_right' ? 'selected' : '' }}>Slide Right</option>
                        <option value="zoom_in" {{ old('transition', $ts['transition'] ?? '') === 'zoom_in' ? 'selected' : '' }}>Zoom In</option>
                        <option value="none" {{ old('transition', $ts['transition'] ?? '') === 'none' ? 'selected' : '' }}>None</option>
                    </select>
                    @error('transition')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Transition Duration (วินาที)</label>
                    <input type="number" name="transition_duration" value="{{ old('transition_duration', $ts['transition_duration'] ?? 1) }}" min="0.3" max="3" step="0.1" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    @error('transition_duration')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Effect</label>
                    <select name="effect" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        <option value="ken_burns" {{ old('effect', $ts['effect'] ?? 'ken_burns') === 'ken_burns' ? 'selected' : '' }}>Ken Burns</option>
                        <option value="zoom_slow" {{ old('effect', $ts['effect'] ?? '') === 'zoom_slow' ? 'selected' : '' }}>Zoom Slow</option>
                        <option value="pan" {{ old('effect', $ts['effect'] ?? '') === 'pan' ? 'selected' : '' }}>Pan</option>
                        <option value="none" {{ old('effect', $ts['effect'] ?? '') === 'none' ? 'selected' : '' }}>None</option>
                    </select>
                    @error('effect')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Background Color</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="background_color" value="{{ old('background_color', $ts['background_color'] ?? '#000000') }}" class="w-10 h-10 rounded border border-gray-300 dark:border-gray-600 cursor-pointer">
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ old('background_color', $ts['background_color'] ?? '#000000') }}</span>
                    </div>
                    @error('background_color')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Section 6: Visual EQ --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <span class="w-7 h-7 rounded-full bg-teal-600 text-white text-sm flex items-center justify-center mr-3">6</span>
                Visual EQ Overlay
            </h3>
            <div class="space-y-4">
                {{-- EQ Toggle --}}
                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="eq_enabled" value="0">
                        <input type="checkbox" name="eq_enabled" value="1" x-model="eqEnabled" {{ old('eq_enabled', !empty($eq['enabled'])) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-300 dark:bg-gray-600 peer-focus:ring-2 peer-focus:ring-teal-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600"></div>
                    </label>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เปิด Visual EQ</span>
                </div>

                {{-- EQ Settings (shown when enabled) --}}
                <div x-show="eqEnabled" x-collapse class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">EQ Style</label>
                        <select name="eq_style" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                            @foreach($eqStyles as $key => $label)
                                <option value="{{ $key }}" {{ old('eq_style', $eq['style'] ?? 'showcqt') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ตำแหน่ง</label>
                        <select name="eq_position" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                            <option value="bottom" {{ old('eq_position', $eq['position'] ?? 'bottom') === 'bottom' ? 'selected' : '' }}>ล่าง (Bottom)</option>
                            <option value="top" {{ old('eq_position', $eq['position'] ?? '') === 'top' ? 'selected' : '' }}>บน (Top)</option>
                            <option value="center" {{ old('eq_position', $eq['position'] ?? '') === 'center' ? 'selected' : '' }}>กลาง (Center)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความสูง: <span x-text="eqHeight"></span>%</label>
                        <input type="range" name="eq_height_percent" x-model="eqHeight" min="10" max="50" value="{{ old('eq_height_percent', $eq['height_percent'] ?? 20) }}" class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-teal-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความโปร่งใส: <span x-text="eqOpacity"></span></label>
                        <input type="range" name="eq_opacity" x-model="eqOpacity" min="0.1" max="1.0" step="0.1" value="{{ old('eq_opacity', $eq['opacity'] ?? '0.7') }}" class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-teal-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สี EQ</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="eq_color" value="{{ old('eq_color', $eq['color'] ?? '#00ff88') }}" class="w-10 h-10 rounded border border-gray-300 dark:border-gray-600 cursor-pointer">
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ old('eq_color', $eq['color'] ?? '#00ff88') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 7: Schedule --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <span class="w-7 h-7 rounded-full bg-teal-600 text-white text-sm flex items-center justify-center mr-3">7</span>
                ตารางเวลา
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Frequency --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความถี่ในการสร้าง <span class="text-red-500">*</span></label>
                    <select name="schedule_frequency_hours" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        @foreach($frequencyPresets as $hours => $label)
                            <option value="{{ $hours }}" {{ old('schedule_frequency_hours', $plan->schedule_frequency_hours) == $hours ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('schedule_frequency_hours')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                {{-- Preferred Hour --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เวลาเผยแพร่ <span class="text-red-500">*</span></label>
                    <select name="preferred_publish_hour" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        @for($h = 0; $h < 24; $h++)
                            <option value="{{ $h }}" {{ old('preferred_publish_hour', $plan->preferred_publish_hour) == $h ? 'selected' : '' }}>{{ sprintf('%02d:00', $h) }}</option>
                        @endfor
                    </select>
                    @error('preferred_publish_hour')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                {{-- Preferred Days --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่ต้องการเผยแพร่ <span class="text-xs font-normal text-gray-500">(ถ้าไม่เลือก = ทุกวัน)</span></label>
                    <div class="flex flex-wrap gap-3">
                        @foreach($daysOfWeek as $dayNum => $dayName)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="preferred_publish_days[]" value="{{ $dayNum }}" {{ in_array($dayNum, old('preferred_publish_days', $plan->preferred_publish_days ?? [])) ? 'checked' : '' }} class="rounded text-teal-600 focus:ring-teal-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $dayName }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('preferred_publish_days')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                {{-- Privacy Status --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะเผยแพร่ <span class="text-red-500">*</span></label>
                    <select name="privacy_status" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        <option value="private" {{ old('privacy_status', $plan->privacy_status) === 'private' ? 'selected' : '' }}>Private</option>
                        <option value="unlisted" {{ old('privacy_status', $plan->privacy_status) === 'unlisted' ? 'selected' : '' }}>Unlisted</option>
                        <option value="public" {{ old('privacy_status', $plan->privacy_status) === 'public' ? 'selected' : '' }}>Public</option>
                    </select>
                    @error('privacy_status')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                {{-- Max Queue Size --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ขนาดคิวสูงสุด <span class="text-red-500">*</span></label>
                    <input type="number" name="max_queue_size" value="{{ old('max_queue_size', $plan->max_queue_size) }}" required min="1" max="10" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">จำนวนโปรเจกต์ที่ยังไม่เสร็จสูงสุดก่อนหยุดสร้างเพิ่ม (1-10)</p>
                    @error('max_queue_size')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('admin.metal-x.content-plans.index') }}" class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm">
                ยกเลิก
            </a>
            <button type="submit" class="px-6 py-2 bg-teal-600 text-white font-bold rounded-lg hover:bg-teal-700 text-sm">
                <svg class="w-4 h-4 inline -mt-0.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                บันทึกการเปลี่ยนแปลง
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function contentPlanForm() {
    return {
        eqEnabled: {{ old('eq_enabled', !empty($eq['enabled'])) ? 'true' : 'false' }},
        eqHeight: {{ old('eq_height_percent', $eq['height_percent'] ?? 20) }},
        eqOpacity: {{ old('eq_opacity', $eq['opacity'] ?? '0.7') }},
    };
}
</script>
@endpush
@endsection
