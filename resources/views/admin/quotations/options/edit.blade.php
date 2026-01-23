@extends($adminLayout ?? 'layouts.admin')

@section('title', 'แก้ไขตัวเลือกบริการ')
@section('page-title', 'แก้ไขตัวเลือกบริการ')

@section('content')
<form action="{{ route('admin.quotations.options.update', $option) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="max-w-4xl mx-auto">
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
                            <option value="{{ $category->id }}" {{ old('quotation_category_id', $option->quotation_category_id) == $category->id ? 'selected' : '' }}>
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
                    <input type="text" name="name" value="{{ old('name', $option->name) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        ชื่อ (TH)
                    </label>
                    <input type="text" name="name_th" value="{{ old('name_th', $option->name_th) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('name_th') border-red-500 @enderror">
                    @error('name_th')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Key (ภาษาอังกฤษ ตัวพิมพ์เล็ก ไม่มีช่องว่าง) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="key" value="{{ old('key', $option->key) }}" required
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
                    <input type="number" name="price" value="{{ old('price', $option->price) }}" required min="0" step="0.01"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('price') border-red-500 @enderror">
                    @error('price')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">คำอธิบาย (EN)</label>
                    <textarea name="description" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('description') border-red-500 @enderror">{{ old('description', $option->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">คำอธิบาย (TH)</label>
                    <textarea name="description_th" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('description_th') border-red-500 @enderror">{{ old('description_th', $option->description_th) }}</textarea>
                    @error('description_th')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Information - Full Width for Page Builder -->
    <div class="space-y-6">
        <!-- EN Description (smaller) -->
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">รายละเอียดเพิ่มเติม</h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียดแบบยาว (EN)</label>
                    <textarea name="long_description" rows="4" placeholder="รายละเอียดเต็มของบริการนี้..."
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('long_description') border-red-500 @enderror">{{ old('long_description', $option->long_description) }}</textarea>
                    @error('long_description')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- TH Description - Full Width Page Builder -->
        <div class="bg-white rounded-lg shadow-lg">
            <div class="bg-gradient-to-r from-primary-600 to-primary-700 text-white px-6 py-3 rounded-t-lg flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                    </svg>
                    <span class="font-semibold">รายละเอียดแบบยาว (TH)</span>
                </div>
                <span class="text-xs bg-white/20 px-2 py-1 rounded">Page Builder</span>
            </div>
            <div class="p-0">
                @php
                    $longDescValue = old('long_description_th', $option->long_description_th);
                    // If it's an array, encode to JSON
                    if (is_array($longDescValue)) {
                        $longDescValue = json_encode($longDescValue, JSON_UNESCAPED_UNICODE);
                    }
                @endphp
                <x-page-builder name="long_description_th" :value="$longDescValue" placeholder="ลากบล็อกมาวางเพื่อสร้างรายละเอียดบริการ..." :fullWidth="true" />
            </div>
            @error('long_description_th')
                <p class="px-6 pb-4 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <!-- EN Features (smaller) -->
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow p-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        คุณสมบัติ/Features (EN) <span class="text-sm text-gray-500">- พิมพ์ทีละบรรทัด</span>
                    </label>
                    <textarea name="features_text" rows="5" placeholder="Feature 1&#10;Feature 2&#10;Feature 3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 font-mono text-sm @error('features_text') border-red-500 @enderror">{{ old('features_text', is_array($option->features) ? implode("\n", $option->features) : '') }}</textarea>
                    @error('features_text')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- TH Features - Full Width Page Builder -->
        <div class="bg-white rounded-lg shadow-lg">
            <div class="bg-gradient-to-r from-green-600 to-green-700 text-white px-6 py-3 rounded-t-lg flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-semibold">คุณสมบัติ/Features (TH)</span>
                </div>
                <span class="text-xs bg-white/20 px-2 py-1 rounded">Page Builder</span>
            </div>
            <div class="p-0">
                @php
                    $featuresValue = old('features_th', $option->features_th);
                    // If it's an array, encode to JSON
                    if (is_array($featuresValue)) {
                        $featuresValue = json_encode($featuresValue, JSON_UNESCAPED_UNICODE);
                    }
                @endphp
                <x-page-builder name="features_th" :value="$featuresValue" placeholder="ลากบล็อกมาวางเพื่อสร้างรายการคุณสมบัติ..." :fullWidth="true" />
            </div>
            @error('features_th')
                <p class="px-6 pb-4 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <!-- EN Steps (smaller) -->
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow p-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        ขั้นตอน/Steps (EN) <span class="text-sm text-gray-500">- พิมพ์ทีละบรรทัด</span>
                    </label>
                    <textarea name="steps_text" rows="5" placeholder="Step 1: ...&#10;Step 2: ...&#10;Step 3: ..."
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 font-mono text-sm @error('steps_text') border-red-500 @enderror">{{ old('steps_text', is_array($option->steps) ? implode("\n", $option->steps) : '') }}</textarea>
                    @error('steps_text')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- TH Steps - Full Width Page Builder -->
        <div class="bg-white rounded-lg shadow-lg">
            <div class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-3 rounded-t-lg flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                    </svg>
                    <span class="font-semibold">ขั้นตอน/Steps (TH)</span>
                </div>
                <span class="text-xs bg-white/20 px-2 py-1 rounded">Page Builder</span>
            </div>
            <div class="p-0">
                @php
                    $stepsValue = old('steps_th', $option->steps_th);
                    // If it's an array, encode to JSON
                    if (is_array($stepsValue)) {
                        $stepsValue = json_encode($stepsValue, JSON_UNESCAPED_UNICODE);
                    }
                @endphp
                <x-page-builder name="steps_th" :value="$stepsValue" placeholder="ลากบล็อกมาวางเพื่อสร้างขั้นตอนการทำงาน..." :fullWidth="true" />
            </div>
            @error('steps_th')
                <p class="px-6 pb-4 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="max-w-4xl mx-auto">
        <!-- Settings -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">การตั้งค่า</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($option->image)
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">รูปภาพปัจจุบัน</label>
                        <img src="{{ asset('storage/' . $option->image) }}" alt="{{ $option->name }}" class="w-32 h-32 object-cover rounded">
                    </div>
                @endif

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">รูปภาพใหม่</label>
                    <input type="file" name="image" accept="image/*"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('image') border-red-500 @enderror">
                    @error('image')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ลำดับการแสดง</label>
                    <input type="number" name="order" value="{{ old('order', $option->order) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('order') border-red-500 @enderror">
                    @error('order')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $option->is_active) ? 'checked' : '' }}
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
    </div>
</form>
@endsection
