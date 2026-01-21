@extends('layouts.admin')

@section('title', 'Edit Banner')
@section('page-title', 'แก้ไขแบนเนอร์')

@section('content')
<div class="space-y-6">
    <div class="flex items-center space-x-4">
        <a href="{{ route('admin.banners.index') }}" class="text-gray-600 hover:text-gray-900">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <h2 class="text-xl font-semibold">กลับไปรายการแบนเนอร์</h2>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <div class="font-semibold mb-2">กรุณาแก้ไขข้อผิดพลาด:</div>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.banners.update', $banner) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Info -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลพื้นฐาน</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อแบนเนอร์ *</label>
                    <input type="text" name="title" value="{{ old('title', $banner->title) }}"
                           class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">รูปภาพแบนเนอร์</label>
                    <input type="file" name="image" id="image-upload" accept="image/*"
                           class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500 mb-3">
                    <p class="text-xs text-gray-500 mb-3">อัปโหลดรูปใหม่หากต้องการเปลี่ยน | รองรับ: JPG, PNG, GIF, WebP (สูงสุด 5MB)</p>

                    <!-- Banner Cropper -->
                    <div id="banner-cropper-container"></div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ลิงก์ URL</label>
                    <input type="url" name="link_url" value="{{ old('link_url', $banner->link_url) }}"
                           class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500"
                           placeholder="https://example.com">
                    <p class="text-xs text-gray-500 mt-1">URL ที่จะเปิดเมื่อคลิกแบนเนอร์</p>
                </div>

                <div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="target_blank" value="1" class="sr-only peer" {{ old('target_blank', $banner->target_blank) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900">เปิดลิงก์ในแท็บใหม่</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">คำอธิบาย</label>
                    <textarea name="description" rows="2"
                              class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500">{{ old('description', $banner->description) }}</textarea>
                </div>

                <div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="enabled" value="1" class="sr-only peer" {{ old('enabled', $banner->enabled) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900">เปิดใช้งานแบนเนอร์นี้</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Position & Pages -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ตำแหน่งและหน้าที่แสดง</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ตำแหน่งแบนเนอร์ *</label>
                    <select name="position" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500" required>
                        <option value="header" {{ old('position', $banner->position) === 'header' ? 'selected' : '' }}>Header Top</option>
                        <option value="sidebar" {{ old('position', $banner->position) === 'sidebar' ? 'selected' : '' }}>Sidebar</option>
                        <option value="in-content" {{ old('position', $banner->position) === 'in-content' ? 'selected' : '' }}>In Content</option>
                        <option value="footer" {{ old('position', $banner->position) === 'footer' ? 'selected' : '' }}>Footer Above</option>
                        <option value="between-products" {{ old('position', $banner->position) === 'between-products' ? 'selected' : '' }}>Between Products</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">หน้าที่จะแสดงแบนเนอร์ *</label>
                    <div class="space-y-2">
                        @php
                            $selectedPages = old('pages', $banner->pages ?? []);
                        @endphp
                        <label class="flex items-center">
                            <input type="checkbox" name="pages[]" value="all" {{ in_array('all', $selectedPages) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-gray-700">ทุกหน้า</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="pages[]" value="home" {{ in_array('home', $selectedPages) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-gray-700">หน้าแรก</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="pages[]" value="products" {{ in_array('products', $selectedPages) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-gray-700">หน้าผลิตภัณฑ์</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="pages[]" value="services" {{ in_array('services', $selectedPages) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-gray-700">หน้าบริการ</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="pages[]" value="support" {{ in_array('support', $selectedPages) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-gray-700">หน้าสนับสนุน/ใบเสนอราคา</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="pages[]" value="rental" {{ in_array('rental', $selectedPages) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-gray-700">หน้าแพ็กเกจเช่า</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Priority (ลำดับความสำคัญ)</label>
                    <input type="number" name="priority" value="{{ old('priority', $banner->priority) }}" min="0" max="100"
                           class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500">
                    <p class="text-xs text-gray-500 mt-1">ตัวเลขที่สูงกว่าจะแสดงก่อน (0-100)</p>
                </div>
            </div>
        </div>

        <!-- Display Schedule -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">กำหนดเวลาการแสดงผล</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">วันที่เริ่มแสดง</label>
                    <input type="datetime-local" name="start_date"
                           value="{{ old('start_date', $banner->start_date ? $banner->start_date->format('Y-m-d\TH:i') : '') }}"
                           class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500">
                    <p class="text-xs text-gray-500 mt-1">เว้นว่างหากต้องการแสดงทันที</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">วันที่สิ้นสุด</label>
                    <input type="datetime-local" name="end_date"
                           value="{{ old('end_date', $banner->end_date ? $banner->end_date->format('Y-m-d\TH:i') : '') }}"
                           class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500">
                    <p class="text-xs text-gray-500 mt-1">เว้นว่างหากไม่มีวันสิ้นสุด</p>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">สถิติการแสดงผล</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="text-sm text-blue-600 font-medium">Views (การแสดงผล)</div>
                    <div class="text-2xl font-bold text-blue-900 mt-1">{{ number_format($banner->views) }}</div>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="text-sm text-green-600 font-medium">Clicks (การคลิก)</div>
                    <div class="text-2xl font-bold text-green-900 mt-1">{{ number_format($banner->clicks) }}</div>
                </div>
                <div class="bg-purple-50 rounded-lg p-4">
                    <div class="text-sm text-purple-600 font-medium">CTR (Click-Through Rate)</div>
                    <div class="text-2xl font-bold text-purple-900 mt-1">{{ $banner->ctr }}%</div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('admin.banners.index') }}"
               class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 transition-colors">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                บันทึกการเปลี่ยนแปลง
            </button>
        </div>
    </form>
</div>

<script src="{{ asset('js/banner-cropper.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize banner cropper
    bannerCropper = new BannerCropper('banner-cropper-container', {
        aspectRatio: 16/9,
        minWidth: 800,
        minHeight: 400
    });

    // Load existing image and crop data
    const existingImageUrl = '{{ $banner->image_url }}';
    const existingCropData = @json($banner->crop_data);
    const displayWidth = {{ $banner->display_width ?? 1200 }};
    const displayHeight = {{ $banner->display_height ?? 630 }};

    if (existingImageUrl) {
        bannerCropper.loadExistingCrop(existingImageUrl, existingCropData);

        // Set display size inputs
        if (displayWidth && displayHeight) {
            document.getElementById('display-width-input').value = displayWidth;
            document.getElementById('display-height-input').value = displayHeight;
        }
    }

    // Handle new image upload
    document.getElementById('image-upload').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            bannerCropper.loadImage(file);
        }
    });
});
</script>
@endsection
