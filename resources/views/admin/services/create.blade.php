@extends('layouts.admin')

@section('title', 'เพิ่มบริการใหม่')
@section('page-title', 'เพิ่มบริการใหม่')

@section('content')
<div class="max-w-4xl">
    <form action="{{ route('admin.services.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลพื้นฐาน</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อบริการ (EN) <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อบริการ (TH)</label>
                    <input type="text" name="name_th" value="{{ old('name_th') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="auto-generate if empty">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Icon (Emoji)</label>
                    <input type="text" name="icon" value="{{ old('icon') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="e.g. 🔗">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด (EN) <span class="text-red-500">*</span></label>
                    <textarea name="description" rows="3" required
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด (TH)</label>
                    <textarea name="description_th" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('description_th') }}</textarea>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Features (EN)</h3>
            <div id="features-container" class="space-y-2">
                @if(old('features'))
                    @foreach(old('features') as $feature)
                        <div class="flex gap-2">
                            <input type="text" name="features[]" value="{{ $feature }}"
                                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                            <button type="button" onclick="removeFeature(this)" class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg">X</button>
                        </div>
                    @endforeach
                @else
                    <div class="flex gap-2">
                        <input type="text" name="features[]"
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        <button type="button" onclick="removeFeature(this)" class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg">X</button>
                    </div>
                @endif
            </div>
            <button type="button" onclick="addFeature('features-container', 'features[]')"
                    class="mt-3 text-primary-600 hover:underline">+ เพิ่ม Feature</button>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Features (TH)</h3>
            <div id="features-th-container" class="space-y-2">
                <div class="flex gap-2">
                    <input type="text" name="features_th[]"
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    <button type="button" onclick="removeFeature(this)" class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg">X</button>
                </div>
            </div>
            <button type="button" onclick="addFeature('features-th-container', 'features_th[]')"
                    class="mt-3 text-primary-600 hover:underline">+ เพิ่ม Feature</button>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ราคาและการแสดงผล</h3>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ราคาเริ่มต้น</label>
                    <input type="number" name="starting_price" value="{{ old('starting_price') }}" step="0.01" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">หน่วยราคา</label>
                    <input type="text" name="price_unit" value="{{ old('price_unit', 'โปรเจกต์') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ลำดับ</label>
                    <input type="number" name="order" value="{{ old('order', 0) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div class="flex items-end space-x-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">เปิดใช้งาน</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">แนะนำ</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="is_coming_soon" value="1" {{ old('is_coming_soon') ? 'checked' : '' }}
                               class="rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                               id="is_coming_soon" onchange="toggleComingSoonDate()">
                        <span class="ml-2 text-sm text-gray-700">Coming Soon</span>
                    </label>
                </div>
            </div>

            <div id="coming_soon_date_container" class="mt-4 {{ old('is_coming_soon') ? '' : 'hidden' }}">
                <label class="block text-sm font-medium text-gray-700 mb-1">เปิดให้บริการเมื่อ (ไม่จำเป็น)</label>
                <input type="datetime-local" name="coming_soon_until"
                       value="{{ old('coming_soon_until') }}"
                       class="w-full max-w-xs px-4 py-2 border border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500">
                <p class="mt-1 text-xs text-gray-500">ระบบจะเปิดบริการอัตโนมัติเมื่อถึงวันที่กำหนด</p>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('admin.services.index') }}"
               class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">ยกเลิก</a>
            <button type="submit"
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">บันทึก</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function addFeature(containerId, inputName) {
        const container = document.getElementById(containerId);
        const div = document.createElement('div');
        div.className = 'flex gap-2';
        div.innerHTML = `
            <input type="text" name="${inputName}"
                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
            <button type="button" onclick="removeFeature(this)" class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg">X</button>
        `;
        container.appendChild(div);
    }

    function removeFeature(button) {
        const container = button.parentElement.parentElement;
        if (container.children.length > 1) {
            button.parentElement.remove();
        }
    }

    function toggleComingSoonDate() {
        const checkbox = document.getElementById('is_coming_soon');
        const container = document.getElementById('coming_soon_date_container');
        if (checkbox.checked) {
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
        }
    }
</script>
@endpush
@endsection
