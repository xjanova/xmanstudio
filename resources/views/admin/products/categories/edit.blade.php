@extends($adminLayout ?? 'layouts.admin')

@section('title', 'แก้ไขหมวดหมู่ผลิตภัณฑ์')
@section('page-title', 'แก้ไขหมวดหมู่: ' . $category->name)

@section('content')
<form action="{{ route('admin.products.categories.update', $category) }}" method="POST" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลหมวดหมู่</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        ชื่อหมวดหมู่ <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $category->name) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('name') border-red-500 @enderror"
                           placeholder="เช่น Trading Bots, Automation Tools">
                    @error('name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        ไอคอน (Emoji)
                    </label>
                    <input type="text" name="icon" value="{{ old('icon', $category->icon) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('icon') border-red-500 @enderror"
                           placeholder="เช่น 📊, 🤖, 💻">
                    <p class="mt-1 text-sm text-gray-500">ใช้ Emoji สำหรับแสดงเป็นไอคอนหมวดหมู่</p>
                    @error('icon')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        คำอธิบาย
                    </label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('description') border-red-500 @enderror"
                              placeholder="คำอธิบายสั้นๆ เกี่ยวกับหมวดหมู่นี้">{{ old('description', $category->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        ลำดับการแสดง
                    </label>
                    <input type="number" name="order" value="{{ old('order', $category->order) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('order') border-red-500 @enderror">
                    <p class="mt-1 text-sm text-gray-500">ตัวเลขน้อยจะแสดงก่อน</p>
                    @error('order')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">เปิดใช้งาน</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Current Products in Category -->
        @if($category->products && $category->products->count() > 0)
        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ผลิตภัณฑ์ในหมวดหมู่นี้ ({{ $category->products->count() }} รายการ)</h3>
            <div class="space-y-2">
                @foreach($category->products->take(5) as $product)
                <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                    <div class="flex items-center">
                        @if($product->image)
                            <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="w-10 h-10 rounded object-cover mr-3">
                        @else
                            <div class="w-10 h-10 rounded bg-gray-200 flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                        @endif
                        <span class="text-sm text-gray-700">{{ $product->name }}</span>
                    </div>
                    <a href="{{ route('admin.products.edit', $product) }}" class="text-sm text-primary-600 hover:underline">แก้ไข</a>
                </div>
                @endforeach
                @if($category->products->count() > 5)
                <p class="text-sm text-gray-500 mt-2">และอีก {{ $category->products->count() - 5 }} รายการ...</p>
                @endif
            </div>
        </div>
        @endif

        <!-- Preview -->
        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ตัวอย่าง</h3>
            <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                <span class="text-3xl mr-4">{{ $category->icon ?: '📦' }}</span>
                <div>
                    <div class="font-medium text-gray-900">{{ $category->name }}</div>
                    <div class="text-sm text-gray-500">{{ $category->products_count ?? 0 }} ผลิตภัณฑ์</div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-4 mt-6">
            <a href="{{ route('admin.products.categories.index') }}"
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
