@extends($adminLayout ?? 'layouts.admin')

@section('title', 'เพิ่มหมวดหมู่ผลิตภัณฑ์')
@section('page-title', 'เพิ่มหมวดหมู่ผลิตภัณฑ์')

@section('content')
<form action="{{ route('admin.products.categories.store') }}" method="POST" class="space-y-6">
    @csrf

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลหมวดหมู่</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        ชื่อหมวดหมู่ <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required
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
                    <input type="text" name="icon" value="{{ old('icon') }}"
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
                              placeholder="คำอธิบายสั้นๆ เกี่ยวกับหมวดหมู่นี้">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        ลำดับการแสดง
                    </label>
                    <input type="number" name="order" value="{{ old('order', 0) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('order') border-red-500 @enderror">
                    <p class="mt-1 text-sm text-gray-500">ตัวเลขน้อยจะแสดงก่อน</p>
                    @error('order')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">เปิดใช้งาน</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Preview -->
        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ตัวอย่าง</h3>
            <div class="flex items-center p-4 bg-gray-50 rounded-lg" x-data="{ icon: '{{ old('icon', '📦') }}', name: '{{ old('name', 'ชื่อหมวดหมู่') }}' }">
                <span class="text-3xl mr-4" x-text="$refs.iconInput?.value || '📦'"></span>
                <div>
                    <div class="font-medium text-gray-900" x-text="$refs.nameInput?.value || 'ชื่อหมวดหมู่'"></div>
                    <div class="text-sm text-gray-500">0 ผลิตภัณฑ์</div>
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
