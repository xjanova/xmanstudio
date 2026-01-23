@extends($adminLayout ?? 'layouts.admin')

@section('title', 'แก้ไขเพลย์ลิสต์')
@section('page-title', 'แก้ไขเพลย์ลิสต์: ' . $playlist->title)

@section('content')
<div class="max-w-4xl">
    <form action="{{ route('admin.metal-x.playlists.update', $playlist) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลเพลย์ลิสต์</h3>

            @if($playlist->youtube_id)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-blue-700">
                        <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                        </svg>
                        เชื่อมต่อกับ YouTube: <a href="{{ $playlist->youtube_url }}" target="_blank" class="underline">{{ $playlist->youtube_id }}</a>
                    </p>
                </div>
            @endif

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อเพลย์ลิสต์ (EN) <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $playlist->title) }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อเพลย์ลิสต์ (TH)</label>
                        <input type="text" name="title_th" value="{{ old('title_th', $playlist->title_th) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด (EN)</label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('description', $playlist->description) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด (TH)</label>
                    <textarea name="description_th" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('description_th', $playlist->description_th) }}</textarea>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">วิดีโอในเพลย์ลิสต์ ({{ count($selectedVideoIds) }} วิดีโอ)</h3>
            <p class="text-sm text-gray-500 mb-4">เลือกวิดีโอที่ต้องการในเพลย์ลิสต์</p>

            <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg">
                @foreach($allVideos as $video)
                    <label class="flex items-center p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0">
                        <input type="checkbox" name="videos[]" value="{{ $video->id }}"
                               {{ in_array($video->id, old('videos', $selectedVideoIds)) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-3">
                        <img src="{{ $video->thumbnail_url }}" alt="" class="w-16 h-10 object-cover rounded mr-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $video->title }}</p>
                            <p class="text-xs text-gray-500">{{ $video->formatted_view_count }} views</p>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ตั้งค่าการแสดงผล</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ลำดับ</label>
                    <input type="number" name="order" value="{{ old('order', $playlist->order) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div class="flex items-end space-x-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $playlist->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">เปิดใช้งาน</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $playlist->is_featured) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">แนะนำ</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('admin.metal-x.playlists.index') }}"
               class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">ยกเลิก</a>
            <button type="submit"
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">บันทึก</button>
        </div>
    </form>
</div>
@endsection
