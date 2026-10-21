@extends($customerLayout ?? 'layouts.customer')

@section('title', 'โปรไฟล์')
@section('page-title', 'โปรไฟล์ / Profile')
@section('page-description', 'จัดการข้อมูลส่วนตัวและความปลอดภัยบัญชี / Manage your personal information and account security')

@section('content')
<div class="space-y-6">
    <!-- Profile Photo -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
            <h2 class="text-lg font-semibold text-gray-900"><x-bi th="รูปโปรไฟล์" en="Profile Photo" /></h2>
            <p class="text-sm text-gray-500 mt-1"><x-bi th="อัปโหลดรูปภาพโปรไฟล์ของคุณ" en="Upload your profile photo" /></p>
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
                            <label for="avatar" class="block text-sm font-medium text-gray-700 mb-2"><x-bi th="เลือกรูปภาพใหม่" en="Choose a new image" /></label>
                            <input type="file" id="avatar" name="avatar" accept="image/*"
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 cursor-pointer">
                            <p class="mt-1 text-xs text-gray-500"><x-bi th="PNG, JPG หรือ GIF ขนาดไม่เกิน 2MB" en="PNG, JPG or GIF, up to 2MB" /></p>
                            @error('avatar')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium transition-colors text-sm">
                                <x-bi th="อัปโหลดรูปภาพ" en="Upload Photo" />
                            </button>
                            @if($user->avatar)
                                <button type="button" onclick="document.getElementById('delete-avatar-form').submit()"
                                        class="px-4 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg font-medium transition-colors text-sm">
                                    <x-bi th="ลบรูปภาพ" en="Remove Photo" />
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
                            <x-bi th="อัปโหลดรูปภาพแล้ว" en="Photo uploaded" />
                        </p>
                    @endif
                    @if (session('status') === 'avatar-removed')
                        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                           class="mt-2 text-sm text-green-600 font-medium">
                            <x-bi th="ลบรูปภาพแล้ว" en="Photo removed" />
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Information -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
            <h2 class="text-lg font-semibold text-gray-900"><x-bi th="ข้อมูลโปรไฟล์" en="Profile Information" /></h2>
            <p class="text-sm text-gray-500 mt-1"><x-bi th="อัปเดตข้อมูลโปรไฟล์และอีเมลของคุณ" en="Update your profile information and email address" /></p>
        </div>
        <div class="p-6">
            <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                @csrf
            </form>

            <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
                @csrf
                @method('patch')

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1"><x-bi k="common.name" /></label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1"><x-bi k="common.email" /></label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-sm text-yellow-800">
                                <x-bi th="อีเมลของคุณยังไม่ได้รับการยืนยัน" en="Your email address is not verified" />
                                <button form="send-verification" class="underline text-yellow-700 hover:text-yellow-900 font-medium ml-1">
                                    <x-bi th="คลิกเพื่อส่งลิงก์ยืนยันอีกครั้ง" en="Click here to resend the verification link" />
                                </button>
                            </p>
                            @if (session('status') === 'verification-link-sent')
                                <p class="mt-2 text-sm text-green-600 font-medium">
                                    <x-bi th="ส่งลิงก์ยืนยันไปยังอีเมลของคุณแล้ว" en="A new verification link has been sent to your email address" />
                                </p>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <button type="submit" class="px-6 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium transition-colors">
                        <x-bi th="บันทึกข้อมูล" en="Save Changes" />
                    </button>
                    @if (session('status') === 'profile-updated')
                        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                           class="text-sm text-green-600 font-medium">
                            <x-bi th="บันทึกแล้ว" en="Saved" />
                        </p>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Google / LINE / Telegram, with the disconnect guard. --}}
    @include('profile.partials.social-accounts')

    <!-- Notification Settings -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-white">
            <h2 class="text-lg font-semibold text-gray-900"><x-bi th="การแจ้งเตือน" en="Notifications" /></h2>
            <p class="text-sm text-gray-500 mt-1"><x-bi th="เลือกรับการแจ้งเตือนผ่านช่องทางที่ต้องการ" en="Choose how you want to receive notifications" /></p>
        </div>
        <div class="p-6">
            <form method="POST" action="{{ route('profile.notifications.update') }}">
                @csrf

                @php
                    $prefs = $user->notification_preferences ?? [];
                @endphp

                <!-- Marketing Preferences -->
                <div class="mb-6 pb-6 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4"><x-bi th="การตลาดและโปรโมชั่น" en="Marketing &amp; Promotions" /></h3>
                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="marketing_email_enabled" value="1"
                                   {{ $user->marketing_email_enabled ? 'checked' : '' }}
                                   class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <span class="ml-3 text-sm text-gray-700"><x-bi th="รับข่าวสารและโปรโมชั่นทาง Email" en="Receive news and promotions via Email" /></span>
                        </label>
                        @if($user->line_uid)
                        <label class="flex items-center">
                            <input type="checkbox" name="marketing_line_enabled" value="1"
                                   {{ $user->marketing_line_enabled ? 'checked' : '' }}
                                   class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <span class="ml-3 text-sm text-gray-700"><x-bi th="รับข่าวสารและโปรโมชั่นทาง LINE" en="Receive news and promotions via LINE" /></span>
                        </label>
                        @endif
                    </div>
                </div>

                <!-- Notification Types -->
                <div class="space-y-6">
                    <!-- License Expiry -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-3"><x-bi th="แจ้งเตือน License ใกล้หมดอายุ" en="License Expiry Alerts" /></h3>
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
                        <h3 class="text-sm font-semibold text-gray-900 mb-3"><x-bi th="อัปเดตสถานะคำสั่งซื้อ" en="Order Status Updates" /></h3>
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
                        <h3 class="text-sm font-semibold text-gray-900 mb-3"><x-bi th="โปรโมชั่นและส่วนลด" en="Promotions &amp; Discounts" /></h3>
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
                        <h3 class="text-sm font-semibold text-gray-900 mb-3"><x-bi th="สินค้าใหม่" en="New Products" /></h3>
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
                        <x-bi th="บันทึกการตั้งค่า" en="Save Settings" />
                    </button>
                    @if (session('status') === 'notifications-updated')
                        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                           class="text-sm text-green-600 font-medium">
                            <x-bi th="บันทึกแล้ว" en="Saved" />
                        </p>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Update Password -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
            <h2 class="text-lg font-semibold text-gray-900"><x-bi th="เปลี่ยนรหัสผ่าน" en="Change Password" /></h2>
            <p class="text-sm text-gray-500 mt-1"><x-bi th="ใช้รหัสผ่านที่ยาวและสุ่มเพื่อความปลอดภัย" en="Use a long, random password to stay secure" /></p>
        </div>
        <div class="p-6">
            <form method="post" action="{{ route('password.update') }}" class="space-y-5">
                @csrf
                @method('put')

                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1"><x-bi k="common.current_password" /></label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                    @error('current_password', 'updatePassword')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1"><x-bi k="common.new_password" /></label>
                    <input id="password" name="password" type="password" autocomplete="new-password"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                    @error('password', 'updatePassword')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1"><x-bi th="ยืนยันรหัสผ่านใหม่" en="Confirm New Password" /></label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                    @error('password_confirmation', 'updatePassword')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <button type="submit" class="px-6 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium transition-colors">
                        <x-bi th="เปลี่ยนรหัสผ่าน" en="Change Password" />
                    </button>
                    @if (session('status') === 'password-updated')
                        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                           class="text-sm text-green-600 font-medium">
                            <x-bi th="เปลี่ยนรหัสผ่านแล้ว" en="Password changed" />
                        </p>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Account -->
    <div class="bg-white rounded-xl shadow-sm border border-red-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-red-100 bg-gradient-to-r from-red-50 to-white">
            <h2 class="text-lg font-semibold text-red-700"><x-bi th="ลบบัญชี" en="Delete Account" /></h2>
            <p class="text-sm text-red-600 mt-1"><x-bi th="เมื่อลบบัญชีแล้ว ข้อมูลทั้งหมดจะถูกลบอย่างถาวร" en="Once your account is deleted, all of its data will be permanently removed" /></p>
        </div>
        <div class="p-6">
            <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
                    class="px-6 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium transition-colors">
                <x-bi th="ลบบัญชีของฉัน" en="Delete My Account" />
            </button>

            <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
                <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
                    @csrf
                    @method('delete')

                    <h2 class="text-lg font-semibold text-gray-900">
                        <x-bi th="คุณแน่ใจหรือไม่ที่จะลบบัญชี?" en="Are you sure you want to delete your account?" />
                    </h2>

                    <p class="mt-2 text-sm text-gray-600">
                        <x-bi th="เมื่อลบบัญชีแล้ว ข้อมูลและทรัพยากรทั้งหมดจะถูกลบอย่างถาวร กรุณากรอกรหัสผ่านเพื่อยืนยันการลบบัญชี" en="Once your account is deleted, all of its data and resources will be permanently removed. Please enter your password to confirm." />
                    </p>

                    <div class="mt-6">
                        <label for="password_delete" class="sr-only"><x-bi k="common.password" /></label>
                        <input id="password_delete" name="password" type="password" placeholder="{{ bi('common.password') }}"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        @error('password', 'userDeletion')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" x-on:click="$dispatch('close')"
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 font-medium transition-colors">
                            <x-bi k="common.cancel" />
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium transition-colors">
                            <x-bi th="ลบบัญชี" en="Delete Account" />
                        </button>
                    </div>
                </form>
            </x-modal>
        </div>
    </div>
</div>
@endsection
