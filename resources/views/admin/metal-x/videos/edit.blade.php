@extends($adminLayout ?? 'layouts.admin')

@section('title', 'แก้ไขวิดีโอ')
@section('page-title', 'แก้ไขวิดีโอ')

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
</style>
@endpush

@section('content')
<!-- Premium Header Banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-red-500 via-rose-500 to-pink-500 p-8 shadow-2xl mb-8">
    <div class="absolute top-0 left-0 w-72 h-72 bg-red-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
    <div class="absolute top-0 right-0 w-72 h-72 bg-rose-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
    <div class="relative">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.metal-x.videos.index') }}" class="p-2 bg-white/20 backdrop-blur-sm rounded-xl hover:bg-white/30 transition-all">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h2 class="text-3xl font-bold text-white mb-2">แก้ไขวิดีโอ</h2>
                    <p class="text-red-100 truncate max-w-md">{{ $video->title }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="max-w-4xl mx-auto">
    <!-- Video Preview -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 mb-6">
        <div class="flex flex-col md:flex-row gap-6">
            <div class="md:w-1/3">
                <div class="aspect-video bg-gray-100 dark:bg-gray-700 rounded-xl overflow-hidden">
                    @if($video->best_thumbnail)
                        <img src="{{ $video->best_thumbnail }}" alt="{{ $video->title }}"
                             class="w-full h-full object-cover">
                    @endif
                </div>
                <div class="mt-3 text-center">
                    <a href="{{ $video->youtube_url }}" target="_blank"
                       class="inline-flex items-center text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 font-medium">
                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                        </svg>
                        ดูบน YouTube
                    </a>
                </div>
            </div>
            <div class="md:w-2/3">
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-700 rounded-xl p-4">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $video->formatted_view_count }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ยอดวิว</p>
                    </div>
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-700 rounded-xl p-4">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $video->formatted_like_count }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ไลค์</p>
                    </div>
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-700 rounded-xl p-4">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($video->comment_count) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">คอมเมนต์</p>
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-500 dark:text-gray-400 space-y-1">
                    <p><strong class="text-gray-700 dark:text-gray-300">YouTube ID:</strong> {{ $video->youtube_id }}</p>
                    <p><strong class="text-gray-700 dark:text-gray-300">ความยาว:</strong> {{ $video->formatted_duration }}</p>
                    <p><strong class="text-gray-700 dark:text-gray-300">เผยแพร่:</strong> {{ $video->published_at?->format('d M Y H:i') ?? 'ไม่ระบุ' }}</p>
                    <p><strong class="text-gray-700 dark:text-gray-300">Sync ล่าสุด:</strong> {{ $video->synced_at?->format('d M Y H:i') ?? 'ไม่เคย' }}</p>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.metal-x.videos.update', $video) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Video Information -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                <span class="w-8 h-8 bg-gradient-to-r from-red-500 to-rose-500 rounded-lg flex items-center justify-center mr-3">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </span>
                ข้อมูลวิดีโอ
            </h3>

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อวิดีโอ (EN) <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $video->title) }}" required
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white transition-all @error('title') border-red-500 @enderror">
                        @error('title')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อวิดีโอ (TH)</label>
                        <input type="text" name="title_th" value="{{ old('title_th', $video->title_th) }}"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รายละเอียด (EN)</label>
                    <textarea name="description" rows="4"
                              class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white transition-all">{{ old('description', $video->description) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รายละเอียด (TH)</label>
                    <textarea name="description_th" rows="4"
                              class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white transition-all">{{ old('description_th', $video->description_th) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมวดหมู่</label>
                    <input type="text" name="category" value="{{ old('category', $video->category) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white transition-all"
                           placeholder="เช่น Music Video, Cover, Live Performance">
                </div>
            </div>
        </div>

        <!-- Display Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                <span class="w-8 h-8 bg-gradient-to-r from-rose-500 to-pink-500 rounded-lg flex items-center justify-center mr-3">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </span>
                ตั้งค่าการแสดงผล
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ลำดับ</label>
                    <input type="number" name="order" value="{{ old('order', $video->order) }}" min="0"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white transition-all">
                </div>

                <div class="flex items-end gap-4">
                    <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer flex-1">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $video->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500">
                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">เปิดใช้งาน</span>
                    </label>

                    <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer flex-1">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $video->is_featured) ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500">
                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">แนะนำ</span>
                    </label>
                </div>
            </div>
        </div>

        @if($video->tags && count($video->tags) > 0)
            <!-- Tags -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                    <span class="w-8 h-8 bg-gradient-to-r from-pink-500 to-fuchsia-500 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    </span>
                    Tags
                </h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($video->tags as $tag)
                        <span class="px-3 py-1.5 bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 text-gray-700 dark:text-gray-300 rounded-full text-sm font-medium">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Actions -->
        <div class="flex justify-end gap-4">
            <a href="{{ route('admin.metal-x.videos.index') }}"
               class="px-6 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-2.5 bg-gradient-to-r from-red-500 to-rose-500 text-white rounded-xl hover:from-red-600 hover:to-rose-600 transition-all shadow-lg flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                บันทึก
            </button>
        </div>
    </form>
</div>
@endsection
