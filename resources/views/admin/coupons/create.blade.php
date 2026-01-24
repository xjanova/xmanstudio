@extends($adminLayout ?? 'layouts.admin')

@section('title', 'สร้างคูปองใหม่')
@section('page-title', 'สร้างคูปองใหม่')

@section('content')
<!-- Premium Header Banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 p-6 sm:p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/5 rounded-full blur-3xl animate-blob"></div>
    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-orange-400/10 rounded-full blur-3xl animate-blob animation-delay-2000"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                สร้างคูปองใหม่
            </h1>
            <p class="mt-2 text-white/80 text-sm sm:text-base">สร้างโค้ดส่วนลดสำหรับลูกค้า</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.coupons.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white/20 text-white rounded-xl hover:bg-white/30 transition-all font-medium backdrop-blur-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับ
            </a>
        </div>
    </div>
</div>

<form action="{{ route('admin.coupons.store') }}" method="POST">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Info Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    ข้อมูลคูปอง
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">โค้ดคูปอง</label>
                        <div class="flex gap-2">
                            <input type="text" name="code" id="couponCode"
                                   class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 dark:bg-gray-700 dark:text-white uppercase @error('code') border-red-500 @enderror"
                                   value="{{ old('code') }}" placeholder="ปล่อยว่างเพื่อสร้างอัตโนมัติ">
                            <button type="button" onclick="generateCode()"
                                    class="px-4 py-2.5 bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-500 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </button>
                        </div>
                        @error('code')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อคูปอง <span class="text-red-500">*</span></label>
                        <input type="text" name="name"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror"
                               value="{{ old('name') }}" required placeholder="เช่น ส่วนลดปีใหม่ 2026">
                        @error('name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รายละเอียด</label>
                        <textarea name="description" rows="2"
                                  class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 dark:bg-gray-700 dark:text-white"
                                  placeholder="รายละเอียดเพิ่มเติม...">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Discount Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    ส่วนลด
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภทส่วนลด <span class="text-red-500">*</span></label>
                        <select name="discount_type" id="discountType" onchange="toggleMaxDiscount()"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 dark:bg-gray-700 dark:text-white">
                            <option value="percentage" {{ old('discount_type', 'percentage') === 'percentage' ? 'selected' : '' }}>เปอร์เซ็นต์ (%)</option>
                            <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>จำนวนเงิน (฿)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">มูลค่าส่วนลด <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="number" name="discount_value"
                                   class="w-full px-4 py-2.5 pr-12 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 dark:bg-gray-700 dark:text-white @error('discount_value') border-red-500 @enderror"
                                   value="{{ old('discount_value') }}" required min="0" step="0.01">
                            <span id="discountUnit" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">%</span>
                        </div>
                        @error('discount_value')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="maxDiscountGroup">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ส่วนลดสูงสุด</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">฿</span>
                            <input type="number" name="max_discount"
                                   class="w-full px-4 py-2.5 pl-8 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 dark:bg-gray-700 dark:text-white"
                                   value="{{ old('max_discount') }}" min="0" step="0.01" placeholder="ไม่จำกัด">
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ยอดสั่งซื้อขั้นต่ำ</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">฿</span>
                            <input type="number" name="min_order_amount"
                                   class="w-full px-4 py-2.5 pl-8 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 dark:bg-gray-700 dark:text-white"
                                   value="{{ old('min_order_amount') }}" min="0" step="0.01" placeholder="ไม่จำกัด">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Restrictions Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    เงื่อนไขการใช้
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สินค้าที่ใช้ได้</label>
                        <select name="applicable_products[]" multiple size="4"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 dark:bg-gray-700 dark:text-white">
                            @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ in_array($product->id, old('applicable_products', [])) ? 'selected' : '' }}>
                                {{ $product->name }}
                            </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">กด Ctrl+Click เพื่อเลือกหลายรายการ (ว่างไว้ = ใช้ได้ทุกสินค้า)</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภท License ที่ใช้ได้</label>
                        <div class="space-y-3">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="applicable_license_types[]" value="monthly"
                                       class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-orange-500 focus:ring-orange-500 dark:bg-gray-700"
                                       {{ in_array('monthly', old('applicable_license_types', [])) ? 'checked' : '' }}>
                                <span class="text-gray-700 dark:text-gray-300">รายเดือน</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="applicable_license_types[]" value="yearly"
                                       class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-orange-500 focus:ring-orange-500 dark:bg-gray-700"
                                       {{ in_array('yearly', old('applicable_license_types', [])) ? 'checked' : '' }}>
                                <span class="text-gray-700 dark:text-gray-300">รายปี</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="applicable_license_types[]" value="lifetime"
                                       class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-orange-500 focus:ring-orange-500 dark:bg-gray-700"
                                       {{ in_array('lifetime', old('applicable_license_types', [])) ? 'checked' : '' }}>
                                <span class="text-gray-700 dark:text-gray-300">ตลอดชีพ</span>
                            </label>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">ไม่เลือก = ใช้ได้ทุกประเภท</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="first_order_only" value="1"
                                   class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-orange-500 focus:ring-orange-500 dark:bg-gray-700"
                                   {{ old('first_order_only') ? 'checked' : '' }}>
                            <span class="text-gray-700 dark:text-gray-300 font-medium">สำหรับการสั่งซื้อครั้งแรกเท่านั้น</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Sidebar -->
        <div class="space-y-6">
            <!-- Usage Limits Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                    จำนวนการใช้งาน
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนที่ใช้ได้ทั้งหมด</label>
                        <input type="number" name="usage_limit"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 dark:bg-gray-700 dark:text-white"
                               value="{{ old('usage_limit') }}" min="1" placeholder="ไม่จำกัด">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนที่ใช้ได้ต่อคน <span class="text-red-500">*</span></label>
                        <input type="number" name="usage_limit_per_user"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 dark:bg-gray-700 dark:text-white"
                               value="{{ old('usage_limit_per_user', 1) }}" min="1" required>
                    </div>
                </div>
            </div>

            <!-- Validity Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    ระยะเวลา
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่เริ่มใช้ได้</label>
                        <input type="datetime-local" name="starts_at"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 dark:bg-gray-700 dark:text-white"
                               value="{{ old('starts_at') }}">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันหมดอายุ</label>
                        <input type="datetime-local" name="expires_at"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 dark:bg-gray-700 dark:text-white"
                               value="{{ old('expires_at') }}">
                    </div>
                </div>
            </div>

            <!-- Status Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                <label class="flex items-center justify-between cursor-pointer">
                    <span class="text-gray-700 dark:text-gray-300 font-medium">เปิดใช้งานทันที</span>
                    <div class="relative">
                        <input type="checkbox" name="is_active" value="1" checked class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 dark:peer-focus:ring-orange-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-orange-500"></div>
                    </div>
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    class="w-full px-6 py-3 bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 text-white font-semibold rounded-xl hover:from-amber-600 hover:via-orange-600 hover:to-red-600 transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                สร้างคูปอง
            </button>
        </div>
    </div>
</form>

@push('scripts')
<style>
@keyframes blob {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
}
.animate-blob { animation: blob 7s infinite; }
.animation-delay-2000 { animation-delay: 2s; }
</style>
<script>
function generateCode() {
    fetch('{{ route("admin.coupons.generate-code") }}')
        .then(r => r.json())
        .then(data => {
            document.getElementById('couponCode').value = data.code;
        });
}

function toggleMaxDiscount() {
    const type = document.getElementById('discountType').value;
    const unit = document.getElementById('discountUnit');
    const maxGroup = document.getElementById('maxDiscountGroup');

    if (type === 'percentage') {
        unit.textContent = '%';
        maxGroup.style.display = 'block';
    } else {
        unit.textContent = '฿';
        maxGroup.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', toggleMaxDiscount);
</script>
@endpush
@endsection
