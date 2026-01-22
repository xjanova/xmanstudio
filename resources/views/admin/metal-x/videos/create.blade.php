@extends('layouts.admin')

@section('title', 'เพิ่มวิดีโอ Metal-X')
@section('page-title', 'เพิ่มวิดีโอใหม่')

@section('content')
<div class="max-w-4xl">
    <form action="{{ route('admin.metal-x.videos.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                @if($isApiConfigured)
                    นำเข้าจาก YouTube
                @else
                    เพิ่มวิดีโอ
                @endif
            </h3>

            @if($isApiConfigured)
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-green-700">
                        <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        YouTube API เชื่อมต่อแล้ว - ระบบจะดึงข้อมูลวิดีโออัตโนมัติ
                    </p>
                </div>
            @else
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-yellow-700">
                        <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        YouTube API ยังไม่ได้ตั้งค่า - กรุณากรอกข้อมูลด้วยตัวเอง
                    </p>
                </div>
            @endif

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">YouTube URL หรือ Video ID <span class="text-red-500">*</span></label>
                    <input type="text" name="youtube_url" value="{{ old('youtube_url') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="https://www.youtube.com/watch?v=xxxxx หรือ xxxxx">
                    <p class="mt-1 text-sm text-gray-500">วาง URL ของวิดีโอ YouTube หรือ Video ID</p>
                    @error('youtube_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if(!$isApiConfigured)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อวิดีโอ (EN)</label>
                            <input type="text" name="title" value="{{ old('title') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อวิดีโอ (TH)</label>
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
                @else
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อวิดีโอ (TH) - เพิ่มเติม</label>
                        <input type="text" name="title_th" value="{{ old('title_th') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                               placeholder="(ไม่บังคับ) ชื่อภาษาไทย">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด (TH) - เพิ่มเติม</label>
                        <textarea name="description_th" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                  placeholder="(ไม่บังคับ) รายละเอียดภาษาไทย">{{ old('description_th') }}</textarea>
                    </div>
                @endif
            </div>
        </div>

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
            <a href="{{ route('admin.metal-x.videos.index') }}"
               class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">ยกเลิก</a>
            <button type="submit"
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                @if($isApiConfigured)
                    นำเข้าวิดีโอ
                @else
                    บันทึก
                @endif
            </button>
        </div>
    </form>
</div>
@endsection
