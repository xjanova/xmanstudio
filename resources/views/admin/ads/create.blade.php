@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Create Ad Placement')
@section('page-title', 'เพิ่มตำแหน่งโฆษณาใหม่')

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
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-cyan-600 via-teal-600 to-emerald-500 p-8 shadow-2xl mb-8">
    <div class="absolute top-0 left-0 w-72 h-72 bg-cyan-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
    <div class="absolute top-0 right-0 w-72 h-72 bg-teal-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-8 left-20 w-72 h-72 bg-emerald-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
    <div class="relative">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.ads.index') }}" class="p-2 bg-white/20 backdrop-blur-sm rounded-xl hover:bg-white/30 transition-all">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h2 class="text-3xl font-bold text-white mb-2">เพิ่มตำแหน่งโฆษณาใหม่</h2>
                    <p class="text-cyan-100">สร้างตำแหน่งโฆษณา Google AdSense</p>
                </div>
            </div>
        </div>
    </div>
</div>

<form action="{{ route('admin.ads.store') }}" method="POST" class="space-y-6">
    @csrf

    <!-- Basic Info -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
            <span class="w-8 h-8 bg-gradient-to-r from-cyan-500 to-teal-500 rounded-lg flex items-center justify-center mr-3">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>
            ข้อมูลพื้นฐาน
        </h3>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อตำแหน่ง *</label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 dark:bg-gray-700 dark:text-white transition-all" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Slug (URL-friendly name) *</label>
                <input type="text" name="slug" value="{{ old('slug') }}"
                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 dark:bg-gray-700 dark:text-white transition-all" required>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ใช้ตัวอักษรภาษาอังกฤษ ตัวเลข และ - เท่านั้น (เช่น header-top)</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คำอธิบาย</label>
                <textarea name="description" rows="2"
                          class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 dark:bg-gray-700 dark:text-white transition-all">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="enabled" value="1" class="sr-only peer" {{ old('enabled') ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-cyan-300 dark:peer-focus:ring-cyan-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-cyan-500 peer-checked:to-teal-500"></div>
                    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">เปิดใช้งานโฆษณานี้</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Position & Pages -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
            <span class="w-8 h-8 bg-gradient-to-r from-teal-500 to-emerald-500 rounded-lg flex items-center justify-center mr-3">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                </svg>
            </span>
            ตำแหน่งและหน้าที่แสดง
        </h3>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ตำแหน่งโฆษณา *</label>
                <select name="position" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 dark:bg-gray-700 dark:text-white transition-all" required>
                    <option value="header" {{ old('position') === 'header' ? 'selected' : '' }}>Header Top</option>
                    <option value="sidebar" {{ old('position') === 'sidebar' ? 'selected' : '' }}>Sidebar</option>
                    <option value="in-content" {{ old('position') === 'in-content' ? 'selected' : '' }}>In Content</option>
                    <option value="footer" {{ old('position') === 'footer' ? 'selected' : '' }}>Footer Above</option>
                    <option value="between-products" {{ old('position') === 'between-products' ? 'selected' : '' }}>Between Products</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หน้าที่จะแสดงโฆษณา *</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @php
                        $selectedPages = old('pages', []);
                    @endphp
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer">
                        <input type="checkbox" name="pages[]" value="all" {{ in_array('all', $selectedPages) ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-cyan-600 focus:ring-cyan-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ทุกหน้า</span>
                    </label>
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer">
                        <input type="checkbox" name="pages[]" value="home" {{ in_array('home', $selectedPages) ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-cyan-600 focus:ring-cyan-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">หน้าแรก</span>
                    </label>
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer">
                        <input type="checkbox" name="pages[]" value="products" {{ in_array('products', $selectedPages) ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-cyan-600 focus:ring-cyan-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">หน้าผลิตภัณฑ์</span>
                    </label>
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer">
                        <input type="checkbox" name="pages[]" value="services" {{ in_array('services', $selectedPages) ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-cyan-600 focus:ring-cyan-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">หน้าบริการ</span>
                    </label>
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer">
                        <input type="checkbox" name="pages[]" value="support" {{ in_array('support', $selectedPages) ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-cyan-600 focus:ring-cyan-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">หน้าสนับสนุน</span>
                    </label>
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer">
                        <input type="checkbox" name="pages[]" value="rental" {{ in_array('rental', $selectedPages) ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-cyan-600 focus:ring-cyan-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">หน้าแพ็กเกจเช่า</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Priority (ลำดับความสำคัญ)</label>
                <input type="number" name="priority" value="{{ old('priority', 5) }}"
                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 dark:bg-gray-700 dark:text-white transition-all">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ตัวเลขที่สูงกว่าจะแสดงก่อน (0-10)</p>
            </div>
        </div>
    </div>

    <!-- Ad Code -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
            <span class="w-8 h-8 bg-gradient-to-r from-emerald-500 to-green-500 rounded-lg flex items-center justify-center mr-3">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                </svg>
            </span>
            Google AdSense Code
        </h3>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">โค้ดโฆษณา</label>
            <textarea name="code" rows="10"
                      class="w-full font-mono text-sm px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 dark:bg-gray-700 dark:text-white transition-all"
                      placeholder="<script async src='https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXXXXXXXXXX'&#10;     crossorigin='anonymous'></script>&#10;<ins class='adsbygoogle'&#10;     style='display:block'&#10;     data-ad-client='ca-pub-XXXXXXXXXXXXXXXX'&#10;     data-ad-slot='XXXXXXXXXX'&#10;     data-ad-format='auto'&#10;     data-full-width-responsive='true'></ins>&#10;<script>&#10;     (adsbygoogle = window.adsbygoogle || []).push({});&#10;</script>">{{ old('code') }}</textarea>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">วางโค้ดโฆษณาจาก Google AdSense</p>
        </div>
    </div>

    <!-- Submit -->
    <div class="flex justify-end space-x-3">
        <a href="{{ route('admin.ads.index') }}"
           class="px-6 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
            ยกเลิก
        </a>
        <button type="submit"
                class="px-6 py-2.5 bg-gradient-to-r from-cyan-500 to-teal-500 text-white rounded-xl hover:from-cyan-600 hover:to-teal-600 transition-all shadow-lg flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            สร้างตำแหน่งโฆษณา
        </button>
    </div>
</form>
@endsection
