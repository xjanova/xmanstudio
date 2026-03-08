@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ตั้งค่า Cloudflare Turnstile')
@section('page-title', 'ตั้งค่า Cloudflare Turnstile')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 p-8 shadow-2xl">
        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">Cloudflare Turnstile</h1>
                    <p class="text-amber-100 text-lg">ป้องกันบอทสำหรับฟอร์มต่างๆ ในเว็บไซต์</p>
                </div>
                <div class="hidden md:block">
                    <div class="w-16 h-16 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-6 py-4 rounded-xl flex items-center shadow-lg">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center mr-4">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-6 py-4 rounded-xl shadow-lg">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.turnstile.update') }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Global Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700 mb-6">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-400 to-amber-600 flex items-center justify-center mr-4 shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">การตั้งค่าทั่วไป</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ตั้งค่า API Key จาก Cloudflare Turnstile Dashboard</p>
                </div>
            </div>

            <!-- Global Enable/Disable -->
            <div class="mb-6 p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                <label class="flex items-center justify-between cursor-pointer">
                    <div>
                        <span class="text-base font-semibold text-gray-900 dark:text-white">เปิดใช้งาน Turnstile</span>
                        <p class="text-sm text-gray-500 dark:text-gray-400">เปิด/ปิดระบบป้องกันบอททั้งหมด</p>
                    </div>
                    <div class="relative">
                        <input type="hidden" name="turnstile_enabled" value="0">
                        <input type="checkbox" name="turnstile_enabled" value="1"
                               class="sr-only peer"
                               id="turnstile_enabled"
                               {{ old('turnstile_enabled', $settings['turnstile_enabled']) ? 'checked' : '' }}>
                        <label for="turnstile_enabled" class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 dark:peer-focus:ring-orange-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-500 peer-checked:bg-orange-500 cursor-pointer block"></label>
                    </div>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Site Key</label>
                    <input type="text" name="turnstile_site_key" value="{{ old('turnstile_site_key', $settings['turnstile_site_key']) }}"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent font-mono text-sm"
                           placeholder="0x4AAAAAAXXXXXXXXXXXXXXX">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">คีย์สาธารณะจาก Cloudflare Turnstile Dashboard</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Secret Key</label>
                    <input type="password" name="turnstile_secret_key"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent font-mono text-sm"
                           placeholder="{{ $settings['turnstile_secret_key'] ? '••••••••••••••••' : 'ใส่ Secret Key' }}">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">คีย์ลับ (เว้นว่างไว้หากไม่ต้องการเปลี่ยน)</p>
                </div>
            </div>
        </div>

        <!-- Section Toggles -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700 mb-6">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center mr-4 shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">เปิด/ปิดตามส่วน</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">เลือกว่าจะใช้ Turnstile ในส่วนไหนบ้าง</p>
                </div>
            </div>

            <div class="space-y-4">
                @php
                    $sections = [
                        ['key' => 'turnstile_login', 'label' => 'หน้าเข้าสู่ระบบ (Login)', 'desc' => 'แสดง Turnstile ในฟอร์มล็อกอิน', 'icon' => 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1'],
                        ['key' => 'turnstile_register', 'label' => 'หน้าสมัครสมาชิก (Register)', 'desc' => 'แสดง Turnstile ในฟอร์มสมัครสมาชิก', 'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'],
                        ['key' => 'turnstile_checkout', 'label' => 'หน้าสั่งซื้อ (Checkout)', 'desc' => 'แสดง Turnstile ในฟอร์มสั่งซื้อสินค้า', 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z'],
                        ['key' => 'turnstile_support', 'label' => 'ส่งตั๋วซัพพอร์ต (Support)', 'desc' => 'แสดง Turnstile ในฟอร์มสร้างตั๋วซัพพอร์ต', 'icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z'],
                    ];
                @endphp

                @foreach($sections as $section)
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                        <label class="flex items-center justify-between cursor-pointer">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-500 flex items-center justify-center mr-4">
                                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $section['icon'] }}"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="text-base font-semibold text-gray-900 dark:text-white">{{ $section['label'] }}</span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $section['desc'] }}</p>
                                </div>
                            </div>
                            <div class="relative ml-4">
                                <input type="hidden" name="{{ $section['key'] }}" value="0">
                                <input type="checkbox" name="{{ $section['key'] }}" value="1"
                                       class="sr-only peer"
                                       id="{{ $section['key'] }}"
                                       {{ old($section['key'], $settings[$section['key']]) ? 'checked' : '' }}>
                                <label for="{{ $section['key'] }}" class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-500 peer-checked:bg-blue-500 cursor-pointer block"></label>
                            </div>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Info Box -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-6 mb-6">
            <div class="flex items-start">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center mr-4 flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-blue-900 dark:text-blue-200 mb-1">วิธีตั้งค่า Cloudflare Turnstile</h4>
                    <ol class="text-sm text-blue-800 dark:text-blue-300 space-y-1 list-decimal list-inside">
                        <li>ไปที่ <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" class="underline font-medium">Cloudflare Dashboard &rarr; Turnstile</a></li>
                        <li>กด "Add Widget" แล้วใส่ชื่อเว็บไซต์และโดเมน</li>
                        <li>คัดลอก Site Key และ Secret Key มาวางในช่องด้านบน</li>
                        <li>เปิดใช้งานส่วนที่ต้องการป้องกันบอท</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>
@endsection
