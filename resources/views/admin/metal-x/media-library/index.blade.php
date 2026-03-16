@extends($adminLayout ?? 'layouts.admin')

@section('title', 'คลังสื่อ Media Library - Metal-X')
@section('page-title', 'คลังสื่อ Media Library')

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
    .media-thumb {
        aspect-ratio: 16/9;
        object-fit: cover;
    }
</style>
@endpush

@section('content')
<!-- Premium Header Banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-cyan-600 via-blue-700 to-blue-800 p-8 shadow-2xl mb-8">
    <div class="absolute top-0 left-0 w-72 h-72 bg-cyan-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
    <div class="absolute top-0 right-0 w-72 h-72 bg-blue-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-8 left-20 w-72 h-72 bg-indigo-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
    <div class="relative">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="text-3xl font-bold text-white mb-2">คลังสื่อ Media Library</h2>
                <p class="text-blue-200">จัดการรูปภาพและคลิปวิดีโอสำหรับ Metal-X</p>
            </div>
            <a href="{{ route('admin.metal-x.pipeline.index') }}"
               class="px-6 py-3 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all flex items-center font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับ Pipeline
            </a>
        </div>
    </div>
</div>

<!-- Success Message -->
@if(session('success'))
<div class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl p-4">
    <div class="flex items-center">
        <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="text-green-700 dark:text-green-300">{{ session('success') }}</span>
    </div>
</div>
@endif

<!-- Stats Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 hover:shadow-xl transition-shadow">
        <div class="flex items-center">
            <div class="p-3 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 text-white shadow-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">ทั้งหมด</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 hover:shadow-xl transition-shadow">
        <div class="flex items-center">
            <div class="p-3 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 text-white shadow-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">ภาพนิ่ง</p>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($stats['images']) }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 hover:shadow-xl transition-shadow">
        <div class="flex items-center">
            <div class="p-3 rounded-xl bg-gradient-to-br from-orange-500 to-red-500 text-white shadow-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">คลิปวิดีโอ</p>
                <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($stats['video_clips']) }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 hover:shadow-xl transition-shadow">
        <div class="flex items-center">
            <div class="p-3 rounded-xl bg-gradient-to-br from-green-500 to-emerald-500 text-white shadow-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">เปิดใช้งาน</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['active']) }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Upload Form -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 mb-8">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
        </svg>
        อัปโหลดสื่อใหม่
    </h3>
    <form action="{{ route('admin.metal-x.media-library.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เลือกไฟล์ (รูปภาพ/วิดีโอ)</label>
                <input type="file" name="files[]" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime"
                       class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100 dark:file:bg-cyan-900/30 dark:file:text-cyan-300" required>
                <p class="mt-1 text-xs text-gray-400">JPG, PNG, WebP, MP4, WebM, MOV (สูงสุด 100MB/ไฟล์)</p>
                @error('files') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                @error('files.*') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">แหล่งที่มา</label>
                <select name="source" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-cyan-500 focus:border-cyan-500" required>
                    <option value="freepik">Freepik</option>
                    <option value="custom">อัปโหลดเอง</option>
                    <option value="ai_generated">AI สร้าง</option>
                </select>
                @error('source') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">แท็ก (คั่นด้วย ,)</label>
                <input type="text" name="tags" placeholder="cyberpunk, neon, dark"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-cyan-500 focus:border-cyan-500">
                @error('tags') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-cyan-600 to-blue-600 text-white rounded-lg hover:from-cyan-700 hover:to-blue-700 transition-all font-medium text-sm shadow-lg">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                อัปโหลด
            </button>
        </div>
    </form>
</div>

<!-- Filters -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-4 mb-6">
    <form method="GET" action="{{ route('admin.metal-x.media-library.index') }}" class="flex flex-wrap items-center gap-3">
        <div>
            <select name="type" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-cyan-500 focus:border-cyan-500">
                <option value="">ประเภททั้งหมด</option>
                @foreach(\App\Models\MetalXMediaLibrary::TYPES as $value => $label)
                    <option value="{{ $value }}" {{ request('type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select name="source" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-cyan-500 focus:border-cyan-500">
                <option value="">แหล่งที่มาทั้งหมด</option>
                @foreach(\App\Models\MetalXMediaLibrary::SOURCES as $value => $label)
                    <option value="{{ $value }}" {{ request('source') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select name="tag" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-cyan-500 focus:border-cyan-500">
                <option value="">แท็กทั้งหมด</option>
                @foreach($allTags as $tag)
                    <option value="{{ $tag }}" {{ request('tag') == $tag ? 'selected' : '' }}>{{ $tag }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 text-sm font-medium transition-colors">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
            กรอง
        </button>
        @if(request()->hasAny(['type', 'source', 'tag']))
            <a href="{{ route('admin.metal-x.media-library.index') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 text-sm font-medium transition-colors">
                ล้างตัวกรอง
            </a>
        @endif
    </form>
</div>

<!-- Bulk Tag Form -->
<div x-data="{ selectedIds: [], showBulkTag: false }" class="mb-6">
    <div x-show="selectedIds.length > 0" x-cloak class="bg-cyan-50 dark:bg-cyan-900/30 border border-cyan-200 dark:border-cyan-800 rounded-xl p-4 mb-4">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm text-cyan-700 dark:text-cyan-300 font-medium" x-text="'เลือกแล้ว ' + selectedIds.length + ' รายการ'"></span>
            <button @click="showBulkTag = !showBulkTag" class="px-3 py-1.5 bg-cyan-600 text-white rounded-lg text-sm hover:bg-cyan-700 transition-colors">
                เพิ่มแท็กทั้งหมด
            </button>
        </div>
        <form x-show="showBulkTag" action="{{ route('admin.metal-x.media-library.bulk-tag') }}" method="POST" class="mt-3 flex items-center gap-3">
            @csrf
            <template x-for="id in selectedIds" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
            <input type="text" name="tags" placeholder="แท็กที่ต้องการเพิ่ม (คั่นด้วย ,)" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" required>
            <button type="submit" class="px-4 py-2 bg-cyan-600 text-white rounded-lg text-sm hover:bg-cyan-700">เพิ่มแท็ก</button>
        </form>
    </div>

    <!-- Media Grid -->
    @if($media->count() > 0)
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($media as $item)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-100 dark:border-gray-700 overflow-hidden group {{ !$item->is_active ? 'opacity-60' : '' }}">
            <!-- Checkbox -->
            <div class="relative">
                <div class="absolute top-2 left-2 z-10">
                    <input type="checkbox" :value="{{ $item->id }}" x-model="selectedIds"
                           class="w-4 h-4 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500">
                </div>

                <!-- Media Preview -->
                @if($item->type === 'image')
                    <img src="{{ Storage::url($item->file_path) }}" alt="{{ $item->filename }}" class="w-full media-thumb bg-gray-100 dark:bg-gray-900" loading="lazy">
                @else
                    <video class="w-full media-thumb bg-black" controls preload="metadata">
                        <source src="{{ Storage::url($item->file_path) }}" type="video/mp4">
                    </video>
                @endif

                <!-- Type Badge -->
                <div class="absolute top-2 right-2">
                    @if($item->type === 'image')
                        <span class="px-2 py-0.5 text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300 rounded-full">ภาพ</span>
                    @else
                        <span class="px-2 py-0.5 text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300 rounded-full">วิดีโอ</span>
                    @endif
                </div>
            </div>

            <!-- Info -->
            <div class="p-3">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate" title="{{ $item->filename }}">{{ $item->filename }}</p>
                <div class="flex items-center gap-2 mt-1.5">
                    <!-- Source Badge -->
                    @if($item->source === 'freepik')
                        <span class="px-1.5 py-0.5 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 rounded">Freepik</span>
                    @elseif($item->source === 'ai_generated')
                        <span class="px-1.5 py-0.5 text-xs bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300 rounded">AI</span>
                    @else
                        <span class="px-1.5 py-0.5 text-xs bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded">Custom</span>
                    @endif
                    <span class="text-xs text-gray-400">{{ $item->file_size_human }}</span>
                    @if($item->duration_seconds)
                        <span class="text-xs text-gray-400">{{ gmdate('i:s', $item->duration_seconds) }}</span>
                    @endif
                </div>

                <!-- Tags -->
                @if($item->tags && count($item->tags) > 0)
                <div class="flex flex-wrap gap-1 mt-2">
                    @foreach(array_slice($item->tags, 0, 3) as $tag)
                        <span class="px-1.5 py-0.5 text-xs bg-cyan-50 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300 rounded">{{ $tag }}</span>
                    @endforeach
                    @if(count($item->tags) > 3)
                        <span class="text-xs text-gray-400">+{{ count($item->tags) - 3 }}</span>
                    @endif
                </div>
                @endif

                <!-- Usage & Actions -->
                <div class="flex items-center justify-between mt-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <span class="text-xs text-gray-400">ใช้แล้ว {{ $item->usage_count ?? 0 }} ครั้ง</span>
                    <div class="flex items-center gap-1">
                        <!-- Edit Tags -->
                        <div x-data="{ editing: false }" class="relative">
                            <button @click="editing = !editing" class="p-1 text-gray-400 hover:text-cyan-600 transition-colors" title="แก้ไขแท็ก">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                            </button>
                            <div x-show="editing" @click.outside="editing = false" x-cloak
                                 class="absolute right-0 bottom-8 z-20 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-600 p-3">
                                <form action="{{ route('admin.metal-x.media-library.update', $item) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">แท็ก (คั่นด้วย ,)</label>
                                    <input type="text" name="tags" value="{{ $item->tags ? implode(', ', $item->tags) : '' }}"
                                           class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white mb-2">
                                    <button type="submit" class="w-full px-2 py-1 bg-cyan-600 text-white rounded text-xs hover:bg-cyan-700">บันทึก</button>
                                </form>
                            </div>
                        </div>

                        <!-- Toggle Active -->
                        <form action="{{ route('admin.metal-x.media-library.update', $item) }}" method="POST" class="inline">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="tags" value="{{ $item->tags ? implode(', ', $item->tags) : '' }}">
                            <input type="hidden" name="is_active" value="{{ $item->is_active ? '0' : '1' }}">
                            <button type="submit" class="p-1 {{ $item->is_active ? 'text-green-500 hover:text-green-700' : 'text-gray-400 hover:text-green-500' }} transition-colors" title="{{ $item->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($item->is_active)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    @endif
                                </svg>
                            </button>
                        </form>

                        <!-- Delete -->
                        <form action="{{ route('admin.metal-x.media-library.destroy', $item) }}" method="POST" class="inline"
                              onsubmit="return confirm('ยืนยันลบไฟล์นี้?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1 text-gray-400 hover:text-red-600 transition-colors" title="ลบ">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <p class="text-gray-500 dark:text-gray-400 text-lg">ยังไม่มีสื่อในคลัง</p>
        <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">อัปโหลดรูปภาพหรือคลิปวิดีโอเพื่อเริ่มต้น</p>
    </div>
    @endif

    <!-- Pagination -->
    <div class="mt-6">
        {{ $media->withQueryString()->links() }}
    </div>
</div>
@endsection
