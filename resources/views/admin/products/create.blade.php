@extends($adminLayout ?? 'layouts.admin')

@section('title', 'เพิ่มผลิตภัณฑ์')
@section('page-title', 'เพิ่มผลิตภัณฑ์ใหม่')

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
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-500 p-8 shadow-2xl mb-8">
    <div class="absolute top-0 left-0 w-72 h-72 bg-blue-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
    <div class="absolute top-0 right-0 w-72 h-72 bg-indigo-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-8 left-20 w-72 h-72 bg-violet-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
    <div class="relative">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.products.index') }}" class="p-2 bg-white/20 backdrop-blur-sm rounded-xl hover:bg-white/30 transition-all">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h2 class="text-3xl font-bold text-white mb-2">เพิ่มผลิตภัณฑ์ใหม่</h2>
                    <p class="text-blue-100">สร้างผลิตภัณฑ์ใหม่สำหรับร้านค้า</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div x-data="productForm()">
    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- Basic Information -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                <span class="w-8 h-8 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-lg flex items-center justify-center mr-3">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                ข้อมูลพื้นฐาน
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ชื่อผลิตภัณฑ์ <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SKU</label>
                    <input type="text" name="sku" value="{{ old('sku') }}"
                           placeholder="PROD-001"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all @error('sku') border-red-500 @enderror">
                    @error('sku')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมวดหมู่</label>
                    <select name="category_id"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all">
                        <option value="">-- ไม่ระบุหมวดหมู่ --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คำอธิบายสั้น</label>
                    <textarea name="short_description" rows="2" maxlength="500"
                              placeholder="คำอธิบายสั้นๆ สำหรับแสดงในรายการสินค้า (ไม่เกิน 500 ตัวอักษร)"
                              class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all">{{ old('short_description') }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รายละเอียด</label>
                    <textarea name="description" rows="5"
                              placeholder="รายละเอียดเต็มของผลิตภัณฑ์"
                              class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                <span class="w-8 h-8 bg-gradient-to-r from-indigo-500 to-violet-500 rounded-lg flex items-center justify-center mr-3">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                คุณสมบัติ
            </h3>

            <div class="space-y-3">
                <template x-for="(feature, index) in features" :key="index">
                    <div class="flex gap-2">
                        <input type="text"
                               :name="'features[' + index + ']'"
                               x-model="features[index]"
                               placeholder="คุณสมบัติ"
                               class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all">
                        <button type="button"
                                @click="removeFeature(index)"
                                class="px-4 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </template>

                <button type="button"
                        @click="addFeature()"
                        class="w-full px-4 py-2.5 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-all">
                    + เพิ่มคุณสมบัติ
                </button>
            </div>
        </div>

        <!-- Images -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                <span class="w-8 h-8 bg-gradient-to-r from-violet-500 to-purple-500 rounded-lg flex items-center justify-center mr-3">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </span>
                รูปภาพ
            </h3>

            <!-- Main Image -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปภาพหลัก</label>
                <div class="relative">
                    <div @click="$refs.mainImageInput.click()"
                         class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 hover:border-blue-500 cursor-pointer transition-all">
                        <div class="text-center">
                            <template x-if="!mainImagePreview">
                                <div>
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">คลิกเพื่อเลือกรูปภาพหลัก</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500">PNG, JPG, GIF ขนาดไม่เกิน 2MB</p>
                                </div>
                            </template>
                            <template x-if="mainImagePreview">
                                <div class="relative">
                                    <img :src="mainImagePreview" class="max-h-64 mx-auto rounded-xl">
                                    <button type="button"
                                            @click.stop="removeMainImage()"
                                            class="absolute top-2 right-2 bg-red-500 text-white p-2 rounded-full hover:bg-red-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <input type="file"
                           x-ref="mainImageInput"
                           name="image"
                           accept="image/*"
                           @change="previewMainImage($event)"
                           class="hidden">
                </div>
            </div>

            <!-- Gallery Images -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปภาพเพิ่มเติม (Gallery)</label>

                <div @drop.prevent="handleDrop($event)"
                     @dragover.prevent="dragOver = true"
                     @dragleave.prevent="dragOver = false"
                     :class="{'border-blue-500 bg-blue-50 dark:bg-blue-900/20': dragOver}"
                     class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 transition-all">

                    <div class="text-center mb-4">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">ลากไฟล์มาวางที่นี่ หรือ</p>
                        <button type="button"
                                @click="$refs.galleryInput.click()"
                                class="mt-2 px-4 py-2 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-xl hover:from-blue-600 hover:to-indigo-600 transition-all shadow-lg">
                            เลือกรูปภาพ
                        </button>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">สามารถเลือกได้หลายรูป (PNG, JPG, GIF ไม่เกิน 2MB/รูป)</p>
                    </div>

                    <input type="file"
                           x-ref="galleryInput"
                           name="gallery_images[]"
                           accept="image/*"
                           multiple
                           @change="addGalleryImages($event)"
                           class="hidden">

                    <!-- Gallery Preview Grid -->
                    <div x-show="galleryPreviews.length > 0"
                         class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                        <template x-for="(preview, index) in galleryPreviews" :key="index">
                            <div class="relative group">
                                <img :src="preview" class="w-full h-32 object-cover rounded-xl">
                                <button type="button"
                                        @click="removeGalleryImage(index)"
                                        class="absolute top-2 right-2 bg-red-500 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing & Inventory -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                <span class="w-8 h-8 bg-gradient-to-r from-green-500 to-emerald-500 rounded-lg flex items-center justify-center mr-3">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                ราคาและคลังสินค้า
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ราคา (บาท) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="price" value="{{ old('price', 0) }}" min="0" step="0.01" required
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all @error('price') border-red-500 @enderror">
                    @error('price')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนในสต็อก</label>
                    <input type="number" name="stock" value="{{ old('stock', 0) }}" min="0"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        แจ้งเตือนเมื่อสต็อกต่ำกว่า
                    </label>
                    <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', 5) }}" min="0"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all">
                </div>
            </div>
        </div>

        <!-- Options -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                <span class="w-8 h-8 bg-gradient-to-r from-amber-500 to-orange-500 rounded-lg flex items-center justify-center mr-3">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </span>
                ตัวเลือก
            </h3>

            <div class="space-y-4">
                <label class="flex items-start p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer">
                    <input type="checkbox" name="is_custom" value="1" {{ old('is_custom') ? 'checked' : '' }}
                           class="mt-1 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                    <div class="ml-3">
                        <span class="text-gray-900 dark:text-white font-medium">สินค้าแบบกำหนดเอง</span>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ลูกค้าต้องสอบถามราคาก่อนสั่งซื้อ</p>
                    </div>
                </label>

                <label class="flex items-start p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer">
                    <input type="checkbox" name="requires_license" value="1" {{ old('requires_license', true) ? 'checked' : '' }}
                           class="mt-1 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                    <div class="ml-3">
                        <span class="text-gray-900 dark:text-white font-medium">ต้องใช้ License Key</span>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ระบบจะสร้าง License Key อัตโนมัติเมื่อมีการสั่งซื้อ</p>
                    </div>
                </label>

                <label class="flex items-start p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                           class="mt-1 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                    <div class="ml-3">
                        <span class="text-gray-900 dark:text-white font-medium">เปิดใช้งาน</span>
                        <p class="text-sm text-gray-500 dark:text-gray-400">แสดงสินค้าในหน้าร้านค้า</p>
                    </div>
                </label>

                <label class="flex items-start p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer">
                    <input type="checkbox" name="is_coming_soon" value="1" {{ old('is_coming_soon') ? 'checked' : '' }}
                           class="mt-1 rounded border-gray-300 dark:border-gray-600 text-orange-600 focus:ring-orange-500"
                           x-model="isComingSoon">
                    <div class="ml-3">
                        <span class="text-gray-900 dark:text-white font-medium">Coming Soon</span>
                        <p class="text-sm text-gray-500 dark:text-gray-400">แสดงสินค้าแต่ยังซื้อไม่ได้ (จะเปิดขายเร็วๆ นี้)</p>
                    </div>
                </label>

                <div x-show="isComingSoon" x-cloak class="ml-7 mt-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เปิดขายเมื่อ (ไม่จำเป็น)</label>
                    <input type="datetime-local" name="coming_soon_until"
                           value="{{ old('coming_soon_until') }}"
                           class="w-full max-w-xs px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 dark:bg-gray-700 dark:text-white transition-all">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ระบบจะเปิดขายอัตโนมัติเมื่อถึงวันที่กำหนด</p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3 pb-6">
            <a href="{{ route('admin.products.index') }}"
               class="px-6 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-2.5 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-xl hover:from-blue-600 hover:to-indigo-600 transition-all shadow-lg flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                บันทึกผลิตภัณฑ์
            </button>
        </div>
    </form>
</div>

<script>
function productForm() {
    return {
        features: [''],
        mainImagePreview: null,
        galleryPreviews: [],
        galleryFiles: [],
        dragOver: false,
        isComingSoon: {{ old('is_coming_soon') ? 'true' : 'false' }},

        addFeature() {
            this.features.push('');
        },

        removeFeature(index) {
            this.features.splice(index, 1);
            if (this.features.length === 0) {
                this.features = [''];
            }
        },

        previewMainImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.mainImagePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        removeMainImage() {
            this.mainImagePreview = null;
            this.$refs.mainImageInput.value = '';
        },

        addGalleryImages(event) {
            const files = Array.from(event.target.files);
            files.forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.galleryPreviews.push(e.target.result);
                    };
                    reader.readAsDataURL(file);
                    this.galleryFiles.push(file);
                }
            });
        },

        handleDrop(event) {
            this.dragOver = false;
            const files = Array.from(event.dataTransfer.files);

            // Create a new FileList-like object
            const dataTransfer = new DataTransfer();
            files.forEach(file => {
                if (file.type.startsWith('image/')) {
                    dataTransfer.items.add(file);

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.galleryPreviews.push(e.target.result);
                    };
                    reader.readAsDataURL(file);
                }
            });

            this.$refs.galleryInput.files = dataTransfer.files;
        },

        removeGalleryImage(index) {
            this.galleryPreviews.splice(index, 1);
            this.galleryFiles.splice(index, 1);

            // Update the file input
            const dataTransfer = new DataTransfer();
            this.galleryFiles.forEach(file => {
                dataTransfer.items.add(file);
            });
            this.$refs.galleryInput.files = dataTransfer.files;
        }
    };
}
</script>
@endsection
