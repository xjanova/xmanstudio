@extends('layouts.admin')

@section('title', 'เพิ่มตัวเลือกบริการ')
@section('page-title', 'เพิ่มตัวเลือกบริการใหม่')

@section('content')
<div class="max-w-4xl mx-auto">
    <form action="{{ route('admin.quotations.options.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- Basic Information -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลพื้นฐาน</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        หมวดหมู่ <span class="text-red-500">*</span>
                    </label>
                    <select name="quotation_category_id" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('quotation_category_id') border-red-500 @enderror">
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('quotation_category_id', $selectedCategoryId) == $category->id ? 'selected' : '' }}>
                                {{ $category->icon }} {{ $category->name }} ({{ $category->name_th }})
                            </option>
                        @endforeach
                    </select>
                    @error('quotation_category_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        ชื่อ (EN) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        ชื่อ (TH)
                    </label>
                    <input type="text" name="name_th" value="{{ old('name_th') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('name_th') border-red-500 @enderror">
                    @error('name_th')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Key (ภาษาอังกฤษ ตัวพิมพ์เล็ก ไม่มีช่องว่าง) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="key" value="{{ old('key') }}" required
                           placeholder="example: music_basic, web_ecommerce"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('key') border-red-500 @enderror">
                    @error('key')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        ราคา (บาท) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="price" value="{{ old('price', 0) }}" required min="0" step="0.01"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('price') border-red-500 @enderror">
                    @error('price')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">คำอธิบาย (EN)</label>
                    <textarea name="description" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">คำอธิบาย (TH)</label>
                    <textarea name="description_th" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('description_th') border-red-500 @enderror">{{ old('description_th') }}</textarea>
                    @error('description_th')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Detailed Information -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">รายละเอียดเพิ่มเติม</h3>

            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียดแบบยาว (EN)</label>
                    <textarea name="long_description" rows="4" placeholder="รายละเอียดเต็มของบริการนี้..."
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('long_description') border-red-500 @enderror">{{ old('long_description') }}</textarea>
                    @error('long_description')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียดแบบยาว (TH)</label>
                    <textarea name="long_description_th" rows="4" placeholder="รายละเอียดเต็มของบริการนี้..."
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('long_description_th') border-red-500 @enderror">{{ old('long_description_th') }}</textarea>
                    @error('long_description_th')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        คุณสมบัติ/Features (EN) <span class="text-sm text-gray-500">- พิมพ์ทีละบรรทัด</span>
                    </label>
                    <textarea name="features_text" rows="5" placeholder="Feature 1&#10;Feature 2&#10;Feature 3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 font-mono text-sm @error('features_text') border-red-500 @enderror">{{ old('features_text') }}</textarea>
                    @error('features_text')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        คุณสมบัติ/Features (TH) <span class="text-sm text-gray-500">- พิมพ์ทีละบรรทัด</span>
                    </label>
                    <textarea name="features_th_text" rows="5" placeholder="คุณสมบัติ 1&#10;คุณสมบัติ 2&#10;คุณสมบัติ 3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 font-mono text-sm @error('features_th_text') border-red-500 @enderror">{{ old('features_th_text') }}</textarea>
                    @error('features_th_text')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        ขั้นตอน/Steps (EN) <span class="text-sm text-gray-500">- พิมพ์ทีละบรรทัด</span>
                    </label>
                    <textarea name="steps_text" rows="5" placeholder="Step 1: ...&#10;Step 2: ...&#10;Step 3: ..."
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 font-mono text-sm @error('steps_text') border-red-500 @enderror">{{ old('steps_text') }}</textarea>
                    @error('steps_text')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        ขั้นตอน/Steps (TH) <span class="text-sm text-gray-500">- พิมพ์ทีละบรรทัด</span>
                    </label>
                    <textarea name="steps_th_text" rows="5" placeholder="ขั้นตอนที่ 1: ...&#10;ขั้นตอนที่ 2: ...&#10;ขั้นตอนที่ 3: ..."
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 font-mono text-sm @error('steps_th_text') border-red-500 @enderror">{{ old('steps_th_text') }}</textarea>
                    @error('steps_th_text')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Settings -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">การตั้งค่า</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รูปภาพ</label>
                    <input type="file" name="image" accept="image/*"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('image') border-red-500 @enderror">
                    @error('image')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ลำดับการแสดง</label>
                    <input type="number" name="order" value="{{ old('order', 0) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('order') border-red-500 @enderror">
                    @error('order')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">เปิดใช้งาน</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-4">
            <a href="{{ route('admin.quotations.options.index') }}"
               class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                บันทึก
            </button>
        </div>
    </form>
</div>
@endsection
