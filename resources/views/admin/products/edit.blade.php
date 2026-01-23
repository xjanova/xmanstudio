@extends($adminLayout ?? 'layouts.admin')

@section('title', 'แก้ไขผลิตภัณฑ์')
@section('page-title', 'แก้ไขผลิตภัณฑ์: ' . $product->name)

@section('content')
<div class="max-w-5xl mx-auto" x-data="productEditForm()">
    <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Information -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลพื้นฐาน</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        ชื่อผลิตภัณฑ์ <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                    <input type="text" name="sku" value="{{ old('sku', $product->sku) }}"
                           placeholder="PROD-001"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('sku') border-red-500 @enderror">
                    @error('sku')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">หมวดหมู่</label>
                    <select name="category_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        <option value="">-- ไม่ระบุหมวดหมู่ --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">คำอธิบายสั้น</label>
                    <textarea name="short_description" rows="2" maxlength="500"
                              placeholder="คำอธิบายสั้นๆ สำหรับแสดงในรายการสินค้า (ไม่เกิน 500 ตัวอักษร)"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('short_description', $product->short_description) }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด</label>
                    <textarea name="description" rows="5"
                              placeholder="รายละเอียดเต็มของผลิตภัณฑ์"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('description', $product->description) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">คุณสมบัติ</h3>

            <div class="space-y-3">
                <template x-for="(feature, index) in features" :key="index">
                    <div class="flex gap-2">
                        <input type="text"
                               :name="'features[' + index + ']'"
                               x-model="features[index]"
                               placeholder="คุณสมบัติ"
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        <button type="button"
                                @click="removeFeature(index)"
                                class="px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </template>

                <button type="button"
                        @click="addFeature()"
                        class="w-full px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg hover:border-primary-500 hover:bg-primary-50 text-gray-600 hover:text-primary-600 transition">
                    + เพิ่มคุณสมบัติ
                </button>
            </div>
        </div>

        <!-- Images -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">รูปภาพ</h3>

            <!-- Main Image -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">รูปภาพหลัก</label>

                @if($product->image)
                <div class="mb-4">
                    <div class="relative inline-block">
                        <img src="{{ asset('storage/' . $product->image) }}" class="max-h-64 rounded-lg border">
                        <div class="mt-2 text-sm text-gray-500">รูปภาพปัจจุบัน</div>
                    </div>
                </div>
                @endif

                <div class="relative">
                    <div @click="$refs.mainImageInput.click()"
                         class="border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-primary-500 cursor-pointer transition">
                        <div class="text-center">
                            <template x-if="!mainImagePreview">
                                <div>
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-600">คลิกเพื่อเลือกรูปภาพหลักใหม่</p>
                                    <p class="text-xs text-gray-500">PNG, JPG, GIF ขนาดไม่เกิน 2MB</p>
                                </div>
                            </template>
                            <template x-if="mainImagePreview">
                                <div class="relative">
                                    <img :src="mainImagePreview" class="max-h-64 mx-auto rounded-lg">
                                    <button type="button"
                                            @click.stop="removeMainImage()"
                                            class="absolute top-2 right-2 bg-red-500 text-white p-2 rounded-full hover:bg-red-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                    <p class="mt-2 text-sm text-gray-600">รูปภาพใหม่ที่จะอัปโหลด</p>
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

            <!-- Existing Gallery Images -->
            @if($product->images && count($product->images) > 0)
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">รูปภาพ Gallery ปัจจุบัน</label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($product->images as $index => $image)
                    <div class="relative group">
                        <img src="{{ asset('storage/' . $image) }}" class="w-full h-32 object-cover rounded-lg border"
                             :class="{'opacity-50': imagesToRemove.includes('{{ $image }}')}"
                             x-data>
                        <button type="button"
                                @click="markForRemoval('{{ $image }}')"
                                class="absolute top-2 right-2 p-1 rounded-full opacity-0 group-hover:opacity-100 transition"
                                :class="imagesToRemove.includes('{{ $image }}') ? 'bg-green-500 text-white' : 'bg-red-500 text-white'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="!imagesToRemove.includes('{{ $image }}')">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="imagesToRemove.includes('{{ $image }}')">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>
                        <div x-show="imagesToRemove.includes('{{ $image }}')"
                             class="absolute inset-0 bg-red-500 bg-opacity-50 rounded-lg flex items-center justify-center">
                            <span class="text-white text-sm font-medium">จะลบ</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- New Gallery Images -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">เพิ่มรูปภาพ Gallery ใหม่</label>

                <div @drop.prevent="handleDrop($event)"
                     @dragover.prevent="dragOver = true"
                     @dragleave.prevent="dragOver = false"
                     :class="{'border-primary-500 bg-primary-50': dragOver}"
                     class="border-2 border-dashed border-gray-300 rounded-lg p-6 transition">

                    <div class="text-center mb-4">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-600">ลากไฟล์มาวางที่นี่ หรือ</p>
                        <button type="button"
                                @click="$refs.galleryInput.click()"
                                class="mt-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                            เลือกรูปภาพ
                        </button>
                        <p class="mt-1 text-xs text-gray-500">สามารถเลือกได้หลายรูป (PNG, JPG, GIF ไม่เกิน 2MB/รูป)</p>
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
                                <img :src="preview" class="w-full h-32 object-cover rounded-lg">
                                <button type="button"
                                        @click="removeGalleryImage(index)"
                                        class="absolute top-2 right-2 bg-red-500 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                <div class="absolute bottom-2 left-2 bg-green-500 text-white text-xs px-2 py-1 rounded">
                                    ใหม่
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Hidden inputs for images to remove -->
                <template x-for="(image, index) in imagesToRemove" :key="index">
                    <input type="hidden" :name="'remove_images[' + index + ']'" :value="image">
                </template>
            </div>
        </div>

        <!-- Pricing & Inventory -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ราคาและคลังสินค้า</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        ราคา (บาท) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="price" value="{{ old('price', $product->price) }}" min="0" step="0.01" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('price') border-red-500 @enderror">
                    @error('price')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">จำนวนในสต็อก</label>
                    <input type="number" name="stock" value="{{ old('stock', $product->stock) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        แจ้งเตือนเมื่อสต็อกต่ำกว่า
                    </label>
                    <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $product->low_stock_threshold ?? 5) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </div>
            </div>
        </div>

        <!-- Options -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ตัวเลือก</h3>

            <div class="space-y-4">
                <label class="flex items-start">
                    <input type="checkbox" name="is_custom" value="1" {{ old('is_custom', $product->is_custom) ? 'checked' : '' }}
                           class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <div class="ml-3">
                        <span class="text-gray-900 font-medium">สินค้าแบบกำหนดเอง</span>
                        <p class="text-sm text-gray-500">ลูกค้าต้องสอบถามราคาก่อนสั่งซื้อ</p>
                    </div>
                </label>

                <label class="flex items-start">
                    <input type="checkbox" name="requires_license" value="1" {{ old('requires_license', $product->requires_license) ? 'checked' : '' }}
                           class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <div class="ml-3">
                        <span class="text-gray-900 font-medium">ต้องใช้ License Key</span>
                        <p class="text-sm text-gray-500">ระบบจะสร้าง License Key อัตโนมัติเมื่อมีการสั่งซื้อ</p>
                    </div>
                </label>

                <label class="flex items-start">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                           class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <div class="ml-3">
                        <span class="text-gray-900 font-medium">เปิดใช้งาน</span>
                        <p class="text-sm text-gray-500">แสดงสินค้าในหน้าร้านค้า</p>
                    </div>
                </label>

                <label class="flex items-start">
                    <input type="checkbox" name="is_coming_soon" value="1" {{ old('is_coming_soon', $product->is_coming_soon) ? 'checked' : '' }}
                           class="mt-1 rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                           x-model="isComingSoon">
                    <div class="ml-3">
                        <span class="text-gray-900 font-medium">Coming Soon</span>
                        <p class="text-sm text-gray-500">แสดงสินค้าแต่ยังซื้อไม่ได้ (จะเปิดขายเร็วๆ นี้)</p>
                    </div>
                </label>

                <div x-show="isComingSoon" x-cloak class="ml-7 mt-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">เปิดขายเมื่อ (ไม่จำเป็น)</label>
                    <input type="datetime-local" name="coming_soon_until"
                           value="{{ old('coming_soon_until', $product->coming_soon_until?->format('Y-m-d\TH:i')) }}"
                           class="w-full max-w-xs px-4 py-2 border border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500">
                    <p class="mt-1 text-xs text-gray-500">ระบบจะเปิดขายอัตโนมัติเมื่อถึงวันที่กำหนด</p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3 pb-6">
            <a href="{{ route('admin.products.index') }}"
               class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition">ยกเลิก</a>
            <button type="submit"
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                บันทึกการเปลี่ยนแปลง
            </button>
        </div>
    </form>
</div>

<script>
function productEditForm() {
    return {
        features: @json(old('features', $product->features ?? [''])),
        mainImagePreview: null,
        galleryPreviews: [],
        galleryFiles: [],
        imagesToRemove: [],
        dragOver: false,
        isComingSoon: {{ old('is_coming_soon', $product->is_coming_soon) ? 'true' : 'false' }},

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

        markForRemoval(imagePath) {
            if (!this.imagesToRemove.includes(imagePath)) {
                this.imagesToRemove.push(imagePath);
            } else {
                const index = this.imagesToRemove.indexOf(imagePath);
                this.imagesToRemove.splice(index, 1);
            }
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
