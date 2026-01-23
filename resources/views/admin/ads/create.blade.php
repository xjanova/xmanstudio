@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Create Ad Placement')
@section('page-title', 'เพิ่มตำแหน่งโฆษณาใหม่')

@section('content')
<div class="space-y-6">
    <div class="flex items-center space-x-4">
        <a href="{{ route('admin.ads.index') }}" class="text-gray-600 hover:text-gray-900">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <h2 class="text-xl font-semibold">กลับไปรายการโฆษณา</h2>
    </div>

    <form action="{{ route('admin.ads.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Basic Info -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลพื้นฐาน</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อตำแหน่ง *</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Slug (URL-friendly name) *</label>
                    <input type="text" name="slug" value="{{ old('slug') }}"
                           class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500" required>
                    <p class="text-xs text-gray-500 mt-1">ใช้ตัวอักษรภาษาอังกฤษ ตัวเลข และ - เท่านั้น (เช่น header-top)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">คำอธิบาย</label>
                    <textarea name="description" rows="2"
                              class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500">{{ old('description') }}</textarea>
                </div>

                <div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="enabled" value="1" class="sr-only peer" {{ old('enabled') ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900">เปิดใช้งานโฆษณานี้</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Position & Pages -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ตำแหน่งและหน้าที่แสดง</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ตำแหน่งโฆษณา *</label>
                    <select name="position" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500" required>
                        <option value="header" {{ old('position') === 'header' ? 'selected' : '' }}>Header Top</option>
                        <option value="sidebar" {{ old('position') === 'sidebar' ? 'selected' : '' }}>Sidebar</option>
                        <option value="in-content" {{ old('position') === 'in-content' ? 'selected' : '' }}>In Content</option>
                        <option value="footer" {{ old('position') === 'footer' ? 'selected' : '' }}>Footer Above</option>
                        <option value="between-products" {{ old('position') === 'between-products' ? 'selected' : '' }}>Between Products</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">หน้าที่จะแสดงโฆษณา *</label>
                    <div class="space-y-2">
                        @php
                            $selectedPages = old('pages', []);
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
                    <input type="number" name="priority" value="{{ old('priority', 5) }}"
                           class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500">
                    <p class="text-xs text-gray-500 mt-1">ตัวเลขที่สูงกว่าจะแสดงก่อน (0-10)</p>
                </div>
            </div>
        </div>

        <!-- Ad Code -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Google AdSense Code</h3>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">โค้ดโฆษณา</label>
                <textarea name="code" rows="10"
                          class="w-full font-mono text-sm border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-primary-500"
                          placeholder="<script async src='https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXXXXXXXXXX'&#10;     crossorigin='anonymous'></script>&#10;<ins class='adsbygoogle'&#10;     style='display:block'&#10;     data-ad-client='ca-pub-XXXXXXXXXXXXXXXX'&#10;     data-ad-slot='XXXXXXXXXX'&#10;     data-ad-format='auto'&#10;     data-full-width-responsive='true'></ins>&#10;<script>&#10;     (adsbygoogle = window.adsbygoogle || []).push({});&#10;</script>">{{ old('code') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">วางโค้ดโฆษณาจาก Google AdSense</p>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('admin.ads.index') }}"
               class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 transition-colors">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                สร้างตำแหน่งโฆษณา
            </button>
        </div>
    </form>
</div>
@endsection
