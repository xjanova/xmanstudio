@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Create Banner')
@section('page-title', 'เพิ่มแบนเนอร์ใหม่')

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
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-500 p-8 shadow-2xl mb-8">
    <div class="absolute top-0 left-0 w-72 h-72 bg-violet-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
    <div class="absolute top-0 right-0 w-72 h-72 bg-purple-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-8 left-20 w-72 h-72 bg-fuchsia-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
    <div class="relative">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.banners.index') }}" class="p-2 bg-white/20 backdrop-blur-sm rounded-xl hover:bg-white/30 transition-all">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h2 class="text-3xl font-bold text-white mb-2">เพิ่มแบนเนอร์ใหม่</h2>
                    <p class="text-violet-100">สร้างแบนเนอร์โปรโมชั่นสำหรับเว็บไซต์</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Error Messages -->
@if ($errors->any())
    <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-6 py-4 rounded-2xl mb-6">
        <div class="font-semibold mb-2 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            กรุณาแก้ไขข้อผิดพลาด:
        </div>
        <ul class="list-disc list-inside ml-7">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf

    <!-- Basic Info -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
            <span class="w-8 h-8 bg-gradient-to-r from-violet-500 to-purple-500 rounded-lg flex items-center justify-center mr-3">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>
            ข้อมูลพื้นฐาน
        </h3>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อแบนเนอร์ *</label>
                <input type="text" name="title" value="{{ old('title') }}"
                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 dark:bg-gray-700 dark:text-white transition-all" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปภาพแบนเนอร์ *</label>
                <input type="file" name="image" id="image-upload" accept="image/*" required
                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 dark:bg-gray-700 dark:text-white transition-all mb-3">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">รองรับ: JPG, PNG, GIF, WebP (สูงสุด 5MB)</p>

                <!-- Banner Cropper -->
                <div id="banner-cropper-container"></div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ลิงก์ URL</label>
                <input type="url" name="link_url" value="{{ old('link_url') }}"
                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 dark:bg-gray-700 dark:text-white transition-all"
                       placeholder="https://example.com">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">URL ที่จะเปิดเมื่อคลิกแบนเนอร์</p>
            </div>

            <div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="target_blank" value="1" class="sr-only peer" {{ old('target_blank', true) ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-violet-300 dark:peer-focus:ring-violet-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-violet-500 peer-checked:to-purple-500"></div>
                    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">เปิดลิงก์ในแท็บใหม่</span>
                </label>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คำอธิบาย</label>
                <textarea name="description" rows="2"
                          class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 dark:bg-gray-700 dark:text-white transition-all">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="enabled" value="1" class="sr-only peer" {{ old('enabled') ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-violet-300 dark:peer-focus:ring-violet-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-violet-500 peer-checked:to-purple-500"></div>
                    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">เปิดใช้งานแบนเนอร์นี้</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Position & Pages -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
            <span class="w-8 h-8 bg-gradient-to-r from-purple-500 to-fuchsia-500 rounded-lg flex items-center justify-center mr-3">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                </svg>
            </span>
            ตำแหน่งและหน้าที่แสดง
        </h3>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ตำแหน่งแบนเนอร์ *</label>
                <select name="position" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 dark:bg-gray-700 dark:text-white transition-all" required>
                    <option value="header" {{ old('position') === 'header' ? 'selected' : '' }}>Header Top</option>
                    <option value="sidebar" {{ old('position') === 'sidebar' ? 'selected' : '' }}>Sidebar</option>
                    <option value="in-content" {{ old('position') === 'in-content' ? 'selected' : '' }}>In Content</option>
                    <option value="footer" {{ old('position') === 'footer' ? 'selected' : '' }}>Footer Above</option>
                    <option value="between-products" {{ old('position') === 'between-products' ? 'selected' : '' }}>Between Products</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หน้าที่จะแสดงแบนเนอร์ *</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @php
                        $selectedPages = old('pages', []);
                    @endphp
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer">
                        <input type="checkbox" name="pages[]" value="all" {{ in_array('all', $selectedPages) ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-violet-600 focus:ring-violet-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ทุกหน้า</span>
                    </label>
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer">
                        <input type="checkbox" name="pages[]" value="home" {{ in_array('home', $selectedPages) ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-violet-600 focus:ring-violet-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">หน้าแรก</span>
                    </label>
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer">
                        <input type="checkbox" name="pages[]" value="products" {{ in_array('products', $selectedPages) ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-violet-600 focus:ring-violet-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">หน้าผลิตภัณฑ์</span>
                    </label>
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer">
                        <input type="checkbox" name="pages[]" value="services" {{ in_array('services', $selectedPages) ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-violet-600 focus:ring-violet-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">หน้าบริการ</span>
                    </label>
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer">
                        <input type="checkbox" name="pages[]" value="support" {{ in_array('support', $selectedPages) ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-violet-600 focus:ring-violet-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">หน้าสนับสนุน</span>
                    </label>
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer">
                        <input type="checkbox" name="pages[]" value="rental" {{ in_array('rental', $selectedPages) ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-violet-600 focus:ring-violet-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">หน้าแพ็กเกจเช่า</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Priority (ลำดับความสำคัญ)</label>
                <input type="number" name="priority" value="{{ old('priority', 5) }}" min="0" max="100"
                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 dark:bg-gray-700 dark:text-white transition-all">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ตัวเลขที่สูงกว่าจะแสดงก่อน (0-100)</p>
            </div>
        </div>
    </div>

    <!-- Display Schedule -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
            <span class="w-8 h-8 bg-gradient-to-r from-fuchsia-500 to-pink-500 rounded-lg flex items-center justify-center mr-3">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>
            กำหนดเวลาการแสดงผล
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่เริ่มแสดง</label>
                <input type="datetime-local" name="start_date" value="{{ old('start_date') }}"
                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 dark:bg-gray-700 dark:text-white transition-all">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เว้นว่างหากต้องการแสดงทันที</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่สิ้นสุด</label>
                <input type="datetime-local" name="end_date" value="{{ old('end_date') }}"
                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 dark:bg-gray-700 dark:text-white transition-all">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เว้นว่างหากไม่มีวันสิ้นสุด</p>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div class="flex justify-end space-x-3">
        <a href="{{ route('admin.banners.index') }}"
           class="px-6 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
            ยกเลิก
        </a>
        <button type="submit"
                class="px-6 py-2.5 bg-gradient-to-r from-violet-500 to-purple-500 text-white rounded-xl hover:from-violet-600 hover:to-purple-600 transition-all shadow-lg flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            สร้างแบนเนอร์
        </button>
    </div>
</form>

<script src="{{ asset('js/banner-cropper.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize banner cropper
    bannerCropper = new BannerCropper('banner-cropper-container', {
        aspectRatio: 16/9,
        minWidth: 800,
        minHeight: 400
    });

    // Handle image upload
    document.getElementById('image-upload').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            bannerCropper.loadImage(file);
        }
    });
});
</script>
@endsection
