@extends($adminLayout ?? 'layouts.admin')

@section('page-title', 'แก้ไขสมาชิก - ' . $user->name)

@section('content')
<!-- Premium Gradient Header -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-grid-white/10"></div>
    <div class="absolute top-0 right-0 -mt-16 -mr-16 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 -mb-16 -ml-16 w-64 h-64 bg-amber-400/20 rounded-full blur-3xl"></div>
    <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0 w-16 h-16 rounded-2xl overflow-hidden bg-white/20 flex items-center justify-center shadow-xl">
                @if($user->avatar_url)
                <img src="{{ $user->avatar_url }}" alt="" class="w-full h-full object-cover">
                @else
                <span class="text-2xl font-bold text-white">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                @endif
            </div>
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-bold text-white">แก้ไขสมาชิก</h1>
                </div>
                <p class="text-amber-100 text-lg">{{ $user->email }}</p>
            </div>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center px-5 py-2.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-medium rounded-xl transition-all duration-200 border border-white/25">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                กลับ
            </a>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('admin.users.update', $user) }}" class="max-w-4xl">
    @csrf
    @method('PUT')

    <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ข้อมูลสมาชิก</h3>
        </div>
        <div class="p-6 space-y-6">
            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ชื่อ <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                       class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all duration-200 @error('name') border-rose-500 @enderror">
                @error('name')
                <p class="mt-2 text-sm text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    อีเมล <span class="text-rose-500">*</span>
                </label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                       class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all duration-200 @error('email') border-rose-500 @enderror">
                @error('email')
                <p class="mt-2 text-sm text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Phone -->
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    เบอร์โทร
                </label>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                       class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all duration-200 @error('phone') border-rose-500 @enderror">
                @error('phone')
                <p class="mt-2 text-sm text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Role -->
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    บทบาท <span class="text-rose-500">*</span>
                </label>
                <select name="role" id="role" required
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all duration-200 @error('role') border-rose-500 @enderror">
                    <option value="user" {{ old('role', $user->role) === 'user' ? 'selected' : '' }}>ผู้ใช้ทั่วไป</option>
                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>แอดมิน</option>
                    @if(auth()->user()->isSuperAdmin())
                    <option value="super_admin" {{ old('role', $user->role) === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    @endif
                </select>
                @error('role')
                <p class="mt-2 text-sm text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        รหัสผ่านใหม่
                    </label>
                    <input type="password" name="password" id="password"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all duration-200 @error('password') border-rose-500 @enderror"
                           placeholder="เว้นว่างถ้าไม่ต้องการเปลี่ยน">
                    @error('password')
                    <p class="mt-2 text-sm text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ยืนยันรหัสผ่านใหม่
                    </label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all duration-200">
                </div>
            </div>

            <!-- Status -->
            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                           class="w-5 h-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500 transition-colors duration-200">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เปิดใช้งานบัญชี</span>
                </label>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 ml-8">ผู้ใช้ที่ถูกปิดใช้งานจะไม่สามารถเข้าสู่ระบบได้</p>
            </div>

            <!-- Marketing Preferences -->
            <div class="pt-6 border-t border-gray-100 dark:border-gray-700">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">การตลาด</h4>
                <div class="space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="marketing_email_enabled" value="1" {{ old('marketing_email_enabled', $user->marketing_email_enabled) ? 'checked' : '' }}
                               class="w-5 h-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500 transition-colors duration-200">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">รับข่าวสารทางอีเมล</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="marketing_line_enabled" value="1" {{ old('marketing_line_enabled', $user->marketing_line_enabled) ? 'checked' : '' }}
                               class="w-5 h-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500 transition-colors duration-200">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">รับข่าวสารทาง LINE</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row justify-end gap-3">
            <a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center justify-center px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-all duration-200">
                ยกเลิก
            </a>
            <button type="submit" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-semibold rounded-xl shadow-lg shadow-amber-500/30 hover:shadow-amber-500/40 transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                บันทึกการเปลี่ยนแปลง
            </button>
        </div>
    </div>
</form>
@endsection
