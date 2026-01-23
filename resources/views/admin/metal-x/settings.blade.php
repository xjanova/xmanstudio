@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ตั้งค่า Metal-X')
@section('page-title', 'ตั้งค่า Metal-X Channel')

@section('content')
<div class="max-w-4xl">
    <form action="{{ route('admin.metal-x.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูล Channel</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ Channel <span class="text-red-500">*</span></label>
                    <input type="text" name="channel_name" value="{{ old('channel_name', $settings['channel_name']) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    @error('channel_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">URL Channel <span class="text-red-500">*</span></label>
                    <input type="url" name="channel_url" value="{{ old('channel_url', $settings['channel_url']) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="https://www.youtube.com/@...">
                    @error('channel_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด Channel</label>
                    <textarea name="channel_description" rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('channel_description', $settings['channel_description']) }}</textarea>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">รูปภาพ</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">โลโก้ Channel</label>
                    @if($settings['channel_logo'])
                        <div class="mb-3">
                            <img src="{{ Storage::url($settings['channel_logo']) }}" alt="Channel Logo"
                                 class="w-24 h-24 rounded-lg object-cover">
                            <p class="mt-1 text-sm text-gray-500">โลโก้ปัจจุบัน</p>
                        </div>
                    @endif
                    <input type="file" name="channel_logo" accept="image/*"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    <p class="mt-1 text-sm text-gray-500">รองรับไฟล์รูปภาพ ขนาดไม่เกิน 2MB</p>
                    @error('channel_logo')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">แบนเนอร์ Channel</label>
                    @if($settings['channel_banner'])
                        <div class="mb-3">
                            <img src="{{ Storage::url($settings['channel_banner']) }}" alt="Channel Banner"
                                 class="w-full h-24 rounded-lg object-cover">
                            <p class="mt-1 text-sm text-gray-500">แบนเนอร์ปัจจุบัน</p>
                        </div>
                    @endif
                    <input type="file" name="channel_banner" accept="image/*"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    <p class="mt-1 text-sm text-gray-500">รองรับไฟล์รูปภาพ ขนาดไม่เกิน 2MB</p>
                    @error('channel_banner')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">YouTube API</h3>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <p class="text-sm text-blue-700">
                    ตั้งค่า YouTube API เพื่อเปิดใช้งานการ sync วิดีโออัตโนมัติ ดูสถิติ และจัดการเนื้อหา
                </p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">YouTube API Key</label>
                    <input type="text" name="youtube_api_key" value="{{ old('youtube_api_key', $settings['youtube_api_key'] ?? '') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="AIza...">
                    <p class="mt-1 text-sm text-gray-500">สร้าง API Key ได้ที่ <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-primary-600 hover:underline">Google Cloud Console</a></p>
                    @error('youtube_api_key')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Channel ID</label>
                    <input type="text" name="channel_id" value="{{ old('channel_id', $settings['channel_id'] ?? '') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="UCxxxxxxxxxxxxxxxxxx">
                    <p class="mt-1 text-sm text-gray-500">Channel ID สำหรับ sync วิดีโอจากช่อง (หา ID ได้จาก YouTube Studio)</p>
                    @error('channel_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('admin.metal-x.index') }}"
               class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">ยกเลิก</a>
            <button type="submit"
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">บันทึก</button>
        </div>
    </form>
</div>
@endsection
