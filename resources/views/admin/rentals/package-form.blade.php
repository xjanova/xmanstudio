@extends($adminLayout ?? 'layouts.admin')

@section('title', $package ? 'แก้ไขแพ็กเกจ' : 'สร้างแพ็กเกจใหม่')
@section('page-title', $package ? 'แก้ไขแพ็กเกจ' : 'สร้างแพ็กเกจใหม่')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.rentals.packages') }}" class="text-primary-600 hover:underline">
        &larr; กลับ
    </a>
</div>

<div class="bg-white rounded-lg shadow">
    <form action="{{ $package ? route('admin.rentals.packages.update', $package) : route('admin.rentals.packages.store') }}"
          method="POST">
        @csrf
        @if($package)
            @method('PUT')
        @endif

        <div class="p-6 space-y-6">
            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ (อังกฤษ) *</label>
                    <input type="text" name="name" value="{{ old('name', $package?->name) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ (ไทย) *</label>
                    <input type="text" name="name_th" value="{{ old('name_th', $package?->name_th) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('name_th') border-red-500 @enderror">
                    @error('name_th')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด (อังกฤษ)</label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('description', $package?->description) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด (ไทย)</label>
                    <textarea name="description_th" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('description_th', $package?->description_th) }}</textarea>
                </div>
            </div>

            <!-- Pricing -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ราคา</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ราคา (บาท) *</label>
                        <input type="number" name="price" value="{{ old('price', $package?->price) }}" min="0" step="0.01" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ราคาเดิม (สำหรับแสดงส่วนลด)</label>
                        <input type="number" name="original_price" value="{{ old('original_price', $package?->original_price) }}" min="0" step="0.01"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
            </div>

            <!-- Duration -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ระยะเวลา</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ประเภท *</label>
                        <select name="duration_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                            <option value="hourly" {{ old('duration_type', $package?->duration_type) === 'hourly' ? 'selected' : '' }}>ชั่วโมง</option>
                            <option value="daily" {{ old('duration_type', $package?->duration_type) === 'daily' ? 'selected' : '' }}>วัน</option>
                            <option value="weekly" {{ old('duration_type', $package?->duration_type) === 'weekly' ? 'selected' : '' }}>สัปดาห์</option>
                            <option value="monthly" {{ old('duration_type', $package?->duration_type) === 'monthly' ? 'selected' : '' }}>เดือน</option>
                            <option value="yearly" {{ old('duration_type', $package?->duration_type) === 'yearly' ? 'selected' : '' }}>ปี</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">จำนวน *</label>
                        <input type="number" name="duration_value" value="{{ old('duration_value', $package?->duration_value ?? 1) }}" min="1" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
            </div>

            <!-- Features -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">คุณสมบัติ</h3>
                <div id="features-container">
                    @php
                        $features = old('features', $package?->features ?? ['']);
                    @endphp
                    @foreach($features as $index => $feature)
                        <div class="flex items-center gap-2 mb-2 feature-row">
                            <input type="text" name="features[]" value="{{ $feature }}"
                                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                   placeholder="เช่น โพสต์ได้ไม่จำกัด">
                            <button type="button" onclick="removeFeature(this)"
                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
                <button type="button" onclick="addFeature()"
                        class="mt-2 text-primary-600 hover:underline text-sm">
                    + เพิ่มคุณสมบัติ
                </button>
            </div>

            <!-- Limits -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">โควต้า/จำกัด</h3>
                @php
                    $limits = old('limits', $package?->limits ?? []);
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">จำนวนโพสต์/เดือน</label>
                        <input type="number" name="limits[max_posts]" value="{{ $limits['max_posts'] ?? '' }}" min="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                               placeholder="ไม่จำกัด = เว้นว่าง">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">จำนวนบัญชี</label>
                        <input type="number" name="limits[max_accounts]" value="{{ $limits['max_accounts'] ?? '' }}" min="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                               placeholder="ไม่จำกัด = เว้นว่าง">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">AI Credits</label>
                        <input type="number" name="limits[ai_credits]" value="{{ $limits['ai_credits'] ?? '' }}" min="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                               placeholder="ไม่จำกัด = เว้นว่าง">
                    </div>
                </div>
            </div>

            <!-- Flags -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ตัวเลือก</h3>
                <div class="flex flex-wrap gap-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $package?->is_active ?? true) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">เปิดใช้งาน</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="is_featured" value="1"
                               {{ old('is_featured', $package?->is_featured) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">แนะนำ</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="is_popular" value="1"
                               {{ old('is_popular', $package?->is_popular) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">ยอดนิยม</span>
                    </label>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">ลำดับการแสดง</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $package?->sort_order ?? 0) }}" min="0"
                           class="w-32 px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
            <a href="{{ route('admin.rentals.packages') }}"
               class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">ยกเลิก</a>
            <button type="submit"
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                {{ $package ? 'บันทึก' : 'สร้างแพ็กเกจ' }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function addFeature() {
        const container = document.getElementById('features-container');
        const row = document.createElement('div');
        row.className = 'flex items-center gap-2 mb-2 feature-row';
        row.innerHTML = `
            <input type="text" name="features[]"
                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                   placeholder="เช่น โพสต์ได้ไม่จำกัด">
            <button type="button" onclick="removeFeature(this)"
                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        `;
        container.appendChild(row);
    }

    function removeFeature(button) {
        const container = document.getElementById('features-container');
        if (container.querySelectorAll('.feature-row').length > 1) {
            button.closest('.feature-row').remove();
        } else {
            button.closest('.feature-row').querySelector('input').value = '';
        }
    }
</script>
@endpush
@endsection
