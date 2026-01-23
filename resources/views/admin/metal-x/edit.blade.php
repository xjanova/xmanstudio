@extends($adminLayout ?? 'layouts.admin')

@section('title', 'แก้ไขสมาชิกทีม Metal-X')
@section('page-title', 'แก้ไขสมาชิก: ' . $metalX->name)

@section('content')
<div class="max-w-4xl">
    <form action="{{ route('admin.metal-x.update', $metalX) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลพื้นฐาน</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ (EN) <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $metalX->name) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ (TH)</label>
                    <input type="text" name="name_th" value="{{ old('name_th', $metalX->name_th) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ตำแหน่ง (EN) <span class="text-red-500">*</span></label>
                    <input type="text" name="role" value="{{ old('role', $metalX->role) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    @error('role')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ตำแหน่ง (TH)</label>
                    <input type="text" name="role_th" value="{{ old('role_th', $metalX->role_th) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">ประวัติย่อ (EN)</label>
                    <textarea name="bio" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('bio', $metalX->bio) }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">ประวัติย่อ (TH)</label>
                    <textarea name="bio_th" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('bio_th', $metalX->bio_th) }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">รูปภาพ</label>
                    @if($metalX->image)
                        <div class="mb-3">
                            <img src="{{ Storage::url($metalX->image) }}" alt="{{ $metalX->name }}"
                                 class="w-24 h-24 rounded-lg object-cover">
                            <p class="mt-1 text-sm text-gray-500">รูปภาพปัจจุบัน</p>
                        </div>
                    @endif
                    <input type="file" name="image" accept="image/*"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    <p class="mt-1 text-sm text-gray-500">รองรับไฟล์รูปภาพ ขนาดไม่เกิน 2MB (เว้นว่างถ้าไม่ต้องการเปลี่ยน)</p>
                    @error('image')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ลิงก์โซเชียลมีเดีย</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">YouTube</label>
                    <input type="url" name="youtube_url" value="{{ old('youtube_url', $metalX->youtube_url) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="https://youtube.com/...">
                    @error('youtube_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Facebook</label>
                    <input type="url" name="facebook_url" value="{{ old('facebook_url', $metalX->facebook_url) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="https://facebook.com/...">
                    @error('facebook_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Instagram</label>
                    <input type="url" name="instagram_url" value="{{ old('instagram_url', $metalX->instagram_url) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="https://instagram.com/...">
                    @error('instagram_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Twitter / X</label>
                    <input type="url" name="twitter_url" value="{{ old('twitter_url', $metalX->twitter_url) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="https://twitter.com/...">
                    @error('twitter_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">TikTok</label>
                    <input type="url" name="tiktok_url" value="{{ old('tiktok_url', $metalX->tiktok_url) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="https://tiktok.com/...">
                    @error('tiktok_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">การแสดงผล</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ลำดับ</label>
                    <input type="number" name="order" value="{{ old('order', $metalX->order) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div class="flex items-end">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $metalX->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">เปิดใช้งาน</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('admin.metal-x.index') }}"
               class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">ยกเลิก</a>
            <button type="submit"
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">บันทึก</button>
        </div>
    </form>
</div>
@endsection
