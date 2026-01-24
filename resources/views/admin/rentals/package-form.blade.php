@extends($adminLayout ?? 'layouts.admin')

@section('title', $package ? 'แก้ไขแพ็กเกจ' : 'สร้างแพ็กเกจใหม่')
@section('page-title', $package ? 'แก้ไขแพ็กเกจ' : 'สร้างแพ็กเกจใหม่')

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
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-pink-500 via-rose-500 to-red-500 p-6 sm:p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="absolute top-0 left-0 w-72 h-72 bg-pink-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
    <div class="absolute top-0 right-0 w-72 h-72 bg-rose-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-8 left-20 w-72 h-72 bg-red-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.rentals.packages') }}" class="p-2 bg-white/20 backdrop-blur-sm rounded-xl hover:bg-white/30 transition-all">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
                    <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($package)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            @endif
                        </svg>
                    </div>
                    {{ $package ? 'แก้ไขแพ็กเกจ' : 'สร้างแพ็กเกจใหม่' }}
                </h1>
                <p class="mt-2 text-white/80 text-sm sm:text-base">{{ $package ? 'แก้ไขข้อมูลแพ็กเกจ: ' . $package->display_name : 'เพิ่มแพ็กเกจใหม่เข้าสู่ระบบ' }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Form Card -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
    <form action="{{ $package ? route('admin.rentals.packages.update', $package) : route('admin.rentals.packages.store') }}"
          method="POST">
        @csrf
        @if($package)
            @method('PUT')
        @endif

        <div class="p-6 sm:p-8 space-y-8">
            <!-- Basic Info Section -->
            <div>
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-pink-400 to-rose-600 flex items-center justify-center shadow-lg mr-4">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">ข้อมูลพื้นฐาน</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อ (อังกฤษ) *</label>
                        <input type="text" name="name" value="{{ old('name', $package?->name) }}" required
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 dark:bg-gray-700 dark:text-white transition-all @error('name') border-red-500 @enderror"
                               placeholder="เช่น Basic Plan">
                        @error('name')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อ (ไทย) *</label>
                        <input type="text" name="name_th" value="{{ old('name_th', $package?->name_th) }}" required
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 dark:bg-gray-700 dark:text-white transition-all @error('name_th') border-red-500 @enderror"
                               placeholder="เช่น แพ็กเกจพื้นฐาน">
                        @error('name_th')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รายละเอียด (อังกฤษ)</label>
                        <textarea name="description" rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 dark:bg-gray-700 dark:text-white transition-all"
                                  placeholder="รายละเอียดแพ็กเกจ...">{{ old('description', $package?->description) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รายละเอียด (ไทย)</label>
                        <textarea name="description_th" rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 dark:bg-gray-700 dark:text-white transition-all"
                                  placeholder="รายละเอียดแพ็กเกจ...">{{ old('description_th', $package?->description_th) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Pricing Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center shadow-lg mr-4">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">ราคา</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ราคา (บาท) *</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">฿</span>
                            <input type="number" name="price" value="{{ old('price', $package?->price) }}" min="0" step="0.01" required
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 dark:bg-gray-700 dark:text-white transition-all"
                                   placeholder="0.00">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ราคาเดิม (สำหรับแสดงส่วนลด)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">฿</span>
                            <input type="number" name="original_price" value="{{ old('original_price', $package?->original_price) }}" min="0" step="0.01"
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 dark:bg-gray-700 dark:text-white transition-all"
                                   placeholder="0.00">
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">เว้นว่างหากไม่ต้องการแสดงส่วนลด</p>
                    </div>
                </div>
            </div>

            <!-- Duration Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center shadow-lg mr-4">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">ระยะเวลา</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภท *</label>
                        <select name="duration_type" required
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 dark:bg-gray-700 dark:text-white transition-all">
                            <option value="hourly" {{ old('duration_type', $package?->duration_type) === 'hourly' ? 'selected' : '' }}>ชั่วโมง</option>
                            <option value="daily" {{ old('duration_type', $package?->duration_type) === 'daily' ? 'selected' : '' }}>วัน</option>
                            <option value="weekly" {{ old('duration_type', $package?->duration_type) === 'weekly' ? 'selected' : '' }}>สัปดาห์</option>
                            <option value="monthly" {{ old('duration_type', $package?->duration_type) === 'monthly' ? 'selected' : '' }}>เดือน</option>
                            <option value="yearly" {{ old('duration_type', $package?->duration_type) === 'yearly' ? 'selected' : '' }}>ปี</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวน *</label>
                        <input type="number" name="duration_value" value="{{ old('duration_value', $package?->duration_value ?? 1) }}" min="1" required
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 dark:bg-gray-700 dark:text-white transition-all">
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-400 to-violet-600 flex items-center justify-center shadow-lg mr-4">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">คุณสมบัติ</h2>
                </div>
                <div id="features-container" class="space-y-3">
                    @php
                        $features = old('features', $package?->features ?? ['']);
                    @endphp
                    @foreach($features as $index => $feature)
                        <div class="flex items-center gap-3 feature-row">
                            <div class="flex-1">
                                <input type="text" name="features[]" value="{{ $feature }}"
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 dark:bg-gray-700 dark:text-white transition-all"
                                       placeholder="เช่น โพสต์ได้ไม่จำกัด">
                            </div>
                            <button type="button" onclick="removeFeature(this)"
                                    class="p-3 text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-xl transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
                <button type="button" onclick="addFeature()"
                        class="mt-4 inline-flex items-center px-4 py-2 text-pink-600 dark:text-pink-400 hover:bg-pink-50 dark:hover:bg-pink-900/30 rounded-xl transition-colors font-medium">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    เพิ่มคุณสมบัติ
                </button>
            </div>

            <!-- Limits Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-600 flex items-center justify-center shadow-lg mr-4">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">โควต้า/จำกัด</h2>
                </div>
                @php
                    $limits = old('limits', $package?->limits ?? []);
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนโพสต์/เดือน</label>
                        <input type="number" name="limits[max_posts]" value="{{ $limits['max_posts'] ?? '' }}" min="0"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 dark:bg-gray-700 dark:text-white transition-all"
                               placeholder="ไม่จำกัด">
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">เว้นว่าง = ไม่จำกัด</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนบัญชี</label>
                        <input type="number" name="limits[max_accounts]" value="{{ $limits['max_accounts'] ?? '' }}" min="0"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 dark:bg-gray-700 dark:text-white transition-all"
                               placeholder="ไม่จำกัด">
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">เว้นว่าง = ไม่จำกัด</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">AI Credits</label>
                        <input type="number" name="limits[ai_credits]" value="{{ $limits['ai_credits'] ?? '' }}" min="0"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 dark:bg-gray-700 dark:text-white transition-all"
                               placeholder="ไม่จำกัด">
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">เว้นว่าง = ไม่จำกัด</p>
                    </div>
                </div>
            </div>

            <!-- Flags Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-400 to-cyan-600 flex items-center justify-center shadow-lg mr-4">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">ตัวเลือก</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <label class="relative flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $package?->is_active ?? true) ? 'checked' : '' }}
                               class="w-5 h-5 text-pink-600 border-gray-300 dark:border-gray-600 rounded focus:ring-pink-500 dark:bg-gray-600">
                        <div class="ml-4">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">เปิดใช้งาน</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">แสดงแพ็กเกจให้ผู้ใช้เห็น</p>
                        </div>
                    </label>

                    <label class="relative flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group">
                        <input type="checkbox" name="is_featured" value="1"
                               {{ old('is_featured', $package?->is_featured) ? 'checked' : '' }}
                               class="w-5 h-5 text-pink-600 border-gray-300 dark:border-gray-600 rounded focus:ring-pink-500 dark:bg-gray-600">
                        <div class="ml-4">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">แนะนำ</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">แสดงเป็นแพ็กเกจแนะนำ</p>
                        </div>
                    </label>

                    <label class="relative flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group">
                        <input type="checkbox" name="is_popular" value="1"
                               {{ old('is_popular', $package?->is_popular) ? 'checked' : '' }}
                               class="w-5 h-5 text-pink-600 border-gray-300 dark:border-gray-600 rounded focus:ring-pink-500 dark:bg-gray-600">
                        <div class="ml-4">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">ยอดนิยม</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">แสดงป้ายยอดนิยม</p>
                        </div>
                    </label>
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ลำดับการแสดง</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $package?->sort_order ?? 0) }}" min="0"
                           class="w-32 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 dark:bg-gray-700 dark:text-white transition-all">
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">ตัวเลขน้อยจะแสดงก่อน</p>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="px-6 sm:px-8 py-6 bg-gray-50 dark:bg-gray-900/50 flex flex-col sm:flex-row justify-end gap-3 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('admin.rentals.packages') }}"
               class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium transition-colors text-center">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-pink-500 to-rose-600 text-white rounded-xl hover:from-pink-600 hover:to-rose-700 font-medium shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                {{ $package ? 'บันทึกการเปลี่ยนแปลง' : 'สร้างแพ็กเกจ' }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function addFeature() {
        const container = document.getElementById('features-container');
        const row = document.createElement('div');
        row.className = 'flex items-center gap-3 feature-row';
        row.innerHTML = `
            <div class="flex-1">
                <input type="text" name="features[]"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 dark:bg-gray-700 dark:text-white transition-all"
                       placeholder="เช่น โพสต์ได้ไม่จำกัด">
            </div>
            <button type="button" onclick="removeFeature(this)"
                    class="p-3 text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-xl transition-colors">
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
