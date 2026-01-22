@extends('layouts.admin')

@section('title', 'สร้างเพลย์ลิสต์ Metal-X')
@section('page-title', 'สร้างเพลย์ลิสต์ใหม่')

@section('content')
<div class="max-w-4xl">
    <form action="{{ route('admin.metal-x.playlists.store') }}" method="POST" class="space-y-6">
        @csrf

        @if($isApiConfigured)
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">นำเข้าจาก YouTube (ไม่บังคับ)</h3>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-blue-700">
                        วาง URL ของเพลย์ลิสต์ YouTube เพื่อนำเข้าอัตโนมัติ หรือเว้นว่างเพื่อสร้างเพลย์ลิสต์ใหม่ด้วยตัวเอง
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">YouTube Playlist URL</label>
                    <input type="text" name="youtube_url" value="{{ old('youtube_url') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="https://www.youtube.com/playlist?list=PLxxxxx">
                    @error('youtube_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endif

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลเพลย์ลิสต์</h3>

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อเพลย์ลิสต์ (EN) <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                               {{ $isApiConfigured ? '' : 'required' }}>
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อเพลย์ลิสต์ (TH)</label>
                        <input type="text" name="title_th" value="{{ old('title_th') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด (EN)</label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('description') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด (TH)</label>
                    <textarea name="description_th" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('description_th') }}</textarea>
                </div>
            </div>
        </div>

        @if($videos->count() > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">เลือกวิดีโอ</h3>
                <p class="text-sm text-gray-500 mb-4">เลือกวิดีโอที่ต้องการเพิ่มในเพลย์ลิสต์ (สามารถลากเพื่อเรียงลำดับได้ภายหลัง)</p>

                <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg">
                    @foreach($videos as $video)
                        <label class="flex items-center p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0">
                            <input type="checkbox" name="videos[]" value="{{ $video->id }}"
                                   {{ in_array($video->id, old('videos', [])) ? 'checked' : '' }}
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
        @endif

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ตั้งค่าการแสดงผล</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ลำดับ</label>
                    <input type="number" name="order" value="{{ old('order', 0) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div class="flex items-end space-x-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">เปิดใช้งาน</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
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
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">สร้างเพลย์ลิสต์</button>
        </div>
    </form>
</div>
@endsection
