@extends('layouts.admin')

@section('title', 'แก้ไขวิดีโอ')
@section('page-title', 'แก้ไขวิดีโอ')

@section('content')
<div class="max-w-4xl">
    <!-- Video Preview -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex flex-col md:flex-row gap-6">
            <div class="md:w-1/3">
                <div class="aspect-video bg-gray-100 rounded-lg overflow-hidden">
                    @if($video->best_thumbnail)
                        <img src="{{ $video->best_thumbnail }}" alt="{{ $video->title }}"
                             class="w-full h-full object-cover">
                    @endif
                </div>
                <div class="mt-3 text-center">
                    <a href="{{ $video->youtube_url }}" target="_blank"
                       class="inline-flex items-center text-red-600 hover:text-red-700">
                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                        </svg>
                        ดูบน YouTube
                    </a>
                </div>
            </div>
            <div class="md:w-2/3">
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-2xl font-bold text-gray-900">{{ $video->formatted_view_count }}</p>
                        <p class="text-sm text-gray-500">ยอดวิว</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-2xl font-bold text-gray-900">{{ $video->formatted_like_count }}</p>
                        <p class="text-sm text-gray-500">ไลค์</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($video->comment_count) }}</p>
                        <p class="text-sm text-gray-500">คอมเมนต์</p>
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-500">
                    <p><strong>YouTube ID:</strong> {{ $video->youtube_id }}</p>
                    <p><strong>ความยาว:</strong> {{ $video->formatted_duration }}</p>
                    <p><strong>เผยแพร่:</strong> {{ $video->published_at?->format('d M Y H:i') ?? 'ไม่ระบุ' }}</p>
                    <p><strong>Sync ล่าสุด:</strong> {{ $video->synced_at?->format('d M Y H:i') ?? 'ไม่เคย' }}</p>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.metal-x.videos.update', $video) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลวิดีโอ</h3>

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อวิดีโอ (EN) <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $video->title) }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อวิดีโอ (TH)</label>
                        <input type="text" name="title_th" value="{{ old('title_th', $video->title_th) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด (EN)</label>
                    <textarea name="description" rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('description', $video->description) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด (TH)</label>
                    <textarea name="description_th" rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('description_th', $video->description_th) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">หมวดหมู่</label>
                    <input type="text" name="category" value="{{ old('category', $video->category) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="เช่น Music Video, Cover, Live Performance">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ตั้งค่าการแสดงผล</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ลำดับ</label>
                    <input type="number" name="order" value="{{ old('order', $video->order) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div class="flex items-end space-x-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $video->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">เปิดใช้งาน</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $video->is_featured) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">แนะนำ</span>
                    </label>
                </div>
            </div>
        </div>

        @if($video->tags && count($video->tags) > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Tags</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($video->tags as $tag)
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex justify-end space-x-3">
            <a href="{{ route('admin.metal-x.videos.index') }}"
               class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">ยกเลิก</a>
            <button type="submit"
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">บันทึก</button>
        </div>
    </form>
</div>
@endsection
