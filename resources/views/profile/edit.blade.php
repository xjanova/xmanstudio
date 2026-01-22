@extends('layouts.customer')

@section('title', 'โปรไฟล์')
@section('page-title', 'โปรไฟล์')
@section('page-description', 'จัดการข้อมูลส่วนตัวและความปลอดภัยบัญชี')

@section('content')
<div class="space-y-6">
    <!-- Profile Photo -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
            <h2 class="text-lg font-semibold text-gray-900">รูปโปรไฟล์</h2>
            <p class="text-sm text-gray-500 mt-1">อัปโหลดรูปภาพโปรไฟล์ของคุณ</p>
        </div>
        <div class="p-6">
            <div class="flex items-center gap-6">
                <!-- Current Avatar -->
                <div class="flex-shrink-0">
                    @if($user->avatar)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                             class="w-24 h-24 rounded-full object-cover border-4 border-primary-100 shadow-lg">
                    @else
                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center border-4 border-primary-100 shadow-lg">
                            <span class="text-white font-bold text-3xl">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        </div>
                    @endif
                </div>

                <!-- Upload Form -->
                <div class="flex-1">
                    <form method="post" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label for="avatar" class="block text-sm font-medium text-gray-700 mb-2">เลือกรูปภาพใหม่</label>
                            <input type="file" id="avatar" name="avatar" accept="image/*"
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 cursor-pointer">
                            <p class="mt-1 text-xs text-gray-500">PNG, JPG หรือ GIF ขนาดไม่เกิน 2MB</p>
                            @error('avatar')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium transition-colors text-sm">
                                อัปโหลดรูปภาพ
                            </button>
                            @if($user->avatar)
                                <button type="button" onclick="document.getElementById('delete-avatar-form').submit()"
                                        class="px-4 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg font-medium transition-colors text-sm">
                                    ลบรูปภาพ
                                </button>
                            @endif
                        </div>
                    </form>
                    <form id="delete-avatar-form" method="post" action="{{ route('profile.avatar.destroy') }}" class="hidden">
                        @csrf
                        @method('delete')
                    </form>
                    @if (session('status') === 'avatar-updated')
                        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                           class="mt-2 text-sm text-green-600 font-medium">
                            อัปโหลดรูปภาพแล้ว
                        </p>
                    @endif
                    @if (session('status') === 'avatar-removed')
                        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                           class="mt-2 text-sm text-green-600 font-medium">
                            ลบรูปภาพแล้ว
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Information -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
            <h2 class="text-lg font-semibold text-gray-900">ข้อมูลโปรไฟล์</h2>
            <p class="text-sm text-gray-500 mt-1">อัปเดตข้อมูลโปรไฟล์และอีเมลของคุณ</p>
        </div>
        <div class="p-6">
            <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                @csrf
            </form>

            <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
                @csrf
                @method('patch')

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">ชื่อ</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-sm text-yellow-800">
                                อีเมลของคุณยังไม่ได้รับการยืนยัน
                                <button form="send-verification" class="underline text-yellow-700 hover:text-yellow-900 font-medium ml-1">
                                    คลิกเพื่อส่งลิงก์ยืนยันอีกครั้ง
                                </button>
                            </p>
                            @if (session('status') === 'verification-link-sent')
                                <p class="mt-2 text-sm text-green-600 font-medium">
                                    ส่งลิงก์ยืนยันไปยังอีเมลของคุณแล้ว
                                </p>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <button type="submit" class="px-6 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium transition-colors">
                        บันทึกข้อมูล
                    </button>
                    @if (session('status') === 'profile-updated')
                        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                           class="text-sm text-green-600 font-medium">
                            บันทึกแล้ว
                        </p>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- LINE Connection -->
    @if (\App\Models\Setting::getValue('line_login_enabled', false))
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-green-50 to-white">
            <h2 class="text-lg font-semibold text-gray-900">เชื่อมต่อ LINE</h2>
            <p class="text-sm text-gray-500 mt-1">เชื่อมต่อบัญชี LINE เพื่อรับการแจ้งเตือนและเข้าสู่ระบบได้ง่ายขึ้น</p>
        </div>
        <div class="p-6">
            @if($user->line_uid)
                <div class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center gap-4">
                        @if($user->line_picture_url)
                            <img src="{{ $user->line_picture_url }}" alt="LINE Profile"
                                 class="w-12 h-12 rounded-full border-2 border-green-300">
                        @else
                            <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                </svg>
                            </div>
                        @endif
                        <div>
                            <p class="font-medium text-green-800">{{ $user->line_display_name ?? 'LINE Account' }}</p>
                            <p class="text-sm text-green-600">เชื่อมต่อแล้ว</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('line.unlink') }}" onsubmit="return confirm('ต้องการยกเลิกการเชื่อมต่อ LINE หรือไม่?')">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg font-medium transition-colors text-sm">
                            ยกเลิกการเชื่อมต่อ
                        </button>
                    </form>
                </div>
            @else
                <div class="text-center py-6">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                    </div>
                    <p class="text-gray-600 mb-4">ยังไม่ได้เชื่อมต่อบัญชี LINE</p>
                    <a href="{{ route('line.redirect', ['link' => 1]) }}"
                       class="inline-flex items-center px-6 py-3 rounded-xl text-white font-medium transition-colors"
                       style="background-color: #06C755;">
                        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                        เชื่อมต่อ LINE
                    </a>
                </div>
            @endif
            @if (session('status') === 'line-connected')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                   class="mt-4 text-sm text-green-600 font-medium text-center">
                    เชื่อมต่อ LINE สำเร็จ
                </p>
            @endif
        </div>
    </div>
    @endif

    <!-- Notification Settings -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-white">
            <h2 class="text-lg font-semibold text-gray-900">การแจ้งเตือน</h2>
            <p class="text-sm text-gray-500 mt-1">เลือกรับการแจ้งเตือนผ่านช่องทางที่ต้องการ</p>
        </div>
        <div class="p-6">
            <form method="POST" action="{{ route('profile.notifications.update') }}">
                @csrf

                @php
                    $prefs = $user->notification_preferences ?? [];
                @endphp

                <!-- Marketing Preferences -->
                <div class="mb-6 pb-6 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">การตลาดและโปรโมชั่น</h3>
                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="marketing_email_enabled" value="1"
                                   {{ $user->marketing_email_enabled ? 'checked' : '' }}
                                   class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <span class="ml-3 text-sm text-gray-700">รับข่าวสารและโปรโมชั่นทาง Email</span>
                        </label>
                        @if($user->line_uid)
                        <label class="flex items-center">
                            <input type="checkbox" name="marketing_line_enabled" value="1"
                                   {{ $user->marketing_line_enabled ? 'checked' : '' }}
                                   class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <span class="ml-3 text-sm text-gray-700">รับข่าวสารและโปรโมชั่นทาง LINE</span>
                        </label>
                        @endif
                    </div>
                </div>

                <!-- Notification Types -->
                <div class="space-y-6">
                    <!-- License Expiry -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">แจ้งเตือน License ใกล้หมดอายุ</h3>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="notify_license_email" value="1"
                                       {{ ($prefs['license_expiry']['email'] ?? true) ? 'checked' : '' }}
                                       class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-600">Email</span>
                            </label>
                            @if($user->line_uid)
                            <label class="flex items-center">
                                <input type="checkbox" name="notify_license_line" value="1"
                                       {{ ($prefs['license_expiry']['line'] ?? true) ? 'checked' : '' }}
                                       class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-600">LINE</span>
                            </label>
                            @endif
                        </div>
                    </div>

                    <!-- Order Status -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">อัปเดตสถานะคำสั่งซื้อ</h3>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="notify_order_email" value="1"
                                       {{ ($prefs['order_status']['email'] ?? true) ? 'checked' : '' }}
                                       class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-600">Email</span>
                            </label>
                            @if($user->line_uid)
                            <label class="flex items-center">
                                <input type="checkbox" name="notify_order_line" value="1"
                                       {{ ($prefs['order_status']['line'] ?? true) ? 'checked' : '' }}
                                       class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-600">LINE</span>
                            </label>
                            @endif
                        </div>
                    </div>

                    <!-- Promotions -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">โปรโมชั่นและส่วนลด</h3>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="notify_promo_email" value="1"
                                       {{ ($prefs['promotions']['email'] ?? true) ? 'checked' : '' }}
                                       class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-600">Email</span>
                            </label>
                            @if($user->line_uid)
                            <label class="flex items-center">
                                <input type="checkbox" name="notify_promo_line" value="1"
                                       {{ ($prefs['promotions']['line'] ?? true) ? 'checked' : '' }}
                                       class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-600">LINE</span>
                            </label>
                            @endif
                        </div>
                    </div>

                    <!-- New Products -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">สินค้าใหม่</h3>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="notify_products_email" value="1"
                                       {{ ($prefs['new_products']['email'] ?? true) ? 'checked' : '' }}
                                       class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-600">Email</span>
                            </label>
                            @if($user->line_uid)
                            <label class="flex items-center">
                                <input type="checkbox" name="notify_products_line" value="1"
                                       {{ ($prefs['new_products']['line'] ?? true) ? 'checked' : '' }}
                                       class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-600">LINE</span>
                            </label>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-6 mt-6 border-t border-gray-200">
                    <button type="submit" class="px-6 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium transition-colors">
                        บันทึกการตั้งค่า
                    </button>
                    @if (session('status') === 'notifications-updated')
                        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                           class="text-sm text-green-600 font-medium">
                            บันทึกแล้ว
                        </p>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Update Password -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
            <h2 class="text-lg font-semibold text-gray-900">เปลี่ยนรหัสผ่าน</h2>
            <p class="text-sm text-gray-500 mt-1">ใช้รหัสผ่านที่ยาวและสุ่มเพื่อความปลอดภัย</p>
        </div>
        <div class="p-6">
            <form method="post" action="{{ route('password.update') }}" class="space-y-5">
                @csrf
                @method('put')

                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่านปัจจุบัน</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                    @error('current_password', 'updatePassword')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่านใหม่</label>
                    <input id="password" name="password" type="password" autocomplete="new-password"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                    @error('password', 'updatePassword')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">ยืนยันรหัสผ่านใหม่</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                    @error('password_confirmation', 'updatePassword')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <button type="submit" class="px-6 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium transition-colors">
                        เปลี่ยนรหัสผ่าน
                    </button>
                    @if (session('status') === 'password-updated')
                        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                           class="text-sm text-green-600 font-medium">
                            เปลี่ยนรหัสผ่านแล้ว
                        </p>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Account -->
    <div class="bg-white rounded-xl shadow-sm border border-red-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-red-100 bg-gradient-to-r from-red-50 to-white">
            <h2 class="text-lg font-semibold text-red-700">ลบบัญชี</h2>
            <p class="text-sm text-red-600 mt-1">เมื่อลบบัญชีแล้ว ข้อมูลทั้งหมดจะถูกลบอย่างถาวร</p>
        </div>
        <div class="p-6">
            <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
                    class="px-6 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium transition-colors">
                ลบบัญชีของฉัน
            </button>

            <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
                <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
                    @csrf
                    @method('delete')

                    <h2 class="text-lg font-semibold text-gray-900">
                        คุณแน่ใจหรือไม่ที่จะลบบัญชี?
                    </h2>

                    <p class="mt-2 text-sm text-gray-600">
                        เมื่อลบบัญชีแล้ว ข้อมูลและทรัพยากรทั้งหมดจะถูกลบอย่างถาวร กรุณากรอกรหัสผ่านเพื่อยืนยันการลบบัญชี
                    </p>

                    <div class="mt-6">
                        <label for="password_delete" class="sr-only">รหัสผ่าน</label>
                        <input id="password_delete" name="password" type="password" placeholder="รหัสผ่าน"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        @error('password', 'userDeletion')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" x-on:click="$dispatch('close')"
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 font-medium transition-colors">
                            ยกเลิก
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium transition-colors">
                            ลบบัญชี
                        </button>
                    </div>
                </form>
            </x-modal>
        </div>
    </div>
</div>
@endsection
